<?php

namespace backend\modules\customers\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use common\models\WizardDraft;
use common\helper\Permissions;
use backend\modules\customers\components\VisionService;

/**
 * Customer Onboarding Wizard — V2.
 *
 * Server-side, draft-backed, 4-step wizard that replaces the legacy
 * `smart-onboarding.js` flow living under CustomersController::actionCreate.
 *
 * Single source of truth: every change is persisted to `wizard_drafts`
 * (key = "customer_create"). The browser never owns canonical state.
 *
 * URL space:
 *   GET    /customers/wizard            → start (creates or resumes auto draft)
 *   GET    /customers/wizard/start      → start (alias)
 *   GET    /customers/wizard/step?n=N   → render step N partial
 *   POST   /customers/wizard/save       → save partial step data + advance
 *   POST   /customers/wizard/validate   → validate single step (AJAX)
 *   POST   /customers/wizard/finish     → commit draft → real customer
 *   POST   /customers/wizard/discard    → drop the auto draft
 *   GET    /customers/wizard/drafts     → list manual saved drafts
 *   GET    /customers/wizard/resume?id  → load a manual saved draft
 *   POST   /customers/wizard/scan       → OCR endpoint (smart-media bridge)
 */
class WizardController extends Controller
{
    const DRAFT_KEY = 'customer_create';
    const TOTAL_STEPS = 4;

    /**
     * Hard cap on a single step's POST body (after PHP's own post_max_size).
     * 256 KB is generous: the heaviest step is identity (~1 KB serialized).
     */
    const MAX_STEP_PAYLOAD_BYTES = 262144;

    /** Hard cap on the cumulative draft JSON kept in the DB. */
    const MAX_DRAFT_BYTES = 524288;

    /** Hard cap on uploaded scan image (10 MB — matches legacy SmartMedia cap). */
    const MAX_SCAN_BYTES = 10 * 1024 * 1024;

    /** Whitelisted MIME types for the smart-scan endpoint. */
    const SCAN_ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    public $defaultAction = 'start';

    /** {@inheritdoc} */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [[
                    'allow' => true,
                    'roles' => ['@'],
                    'matchCallback' => function ($rule, $action) {
                        return Permissions::can(Permissions::CUST_CREATE);
                    },
                ]],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'save'     => ['POST'],
                    'validate' => ['POST'],
                    'finish'   => ['POST'],
                    'discard'  => ['POST'],
                    'scan'     => ['POST'],
                ],
            ],
        ];
    }

    /* ════════════════════════════════════════════════════════════════
       ENTRY POINTS
       ════════════════════════════════════════════════════════════════ */

    /**
     * Entry — load (or create) the user's auto-draft and render the wizard
     * shell at the saved step.
     */
    public function actionStart()
    {
        $draft = $this->getOrCreateAutoDraft();
        $payload = $this->decodePayload($draft);
        $currentStep = (int)($payload['_step'] ?? 1);
        if ($currentStep < 1 || $currentStep > self::TOTAL_STEPS) {
            $currentStep = 1;
        }

        return $this->render('layout', [
            'draft'       => $draft,
            'payload'     => $payload,
            'currentStep' => $currentStep,
            'totalSteps'  => self::TOTAL_STEPS,
            'lookups'     => $this->loadLookups(),
        ]);
    }

    /**
     * Render a single step's partial (AJAX — used when navigating inside the
     * wizard without a full page reload).
     */
    public function actionStep($n = 1)
    {
        $n = (int)$n;
        if ($n < 1 || $n > self::TOTAL_STEPS) {
            throw new BadRequestHttpException('Invalid step number.');
        }

        Yii::$app->response->format = Response::FORMAT_HTML;

        $draft = $this->getOrCreateAutoDraft();
        $payload = $this->decodePayload($draft);

        return $this->renderPartial($this->stepView($n), [
            'draft'   => $draft,
            'payload' => $payload,
            'step'    => $n,
            'lookups' => $this->loadLookups(),
        ]);
    }

    /* ════════════════════════════════════════════════════════════════
       PERSISTENCE
       ════════════════════════════════════════════════════════════════ */

    /**
     * Save a single step's data into the auto-draft. Returns JSON with the
     * updated payload + any validation errors (server-side validation is
     * authoritative).
     */
    public function actionSave()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $request = Yii::$app->request;
        $step = (int)$request->post('step', 1);
        if ($step < 1 || $step > self::TOTAL_STEPS) {
            return ['ok' => false, 'error' => 'Invalid step.'];
        }

        $stepData = $request->post('data', []);
        if (!is_array($stepData)) {
            return ['ok' => false, 'error' => 'Bad payload.'];
        }

        // Defensive size caps to prevent draft-table abuse / DoS.
        $encoded = json_encode($stepData, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return ['ok' => false, 'error' => 'تعذّر ترميز البيانات.'];
        }
        if (strlen($encoded) > self::MAX_STEP_PAYLOAD_BYTES) {
            Yii::warning('Wizard step payload exceeded cap: ' . strlen($encoded), __METHOD__);
            return ['ok' => false, 'error' => 'حجم البيانات أكبر من المسموح به.'];
        }

        $draft = $this->getOrCreateAutoDraft();
        $payload = $this->decodePayload($draft);

        $payload["step{$step}"] = $stepData;
        $payload['_step'] = $step;
        $payload['_summary'] = $this->buildSummary($payload);
        $payload['_updated'] = time();

        $finalJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (strlen($finalJson) > self::MAX_DRAFT_BYTES) {
            return ['ok' => false, 'error' => 'تجاوزت المسودة الحجم الأقصى. احذف بعض البيانات.'];
        }

        WizardDraft::saveAutoDraft(Yii::$app->user->id, self::DRAFT_KEY, $finalJson);

        return [
            'ok'      => true,
            'step'    => $step,
            'summary' => $payload['_summary'],
        ];
    }

    /**
     * Validate a step's data without persisting (AJAX — used to gate "Next").
     * Per-step validators live in `validateStepN()` methods below.
     */
    public function actionValidate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $step = (int)Yii::$app->request->post('step', 1);
        $data = Yii::$app->request->post('data', []);
        if (!is_array($data)) {
            return ['ok' => false, 'errors' => ['_global' => 'Bad payload.']];
        }

        $method = "validateStep{$step}";
        if (!method_exists($this, $method)) {
            return ['ok' => true, 'errors' => []];
        }

        $errors = $this->{$method}($data);
        return ['ok' => empty($errors), 'errors' => $errors];
    }

    /**
     * Commit the draft → real Customer record (+ related sub-rows). On
     * success the auto-draft is cleared.
     */
    public function actionFinish()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $draft = WizardDraft::loadAutoDraft(Yii::$app->user->id, self::DRAFT_KEY);
        if (!$draft) {
            return ['ok' => false, 'error' => 'لا توجد مسودة لاعتمادها.'];
        }

        $payload = $this->decodePayload($draft);

        // The actual create-customer logic will be wired in Phase 4 once all
        // step partials exist. For now we return a clear "not yet" so the UI
        // can show a skeleton-mode banner during dev.
        return [
            'ok'      => false,
            'error'   => 'الاعتماد النهائي قيد التطوير — استخدم زر "حفظ كمسودة" حالياً.',
            'payload' => $payload,
        ];
    }

    /**
     * Discard the auto-draft entirely (user clicked "ابدأ من جديد").
     */
    public function actionDiscard()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        WizardDraft::clearAutoDraft(Yii::$app->user->id, self::DRAFT_KEY);
        return ['ok' => true];
    }

    /**
     * List all manually-saved drafts for the current user.
     */
    public function actionDrafts()
    {
        $items = WizardDraft::listSavedDrafts(Yii::$app->user->id, self::DRAFT_KEY);
        return $this->renderPartial('_drafts_list', ['items' => $items]);
    }

    /**
     * Resume a manually-saved draft (copies it into the auto slot and
     * redirects to the wizard shell).
     */
    public function actionResume($id)
    {
        $draft = WizardDraft::loadSavedDraft(Yii::$app->user->id, self::DRAFT_KEY, $id);
        if (!$draft) {
            throw new NotFoundHttpException('المسودة غير موجودة.');
        }
        WizardDraft::saveAutoDraft(Yii::$app->user->id, self::DRAFT_KEY, $draft->draft_data);
        return $this->redirect(['start']);
    }

    /**
     * Smart OCR scan — accepts an uploaded ID/passport image, sends it
     * directly to Gemini Vision via the shared VisionService, then maps
     * the structured fields back into the wizard's `Customers[*]` keys
     * (including resolving free-text city/citizen → lookup IDs).
     *
     * The browser uploads a single image as multipart "file"; we stream it
     * to Gemini WITHOUT persisting it to disk under `web/uploads/` (privacy:
     * user may abort the wizard, no orphan files left behind). The temp
     * file is deleted in a finally block.
     *
     * Response:
     *   { ok: true,
     *     fields: { 'Customers[name]': '...', 'Customers[id_number]': '...', ... },
     *     unmapped: { city: 'عمان', citizen: 'أردني' },   // text we couldn't resolve to an ID
     *     meta: { source: 'gemini-vision', elapsed_ms: 1234 } }
     *
     *   { ok: false, error: 'human-readable Arabic message' }
     */
    public function actionScan()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $startedAt = microtime(true);

        if (!VisionService::isEnabled()) {
            return [
                'ok'    => false,
                'error' => 'المسح الذكي غير مفعّل في إعدادات النظام (Google Cloud).',
            ];
        }

        // ── Read the optional `side` flag (front | back | auto). ──
        // Camera mode sends explicit side; file-upload mode defaults to "auto"
        // — which means: try to auto-detect from Gemini's response.
        $side = strtolower((string)Yii::$app->request->post('side', 'auto'));
        if (!in_array($side, ['front', 'back', 'auto'], true)) {
            $side = 'auto';
        }

        $file = UploadedFile::getInstanceByName('file');
        if (!$file) {
            return ['ok' => false, 'error' => 'لم يتم استلام ملف للمسح.'];
        }
        if ($file->error !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'خطأ في رفع الملف (#' . (int)$file->error . ').'];
        }
        if (!in_array($file->type, self::SCAN_ALLOWED_MIMES, true)) {
            return ['ok' => false, 'error' => 'نوع الملف غير مدعوم — استخدم JPG / PNG / WEBP / PDF.'];
        }
        if ($file->size > self::MAX_SCAN_BYTES) {
            return ['ok' => false, 'error' => 'حجم الملف أكبر من 10 ميجابايت.'];
        }

        // Save to runtime (NOT web/uploads) so a wizard abort leaves no
        // orphan files inside the public tree.
        $ext = strtolower($file->extension ?: 'jpg');
        $tmpPath = Yii::getAlias('@runtime') . '/wizard_scan_'
                 . Yii::$app->security->generateRandomString(8) . '.' . $ext;

        if (!$file->saveAs($tmpPath, false)) {
            return ['ok' => false, 'error' => 'تعذّر حفظ الملف للمعالجة.'];
        }

        // Don't hold the session lock during the multi-second Gemini call.
        Yii::$app->session->close();

        try {
            $extracted = VisionService::extractFromImage($tmpPath);
            if (!is_array($extracted) || empty($extracted)) {
                return [
                    'ok'    => false,
                    'error' => 'لم يتمكّن النظام من قراءة الوثيقة — جرّب صورة أوضح.',
                ];
            }

            // ── Detect which side this actually is (front vs back). ──
            // We trust three signals from the Gemini extraction:
            //   • presence of `name` + `id_number`        → strong "front" signal
            //   • presence of `document_number` only      → strong "back" signal
            //   • complete absence of any usable fields   → likely a bad image
            $hasName     = !empty($extracted['name']);
            $hasIdNumber = !empty($extracted['id_number']);
            $hasDocNum   = !empty($extracted['document_number']);

            if ($hasName && $hasIdNumber)      $detectedSide = 'front';
            elseif ($hasDocNum && !$hasName)   $detectedSide = 'back';
            else                                $detectedSide = 'unknown';

            // Reject mismatches with a friendly hint so the camera can re-prompt.
            if ($side === 'front' && $detectedSide === 'back') {
                return [
                    'ok'             => false,
                    'side_expected'  => 'front',
                    'side_detected'  => 'back',
                    'error'          => 'يبدو أنك صوّرت الوجه الخلفي — صوّر الوجه الأمامي (الذي يحمل الاسم والصورة).',
                ];
            }
            if ($side === 'back' && $detectedSide === 'front') {
                return [
                    'ok'             => false,
                    'side_expected'  => 'back',
                    'side_detected'  => 'front',
                    'error'          => 'يبدو أنك صوّرت الوجه الأمامي — صوّر الوجه الخلفي (الذي يحمل الـ MRZ والباركود).',
                ];
            }

            $lookups = $this->loadLookups();

            // ── Filter the extraction by the side we're actually saving. ──
            // Back-of-ID has limited useful info: only the card/document number
            // and possibly a re-validation of id_number from the MRZ. We strip
            // personal fields so we never overwrite values the user/front-side
            // already established.
            $filtered = $this->filterScanBySide($extracted, $side, $detectedSide);
            $mapped   = $this->mapScanToWizardFields($filtered, $lookups);

            // Tell the client what to do next:
            //   • front captured → ask for back
            //   • back captured (or auto/unknown) → done
            $nextAction = ($side === 'front' || ($side === 'auto' && $detectedSide === 'front'))
                ? 'capture_back'
                : 'done';

            return [
                'ok'             => true,
                'side'           => $side,
                'side_detected'  => $detectedSide,
                'next_action'    => $nextAction,
                'fields'         => $mapped['fields'],
                'unmapped'       => $mapped['unmapped'],
                'raw'            => $extracted,
                'meta'           => [
                    'source'     => 'gemini-vision',
                    'elapsed_ms' => (int)((microtime(true) - $startedAt) * 1000),
                ],
            ];

        } catch (\Throwable $e) {
            Yii::error('Wizard scan failed: ' . $e->getMessage(), __METHOD__);
            return [
                'ok'    => false,
                'error' => 'تعذّر تحليل الوثيقة: ' . $e->getMessage(),
            ];
        } finally {
            if (file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    /**
     * Drop the fields that don't belong to the side the user just scanned.
     *
     * Why: the back of a Jordanian ID frequently has Gemini accidentally
     * picking up faint text from the front bleed-through (or returning stale
     * cached impressions of a similar card). To prevent overwriting good
     * front-side data with garbage from the back, we restrict back captures
     * to a strict allow-list of fields.
     *
     * @param array  $extracted        raw fields from Gemini
     * @param string $requestedSide    front | back | auto
     * @param string $detectedSide     front | back | unknown
     */
    protected function filterScanBySide(array $extracted, $requestedSide, $detectedSide)
    {
        $effective = ($requestedSide === 'auto') ? $detectedSide : $requestedSide;

        if ($effective !== 'back') {
            return $extracted;
        }

        // Back-of-ID: only these fields are reliably present + safe to use.
        $backWhitelist = ['document_number', 'id_number'];
        $filtered = [];
        foreach ($backWhitelist as $key) {
            if (isset($extracted[$key]) && $extracted[$key] !== '') {
                $filtered[$key] = $extracted[$key];
            }
        }
        return $filtered;
    }

    /**
     * Translate the raw Gemini extraction → wizard-form keys.
     *
     * Gemini returns fields like:
     *   ['name' => '...', 'id_number' => '...', 'sex' => 0,
     *    'birth_date' => 'YYYY-MM-DD', 'birth_place' => '...',
     *    'nationality_text' => '...']
     *
     * We need to:
     *   1. Map keys → 'Customers[name]', 'Customers[id_number]', etc.
     *   2. Convert `sex` (0=male/1=female from Gemini) → '1'/'2' (model enum).
     *   3. Resolve `birth_place` (Arabic string) → city dropdown ID.
     *   4. Resolve `nationality_text` → citizen dropdown ID.
     *
     * Anything that can't be resolved goes into `unmapped` so the UI can
     * surface a friendly hint to the user.
     */
    protected function mapScanToWizardFields(array $extracted, array $lookups)
    {
        $fields   = [];
        $unmapped = [];

        // Plain pass-through (string-safe).
        if (!empty($extracted['name']) && is_string($extracted['name'])) {
            $fields['Customers[name]'] = trim($extracted['name']);
        }
        if (!empty($extracted['id_number'])) {
            $digits = preg_replace('/\D+/', '', (string)$extracted['id_number']);
            if (strlen($digits) >= 9 && strlen($digits) <= 12) {
                $fields['Customers[id_number]'] = $digits;
            }
        }

        // Gemini uses 0=male, 1=female. Our model uses 1=male, 2=female.
        if (isset($extracted['sex']) && $extracted['sex'] !== '') {
            $g = (int)$extracted['sex'];
            $fields['Customers[sex]'] = ($g === 1) ? '2' : '1';
        }

        // Birth date — only accept ISO YYYY-MM-DD that pass strtotime.
        if (!empty($extracted['birth_date']) && is_string($extracted['birth_date'])) {
            $bd = trim($extracted['birth_date']);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $bd) && strtotime($bd) !== false) {
                $fields['Customers[birth_date]'] = $bd;
            }
        }

        // Resolve birth_place (Arabic text) → city ID.
        if (!empty($extracted['birth_place']) && is_string($extracted['birth_place'])) {
            $cityId = $this->resolveLookupId($extracted['birth_place'], $lookups['cities'] ?? []);
            if ($cityId !== null) {
                $fields['Customers[city]'] = (string)$cityId;
            } else {
                $unmapped['city'] = trim($extracted['birth_place']);
            }
        }

        // Resolve nationality_text → citizen ID.
        if (!empty($extracted['nationality_text']) && is_string($extracted['nationality_text'])) {
            $citId = $this->resolveLookupId($extracted['nationality_text'], $lookups['citizens'] ?? []);
            if ($citId !== null) {
                $fields['Customers[citizen]'] = (string)$citId;
            } else {
                $unmapped['citizen'] = trim($extracted['nationality_text']);
            }
        }

        return ['fields' => $fields, 'unmapped' => $unmapped];
    }

    /**
     * Resolve free-form text (e.g. "عمان", "الأردن") to a lookup table ID
     * using a two-pass match: exact first, then substring contained either way.
     *
     * @param string $text   the text to resolve
     * @param array  $rows   array of rows like [{id, name}, ...]
     * @return int|string|null  the ID if found, else null
     */
    protected function resolveLookupId($text, array $rows)
    {
        $needle = trim((string)$text);
        if ($needle === '' || empty($rows)) return null;

        $needleNorm = self::normalizeArabic($needle);

        // Pass 1: exact (normalized) match.
        foreach ($rows as $row) {
            if (self::normalizeArabic($row['name'] ?? '') === $needleNorm) {
                return $row['id'];
            }
        }

        // Pass 2: substring match in either direction.
        foreach ($rows as $row) {
            $rowNorm = self::normalizeArabic($row['name'] ?? '');
            if ($rowNorm === '') continue;
            if (mb_strpos($rowNorm, $needleNorm) !== false
             || mb_strpos($needleNorm, $rowNorm) !== false) {
                return $row['id'];
            }
        }

        return null;
    }

    /**
     * Normalize Arabic text for fuzzy matching:
     *   • Collapse alef variants  أإآ → ا
     *   • Strip taa marbouta noise ة/ه equivalence
     *   • Strip diacritics + tatweel + extra spaces
     */
    protected static function normalizeArabic($text)
    {
        $t = (string)$text;
        $t = preg_replace('/[\x{064B}-\x{065F}\x{0640}]/u', '', $t); // diacritics + tatweel
        $t = strtr($t, [
            'إ' => 'ا', 'أ' => 'ا', 'آ' => 'ا',
            'ى' => 'ي', 'ؤ' => 'و', 'ئ' => 'ي',
            'ة' => 'ه',
        ]);
        $t = preg_replace('/\s+/u', ' ', $t);
        return trim(mb_strtolower($t));
    }

    /* ════════════════════════════════════════════════════════════════
       INTERNAL HELPERS
       ════════════════════════════════════════════════════════════════ */

    protected function stepView($n)
    {
        return [
            1 => '_step_1_identity',
            2 => '_step_2_employment',
            3 => '_step_3_guarantors',
            4 => '_step_4_review',
        ][$n];
    }

    protected function getOrCreateAutoDraft()
    {
        $draft = WizardDraft::loadAutoDraft(Yii::$app->user->id, self::DRAFT_KEY);
        if (!$draft) {
            WizardDraft::saveAutoDraft(Yii::$app->user->id, self::DRAFT_KEY, [
                '_step'    => 1,
                '_created' => time(),
                '_updated' => time(),
            ]);
            $draft = WizardDraft::loadAutoDraft(Yii::$app->user->id, self::DRAFT_KEY);
        }
        return $draft;
    }

    protected function decodePayload($draft)
    {
        if (!$draft || empty($draft->draft_data)) {
            return ['_step' => 1];
        }
        $data = json_decode($draft->draft_data, true);
        return is_array($data) ? $data : ['_step' => 1];
    }

    /**
     * Build a one-line summary used by WizardDraft::extractSummary so the
     * "saved drafts" picker shows useful labels.
     */
    protected function buildSummary($payload)
    {
        $name = $payload['step1']['Customers']['name']    ?? null;
        $id   = $payload['step1']['Customers']['id_number'] ?? null;
        if ($name && $id) {
            return $name . ' — هوية ' . $id;
        }
        if ($name) return $name;
        if ($id)   return 'هوية ' . $id;
        return 'مسودة عميل جديدة';
    }

    /**
     * Load shared dropdown lookups (cities, nationalities, "how heard about us")
     * from the same cached params used by the legacy form. Returns empty arrays
     * on any failure so views never crash.
     */
    protected function loadLookups()
    {
        $cache  = Yii::$app->cache;
        $params = Yii::$app->params;
        $duration = $params['time_duration'] ?? 3600;
        $db = Yii::$app->db;

        $fetch = function ($keyName, $queryName) use ($cache, $params, $duration, $db) {
            try {
                $key = $params[$keyName] ?? null;
                $sql = $params[$queryName] ?? null;
                if (!$key || !$sql) return [];
                return $cache->getOrSet($key, function () use ($db, $sql) {
                    return $db->createCommand($sql)->queryAll();
                }, $duration);
            } catch (\Throwable $e) {
                Yii::warning("Wizard lookup '$keyName' failed: " . $e->getMessage(), __METHOD__);
                return [];
            }
        };

        return [
            'cities'        => $fetch('key_city', 'city_query'),
            'citizens'      => $fetch('key_citizen', 'citizen_query'),
            'hearAboutUs'   => $fetch('key_hear_about_us', 'hear_about_us_query'),
        ];
    }

    /* ── Per-step server-side validators ── */

    /**
     * Validate identity step. Server-side rules mirror the eventual
     * Customers model rules but are applied here so we can fail fast on
     * the wizard's per-step "Next" without instantiating the full model.
     */
    protected function validateStep1($data)
    {
        $errors = [];

        $required = [
            'Customers[name]'                 => 'الاسم الرباعي',
            'Customers[id_number]'            => 'الرقم الوطني',
            'Customers[sex]'                  => 'الجنس',
            'Customers[birth_date]'           => 'تاريخ الميلاد',
            'Customers[city]'                 => 'مدينة الولادة',
            'Customers[citizen]'              => 'الجنسية',
            'Customers[primary_phone_number]' => 'الهاتف الرئيسي',
            'Customers[hear_about_us]'        => 'كيف سمعت عنا',
        ];
        foreach ($required as $key => $label) {
            $val = $this->dotGet($data, $key);
            if ($val === null || trim((string)$val) === '') {
                $errors[$key] = "حقل «{$label}» مطلوب.";
            }
        }

        // Name must contain at least 2 words (first + last as bare minimum).
        $name = trim((string)$this->dotGet($data, 'Customers[name]'));
        if ($name !== '') {
            $wordCount = preg_match_all('/\S+/u', $name);
            if ($wordCount < 2) {
                $errors['Customers[name]'] = 'الرجاء إدخال الاسم الرباعي (4 كلمات يفضّل، 2 كحد أدنى).';
            }
            if (mb_strlen($name) > 250) {
                $errors['Customers[name]'] = 'الاسم طويل جداً (الحد الأقصى 250 حرفاً).';
            }
        }

        // Jordanian national ID = 10 digits. Accept 9–12 to cover legacy/foreign IDs.
        $id = trim((string)$this->dotGet($data, 'Customers[id_number]'));
        if ($id !== '' && !preg_match('/^\d{9,12}$/', $id)) {
            $errors['Customers[id_number]'] = 'الرقم الوطني يجب أن يكون أرقاماً (9–12 خانة).';
        }

        // Birth date must be a real date AND user must be ≥ 18 years old AND ≤ 110.
        $birth = trim((string)$this->dotGet($data, 'Customers[birth_date]'));
        if ($birth !== '') {
            $ts = strtotime($birth);
            if ($ts === false) {
                $errors['Customers[birth_date]'] = 'صيغة التاريخ غير صحيحة.';
            } else {
                $age = (int)((time() - $ts) / (365.25 * 24 * 3600));
                if ($age < 18) {
                    $errors['Customers[birth_date]'] = 'يجب ألا يقل عمر العميل عن 18 سنة.';
                } elseif ($age > 110) {
                    $errors['Customers[birth_date]'] = 'تاريخ الميلاد يبدو غير منطقي (العمر > 110 سنة).';
                }
            }
        }

        // Phone — accept JO mobile patterns: 07XXXXXXXX | +9627XXXXXXXX | 009627XXXXXXXX.
        $phone = trim((string)$this->dotGet($data, 'Customers[primary_phone_number]'));
        if ($phone !== '') {
            $digits = preg_replace('/\D+/', '', $phone);
            $okJO   = (bool)preg_match('/^(?:00962|962|0)?7[789]\d{7}$/', $digits);
            $okIntl = strlen($digits) >= 8 && strlen($digits) <= 15; // E.164 floor/ceiling
            if (!$okJO && !$okIntl) {
                $errors['Customers[primary_phone_number]'] = 'رقم الهاتف غير صالح.';
            }
        }

        // Email is optional, but if present must be syntactically valid.
        $email = trim((string)$this->dotGet($data, 'Customers[email]'));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['Customers[email]'] = 'البريد الإلكتروني غير صالح.';
        }

        // Sex must be one of the accepted enum values (1 = male, 2 = female).
        $sex = $this->dotGet($data, 'Customers[sex]');
        if ($sex !== null && $sex !== '' && !in_array((string)$sex, ['1', '2'], true)) {
            $errors['Customers[sex]'] = 'قيمة الجنس غير صالحة.';
        }

        // Notes — soft cap 500 chars.
        $notes = (string)$this->dotGet($data, 'Customers[notes]');
        if (mb_strlen($notes) > 500) {
            $errors['Customers[notes]'] = 'الملاحظات تتجاوز 500 حرف.';
        }

        return $errors;
    }

    protected function validateStep2($data) { return []; }
    protected function validateStep3($data) { return []; }
    protected function validateStep4($data) { return []; }

    /**
     * Read `Customers[name]`-style keys out of a nested-or-flat array. Both
     * the legacy POST shape ($_POST['Customers']['name']) and the flattened
     * shape ($_POST['Customers[name]']) are supported.
     */
    protected function dotGet($data, $key)
    {
        if (array_key_exists($key, $data)) return $data[$key];
        // Try nested form: "Customers[name]" → $data['Customers']['name'].
        if (preg_match('/^([^\[]+)\[([^\]]+)\]$/', $key, $m)) {
            return $data[$m[1]][$m[2]] ?? null;
        }
        return null;
    }
}
