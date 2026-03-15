<?php
/**
 * Smart Customer Onboarding Form — نموذج العميل الذكي
 * Split layout: Form (8/12) + Risk Panel (4/12)
 * Mobile: Wizard + Bottom Risk Summary
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;
use kartik\select2\Select2;
use backend\helpers\PhoneInputWidget;
use backend\widgets\ImageManagerInputWidget;
use common\helper\Permissions;

/* Register assets */
$this->registerCssFile('@web/css/smart-onboarding.css', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerCssFile('@web/css/smart-media.css', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerJsFile('@web/js/smart-onboarding.js', ['depends' => [\yii\web\JqueryAsset::class], 'position' => \yii\web\View::POS_END]);
$this->registerJsFile('@web/js/smart-media.js', ['depends' => [\yii\web\JqueryAsset::class], 'position' => \yii\web\View::POS_END]);

/* Smart Media URLs for JS */
$this->registerJs("window.smConfig = " . json_encode([
    'uploadUrl'      => Url::to(['/customers/smart-media/upload']),
    'webcamUrl'      => Url::to(['/customers/smart-media/webcam-capture']),
    'classifyUrl'    => Url::to(['/customers/smart-media/classify']),
    'usageUrl'       => Url::to(['/customers/smart-media/usage-stats']),
    'googleStatsUrl' => Url::to(['/customers/smart-media/google-stats']),
    'deleteUrl'      => Url::to(['/customers/smart-media/delete']),
]) . ";", \yii\web\View::POS_HEAD);

/* Determine mode early (needed by CSS/JS registrations below) */
$isNew = $model->isNewRecord;

/* Hide AdminLTE content header */
$this->registerCss('.content-header,.page-header { display: none !important; } .content-wrapper { padding-top: 0 !important; } .content { padding: 0 !important; }');

/* Edit mode styles are in smart-onboarding.css */

/* Config for JS */
$this->registerJs("window.soConfig = " . json_encode([
    'riskCalcUrl'       => Url::to(['calculate-risk']),
    'duplicateCheckUrl' => Url::to(['check-duplicate']),
    'customerViewUrl'   => Url::to(['view', 'id' => '__ID__']),
    'isEditMode'        => !$isNew,
]) . ";", \yii\web\View::POS_HEAD);

/* Cache lookups */
$cache = Yii::$app->cache;
$p = Yii::$app->params;
$d = $p['time_duration'];
$db = Yii::$app->db;

$jobs = $cache->getOrSet($p['key_jobs'], fn() => $db->createCommand($p['jobs_query'])->queryAll(), $d);
$city = $cache->getOrSet($p['key_city'], fn() => $db->createCommand($p['city_query'])->queryAll(), $d);
$citizen = $cache->getOrSet($p['key_citizen'], fn() => $db->createCommand($p['citizen_query'])->queryAll(), $d);
$hearAboutUs = $cache->getOrSet($p['key_hear_about_us'], fn() => $db->createCommand($p['hear_about_us_query'])->queryAll(), $d);
$banks = $cache->getOrSet($p['key_banks'], fn() => $db->createCommand($p['banks_query'])->queryAll(), $d);
$cousins = ArrayHelper::map(
    \backend\modules\cousins\models\Cousins::find()->asArray()->all(),
    'id', 'name'
);

$imgRandId = rand(100000000, 1000000000);
if (empty($model->image_manager_id)) $model->image_manager_id = $imgRandId;

/* Previous contracts data */
$prevContracts = 0;
$hasDefaults = false;
if (!$isNew) {
    try {
        $prevContracts = (int)$db->createCommand("SELECT COUNT(*) FROM os_contracts_customers WHERE customer_id=:cid", [':cid' => $model->id])->queryScalar();
        if ($prevContracts > 0) {
            $hasDefaults = (int)$db->createCommand("SELECT COUNT(*) FROM os_contracts_customers cc JOIN os_contracts c ON c.id=cc.contract_id WHERE cc.customer_id=:cid AND c.status IN ('judiciary','canceled')", [':cid' => $model->id])->queryScalar() > 0;
        }
    } catch (\Exception $e) {}
}

/* Employment types */
$employmentTypes = [
    'government'    => 'حكومي',
    'military'      => 'عسكري',
    'private'       => 'قطاع خاص',
    'self_employed' => 'عمل حر',
    'retired'       => 'متقاعد',
    'unemployed'    => 'بدون عمل',
    'other'         => 'أخرى',
];

/* Customer financials (if exists) */
$financials = null;
if (!$isNew) {
    try {
        $financials = $db->createCommand("SELECT * FROM os_customer_financials WHERE customer_id=:cid", [':cid' => $model->id])->queryOne();
    } catch (\Exception $e) {}
}
?>

<div class="so-page <?= $isNew ? 'so-mode-create' : 'so-mode-edit' ?>">
    <!-- Header -->
    <div class="so-header">
        <?php if ($isNew): ?>
            <h1><i class="fa fa-user-plus"></i> إضافة عميل جديد</h1>
        <?php else: ?>
            <h1><i class="fa fa-pencil"></i> تعديل بيانات: <?= Html::encode($model->name) ?></h1>
        <?php endif ?>
        <div class="so-header-actions">
            <?php if (!$isNew): ?>
                <a href="<?= Url::to(['/contracts/contracts/create', 'customer_id' => $model->id]) ?>" class="so-back-btn" style="background:#059669;color:#fff;border-color:#059669"><i class="fa fa-file-text-o"></i> إنشاء عقد</a>
            <?php endif ?>
            <a href="<?= Url::to(['index']) ?>" class="so-back-btn"><i class="fa fa-arrow-right"></i> العودة للقائمة</a>
        </div>
    </div>

    <div class="so-body">
        <!-- ═══════════════════════════════════════════
             LEFT: FORM AREA
             ═══════════════════════════════════════════ -->
        <div class="so-form-area">
            <?php
            $formConfig = [
                'options' => [
                    'enctype' => 'multipart/form-data',
                    'data-prev-contracts' => $prevContracts,
                    'data-has-defaults' => $hasDefaults ? '1' : '0',
                ],
                'id' => 'smart-onboarding-form',
            ];
            if (isset($id)) $formConfig['action'] = Url::to(['update', 'id' => $id]);
            $form = ActiveForm::begin($formConfig);
            ?>
            <?= $form->errorSummary($model, ['class' => 'alert alert-danger', 'style' => 'border-radius:8px; font-size:13px']) ?>

            <!-- Wizard Steps / Section Navigation -->
            <div class="so-steps">
                <div class="so-step <?= $isNew ? 'active' : '' ?>" data-step="0">
                    <span class="so-step-num"><?= $isNew ? '1' : '' ?><i class="fa fa-user so-step-icon"></i></span>
                    <span class="so-step-label">البيانات الشخصية</span>
                </div>
                <div class="so-step" data-step="1">
                    <span class="so-step-num"><?= $isNew ? '2' : '' ?><i class="fa fa-briefcase so-step-icon"></i></span>
                    <span class="so-step-label">الوظيفة والدخل</span>
                </div>
                <div class="so-step" data-step="2">
                    <span class="so-step-num"><?= $isNew ? '3' : '' ?><i class="fa fa-university so-step-icon"></i></span>
                    <span class="so-step-label">البنك والضمانات</span>
                </div>
                <div class="so-step" data-step="3">
                    <span class="so-step-num"><?= $isNew ? '4' : '' ?><i class="fa fa-address-book so-step-icon"></i></span>
                    <span class="so-step-label">المعرّفون والمستندات</span>
                </div>
                <div class="so-step" data-step="4">
                    <span class="so-step-num"><?= $isNew ? '5' : '' ?><i class="fa fa-image so-step-icon"></i></span>
                    <span class="so-step-label">الصور والمراجعة</span>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 STEP 1: البيانات الشخصية
                 ══════════════════════════════════════ -->
            <div class="so-section active" data-step="0">
                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-user"></i> البيانات الشخصية</h3>
                    <div class="so-grid so-grid-3">
                        <div><?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'الاسم الرباعي', 'required' => true])->label('اسم العميل') ?></div>
                        <div><?= $form->field($model, 'id_number')->textInput(['maxlength' => true, 'placeholder' => 'الرقم الوطني', 'required' => true])->label('الرقم الوطني') ?></div>
                        <div><?= $form->field($model, 'sex')->dropDownList([0 => 'ذكر', 1 => 'أنثى'])->label('الجنس') ?></div>
                    </div>
                    <div class="so-grid so-grid-3" style="margin-top: 16px">
                        <div><?= $form->field($model, 'birth_date')->widget(FlatpickrWidget::class, [
                            'options' => ['placeholder' => 'YYYY-MM-DD', 'required' => true],
                            'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                        ])->label('تاريخ الميلاد') ?></div>
                        <div><?= $form->field($model, 'city')->dropDownList(ArrayHelper::map($city, 'id', 'name'), ['prompt' => '-- المدينة --'])->label('مدينة الولادة') ?></div>
                        <div><?= $form->field($model, 'citizen')->dropDownList(ArrayHelper::map($citizen, 'id', 'name'), ['prompt' => '-- الجنسية --'])->label('الجنسية') ?></div>
                    </div>
                </div>

                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-phone"></i> بيانات التواصل</h3>
                    <div class="so-grid so-grid-3">
                        <div><?= $form->field($model, 'primary_phone_number')->widget(PhoneInputWidget::class, [
                            'options' => ['class' => 'form-control', 'inputmode' => 'tel', 'autocomplete' => 'tel'],
                        ])->label('الهاتف الرئيسي') ?></div>
                        <div><?= $form->field($model, 'email')->textInput(['type' => 'email', 'placeholder' => 'example@email.com'])->label('البريد الإلكتروني') ?></div>
                        <div><?= $form->field($model, 'hear_about_us')->dropDownList(ArrayHelper::map($hearAboutUs, 'id', 'name'), ['prompt' => '-- كيف سمعت عنا --'])->label('كيف سمعت عنا') ?></div>
                    </div>
                </div>

                <div class="so-nav">
                    <span></span>
                    <button type="button" class="so-btn so-btn-primary so-next-btn"><span>الخطوة التالية</span> <i class="fa fa-arrow-left"></i></button>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 STEP 2: الوظيفة والدخل
                 ══════════════════════════════════════ -->
            <div class="so-section" data-step="1">
                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-briefcase"></i> المعلومات المهنية</h3>
                    <div class="so-grid so-grid-3">
                        <div class="job-title-wrapper">
                            <?= $form->field($model, 'job_title')->widget(Select2::class, [
                                'initValueText' => $model->job_title ? \backend\modules\jobs\models\Jobs::findOne($model->job_title)?->name : '',
                                'options' => ['placeholder' => 'ابحث عن جهة العمل...', 'id' => 'customers-job_title'],
                                'pluginOptions' => [
                                    'allowClear' => true,
                                    'dir' => 'rtl',
                                    'minimumInputLength' => 0,
                                    'ajax' => [
                                        'url' => Url::to(['/jobs/jobs/search-list']),
                                        'dataType' => 'json',
                                        'delay' => 200,
                                        'data' => new \yii\web\JsExpression('function(p){return {q:p.term||""};}'),
                                        'processResults' => new \yii\web\JsExpression('function(d){return d;}'),
                                    ],
                                ],
                            ])->label('جهة العمل') ?>
                            <button type="button" class="btn-add-job" id="btn-add-job-smart" title="إضافة جهة عمل جديدة"><i class="fa fa-plus"></i></button>
                        </div>
                        <div>
                            <div class="form-group">
                                <label for="fin-employer-name">المسمى الوظيفي</label>
                                <input type="text" id="fin-employer-name" name="CustomerFinancials[employer_name]" class="form-control" value="<?= Html::encode($financials['employer_name'] ?? '') ?>" placeholder="مثال: محاسب، سائق، مهندس">
                            </div>
                        </div>
                        <div>
                            <div class="form-group">
                                <label for="fin-employment-type">نوع التوظيف</label>
                                <select id="fin-employment-type" name="CustomerFinancials[employment_type]" class="form-control">
                                    <option value="">-- اختر --</option>
                                    <?php foreach ($employmentTypes as $k => $v): ?>
                                        <option value="<?= $k ?>" <?= ($financials['employment_type'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="so-grid so-grid-3" style="margin-top: 16px">
                        <div><?= $form->field($model, 'job_number')->textInput(['maxlength' => true, 'placeholder' => 'الرقم الوظيفي'])->label('الرقم الوظيفي') ?></div>
                        <div>
                            <div class="form-group">
                                <label for="fin-years-at-job">سنوات الخدمة</label>
                                <input type="number" id="fin-years-at-job" name="CustomerFinancials[years_at_current_job]" class="form-control" value="<?= $financials['years_at_current_job'] ?? '' ?>" step="0.5" min="0" placeholder="0">
                            </div>
                        </div>
                        <div><?= $form->field($model, 'last_job_query_date')->widget(FlatpickrWidget::class, [
                            'options' => ['placeholder' => 'آخر استعلام'],
                            'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                        ])->label('آخر استعلام وظيفي') ?></div>
                    </div>
                </div>

                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-money"></i> الدخل والالتزامات</h3>
                    <div class="so-grid so-grid-4">
                        <div><?= $form->field($model, 'total_salary')->textInput(['type' => 'number', 'step' => '0.01', 'placeholder' => '0.00'])->label('الراتب الأساسي (شهري)') ?></div>
                        <div>
                            <div class="form-group">
                                <label for="fin-additional-income">دخل إضافي (شهري)</label>
                                <input type="number" id="fin-additional-income" name="CustomerFinancials[additional_income]" class="form-control" value="<?= $financials['additional_income'] ?? '0' ?>" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                        <div>
                            <div class="form-group">
                                <label for="fin-obligations">الالتزامات الشهرية</label>
                                <input type="number" id="fin-obligations" name="CustomerFinancials[monthly_obligations]" class="form-control" value="<?= $financials['monthly_obligations'] ?? '0' ?>" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                        <div>
                            <div class="form-group">
                                <label for="fin-dependents">عدد المعالين</label>
                                <input type="number" id="fin-dependents" name="CustomerFinancials[dependents_count]" class="form-control" value="<?= $financials['dependents_count'] ?? '0' ?>" min="0" placeholder="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="so-nav">
                    <button type="button" class="so-btn so-btn-outline so-prev-btn"><i class="fa fa-arrow-right"></i> <span>السابق</span></button>
                    <button type="button" class="so-btn so-btn-primary so-next-btn"><span>الخطوة التالية</span> <i class="fa fa-arrow-left"></i></button>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 STEP 3: البنك والضمانات
                 ══════════════════════════════════════ -->
            <div class="so-section" data-step="2">
                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-university"></i> الحساب البنكي</h3>
                    <div class="so-grid so-grid-3">
                        <div><?= $form->field($model, 'bank_name')->widget(Select2::class, [
                            'data' => ArrayHelper::map($banks, 'id', 'name'),
                            'options' => ['placeholder' => 'اختر البنك'],
                            'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                        ])->label('البنك') ?></div>
                        <div><?= $form->field($model, 'bank_branch')->textInput(['maxlength' => true, 'placeholder' => 'اسم الفرع'])->label('الفرع') ?></div>
                        <div><?= $form->field($model, 'account_number')->textInput(['maxlength' => true, 'placeholder' => 'رقم الحساب'])->label('رقم الحساب') ?></div>
                    </div>
                </div>

                <div class="so-fieldset so-ss-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-shield"></i> الضمان والتقاعد</h3>

                    <!-- 1. مشترك بالضمان؟ -->
                    <div class="so-ss-row">
                        <?= $form->field($model, 'is_social_security')->dropDownList([0 => 'لا', 1 => 'نعم'], ['prompt' => '-- مشترك بالضمان؟ --', 'class' => 'form-control js-ss-trigger'])->label('مشترك بالضمان؟') ?>
                    </div>
                    <div class="js-social-number-row so-ss-conditional" style="display:<?= (!$isNew && $model->is_social_security == 1) ? 'block' : 'none' ?>">
                        <?= $form->field($model, 'social_security_number')->textInput(['placeholder' => 'رقم اشتراك الضمان'])->label('رقم اشتراك الضمان') ?>
                    </div>

                    <!-- 2. راتب تقاعد؟ -->
                    <div class="so-ss-row" style="margin-top: 16px">
                        <?= $form->field($model, 'has_social_security_salary')->dropDownList(['yes' => 'نعم', 'no' => 'لا'], ['prompt' => '-- يتقاضى رواتب تقاعدية؟ --', 'class' => 'form-control js-ss-trigger'])->label('يتقاضى رواتب تقاعدية؟') ?>
                    </div>
                    <div class="js-salary-source-row so-ss-conditional" style="display:<?= (!$isNew && $model->has_social_security_salary == 'yes') ? 'block' : 'none' ?>">
                        <div style="margin-top: 12px"><?= $form->field($model, 'social_security_salary_source')->dropDownList(Yii::$app->params['socialSecuritySources'] ?? [], ['prompt' => '-- المصدر --', 'class' => 'form-control js-ss-trigger'])->label('مصدر الراتب') ?></div>
                        <div class="js-retirement-fields so-grid so-grid-2" style="margin-top: 12px; display:<?= in_array($model->social_security_salary_source ?? '', ['retirement_directorate', 'both']) ? 'flex' : 'none' ?>">
                            <div><?= $form->field($model, 'retirement_status')->dropDownList(['effective' => 'فعّال', 'stopped' => 'متوقف'], ['prompt' => '--'])->label('حالة التقاعد') ?></div>
                            <div><?= $form->field($model, 'total_retirement_income')->textInput(['type' => 'number', 'step' => '0.01', 'placeholder' => '0.00'])->label('دخل التقاعد') ?></div>
                        </div>
                    </div>

                    <!-- 3. آخر استعلام دخل -->
                    <div class="so-ss-row" style="margin-top: 16px">
                        <?= $form->field($model, 'last_income_query_date')->widget(FlatpickrWidget::class, [
                            'options' => ['placeholder' => 'آخر استعلام دخل'],
                            'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                        ])->label('آخر استعلام دخل') ?>
                    </div>

                    <!-- 4. يملك عقارات؟ -->
                    <div class="so-ss-row" style="margin-top: 16px">
                        <?= $form->field($model, 'do_have_any_property')->dropDownList([0 => 'لا', 1 => 'نعم'], ['prompt' => '-- يملك عقارات؟ --', 'class' => 'form-control js-ss-trigger'])->label('يملك عقارات؟') ?>
                    </div>
                </div>

                <!-- Real Estate (conditional) -->
                <div class="js-real-estate-section" style="display:<?= (!$isNew && $model->do_have_any_property == 1) ? 'block' : 'none' ?>">
                    <div class="so-fieldset">
                        <h3 class="so-fieldset-title"><i class="fa fa-building"></i> العقارات</h3>
                        <?= $this->render('partial/real_estate', ['form' => $form, 'modelRealEstate' => $modelRealEstate]) ?>
                    </div>
                </div>

                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-sticky-note"></i> ملاحظات</h3>
                    <?= $form->field($model, 'notes')->textarea(['rows' => 2, 'maxlength' => true, 'placeholder' => 'ملاحظات إضافية'])->label(false) ?>
                </div>

                <div class="so-nav">
                    <button type="button" class="so-btn so-btn-outline so-prev-btn"><i class="fa fa-arrow-right"></i> <span>السابق</span></button>
                    <button type="button" class="so-btn so-btn-primary so-next-btn"><span>الخطوة التالية</span> <i class="fa fa-arrow-left"></i></button>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 STEP 4: المعرّفون والمستندات
                 ══════════════════════════════════════ -->
            <div class="so-section" data-step="3">
                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-map-marker"></i> العناوين</h3>
                    <?= $this->render('partial/address', ['form' => $form, 'modelsAddress' => $modelsAddress]) ?>
                </div>

                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-address-book"></i> المعرّفون</h3>
                    <?= $this->render('partial/phone_numbers', ['form' => $form, 'modelsPhoneNumbers' => $modelsPhoneNumbers, 'cousins' => $cousins]) ?>
                </div>

                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-file-o"></i> المستندات</h3>
                    <?= $this->render('partial/customer_documents', ['form' => $form, 'customerDocumentsModel' => $customerDocumentsModel]) ?>
                </div>

                <div class="so-nav">
                    <button type="button" class="so-btn so-btn-outline so-prev-btn"><i class="fa fa-arrow-right"></i> <span>السابق</span></button>
                    <button type="button" class="so-btn so-btn-primary so-next-btn"><span>الخطوة التالية</span> <i class="fa fa-arrow-left"></i></button>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 STEP 5: الصور والمراجعة النهائية
                 ══════════════════════════════════════ -->
            <div class="so-section" data-step="4">
                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-image"></i> الصور والمستندات الذكية</h3>

                    <!-- Hidden fields for backward compatibility -->
                    <?= $form->field($model, 'selected_image')->hiddenInput()->label(false) ?>
                    <?= $form->field($model, 'image_manager_id')->hiddenInput()->label(false) ?>
                    <input type="hidden" name="customer_id_for_media" value="<?= $isNew ? '' : $model->id ?>">

                    <?php if (!$isNew && !empty($model->selected_image)): ?>
                        <div style="margin-bottom:15px">
                            <img src="<?= $model->selectedImagePath ?>" style="max-width:200px;border-radius:8px;border:2px solid #e2e8f0" alt="صورة العميل">
                            <span style="display:block;font-size:11px;color:#64748b;margin-top:4px"><i class="fa fa-star" style="color:#f59e0b"></i> الصورة المختارة</span>
                        </div>
                    <?php endif ?>

                    <?php if (!$isNew): ?>
                    <!-- ═══ معرض الصور الحالية ═══ -->
                    <?php
                    $currentImages = $db->createCommand(
                        "SELECT id, fileName, fileHash, groupName, created FROM os_ImageManager 
                         WHERE CAST(contractId AS UNSIGNED) = :cid 
                         AND groupName IN ('coustmers','customers','0','1','2','3','4','5','6','7','8','9') 
                         ORDER BY created DESC LIMIT 20",
                        [':cid' => $model->id]
                    )->queryAll();
                    ?>
                    <?php if (!empty($currentImages)): ?>
                    <div style="margin-bottom:16px">
                        <h4 style="font-size:13px;font-weight:700;color:#334155;margin-bottom:8px"><i class="fa fa-images"></i> الصور المرفوعة (<?= count($currentImages) ?>)</h4>
                        <div style="display:flex;flex-wrap:wrap;gap:8px">
                            <?php 
                            $DOC_TYPES = ['0'=>'هوية وطنية','1'=>'جواز سفر','2'=>'رخصة قيادة','3'=>'شهادة ميلاد','4'=>'شهادة تعيين','5'=>'كتاب ضمان','6'=>'كشف راتب','7'=>'تعيين عسكري','8'=>'صورة شخصية','9'=>'أخرى'];
                            foreach ($currentImages as $cimg):
                                $ext = pathinfo($cimg['fileName'] ?? '', PATHINFO_EXTENSION);
                                $imgPath = '/images/imagemanager/' . $cimg['id'] . '_' . $cimg['fileHash'] . '.' . $ext;
                                $typeName = $DOC_TYPES[$cimg['groupName']] ?? '';
                                $isSelected = (!empty($model->selected_image) && $model->selected_image == $cimg['id']);
                            ?>
                            <div style="width:110px;text-align:center;position:relative">
                                <img src="<?= $imgPath ?>" style="width:110px;height:80px;object-fit:cover;border-radius:6px;border:2px solid <?= $isSelected ? '#f59e0b' : '#e2e8f0' ?>;cursor:pointer" 
                                     onclick="window.open('<?= $imgPath ?>','_blank')" title="<?= $typeName ?>">
                                <?php if ($typeName): ?>
                                    <span style="display:block;font-size:10px;color:#64748b;margin-top:2px"><?= $typeName ?></span>
                                <?php endif ?>
                                <?php if ($isSelected): ?>
                                    <span style="position:absolute;top:2px;right:2px;background:#f59e0b;color:#fff;border-radius:50%;width:18px;height:18px;font-size:10px;display:flex;align-items:center;justify-content:center"><i class="fa fa-star"></i></span>
                                <?php endif ?>
                            </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                    <?php endif ?>
                    <?php endif ?>

                    <!-- ═══ Smart Media: Drag & Drop Upload ═══ -->
                    <div class="sm-zone">
                        <input type="file" multiple accept="image/*,application/pdf">
                        <div class="sm-zone-icon"><i class="fa fa-cloud-upload"></i></div>
                        <div class="sm-zone-text">اسحب الملفات هنا أو اضغط للاختيار</div>
                        <div class="sm-zone-hint">يدعم: JPG, PNG, WebP, PDF — حد أقصى 20MB لكل ملف</div>
                    </div>

                    <!-- ═══ Action Buttons ═══ -->
                    <div class="sm-actions">
                        <button type="button" class="sm-action-btn sm-webcam-btn">
                            <i class="fa fa-camera"></i> التقاط من الكاميرا
                        </button>
                        <button type="button" class="sm-action-btn" onclick="$('.sm-zone input').click()">
                            <i class="fa fa-folder-open"></i> اختيار ملفات
                        </button>
                    </div>

                    <!-- ═══ WebCam Interface ═══ -->
                    <div class="sm-webcam">
                        <video autoplay playsinline muted></video>
                        <canvas></canvas>
                        <div class="sm-webcam-controls">
                            <button type="button" class="sm-cam-btn sm-cam-close" title="إغلاق"><i class="fa fa-times"></i></button>
                            <button type="button" class="sm-cam-btn sm-cam-capture" title="التقاط"><i class="fa fa-camera"></i></button>
                            <button type="button" class="sm-cam-btn sm-cam-switch" title="تبديل الكاميرا"><i class="fa fa-refresh"></i></button>
                        </div>
                    </div>

                    <!-- ═══ Gallery: Uploaded Files + AI Results ═══ -->
                    <div class="sm-gallery">
                        <?php
                        /* Pre-populate gallery with existing images from os_ImageManager */
                        if (!$isNew && $model->id) {
                            $existingImages = (new \yii\db\Query())
                                ->from('{{%ImageManager}}')
                                ->where(['customer_id' => (int)$model->id])
                                ->orderBy(['id' => SORT_DESC])
                                ->all();

                            $docTypes = [
                                '0' => 'هوية وطنية', '1' => 'جواز سفر', '2' => 'رخصة قيادة',
                                '3' => 'شهادة ميلاد', '4' => 'شهادة تعيين', '5' => 'كتاب ضمان اجتماعي',
                                '6' => 'كشف راتب', '7' => 'شهادة تعيين عسكري', '8' => 'صورة شخصية', '9' => 'أخرى'
                            ];

                            foreach ($existingImages as $idx => $img) {
                                $imgExt = pathinfo($img['fileName'] ?? '', PATHINFO_EXTENSION);
                                $imgWebPath = '/images/imagemanager/' . $img['id'] . '_' . $img['fileHash'] . '.' . $imgExt;
                                $imgLabel = $docTypes[$img['groupName'] ?? '9'] ?? 'أخرى';
                                $isPdf = strtolower($imgExt) === 'pdf';
                                $thumbSrc = $isPdf ? '/css/images/pdf-icon.png' : $imgWebPath;
                        ?>
                        <div class="sm-card" data-image-id="<?= $img['id'] ?>">
                            <div class="sm-card-actions">
                                <button type="button" class="sm-card-action danger sm-delete-btn" data-path="<?= htmlspecialchars($imgWebPath) ?>" data-image-id="<?= $img['id'] ?>" title="حذف"><i class="fa fa-trash"></i></button>
                                <button type="button" class="sm-card-action sm-reclassify-btn" data-path="<?= htmlspecialchars($imgWebPath) ?>" data-image-id="<?= $img['id'] ?>" title="إعادة تصنيف AI"><i class="fa fa-magic"></i></button>
                            </div>
                            <img class="sm-card-img" src="<?= htmlspecialchars($thumbSrc) ?>" alt="">
                            <div class="sm-card-body">
                                <div class="sm-card-name"><?= htmlspecialchars($img['fileName']) ?></div>
                                <div class="sm-card-meta"><span><?= $imgLabel ?></span><span><?= date('Y-m-d', strtotime($img['created'])) ?></span></div>
                                <select class="sm-type-select" data-path="<?= htmlspecialchars($imgWebPath) ?>" data-image-id="<?= $img['id'] ?>">
                                    <?php foreach ($docTypes as $k => $v): ?>
                                    <option value="<?= $k ?>"<?= ($k == ($img['groupName'] ?? '9')) ? ' selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <?php } } ?>
                        <!-- New cards added dynamically by smart-media.js -->
                    </div>

                    <!-- ═══ AI Usage Stats Widget — Dual Source ═══ -->
                    <div class="sm-usage" style="margin-top:20px">
                        <h4 class="sm-usage-title"><i class="fa fa-bar-chart"></i> إحصائيات التصنيف الذكي</h4>

                        <!-- Tab Toggle: Local vs Google -->
                        <div style="display:flex; gap:4px; margin-bottom:12px; border-bottom:2px solid #eee; padding-bottom:8px">
                            <button type="button" class="sm-tab-btn active" data-tab="local" style="padding:5px 14px; border:1px solid #ddd; border-radius:6px 6px 0 0; background:#800020; color:#fff; font-size:11px; font-weight:700; cursor:pointer; border-bottom:none">
                                <i class="fa fa-database"></i> تتبع النظام
                            </button>
                            <button type="button" class="sm-tab-btn" data-tab="google" style="padding:5px 14px; border:1px solid #ddd; border-radius:6px 6px 0 0; background:#fff; color:#555; font-size:11px; font-weight:700; cursor:pointer; border-bottom:none">
                                <i class="fa fa-google"></i> Google Cloud مباشر
                            </button>
                        </div>

                        <!-- LOCAL Stats Panel -->
                        <div class="sm-stats-panel" data-panel="local">
                            <div class="sm-usage-grid">
                                <div class="sm-usage-item">
                                    <div class="sm-usage-val sm-usage-total">0</div>
                                    <div class="sm-usage-label">إجمالي الطلبات</div>
                                </div>
                                <div class="sm-usage-item">
                                    <div class="sm-usage-val sm-usage-success">0</div>
                                    <div class="sm-usage-label">ناجحة</div>
                                </div>
                                <div class="sm-usage-item">
                                    <div class="sm-usage-val sm-usage-cost">$0</div>
                                    <div class="sm-usage-label">التكلفة (تقدير)</div>
                                </div>
                                <div class="sm-usage-item">
                                    <div class="sm-usage-val sm-usage-remaining">1000</div>
                                    <div class="sm-usage-label">المتبقي مجاني</div>
                                </div>
                            </div>
                            <div class="sm-usage-bar"><div class="sm-usage-bar-fill" style="width:0%"></div></div>
                            <div class="sm-usage-hint">تتبع محلي — من سجلات النظام</div>
                        </div>

                        <!-- GOOGLE Stats Panel -->
                        <div class="sm-stats-panel" data-panel="google" style="display:none">
                            <div class="sm-usage-grid">
                                <div class="sm-usage-item">
                                    <div class="sm-usage-val sm-g-total">—</div>
                                    <div class="sm-usage-label">طلبات Google</div>
                                </div>
                                <div class="sm-usage-item">
                                    <div class="sm-usage-val sm-g-billable">—</div>
                                    <div class="sm-usage-label">قابلة للفوترة</div>
                                </div>
                                <div class="sm-usage-item">
                                    <div class="sm-usage-val sm-g-cost">—</div>
                                    <div class="sm-usage-label">التكلفة الفعلية</div>
                                </div>
                                <div class="sm-usage-item">
                                    <div class="sm-usage-val sm-g-remaining">—</div>
                                    <div class="sm-usage-label">المتبقي مجاني</div>
                                </div>
                            </div>
                            <div class="sm-usage-bar"><div class="sm-g-bar-fill sm-usage-bar-fill" style="width:0%"></div></div>
                            <div class="sm-g-status" style="font-size:11px; color:#888; margin-top:6px">
                                <i class="fa fa-spinner fa-spin"></i> جاري الاتصال بـ Google Cloud...
                            </div>
                            <div class="sm-g-billing-status" style="margin-top:6px; font-size:11px"></div>
                        </div>
                    </div>

                    <!-- ═══ Old ImageManager (Fallback) ═══ -->
                    <details style="margin-top:18px; border:1px solid #e5e5e5; border-radius:8px; padding:12px">
                        <summary style="cursor:pointer; font-size:13px; color:#888; font-weight:600">
                            <i class="fa fa-history"></i> إدارة الصور (النظام القديم)
                        </summary>
                        <div style="margin-top:12px">
                            <?= $form->field($model, 'customer_images')->widget(ImageManagerInputWidget::class, [
                                'aspectRatio' => 16/9,
                                'cropViewMode' => 1,
                                'showPreview' => true,
                                'showDeletePickedImageConfirm' => true,
                                'groupName' => 'coustmers',
                                'contractId' => $isNew ? $imgRandId : $model->id,
                            ])->label('إدارة الصور — ImageManager') ?>
                        </div>
                    </details>
                </div>

                <!-- Decision Actions -->
                <?php if ($isNew): ?>
                <div class="so-fieldset" style="background: var(--clr-primary-50); border-color: var(--clr-primary-200)">
                    <h3 class="so-fieldset-title"><i class="fa fa-gavel"></i> اتخاذ القرار</h3>
                    <p style="font-size:13px; color: var(--clr-text-muted); margin-bottom: 16px">
                        راجع التقييم في اللوحة الجانبية ثم اختر الإجراء المناسب
                    </p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap">
                        <button type="button" class="so-btn so-btn-success so-decision-btn" data-decision="approved">
                            <i class="fa fa-check-circle"></i> حفظ — مقبول
                        </button>
                        <button type="button" class="so-btn so-btn-warning so-decision-btn" data-decision="conditional">
                            <i class="fa fa-exclamation-circle"></i> حفظ — مشروط
                        </button>
                        <button type="button" class="so-btn so-btn-danger so-decision-btn" data-decision="rejected">
                            <i class="fa fa-times-circle"></i> حفظ — مرفوض
                        </button>
                        <button type="button" class="so-btn so-btn-ghost so-decision-btn" data-decision="draft">
                            <i class="fa fa-save"></i> حفظ كمسودة
                        </button>
                    </div>
                </div>
                <div class="so-nav">
                    <button type="button" class="so-btn so-btn-outline so-prev-btn"><i class="fa fa-arrow-right"></i> <span>السابق</span></button>
                    <span></span>
                </div>
                <?php else: ?>
                <!-- أزرار الحفظ — وضع التعديل -->
                <div class="so-fieldset" style="background:#f0fdf4; border-color:#bbf7d0">
                    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center">
                        <?php if (Permissions::can(Permissions::CUST_UPDATE)): ?>
                            <?= Html::submitButton('<i class="fa fa-save"></i> حفظ التعديلات', ['class' => 'so-btn so-btn-success', 'style' => 'font-size:15px; padding:12px 28px']) ?>
                        <?php endif ?>
                        <a href="<?= Url::to(['view', 'id' => $model->id]) ?>" class="so-btn so-btn-outline" style="font-size:13px"><i class="fa fa-eye"></i> عرض الملف</a>
                    </div>
                </div>
                <!-- معلومات الإنشاء -->
                <?php
                $lastUpdatedByName = '';
                if (!empty($model->last_updated_by)) {
                    $updater = \common\models\User::findOne($model->last_updated_by);
                    $lastUpdatedByName = $updater ? (trim(($updater->name ?? '') . ' ' . ($updater->last_name ?? '')) ?: $updater->username) : '';
                }
                ?>
                <div style="margin-top:12px; padding:12px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:12px; color:#64748b; display:flex; gap:20px; flex-wrap:wrap">
                    <?php if (!empty($model->created_at)): ?>
                        <span><i class="fa fa-calendar-plus-o"></i> تاريخ الإنشاء: <b><?= Yii::$app->formatter->asDatetime($model->created_at, 'yyyy-MM-dd hh:mm a') ?></b></span>
                    <?php endif ?>
                    <?php if (!empty($model->updated_at)): ?>
                        <span><i class="fa fa-clock-o"></i> آخر تعديل: <b><?= Yii::$app->formatter->asDatetime($model->updated_at, 'yyyy-MM-dd hh:mm a') ?></b></span>
                    <?php endif ?>
                    <?php if (!empty($lastUpdatedByName)): ?>
                        <span><i class="fa fa-user-o"></i> تم التعديل بواسطة: <b><?= Html::encode($lastUpdatedByName) ?></b></span>
                    <?php endif ?>
                    <span><i class="fa fa-hashtag"></i> رقم العميل: <b>#<?= $model->id ?></b></span>
                </div>
                <?php endif ?>
            </div>

            <?php ActiveForm::end() ?>
        </div>

        <!-- ═══════════════════════════════════════════
             RIGHT: RISK ASSESSMENT PANEL
             ═══════════════════════════════════════════ -->
        <div class="so-risk-panel">
            <?php if ($isNew): ?>
            <!-- ═══ وضع الإضافة: تقييم المخاطر ═══ -->
            <div class="rp-mobile-handle">
                <div class="rp-mobile-summary">
                    <span class="rp-mobile-score" style="font-size:18px; font-weight:800">—</span>
                    <span class="rp-tier-badge rp-mobile-tier rp-tier-conditional" style="font-size:11px; padding:3px 10px">—</span>
                </div>
                <div class="rp-mobile-handle-bar"></div>
            </div>

            <h3 class="rp-title"><i class="fa fa-shield"></i> تقييم المخاطر</h3>
            <div class="rp-gauge">
                <div class="rp-gauge-ring">
                    <svg viewBox="0 0 128 128">
                        <circle class="rp-gauge-bg" cx="64" cy="64" r="58" stroke-dasharray="364.42" stroke-dashoffset="0"></circle>
                        <circle class="rp-gauge-fill approved" cx="64" cy="64" r="58" stroke-dasharray="364.42" stroke-dashoffset="364.42"></circle>
                    </svg>
                    <div class="rp-gauge-center">
                        <div class="rp-score-num">—</div>
                        <div class="rp-score-label">درجة المخاطر</div>
                    </div>
                </div>
            </div>
            <div class="rp-tier"><span class="rp-tier-badge rp-tier-conditional">جاري التقييم...</span></div>
            <div class="rp-completeness">
                <div class="rp-completeness-header"><span class="rp-completeness-label">اكتمال الملف</span><span class="rp-completeness-val">0%</span></div>
                <div class="rp-completeness-bar"><div class="rp-completeness-fill" style="width: 0"></div></div>
            </div>
            <div class="rp-factors">
                <h4 class="rp-factors-title"><i class="fa fa-bar-chart"></i> أهم العوامل</h4>
                <div class="rp-factors-list"><div style="text-align:center; color:#999; font-size:12px; padding:12px 0">أدخل البيانات لبدء التقييم</div></div>
            </div>
            <div class="rp-financing" style="display:none">
                <h4 class="rp-financing-title"><i class="fa fa-calculator"></i> توصية التمويل</h4>
                <div class="rp-fin-grid">
                    <div class="rp-fin-item"><div class="rp-fin-val" id="rp-fin-max">0</div><div class="rp-fin-label">سقف التمويل</div></div>
                    <div class="rp-fin-item"><div class="rp-fin-val" id="rp-fin-installment">0</div><div class="rp-fin-label">القسط الأقصى</div></div>
                    <div class="rp-fin-item"><div class="rp-fin-val" id="rp-fin-months">—</div><div class="rp-fin-label">المدة القصوى</div></div>
                    <div class="rp-fin-item"><div class="rp-fin-val" id="rp-fin-available">0</div><div class="rp-fin-label">المتاح شهريًا</div></div>
                </div>
            </div>
            <div class="rp-alerts"></div>
            <button type="button" class="rp-toggle-reasons">عرض سبب التقييم</button>
            <div class="rp-reasons"></div>
            <div class="rp-actions">
                <button type="button" class="so-btn so-btn-success so-decision-btn" data-decision="approved"><i class="fa fa-check-circle"></i> حفظ — مقبول</button>
                <button type="button" class="so-btn so-btn-warning so-decision-btn" data-decision="conditional"><i class="fa fa-exclamation-circle"></i> حفظ — مشروط</button>
                <button type="button" class="so-btn so-btn-danger so-decision-btn" data-decision="rejected"><i class="fa fa-times-circle"></i> حفظ — مرفوض</button>
                <button type="button" class="so-btn so-btn-ghost so-decision-btn" data-decision="draft"><i class="fa fa-save"></i> حفظ كمسودة</button>
            </div>

            <?php else: ?>
            <!-- ═══ وضع التعديل: ملخص العميل ═══ -->
            <?php
            $contractsCount = (int) $db->createCommand("SELECT COUNT(*) FROM os_contracts_customers WHERE customer_id=:cid", [':cid' => $model->id])->queryScalar();
            $activeContracts = (int) $db->createCommand("SELECT COUNT(*) FROM os_contracts_customers cc INNER JOIN os_contracts c ON c.id=cc.contract_id WHERE cc.customer_id=:cid AND c.status='active'", [':cid' => $model->id])->queryScalar();
            $totalPaid = (float) $db->createCommand("SELECT COALESCE(SUM(i.amount),0) FROM os_income i INNER JOIN os_contracts_customers cc ON cc.contract_id=i.contract_id WHERE cc.customer_id=:cid", [':cid' => $model->id])->queryScalar();
            $lastFollowUp = $db->createCommand("SELECT MAX(f.date_time) FROM os_follow_up f INNER JOIN os_contracts_customers cc ON cc.contract_id=f.contract_id WHERE cc.customer_id=:cid", [':cid' => $model->id])->queryScalar();
            $existingImages = (int) $db->createCommand("SELECT COUNT(*) FROM os_ImageManager WHERE contractId=:cid AND groupName IN ('coustmers','customers','0','1','2','3','4','5','6','7','8','9')", [':cid' => $model->id])->queryScalar();
            ?>
            <div class="rp-mobile-handle">
                <div class="rp-mobile-summary">
                    <span class="rp-mobile-summary-title">ملخص العميل</span>
                </div>
                <div class="rp-mobile-handle-bar"></div>
            </div>

            <h3 class="rp-title"><i class="fa fa-user-circle"></i> ملخص العميل</h3>

            <?php if (!empty($model->selected_image)): ?>
            <div class="rp-avatar">
                <img src="<?= $model->selectedImagePath ?>" alt="">
            </div>
            <?php endif ?>

            <div class="rp-summary-grid">
                <div class="rp-summary-card rp-summary-blue">
                    <div class="rp-summary-val"><?= $contractsCount ?></div>
                    <div class="rp-summary-label">إجمالي العقود</div>
                </div>
                <div class="rp-summary-card rp-summary-green">
                    <div class="rp-summary-val"><?= $activeContracts ?></div>
                    <div class="rp-summary-label">عقود نشطة</div>
                </div>
                <div class="rp-summary-card rp-summary-amber">
                    <div class="rp-summary-val"><?= number_format($totalPaid, 0) ?></div>
                    <div class="rp-summary-label">إجمالي المدفوع</div>
                </div>
                <div class="rp-summary-card rp-summary-pink">
                    <div class="rp-summary-val"><?= $existingImages ?></div>
                    <div class="rp-summary-label">صور مرفوعة</div>
                </div>
            </div>

            <div class="rp-details">
                <div class="rp-details-row">
                    <span><i class="fa fa-phone rp-icon-cyan"></i> الهاتف</span>
                    <b dir="ltr"><?= $model->primary_phone_number ?: '—' ?></b>
                </div>
                <div class="rp-details-row">
                    <span><i class="fa fa-id-card rp-icon-purple"></i> الرقم الوطني</span>
                    <b dir="ltr"><?= $model->id_number ?: '—' ?></b>
                </div>
                <div class="rp-details-row">
                    <span><i class="fa fa-money rp-icon-green"></i> الراتب</span>
                    <b><?= $model->total_salary ? number_format($model->total_salary, 0) : '—' ?></b>
                </div>
                <div class="rp-details-row">
                    <span><i class="fa fa-calendar rp-icon-amber"></i> آخر متابعة</span>
                    <b><?= $lastFollowUp ? date('Y-m-d', strtotime($lastFollowUp)) : 'لم يُتابع' ?></b>
                </div>
                <?php if (!empty($model->notes)): ?>
                <div class="rp-details-notes">
                    <span><i class="fa fa-sticky-note"></i> ملاحظات:</span>
                    <p><?= Html::encode($model->notes) ?></p>
                </div>
                <?php endif ?>
            </div>

            <div class="rp-quick-links">
                <a href="<?= Url::to(['view', 'id' => $model->id]) ?>" class="rp-quick-link">
                    <i class="fa fa-eye rp-icon-cyan"></i> عرض ملف العميل الكامل
                </a>
                <a href="<?= Url::to(['/contracts/contracts/create', 'customer_id' => $model->id]) ?>" class="rp-quick-link">
                    <i class="fa fa-file-text-o rp-icon-green"></i> إنشاء عقد جديد
                </a>
                <a href="<?= Url::to(['/site/image-manager']) ?>" class="rp-quick-link">
                    <i class="fa fa-image rp-icon-purple"></i> إدارة الصور
                </a>
            </div>
            <?php endif ?>
        </div>
    </div>
</div>

