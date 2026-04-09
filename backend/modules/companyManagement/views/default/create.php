<?php
/**
 * @var yii\web\View $this
 * @var backend\modules\companyManagement\models\CompanyForm $model
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'إضافة منشأة جديدة';
?>

<div class="company-create">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fa fa-plus-circle"></i> إضافة منشأة جديدة</h5>
        </div>
        <div class="card-body">
            <?php $form = ActiveForm::begin(['id' => 'company-form']); ?>

            <div class="row">
                <div class="col-md-6">
                    <fieldset>
                        <legend><i class="fa fa-building"></i> بيانات الشركة</legend>
                        <?= $form->field($model, 'name_ar')->textInput(['placeholder' => 'مثال: عالم المجد للتقسيط']) ?>
                        <?= $form->field($model, 'name_en')->textInput(['placeholder' => 'Optional: Alam Al-Majd']) ?>
                        <?= $form->field($model, 'slug')->textInput([
                            'placeholder' => 'مثال: majd',
                            'dir' => 'ltr',
                            'style' => 'text-align:left',
                        ])->hint('سيُستخدم كمعرّف فريد. النطاق سيكون: <strong>{slug}.aqssat.co</strong>') ?>
                    </fieldset>
                </div>

                <div class="col-md-6">
                    <fieldset>
                        <legend><i class="fa fa-comment"></i> إعدادات SMS</legend>
                        <?= $form->field($model, 'sms_sender')->textInput(['placeholder' => 'اسم المرسل', 'dir' => 'ltr']) ?>
                        <?= $form->field($model, 'sms_user')->textInput(['placeholder' => 'اسم المستخدم', 'dir' => 'ltr']) ?>
                        <?= $form->field($model, 'sms_pass')->passwordInput(['placeholder' => 'كلمة المرور']) ?>
                    </fieldset>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <fieldset>
                        <legend><i class="fa fa-user-shield"></i> المدير الرئيسي</legend>
                        <?= $form->field($model, 'admin_username')->textInput(['dir' => 'ltr', 'value' => 'admin']) ?>
                        <?= $form->field($model, 'admin_email')->textInput(['dir' => 'ltr', 'placeholder' => 'admin@company.com', 'type' => 'email']) ?>
                        <?= $form->field($model, 'admin_password')->passwordInput(['placeholder' => 'كلمة مرور قوية']) ?>
                    </fieldset>
                </div>

                <div class="col-md-6">
                    <fieldset>
                        <legend><i class="fa fa-info-circle"></i> معاينة</legend>
                        <div class="alert alert-info">
                            <p><strong>النطاق:</strong> <span id="preview-domain" dir="ltr">—.aqssat.co</span></p>
                            <p><strong>قاعدة البيانات:</strong> <span id="preview-db" dir="ltr">tayseer_—</span></p>
                            <p class="mb-0"><strong>بعد الإنشاء:</strong> ستتمكن من بدء التجهيز التلقائي (DNS → قاعدة بيانات → سيرفر → SSL → صلاحيات)</p>
                        </div>
                    </fieldset>
                </div>
            </div>

            <hr>
            <div class="d-flex justify-content-between">
                <?= Html::a('<i class="fa fa-arrow-right"></i> العودة', ['index'], ['class' => 'btn btn-secondary']) ?>
                <?= Html::submitButton('<i class="fa fa-check"></i> إنشاء الشركة', ['class' => 'btn btn-success']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<?php
$js = <<<JS
$('#companyform-slug').on('input', function() {
    var slug = $(this).val().toLowerCase().replace(/[^a-z0-9_]/g, '');
    $(this).val(slug);
    $('#preview-domain').text(slug ? slug + '.aqssat.co' : '—.aqssat.co');
    $('#preview-db').text(slug ? 'tayseer_' + slug : 'tayseer_—');
});
JS;
$this->registerJs($js);
?>
