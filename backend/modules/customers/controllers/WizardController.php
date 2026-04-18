<?php

namespace backend\modules\customers\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use common\models\WizardDraft;
use common\helper\Permissions;

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
     * OCR scan bridge — Phase 5 wires this to the existing smart-media
     * extractor. Stubbed for now.
     */
    public function actionScan()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            'ok'    => false,
            'error' => 'المسح الذكي يُربط في المرحلة الخامسة.',
        ];
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
