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
use backend\models\Media;
use backend\helpers\MediaHelper;
use backend\modules\customers\models\Customers;
use backend\modules\address\models\Address;
use backend\modules\phoneNumbers\models\PhoneNumbers;

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
                    'scan'        => ['POST'],
                    'scan-income' => ['POST'],
                    'add-city'    => ['POST'],
                    'add-citizen' => ['POST'],
                    'add-job'     => ['POST'],
                    'add-bank'    => ['POST'],
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
     * success the auto-draft is cleared and the response carries the new
     * customer's id + redirect URL for the SPA layer to navigate to.
     *
     * Flow:
     *   1. Re-run server-side validation for ALL four steps (defense-in-depth
     *      — the user could have deep-linked to /finish without going through
     *      the per-step validators).
     *   2. Open a single DB transaction; create the Customers row, then the
     *      Address row(s), then the PhoneNumbers (guarantor) rows.
     *   3. Adopt orphan scan-Media rows via linkScanImagesToCustomer().
     *   4. Bust the legacy customer-list caches so the new row appears
     *      immediately in the customers index.
     *   5. Clear the auto-draft. Manual saved drafts are preserved.
     */
    public function actionFinish()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $draft = WizardDraft::loadAutoDraft(Yii::$app->user->id, self::DRAFT_KEY);
        if (!$draft) {
            return ['ok' => false, 'error' => 'لا توجد مسودة لاعتمادها.'];
        }

        $payload = $this->decodePayload($draft);

        // ── 1. Validate every step. ──
        $allErrors = [];
        for ($n = 1; $n <= self::TOTAL_STEPS; $n++) {
            $stepData = $payload['step' . $n] ?? [];
            if (!is_array($stepData)) $stepData = [];
            $method = "validateStep{$n}";
            if (method_exists($this, $method)) {
                $errs = $this->{$method}($stepData);
                if (!empty($errs)) {
                    $allErrors['step' . $n] = $errs;
                }
            }
        }
        if (!empty($allErrors)) {
            return [
                'ok'     => false,
                'error'  => 'تعذّر اعتماد العميل: بعض الخطوات تحتاج إلى تصحيح. عُد إليها وأكمل الحقول الناقصة.',
                'errors' => $allErrors,
            ];
        }

        // ── 2. Merge Customers attributes from all steps. ──
        $custAttr = [];
        foreach (['step1', 'step2', 'step3'] as $sk) {
            $part = $payload[$sk]['Customers'] ?? null;
            if (is_array($part)) $custAttr = array_merge($custAttr, $part);
        }
        // Normalize empty strings on optional FK fields → NULL so they don't
        // violate `exist` validators (e.g. job_title=0).
        foreach (['job_title', 'bank_name', 'address_city', 'citizen_id', 'hear_about_us'] as $optFk) {
            if (isset($custAttr[$optFk]) && $custAttr[$optFk] === '') {
                $custAttr[$optFk] = null;
            }
        }

        $address    = $payload['step3']['address']    ?? [];
        if (!is_array($address)) $address = [];
        $guarantors = $payload['step3']['guarantors'] ?? [];
        if (!is_array($guarantors)) $guarantors = [];

        // ── 3. Persist inside a transaction. ──
        $db = Yii::$app->db;
        $tx = $db->beginTransaction();
        try {
            $customer = new Customers();
            // Use 'create' scenario if defined; otherwise fall back to default.
            $scenarios = $customer->scenarios();
            if (isset($scenarios['create'])) {
                $customer->scenario = 'create';
            }
            $customer->setAttributes($custAttr, false);
            // Stamp auditing columns the model doesn't auto-fill.
            if ($customer->hasAttribute('created_at') && empty($customer->created_at)) {
                $customer->created_at = time();
            }
            if ($customer->hasAttribute('createdBy') && empty($customer->createdBy)) {
                $customer->createdBy = Yii::$app->user->id ?? null;
            }
            if ($customer->hasAttribute('created_by') && empty($customer->created_by)) {
                $customer->created_by = Yii::$app->user->id ?? null;
            }

            if (!$customer->save(false)) {
                $tx->rollBack();
                Yii::error('Wizard finish: customer save failed: '
                    . print_r($customer->getErrors(), true), __METHOD__);
                return ['ok' => false, 'error' => 'تعذّر حفظ بيانات العميل.'];
            }

            // ── 3a. Address. ──
            $hasAddr = false;
            foreach (['address_city', 'address_area', 'address_street',
                      'address_building', 'postal_code', 'address'] as $k) {
                if (trim((string)($address[$k] ?? '')) !== '') { $hasAddr = true; break; }
            }
            if ($hasAddr) {
                $addrModel = new Address();
                $addrModel->setAttributes([
                    'customers_id'     => $customer->id,
                    'address_type'     => (int)($address['address_type'] ?? 2),
                    'address_city'     => $address['address_city']     ?? null,
                    'address_area'     => $address['address_area']     ?? null,
                    'address_street'   => $address['address_street']   ?? null,
                    'address_building' => $address['address_building'] ?? null,
                    'postal_code'      => $address['postal_code']      ?? null,
                    'address'          => $address['address']          ?? null,
                ], false);
                if (!$addrModel->save()) {
                    $tx->rollBack();
                    Yii::error('Wizard finish: address save failed: '
                        . print_r($addrModel->getErrors(), true), __METHOD__);
                    return ['ok' => false, 'error' => 'تعذّر حفظ بيانات العنوان.'];
                }
            }

            // ── 3b. Guarantors → PhoneNumbers. ──
            foreach ($guarantors as $g) {
                if (!is_array($g)) continue;
                $name  = trim((string)($g['owner_name']  ?? ''));
                $phone = trim((string)($g['phone_number'] ?? ''));
                $rel   = trim((string)($g['phone_number_owner'] ?? ''));
                $fb    = trim((string)($g['fb_account']  ?? ''));
                if ($name === '' && $phone === '' && $rel === '' && $fb === '') {
                    continue; // empty placeholder row
                }
                $pn = new PhoneNumbers();
                $pn->setAttributes([
                    'customers_id'       => $customer->id,
                    'owner_name'         => $name,
                    'phone_number'       => $phone,
                    'phone_number_owner' => $rel,
                    'fb_account'         => $fb !== '' ? $fb : null,
                ], false);
                if (!$pn->save()) {
                    $tx->rollBack();
                    Yii::error('Wizard finish: phone save failed: '
                        . print_r($pn->getErrors(), true), __METHOD__);
                    return ['ok' => false, 'error' => 'تعذّر حفظ بيانات المعرّفين.'];
                }
            }

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error('Wizard finish: unexpected error: ' . $e->getMessage()
                . "\n" . $e->getTraceAsString(), __METHOD__);
            return ['ok' => false, 'error' => 'حدث خطأ غير متوقع أثناء حفظ العميل.'];
        }

        // ── 4. Adopt orphan scan-Media rows + bust caches. ──
        try {
            $adopted = $this->linkScanImagesToCustomer((int)$customer->id, $payload);
        } catch (\Throwable $e) {
            $adopted = 0;
            Yii::warning('Wizard finish: linkScanImagesToCustomer failed: '
                . $e->getMessage(), __METHOD__);
        }

        try {
            $params = Yii::$app->params;
            $cache  = Yii::$app->cache;
            if (!empty($params['key_customers']) && !empty($params['customers_query'])) {
                $cache->set(
                    $params['key_customers'],
                    $db->createCommand($params['customers_query'])->queryAll(),
                    $params['time_duration'] ?? 3600
                );
            }
            if (!empty($params['key_customers_name']) && !empty($params['customers_name_query'])) {
                $cache->set(
                    $params['key_customers_name'],
                    $db->createCommand($params['customers_name_query'])->queryAll(),
                    $params['time_duration'] ?? 3600
                );
            }
        } catch (\Throwable $e) {
            Yii::warning('Wizard finish: cache refresh failed: '
                . $e->getMessage(), __METHOD__);
        }

        // ── 5. Drop the auto-draft (manual saved drafts kept). ──
        WizardDraft::clearAutoDraft(Yii::$app->user->id, self::DRAFT_KEY);

        return [
            'ok'           => true,
            'customer_id'  => (int)$customer->id,
            'customer_name'=> (string)($customer->name ?? ''),
            'adopted'      => $adopted,
            'redirect'     => \yii\helpers\Url::to([
                '/customers/customers/create-summary', 'id' => $customer->id,
            ]),
            'message'      => 'تم اعتماد العميل بنجاح.',
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

        // Save to runtime first — Gemini reads from here. We'll only promote
        // to the canonical Media store (os_ImageManager) AFTER a successful
        // extraction, so failed scans never leave orphan files in /web/.
        $ext = strtolower($file->extension ?: 'jpg');
        $tmpPath = Yii::getAlias('@runtime') . '/wizard_scan_'
                 . Yii::$app->security->generateRandomString(8) . '.' . $ext;

        if (!$file->saveAs($tmpPath, false)) {
            return ['ok' => false, 'error' => 'تعذّر حفظ الملف للمعالجة.'];
        }

        // Don't hold the session lock during the multi-second Gemini call.
        Yii::$app->session->close();

        $promoted = false; // true once we move the file to Media (so finally{} skips unlink)

        try {
            // Pass the side hint to Gemini so it tunes the prompt accordingly
            // (back-of-ID gets explicit MRZ + document_number guidance).
            $sideHint = ($side === 'auto') ? null : $side;
            $extracted = VisionService::extractFromImage($tmpPath, $sideHint);
            $extracted = is_array($extracted) ? $extracted : [];

            // ── Detect which side this actually is (front vs back). ──
            // Strong signals:
            //   • name + id_number                  → front (civilian or military)
            //   • document_number without name      → back
            //   • military_number without name      → back of military card
            $hasName     = !empty($extracted['name']);
            $hasIdNumber = !empty($extracted['id_number']);
            $hasDocNum   = !empty($extracted['document_number']);
            $hasMilNum   = !empty($extracted['military_number']);

            if ($hasName && ($hasIdNumber || $hasMilNum)) {
                $detectedSide = 'front';
            } elseif (($hasDocNum || ($hasMilNum && !$hasName))) {
                $detectedSide = 'back';
            } else {
                $detectedSide = 'unknown';
            }

            // ── Detect the issuing body (army / security / intelligence / civilian).
            // We need this BEFORE the empty-back check because intelligence cards
            // are the only family with a legitimately blank back side.
            $issuingBody = isset($extracted['issuing_body']) && is_string($extracted['issuing_body'])
                ? strtolower(trim($extracted['issuing_body']))
                : null;
            // Inherit issuing_body from a previous front capture stored in the
            // draft, so the back-side request can rely on it too.
            $previousIssuingBody = $this->getDraftIssuingBody();
            $effectiveIssuingBody = $issuingBody ?: $previousIssuingBody;

            // ── FRONT-side empty extraction = bad photo, can't proceed.
            if (empty($extracted) && $side === 'front') {
                return [
                    'ok'    => false,
                    'error' => 'لم يتمكّن النظام من قراءة الوجه الأمامي — تأكد من إضاءة جيدة، وضع البطاقة على خلفية داكنة، وتجنّب الانعكاسات.',
                ];
            }

            // ── BACK-side: document_number is REQUIRED (the whole point of the
            // back capture). ONE narrow exception: General Intelligence cards
            // legitimately have a blank back (warning text only) — for them we
            // accept the capture but record no document_number.
            if ($side === 'back') {
                $isIntelligence = ($effectiveIssuingBody === 'intelligence');

                if (!$hasDocNum && !$isIntelligence) {
                    // No document_number → we can't accept the back. Tell the
                    // user EXACTLY what we're looking for so the next attempt
                    // is informed, not random.
                    return [
                        'ok'             => false,
                        'side_expected'  => 'back',
                        'side_detected'  => $detectedSide,
                        'error'          => 'لم نستطع قراءة رقم البطاقة (Document No) من الظهر. '
                                          . 'يقع عادةً تحت "ID no:" أو في أول سطر MRZ بعد "IDJOR/IDJAF/IDPSD". '
                                          . 'حاوِل تقريب الكاميرا أكثر مع تثبيت البطاقة وإضاءة جيدة.',
                        'hint'           => [
                            'looking_for'   => 'document_number',
                            'examples'      => ['FBY86966', 'A212449', 'B097368'],
                        ],
                    ];
                }

                if ($isIntelligence && empty($extracted)) {
                    // Intelligence card back is genuinely blank — accept it.
                    $imageRef = $this->persistScanImage($tmpPath, $file, 'back', $effectiveIssuingBody, null);
                    if ($imageRef) { $promoted = true; }

                    return [
                        'ok'             => true,
                        'side'           => 'back',
                        'side_detected'  => 'unknown',
                        'next_action'    => 'done',
                        'fields'         => [],
                        'unmapped'       => [],
                        'raw'            => [],
                        'image_id'       => $imageRef['image_id'] ?? null,
                        'note'           => 'تم تسجيل ظهر بطاقة المخابرات (لا يحوي بيانات بطبيعته).',
                        'meta'           => [
                            'source'     => 'gemini-vision',
                            'elapsed_ms' => (int)((microtime(true) - $startedAt) * 1000),
                        ],
                    ];
                }
            }

            // Reject obvious mismatches with a friendly hint so the camera can re-prompt.
            // (Only when extraction is non-empty AND the detection is strongly the wrong
            // side — not for "unknown" which can be either.)
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
            $filtered = $this->filterScanBySide($extracted, $side, $detectedSide);
            $mapped   = $this->mapScanToWizardFields($filtered, $lookups);

            // ── Persist the captured image to the new Media store (os_ImageManager).
            // Done AFTER successful extraction so failed/blurry attempts never
            // pollute permanent storage. customer_id stays NULL until the
            // wizard finalizes — at which point linkScanImagesToCustomer()
            // adopts these orphan rows for the new customer.
            $effectiveSide = ($side === 'auto') ? $detectedSide : $side;
            $imageRef = $this->persistScanImage(
                $tmpPath,
                $file,
                $effectiveSide,
                $effectiveIssuingBody,
                $extracted['document_number'] ?? null
            );
            if ($imageRef) {
                $promoted = true;
            }

            // Remember in the draft so actionFinish() can adopt + the next
            // scan request inherits issuing_body from the front capture.
            $this->rememberScanInDraft(
                $effectiveSide,
                $imageRef['image_id'] ?? null,
                $effectiveIssuingBody,
                $extracted
            );

            // Tell the client what to do next.
            $nextAction = ($side === 'front' || ($side === 'auto' && $detectedSide === 'front'))
                ? 'capture_back'
                : 'done';

            return [
                'ok'             => true,
                'side'           => $side,
                'side_detected'  => $detectedSide,
                'next_action'    => $nextAction,
                'issuing_body'   => $effectiveIssuingBody,
                'fields'         => $mapped['fields'],
                'unmapped'       => $mapped['unmapped'],
                'raw'            => $extracted,
                'image_id'       => $imageRef['image_id'] ?? null,
                'image_url'      => $imageRef['url']      ?? null,
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
            // Only delete the runtime tmp file when it WASN'T promoted to Media
            // storage — promoted files live elsewhere now, the runtime copy is
            // already gone (rename) but we double-check just in case.
            if (!$promoted && file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    /* ════════════════════════════════════════════════════════════════
       SOCIAL-SECURITY STATEMENT SCAN (كشف الضمان الاجتماعي)
       Step 2 helper — accepts a PDF/image of the SSC's "كشف البيانات
       التفصيلي" and pre-fills employment + income fields.
       ════════════════════════════════════════════════════════════════ */

    /**
     * POST /customers/wizard/scan-income
     *
     * Inputs (multipart/form-data):
     *   • file  — the kashf statement (PDF, JPG, PNG, WEBP — up to 10 MB).
     *
     * Returns (JSON):
     *   { ok: true,
     *     fields: { 'Customers[is_social_security]': '1', … },   // ready for auto-fill
     *     unmapped: { current_employer: 'مؤسسة …' },             // text we couldn't resolve
     *     summary: { … }                                         // raw structured fields for UI
     *     image_id, image_url,
     *     meta: { source, elapsed_ms } }
     *
     * Why a separate action (not reusing actionScan)?
     *   • The prompt is fundamentally different (multi-page table reading vs.
     *     single ID card).
     *   • The mapping target is different (employment + income, not identity).
     *   • The persisted media's groupName is "5" (كتاب ضمان اجتماعي).
     *   • Side / MRZ / issuing-body logic from the ID flow is irrelevant here.
     */
    public function actionScanIncome()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $startedAt = microtime(true);

        if (!VisionService::isEnabled()) {
            return [
                'ok'    => false,
                'error' => 'المسح الذكي غير مفعّل في إعدادات النظام (Google Cloud).',
            ];
        }

        $file = UploadedFile::getInstanceByName('file');
        if (!$file) {
            return ['ok' => false, 'error' => 'لم يتم استلام ملف الكشف.'];
        }
        if ($file->error !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'خطأ في رفع الملف (#' . (int)$file->error . ').'];
        }
        if (!in_array($file->type, self::SCAN_ALLOWED_MIMES, true)) {
            return [
                'ok'    => false,
                'error' => 'نوع الملف غير مدعوم — استخدم PDF / JPG / PNG / WEBP فقط.',
            ];
        }
        if ($file->size > self::MAX_SCAN_BYTES) {
            return ['ok' => false, 'error' => 'حجم الملف أكبر من 10 ميجابايت.'];
        }

        // Save to runtime first; promote to Media only on successful extraction.
        $ext = strtolower($file->extension ?: 'pdf');
        $tmpPath = Yii::getAlias('@runtime') . '/wizard_income_'
                 . Yii::$app->security->generateRandomString(8) . '.' . $ext;

        if (!$file->saveAs($tmpPath, false)) {
            return ['ok' => false, 'error' => 'تعذّر حفظ الملف للمعالجة.'];
        }

        // Don't hold the session lock during the multi-second Gemini call.
        Yii::$app->session->close();

        $promoted = false;

        try {
            $extracted = VisionService::extractFromIncomeStatement($tmpPath);
            $extracted = is_array($extracted) ? $extracted : [];

            // Defensive: if the document doesn't look like a SS statement,
            // tell the user politely and don't pretend we extracted anything.
            if (isset($extracted['is_social_security_document'])
                && $extracted['is_social_security_document'] === false) {
                return [
                    'ok'    => false,
                    'error' => 'لا يبدو أن الملف هو كشف ضمان اجتماعي رسمي. '
                             . 'تأكد أنه "كشف البيانات التفصيلي" الصادر من المؤسسة العامة للضمان.',
                ];
            }
            // Heuristic: empty result OR no SS-specific field at all.
            $hasAny = !empty($extracted['social_security_number'])
                   || !empty($extracted['salary_history'])
                   || !empty($extracted['subscription_periods'])
                   || !empty($extracted['current_employer']);
            if (!$hasAny) {
                return [
                    'ok'    => false,
                    'error' => 'لم نتمكّن من قراءة الكشف بوضوح — '
                             . 'تأكّد من جودة الصورة/PDF وأن جميع الجداول ظاهرة بالكامل.',
                ];
            }

            $lookups = $this->loadLookups();
            $mapped  = $this->mapIncomeScanToWizardFields($extracted, $lookups);

            // Persist to Media as "كتاب ضمان اجتماعي" (groupName='5').
            $imageRef = $this->persistIncomeScanImage($tmpPath, $file);
            if ($imageRef) {
                $promoted = true;
            }

            // Track in draft so finish() can adopt the orphan Media row.
            $this->rememberIncomeScanInDraft($imageRef['image_id'] ?? null, $extracted);

            return [
                'ok'        => true,
                'fields'    => $mapped['fields'],
                'unmapped'  => $mapped['unmapped'],
                'summary'   => $this->buildIncomeSummary($extracted),
                'image_id'  => $imageRef['image_id'] ?? null,
                'image_url' => $imageRef['url']      ?? null,
                'meta'      => [
                    'source'     => 'gemini-vision',
                    'elapsed_ms' => (int)((microtime(true) - $startedAt) * 1000),
                ],
            ];

        } catch (\Throwable $e) {
            Yii::error('Wizard income scan failed: ' . $e->getMessage()
                . "\n" . $e->getTraceAsString(), __METHOD__);
            return [
                'ok'    => false,
                'error' => 'تعذّر تحليل الكشف: ' . $e->getMessage(),
            ];
        } finally {
            if (!$promoted && file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    /**
     * Translate the SS statement extraction → wizard form keys (Step 2 / 1).
     *
     * Auto-fills (when the statement carries the data):
     *   Step 1 cross-fill (only when the value is missing in the draft):
     *     Customers[name], Customers[id_number], Customers[birth_date],
     *     Customers[sex]
     *
     *   Step 2 (always, the SS statement is authoritative for these):
     *     Customers[is_social_security]      → '1'
     *     Customers[social_security_number]  → from "رقم تأمين"
     *     Customers[total_salary]            → latest year's wage
     *     Customers[job_title]               → resolved against jobs lookup
     *                                          (Arabic name → id), else unmapped
     *     Customers[last_income_query_date]  → today (we just queried)
     */
    protected function mapIncomeScanToWizardFields(array $extracted, array $lookups)
    {
        $fields   = [];
        $unmapped = [];

        // ── Step 2: SS flag is on. ──
        $fields['Customers[is_social_security]'] = '1';

        // ── SS number — strict digits-only. ──
        if (!empty($extracted['social_security_number'])) {
            $digits = preg_replace('/\D+/', '', (string)$extracted['social_security_number']);
            if ($digits !== '') {
                $fields['Customers[social_security_number]'] = $digits;
            }
        }

        // ── Latest monthly salary → total_salary. ──
        if (!empty($extracted['latest_monthly_salary'])) {
            $sal = (float)$extracted['latest_monthly_salary'];
            if ($sal > 0) {
                $fields['Customers[total_salary]'] = (string)round($sal, 2);
            }
        }

        // ── Current employer → job_title.
        //
        // We only auto-select on an EXACT (Arabic-normalized) match. The
        // generic resolveLookupId() also does a Pass-2 substring match,
        // which is great for cities (where "عمّان" should match "عمان")
        // but dangerous for employers — "شركة الخير لذبح وتجهيز الدواجن"
        // could substring-collide with "شركة الخير للنقل" and silently
        // pick the wrong one. Force the user to confirm via the combobox
        // when there's any ambiguity.
        $employer = trim((string)($extracted['current_employer'] ?? ''));
        if ($employer !== '') {
            // Always surface the raw text so the frontend can prefill the
            // combobox input regardless of match outcome.
            $unmapped['job_title_text'] = $employer;

            $jobId = $this->resolveExactLookupId($employer, $lookups['jobs'] ?? []);
            if ($jobId !== null) {
                $fields['Customers[job_title]'] = (string)$jobId;
            } else {
                // Legacy contract — kept so the UI still gets a hint when
                // the employer is brand new.
                $unmapped['job_title'] = $employer;
            }
        }

        // ── Stamp last income query date. ──
        $fields['Customers[last_income_query_date]'] = date('Y-m-d');

        // ── Cross-fill Step 1 fields when they're authoritative on the kashf. ──
        if (!empty($extracted['name']) && is_string($extracted['name'])) {
            $fields['Customers[name]'] = trim(preg_replace('/\s+/u', ' ', $extracted['name']));
        }
        if (!empty($extracted['id_number'])) {
            $idDigits = preg_replace('/\D+/', '', (string)$extracted['id_number']);
            if (preg_match('/^[920][0-9]{9}$/', $idDigits)) {
                $fields['Customers[id_number]'] = $idDigits;
            }
        }
        if (!empty($extracted['birth_date'])
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$extracted['birth_date'])) {
            $fields['Customers[birth_date]'] = $extracted['birth_date'];
        }
        if (isset($extracted['sex']) && $extracted['sex'] !== '' && $extracted['sex'] !== null) {
            // Gemini schema: 0 = ذكر, 1 = أنثى
            // Customers model:  '1' = male, '2' = female
            $fields['Customers[sex]'] = ((int)$extracted['sex'] === 1) ? '2' : '1';
        }

        return ['fields' => $fields, 'unmapped' => $unmapped];
    }

    /**
     * Build a compact, JSON-safe summary the JS can render under the upload
     * widget so the user sees what we actually read (and can sanity-check
     * before continuing).
     */
    protected function buildIncomeSummary(array $extracted)
    {
        $periods = [];
        foreach ((array)($extracted['subscription_periods'] ?? []) as $p) {
            if (!is_array($p)) continue;
            $periods[] = [
                'from'   => $p['from'] ?? null,
                'to'     => $p['to']   ?? null,
                'salary' => isset($p['salary']) ? (float)$p['salary'] : null,
                'reason' => $p['reason'] ?? null,
                'name'   => $p['establishment_name'] ?? null,
                'months' => isset($p['months']) ? (int)$p['months'] : null,
            ];
        }

        $salaries = [];
        foreach ((array)($extracted['salary_history'] ?? []) as $r) {
            if (!is_array($r)) continue;
            $salaries[] = [
                'year'    => isset($r['year']) ? (int)$r['year'] : null,
                'salary'  => isset($r['salary']) ? (float)$r['salary'] : null,
                'name'    => $r['establishment_name'] ?? null,
            ];
        }

        return [
            'name'                       => $extracted['name'] ?? null,
            'social_security_number'     => $extracted['social_security_number'] ?? null,
            'id_number'                  => $extracted['id_number'] ?? null,
            'statement_date'             => $extracted['statement_date'] ?? null,
            'join_date'                  => $extracted['join_date'] ?? null,
            'subjection_salary'          => isset($extracted['subjection_salary'])
                                            ? (float)$extracted['subjection_salary'] : null,
            'current_employer'           => $extracted['current_employer'] ?? null,
            'subjection_employer'        => $extracted['subjection_employer'] ?? null,
            'latest_salary_year'         => isset($extracted['latest_salary_year'])
                                            ? (int)$extracted['latest_salary_year'] : null,
            'latest_monthly_salary'      => isset($extracted['latest_monthly_salary'])
                                            ? (float)$extracted['latest_monthly_salary'] : null,
            'total_subscription_months'  => isset($extracted['total_subscription_months'])
                                            ? (float)$extracted['total_subscription_months'] : null,
            'active_subscription'        => !empty($extracted['active_subscription']),
            'subscription_periods'       => $periods,
            'salary_history'             => $salaries,
        ];
    }

    /**
     * Persist a successfully-scanned SS statement into the Media store with
     * groupName = '5' (كتاب ضمان اجتماعي). Mirror of persistScanImage()
     * but without side / issuing-body / document_number.
     */
    protected function persistIncomeScanImage($tmpPath, $uploadedFile)
    {
        try {
            if (!is_file($tmpPath)) return null;

            $fileHash = Yii::$app->security->generateRandomString(32);
            $origName = $uploadedFile->name ?: ('income_kashf.pdf');
            $origName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($origName));
            if ($origName === '' || $origName === '.' || $origName === '..') {
                $ext = strtolower(pathinfo($tmpPath, PATHINFO_EXTENSION)) ?: 'pdf';
                $origName = 'income_kashf.' . $ext;
            }

            $media = new Media([
                'fileName'    => $origName,
                'fileHash'    => $fileHash,
                'customer_id' => null, // adopted by linkScanImagesToCustomer()
                'contractId'  => null,
                'groupName'   => '5', // كتاب ضمان اجتماعي
                'created'     => date('Y-m-d H:i:s'),
                'modified'    => date('Y-m-d H:i:s'),
                'createdBy'   => Yii::$app->user->id ?? null,
                'modifiedBy'  => Yii::$app->user->id ?? null,
            ]);
            if (!$media->save(false)) {
                Yii::warning('Wizard income scan: failed to persist Media row', __METHOD__);
                return null;
            }

            $destPath = MediaHelper::filePath((int)$media->id, $fileHash, $origName);
            $destDir  = dirname($destPath);
            if (!is_dir($destDir)) @mkdir($destDir, 0755, true);

            if (!@rename($tmpPath, $destPath)) {
                if (!@copy($tmpPath, $destPath)) {
                    $media->delete();
                    Yii::warning('Wizard income scan: failed to move file to Media store', __METHOD__);
                    return null;
                }
                @unlink($tmpPath);
            }
            @chmod($destPath, 0644);

            // Best-effort thumbnail (image only — we don't rasterize PDFs here).
            try {
                $isImage = strpos((string)$uploadedFile->type, 'image/') === 0;
                if ($isImage && method_exists(VisionService::class, 'createThumbnail')) {
                    $thumbDir = Yii::getAlias('@backend/web/uploads/customers/documents/thumbs');
                    if (!is_dir($thumbDir)) @mkdir($thumbDir, 0755, true);
                    $thumbPath = $thumbDir . '/thumb_' . basename($destPath);
                    VisionService::createThumbnail($destPath, $thumbPath);
                }
            } catch (\Throwable $te) {
                Yii::warning('Wizard income scan: thumbnail generation failed: ' . $te->getMessage(), __METHOD__);
            }

            return [
                'image_id'   => (int)$media->id,
                'url'        => $media->getUrl(),
                'group_name' => '5',
                'file_name'  => $origName,
            ];
        } catch (\Throwable $e) {
            Yii::error('Wizard income scan persistence failed: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Track the SS scan in the wizard's auto-draft so:
     *   • finish() adopts the Media row for the new customer.
     *   • the review screen can show the kashf as a known document.
     *   • re-uploading replaces the previous summary cleanly.
     */
    protected function rememberIncomeScanInDraft($imageId, array $extracted)
    {
        try {
            $draft   = $this->getOrCreateAutoDraft();
            $payload = $this->decodePayload($draft);

            if (!isset($payload['_scan']) || !is_array($payload['_scan'])) {
                $payload['_scan'] = [];
            }
            if (!isset($payload['_scan']['images']) || !is_array($payload['_scan']['images'])) {
                $payload['_scan']['images'] = [];
            }
            if ($imageId) {
                $payload['_scan']['images']['income'] = (int)$imageId;
            }
            $payload['_scan']['income_summary'] = $this->buildIncomeSummary($extracted);
            $payload['_scan']['updated'] = time();

            $payload['_updated'] = time();
            $finalJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
            if ($finalJson !== false && strlen($finalJson) <= self::MAX_DRAFT_BYTES) {
                WizardDraft::saveAutoDraft(Yii::$app->user->id, self::DRAFT_KEY, $finalJson);
            }
        } catch (\Throwable $e) {
            Yii::warning('Wizard income scan: failed to remember in draft: ' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Map (issuing_body, side) → groupName values used by the rest of the
     * platform (`os_ImageManager.groupName`). Keeps a single, documented
     * mapping in one place so reports/filters stay consistent.
     *
     * Convention used elsewhere in the codebase (SmartMediaController):
     *   '0' = هوية وطنية (national ID)
     *   '4' = شهادة تعيين عسكرية (military appointment certificate)
     *
     * For the wizard we extend this with side-specific subcodes so the
     * customer's documents tab can show "ID — Front" vs "ID — Back" cleanly:
     *   '0_front', '0_back'   civilian ID
     *   '4_front', '4_back'   military / security / intelligence
     */
    protected function groupNameForScan($side, $issuingBody)
    {
        $isMilitary = in_array($issuingBody, ['army', 'security', 'intelligence'], true);
        $base = $isMilitary ? '4' : '0';
        $sideKey = ($side === 'back') ? 'back' : 'front';
        return $base . '_' . $sideKey;
    }

    /**
     * Persist a successfully-scanned image into the canonical Media store
     * (os_ImageManager via backend\models\Media). The wizard isn't yet
     * attached to a customer, so customer_id is left NULL — the wizard's
     * finish step adopts these orphan rows once the real customer exists.
     *
     * @return array{image_id:int,url:string,group_name:string,file_name:string}|null
     */
    protected function persistScanImage($tmpPath, $uploadedFile, $side, $issuingBody, $documentNumber)
    {
        try {
            if (!is_file($tmpPath)) return null;

            // 1. Insert Media row first to get the auto-increment ID — the
            //    file path includes the row ID so we can't write the file
            //    until we know its ID.
            $fileHash = Yii::$app->security->generateRandomString(32);
            $origName = $uploadedFile->name ?: ('scan_' . $side . '.jpg');
            // Sanitize the filename: avoid path traversal & weird chars.
            $origName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($origName));
            if ($origName === '' || $origName === '.' || $origName === '..') {
                $origName = 'scan_' . $side . '.' . (strtolower(pathinfo($tmpPath, PATHINFO_EXTENSION)) ?: 'jpg');
            }

            $groupName = $this->groupNameForScan($side, $issuingBody);

            $media = new Media([
                'fileName'    => $origName,
                'fileHash'    => $fileHash,
                'customer_id' => null, // adopted later by linkScanImagesToCustomer()
                'contractId'  => null,
                'groupName'   => $groupName,
                'created'     => date('Y-m-d H:i:s'),
                'modified'    => date('Y-m-d H:i:s'),
                'createdBy'   => Yii::$app->user->id ?? null,
                'modifiedBy'  => Yii::$app->user->id ?? null,
            ]);
            if (!$media->save(false)) {
                Yii::warning('Wizard scan: failed to persist Media row', __METHOD__);
                return null;
            }

            // 2. Move the runtime tmp file into the Media-canonical path.
            $destPath = MediaHelper::filePath((int)$media->id, $fileHash, $origName);
            $destDir  = dirname($destPath);
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            // Use rename() — fast, atomic, and zero-copy on the same volume.
            if (!@rename($tmpPath, $destPath)) {
                // Fall back to copy + unlink if rename crosses devices.
                if (!@copy($tmpPath, $destPath)) {
                    $media->delete();
                    Yii::warning('Wizard scan: failed to move file to Media store', __METHOD__);
                    return null;
                }
                @unlink($tmpPath);
            }
            @chmod($destPath, 0644);

            // 3. Best-effort thumbnail (matches SmartMediaController convention).
            try {
                $thumbDir  = Yii::getAlias('@backend/web/uploads/customers/documents/thumbs');
                if (!is_dir($thumbDir)) @mkdir($thumbDir, 0755, true);
                $thumbFile = 'thumb_' . basename($destPath);
                $thumbPath = $thumbDir . '/' . $thumbFile;
                if (method_exists(VisionService::class, 'createThumbnail')) {
                    VisionService::createThumbnail($destPath, $thumbPath);
                }
            } catch (\Throwable $te) {
                Yii::warning('Wizard scan: thumbnail generation failed: ' . $te->getMessage(), __METHOD__);
            }

            return [
                'image_id'   => (int)$media->id,
                'url'        => $media->getUrl(),
                'group_name' => $groupName,
                'file_name'  => $origName,
            ];
        } catch (\Throwable $e) {
            Yii::error('Wizard scan persistence failed: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Track scan output inside the wizard's auto-draft so the finish step
     * can later adopt the orphan Media rows + we can carry issuing_body
     * forward from the front to the back capture.
     */
    protected function rememberScanInDraft($side, $imageId, $issuingBody, array $extracted)
    {
        try {
            $draft   = $this->getOrCreateAutoDraft();
            $payload = $this->decodePayload($draft);

            if (!isset($payload['_scan']) || !is_array($payload['_scan'])) {
                $payload['_scan'] = [];
            }
            if (!isset($payload['_scan']['images']) || !is_array($payload['_scan']['images'])) {
                $payload['_scan']['images'] = [];
            }

            if ($imageId) {
                $payload['_scan']['images'][$side] = (int)$imageId;
            }
            if ($issuingBody) {
                $payload['_scan']['issuing_body'] = $issuingBody;
            }
            if (!empty($extracted['document_number'])) {
                $payload['_scan']['document_number'] = (string)$extracted['document_number'];
            }
            if (!empty($extracted['document_type'])) {
                $payload['_scan']['document_type'] = (string)$extracted['document_type'];
            }
            $payload['_scan']['updated'] = time();

            $payload['_updated'] = time();
            $finalJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
            if ($finalJson !== false && strlen($finalJson) <= self::MAX_DRAFT_BYTES) {
                WizardDraft::saveAutoDraft(Yii::$app->user->id, self::DRAFT_KEY, $finalJson);
            }
        } catch (\Throwable $e) {
            // Non-fatal — the scan itself succeeded. Just log.
            Yii::warning('Wizard scan: failed to remember in draft: ' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Read issuing_body from the draft (set by a prior front-side scan).
     * Used when a back-side scan needs to know whether to relax the
     * document_number requirement (intelligence cards only).
     */
    protected function getDraftIssuingBody()
    {
        try {
            $draft = WizardDraft::loadAutoDraft(Yii::$app->user->id, self::DRAFT_KEY);
            if (!$draft) return null;
            $payload = $this->decodePayload($draft);
            $val = $payload['_scan']['issuing_body'] ?? null;
            return is_string($val) && $val !== '' ? strtolower($val) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Adopt orphan scan-Media rows for a freshly-created customer.
     *
     * Called from actionFinish() once a real customer record exists. We
     * look up the image IDs we tracked in the draft, set their customer_id,
     * and create matching `customers_document` rows so the documents tab
     * shows them properly.
     *
     * @param int $customerId  Real customer id (just created)
     * @param array $payload   Decoded wizard draft payload
     * @return int  Number of images successfully adopted.
     */
    public function linkScanImagesToCustomer($customerId, array $payload)
    {
        $images = $payload['_scan']['images'] ?? [];
        if (!is_array($images) || empty($images)) return 0;

        $documentNumber = $payload['_scan']['document_number'] ?? null;
        $documentType   = $payload['_scan']['document_type']   ?? '0';
        $count = 0;
        $db = Yii::$app->db;

        foreach ($images as $side => $imageId) {
            $imageId = (int)$imageId;
            if ($imageId <= 0) continue;

            try {
                // Adopt the orphan Media row.
                $db->createCommand()->update(
                    Media::tableName(),
                    [
                        'customer_id' => $customerId,
                        'modified'    => date('Y-m-d H:i:s'),
                    ],
                    ['id' => $imageId, 'customer_id' => null]
                )->execute();

                // Best-effort customers_document entry — only on the front
                // capture (the document_number is the same for both sides;
                // we don't need two doc rows for the same physical card).
                if ($side === 'front' && $documentNumber) {
                    $db->createCommand()->insert('{{%customers_document}}', [
                        'customer_id'     => $customerId,
                        'document_type'   => (string)$documentType,
                        'document_number' => (string)$documentNumber,
                        'document_image'  => (string)$imageId,
                        'created_at'      => time(),
                        'updated_at'      => time(),
                        'created_by'      => Yii::$app->user->id ?? null,
                    ])->execute();
                }
                $count++;
            } catch (\Throwable $e) {
                Yii::warning('Wizard adopt scan image failed: ' . $e->getMessage(), __METHOD__);
            }
        }
        return $count;
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

        // Back-of-ID: a strict allow-list of fields that ARE reliably present
        // on the back of any of the four supported families:
        //   • civilian    — MRZ has id_number, birth_date, sex, expiry_date,
        //                   document_number; printed area has issue_date too.
        //   • military    — same MRZ pattern, plus military_number.
        //   • security    — same as military.
        //   • intelligence — back is blank (this list is irrelevant).
        $backWhitelist = [
            'document_number', 'id_number', 'military_number',
            'birth_date', 'sex', 'expiry_date', 'issue_date',
        ];
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

        // ── NAME — preserve verbatim. We only collapse repeated whitespace
        // (cosmetic) and trim end-spaces; we DO NOT touch hamzas, taa
        // marbouta, alef variants, or word boundaries. The user explicitly
        // requires "اسامه عبد المهدي" stays "اسامه عبد المهدي" — never
        // normalized to "أسامة عبدالمهدي".
        if (!empty($extracted['name']) && is_string($extracted['name'])) {
            $name = (string)$extracted['name'];
            // Collapse runs of any Unicode whitespace into a single ASCII
            // space (handles non-breaking spaces, tabs, etc) without
            // touching the actual letters.
            $name = preg_replace('/\s+/u', ' ', $name);
            $fields['Customers[name]'] = trim($name);
        }
        // ── ID NUMBER (Jordanian National ID) — strict 10-digit validation
        // with MRZ-shift recovery.
        //
        // The Jordanian National ID is exactly 10 digits and ALWAYS starts
        // with one of: 9, 2, 1, 0. It NEVER starts with 3-8.
        //
        // A common Gemini failure mode: when reading from the MRZ optional
        // data field on the back, it grabs the trailing check digit instead
        // of skipping it. Example:
        //   raw MRZ ...JOR9891028911<<3<  →  Gemini returns 3989102891
        // Notice the wrong reading is the right number rotated by one
        // position with the check digit (3) prepended. We detect this by
        // checking the leading digit and try to recover.
        $rawId = $extracted['id_number'] ?? null;
        if ($rawId !== null && $rawId !== '') {
            $idDigits = $this->normalizeJordanianNationalId(
                $rawId,
                $extracted['mrz']             ?? null,
                $extracted['mrz_optional']    ?? null
            );
            if ($idDigits !== null) {
                $fields['Customers[id_number]'] = $idDigits;
            } else {
                Yii::warning(
                    'Wizard scan: rejected suspicious id_number "' . $rawId
                    . '" — does not match Jordanian National ID format.',
                    __METHOD__
                );
            }
        }

        // ── SEX — accept any of the formats Gemini may emit:
        //   • numeric  0/1   (per our prompt: 0=male, 1=female)
        //   • single   M/F
        //   • Arabic   ذكر / أنثى / انثى
        //   • English  male / female
        // Our model wants '1' (male) or '2' (female).
        if (isset($extracted['sex']) && $extracted['sex'] !== '' && $extracted['sex'] !== null) {
            $sexResolved = self::normalizeSexValue($extracted['sex']);
            if ($sexResolved !== null) {
                $fields['Customers[sex]'] = $sexResolved;
            }
        }
        // Fallback: read sex from MRZ if we have it raw and Gemini missed it.
        if (!isset($fields['Customers[sex]']) && !empty($extracted['mrz']) && is_string($extracted['mrz'])) {
            if (preg_match('/\b\d{6}([MF])\d{6}\b/', strtoupper($extracted['mrz']), $mm)) {
                $fields['Customers[sex]'] = ($mm[1] === 'F') ? '2' : '1';
            }
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

        // ── NATIONALITY — multi-source detection. We try (in order):
        //   1. Explicit `nationality_text` from Gemini.
        //   2. Headline / footer text mentioning "الأردنية الهاشمية"
        //      / "الأردن" / "JORDAN" / MRZ country code "JOR".
        //   3. document_number prefixes that imply Jordanian issuer
        //      (IDJOR/IDJAF/IDPSD).
        // Any match → resolve to the citizens lookup with "أردني" / "أردنية".
        $nationalityText = $this->detectNationalityText($extracted);
        if ($nationalityText !== null) {
            $citId = $this->resolveLookupId($nationalityText, $lookups['citizens'] ?? []);
            if ($citId !== null) {
                $fields['Customers[citizen]'] = (string)$citId;
            } else {
                // Last attempt: try the alternate gender-form ("أردنية" vs "أردني")
                $alt = ($nationalityText === 'أردني') ? 'أردنية' : 'أردني';
                $citId = $this->resolveLookupId($alt, $lookups['citizens'] ?? []);
                if ($citId !== null) {
                    $fields['Customers[citizen]'] = (string)$citId;
                } else {
                    $unmapped['citizen'] = $nationalityText;
                }
            }
        }

        return ['fields' => $fields, 'unmapped' => $unmapped];
    }

    /**
     * Validate and (when possible) repair a Jordanian National ID number.
     *
     * Rules:
     *   • Must be exactly 10 digits.
     *   • First digit MUST be 9, 2, 1, or 0 (Jordanian Civil-Status convention).
     *   • If Gemini returned 10 digits but the first is 3-8, this is the
     *     classic "MRZ check-digit shift" — we try to recover by rotating
     *     left (drop the leading wrong digit, append a digit from the
     *     raw MRZ when available).
     *   • If the raw MRZ string is given, we prefer extracting from there
     *     because the prefix detection is unambiguous after "JOR".
     *
     * @return string|null  10-digit string on success, null on rejection.
     */
    protected function normalizeJordanianNationalId($raw, $mrz = null, $mrzOptional = null)
    {
        $valid = ['9', '2', '1', '0'];
        $isValid = function ($d) use ($valid) {
            return is_string($d)
                && strlen($d) === 10
                && ctype_digit($d)
                && in_array($d[0], $valid, true);
        };

        // 1. First, try the value Gemini gave us directly.
        $digits = preg_replace('/\D+/', '', (string)$raw);

        if ($isValid($digits)) {
            return $digits;
        }

        // 2. If there's MRZ raw text, that's the most reliable source.
        //    The Jordanian ID lives in the optional-data field of MRZ line 2,
        //    sitting right after "JOR". Look for: JOR + 10 digits + <<X<.
        $sources = array_filter([$mrz, $mrzOptional, $raw], 'is_string');
        foreach ($sources as $src) {
            // Strip any non-allowed chars first.
            $src = strtoupper((string)$src);
            // Remove Arabic-Indic digits and convert to ASCII just in case.
            $src = strtr($src, [
                '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
                '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            ]);
            // Pattern: after "JOR", a run of digits where the first valid
            // 10-digit group starting with 9/2/1/0 wins.
            if (preg_match('/JOR[<\s]*((?:[<\s]*\d){10,})/u', $src, $m)) {
                $blob = preg_replace('/\D/', '', $m[1]);
                if (preg_match('/([920][0-9]{9})/', $blob, $mm)) {
                    if ($isValid($mmCand = $mm[1])) return $mmCand;
                }
            }
            // Generic fallback: scan for any 10-digit run starting with 9/2/1/0.
            if (preg_match('/(?<!\d)([9210][0-9]{9})(?!\d)/', $src, $m)) {
                if ($isValid($m[1])) return $m[1];
            }
        }

        // 3. Recovery for the "check-digit shift" pattern. If Gemini returned
        //    10 digits whose LAST 9 happen to start with a valid prefix, the
        //    first digit is most likely the trailing check digit it grabbed
        //    by accident. We CANNOT safely guess the missing trailing digit,
        //    so reject and let the user retry.
        if (strlen($digits) === 10 && in_array($digits[1], $valid, true)) {
            // (intentionally no auto-fix — log and reject)
        }

        // 4. Length-based fallback (only when there's no shift suspicion):
        //    accept 9-12 digit strings IF the first digit is valid. This
        //    catches edge cases like extra trailing non-digits we missed.
        if (strlen($digits) >= 10 && in_array($digits[0], $valid, true)) {
            $clean = substr($digits, 0, 10);
            if ($isValid($clean)) return $clean;
        }

        return null;
    }

    /**
     * Normalize a raw sex value (anything Gemini may emit) into our model's
     * '1' (male) / '2' (female) string. Returns null when nothing matches.
     */
    protected static function normalizeSexValue($raw)
    {
        if (is_int($raw)) {
            return ($raw === 1) ? '2' : '1'; // Gemini: 0=male, 1=female
        }
        $s = trim((string)$raw);
        if ($s === '') return null;

        $u = strtoupper($s);
        if ($u === '0') return '1';
        if ($u === '1') return '2';
        if ($u === '2') return '2'; // already-normalized passthrough
        if ($u === 'M' || $u === 'MALE')   return '1';
        if ($u === 'F' || $u === 'FEMALE') return '2';

        // Arabic — strip diacritics and common variants before comparing.
        $n = self::normalizeArabic($s);
        if ($n === 'ذكر' || $n === 'ذكور') return '1';
        if ($n === 'انثي' || $n === 'انثى' || $n === 'اناث') return '2';

        return null;
    }

    /**
     * Detect the customer's nationality from any signal in the extracted
     * payload (headline text, MRZ, document_number prefix, raw mrz string,
     * etc). Returns the canonical Arabic word (gendered when possible) or
     * null when no Jordan/foreign indicator is present.
     */
    protected function detectNationalityText(array $e)
    {
        // 1. Direct field — trust Gemini if it gave us something.
        if (!empty($e['nationality_text']) && is_string($e['nationality_text'])) {
            return trim($e['nationality_text']);
        }
        if (!empty($e['nationality']) && is_string($e['nationality'])) {
            return trim($e['nationality']);
        }

        // 2. Build a "haystack" from any free-text field that may contain a
        //    country/headline marker.
        $bag = [];
        foreach (['address', 'birth_place', 'mrz', 'document_number'] as $k) {
            if (!empty($e[$k]) && is_string($e[$k])) {
                $bag[] = $e[$k];
            }
        }
        $haystack = ' ' . implode(' ', $bag) . ' ';
        $hayU = strtoupper($haystack);

        // 3. Strong Jordanian indicators.
        $isJordan = false;
        $jordanArMarkers = ['الأردن', 'الاردن', 'الأردنية', 'الاردنيه',
                            'المملكة الأردنية الهاشمية', 'المملكه الاردنيه الهاشميه',
                            'أردني', 'اردني', 'أردنية', 'اردنيه'];
        foreach ($jordanArMarkers as $m) {
            if (mb_strpos($haystack, $m) !== false) { $isJordan = true; break; }
        }
        if (!$isJordan) {
            // English/MRZ markers
            if (strpos($hayU, 'JORDAN') !== false
             || preg_match('/\bIDJ(OR|AF|PSD)\b/', $hayU)
             || preg_match('/\b[A-Z]{2,3}<<<JOR\b/', $hayU)
             || preg_match('/<JOR<|JOR<<<<<<<<<<<<</', $hayU)) {
                $isJordan = true;
            }
        }
        if ($isJordan) {
            // Gender-aware: pick "أردنية" for females, "أردني" for males,
            // default to masculine when sex unknown (matches typical
            // citizen-lookup default).
            $sexNorm = isset($e['sex']) ? self::normalizeSexValue($e['sex']) : null;
            return ($sexNorm === '2') ? 'أردنية' : 'أردني';
        }

        return null;
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
     * Strict variant of resolveLookupId — exact (normalized) match only,
     * no substring fallback. Used for free-form fields where a wrong-but-
     * plausible substring hit would be worse than asking the user.
     */
    protected function resolveExactLookupId($text, array $rows)
    {
        $needle = trim((string)$text);
        if ($needle === '' || empty($rows)) return null;
        $needleNorm = self::normalizeArabic($needle);
        foreach ($rows as $row) {
            if (self::normalizeArabic($row['name'] ?? '') === $needleNorm) {
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
     * Add a new city to the lookup table on demand. Used by the smart-scan
     * flow when the OCR returned a city name we couldn't resolve to an
     * existing row — instead of forcing the user to pick "غير محدد", we
     * let them upgrade the unknown name into a permanent option in one tap.
     *
     * Returns: { ok: true, id: <int>, name: <string> } on success.
     */
    public function actionAddCity()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $name = trim((string)Yii::$app->request->post('name', ''));
        return $this->addLookupRow([
            'name'        => $name,
            'table'       => '{{%city}}',
            'cacheKey'    => 'key_city',
            'emptyError'  => 'يرجى إدخال اسم المدينة.',
            'tooLongError'=> 'اسم المدينة طويل جداً.',
            'failError'   => 'تعذّر حفظ المدينة. حاول مرة أخرى.',
            'logLabel'    => 'city',
        ]);
    }

    /**
     * Same idea as actionAddCity, but for the citizens (nationality) lookup.
     * Wired so the city/citizen comboboxes can both offer a one-tap "add new"
     * affordance with identical UX.
     */
    public function actionAddCitizen()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $name = trim((string)Yii::$app->request->post('name', ''));
        return $this->addLookupRow([
            'name'        => $name,
            'table'       => '{{%citizen}}',
            'cacheKey'    => 'key_citizen',
            'emptyError'  => 'يرجى إدخال اسم الجنسية.',
            'tooLongError'=> 'اسم الجنسية طويل جداً.',
            'failError'   => 'تعذّر حفظ الجنسية. حاول مرة أخرى.',
            'logLabel'    => 'citizen',
        ]);
    }

    /**
     * Add (or restore) a job — used by the Step 2 jobs combobox so that a user
     * can register a new employer on the fly without leaving the wizard.
     */
    public function actionAddJob()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $name = trim((string)Yii::$app->request->post('name', ''));

        // os_jobs has job_type INT NOT NULL with no default — addLookupRow's
        // generic insert would die without it. Default to 2 = "قطاع خاص"
        // (private sector) which is the most common bucket for free-form
        // employer additions. The user can refine the type later from the
        // jobs admin module.
        return $this->addLookupRow([
            'name'        => $name,
            'table'       => '{{%jobs}}',
            'cacheKey'    => 'key_jobs',
            'emptyError'  => 'يرجى إدخال اسم جهة العمل.',
            'tooLongError'=> 'اسم جهة العمل طويل جداً.',
            'failError'   => 'تعذّر حفظ جهة العمل. حاول مرة أخرى.',
            'logLabel'    => 'job',
            'extra'       => ['job_type' => 2, 'status' => 1],
        ]);
    }

    /**
     * Add (or restore) a bank — used by the Step 2 bank combobox.
     * NOTE: legacy table name has a typo ('bancks'); we use it as-is.
     */
    public function actionAddBank()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $name = trim((string)Yii::$app->request->post('name', ''));
        return $this->addLookupRow([
            'name'        => $name,
            'table'       => '{{%bancks}}',
            'cacheKey'    => 'key_banks',
            'emptyError'  => 'يرجى إدخال اسم البنك.',
            'tooLongError'=> 'اسم البنك طويل جداً.',
            'failError'   => 'تعذّر حفظ البنك. حاول مرة أخرى.',
            'logLabel'    => 'bank',
        ]);
    }

    /**
     * Return enrichment metadata for a single employer (os_jobs row) so the
     * Step-2 combobox can warn the user when the chosen employer is missing
     * data the loan officer relies on later (address for visits, phones for
     * income verification, working hours for follow-ups).
     *
     * Response shape (JSON):
     *   { ok, id, name, has_address, has_phones, has_hours, missing:[…] }
     *
     * The endpoint is intentionally read-only and small — it is hit on every
     * combobox change so latency matters more than completeness.
     */
    public function actionJobMeta($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = (int)$id;
        if ($id <= 0) {
            return ['ok' => false, 'error' => 'invalid id'];
        }

        $db = Yii::$app->db;

        try {
            $row = $db->createCommand(
                "SELECT id, name, address_city, address_area, address_street, address_building,
                        latitude, longitude
                 FROM {{%jobs}}
                 WHERE id = :id AND (is_deleted IS NULL OR is_deleted = 0)
                 LIMIT 1",
                [':id' => $id]
            )->queryOne();

            if (!$row) {
                return ['ok' => false, 'error' => 'not found'];
            }

            $hasAddress = trim((string)($row['address_city']     ?? '')) !== ''
                       || trim((string)($row['address_area']     ?? '')) !== ''
                       || trim((string)($row['address_street']   ?? '')) !== ''
                       || trim((string)($row['address_building'] ?? '')) !== ''
                       || ((float)($row['latitude'] ?? 0) !== 0.0
                           && (float)($row['longitude'] ?? 0) !== 0.0);

            $hasPhones = false;
            try {
                $hasPhones = (int)$db->createCommand(
                    'SELECT COUNT(*) FROM {{%jobs_phones}}
                     WHERE job_id = :id AND (is_deleted IS NULL OR is_deleted = 0)',
                    [':id' => $id]
                )->queryScalar() > 0;
            } catch (\Throwable $e) { /* table missing on legacy installs */ }

            $hasHours = false;
            try {
                $hasHours = (int)$db->createCommand(
                    'SELECT COUNT(*) FROM {{%jobs_working_hours}} WHERE job_id = :id',
                    [':id' => $id]
                )->queryScalar() > 0;
            } catch (\Throwable $e) { /* table missing on legacy installs */ }

            $missing = [];
            if (!$hasAddress) $missing[] = 'address';
            if (!$hasPhones)  $missing[] = 'phones';
            if (!$hasHours)   $missing[] = 'hours';

            return [
                'ok'          => true,
                'id'          => (int)$row['id'],
                'name'        => $row['name'],
                'has_address' => $hasAddress,
                'has_phones'  => $hasPhones,
                'has_hours'   => $hasHours,
                'missing'     => $missing,
            ];
        } catch (\Throwable $e) {
            Yii::error('actionJobMeta failed: ' . $e->getMessage(), __METHOD__);
            return ['ok' => false, 'error' => 'server error'];
        }
    }

    /**
     * Generic "insert (or restore) a row in a {id, name, is_deleted?} lookup
     * table" helper. Used by actionAddCity / actionAddCitizen and any future
     * lookup endpoints with the same shape.
     *
     * Behaviour:
     *   • Trim + length-guard the input.
     *   • Detect optional columns (is_deleted, created_at, created_by) once.
     *   • Scan ALL rows (including soft-deleted) for an Arabic-normalized
     *     match → either return the matching active id, or restore a
     *     soft-deleted row by clearing is_deleted (preserves historical FKs).
     *   • Otherwise INSERT with proper audit columns.
     *   • Bust both the legacy and wizard-scoped cache keys for the table.
     *
     * @param array $opts {
     *     @var string $name         user-supplied display name
     *     @var string $table        Yii table token, e.g. '{{%city}}'
     *     @var string $cacheKey     params key for the legacy cache id
     *     @var string $emptyError   message when name is blank
     *     @var string $tooLongError message when name exceeds 100 chars
     *     @var string $failError    message on unexpected DB failure
     *     @var string $logLabel     short tag for log messages ('city', 'citizen')
     *     @var array  $extra        OPTIONAL — additional column values to
     *                               include in the INSERT (and the existing-
     *                               row UPDATE if the row was soft-deleted).
     *                               Useful when the target table has NOT NULL
     *                               columns without a DB default (e.g. jobs
     *                               needs job_type).
     * }
     * @return array JSON-shaped response (always with `ok` + either id/name/… or error)
     */
    protected function addLookupRow(array $opts)
    {
        $name  = (string)$opts['name'];
        $extra = isset($opts['extra']) && is_array($opts['extra']) ? $opts['extra'] : [];
        if ($name === '') return ['ok' => false, 'error' => $opts['emptyError']];
        if (mb_strlen($name) > 100) return ['ok' => false, 'error' => $opts['tooLongError']];

        $db = Yii::$app->db;
        $tableName = $db->schema->getRawTableName($opts['table']);
        $tq = $db->quoteTableName($tableName);

        $hasIsDeleted = false; $hasCreatedAt = false; $hasCreatedBy = false;
        $tableCols = [];
        try {
            $cols = $db->createCommand("SHOW COLUMNS FROM {$tq}")->queryAll();
            foreach ($cols as $c) {
                $tableCols[$c['Field']] = true;
                if ($c['Field'] === 'is_deleted') $hasIsDeleted = true;
                if ($c['Field'] === 'created_at') $hasCreatedAt = true;
                if ($c['Field'] === 'created_by') $hasCreatedBy = true;
            }
        } catch (\Throwable $e) { /* schema introspection is best-effort */ }

        // Drop any extras that the target table doesn't actually have so a
        // shared helper can be called with optimistic column lists.
        foreach ($extra as $col => $_) {
            if ($tableCols && !isset($tableCols[$col])) unset($extra[$col]);
        }

        try {
            $normalized = self::normalizeArabic($name);

            // Scan ALL rows (including soft-deleted) for a normalized match.
            $select = 'SELECT id, name'
                    . ($hasIsDeleted ? ', is_deleted' : '')
                    . " FROM {$tq}";
            $rows = $db->createCommand($select)->queryAll();
            foreach ($rows as $r) {
                if (self::normalizeArabic((string)$r['name']) !== $normalized) continue;

                if (!$hasIsDeleted || (int)$r['is_deleted'] === 0) {
                    $this->bustLookupCache($opts['cacheKey']);
                    return [
                        'ok'      => true,
                        'id'      => (int)$r['id'],
                        'name'    => $r['name'],
                        'existed' => true,
                    ];
                }

                // Soft-deleted match → restore in-place to keep referential history.
                $update = ['is_deleted' => 0];
                if ($hasCreatedAt) $update['updated_at'] = time();
                $db->createCommand()->update($tableName, $update, ['id' => $r['id']])->execute();
                $this->bustLookupCache($opts['cacheKey']);
                Yii::info("Wizard: restored soft-deleted {$opts['logLabel']} '$name' (id={$r['id']})", __METHOD__);
                return [
                    'ok'       => true,
                    'id'       => (int)$r['id'],
                    'name'     => $r['name'],
                    'existed'  => true,
                    'restored' => true,
                ];
            }

            // No match → fresh insert with the audit columns this schema has.
            $insert = ['name' => $name];
            if ($hasIsDeleted) $insert['is_deleted'] = 0;
            if ($hasCreatedAt) {
                $insert['created_at'] = time();
                $insert['updated_at'] = time();
            }
            if ($hasCreatedBy) {
                $insert['created_by'] = (int)Yii::$app->user->id;
                if (isset($tableCols['last_updated_by'])) {
                    $insert['last_updated_by'] = (int)Yii::$app->user->id;
                }
                if (isset($tableCols['updated_by'])) {
                    $insert['updated_by'] = (int)Yii::$app->user->id;
                }
            }
            // Caller-supplied required columns (e.g. jobs.job_type).
            foreach ($extra as $col => $val) {
                $insert[$col] = $val;
            }
            $db->createCommand()->insert($tableName, $insert)->execute();
            $newId = (int)$db->getLastInsertID();

            $this->bustLookupCache($opts['cacheKey']);
            Yii::info("Wizard: added new {$opts['logLabel']} '$name' (id=$newId)", __METHOD__);
            return ['ok' => true, 'id' => $newId, 'name' => $name, 'existed' => false];
        } catch (\Throwable $e) {
            Yii::error("addLookupRow ({$opts['logLabel']}) failed: " . $e->getMessage(), __METHOD__);
            return ['ok' => false, 'error' => $opts['failError']];
        }
    }

    /**
     * Invalidate cached lookup lists so newly-added rows appear on reload.
     * Clears both the legacy key (whole table) and the `:wizard:active`
     * filtered key the wizard's loadLookups() builds.
     */
    protected function bustLookupCache($paramsKey)
    {
        try {
            $base = Yii::$app->params[$paramsKey] ?? null;
            if (!$base) return;
            $cache = Yii::$app->cache;
            $cache->delete($base);
            $cache->delete($base . ':wizard:active');
        } catch (\Throwable $e) {
            // Cache invalidation is best-effort.
        }
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

        // Soft-delete-aware lookup fetcher. The legacy params-local SQL
        // only selects `id,name`, so we can't post-filter on is_deleted —
        // we rebuild the query here, pulling the column when it exists.
        // We use a dedicated cache key (suffixed `:wizard`) so this
        // narrower result set never poisons the legacy cache used by
        // older views that intentionally include all rows.
        $fetch = function ($keyName, $queryName, $defaultTable) use ($cache, $params, $duration, $db) {
            try {
                $baseKey = $params[$keyName] ?? $defaultTable;
                $cacheKey = $baseKey . ':wizard:active';

                return $cache->getOrSet($cacheKey, function () use ($db, $params, $queryName, $defaultTable) {
                    $sql = $params[$queryName] ?? null;
                    // Try to extract the table name out of the legacy SQL —
                    // falls back to the supplied default table.
                    $table = $defaultTable;
                    if (is_string($sql) && preg_match('/\bFROM\s+([{\w}%]+)/i', $sql, $m)) {
                        $table = $m[1];
                    }
                    $tableQuoted = $db->quoteTableName($table);

                    // Detect is_deleted column once (cached per request).
                    $hasIsDeleted = false;
                    try {
                        $cols = $db->createCommand("SHOW COLUMNS FROM {$tableQuoted} LIKE 'is_deleted'")
                                   ->queryAll();
                        $hasIsDeleted = !empty($cols);
                    } catch (\Throwable $e) { /* fall through */ }

                    $where = $hasIsDeleted
                        ? 'WHERE (is_deleted IS NULL OR is_deleted = 0)'
                        : '';
                    return $db->createCommand("SELECT id, name FROM {$tableQuoted} {$where} ORDER BY name")
                              ->queryAll();
                }, $duration);
            } catch (\Throwable $e) {
                Yii::warning("Wizard lookup '$keyName' failed: " . $e->getMessage(), __METHOD__);
                return [];
            }
        };

        // Cousins (relationship labels for guarantor rows). Not in the
        // default params query map, so we hit the table directly with the
        // same "tolerate-missing" guard used by other fetches.
        $cousins = [];
        try {
            $cousins = Yii::$app->db->createCommand(
                "SELECT id, name FROM {{%cousins}} ORDER BY name"
            )->queryAll();
        } catch (\Throwable $e) {
            Yii::warning('Wizard loadLookups skipped cousins: ' . $e->getMessage(), __METHOD__);
        }

        return [
            'cities'        => $fetch('key_city',          'city_query',          '{{%city}}'),
            'citizens'      => $fetch('key_citizen',       'citizen_query',       '{{%citizen}}'),
            'hearAboutUs'   => $fetch('key_hear_about_us', 'hear_about_us_query', '{{%hear_about_us}}'),
            'jobs'          => $fetch('key_jobs',          'jobs_query',          '{{%jobs}}'),
            'banks'         => $fetch('key_banks',         'banks_query',         '{{%bancks}}'),
            'cousins'       => $cousins,
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

    /**
     * Validate Step 2 — employment, income, social-security & bank.
     *
     * Required:
     *   • job_title          — selected from jobs lookup
     *   • total_salary       — > 0 numeric
     *   • is_social_security — 0 / 1
     *
     * Conditionally required:
     *   • social_security_number             when is_social_security == 1
     *   • social_security_salary_source      when has_social_security_salary == 'yes'
     *   • retirement_status + total_retirement_income
     *                                        when source ∈ {retirement_directorate, both}
     *
     * Optional but typed:
     *   • job_number, last_*_query_date, bank_*, account_number, total_retirement_income
     */
    protected function validateStep2($data)
    {
        $errors = [];

        // ── Required basics. ──
        $required = [
            'Customers[job_title]'         => 'جهة العمل',
            'Customers[total_salary]'      => 'الراتب الأساسي',
            'Customers[is_social_security]'=> 'حقل الاشتراك بالضمان',
        ];
        foreach ($required as $key => $label) {
            $val = $this->dotGet($data, $key);
            if ($val === null || trim((string)$val) === '') {
                $errors[$key] = "حقل «{$label}» مطلوب.";
            }
        }

        // ── total_salary numeric & sane. ──
        $salary = $this->dotGet($data, 'Customers[total_salary]');
        if ($salary !== null && $salary !== '') {
            if (!is_numeric($salary) || (float)$salary < 0) {
                $errors['Customers[total_salary]'] = 'الراتب يجب أن يكون رقماً موجباً.';
            } elseif ((float)$salary > 999999) {
                $errors['Customers[total_salary]'] = 'الراتب يبدو غير منطقي (> 999,999).';
            }
        }

        // ── job_title must be a positive integer (FK to jobs). ──
        $jobTitle = $this->dotGet($data, 'Customers[job_title]');
        if ($jobTitle !== null && $jobTitle !== '' && !ctype_digit((string)$jobTitle)) {
            $errors['Customers[job_title]'] = 'قيمة جهة العمل غير صالحة.';
        }

        // ── is_social_security must be 0/1. ──
        $isSocSec = $this->dotGet($data, 'Customers[is_social_security]');
        if ($isSocSec !== null && $isSocSec !== '' && !in_array((string)$isSocSec, ['0', '1'], true)) {
            $errors['Customers[is_social_security]'] = 'القيمة غير صالحة.';
        }

        // Conditionally required: subscription number when subscribed.
        if ((string)$isSocSec === '1') {
            $socNum = trim((string)$this->dotGet($data, 'Customers[social_security_number]'));
            if ($socNum === '') {
                $errors['Customers[social_security_number]'] = 'رقم اشتراك الضمان مطلوب لأنك أشرت إلى أن العميل مشترك.';
            } elseif (mb_strlen($socNum) > 50) {
                $errors['Customers[social_security_number]'] = 'رقم الاشتراك طويل جداً (> 50 خانة).';
            }
        }

        // ── has_social_security_salary must be yes/no/empty. ──
        $hasPension = $this->dotGet($data, 'Customers[has_social_security_salary]');
        if ($hasPension !== null && $hasPension !== ''
            && !in_array((string)$hasPension, ['yes', 'no'], true)) {
            $errors['Customers[has_social_security_salary]'] = 'القيمة غير صالحة.';
        }

        // Conditionally required: pension source when receiving.
        if ((string)$hasPension === 'yes') {
            $src = trim((string)$this->dotGet($data, 'Customers[social_security_salary_source]'));
            $allowedSrc = array_keys(Yii::$app->params['socialSecuritySources'] ?? [
                'social_security' => 1, 'retirement_directorate' => 1, 'both' => 1,
            ]);
            if ($src === '') {
                $errors['Customers[social_security_salary_source]'] = 'يرجى اختيار مصدر الراتب التقاعدي.';
            } elseif (!in_array($src, $allowedSrc, true)) {
                $errors['Customers[social_security_salary_source]'] = 'مصدر الراتب غير معروف.';
            }

            // Retirement-directorate sub-fields when source involves the directorate.
            if (in_array($src, ['retirement_directorate', 'both'], true)) {
                $rstatus = trim((string)$this->dotGet($data, 'Customers[retirement_status]'));
                if ($rstatus !== '' && !in_array($rstatus, ['effective', 'stopped'], true)) {
                    $errors['Customers[retirement_status]'] = 'حالة التقاعد غير صالحة.';
                }
                $rinc = $this->dotGet($data, 'Customers[total_retirement_income]');
                if ($rinc !== null && $rinc !== '') {
                    if (!is_numeric($rinc) || (float)$rinc < 0) {
                        $errors['Customers[total_retirement_income]'] = 'دخل التقاعد يجب أن يكون رقماً موجباً.';
                    } elseif ((float)$rinc > 999999) {
                        $errors['Customers[total_retirement_income]'] = 'دخل التقاعد يبدو غير منطقي.';
                    }
                }
            }
        }

        // ── Optional date fields — must parse if present, no future dates. ──
        foreach (['Customers[last_income_query_date]', 'Customers[last_job_query_date]'] as $dk) {
            $v = trim((string)$this->dotGet($data, $dk));
            if ($v === '') continue;
            $ts = strtotime($v);
            if ($ts === false) {
                $errors[$dk] = 'صيغة التاريخ غير صحيحة.';
            } elseif ($ts > time()) {
                $errors[$dk] = 'التاريخ لا يمكن أن يكون في المستقبل.';
            }
        }

        // ── Optional bank-account fields. ──
        $bankName = $this->dotGet($data, 'Customers[bank_name]');
        if ($bankName !== null && $bankName !== '' && !ctype_digit((string)$bankName)) {
            $errors['Customers[bank_name]'] = 'قيمة البنك غير صالحة.';
        }
        $branch = (string)$this->dotGet($data, 'Customers[bank_branch]');
        if (mb_strlen($branch) > 100) {
            $errors['Customers[bank_branch]'] = 'اسم الفرع طويل جداً (الحد الأقصى 100 حرف).';
        }
        $acc = (string)$this->dotGet($data, 'Customers[account_number]');
        if (mb_strlen($acc) > 50) {
            $errors['Customers[account_number]'] = 'رقم الحساب طويل جداً (الحد الأقصى 50 خانة).';
        }

        // ── job_number length. ──
        $jobNum = (string)$this->dotGet($data, 'Customers[job_number]');
        if (mb_strlen($jobNum) > 20) {
            $errors['Customers[job_number]'] = 'الرقم الوظيفي طويل جداً (الحد الأقصى 20 خانة).';
        }

        return $errors;
    }

    /**
     * Validate Step 3 — guarantors, primary address, and real-estate.
     *
     * Required:
     *   • At least 1 guarantor with non-empty name + phone + relationship.
     *   • address[address_city] must be filled.
     *
     * Conditional:
     *   • property_name + property_number when do_have_any_property == 1.
     *
     * Optional:
     *   • Additional guarantors (max 10), address area/street/etc.
     */
    protected function validateStep3($data)
    {
        $errors = [];

        // ── Guarantors. ──
        $guarantors = $this->dotGet($data, 'guarantors');
        if (!is_array($guarantors)) {
            $guarantors = [];
        }

        // Filter out completely empty rows so users aren't punished for an
        // accidentally-added blank row at the end.
        $filled = [];
        foreach ($guarantors as $i => $g) {
            if (!is_array($g)) continue;
            $hasAny = false;
            foreach (['owner_name', 'phone_number', 'phone_number_owner', 'fb_account'] as $f) {
                if (trim((string)($g[$f] ?? '')) !== '') { $hasAny = true; break; }
            }
            if ($hasAny) $filled[$i] = $g;
        }

        if (count($filled) === 0) {
            $errors['guarantors'] = 'يجب إضافة معرّف واحد على الأقل (الاسم + الهاتف + صلة القرابة).';
        }

        if (count($filled) > 10) {
            $errors['guarantors'] = 'الحد الأقصى للمعرّفين هو 10.';
        }

        foreach ($filled as $i => $g) {
            $name  = trim((string)($g['owner_name'] ?? ''));
            $phone = trim((string)($g['phone_number'] ?? ''));
            $rel   = trim((string)($g['phone_number_owner'] ?? ''));
            $fb    = trim((string)($g['fb_account'] ?? ''));

            if ($name === '') {
                $errors["guarantors[$i][owner_name]"] = 'الاسم مطلوب.';
            } elseif (mb_strlen($name) > 100) {
                $errors["guarantors[$i][owner_name]"] = 'الاسم طويل جداً (الحد الأقصى 100 حرف).';
            }

            if ($phone === '') {
                $errors["guarantors[$i][phone_number]"] = 'رقم الهاتف مطلوب.';
            } else {
                $digits = preg_replace('/\D+/', '', $phone);
                $okJO   = (bool)preg_match('/^(?:00962|962|0)?7[789]\d{7}$/', $digits);
                $okIntl = strlen($digits) >= 8 && strlen($digits) <= 15;
                if (!$okJO && !$okIntl) {
                    $errors["guarantors[$i][phone_number]"] = 'رقم الهاتف غير صالح.';
                }
            }

            if ($rel === '') {
                $errors["guarantors[$i][phone_number_owner]"] = 'حدّد صلة القرابة.';
            } elseif (mb_strlen($rel) > 100) {
                $errors["guarantors[$i][phone_number_owner]"] = 'القيمة طويلة جداً.';
            }

            if (mb_strlen($fb) > 255) {
                $errors["guarantors[$i][fb_account]"] = 'حساب فيسبوك طويل جداً.';
            }
        }

        // ── Address. ──
        $address = $this->dotGet($data, 'address');
        if (!is_array($address)) $address = [];

        $city = trim((string)($address['address_city'] ?? ''));
        if ($city === '') {
            $errors['address[address_city]'] = 'مدينة السكن مطلوبة.';
        } elseif (mb_strlen($city) > 100) {
            $errors['address[address_city]'] = 'اسم المدينة طويل جداً.';
        }

        foreach (['address_area' => 100, 'address_building' => 100,
                  'postal_code'  => 20,  'address'          => 255] as $field => $cap) {
            $v = (string)($address[$field] ?? '');
            if (mb_strlen($v) > $cap) {
                $errors["address[$field]"] = "القيمة طويلة جداً (الحد الأقصى {$cap}).";
            }
        }
        $street = (string)($address['address_street'] ?? '');
        if (mb_strlen($street) > 500) {
            $errors['address[address_street]'] = 'الشارع طويل جداً (الحد الأقصى 500 حرف).';
        }

        // ── Real-estate. ──
        $owns = $this->dotGet($data, 'Customers[do_have_any_property]');
        if ($owns !== null && $owns !== '' && !in_array((string)$owns, ['0', '1'], true)) {
            $errors['Customers[do_have_any_property]'] = 'القيمة غير صالحة.';
        }

        if ((string)$owns === '1') {
            $pname = trim((string)$this->dotGet($data, 'Customers[property_name]'));
            if ($pname === '') {
                $errors['Customers[property_name]'] = 'اسم/نوع العقار مطلوب.';
            } elseif (mb_strlen($pname) > 50) {
                $errors['Customers[property_name]'] = 'الاسم طويل جداً.';
            }
            $pnum = trim((string)$this->dotGet($data, 'Customers[property_number]'));
            if (mb_strlen($pnum) > 100) {
                $errors['Customers[property_number]'] = 'الرقم طويل جداً.';
            }
        }

        return $errors;
    }

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
