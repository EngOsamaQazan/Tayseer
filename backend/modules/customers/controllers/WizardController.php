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
use yii\helpers\Url;
use common\models\WizardDraft;
use common\models\FahrasCheckLog;
use common\helper\Permissions;
use backend\modules\customers\components\VisionService;
use backend\models\Media;
use backend\helpers\MediaHelper;
use backend\modules\customers\models\Customers;
use backend\modules\customers\models\CustomersDocument;
use backend\modules\address\models\Address;
use backend\modules\phoneNumbers\models\PhoneNumbers;
use backend\modules\notification\models\Notification;
use backend\modules\realEstate\models\RealEstate;
use common\services\LocationResolverService;
use common\services\dto\FahrasVerdict;

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

    /**
     * Cached draft key for the current request — resolved once via
     * {@see resolveDraftKey()} and reused by every helper that touches the
     * `wizard_drafts` row (save / scan / extras / Fahras / finish).
     *
     * `null` means "not yet resolved"; callers should always go through
     * {@see draftKey()} which lazily computes it.
     *
     * @var string|null
     */
    private $_draftKey = null;

    /** {@inheritdoc} */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    // Edit-only entry points: gated by CUST_UPDATE.
                    [
                        'actions' => ['edit'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Permissions::can(Permissions::CUST_UPDATE);
                        },
                    ],
                    // Shared actions (save / validate / finish / scan / etc.) accept
                    // either permission; per-mode enforcement happens inline inside
                    // actionFinish() based on the active draft's _mode flag.
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Permissions::can(Permissions::CUST_CREATE)
                                || Permissions::can(Permissions::CUST_UPDATE);
                        },
                    ],
                ],
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
                    'upload-extra' => ['POST'],
                    'delete-extra' => ['POST'],
                    'fahras-check'   => ['POST'],
                    'fahras-search'  => ['POST'],
                    'fahras-override' => ['POST'],
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

    /**
     * Edit-mode entry — loads an existing customer + sub-models, hydrates a
     * scoped wizard draft (`customer_edit:{id}`) and renders the same shell
     * with `mode='edit'`.
     *
     * Distinct draft slot guarantees that a user's in-flight create draft
     * (`customer_create`) is NEVER clobbered by clicking «تعديل» on the
     * customer list — and vice versa.
     *
     * Re-hydration policy: every visit to /wizard/edit?id=X re-reads the
     * authoritative DB row, overwriting any stale draft that may have been
     * left behind by a previous edit session. Without this, the user would
     * silently see hours-old data.
     */
    public function actionEdit($id, $notificationID = 0)
    {
        $id = (int)$id;
        if ($id <= 0) {
            throw new NotFoundHttpException('العميل غير موجود.');
        }

        // Parity with the legacy actionUpdate: clear the originating notification.
        if ((int)$notificationID !== 0) {
            try {
                Yii::$app->notifications->setReaded((int)$notificationID);
            } catch (\Throwable $e) {
                Yii::warning('Wizard edit: notification mark-read failed: ' . $e->getMessage(), __METHOD__);
            }
        }

        $customer = Customers::findOne($id);
        if (!$customer) {
            throw new NotFoundHttpException('العميل غير موجود.');
        }

        // Pin the active draft slot for this entire request before any helper
        // touches the draft store.
        $key = 'customer_edit:' . $id;
        $this->_draftKey = $key;

        // Re-hydrate from DB on every entry (auto-clear any stale edit draft).
        $payload = $this->hydratePayloadFromCustomer($customer);
        $payload['_mode']         = 'edit';
        $payload['_customer_id']  = $id;
        $payload['_hydrated_at']  = time();
        $payload['_step']         = 1;
        $payload['_summary']      = $this->buildSummary($payload);
        $payload['_updated']      = time();

        WizardDraft::saveAutoDraft(
            Yii::$app->user->id,
            $key,
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        );

        $draft = WizardDraft::loadAutoDraft(Yii::$app->user->id, $key);

        return $this->render('layout', [
            'draft'       => $draft,
            'payload'     => $payload,
            'currentStep' => 1,
            'totalSteps'  => self::TOTAL_STEPS,
            'lookups'     => $this->loadLookups(),
            'mode'        => 'edit',
            'customerId'  => $id,
        ]);
    }

    /**
     * Build the wizard payload from an existing customer record + its
     * sub-models (Address, PhoneNumbers, RealEstate, CustomersDocument,
     * Media for photo/extras).
     *
     * Output mirrors the create-flow payload shape so every existing
     * step partial / validator / save endpoint works without branching.
     */
    protected function hydratePayloadFromCustomer(Customers $c): array
    {
        $primaryPhone = trim((string)$c->primary_phone_number);

        // ── Identity (Step 1) ──
        $step1 = [
            'Customers' => [
                'name'                 => (string)($c->name ?? ''),
                'id_number'            => (string)($c->id_number ?? ''),
                'sex'                  => $c->sex !== null ? (string)$c->sex : '',
                'birth_date'           => (string)($c->birth_date ?? ''),
                'city'                 => $c->city !== null && $c->city !== '' ? (string)$c->city : '',
                'citizen'              => (string)($c->citizen ?? ''),
                'primary_phone_number' => $primaryPhone,
                'facebook_account'     => (string)($c->facebook_account ?? ''),
                'email'                => (string)($c->email ?? ''),
                'hear_about_us'        => $c->hear_about_us !== null ? (string)$c->hear_about_us : '',
                'notes'                => (string)($c->notes ?? ''),
            ],
        ];

        // ── Financial (Step 2) ──
        $step2 = [
            'Customers' => [
                'job_title'                     => $c->job_title !== null ? (string)$c->job_title : '',
                'job_number'                    => (string)($c->job_number ?? ''),
                'total_salary'                  => $c->total_salary !== null ? (string)$c->total_salary : '',
                'is_social_security'            => $c->is_social_security !== null ? (string)$c->is_social_security : '',
                'social_security_number'        => (string)($c->social_security_number ?? ''),
                'has_social_security_salary'    => (string)($c->has_social_security_salary ?? ''),
                'social_security_salary_source' => (string)($c->social_security_salary_source ?? ''),
                'retirement_status'             => (string)($c->retirement_status ?? ''),
                'total_retirement_income'       => $c->total_retirement_income !== null ? (string)$c->total_retirement_income : '',
                'last_income_query_date'        => (string)($c->last_income_query_date ?? ''),
                'last_job_query_date'           => (string)($c->last_job_query_date ?? ''),
                'bank_name'                     => $c->bank_name !== null ? (string)$c->bank_name : '',
                'bank_branch'                   => (string)($c->bank_branch ?? ''),
                'account_number'                => (string)($c->account_number ?? ''),
                // Real-estate single-row legacy fields (kept in payload for
                // backwards-compat with anything still reading them; the new
                // canonical source of truth is step2.realestates[]).
                'do_have_any_property'          => $c->do_have_any_property !== null ? (string)$c->do_have_any_property : '',
                'property_name'                 => (string)($c->property_name ?? ''),
                'property_number'               => (string)($c->property_number ?? ''),
            ],
        ];

        // ── RealEstate rows (multi). ──
        // Promote the legacy single Customers.property_* into a synthetic row
        // when no RealEstate row exists yet (so the user doesn't lose data on
        // first edit-and-save). Otherwise we rely strictly on the table.
        $realEstates = [];
        try {
            $rows = RealEstate::find()->where(['customer_id' => (int)$c->id])->all();
            foreach ($rows as $r) {
                $realEstates[] = [
                    'id'              => (int)$r->id,
                    'property_type'   => (string)($r->property_type ?? ''),
                    'property_number' => (string)($r->property_number ?? ''),
                ];
            }
        } catch (\Throwable $e) {
            Yii::warning('hydrate: realEstate read failed: ' . $e->getMessage(), __METHOD__);
        }
        if (empty($realEstates)) {
            $legacyName = trim((string)($c->property_name ?? ''));
            $legacyNum  = trim((string)($c->property_number ?? ''));
            if ($legacyName !== '' || $legacyNum !== '') {
                $realEstates[] = [
                    'id'              => 0,
                    'property_type'   => $legacyName,
                    'property_number' => $legacyNum,
                ];
            }
        }
        $step2['realestates'] = $realEstates;

        // ── Addresses (Step 3) ──
        // Map the first row whose address_type=2 (residential) into "home";
        // first row of address_type=1 (work) into "work". Extra rows are
        // appended to home/work in numerical order — they remain editable
        // through future iterations of the address repeater.
        $home = [];
        $work = [];
        try {
            $addrRows = Address::find()->where(['customers_id' => (int)$c->id])->all();
            foreach ($addrRows as $a) {
                $row = [
                    'id'               => (int)$a->id,
                    'address_type'     => (int)($a->address_type ?? 2),
                    'address_city'     => (string)($a->address_city     ?? ''),
                    'address_area'     => (string)($a->address_area     ?? ''),
                    'address_street'   => (string)($a->address_street   ?? ''),
                    'address_building' => (string)($a->address_building ?? ''),
                    'postal_code'      => (string)($a->postal_code      ?? ''),
                    'address'          => (string)($a->address          ?? ''),
                    'latitude'         => $a->latitude  !== null ? (string)$a->latitude  : '',
                    'longitude'        => $a->longitude !== null ? (string)$a->longitude : '',
                    'plus_code'        => (string)($a->plus_code        ?? ''),
                ];
                if ((int)$a->address_type === 1 && empty($work)) {
                    $work = $row;
                } elseif (empty($home)) {
                    $home = $row;
                }
            }
        } catch (\Throwable $e) {
            Yii::warning('hydrate: address read failed: ' . $e->getMessage(), __METHOD__);
        }

        // ── Guarantors (PhoneNumbers, excluding the primary phone duplicate). ──
        $guarantors = [];
        try {
            $phRows = PhoneNumbers::find()->where(['customers_id' => (int)$c->id])->all();
            $primaryE164 = $primaryPhone !== ''
                ? \backend\helpers\PhoneHelper::toE164($primaryPhone)
                : '';
            foreach ($phRows as $p) {
                $row = [
                    'id'                 => (int)$p->id,
                    'owner_name'         => (string)($p->owner_name         ?? ''),
                    'phone_number'       => (string)($p->phone_number       ?? ''),
                    'phone_number_owner' => (string)($p->phone_number_owner ?? ''),
                    'fb_account'         => (string)($p->fb_account         ?? ''),
                ];
                $rowE164 = (string)$row['phone_number'] !== ''
                    ? \backend\helpers\PhoneHelper::toE164((string)$row['phone_number'])
                    : '';
                // Treat the row as a duplicate of the primary phone only if
                // BOTH the phone and (any) name match the customer's own —
                // protects against legacy rows that re-used the primary phone
                // as a guarantor entry on purpose.
                if ($primaryE164 !== '' && $rowE164 === $primaryE164
                    && trim((string)$row['owner_name']) === trim((string)$c->name)) {
                    continue;
                }
                $guarantors[] = $row;
            }
        } catch (\Throwable $e) {
            Yii::warning('hydrate: phoneNumbers read failed: ' . $e->getMessage(), __METHOD__);
        }

        $step3 = [
            'addresses'  => ['home' => $home, 'work' => $work],
            'guarantors' => $guarantors,
        ];

        // ── Identity scan info (CustomersDocument + Media). ──
        $scan = [];
        try {
            $doc = CustomersDocument::find()
                ->where(['customer_id' => (int)$c->id])
                ->orderBy(['id' => SORT_DESC])
                ->one();
            if ($doc) {
                $scan['document_number'] = (string)($doc->document_number ?? '');
                $scan['document_type']   = (string)($doc->document_type   ?? '');
            }

            $images = [];
            // ID-card scans: groupName starts with '0' (civil) or '4'
            // (military), with an optional '_front' / '_back' suffix.
            $mediaRows = Media::find()
                ->where(['customer_id' => (int)$c->id])
                ->andWhere(['or',
                    ['groupName' => ['0', '4', '0_front', '0_back', '4_front', '4_back']],
                ])
                ->orderBy(['id' => SORT_ASC])
                ->all();
            foreach ($mediaRows as $m) {
                $g = (string)$m->groupName;
                if (substr($g, -5) === '_back') {
                    $images['back'] = (int)$m->id;
                } elseif (substr($g, -6) === '_front') {
                    $images['front'] = (int)$m->id;
                } elseif (in_array($g, ['0', '4'], true) && empty($images['front'])) {
                    $images['front'] = (int)$m->id;
                }
            }
            if (!empty($images)) {
                $scan['images'] = $images;
            }
        } catch (\Throwable $e) {
            Yii::warning('hydrate: scan/media read failed: ' . $e->getMessage(), __METHOD__);
        }

        // ── Extras: personal photo (groupName='8') + docs (groupName='9'). ──
        $extras = ['photo' => null, 'docs' => []];
        try {
            $photo = Media::find()
                ->where(['customer_id' => (int)$c->id, 'groupName' => '8'])
                ->orderBy(['id' => SORT_DESC])
                ->one();
            if ($photo) {
                $extras['photo'] = [
                    'image_id'  => (int)$photo->id,
                    'url'       => (string)$photo->getUrl(),
                    'file_name' => (string)$photo->fileName,
                ];
            }
            $docRows = Media::find()
                ->where(['customer_id' => (int)$c->id, 'groupName' => '9'])
                ->orderBy(['id' => SORT_ASC])
                ->all();
            foreach ($docRows as $d) {
                $extras['docs'][] = [
                    'image_id'  => (int)$d->id,
                    'url'       => (string)$d->getUrl(),
                    'file_name' => (string)$d->fileName,
                ];
            }
        } catch (\Throwable $e) {
            Yii::warning('hydrate: extras read failed: ' . $e->getMessage(), __METHOD__);
        }

        return [
            'step1'   => $step1,
            'step2'   => $step2,
            'step3'   => $step3,
            '_scan'   => $scan,
            '_extras' => $extras,
        ];
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

        WizardDraft::saveAutoDraft(Yii::$app->user->id, $this->draftKey(), $finalJson);

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

        $draftKey = $this->draftKey();
        $draft = WizardDraft::loadAutoDraft(Yii::$app->user->id, $draftKey);
        if (!$draft) {
            return ['ok' => false, 'error' => 'لا توجد مسودة لاعتمادها.'];
        }

        $payload = $this->decodePayload($draft);

        // ── Branch on mode: edit vs create. Per-mode permission enforced inline.
        $mode = (string)($payload['_mode'] ?? 'create');
        if ($mode === 'edit') {
            if (!Permissions::can(Permissions::CUST_UPDATE)) {
                return ['ok' => false, 'error' => 'لا تملك صلاحية تعديل العملاء.'];
            }
            return $this->finishEdit($payload, $draftKey);
        }
        if (!Permissions::can(Permissions::CUST_CREATE)) {
            return ['ok' => false, 'error' => 'لا تملك صلاحية إضافة عملاء.'];
        }

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

        // Address payload — supports both the new addresses[home|work]
        // shape and the legacy single-address shape (older drafts saved
        // under "step3.address"). Legacy data is mapped onto the "home"
        // slot so no field is silently dropped.
        $addresses = $payload['step3']['addresses'] ?? null;
        if (!is_array($addresses)) {
            $legacy = $payload['step3']['address'] ?? [];
            $addresses = [
                'home' => is_array($legacy) ? $legacy : [],
            ];
        }
        $guarantors = $payload['step3']['guarantors'] ?? [];
        if (!is_array($guarantors)) $guarantors = [];

        // RealEstate rows (multi). Same shape as edit-mode finishEdit so
        // both flows treat properties identically. The legacy single-row
        // Customers.property_name / property_number columns are mirrored
        // from the FIRST non-empty row for backward compatibility with
        // downstream reports that haven't migrated yet.
        $realestates = $payload['step2']['realestates'] ?? [];
        if (!is_array($realestates)) $realestates = [];
        $hasRealEstate   = false;
        $firstRealEstate = ['property_type' => '', 'property_number' => ''];
        foreach ($realestates as $re) {
            if (!is_array($re)) continue;
            $t = trim((string)($re['property_type']   ?? ''));
            $n = trim((string)($re['property_number'] ?? ''));
            if ($t !== '' || $n !== '') {
                $hasRealEstate   = true;
                $firstRealEstate = ['property_type' => $t, 'property_number' => $n];
                break;
            }
        }
        $custAttr['do_have_any_property'] = $hasRealEstate ? 1 : 0;
        if ($hasRealEstate) {
            $custAttr['property_name']   = $firstRealEstate['property_type'];
            $custAttr['property_number'] = $firstRealEstate['property_number'];
        } else {
            $custAttr['property_name']   = null;
            $custAttr['property_number'] = null;
        }

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

            // ── 3a-pre. Link the recorded Fahras checks to the new customer
            //          for audit reporting ("show me every Fahras check that
            //          led to creating customer #N"). Best-effort.
            try {
                $idNum = (string)($custAttr['id_number'] ?? '');
                if ($idNum !== '') {
                    FahrasCheckLog::updateAll(
                        ['customer_id' => (int)$customer->id],
                        ['and',
                            ['id_number' => $idNum],
                            ['customer_id' => null],
                            ['>=', 'created_at', time() - 7200], // last 2h only
                        ]
                    );
                }
            } catch (\Throwable $e) {
                Yii::warning('Wizard finish: link FahrasCheckLog failed: ' . $e->getMessage(), __METHOD__);
            }

            // ── 3a. Addresses (residential + work, each saved as its own
            //       row). A block is persisted only when at least one
            //       text field carries content — empty blocks are silently
            //       skipped so an unfilled "work address" doesn't pollute
            //       the DB with phantom rows. ──
            $defaultTypeFor = ['home' => 2, 'work' => 1];
            foreach ($addresses as $slotKey => $addr) {
                if (!is_array($addr)) continue;

                $hasAddr = false;
                foreach (['address_city', 'address_area', 'address_street',
                          'address_building', 'postal_code', 'address'] as $k) {
                    if (trim((string)($addr[$k] ?? '')) !== '') { $hasAddr = true; break; }
                }
                if (!$hasAddr) continue;

                // Cast geo fields: empty string → NULL so the DB
                // doesn't store a 0,0 (Atlantic) coordinate by accident.
                $lat  = isset($addr['latitude'])  && $addr['latitude']  !== '' ? (float)$addr['latitude']  : null;
                $lng  = isset($addr['longitude']) && $addr['longitude'] !== '' ? (float)$addr['longitude'] : null;
                $plus = isset($addr['plus_code']) && $addr['plus_code'] !== '' ? (string)$addr['plus_code'] : null;

                // Resolve type code: trust the explicit field if present,
                // otherwise fall back to the slot's conventional code
                // (home=2, work=1) so legacy payloads still type correctly.
                $typeCode = isset($addr['address_type']) && $addr['address_type'] !== ''
                    ? (int)$addr['address_type']
                    : ($defaultTypeFor[$slotKey] ?? 2);

                $addrModel = new Address();
                $addrModel->setAttributes([
                    'customers_id'     => $customer->id,
                    'address_type'     => $typeCode,
                    'address_city'     => $addr['address_city']     ?? null,
                    'address_area'     => $addr['address_area']     ?? null,
                    'address_street'   => $addr['address_street']   ?? null,
                    'address_building' => $addr['address_building'] ?? null,
                    'postal_code'      => $addr['postal_code']      ?? null,
                    'address'          => $addr['address']          ?? null,
                    'latitude'         => $lat,
                    'longitude'        => $lng,
                    'plus_code'        => $plus,
                ], false);
                if (!$addrModel->save()) {
                    $tx->rollBack();
                    Yii::error("Wizard finish: address save failed (slot={$slotKey}): "
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

            // ── 3c. RealEstate properties (multi-row). Empty rows are
            //       silently skipped so an unfilled "add another" placeholder
            //       doesn't pollute the table. ──
            foreach ($realestates as $re) {
                if (!is_array($re)) continue;
                $type = trim((string)($re['property_type']   ?? ''));
                $num  = trim((string)($re['property_number'] ?? ''));
                if ($type === '' && $num === '') continue;
                $reModel = new RealEstate();
                $reModel->setAttributes([
                    'customer_id'     => $customer->id,
                    'property_type'   => $type,
                    'property_number' => $num,
                ], false);
                if (!$reModel->save(false)) {
                    $tx->rollBack();
                    Yii::error('Wizard finish: realestate save failed: '
                        . print_r($reModel->getErrors(), true), __METHOD__);
                    return ['ok' => false, 'error' => 'تعذّر حفظ بيانات العقارات.'];
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

        // ── 4a. Adopt orphan extras (personal photo + ad-hoc documents).
        // Also writes customers.selected_image for the photo so legacy
        // contract-print-preview code paths keep rendering the buyer's
        // headshot without changes.
        try {
            $extrasAdopted = $this->linkExtrasToCustomer((int)$customer->id, $payload);
            $adopted += (int)($extrasAdopted['photo'] ?? 0)
                      + (int)($extrasAdopted['docs']  ?? 0);
        } catch (\Throwable $e) {
            Yii::warning('Wizard finish: linkExtrasToCustomer failed: '
                . $e->getMessage(), __METHOD__);
        }

        // ── 4b. Persist the SS statement (header + subscriptions + salaries). ──
        // Best-effort: any failure here should not block customer creation,
        // since the customer + media file are already saved successfully.
        try {
            $this->persistSocialSecurityStatement((int)$customer->id, $payload);
        } catch (\Throwable $e) {
            Yii::warning('Wizard finish: persistSocialSecurityStatement failed: '
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
        WizardDraft::clearAutoDraft(Yii::$app->user->id, $this->draftKey());

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
     * Edit-flow finalize — UPDATE an existing Customer + delta-save its
     * sub-models (Address / PhoneNumbers / RealEstate). Mirrors the legacy
     * CustomersController::actionUpdate's `array_diff(oldIds, newIds)`
     * pattern so deletions, modifications, and additions all round-trip.
     *
     * Concurrency: we compare `_hydrated_at` (timestamp captured at the
     * moment we read the customer into the wizard) with the row's current
     * `updated_at`. If another user touched the record meanwhile we abort
     * with a `conflict:true` envelope so the UI can ask the user to refresh.
     *
     * Fahras: deliberately skipped — see {@see runFahrasGate} for rationale.
     *
     * @param array  $payload   Full decoded wizard payload.
     * @param string $draftKey  The 'customer_edit:{id}' slot to clear on success.
     * @return array            JSON envelope (ok | conflict | error).
     */
    protected function finishEdit(array $payload, string $draftKey)
    {
        $customerId = (int)($payload['_customer_id'] ?? 0);
        if ($customerId <= 0) {
            return ['ok' => false, 'error' => 'لا يمكن تحديد العميل المراد تعديله.'];
        }

        $customer = Customers::findOne($customerId);
        if (!$customer) {
            return ['ok' => false, 'error' => 'العميل غير موجود.'];
        }

        // ── 1. Optimistic-concurrency check. ──
        $hydratedAt = (int)($payload['_hydrated_at'] ?? 0);
        $rowUpdated = (int)($customer->updated_at ?? 0);
        if ($hydratedAt > 0 && $rowUpdated > $hydratedAt + 5) {
            // 5s slack absorbs clock-skew between PHP/MySQL hosts.
            return [
                'ok'       => false,
                'conflict' => true,
                'error'    => 'تم تعديل بيانات هذا العميل من مستخدم آخر بعد فتحك للنموذج. أعد تحميل الصفحة لرؤية أحدث البيانات قبل المتابعة.',
            ];
        }

        // ── 2. Per-step validation. ──
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
                'error'  => 'تعذّر حفظ التعديلات: بعض الخطوات تحتاج إلى تصحيح. عُد إليها وأكمل الحقول الناقصة.',
                'errors' => $allErrors,
            ];
        }

        // ── 3. Merge Customers attributes from all steps. ──
        $custAttr = [];
        foreach (['step1', 'step2', 'step3'] as $sk) {
            $part = $payload[$sk]['Customers'] ?? null;
            if (is_array($part)) $custAttr = array_merge($custAttr, $part);
        }
        // Normalize empty strings on optional FK fields → NULL.
        foreach (['job_title', 'bank_name', 'address_city', 'citizen_id', 'hear_about_us'] as $optFk) {
            if (isset($custAttr[$optFk]) && $custAttr[$optFk] === '') {
                $custAttr[$optFk] = null;
            }
        }

        // Prepare sub-model payloads.
        $addresses = $payload['step3']['addresses'] ?? null;
        if (!is_array($addresses)) {
            $legacy = $payload['step3']['address'] ?? [];
            $addresses = ['home' => is_array($legacy) ? $legacy : []];
        }
        $guarantors = $payload['step3']['guarantors'] ?? [];
        if (!is_array($guarantors)) $guarantors = [];

        $realestates = $payload['step2']['realestates'] ?? [];
        if (!is_array($realestates)) $realestates = [];

        // Derive `do_have_any_property` from the live realestates list so
        // the customers row stays consistent with the repeater (legacy
        // single-row property_name/property_number columns are best-effort).
        $hasRealEstate = false;
        $firstRealEstate = ['property_type' => '', 'property_number' => ''];
        foreach ($realestates as $re) {
            if (!is_array($re)) continue;
            $t = trim((string)($re['property_type']   ?? ''));
            $n = trim((string)($re['property_number'] ?? ''));
            if ($t !== '' || $n !== '') {
                $hasRealEstate = true;
                $firstRealEstate = [
                    'property_type'   => $t,
                    'property_number' => $n,
                ];
                break;
            }
        }
        $custAttr['do_have_any_property'] = $hasRealEstate ? 1 : 0;
        if ($hasRealEstate) {
            $custAttr['property_name']   = $firstRealEstate['property_type'];
            $custAttr['property_number'] = $firstRealEstate['property_number'];
        } else {
            $custAttr['property_name']   = null;
            $custAttr['property_number'] = null;
        }

        // ── 4. Persist inside a single transaction. ──
        $db = Yii::$app->db;
        $tx = $db->beginTransaction();
        try {
            // 4a. UPDATE Customers row. Only set attributes the wizard owns
            // (avoids accidentally clobbering audit/system columns).
            $editableAttrs = array_keys($custAttr);
            $customer->setAttributes($custAttr, false);
            // Restrict the column list passed to UPDATE so unrelated columns
            // never get touched even if some safe attribute leaks in.
            if (!$customer->save(false, $editableAttrs)) {
                $tx->rollBack();
                Yii::error('Wizard finishEdit: customer save failed: '
                    . print_r($customer->getErrors(), true), __METHOD__);
                return ['ok' => false, 'error' => 'تعذّر حفظ بيانات العميل.'];
            }

            // 4b. Addresses — delta save. Build the desired set first.
            $oldAddresses = Address::find()
                ->where(['customers_id' => $customerId])
                ->all();
            $oldAddrIds = array_map(function ($a) { return (int)$a->id; }, $oldAddresses);

            $defaultTypeFor = ['home' => 2, 'work' => 1];
            $keptAddrIds = [];
            foreach ($addresses as $slotKey => $addr) {
                if (!is_array($addr)) continue;

                $hasAddr = false;
                foreach (['address_city', 'address_area', 'address_street',
                          'address_building', 'postal_code', 'address'] as $k) {
                    if (trim((string)($addr[$k] ?? '')) !== '') { $hasAddr = true; break; }
                }
                if (!$hasAddr) continue;

                $lat  = isset($addr['latitude'])  && $addr['latitude']  !== '' ? (float)$addr['latitude']  : null;
                $lng  = isset($addr['longitude']) && $addr['longitude'] !== '' ? (float)$addr['longitude'] : null;
                $plus = isset($addr['plus_code']) && $addr['plus_code'] !== '' ? (string)$addr['plus_code'] : null;
                $typeCode = isset($addr['address_type']) && $addr['address_type'] !== ''
                    ? (int)$addr['address_type']
                    : ($defaultTypeFor[$slotKey] ?? 2);

                $existingId = (int)($addr['id'] ?? 0);
                $addrModel = $existingId > 0
                    ? (Address::findOne($existingId) ?: new Address())
                    : new Address();

                $addrModel->setAttributes([
                    'customers_id'     => $customerId,
                    'address_type'     => $typeCode,
                    'address_city'     => $addr['address_city']     ?? null,
                    'address_area'     => $addr['address_area']     ?? null,
                    'address_street'   => $addr['address_street']   ?? null,
                    'address_building' => $addr['address_building'] ?? null,
                    'postal_code'      => $addr['postal_code']      ?? null,
                    'address'          => $addr['address']          ?? null,
                    'latitude'         => $lat,
                    'longitude'        => $lng,
                    'plus_code'        => $plus,
                ], false);
                if (!$addrModel->save()) {
                    $tx->rollBack();
                    Yii::error("Wizard finishEdit: address save failed (slot={$slotKey}): "
                        . print_r($addrModel->getErrors(), true), __METHOD__);
                    return ['ok' => false, 'error' => 'تعذّر حفظ بيانات العنوان.'];
                }
                $keptAddrIds[] = (int)$addrModel->id;
            }
            $deletedAddrIds = array_diff($oldAddrIds, $keptAddrIds);
            if (!empty($deletedAddrIds)) {
                Address::deleteAll(['id' => array_values($deletedAddrIds)]);
            }

            // 4c. Guarantors (PhoneNumbers) — delta save.
            $oldPhones = PhoneNumbers::find()
                ->where(['customers_id' => $customerId])
                ->all();
            $oldPhoneIds = array_map(function ($p) { return (int)$p->id; }, $oldPhones);

            $keptPhoneIds = [];
            foreach ($guarantors as $g) {
                if (!is_array($g)) continue;
                $name  = trim((string)($g['owner_name']        ?? ''));
                $phone = trim((string)($g['phone_number']      ?? ''));
                $rel   = trim((string)($g['phone_number_owner'] ?? ''));
                $fb    = trim((string)($g['fb_account']        ?? ''));
                if ($name === '' && $phone === '' && $rel === '' && $fb === '') {
                    continue;
                }
                $existingId = (int)($g['id'] ?? 0);
                $pn = $existingId > 0
                    ? (PhoneNumbers::findOne($existingId) ?: new PhoneNumbers())
                    : new PhoneNumbers();
                $pn->setAttributes([
                    'customers_id'       => $customerId,
                    'owner_name'         => $name,
                    'phone_number'       => $phone,
                    'phone_number_owner' => $rel,
                    'fb_account'         => $fb !== '' ? $fb : null,
                ], false);
                if (!$pn->save(false)) {
                    $tx->rollBack();
                    Yii::error('Wizard finishEdit: phone save failed: '
                        . print_r($pn->getErrors(), true), __METHOD__);
                    return ['ok' => false, 'error' => 'تعذّر حفظ بيانات المعرّفين.'];
                }
                $keptPhoneIds[] = (int)$pn->id;
            }
            $deletedPhoneIds = array_diff($oldPhoneIds, $keptPhoneIds);
            if (!empty($deletedPhoneIds)) {
                PhoneNumbers::deleteAll(['id' => array_values($deletedPhoneIds)]);
            }

            // 4d. RealEstate — delta save.
            $oldRealEstates = RealEstate::find()
                ->where(['customer_id' => $customerId])
                ->all();
            $oldReIds = array_map(function ($r) { return (int)$r->id; }, $oldRealEstates);

            $keptReIds = [];
            foreach ($realestates as $re) {
                if (!is_array($re)) continue;
                $type = trim((string)($re['property_type']   ?? ''));
                $num  = trim((string)($re['property_number'] ?? ''));
                if ($type === '' && $num === '') continue;
                $existingId = (int)($re['id'] ?? 0);
                $reModel = $existingId > 0
                    ? (RealEstate::findOne($existingId) ?: new RealEstate())
                    : new RealEstate();
                $reModel->setAttributes([
                    'customer_id'     => $customerId,
                    'property_type'   => $type,
                    'property_number' => $num,
                ], false);
                if (!$reModel->save(false)) {
                    $tx->rollBack();
                    Yii::error('Wizard finishEdit: realestate save failed: '
                        . print_r($reModel->getErrors(), true), __METHOD__);
                    return ['ok' => false, 'error' => 'تعذّر حفظ بيانات العقارات.'];
                }
                $keptReIds[] = (int)$reModel->id;
            }
            $deletedReIds = array_diff($oldReIds, $keptReIds);
            if (!empty($deletedReIds)) {
                RealEstate::deleteAll(['id' => array_values($deletedReIds)]);
            }

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error('Wizard finishEdit: unexpected error: ' . $e->getMessage()
                . "\n" . $e->getTraceAsString(), __METHOD__);
            return ['ok' => false, 'error' => 'حدث خطأ غير متوقع أثناء حفظ التعديلات.'];
        }

        // ── 5. Adopt any newly uploaded scan/extras (best-effort). ──
        try {
            $this->linkScanImagesToCustomer($customerId, $payload);
        } catch (\Throwable $e) {
            Yii::warning('Wizard finishEdit: linkScanImagesToCustomer failed: ' . $e->getMessage(), __METHOD__);
        }
        try {
            $this->linkExtrasToCustomer($customerId, $payload);
        } catch (\Throwable $e) {
            Yii::warning('Wizard finishEdit: linkExtrasToCustomer failed: ' . $e->getMessage(), __METHOD__);
        }

        // ── 6. Refresh customer caches (best-effort). ──
        try {
            $params = Yii::$app->params;
            $cache  = Yii::$app->cache;
            if (!empty($params['key_customers']) && !empty($params['customers_query'])) {
                $cache->set(
                    $params['key_customers'],
                    Yii::$app->db->createCommand($params['customers_query'])->queryAll(),
                    $params['time_duration'] ?? 3600
                );
            }
            if (!empty($params['key_customers_name']) && !empty($params['customers_name_query'])) {
                $cache->set(
                    $params['key_customers_name'],
                    Yii::$app->db->createCommand($params['customers_name_query'])->queryAll(),
                    $params['time_duration'] ?? 3600
                );
            }
        } catch (\Throwable $e) {
            Yii::warning('Wizard finishEdit: cache refresh failed: ' . $e->getMessage(), __METHOD__);
        }

        // ── 7. Drop the per-customer edit draft. ──
        WizardDraft::clearAutoDraft(Yii::$app->user->id, $draftKey);

        return [
            'ok'           => true,
            'customer_id'  => $customerId,
            'customer_name'=> (string)($customer->name ?? ''),
            'redirect'     => \yii\helpers\Url::to([
                '/customers/customers/view', 'id' => $customerId,
            ]),
            'message'      => 'تم حفظ تعديلات العميل بنجاح.',
        ];
    }

    /* ════════════════════════════════════════════════════════════════
       FAHRAS INTEGRATION (Step 1 verdict gate + by-name lookup + override)
       ════════════════════════════════════════════════════════════════ */

    /**
     * Live verdict check used by Step 1 of the wizard.
     *
     * The browser POSTs `id_number` (+ optional `name`, `phone`); we ask
     * Fahras for a verdict, persist the attempt to {@see FahrasCheckLog},
     * and return a JSON envelope the front-end uses to lock/unlock the
     * "Next" button and render the verdict card.
     *
     * Response:
     *   { ok: true, enabled: true, verdict: 'can_sell|cannot_sell|...',
     *     reason_code, reason_ar, matches: [...], remote_errors: [...],
     *     blocks: bool, warns: bool, can_override: bool, request_id }
     *
     * Fail-closed: when Fahras is unreachable and `failurePolicy === 'closed'`
     * the response carries `verdict='error'` and `blocks=true`.
     */
    public function actionFahrasCheck()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $svc = Yii::$app->fahras ?? null;
        if (!$svc || !$svc->enabled) {
            return [
                'ok'       => true,
                'enabled'  => false,
                'verdict'  => FahrasVerdict::VERDICT_NO_RECORD,
                'reason_ar'=> '',
                'blocks'   => false,
                'warns'    => false,
            ];
        }

        $req      = Yii::$app->request;
        $idNumber = trim((string)$req->post('id_number', ''));
        $name     = trim((string)$req->post('name', ''));
        $phone    = trim((string)$req->post('phone', ''));

        if ($idNumber === '' && $name === '') {
            return [
                'ok'    => false,
                'error' => 'يرجى إدخال الرقم الوطني (أو الاسم) قبل الفحص.',
            ];
        }

        $verdict = $svc->check($idNumber, $name ?: null, $phone ?: null);

        FahrasCheckLog::record($verdict, [
            'id_number' => $idNumber,
            'name'      => $name,
            'phone'     => $phone,
            'source'    => FahrasCheckLog::SOURCE_STEP1,
        ]);

        $response = [
            'ok'           => true,
            'enabled'      => true,
            'verdict'      => $verdict->verdict,
            'reason_code'  => $verdict->reasonCode,
            'reason_ar'    => $verdict->reasonAr,
            'matches'      => $verdict->matches,
            'remote_errors'=> $verdict->remoteErrors,
            'request_id'   => $verdict->requestId,
            'blocks'       => $verdict->blocks($svc->failurePolicy),
            'warns'        => $verdict->warns(),
            'from_cache'   => $verdict->fromCache,
            'can_override' => Yii::$app->user->can($svc->overridePerm),
            'failure_policy' => $svc->failurePolicy,
            // Per-source diagnostic envelope from Fahras (commit 80acada+).
            // Null when the upstream API hasn't been redeployed yet, in
            // which case the wizard simply hides the «تفاصيل تشخيصية»
            // disclosure and degrades gracefully.
            'diag'         => $verdict->diag,
        ];

        // ── Existing-customer short-circuit ───────────────────────────────
        // The Fahras verdict layer can occasionally flap (Fahras dedup races,
        // upstream sync lag) — but our LOCAL Customers table is the source
        // of truth for "this national_id is already ours". Whenever the rep
        // tries to register a national_id that we already have a record for
        // — REGARDLESS of what Fahras returned — we replace the wizard's
        // hard "blocked" message with a productive choice between two
        // legitimate next actions:
        //   1. Add a new contract on the existing customer (CONT_CREATE).
        //   2. Update the existing customer's data (CUST_UPDATE).
        // The wizard Next button stays locked because creating a *second*
        // customer row for the same national_id would corrupt referential
        // integrity downstream.
        $extras = $this->buildExistingCustomerExtras($verdict, $idNumber);
        if ($extras !== null) {
            $response = array_merge($response, $extras);
            // Always block the Next button when a local customer exists —
            // even if Fahras returned no_record / can_sell — so the rep is
            // forced to pick one of the CTAs instead of pushing through.
            $response['blocks'] = true;
        }

        return $response;
    }

    /**
     * Look up the rep-supplied national_id in our LOCAL Customers table and,
     * when it already exists, return the metadata the front-end needs to
     * render the productive «هذا العميل موجود لديك مسبقاً» CTA strip — two
     * action links (Add Contract / Update Customer) — in place of the usual
     * "blocked" or "no restrictions" Fahras message.
     *
     * Returns null when no local customer matches the national_id, so the
     * caller can `array_merge` the result safely.
     *
     * Why local DB is the source of truth (not the Fahras verdict):
     *   • Fahras verdicts can flip between calls (upstream dedup races,
     *     partial-name vs full-id matching strategies, sync lag with sister
     *     companies). Production observed: same customer → first call
     *     `cannot_sell`, second call `no_record`, both within ~30 seconds.
     *   • A customer row already in our DB is an unambiguous signal that
     *     creating a *second* row with the same national_id would corrupt
     *     downstream foreign keys (contracts, payments, vouchers, …).
     *   • So we ALWAYS surface this CTA whenever the local row exists, even
     *     if Fahras (incorrectly) reports `no_record`. The rep is given the
     *     two legitimate paths forward and the wizard's Next button is
     *     forcibly disabled by the caller (`$response['blocks'] = true`).
     *
     * Fields produced when applicable:
     *   • same_company_only            — bool, always true when present
     *                                    (kept for CSS state hook backwards
     *                                    compatibility — controls the green
     *                                    «same-company» card styling).
     *   • own_company_name             — string|null, canonical company label
     *                                    (null when Fahras integration has no
     *                                    companyName configured).
     *   • existing_customer_id         — int, local Customers.id
     *   • existing_customer_name       — string, local customer name
     *   • add_contract_url             — string, /contracts/contracts/create?id=X
     *   • add_contract_allowed         — bool, user has CONT_CREATE
     *   • update_customer_url          — string, /customers/wizard/edit?id=X
     *   • update_customer_allowed      — bool, user has CUST_UPDATE
     *   • same_company_message_ar      — string, headline tailored to the
     *                                    Fahras verdict + permission combo.
     */
    protected function buildExistingCustomerExtras(FahrasVerdict $verdict, string $idNumber): ?array
    {
        if ($idNumber === '') return null;

        $localCustomer = null;
        try {
            $localCustomer = Customers::find()
                ->where(['id_number' => $idNumber])
                ->limit(1)
                ->one();
        } catch (\Throwable $e) {
            Yii::warning(
                'buildExistingCustomerExtras: local customer lookup failed: ' . $e->getMessage(),
                __METHOD__
            );
            return null;
        }
        if ($localCustomer === null) return null;

        $svc = Yii::$app->fahras ?? null;
        $own = $svc->companyName ?? null;

        $canAddContract    = Permissions::can(Permissions::CONT_CREATE);
        $canUpdateCustomer = Permissions::can(Permissions::CUST_UPDATE);

        $addContractUrl = Url::to([
            '/contracts/contracts/create',
            'id' => (int)$localCustomer->id,
        ]);
        $updateCustomerUrl = Url::to([
            '/customers/wizard/edit',
            'id' => (int)$localCustomer->id,
        ]);

        // Compose a headline that explains WHY the create flow is blocked,
        // tailored to the actual Fahras verdict + permission combo. The
        // dominant fact is always "customer exists locally"; the Fahras
        // bit is supporting context.
        $sameCompanyOnly = $svc !== null && $svc->isSameCompanyOnly($verdict);
        $ownLabel        = $own !== null && $own !== '' ? $own : 'شركتنا';

        if ($verdict->verdict === FahrasVerdict::VERDICT_CANNOT_SELL && $sameCompanyOnly) {
            $msg = sprintf(
                'هذا العميل قائم لدى %s ولا يوجد لديه أي سجل لدى شركات تقسيط أخرى — '
                . 'يحقّ لك إنشاء عقد جديد على ملفه الحالي أو تحديث بياناته بدلاً من إضافته كعميل جديد.',
                $ownLabel
            );
        } elseif ($verdict->verdict === FahrasVerdict::VERDICT_CANNOT_SELL) {
            $msg = sprintf(
                'هذا العميل موجود لديك مسبقاً (%s)، والفهرس يمنع البيع بسبب وجود قيود لدى شركات أخرى — '
                . 'لا يجوز إنشاؤه كعميل جديد. الإجراء الصحيح: إنشاء عقد جديد على ملفه الحالي '
                . '(إذا كانت سياسة شركتنا تسمح) أو تحديث بياناته.',
                $localCustomer->name
            );
        } elseif ($verdict->verdict === FahrasVerdict::VERDICT_CONTACT_FIRST) {
            $msg = sprintf(
                'هذا العميل موجود لديك مسبقاً (%s)، والفهرس ينصح بمراجعة شركة سابقة قبل المتابعة — '
                . 'تواصل مع تلك الشركة، ثم اختر إضافة عقد جديد على ملف العميل أو تحديث بياناته.',
                $localCustomer->name
            );
        } else {
            // no_record / can_sell / error — Fahras would normally allow,
            // but we still must NOT create a duplicate row. Inform the rep
            // of the existing record and offer the two right paths.
            $msg = sprintf(
                'هذا العميل (%s) موجود لديك مسبقاً برقم وطني %s — لا يمكن إعادة إنشائه. '
                . 'استخدم «إضافة عقد جديد» إذا كنت تريد بيع جديد، أو «تحديث بيانات العميل» '
                . 'إذا كنت تريد تعديل بياناته.',
                $localCustomer->name,
                $idNumber
            );
        }

        return [
            'same_company_only'        => true,
            'own_company_name'         => $own,
            'existing_customer_id'     => (int)$localCustomer->id,
            'existing_customer_name'   => (string)$localCustomer->name,
            'add_contract_url'         => $addContractUrl,
            'add_contract_allowed'     => $canAddContract,
            'update_customer_url'      => $updateCustomerUrl,
            'update_customer_allowed'  => $canUpdateCustomer,
            'same_company_message_ar'  => $msg,
        ];
    }

    /**
     * Candidate search by name — returns the raw rows Fahras knows about
     * across local + 6 remote sources. Used by the "بحث في الفهرس" modal.
     */
    public function actionFahrasSearch()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $svc = Yii::$app->fahras ?? null;
        if (!$svc || !$svc->enabled) {
            return ['ok' => true, 'enabled' => false, 'results' => []];
        }

        $q     = trim((string)Yii::$app->request->post('q', ''));
        $limit = (int)Yii::$app->request->post('limit', 20);

        if (mb_strlen($q, 'UTF-8') < 3) {
            return ['ok' => false, 'error' => 'يرجى كتابة 3 أحرف على الأقل للبحث.'];
        }

        $r = $svc->searchByName($q, $limit);
        return [
            'ok'            => $r['ok'] ?? false,
            'enabled'       => true,
            'results'       => $r['results'] ?? [],
            'remote_errors' => $r['remote_errors'] ?? [],
            'request_id'    => $r['request_id'] ?? null,
            'error'         => $r['error'] ?? null,
        ];
    }

    /**
     * Manager override — record a privileged bypass of a `cannot_sell`
     * verdict. The wizard front-end calls this AFTER a manager confirms
     * the override modal; on success the user is allowed to proceed past
     * Step 1 (the override is replayed on actionFinish() by checking
     * `payload['_fahras_override']`).
     *
     * Response: { ok: true, override_id: 123 } | { ok: false, error: '...' }
     */
    public function actionFahrasOverride()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $svc = Yii::$app->fahras ?? null;
        if (!$svc || !$svc->enabled) {
            return ['ok' => false, 'error' => 'تكامل الفهرس مُعطّل.'];
        }
        if (!Yii::$app->user->can($svc->overridePerm)) {
            return ['ok' => false, 'error' => 'لا تملك صلاحية تجاوز حظر الفهرس.'];
        }

        $req       = Yii::$app->request;
        $idNumber  = trim((string)$req->post('id_number', ''));
        $name      = trim((string)$req->post('name', ''));
        $phone     = trim((string)$req->post('phone', ''));
        $reason    = trim((string)$req->post('reason', ''));
        $requestId = trim((string)$req->post('request_id', ''));

        if ($idNumber === '') {
            return ['ok' => false, 'error' => 'الرقم الوطني مطلوب.'];
        }
        if (mb_strlen($reason, 'UTF-8') < 10) {
            return ['ok' => false, 'error' => 'يرجى كتابة سبب التجاوز (10 أحرف على الأقل).'];
        }
        if (mb_strlen($reason, 'UTF-8') > 1000) {
            return ['ok' => false, 'error' => 'سبب التجاوز طويل جداً.'];
        }

        // Re-fetch the verdict so we record the actual matches at override time.
        $verdict = $svc->check($idNumber, $name ?: null, $phone ?: null);
        if ($verdict->verdict !== FahrasVerdict::VERDICT_CANNOT_SELL
            && $verdict->verdict !== FahrasVerdict::VERDICT_ERROR
        ) {
            // Verdict changed in the meantime — no override needed.
            return [
                'ok'       => true,
                'no_override_needed' => true,
                'verdict'  => $verdict->verdict,
            ];
        }

        $log = FahrasCheckLog::record($verdict, [
            'id_number'        => $idNumber,
            'name'             => $name,
            'phone'            => $phone,
            'source'           => FahrasCheckLog::SOURCE_MANUAL,
            'override_user_id' => Yii::$app->user->id,
            'override_reason'  => $reason,
        ]);

        // Record the override into the wizard draft so actionFinish() honours it.
        try {
            $key = $this->draftKey();
            $draft = WizardDraft::loadAutoDraft(Yii::$app->user->id, $key);
            if ($draft) {
                $payload = $this->decodePayload($draft);
                $payload['_fahras_override'] = [
                    'id_number'  => $idNumber,
                    'name'       => $name,
                    'phone'      => $phone,
                    'reason'     => $reason,
                    'request_id' => $verdict->requestId,
                    'log_id'     => $log ? (int)$log->id : null,
                    'verdict'    => $verdict->verdict,
                    'at'         => time(),
                    'by'         => (int)(Yii::$app->user->id ?? 0),
                ];
                WizardDraft::saveAutoDraft(
                    Yii::$app->user->id,
                    $key,
                    json_encode($payload, JSON_UNESCAPED_UNICODE)
                );
            }
        } catch (\Throwable $e) {
            Yii::warning('FahrasOverride: failed to persist override into draft: ' . $e->getMessage(), 'fahras');
        }

        // Notify managers (best-effort; do not fail override on notification error).
        try {
            $notifier = Yii::$app->has('notificationService')
                ? Yii::$app->notificationService
                : (Yii::$app->has('notifications') ? Yii::$app->notifications : null);
            if ($notifier) {
                $username = (Yii::$app->user->identity->username ?? 'مستخدم');
                $notifier->sendToRole(
                    ['مدير', 'manager', 'admin'],
                    Notification::TYPE_FAHRAS_OVERRIDE,
                    'تم تجاوز حظر الفهرس — العميل: ' . ($name ?: $idNumber),
                    '/customers/fahras-log',
                    sprintf(
                        'قام «%s» بتجاوز حظر الفهرس للعميل %s (%s). السبب: %s',
                        $username, $name ?: '—', $idNumber, $reason
                    ),
                    null,
                    'fahras_override',
                    $log ? (int)$log->id : null
                );
            }
        } catch (\Throwable $e) {
            Yii::warning('FahrasOverride: notify failed: ' . $e->getMessage(), 'fahras');
        }

        return [
            'ok'          => true,
            'override_id' => $log ? (int)$log->id : null,
            'request_id'  => $verdict->requestId,
        ];
    }

    /* ════════════════════════════════════════════════════════════════
       END FAHRAS INTEGRATION
       ════════════════════════════════════════════════════════════════ */

    /**
     * Discard the auto-draft entirely (user clicked "ابدأ من جديد").
     */
    public function actionDiscard()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        WizardDraft::clearAutoDraft(Yii::$app->user->id, $this->draftKey());
        return ['ok' => true];
    }

    /**
     * List all manually-saved drafts for the current user. Manual drafts
     * are scoped to the create flow only — edit drafts auto-rehydrate from
     * DB on every entry, so saving them as named slots would be misleading.
     */
    public function actionDrafts()
    {
        $items = WizardDraft::listSavedDrafts(Yii::$app->user->id, self::DRAFT_KEY);
        return $this->renderPartial('_drafts_list', ['items' => $items]);
    }

    /**
     * Resume a manually-saved draft (copies it into the auto slot and
     * redirects to the wizard shell). Always operates on the create slot.
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

            // ── Detect document family BEFORE side routing. ──
            // Gemini's `document_type` enum (see VisionService prompt):
            //   '0' = هوية وطنية / بطاقة شخصية
            //   '1' = جواز سفر
            //   '2' = رخصة قيادة
            //   '3' = شهادة ميلاد
            //   '4' = شهادة تعيين / بطاقة عسكرية أو أمنية
            // ID cards (0/4) keep the strict front+back capture flow because
            // each side carries non-overlapping data. Single-face documents
            // (passports/licenses) don't — the bio page IS the document, so
            // the wizard must NOT pester the rep for a "back" capture and
            // must NOT reject the upload as a wrong-side photo.
            $docType   = isset($extracted['document_type']) ? (string)$extracted['document_type'] : '';
            // Sanad (سند) is Jordan's official mobile-ID app. Its screenshots
            // present a complete document on a single mobile screen — no
            // physical "back" exists to capture. Gemini flags these via
            // `is_sanad: true` regardless of the underlying document_type
            // (0 = هوية سند, 1 = جواز سفر سند, 2 = رخصة قيادة سند).
            $isSanad = !empty($extracted['is_sanad']);
            $isSingleFaceDoc = $isSanad || in_array($docType, [
                VisionService::DOC_TYPE_PASSPORT,   // '1'
                VisionService::DOC_TYPE_LICENSE,    // '2'
            ], true);

            // ── Detect which side this actually is (front vs back). ──
            // Strong signals:
            //   • name + id_number                  → front (civilian or military)
            //   • document_number without name      → back
            //   • military_number without name      → back of military card
            $hasName     = !empty($extracted['name']);
            $hasIdNumber = !empty($extracted['id_number']);
            $hasDocNum   = !empty($extracted['document_number']);
            $hasMilNum   = !empty($extracted['military_number']);

            if ($isSingleFaceDoc) {
                // A passport bio-page or license is its own self-contained
                // "front" — no back exists to capture. Treat it as such so
                // downstream side checks short-circuit cleanly.
                $detectedSide = 'single';
            } elseif ($hasName && ($hasIdNumber || $hasMilNum)) {
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

            // ── SINGLE-FACE DOCS (passport/license): short-circuit the
            // front/back state machine entirely. We persist the image with
            // a doc-typed groupName, map any reusable personal fields
            // (name, sex, birth_date, citizenship), and tell the client
            // we're done — no second-side capture needed.
            if ($isSingleFaceDoc) {
                if (empty($extracted)) {
                    if ($isSanad) {
                        $errMsg = 'لم نستطع قراءة لقطة سند — تأكد من وضوح الشاشة بكامل بياناتها (الاسم، الرقم الوطني، تاريخ الميلاد) وبدون قص أو انعكاس.';
                    } elseif ($docType === VisionService::DOC_TYPE_PASSPORT) {
                        $errMsg = 'لم نستطع قراءة جواز السفر — تأكد من تصوير الصفحة الرئيسية (التي تحوي الصورة والـ MRZ) بإضاءة جيدة وبدون انعكاسات.';
                    } else {
                        $errMsg = 'لم نستطع قراءة رخصة القيادة — تأكد من تصوير الوجه الذي يحمل الاسم ورقم الرخصة بإضاءة جيدة.';
                    }
                    return [
                        'ok'    => false,
                        'error' => $errMsg,
                    ];
                }

                $lookups  = $this->loadLookups();
                $mapped   = $this->mapScanToWizardFields($extracted, $lookups);

                $imageRef = $this->persistScanImage(
                    $tmpPath,
                    $file,
                    'single',
                    null, // issuing_body N/A for passport/license
                    $extracted['document_number'] ?? ($extracted['passport_number'] ?? null),
                    $docType
                );
                if ($imageRef) {
                    $promoted = true;
                }

                $this->rememberScanInDraft('single', $imageRef['image_id'] ?? null, null, $extracted);

                if ($isSanad) {
                    $docLabel = ($docType === VisionService::DOC_TYPE_PASSPORT)
                        ? 'جواز سفر سند'
                        : (($docType === VisionService::DOC_TYPE_LICENSE)
                            ? 'رخصة قيادة سند'
                            : 'هوية سند');
                } else {
                    $docLabel = ($docType === VisionService::DOC_TYPE_PASSPORT)
                        ? 'جواز السفر'
                        : 'رخصة القيادة';
                }

                return [
                    'ok'             => true,
                    'side'           => 'single',
                    'side_detected'  => 'single',
                    'document_type'  => $docType,
                    'is_sanad'       => $isSanad,
                    'next_action'    => 'done',
                    'fields'         => $mapped['fields'],
                    'unmapped'       => $mapped['unmapped'],
                    'raw'            => $extracted,
                    'image_id'       => $imageRef['image_id'] ?? null,
                    'image_url'      => $imageRef['url']      ?? null,
                    'note'           => sprintf('تم التعرف على %s وحفظ صورته في ملف العميل.', $docLabel),
                    'meta'           => [
                        'source'     => 'gemini-vision',
                        'elapsed_ms' => (int)((microtime(true) - $startedAt) * 1000),
                    ],
                ];
            }

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

        // ── Smart salary pick: most recent wage across BOTH SS tables. ──
        //
        // The kashf carries two salary-bearing tables:
        //   • فترات الاشتراك (subscription_periods): each row = a contract
        //     span with from / to / salary. The salary represents what the
        //     customer earned during that span; for an ACTIVE period (to=null)
        //     this IS the current salary, evidenced as-of the period's `from`
        //     date.
        //   • الرواتب المالية (salary_history): per-year aggregate, evidenced
        //     as-of Dec 31 of that year.
        //
        // The naive previous pick of `latest_monthly_salary` (= max year in
        // salary_history) misses the very common case where a raise / new
        // employer started AFTER the most recent salary_history tally — e.g.
        // the kashf shows a 2025 row at 425 JOD but a subscription period
        // starting 2026-02-01 at 600 JOD. The right answer is 600. We
        // compare every salary-bearing row by its evidence date and pick
        // the freshest, with two tie-breakers: an active period beats a
        // closed one, and on a same-date tie the higher salary wins (a
        // tiny, clearly-residual 0.500 row should not overshadow a real
        // wage stamped on the same day).
        $smartEvidence = $this->pickLatestSalaryEvidence(
            (array)($extracted['subscription_periods'] ?? []),
            (array)($extracted['salary_history']       ?? []),
            isset($extracted['latest_monthly_salary']) ? (float)$extracted['latest_monthly_salary'] : 0.0,
            isset($extracted['latest_salary_year'])    ? (int)$extracted['latest_salary_year']      : 0
        );
        if ($smartEvidence && $smartEvidence['salary'] > 0) {
            $fields['Customers[total_salary]'] = (string)round((float)$smartEvidence['salary'], 2);
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

        // ── Stamp last income / job query dates from the kashf itself. ──
        //
        // The "تاريخ الكشف" stamped on the SS statement is what the user
        // actually queried — using today's date instead would later make
        // the customer record look like we double-checked the income on
        // the day of customer creation, which is misleading. The same
        // date applies to last_job_query_date because the SS kashf is
        // the official confirmation of both the salary AND the employer.
        // We fall back to today only if the kashf had no parseable date.
        $stmtDate = (string)($extracted['statement_date'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $stmtDate)) {
            $stmtDate = date('Y-m-d');
        }
        $fields['Customers[last_income_query_date]'] = $stmtDate;
        $fields['Customers[last_job_query_date]']    = $stmtDate;

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
            // Gemini schema and Customers.sex column both use 0 = ذكر, 1 = أنثى.
            $fields['Customers[sex]'] = ((int)$extracted['sex'] === 1) ? '1' : '0';
        }

        return ['fields' => $fields, 'unmapped' => $unmapped];
    }

    /**
     * Most-recent salary in JOD evidenced anywhere on the SS kashf, or null
     * if none. Thin wrapper around pickLatestSalaryEvidence() that returns
     * just the dollar amount — kept as a separate method so the auto-fill
     * call site stays readable.
     */
    protected function pickLatestSalaryByDate(array $periods, array $history,
                                              float $latestMonthly = 0.0,
                                              int   $latestYear    = 0)
    {
        $best = $this->pickLatestSalaryEvidence($periods, $history, $latestMonthly, $latestYear);
        return $best ? (float)$best['salary'] : ($latestMonthly > 0 ? $latestMonthly : null);
    }

    /**
     * Full evidence record for the most-recent salary on the kashf:
     *   ['salary' => float, 'date' => 'YYYY-MM-DD', 'active' => bool,
     *    'source' => 'period'|'history'|'fallback']
     * or null when nothing is parseable.
     *
     * Why a separate method from pickLatestSalaryByDate():
     *   The summary UI needs the as-of date so we can show
     *   "آخر راتب شهري: 600 د.أ (2026-02-01)" instead of the misleading
     *   "(2025)" that comes from latest_salary_year — i.e. so the user
     *   sees WHY the auto-filled wage is what it is.
     *
     * See the long comment in mapIncomeScanToWizardFields() for the full
     * tie-breaker rationale.
     *
     * @param array $periods       subscription_periods rows (from, to, salary, …)
     * @param array $history       salary_history rows (year, salary, …)
     * @param float $latestMonthly server-derived fallback (latest_monthly_salary)
     * @param int   $latestYear    server-derived fallback (latest_salary_year)
     * @return array{salary:float,date:string,active:bool,source:string}|null
     */
    protected function pickLatestSalaryEvidence(array $periods, array $history,
                                                float $latestMonthly = 0.0,
                                                int   $latestYear    = 0)
    {
        $candidates = [];

        // Subscription periods → as-of = `from` (start of contract span).
        foreach ($periods as $p) {
            if (!is_array($p)) continue;
            $from = (string)($p['from'] ?? '');
            $sal  = isset($p['salary']) ? (float)$p['salary'] : 0.0;
            if ($sal <= 0) continue;
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) continue;
            $candidates[] = [
                'date'   => $from,
                'salary' => $sal,
                'active' => empty($p['to']),
                'source' => 'period',
            ];
        }

        // Salary-history rows → as-of = Dec 31 of the year.
        foreach ($history as $r) {
            if (!is_array($r)) continue;
            $year = isset($r['year']) ? (int)$r['year'] : 0;
            $sal  = isset($r['salary']) ? (float)$r['salary'] : 0.0;
            if ($sal <= 0 || $year < 1900) continue;
            $candidates[] = [
                'date'   => sprintf('%04d-12-31', $year),
                'salary' => $sal,
                'active' => false,
                'source' => 'history',
            ];
        }

        // Server-derived fallback so we never regress below the old behavior.
        if ($latestMonthly > 0 && $latestYear >= 1900) {
            $candidates[] = [
                'date'   => sprintf('%04d-12-31', $latestYear),
                'salary' => $latestMonthly,
                'active' => false,
                'source' => 'fallback',
            ];
        }

        if (!$candidates) return null;

        // Most recent date first; active wins ties; higher salary wins same-date ties.
        usort($candidates, function ($a, $b) {
            $cmp = strcmp($b['date'], $a['date']);
            if ($cmp !== 0) return $cmp;
            if ($a['active'] !== $b['active']) return $a['active'] ? -1 : 1;
            return $b['salary'] <=> $a['salary'];
        });

        return $candidates[0];
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

        // Smart salary pick (same logic that fills total_salary so the
        // displayed "آخر راتب شهري" never disagrees with the input value).
        $evidence = $this->pickLatestSalaryEvidence(
            (array)($extracted['subscription_periods'] ?? []),
            (array)($extracted['salary_history']       ?? []),
            isset($extracted['latest_monthly_salary']) ? (float)$extracted['latest_monthly_salary'] : 0.0,
            isset($extracted['latest_salary_year'])    ? (int)$extracted['latest_salary_year']      : 0
        );

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
            // Authoritative pick — what actually populates total_salary.
            // The JS summary renderer should display THIS, not the raw
            // latest_monthly_salary, so the user sees a consistent number.
            'selected_salary'            => $evidence ? (float)$evidence['salary']  : null,
            'selected_salary_date'       => $evidence ? (string)$evidence['date']   : null,
            'selected_salary_active'     => $evidence ? (bool)$evidence['active']   : false,
            'selected_salary_source'     => $evidence ? (string)$evidence['source'] : null,
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
     * Persist the Social Security statement (header + child tables) once the
     * wizard finishes. Reads the cached extraction stashed in the draft by
     * `rememberIncomeScanInDraft()` and delegates the heavy lifting to
     * `CustomerSsStatement::saveExtracted()`.
     *
     * Best-effort — caller wraps this in a try/catch so a failure here will
     * not abort customer creation.
     */
    protected function persistSocialSecurityStatement(int $customerId, array $payload): void
    {
        $extracted = $payload['_scan']['income_extracted'] ?? null;
        if (!is_array($extracted) || empty($extracted)) {
            return;
        }

        $imageId = isset($payload['_scan']['images']['income'])
            ? (int)$payload['_scan']['images']['income']
            : null;

        \backend\modules\customers\models\CustomerSsStatement::saveExtracted(
            $customerId,
            $imageId ?: null,
            $extracted
        );
    }

    /**
     * Track the SS scan in the wizard's auto-draft so:
     *   • finish() adopts the Media row for the new customer.
     *   • the review screen can show the kashf as a known document.
     *   • re-uploading replaces the previous summary cleanly.
     *   • finish() can persist the structured rows to the dedicated tables.
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
            $payload['_scan']['income_summary']   = $this->buildIncomeSummary($extracted);
            // Stash the full normalised extraction so actionFinish() can
            // persist the structured rows (subscriptions + salaries) without
            // re-running OCR. JSON_UNESCAPED_UNICODE keeps Arabic readable in
            // the draft store and small enough to fit MAX_DRAFT_BYTES easily.
            $payload['_scan']['income_extracted'] = $extracted;
            $payload['_scan']['updated']          = time();

            $payload['_updated'] = time();
            $finalJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
            if ($finalJson !== false && strlen($finalJson) <= self::MAX_DRAFT_BYTES) {
                WizardDraft::saveAutoDraft(Yii::$app->user->id, $this->draftKey(), $finalJson);
            }
        } catch (\Throwable $e) {
            Yii::warning('Wizard income scan: failed to remember in draft: ' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Map (issuing_body, side, doc_type) → groupName values used by the rest
     * of the platform (`os_ImageManager.groupName`). Keeps a single,
     * documented mapping in one place so reports/filters stay consistent.
     *
     * Convention used elsewhere in the codebase (SmartMediaController):
     *   '0' = هوية وطنية (national ID)
     *   '1' = جواز سفر    (passport)
     *   '2' = رخصة قيادة  (driver's license)
     *   '4' = شهادة تعيين عسكرية (military appointment certificate)
     *
     * For ID cards we extend with side-specific subcodes so the customer's
     * documents tab can show "ID — Front" vs "ID — Back" cleanly:
     *   '0_front', '0_back'   civilian ID
     *   '4_front', '4_back'   military / security / intelligence
     *
     * Single-face documents (passport/license) don't have a back, so they
     * use the bare doc-type code without a side suffix:
     *   '1'   passport
     *   '2'   license
     *
     * @param string|null $docType  Optional doc-type override (Gemini's
     *                              `document_type`). When provided AND it
     *                              maps to a single-face family, takes
     *                              precedence over (side, issuingBody).
     */
    protected function groupNameForScan($side, $issuingBody, $docType = null)
    {
        if ($docType === VisionService::DOC_TYPE_PASSPORT) return VisionService::DOC_TYPE_PASSPORT; // '1'
        if ($docType === VisionService::DOC_TYPE_LICENSE)  return VisionService::DOC_TYPE_LICENSE;  // '2'

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
    protected function persistScanImage($tmpPath, $uploadedFile, $side, $issuingBody, $documentNumber, $docType = null)
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

            $groupName = $this->groupNameForScan($side, $issuingBody, $docType);

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

            // Per-side metadata so finishCustomer() can create the correct
            // customers_document row PER physical document — needed once we
            // accept passports/licenses alongside ID cards in the same draft.
            // (The legacy single document_number/document_type fields above
            // still reflect the LAST scan to keep older code paths intact.)
            if ($imageId) {
                if (!isset($payload['_scan']['perSide']) || !is_array($payload['_scan']['perSide'])) {
                    $payload['_scan']['perSide'] = [];
                }
                $payload['_scan']['perSide'][$side] = [
                    'document_type'   => isset($extracted['document_type']) ? (string)$extracted['document_type'] : '0',
                    'document_number' => isset($extracted['document_number']) && $extracted['document_number'] !== ''
                        ? (string)$extracted['document_number']
                        : (isset($extracted['passport_number']) ? (string)$extracted['passport_number'] : ''),
                ];
            }

            $payload['_scan']['updated'] = time();

            $payload['_updated'] = time();
            $finalJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
            if ($finalJson !== false && strlen($finalJson) <= self::MAX_DRAFT_BYTES) {
                WizardDraft::saveAutoDraft(Yii::$app->user->id, $this->draftKey(), $finalJson);
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
            $draft = WizardDraft::loadAutoDraft(Yii::$app->user->id, $this->draftKey());
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

        // Legacy fallback for drafts created before per-side metadata existed.
        $legacyDocNumber = $payload['_scan']['document_number'] ?? null;
        $legacyDocType   = $payload['_scan']['document_type']   ?? '0';
        $perSide         = is_array($payload['_scan']['perSide'] ?? null)
            ? $payload['_scan']['perSide']
            : [];

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

                // Best-effort customers_document entry — one row per physical
                // document. Skip the back-of-ID (it's the same physical doc
                // as front; document_number is shared) and the income side
                // (handled by the income-statement scanner separately).
                $skipDocRow = in_array($side, ['back', 'income'], true);

                if (!$skipDocRow) {
                    // Resolve metadata: prefer per-side (accurate when the
                    // draft mixes ID + passport + license), fall back to the
                    // legacy single-doc fields for older drafts.
                    $sideMeta = is_array($perSide[$side] ?? null) ? $perSide[$side] : [];
                    $docType  = (string)($sideMeta['document_type']   ?? $legacyDocType);
                    $docNum   = (string)($sideMeta['document_number'] ?? $legacyDocNumber);

                    if ($docNum !== '') {
                        $db->createCommand()->insert('{{%customers_document}}', [
                            'customer_id'     => $customerId,
                            'document_type'   => $docType,
                            'document_number' => $docNum,
                            'document_image'  => (string)$imageId,
                            'created_at'      => time(),
                            'updated_at'      => time(),
                            'created_by'      => Yii::$app->user->id ?? null,
                        ])->execute();
                    }
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
     *   2. Normalize `sex` to the Customers.sex enum (0=ذكر, 1=أنثى) which
     *      matches what Gemini emits AND the legacy app's column convention.
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
        // Customers.sex stores '0' (male) / '1' (female) — same convention as
        // the legacy _form.php / _smart_form.php / view.php across the app.
        if (isset($extracted['sex']) && $extracted['sex'] !== '' && $extracted['sex'] !== null) {
            $sexResolved = self::normalizeSexValue($extracted['sex']);
            if ($sexResolved !== null) {
                $fields['Customers[sex]'] = $sexResolved;
            }
        }
        // Fallback: read sex from MRZ if we have it raw and Gemini missed it.
        if (!isset($fields['Customers[sex]']) && !empty($extracted['mrz']) && is_string($extracted['mrz'])) {
            if (preg_match('/\b\d{6}([MF])\d{6}\b/', strtoupper($extracted['mrz']), $mm)) {
                $fields['Customers[sex]'] = ($mm[1] === 'F') ? '1' : '0';
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
     * Normalize a raw sex value (anything Gemini may emit) into the
     * Customers.sex enum used app-wide: '0' (male) / '1' (female).
     * Returns null when nothing matches.
     *
     * Tolerates the historical wizard convention ('1'/'2') so any payload
     * that pre-dates the 2026-04 alignment still resolves correctly.
     */
    protected static function normalizeSexValue($raw)
    {
        if (is_int($raw)) {
            // Gemini emits 0=male / 1=female — straight passthrough now
            // that we've aligned with the legacy column convention.
            return ($raw === 1) ? '1' : '0';
        }
        $s = trim((string)$raw);
        if ($s === '') return null;

        $u = strtoupper($s);
        if ($u === '0') return '0';
        if ($u === '1') return '1';
        if ($u === '2') return '1'; // legacy wizard '2' = أنثى → map to '1'.
        if ($u === 'M' || $u === 'MALE')   return '0';
        if ($u === 'F' || $u === 'FEMALE') return '1';

        // Arabic — strip diacritics and common variants before comparing.
        $n = self::normalizeArabic($s);
        if ($n === 'ذكر' || $n === 'ذكور') return '0';
        if ($n === 'انثي' || $n === 'انثى' || $n === 'اناث') return '1';

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
            // sex enum: '0' = male → "أردني", '1' = female → "أردنية".
            return ($sexNorm === '1') ? 'أردنية' : 'أردني';
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
        $key = $this->draftKey();
        $draft = WizardDraft::loadAutoDraft(Yii::$app->user->id, $key);
        if (!$draft) {
            $seed = [
                '_step'    => 1,
                '_created' => time(),
                '_updated' => time(),
            ];
            // Encode mode/customer_id eagerly so subsequent helpers (Fahras
            // gate, finish-branching) read the right context even before the
            // first save round-trip.
            if ($key !== self::DRAFT_KEY && strpos($key, 'customer_edit:') === 0) {
                $seed['_mode']        = 'edit';
                $seed['_customer_id'] = (int)substr($key, strlen('customer_edit:'));
            } else {
                $seed['_mode'] = 'create';
            }
            WizardDraft::saveAutoDraft(Yii::$app->user->id, $key, $seed);
            $draft = WizardDraft::loadAutoDraft(Yii::$app->user->id, $key);
        }
        return $draft;
    }

    /**
     * Resolve the wizard draft slot for the current request — either the
     * default create slot or a per-customer edit slot.
     *
     * Resolution order (highest → lowest priority):
     *   1. Cached value pinned by an action method (e.g. {@see actionEdit}).
     *   2. POST/GET body parameters `mode` + `customerId` — stamped onto
     *      every wizard AJAX request by the global ajaxPrefilter in
     *      backend/web/js/customer-wizard/core.js (`bindEditContextPrefilter`).
     *   3. Request-cookie `cw_draft_key` — legacy fallback, kept for
     *      back-compat with any bookmarked URLs / older client builds.
     *   4. Default → {@see DRAFT_KEY}.
     *
     * Why not session: a single user can have multiple browser tabs open
     * (one create, one edit). Session storage would let one tab silently
     * overwrite the other's slot. Per-request resolution avoids that race.
     */
    protected function draftKey(): string
    {
        if ($this->_draftKey !== null) {
            return $this->_draftKey;
        }

        $req = Yii::$app->request;

        // ── Per-request mode + customerId hints. ──
        $mode = (string)$req->post('mode', $req->get('mode', ''));
        $cid  = (int)$req->post('customerId', $req->get('customerId', 0));

        // ── Cookie fallback (client sets when actionEdit lands). ──
        if ($mode === '' || $cid <= 0) {
            $cookies = $req->cookies;
            $cookieKey = (string)$cookies->getValue('cw_draft_key', '');
            if ($cookieKey !== ''
                && strpos($cookieKey, 'customer_edit:') === 0) {
                $mode = 'edit';
                $cid  = (int)substr($cookieKey, strlen('customer_edit:'));
            }
        }

        if ($mode === 'edit' && $cid > 0) {
            return $this->_draftKey = 'customer_edit:' . $cid;
        }
        return $this->_draftKey = self::DRAFT_KEY;
    }

    /**
     * Whether the current request is operating on an existing customer
     * (edit-mode draft slot) vs. creating a brand-new one.
     *
     * Used by validators to relax "required" enforcement: when editing
     * an existing customer, the user may want to update a single field
     * without re-justifying every required column (the customer was
     * already created with valid data; format/length/range rules still
     * apply, but blanket required-field gating is dropped).
     */
    protected function isEditMode(): bool
    {
        try {
            return strpos($this->draftKey(), 'customer_edit:') === 0;
        } catch (\Throwable $e) {
            return false;
        }
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

    /* ════════════════════════════════════════════════════════════════
       CUSTOMER EXTRAS — personal photo + ad-hoc supporting documents
       ────────────────────────────────────────────────────────────────
       Two endpoints that mirror the smart-scan persistence pattern but
       are user-driven (no OCR, no field auto-fill). Used by the «الصور
       والمستندات الإضافية» fieldset on Step 1:

         • upload-extra (POST file=<binary>, purpose=photo|doc)
              Saves a Media row tagged with groupName='8' (شخصية) or
              '9' (أخرى), records its id in the auto-draft so finalize()
              can adopt it.
         • delete-extra (POST image_id, purpose)
              Removes the row from the draft + soft-deletes the Media
              file on disk if the row was an orphan still pending
              adoption (i.e. customer_id IS NULL).

       Why both purposes share an endpoint:
         The persistence shape is identical (Media row + draft index
         entry) — the only differences are the groupName and where the
         id ends up in the draft (`_extras.photo_id` vs `_extras.docs[]`).
         A single endpoint keeps the JS contract trivially simple.
       ════════════════════════════════════════════════════════════════ */

    /**
     * POST /customers/wizard/upload-extra
     *
     * Inputs (multipart/form-data):
     *   • file    — the image / PDF the user picked.
     *   • purpose — 'photo' (single, becomes selected_image on finish)
     *               'doc'   (multi, lives under «أخرى» in documents).
     *
     * Response (JSON):
     *   { ok: true, image_id, url, file_name, purpose }
     *   { ok: false, error }
     *
     * Storage convention (matches print_preview + customers/getSelectedImagePath):
     *   • Media.customer_id = NULL until actionFinish() adopts the row.
     *   • Media.groupName   = '8' for photo, '9' for doc.
     *   • Draft tracks the id so the orphan row can be reconciled even
     *     if the user closes the tab and resumes hours later.
     */
    public function actionUploadExtra()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $purpose = strtolower((string)Yii::$app->request->post('purpose', 'doc'));
        if (!in_array($purpose, ['photo', 'doc'], true)) {
            return ['ok' => false, 'error' => 'نوع الملف غير معروف.'];
        }

        $file = UploadedFile::getInstanceByName('file');
        if (!$file) {
            return ['ok' => false, 'error' => 'لم يتم استلام ملف للرفع.'];
        }
        if ($file->error !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'خطأ في رفع الملف (#' . (int)$file->error . ').'];
        }
        // Photos are images only (the customer's face is later embedded
        // in the contract print preview as <img>); documents allow PDFs
        // alongside images so kashfs / utility bills don't need conversion.
        $allowed = $purpose === 'photo'
            ? ['image/jpeg', 'image/png', 'image/webp']
            : self::SCAN_ALLOWED_MIMES;
        if (!in_array($file->type, $allowed, true)) {
            return [
                'ok'    => false,
                'error' => $purpose === 'photo'
                    ? 'الصورة الشخصية يجب أن تكون JPG / PNG / WEBP.'
                    : 'نوع الملف غير مدعوم — استخدم JPG / PNG / WEBP / PDF.',
            ];
        }
        if ($file->size > self::MAX_SCAN_BYTES) {
            return ['ok' => false, 'error' => 'حجم الملف أكبر من 10 ميجابايت.'];
        }

        // Persist via the same Media plumbing the scan flow uses so we
        // benefit from its file naming / thumbnail / chmod logic.
        $groupName = $purpose === 'photo' ? '8' : '9';
        $sideTag   = $purpose === 'photo' ? 'photo' : 'extra';

        // Stage to runtime first so a failed Media row insert can't leave
        // a half-uploaded blob in the canonical store.
        $ext = strtolower($file->extension ?: ($purpose === 'photo' ? 'jpg' : 'bin'));
        $tmpPath = Yii::getAlias('@runtime') . '/wizard_extra_'
                 . Yii::$app->security->generateRandomString(8) . '.' . $ext;
        if (!$file->saveAs($tmpPath, false)) {
            return ['ok' => false, 'error' => 'تعذّر حفظ الملف للمعالجة.'];
        }

        try {
            $imageRef = $this->persistExtraMedia($tmpPath, $file, $groupName);
            if (!$imageRef) {
                return ['ok' => false, 'error' => 'تعذّر حفظ الملف في مخزن الصور.'];
            }
            $this->rememberExtraInDraft($purpose, (int)$imageRef['image_id'], [
                'file_name' => $imageRef['file_name'],
                'url'       => $imageRef['url'],
                'mime'      => $file->type,
                'size'      => (int)$file->size,
                'uploaded'  => time(),
            ]);

            return [
                'ok'        => true,
                'purpose'   => $purpose,
                'image_id'  => (int)$imageRef['image_id'],
                'url'       => $imageRef['url'],
                'file_name' => $imageRef['file_name'],
                'mime'      => $file->type,
            ];
        } catch (\Throwable $e) {
            Yii::error('Wizard upload-extra failed: ' . $e->getMessage(), __METHOD__);
            return ['ok' => false, 'error' => 'حدث خطأ غير متوقع أثناء حفظ الملف.'];
        } finally {
            // persistExtraMedia rename()s the file out of runtime; clean up
            // only if it's still here (i.e. persistence failed).
            if (file_exists($tmpPath)) @unlink($tmpPath);
        }
    }

    /**
     * POST /customers/wizard/delete-extra
     *
     * Inputs (form-encoded): image_id, purpose=photo|doc.
     *
     * Strategy:
     *   • If the Media row is still an orphan (customer_id IS NULL) we
     *     hard-delete it — the draft is the only thing referencing it.
     *   • If the row is already adopted (someone reused image_id from a
     *     prior wizard run) we DON'T delete it from the DB; we just drop
     *     the reference from the current draft. Defensive — protects
     *     accidental cross-customer data loss.
     */
    public function actionDeleteExtra()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $imageId = (int)Yii::$app->request->post('image_id', 0);
        $purpose = strtolower((string)Yii::$app->request->post('purpose', 'doc'));
        if ($imageId <= 0 || !in_array($purpose, ['photo', 'doc'], true)) {
            return ['ok' => false, 'error' => 'بيانات غير صحيحة.'];
        }

        $this->forgetExtraInDraft($purpose, $imageId);

        try {
            $row = Media::findOne($imageId);
            if ($row && (int)$row->customer_id === 0) {
                // Best-effort: delete the disk file before the DB row so
                // a failure mid-way doesn't strand bytes on disk.
                try {
                    $abs = MediaHelper::filePath((int)$row->id, $row->fileHash, $row->fileName);
                    if ($abs && is_file($abs)) @unlink($abs);
                } catch (\Throwable $fe) {
                    Yii::warning('delete-extra: file unlink failed: ' . $fe->getMessage(), __METHOD__);
                }
                $row->delete();
            }
        } catch (\Throwable $e) {
            Yii::warning('delete-extra: media cleanup failed: ' . $e->getMessage(), __METHOD__);
        }

        return ['ok' => true];
    }

    /**
     * Persist a user-uploaded extra (photo / document) into Media.
     * Mirrors persistScanImage() but without the side / issuing-body
     * machinery — the user already told us what kind of file this is.
     *
     * @return array{image_id:int,url:string,file_name:string}|null
     */
    protected function persistExtraMedia($tmpPath, $uploadedFile, $groupName)
    {
        if (!is_file($tmpPath)) return null;

        $fileHash = Yii::$app->security->generateRandomString(32);
        $origName = $uploadedFile->name ?: ('extra_' . time() . '.bin');
        $origName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($origName));
        if ($origName === '' || $origName === '.' || $origName === '..') {
            $origName = 'extra_' . time() . '.' . (strtolower(pathinfo($tmpPath, PATHINFO_EXTENSION)) ?: 'bin');
        }

        $media = new Media([
            'fileName'    => $origName,
            'fileHash'    => $fileHash,
            'customer_id' => null,           // adopted by linkExtrasToCustomer() on finish
            'contractId'  => null,
            'groupName'   => $groupName,
            'created'     => date('Y-m-d H:i:s'),
            'modified'    => date('Y-m-d H:i:s'),
            'createdBy'   => Yii::$app->user->id ?? null,
            'modifiedBy'  => Yii::$app->user->id ?? null,
        ]);
        if (!$media->save(false)) {
            Yii::warning('Wizard upload-extra: failed to persist Media row', __METHOD__);
            return null;
        }

        $destPath = MediaHelper::filePath((int)$media->id, $fileHash, $origName);
        $destDir  = dirname($destPath);
        if (!is_dir($destDir)) @mkdir($destDir, 0755, true);
        if (!@rename($tmpPath, $destPath)) {
            if (!@copy($tmpPath, $destPath)) {
                $media->delete();
                return null;
            }
            @unlink($tmpPath);
        }
        @chmod($destPath, 0644);

        return [
            'image_id'  => (int)$media->id,
            'url'       => $media->getUrl(),
            'file_name' => $origName,
        ];
    }

    /**
     * Track the uploaded extra inside the auto-draft so:
     *   • The wizard's finalize step can adopt it.
     *   • A returning user (e.g. resumed draft after browser close) can
     *     see it pre-rendered without re-uploading.
     *
     * Shape:
     *   _extras: {
     *     photo: { image_id, file_name, url, mime, size, uploaded }   // single
     *     docs:  [ { image_id, file_name, url, mime, size, uploaded }, … ]
     *   }
     */
    protected function rememberExtraInDraft($purpose, $imageId, array $meta)
    {
        try {
            $draft   = $this->getOrCreateAutoDraft();
            $payload = $this->decodePayload($draft);

            if (!isset($payload['_extras']) || !is_array($payload['_extras'])) {
                $payload['_extras'] = [];
            }
            $entry = array_merge(['image_id' => (int)$imageId], $meta);

            if ($purpose === 'photo') {
                // Replace any prior photo (single-slot semantics): the
                // user picked a NEW headshot; the old orphan Media row
                // becomes safely deletable but we don't bother — if it
                // wasn't adopted it'll be reaped by housekeeping later.
                $prior = $payload['_extras']['photo'] ?? null;
                if (is_array($prior) && !empty($prior['image_id']) &&
                    (int)$prior['image_id'] !== (int)$imageId) {
                    $this->safeDeleteOrphanMedia((int)$prior['image_id']);
                }
                $payload['_extras']['photo'] = $entry;
            } else {
                if (!isset($payload['_extras']['docs']) || !is_array($payload['_extras']['docs'])) {
                    $payload['_extras']['docs'] = [];
                }
                // Dedupe by image_id so a double-submit never doubles up.
                $payload['_extras']['docs'] = array_values(array_filter(
                    $payload['_extras']['docs'],
                    function ($d) use ($imageId) {
                        return is_array($d) && (int)($d['image_id'] ?? 0) !== (int)$imageId;
                    }
                ));
                $payload['_extras']['docs'][] = $entry;
            }

            $payload['_updated'] = time();
            $finalJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
            if ($finalJson !== false && strlen($finalJson) <= self::MAX_DRAFT_BYTES) {
                WizardDraft::saveAutoDraft(Yii::$app->user->id, $this->draftKey(), $finalJson);
            }
        } catch (\Throwable $e) {
            Yii::warning('Wizard upload-extra: draft remember failed: ' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Remove an extra from the draft (server-side mirror of the JS delete).
     */
    protected function forgetExtraInDraft($purpose, $imageId)
    {
        try {
            $draft = WizardDraft::loadAutoDraft(Yii::$app->user->id, $this->draftKey());
            if (!$draft) return;
            $payload = $this->decodePayload($draft);
            if (empty($payload['_extras'])) return;

            if ($purpose === 'photo') {
                $current = $payload['_extras']['photo'] ?? null;
                if (is_array($current) && (int)($current['image_id'] ?? 0) === (int)$imageId) {
                    unset($payload['_extras']['photo']);
                }
            } else {
                if (!empty($payload['_extras']['docs']) && is_array($payload['_extras']['docs'])) {
                    $payload['_extras']['docs'] = array_values(array_filter(
                        $payload['_extras']['docs'],
                        function ($d) use ($imageId) {
                            return is_array($d) && (int)($d['image_id'] ?? 0) !== (int)$imageId;
                        }
                    ));
                }
            }

            $payload['_updated'] = time();
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
            if ($json !== false) {
                WizardDraft::saveAutoDraft(Yii::$app->user->id, $this->draftKey(), $json);
            }
        } catch (\Throwable $e) {
            Yii::warning('forget-extra failed: ' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Hard-delete an orphan Media row + its file. Safe-noop when the row
     * is already adopted by a customer (customer_id IS NOT NULL).
     */
    protected function safeDeleteOrphanMedia($imageId)
    {
        try {
            $row = Media::findOne((int)$imageId);
            if (!$row) return;
            if ((int)$row->customer_id !== 0) return;     // adopted → leave alone
            try {
                $abs = MediaHelper::filePath((int)$row->id, $row->fileHash, $row->fileName);
                if ($abs && is_file($abs)) @unlink($abs);
            } catch (\Throwable $fe) {
                Yii::warning('safeDeleteOrphanMedia: unlink failed: ' . $fe->getMessage(), __METHOD__);
            }
            $row->delete();
        } catch (\Throwable $e) {
            Yii::warning('safeDeleteOrphanMedia failed: ' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Adopt orphan extras (personal photo + ad-hoc documents) into the
     * freshly-created customer record. Called from actionFinish() right
     * after linkScanImagesToCustomer().
     *
     * For the photo we ALSO write its id into customers.selected_image
     * so legacy UI that consults `selected_image` directly (rather than
     * Customers::getSelectedImagePath()) shows the right face — important
     * because the contract print-preview's secondary code paths still
     * rely on that column for the buyer headshot.
     *
     * @return array{photo:int,docs:int}  count adopted in each bucket
     */
    public function linkExtrasToCustomer($customerId, array $payload)
    {
        $extras = $payload['_extras'] ?? [];
        $report = ['photo' => 0, 'docs' => 0];
        if (!is_array($extras) || empty($extras)) return $report;

        $db = Yii::$app->db;

        // ── Photo (single). ──
        $photo = $extras['photo'] ?? null;
        if (is_array($photo) && !empty($photo['image_id'])) {
            try {
                $photoId = (int)$photo['image_id'];
                $db->createCommand()->update(
                    Media::tableName(),
                    [
                        'customer_id' => (int)$customerId,
                        'modified'    => date('Y-m-d H:i:s'),
                    ],
                    ['id' => $photoId, 'customer_id' => null]
                )->execute();

                // Keep the legacy customers.selected_image FK in sync so
                // older code paths that don't go through Media still see
                // the headshot. The relation is by media id (string).
                $db->createCommand()->update(
                    Customers::tableName(),
                    ['selected_image' => (string)$photoId],
                    ['id' => (int)$customerId]
                )->execute();
                $report['photo'] = 1;
            } catch (\Throwable $e) {
                Yii::warning('linkExtras: photo adopt failed: ' . $e->getMessage(), __METHOD__);
            }
        }

        // ── Additional documents (many). ──
        $docs = $extras['docs'] ?? [];
        if (is_array($docs)) {
            foreach ($docs as $doc) {
                if (!is_array($doc) || empty($doc['image_id'])) continue;
                try {
                    $db->createCommand()->update(
                        Media::tableName(),
                        [
                            'customer_id' => (int)$customerId,
                            'modified'    => date('Y-m-d H:i:s'),
                        ],
                        ['id' => (int)$doc['image_id'], 'customer_id' => null]
                    )->execute();
                    $report['docs']++;
                } catch (\Throwable $e) {
                    Yii::warning('linkExtras: doc adopt failed: ' . $e->getMessage(), __METHOD__);
                }
            }
        }

        return $report;
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
                // The combobox alert links here so loan officers can fill
                // missing data without losing their wizard progress (opens
                // in a new tab). On wizard tab re-focus we re-poll this
                // endpoint and the warning disappears once the data lands.
                'edit_url'    => Url::to(['/jobs/jobs/update', 'id' => (int)$row['id']]),
            ];
        } catch (\Throwable $e) {
            Yii::error('actionJobMeta failed: ' . $e->getMessage(), __METHOD__);
            return ['ok' => false, 'error' => 'server error'];
        }
    }

    /* ════════════════════════════════════════════════════════════════
       LOCATION (Step 3 — address map)
       ════════════════════════════════════════════════════════════════ */

    /**
     * Smart "paste a location" resolver — accepts Google Maps short URLs,
     * full + short Plus Codes, or free-text addresses, and returns lat/lng.
     *
     * Mirrors the legacy /jobs/resolve-location surface but lives under the
     * wizard so we don't hard-couple the wizard's RBAC to the jobs module.
     *
     * GET params: ?q=<text>
     * Response  : { success, lat?, lng?, display_name?, source? }
     */
    public function actionResolveLocation()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $raw = (string)Yii::$app->request->get('q', '');
        return (new LocationResolverService())->resolveAny($raw);
    }

    /**
     * Server-side proxy for Google Places "searchText", restricted to the
     * Jordan bounding box. Lives on the server (not the browser) because:
     *   • Hides the API key.
     *   • Single SystemSettings source for the key (no per-tier config).
     *   • Lets us swap the key / provider without redeploying the JS.
     *
     * GET params: ?q=<text>&lat=<float>&lng=<float>
     * Response  : { results:[{name,addr,lat,lng,types}], source:string }
     */
    public function actionSearchPlaces()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $q   = (string)Yii::$app->request->get('q', '');
        $lat = (float)Yii::$app->request->get('lat', 31.95);
        $lng = (float)Yii::$app->request->get('lng', 35.91);
        return (new LocationResolverService())->searchGooglePlaces($q, $lat, $lng);
    }

    /**
     * Reverse-geocode lat/lng via Google's Geocoding API. Used as a
     * fallback by the address-map widget when Nominatim's `/reverse`
     * doesn't return a `suburb`/`neighbourhood` for a clicked point —
     * Google's `address_components` almost always carries one.
     *
     * GET params: ?lat=<float>&lng=<float>
     * Response  : { ok, address?: {city, suburb, road, …}, source: 'google'|'none' }
     */
    public function actionReverseGeocode()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $lat = (float)Yii::$app->request->get('lat', 0);
        $lng = (float)Yii::$app->request->get('lng', 0);
        if (!is_finite($lat) || !is_finite($lng) || ($lat == 0 && $lng == 0)) {
            return ['ok' => false, 'address' => null, 'source' => 'none'];
        }
        $addr = (new LocationResolverService())->googleReverseGeocode($lat, $lng);
        return [
            'ok'      => $addr !== null,
            'address' => $addr,
            'source'  => $addr !== null ? 'google' : 'none',
        ];
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
        $isEdit = $this->isEditMode();

        // In edit mode we skip required-field gating entirely — the
        // customer already exists and the user may want to touch just
        // one field. Format/length/range checks below still apply.
        if (!$isEdit) {
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
        }

        // Name must contain at least 2 words (first + last as bare minimum).
        // Edits skip the minimum-words rule so historical 1-word names
        // don't block partial updates; the length cap still applies.
        $name = trim((string)$this->dotGet($data, 'Customers[name]'));
        if ($name !== '') {
            if (!$isEdit) {
                $wordCount = preg_match_all('/\S+/u', $name);
                if ($wordCount < 2) {
                    $errors['Customers[name]'] = 'الرجاء إدخال الاسم الرباعي (4 كلمات يفضّل، 2 كحد أدنى).';
                }
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

        // Sex must be one of the accepted enum values (0 = male, 1 = female).
        // Aligned with the legacy app's column convention so the wizard can
        // edit existing customers without flipping their stored gender.
        $sex = $this->dotGet($data, 'Customers[sex]');
        if ($sex !== null && $sex !== '' && !in_array((string)$sex, ['0', '1'], true)) {
            $errors['Customers[sex]'] = 'قيمة الجنس غير صالحة.';
        }

        // Notes — soft cap 500 chars.
        $notes = (string)$this->dotGet($data, 'Customers[notes]');
        if (mb_strlen($notes) > 500) {
            $errors['Customers[notes]'] = 'الملاحظات تتجاوز 500 حرف.';
        }

        // ── Personal photo is mandatory for new customers ──
        // The photo lives outside the Customers form (uploaded async via
        // upload-extra) and is therefore not in $data. Treat the auto-saved
        // draft as the source of truth so we can't be tricked by a forged
        // hidden input — the hidden Customers[_extras_photo_id] field exists
        // only so renderServerErrors() in core.js can attach the message to
        // the right card. Skip enforcement when editing an existing customer.
        $photoErr = $this->validateRequiredPhoto();
        if ($photoErr !== null) {
            $errors['Customers[_extras_photo_id]'] = $photoErr;
        }

        // ── Fahras gate (server-side authoritative) ──
        // Only run when identity basics are syntactically valid; otherwise
        // the user is shown the field-level errors first and re-runs the
        // step. This avoids burning Fahras quota on obviously bad inputs.
        if (empty($errors['Customers[id_number]']) && empty($errors['Customers[name]'])) {
            $idNum = trim((string)$this->dotGet($data, 'Customers[id_number]'));
            $nm    = trim((string)$this->dotGet($data, 'Customers[name]'));
            $ph    = trim((string)$this->dotGet($data, 'Customers[primary_phone_number]'));

            $fahrasErr = $this->runFahrasGate($idNum, $nm, $ph, FahrasCheckLog::SOURCE_STEP1);
            if ($fahrasErr !== null) {
                $errors['Customers[id_number]'] = $fahrasErr;
            }
        }

        return $errors;
    }

    /**
     * Server-side Fahras verdict gate. Returns:
     *   • null            → allowed to proceed.
     *   • Arabic string   → block, message to attach to Customers[id_number].
     *
     * Honours the manager-recorded override stored in the draft under
     * `_fahras_override` (matching id_number + recent `at`); if a valid
     * override exists, the gate returns null.
     *
     * Triggered on every wizard step-1 validation AND on actionFinish()
     * for defense-in-depth.
     */
    protected function runFahrasGate(string $idNumber, string $name, string $phone, string $source): ?string
    {
        if ($idNumber === '' && $name === '') return null;

        $svc = Yii::$app->fahras ?? null;
        if (!$svc || !$svc->enabled) return null;

        // ── Fahras only gates new customer CREATION. Editing an existing
        // customer must not block on Fahras (the customer is already in
        // our system, the gate is moot, and many edits update fields that
        // have nothing to do with eligibility). ──
        try {
            $key = $this->draftKey();
            if (strpos($key, 'customer_edit:') === 0) {
                return null;
            }
        } catch (\Throwable $e) {
            // Defensive: if draft-key resolution fails for any reason, fall
            // through and enforce the gate (fail-closed for create flows).
        }

        // Honour an active manager override stored in the current draft.
        try {
            $draft = WizardDraft::loadAutoDraft(Yii::$app->user->id, $this->draftKey());
            if ($draft) {
                $payload = $this->decodePayload($draft);
                $ovr = $payload['_fahras_override'] ?? null;
                if (is_array($ovr)
                    && (string)($ovr['id_number'] ?? '') === $idNumber
                    && (int)($ovr['at'] ?? 0) > (time() - 86400) // valid for 24h
                ) {
                    return null;
                }
            }
        } catch (\Throwable $e) {
            // fallthrough: if we cannot read the draft, fall back to enforcing the gate.
        }

        $verdict = $svc->check($idNumber, $name ?: null, $phone ?: null);

        FahrasCheckLog::record($verdict, [
            'id_number' => $idNumber,
            'name'      => $name,
            'phone'     => $phone,
            'source'    => $source,
        ]);

        if (!$verdict->blocks($svc->failurePolicy)) {
            return null;
        }

        if ($verdict->verdict === FahrasVerdict::VERDICT_ERROR) {
            return 'تعذّر التحقق من العميل في نظام الفهرس — حاول لاحقاً (لا يمكن المتابعة دون فحص الفهرس).';
        }
        $msg = trim($verdict->reasonAr) !== ''
            ? $verdict->reasonAr
            : 'الفهرس يمنع إضافة هذا العميل.';
        return 'الفهرس: ' . $msg;
    }

    /**
     * Enforce the «الصورة الشخصية» requirement on Step 1.
     *
     * Returns:
     *   • null            → photo present (or this is an edit flow that
     *                       does not require one).
     *   • Arabic string   → block, message to attach under the photo card
     *                       on the wizard via the standard server-error
     *                       renderer (keyed on Customers[_extras_photo_id]).
     *
     * Authoritative source is the auto-saved draft's `_extras.photo`
     * structure (populated by recordExtraInDraft() once the upload-extra
     * AJAX succeeds) — never the posted form data. The hidden form input
     * is only used so renderServerErrors() can locate the right DOM card
     * to highlight.
     */
    protected function validateRequiredPhoto(): ?string
    {
        // Editing an existing customer must not be blocked: many edits
        // change a single field and have nothing to do with the photo,
        // and we don't want to force re-uploading historical files.
        try {
            $key = $this->draftKey();
            if (strpos($key, 'customer_edit:') === 0) {
                return null;
            }
        } catch (\Throwable $e) {
            // Defensive: if the draft key can't be resolved, fall through
            // and enforce the rule (fail-closed for create flows).
        }

        try {
            $draft = WizardDraft::loadAutoDraft(Yii::$app->user->id, $this->draftKey());
            if ($draft) {
                $payload = $this->decodePayload($draft);
                $photo   = $payload['_extras']['photo'] ?? null;
                if (is_array($photo) && (int)($photo['image_id'] ?? 0) > 0) {
                    return null;
                }
            }
        } catch (\Throwable $e) {
            Yii::warning(
                'validateRequiredPhoto: draft read failed: ' . $e->getMessage(),
                __METHOD__
            );
            // Fall through and block — better to ask the user to retry
            // the upload than to silently allow a customer with no photo.
        }

        return 'الصورة الشخصية للعميل مطلوبة — ارفع صورة واضحة للوجه قبل المتابعة.';
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
        $isEdit = $this->isEditMode();

        // ── Required basics (skipped in edit mode). ──
        if (!$isEdit) {
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
        // Required-enforcement skipped in edit mode (length cap still applies).
        if ((string)$isSocSec === '1') {
            $socNum = trim((string)$this->dotGet($data, 'Customers[social_security_number]'));
            if ($socNum === '' && !$isEdit) {
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
            if ($src === '' && !$isEdit) {
                $errors['Customers[social_security_salary_source]'] = 'يرجى اختيار مصدر الراتب التقاعدي.';
            } elseif ($src !== '' && !in_array($src, $allowedSrc, true)) {
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

        // ── Real-estate (relocated from Step 3 — see _step_2_employment.php
        //    Section D for rationale). The wizard now uses a multi-row
        //    repeater under `realestates[]`; the legacy single-row Customers
        //    columns are derived server-side from the first non-empty row
        //    (see finishCreate / finishEdit). Validation here only enforces
        //    per-field length limits — the "owns property?" radio is purely
        //    a UX disclosure toggle and is intentionally not required. ──
        $owns = $this->dotGet($data, 'Customers[do_have_any_property]');
        if ($owns !== null && $owns !== '' && !in_array((string)$owns, ['0', '1'], true)) {
            $errors['Customers[do_have_any_property]'] = 'القيمة غير صالحة.';
        }
        $realestates = $data['realestates'] ?? [];
        if (is_array($realestates)) {
            foreach ($realestates as $i => $re) {
                if (!is_array($re)) continue;
                $rt = trim((string)($re['property_type']   ?? ''));
                $rn = trim((string)($re['property_number'] ?? ''));
                if ($rt === '' && $rn === '') continue; // empty rows are dropped silently
                if ($rt === '' && !$isEdit) {
                    $errors["realestates[$i][property_type]"] = 'اسم/نوع العقار مطلوب.';
                } elseif (mb_strlen($rt) > 100) {
                    $errors["realestates[$i][property_type]"] = 'الاسم طويل جداً (الحد الأقصى 100 حرف).';
                }
                if (mb_strlen($rn) > 100) {
                    $errors["realestates[$i][property_number]"] = 'الرقم طويل جداً (الحد الأقصى 100 خانة).';
                }
            }
        }

        return $errors;
    }

    /**
     * Validate Step 3 — guarantors & primary residential address.
     *
     * Required:
     *   • At least 1 guarantor with non-empty name + phone + relationship.
     *   • address[address_city] must be filled.
     *
     * Optional:
     *   • Additional guarantors (max 10), address area/street/etc.
     *
     * NOTE: Real-estate validation moved to validateStep2() — see Section D
     * of the financial-position card for rationale.
     */
    protected function validateStep3($data)
    {
        $errors = [];
        $isEdit = $this->isEditMode();

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

        if (count($filled) === 0 && !$isEdit) {
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

            if ($name === '' && !$isEdit) {
                $errors["guarantors[$i][owner_name]"] = 'الاسم مطلوب.';
            } elseif (mb_strlen($name) > 100) {
                $errors["guarantors[$i][owner_name]"] = 'الاسم طويل جداً (الحد الأقصى 100 حرف).';
            }

            if ($phone === '' && !$isEdit) {
                $errors["guarantors[$i][phone_number]"] = 'رقم الهاتف مطلوب.';
            } elseif ($phone !== '') {
                $digits = preg_replace('/\D+/', '', $phone);
                $okJO   = (bool)preg_match('/^(?:00962|962|0)?7[789]\d{7}$/', $digits);
                $okIntl = strlen($digits) >= 8 && strlen($digits) <= 15;
                if (!$okJO && !$okIntl) {
                    $errors["guarantors[$i][phone_number]"] = 'رقم الهاتف غير صالح.';
                }
            }

            if ($rel === '' && !$isEdit) {
                $errors["guarantors[$i][phone_number_owner]"] = 'حدّد صلة القرابة.';
            } elseif (mb_strlen($rel) > 100) {
                $errors["guarantors[$i][phone_number_owner]"] = 'القيمة طويلة جداً.';
            }

            if (mb_strlen($fb) > 255) {
                $errors["guarantors[$i][fb_account]"] = 'حساب فيسبوك طويل جداً.';
            }
        }

        // ── Addresses (residential required, work optional). Both blocks
        //    share the same validation rules for length/coords; only the
        //    "city is required" rule differs by slot. ──
        $addresses = $this->dotGet($data, 'addresses');
        if (!is_array($addresses)) {
            // Backward-compat: a legacy single "address" key is mapped to
            // the residential slot so older drafts still validate cleanly.
            $legacy = $this->dotGet($data, 'address');
            $addresses = [
                'home' => is_array($legacy) ? $legacy : [],
            ];
        }

        // In edit mode both blocks are optional — the customer already has
        // an address on file and the user may want to tweak unrelated fields.
        $blocks = [
            'home' => ['required' => !$isEdit, 'cityLabel' => 'مدينة السكن'],
            'work' => ['required' => false,    'cityLabel' => 'مدينة العمل'],
        ];

        foreach ($blocks as $slot => $cfg) {
            $addr = isset($addresses[$slot]) && is_array($addresses[$slot])
                ? $addresses[$slot]
                : [];

            // Detect whether the user filled ANY meaningful field. Optional
            // blocks are validated only when at least one field is present
            // — fully-empty optional blocks are silently ignored so users
            // aren't forced to enter a work address.
            $hasAny = false;
            foreach (['address_city', 'address_area', 'address_street',
                      'address_building', 'postal_code', 'address',
                      'latitude', 'longitude', 'plus_code'] as $k) {
                if (trim((string)($addr[$k] ?? '')) !== '') { $hasAny = true; break; }
            }

            $city = trim((string)($addr['address_city'] ?? ''));

            if ($cfg['required'] && $city === '') {
                $errors["addresses[$slot][address_city]"] = $cfg['cityLabel'] . ' مطلوبة.';
            } elseif (!$cfg['required'] && !$hasAny) {
                // Optional & empty → skip the rest of this block entirely.
                continue;
            } elseif (mb_strlen($city) > 100) {
                $errors["addresses[$slot][address_city]"] = 'اسم المدينة طويل جداً.';
            }

            foreach (['address_area' => 100, 'address_building' => 100,
                      'postal_code'  => 20,  'address'          => 255] as $field => $cap) {
                $v = (string)($addr[$field] ?? '');
                if (mb_strlen($v) > $cap) {
                    $errors["addresses[$slot][$field]"] = "القيمة طويلة جداً (الحد الأقصى {$cap}).";
                }
            }
            $street = (string)($addr['address_street'] ?? '');
            if (mb_strlen($street) > 500) {
                $errors["addresses[$slot][address_street]"] = 'الشارع طويل جداً (الحد الأقصى 500 حرف).';
            }

            // ── Map widget (optional within each block). We accept up
            //    to 8 decimals (~1 mm precision). ──
            $lat = trim((string)($addr['latitude']  ?? ''));
            $lng = trim((string)($addr['longitude'] ?? ''));
            if ($lat !== '') {
                if (!is_numeric($lat) || (float)$lat < -90 || (float)$lat > 90) {
                    $errors["addresses[$slot][latitude]"] = 'خط العرض غير صالح (-90 إلى 90).';
                }
            }
            if ($lng !== '') {
                if (!is_numeric($lng) || (float)$lng < -180 || (float)$lng > 180) {
                    $errors["addresses[$slot][longitude]"] = 'خط الطول غير صالح (-180 إلى 180).';
                }
            }
            // Either both or neither — partial coords would corrupt the
            // map UX next time the wizard is reopened.
            if (($lat === '') !== ($lng === '')) {
                $errors["addresses[$slot][latitude]"] = 'يجب تحديد خط العرض وخط الطول معاً، أو تركهما فارغين.';
            }
            $plus = (string)($addr['plus_code'] ?? '');
            if (mb_strlen($plus) > 20) {
                $errors["addresses[$slot][plus_code]"] = 'رمز Plus Code طويل جداً.';
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
