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
use backend\helpers\PhoneInputWidget;
use common\helper\Permissions;

/* جلب القوائم المنسدلة من الكاش - دفعة واحدة */
$cache = Yii::$app->cache;
$p = Yii::$app->params;
$d = $p['time_duration'];
$db = Yii::$app->db;

$jobs = $cache->getOrSet($p['key_jobs'], fn() => $db->createCommand($p['jobs_query'])->queryAll(), $d);
$selectedJobName = '';
if (!$model->isNewRecord && $model->job_title) {
    $selectedJobName = ArrayHelper::getValue(ArrayHelper::map($jobs, 'id', 'name'), $model->job_title, '');
}
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

<div class="customers-form" x-data="{ showSocial: '<?= $model->is_social_security ?>', showRealEstate: '<?= $model->do_have_any_property ?>' }">
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
                <div class="job-title-wrapper">
                    <?= $form->field($model, 'job_title')->dropDownList(
                        $model->job_title && $selectedJobName ? [$model->job_title => $selectedJobName] : [],
                        ['prompt' => '-- جهة العمل --', 'class' => 'form-control', 'id' => 'customers-job_title']
                    )->label('المسمى الوظيفي') ?>
                    <button type="button" class="btn-add-job" id="btn-add-job-classic" title="إضافة جهة عمل جديدة"><i class="fa fa-plus"></i></button>
                </div>
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
                <?= $form->field($model, 'email')->textInput(['placeholder' => 'example@email.com'])->label('البريد الإلكتروني') ?>
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
                <?= $form->field($model, 'bank_name')->dropDownList(ArrayHelper::map($banks, 'id', 'name'), [
                    'prompt' => '-- اختر البنك --', 'class' => 'form-control',
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
                <?= $form->field($model, 'is_social_security')->dropDownList([0 => 'لا', 1 => 'نعم'], ['prompt' => '--', 'x-model' => 'showSocial'])->label('مشترك بالضمان؟') ?>
            </div>
            <div class="col-md-2 js-social-field" x-show="showSocial == '1'" x-transition x-cloak style="display:<?= (!$isNew && $model->is_social_security == 1) ? 'block' : 'none' ?>">
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
                <?= $form->field($model, 'do_have_any_property')->dropDownList([0 => 'لا', 1 => 'نعم'], ['prompt' => '--', 'x-model' => 'showRealEstate'])->label('يملك عقارات؟') ?>
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
    <div class="js-real-estate-section" x-show="showRealEstate == '1'" x-transition x-cloak style="display:<?= (!$isNew && $model->do_have_any_property == 1) ? 'block' : 'none' ?>">
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

        <?= $form->field($model, 'customer_images')->fileInput(['accept' => 'image/*'])->label('إدارة الصور') ?>
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

    <!-- Quick-Create Job Modal -->
    <div class="modal fade" id="quick-create-job-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content" style="border-radius:12px;overflow:hidden">
                <div class="modal-header" style="background:linear-gradient(135deg,#800020,#a0002a);color:#fff;border:none;padding:16px 20px">
                    <h4 class="modal-title" style="font-weight:700;font-size:16px;margin:0"><i class="fa fa-plus-circle"></i> إضافة جهة عمل جديدة</h4>
                    <button type="button" class="close" data-dismiss="modal" style="color:#fff;opacity:.8;text-shadow:none;font-size:22px">&times;</button>
                </div>
                <div class="modal-body" style="padding:20px">
                    <div class="form-group">
                        <label style="font-weight:600">اسم جهة العمل <span class="text-danger">*</span></label>
                        <input type="text" id="qc-job-name" class="form-control" placeholder="مثال: الجامعة الأردنية" autocomplete="off">
                        <div id="qc-similar-results" style="display:none;margin-top:8px;padding:8px 12px;background:#fff7ed;border:1px solid #fed7aa;border-radius:6px;font-size:12px"></div>
                    </div>
                    <div class="form-group">
                        <label style="font-weight:600">نوع الجهة</label>
                        <select id="qc-job-type" class="form-control"><option value="">-- اختر النوع --</option></select>
                    </div>
                    <div class="form-group">
                        <label style="font-weight:600">رقم الهاتف <span style="color:#999;font-weight:400">(اختياري)</span></label>
                        <input type="text" id="qc-job-phone" class="form-control" placeholder="07XXXXXXXX" dir="ltr">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #eee;padding:12px 20px;display:flex;gap:8px;justify-content:flex-start">
                    <button type="button" class="btn btn-success" id="qc-job-save" style="border-radius:6px;padding:8px 24px;font-weight:600"><i class="fa fa-check"></i> حفظ</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius:6px;padding:8px 18px">إلغاء</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerCss(<<<CSS
.job-title-wrapper { position: relative; }
.job-title-wrapper .form-group { margin-bottom: 0; }
.btn-add-job {
    position: absolute; top: 28px; left: 0;
    width: 34px; height: 34px;
    border: 1px solid #800020; border-radius: 6px;
    background: #fff; color: #800020;
    font-size: 14px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all .2s; z-index: 2;
}
.btn-add-job:hover { background: #800020; color: #fff; transform: scale(1.08); }
.job-title-wrapper .select2-container { width: calc(100% - 42px) !important; }
CSS
);

$searchListUrl = Url::to(['/jobs/jobs/search-list']);
$quickCreateUrl = Url::to(['/jobs/jobs/quick-create']);
$jobTypesUrl = Url::to(['/jobs/jobs/job-types-list']);
$this->registerJs(<<<JSJOB
(function(){
    // Initialize job Select2 with AJAX for real-time data
    setTimeout(function() {
        var \$sel = $('#customers-job_title');
        if (!\$sel.length) return;
        try { \$sel.select2('destroy'); } catch(e) {}
        \$sel.select2({
            theme: 'bootstrap4',
            placeholder: '-- ابحث عن جهة العمل --',
            allowClear: true,
            dir: 'rtl',
            minimumInputLength: 0,
            ajax: {
                url: '$searchListUrl',
                dataType: 'json',
                delay: 250,
                data: function(params) { return {q: params.term}; },
                processResults: function(data) { return data; }
            }
        });
    }, 150);

    var jobTypesLoaded = false;
    $(document).on('click', '.btn-add-job', function() {
        var \$m = $('#quick-create-job-modal');
        if (!\$m.length) { window.open('/jobs/jobs/create', '_blank'); return; }
        \$m.find('#qc-job-name').val('');
        \$m.find('#qc-job-type').val('');
        \$m.find('#qc-job-phone').val('');
        \$m.find('#qc-similar-results').hide().empty();
        \$m.find('#qc-job-save').prop('disabled', false);
        \$m.modal('show');
        if (!jobTypesLoaded) {
            $.getJSON('$jobTypesUrl').done(function(r) {
                if (r.results && r.results.length) {
                    var \$s = $('#qc-job-type');
                    \$s.find('option:not(:first)').remove();
                    $.each(r.results, function(i,t){ \$s.append('<option value="'+t.id+'">'+t.name+'</option>'); });
                    jobTypesLoaded = true;
                }
            });
        }
        setTimeout(function(){ \$m.find('#qc-job-name').focus(); }, 400);
    });
    var simTimer = null;
    $(document).on('input', '#qc-job-name', function() {
        clearTimeout(simTimer);
        var q = $.trim($(this).val());
        if (q.length < 2) { $('#qc-similar-results').hide(); return; }
        simTimer = setTimeout(function() {
            $.getJSON('/jobs/jobs/search-similar', {q:q}).done(function(r) {
                var \$b = $('#qc-similar-results');
                if (r.results && r.results.length) {
                    var h = '<i class="fa fa-exclamation-triangle" style="color:#d97706"></i> <strong>جهات مشابهة:</strong><br>';
                    $.each(r.results, function(i,x){
                        h += '<span style="cursor:pointer;text-decoration:underline;color:#800020" data-id="'+x.id+'" data-name="'+x.name+'" class="qc-pick-existing">'+x.name+'</span>';
                        if (x.city) h += ' <small style="color:#888">('+x.city+')</small>';
                        if (i < r.results.length-1) h += '، ';
                    });
                    \$b.html(h).show();
                } else { \$b.hide(); }
            });
        }, 400);
    });
    $(document).on('click', '.qc-pick-existing', function() {
        var id = $(this).data('id'), name = $(this).data('name');
        var opt = new Option(name, id, true, true);
        $('#customers-job_title').append(opt).trigger('change');
        $('#quick-create-job-modal').modal('hide');
    });
    $(document).on('click', '#qc-job-save', function() {
        var \$btn = $(this), name = $.trim($('#qc-job-name').val());
        if (!name) { $('#qc-job-name').focus(); return; }
        \$btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري الحفظ...');
        $.ajax({
            url: '$quickCreateUrl', method: 'POST', dataType: 'json',
            data: { name:name, job_type:$('#qc-job-type').val()||'', phone:$('#qc-job-phone').val()||'', '_csrf-backend':$('input[name="_csrf-backend"]').val() },
            success: function(resp) {
                \$btn.prop('disabled', false).html('<i class="fa fa-check"></i> حفظ');
                if (resp.success) {
                    var opt = new Option(resp.text, resp.id, true, true);
                    $('#customers-job_title').append(opt).trigger('change');
                    $('#quick-create-job-modal').modal('hide');
                } else { alert(resp.message || 'حدث خطأ'); }
            },
            error: function() { \$btn.prop('disabled', false).html('<i class="fa fa-check"></i> حفظ'); alert('خطأ في الاتصال'); }
        });
    });
})();
JSJOB
);

$jsIsNew = $isNew ? 'true' : 'false';
$this->registerJs(<<<JS
/* Alpine.js handles show/hide for social security & real estate fields via x-show */

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
