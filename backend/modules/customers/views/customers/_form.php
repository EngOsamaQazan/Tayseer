<?php
/**
 * نموذج بيانات العميل - بناء من الصفر
 * نموذج مقسم لأقسام واضحة مع أداء محسن
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

/* جلب القوائم المنسدلة من الكاش - دفعة واحدة */
$cache = Yii::$app->cache;
$p = Yii::$app->params;
$d = $p['time_duration'];
$db = Yii::$app->db;

$jobs = $cache->getOrSet($p['key_jobs'], fn() => $db->createCommand($p['jobs_query'])->queryAll(), $d);
$city = $cache->getOrSet($p['key_city'], fn() => $db->createCommand($p['city_query'])->queryAll(), $d);
$citizen = $cache->getOrSet($p['key_citizen'], fn() => $db->createCommand($p['citizen_query'])->queryAll(), $d);
$hearAboutUs = $cache->getOrSet($p['key_hear_about_us'], fn() => $db->createCommand($p['hear_about_us_query'])->queryAll(), $d);
$banks = $cache->getOrSet($p['key_banks'], fn() => $db->createCommand($p['banks_query'])->queryAll(), $d);

/* جلب قائمة الأقارب دفعة واحدة لتجنب N+1 في الحقول الديناميكية */
$cousins = ArrayHelper::map(
    \backend\modules\cousins\models\Cousins::find()->asArray()->all(),
    'id', 'name'
);

/* إعداد المتغيرات */
$isNew = $model->isNewRecord;
$imgRandId = rand(100000000, 1000000000);
if (empty($model->image_manager_id)) $model->image_manager_id = $imgRandId;
?>

<div class="customers-form">
    <?php
    $formConfig = [
        'options' => ['enctype' => 'multipart/form-data'],
        'id' => 'dynamic-form',
    ];
    if (isset($id)) $formConfig['action'] = Url::to(['update', 'id' => $id]);
    $form = ActiveForm::begin($formConfig);
    ?>

    <?= $form->errorSummary($model) ?>

    <!-- ═══════════════════════════════════════════
         القسم 1: البيانات الشخصية
         ═══════════════════════════════════════════ -->
    <fieldset>
        <legend><i class="fa fa-user"></i> البيانات الشخصية</legend>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'الاسم الرباعي'])->label('اسم العميل') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'sex')->dropDownList([0 => 'ذكر', 1 => 'أنثى'])->label('الجنس') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'id_number')->textInput(['maxlength' => true, 'placeholder' => 'الرقم الوطني'])->label('الرقم الوطني') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'birth_date')->widget(FlatpickrWidget::class, [
                    'options' => ['placeholder' => 'تاريخ الميلاد'],
                    'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                ])->label('تاريخ الميلاد') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <?= $form->field($model, 'city')->dropDownList(ArrayHelper::map($city, 'id', 'name'), ['prompt' => '-- المدينة --'])->label('مدينة الولادة') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'citizen')->dropDownList(ArrayHelper::map($citizen, 'id', 'name'), ['prompt' => '-- الجنسية --'])->label('الجنسية') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'hear_about_us')->dropDownList(ArrayHelper::map($hearAboutUs, 'id', 'name'), ['prompt' => '-- كيف سمعت عنا --'])->label('كيف سمعت عنا') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'primary_phone_number')->widget(PhoneInputWidget::class, [
                    'options' => ['class' => 'form-control'],
                ])->label('الهاتف الرئيسي') ?>
            </div>
        </div>
    </fieldset>

    <!-- ═══════════════════════════════════════════
         القسم 2: البيانات المهنية والمالية
         ═══════════════════════════════════════════ -->
    <fieldset>
        <legend><i class="fa fa-briefcase"></i> المعلومات المهنية</legend>
        <div class="row">
            <div class="col-md-3">
                <?= $form->field($model, 'job_title')->widget(Select2::class, [
                    'data' => ArrayHelper::map($jobs, 'id', 'name'),
                    'options' => ['placeholder' => 'اختر الوظيفة'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('المسمى الوظيفي') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'job_number')->textInput(['maxlength' => true, 'placeholder' => 'الرقم الوظيفي'])->label('الرقم الوظيفي') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'total_salary')->textInput(['type' => 'number', 'step' => '0.01', 'placeholder' => '0.00'])->label('الراتب') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'last_job_query_date')->widget(FlatpickrWidget::class, [
                    'options' => ['placeholder' => 'آخر استعلام'],
                    'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                ])->label('آخر استعلام وظيفي') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'email')->textInput(['type' => 'email', 'placeholder' => 'example@email.com'])->label('البريد الإلكتروني') ?>
            </div>
        </div>
    </fieldset>

    <!-- ═══════════════════════════════════════════
         القسم 3: الحساب البنكي
         ═══════════════════════════════════════════ -->
    <fieldset>
        <legend><i class="fa fa-university"></i> الحساب البنكي</legend>
        <div class="row">
            <div class="col-md-3">
                <?= $form->field($model, 'bank_name')->widget(Select2::class, [
                    'data' => ArrayHelper::map($banks, 'id', 'name'),
                    'options' => ['placeholder' => 'اختر البنك'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('البنك') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'bank_branch')->textInput(['maxlength' => true, 'placeholder' => 'اسم الفرع'])->label('الفرع') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'account_number')->textInput(['maxlength' => true, 'placeholder' => 'رقم الحساب'])->label('رقم الحساب') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'facebook_account')->textInput(['maxlength' => true, 'placeholder' => 'حساب فيسبوك'])->label('فيسبوك') ?>
            </div>
        </div>
    </fieldset>

    <!-- ═══════════════════════════════════════════
         القسم 4: الضمان الاجتماعي والتقاعد
         ═══════════════════════════════════════════ -->
    <fieldset>
        <legend><i class="fa fa-shield"></i> الضمان والتقاعد</legend>
        <div class="row">
            <div class="col-md-2">
                <?= $form->field($model, 'is_social_security')->dropDownList([0 => 'لا', 1 => 'نعم'], ['prompt' => '--'])->label('مشترك بالضمان؟') ?>
            </div>
            <div class="col-md-2 js-social-field" style="display:<?= (!$isNew && $model->is_social_security == 1) ? 'block' : 'none' ?>">
                <?= $form->field($model, 'social_security_number')->textInput(['placeholder' => 'رقم الضمان'])->label('رقم الضمان') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'has_social_security_salary')->dropDownList(['yes' => 'نعم', 'no' => 'لا'], ['prompt' => '--'])->label('راتب ضمان؟') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'social_security_salary_source')->dropDownList(Yii::$app->params['socialSecuritySources'] ?? [], ['prompt' => '-- المصدر --'])->label('مصدر الراتب') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'retirement_status')->dropDownList(['effective' => 'فعّال', 'stopped' => 'متوقف'], ['prompt' => '--'])->label('حالة التقاعد') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <?= $form->field($model, 'total_retirement_income')->textInput(['type' => 'number', 'step' => '0.01', 'placeholder' => '0.00'])->label('دخل التقاعد') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'last_income_query_date')->widget(FlatpickrWidget::class, [
                    'options' => ['placeholder' => 'آخر استعلام دخل'],
                    'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                ])->label('آخر استعلام دخل') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'do_have_any_property')->dropDownList([0 => 'لا', 1 => 'نعم'], ['prompt' => '--'])->label('يملك عقارات؟') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <?= $form->field($model, 'notes')->textarea(['rows' => 2, 'maxlength' => true, 'placeholder' => 'ملاحظات إضافية'])->label('ملاحظات') ?>
            </div>
        </div>
    </fieldset>

    <!-- ═══════════════════════════════════════════
         القسم 5: العقارات (يظهر حسب الاختيار)
         ═══════════════════════════════════════════ -->
    <div class="js-real-estate-section" style="display:<?= (!$isNew && $model->do_have_any_property == 1) ? 'block' : 'none' ?>">
        <fieldset>
            <legend><i class="fa fa-building"></i> العقارات</legend>
            <?= $this->render('partial/real_estate', ['form' => $form, 'modelRealEstate' => $modelRealEstate]) ?>
        </fieldset>
    </div>

    <!-- ═══════════════════════════════════════════
         القسم 6: العناوين
         ═══════════════════════════════════════════ -->
    <fieldset>
        <legend><i class="fa fa-map-marker"></i> العناوين</legend>
        <?= $this->render('partial/address', ['form' => $form, 'modelsAddress' => $modelsAddress]) ?>
    </fieldset>

    <!-- ═══════════════════════════════════════════
         القسم 7: أرقام المعرّفين
         ═══════════════════════════════════════════ -->
    <fieldset>
        <legend><i class="fa fa-address-book"></i> المعرّفون</legend>
        <?= $this->render('partial/phone_numbers', ['form' => $form, 'modelsPhoneNumbers' => $modelsPhoneNumbers, 'cousins' => $cousins]) ?>
    </fieldset>

    <!-- ═══════════════════════════════════════════
         القسم 8: المستندات
         ═══════════════════════════════════════════ -->
    <fieldset>
        <legend><i class="fa fa-file-o"></i> المستندات</legend>
        <?= $this->render('partial/customer_documents', ['form' => $form, 'customerDocumentsModel' => $customerDocumentsModel]) ?>
    </fieldset>

    <!-- ═══════════════════════════════════════════
         القسم 9: الصور
         ═══════════════════════════════════════════ -->
    <fieldset>
        <legend><i class="fa fa-image"></i> الصور</legend>
        <?= $form->field($model, 'selected_image')->hiddenInput()->label(false) ?>
        <?= $form->field($model, 'image_manager_id')->hiddenInput()->label(false) ?>

        <?php if (!$isNew && !empty($model->selected_image)): ?>
            <div class="jadal-image-preview" style="margin-bottom:15px">
                <img src="<?= $model->selectedImagePath ?>" class="img-responsive" style="max-width:350px;border-radius:8px" alt="صورة العميل">
            </div>
        <?php endif ?>

        <?= $form->field($model, 'customer_images')->widget(ImageManagerInputWidget::class, [
            'aspectRatio' => 16/9,
            'cropViewMode' => 1,
            'showPreview' => true,
            'showDeletePickedImageConfirm' => true,
            'groupName' => 'coustmers',
            'contractId' => $isNew ? $imgRandId : $model->id,
        ])->label('إدارة الصور') ?>
    </fieldset>

    <!-- زر الحفظ -->
    <?php if (!Yii::$app->request->isAjax): ?>
        <?php
        $canSubmit = $isNew ? Permissions::can(Permissions::CUST_CREATE) : Permissions::can(Permissions::CUST_UPDATE);
        ?>
        <?php if ($canSubmit): ?>
        <div class="jadal-form-actions">
            <?= Html::submitButton(
                $isNew ? '<i class="fa fa-plus"></i> حفظ العميل' : '<i class="fa fa-save"></i> حفظ التعديلات',
                ['class' => $isNew ? 'btn btn-success btn-lg' : 'btn btn-primary btn-lg']
            ) ?>
        </div>
        <?php endif ?>
    <?php endif ?>

    <?php ActiveForm::end() ?>
</div>

<?php
$jsIsNew = $isNew ? 'true' : 'false';
$this->registerJs(<<<JS
$(document).on('change', '#customers-is_social_security', function(){
    $('.js-social-field').toggle($(this).val() == 1);
}).on('change', '#customers-do_have_any_property', function(){
    $('.js-real-estate-section').toggle($(this).val() == 1);
});

(function(){
    var isNew = $jsIsNew;
    if (isNew) return;

    var warnFields = {
        'customers-name': 'اسم العميل',
        'customers-id_number': 'الرقم الوطني',
        'customers-birth_date': 'تاريخ الميلاد',
        'customers-primary_phone_number': 'الهاتف الرئيسي',
        'customers-city': 'مدينة الولادة',
        'customers-citizen': 'الجنسية',
        'customers-job_title': 'المسمى الوظيفي',
    };

    var origVals = {};
    $.each(warnFields, function(id) {
        var el = document.getElementById(id);
        if (el) origVals[id] = $(el).val() || '';
    });

    $('#dynamic-form').on('beforeSubmit', function(){
        var missing = [];
        $.each(warnFields, function(id, label) {
            var el = document.getElementById(id);
            if (el && !$(el).val()) missing.push(label);
        });
        if (missing.length) {
            var msg = 'تنبيه: الحقول التالية فارغة ويُفضّل تعبئتها في أقرب وقت:\\n- '
                + missing.join('\\n- ')
                + '\\n\\nهل تريد المتابعة بالحفظ؟';
            return confirm(msg);
        }
        return true;
    });

    $.each(warnFields, function(id, label) {
        var el = document.getElementById(id);
        if (!el) return;
        $(el).on('change', function() {
            var newVal = $(this).val() || '';
            var oldVal = origVals[id] || '';
            if (oldVal && !newVal) {
                if (!confirm('تنبيه: أنت على وشك حذف "' + label + '"\\nالقيمة الحالية: ' + oldVal + '\\n\\nهل أنت متأكد؟')) {
                    $(this).val(oldVal).trigger('change.select2');
                }
            }
        });
    });
})();
JS
);
?>
