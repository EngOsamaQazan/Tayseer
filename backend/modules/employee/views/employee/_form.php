<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use common\models\User;
use backend\modules\location\models\Location;
use backend\modules\designation\models\Designation;
use backend\modules\department\models\Department;
use common\models\Countries;

/** @var yii\web\View $this */
/** @var backend\models\Employee $model */
/** @var yii\widgets\ActiveForm $form */
/** @var array $employeeAttachments */
/** @var int $id */

$avatarSrc = !empty($model->profileAvatar) ? $model->profileAvatar->path : '';
$fullName = trim(implode(' ', array_filter([$model->name ?? '', $model->middle_name ?? '', $model->last_name ?? ''])));

$departmentName = '';
try {
    $dept = Department::findOne($model->department);
    $departmentName = $dept ? $dept->title : '—';
} catch (\Exception $e) { $departmentName = '—'; }

$jobTitleName = '';
try {
    $desig = Designation::findOne($model->job_title);
    $jobTitleName = $desig ? $desig->title : '—';
} catch (\Exception $e) { $jobTitleName = '—'; }

$locationName = '';
try {
    $loc = Location::findOne($model->location);
    $locationName = $loc ? $loc->location : '—';
} catch (\Exception $e) { $locationName = '—'; }

$reportingName = '';
try {
    $mgr = User::findOne($model->reporting_to);
    $reportingName = $mgr ? $mgr->username : '—';
} catch (\Exception $e) { $reportingName = '—'; }

$nationalityName = '';
try {
    $country = Countries::findOne($model->nationality);
    $nationalityName = $country ? $country->country_name : '—';
} catch (\Exception $e) { $nationalityName = '—'; }

$genderLabel = $model->gender === 'Male' ? 'ذكر' : ($model->gender === 'Female' ? 'أنثى' : '—');
$statusLabel = $model->employee_type === 'Active' ? 'نشط' : 'موقوف';
$empStatusLabel = $model->employee_status === 'Full_time' ? 'دوام كامل' : 'دوام جزئي';
$maritalLabel = $model->marital_status === 'married' ? 'متزوج' : ($model->marital_status === 'single' ? 'أعزب' : '—');
?>

<div class="emp-form-wrapper">

    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="emp-alert emp-alert-success">
            <i class="fa fa-check-circle"></i>
            <span><?= Yii::$app->session->getFlash('success') ?></span>
        </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════
         Profile Card
         ═══════════════════════════════════════ -->
    <?php $form = ActiveForm::begin([
        'options' => ['enctype' => 'multipart/form-data'],
        'fieldConfig' => [
            'template' => '<div class="form-group emp-field">{label}{input}{error}</div>',
            'labelOptions' => ['class' => 'emp-form-label'],
            'inputOptions' => ['class' => 'form-control emp-form-input'],
            'errorOptions' => ['class' => 'help-block emp-field-error'],
        ],
    ]); ?>

    <!-- Hidden fields: admin-managed values preserved for validation -->
    <?= Html::activeHiddenInput($model, 'name') ?>
    <?= Html::activeHiddenInput($model, 'middle_name') ?>
    <?= Html::activeHiddenInput($model, 'last_name') ?>
    <?= Html::activeHiddenInput($model, 'username') ?>
    <?= Html::activeHiddenInput($model, 'employee_type') ?>
    <?= Html::activeHiddenInput($model, 'employee_status') ?>
    <?= Html::activeHiddenInput($model, 'gender') ?>
    <?= Html::activeHiddenInput($model, 'location', ['value' => $model->location ?: '']) ?>
    <?= Html::activeHiddenInput($model, 'department', ['value' => $model->department ?: '']) ?>
    <?= Html::activeHiddenInput($model, 'job_title', ['value' => $model->job_title ?: '']) ?>
    <?= Html::activeHiddenInput($model, 'reporting_to', ['value' => $model->reporting_to ?: '']) ?>
    <?= Html::activeHiddenInput($model, 'nationality', ['value' => $model->nationality ?: '']) ?>
    <?= Html::activeHiddenInput($model, 'date_of_hire') ?>
    <?= Html::activeHiddenInput($model, 'credental_sms_send', ['value' => $model->credental_sms_send ?: 0]) ?>
    <?= Html::activeHiddenInput($model, 'credental_email_send', ['value' => $model->credental_email_send ?: 0]) ?>

    <?php if ($model->hasErrors()): ?>
        <div class="emp-alert emp-alert-danger">
            <i class="fa fa-exclamation-triangle"></i>
            <div>
                <strong>يرجى تصحيح الأخطاء التالية:</strong>
                <ul>
                    <?php foreach ($model->getErrors() as $errs): ?>
                        <?php foreach ($errs as $err): ?>
                            <li><?= Html::encode($err) ?></li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div class="emp-profile-card">
        <div class="emp-avatar-section">
            <div class="emp-avatar-frame" id="emp-avatar-preview">
                <?php if (!empty($avatarSrc)): ?>
                    <?= Html::img(Url::to([$avatarSrc]), ['class' => 'emp-avatar-img t-zoomable', 'alt' => Html::encode($fullName), 'style' => 'cursor:zoom-in']) ?>
                <?php else: ?>
                    <div class="emp-avatar-placeholder">
                        <i class="fa fa-user"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="emp-avatar-info">
                <h3 class="emp-avatar-name"><?= Html::encode($fullName) ?></h3>
                <span class="emp-avatar-role"><?= Html::encode($jobTitleName) ?></span>
                <div class="emp-avatar-meta">
                    <span class="emp-avatar-badge emp-badge-<?= $model->employee_type === 'Active' ? 'active' : 'suspended' ?>">
                        <i class="fa fa-circle"></i>
                        <?= $statusLabel ?>
                    </span>
                    <span class="emp-avatar-dept">
                        <i class="fa fa-building-o"></i>
                        <?= Html::encode($departmentName) ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="emp-avatar-upload">
            <label class="emp-upload-btn" for="emp-avatar-input">
                <i class="fa fa-camera"></i>
                تغيير الصورة
            </label>
            <div class="emp-file-hidden-wrap">
                <?= Html::activeFileInput($model, 'profile_avatar_file', [
                    'id' => 'emp-avatar-input',
                    'accept' => 'image/*',
                ]) ?>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         Section 1: Contact Information (Editable)
         ═══════════════════════════════════════ -->
    <div class="emp-section">
        <div class="emp-section-header">
            <i class="fa fa-phone"></i>
            <span>معلومات الاتصال</span>
            <span class="emp-section-tag emp-tag-editable"><i class="fa fa-pencil"></i> قابل للتعديل</span>
        </div>
        <div class="emp-section-body">
            <div class="row">
                <div class="col-sm-6">
                    <?= $form->field($model, 'email')->textInput([
                        'maxlength' => true,
                        'type' => 'email',
                        'dir' => 'ltr',
                        'style' => 'text-align:left',
                    ])->label('البريد الإلكتروني') ?>
                </div>
                <div class="col-sm-6">
                    <?= $form->field($model, 'mobile')->textInput([
                        'maxlength' => true,
                        'dir' => 'ltr',
                        'style' => 'text-align:left',
                    ])->label('رقم الجوال') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         Section 2: Personal Information (Editable)
         ═══════════════════════════════════════ -->
    <div class="emp-section">
        <div class="emp-section-header">
            <i class="fa fa-user"></i>
            <span>بيانات شخصية</span>
            <span class="emp-section-tag emp-tag-editable"><i class="fa fa-pencil"></i> قابل للتعديل</span>
        </div>
        <div class="emp-section-body">
            <div class="row">
                <div class="col-sm-6">
                    <?= $form->field($model, 'marital_status')->dropDownList(
                        ['single' => 'أعزب', 'married' => 'متزوج'],
                        ['prompt' => '— اختر الحالة —']
                    )->label('الحالة الاجتماعية') ?>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <?= $form->field($model, 'bio')->textarea([
                        'rows' => 3,
                        'class' => 'form-control emp-form-textarea',
                        'placeholder' => 'اكتب نبذة مختصرة عن نفسك...',
                    ])->label('نبذة تعريفية') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         Section 3: Change Password (Editable)
         ═══════════════════════════════════════ -->
    <div class="emp-section">
        <div class="emp-section-header">
            <i class="fa fa-lock"></i>
            <span>تغيير كلمة المرور</span>
            <span class="emp-section-tag emp-tag-editable"><i class="fa fa-pencil"></i> قابل للتعديل</span>
        </div>
        <div class="emp-section-body">
            <div class="row">
                <div class="col-sm-6">
                    <?= $form->field($model, 'password_hash')->passwordInput([
                        'maxlength' => true,
                        'value' => '',
                        'placeholder' => 'اتركه فارغاً للإبقاء على كلمة المرور الحالية',
                        'autocomplete' => 'new-password',
                    ])->label('كلمة المرور الجديدة') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         Submit
         ═══════════════════════════════════════ -->
    <?php if (!Yii::$app->request->isAjax): ?>
        <div class="emp-form-actions">
            <?= Html::submitButton(
                '<i class="fa fa-check-circle"></i> حفظ التغييرات',
                ['class' => 'btn emp-btn-primary']
            ) ?>
            <?= Html::a(
                '<i class="fa fa-arrow-right"></i> رجوع',
                ['index'],
                ['class' => 'btn emp-btn-secondary']
            ) ?>
        </div>
    <?php endif; ?>

    <?php ActiveForm::end(); ?>

    <!-- ═══════════════════════════════════════
         Section 4: Employment Information (Read-Only)
         ═══════════════════════════════════════ -->
    <div class="emp-section emp-section-readonly">
        <div class="emp-section-header">
            <i class="fa fa-briefcase"></i>
            <span>البيانات الوظيفية</span>
            <span class="emp-section-tag emp-tag-readonly"><i class="fa fa-lock"></i> تُدار من الموارد البشرية</span>
        </div>
        <div class="emp-section-body">
            <div class="emp-info-grid">
                <div class="emp-info-item">
                    <div class="emp-info-icon"><i class="fa fa-id-badge"></i></div>
                    <div class="emp-info-content">
                        <span class="emp-info-label">الاسم الكامل</span>
                        <span class="emp-info-value"><?= Html::encode($fullName) ?></span>
                    </div>
                </div>
                <div class="emp-info-item">
                    <div class="emp-info-icon"><i class="fa fa-tag"></i></div>
                    <div class="emp-info-content">
                        <span class="emp-info-label">المسمى الوظيفي</span>
                        <span class="emp-info-value"><?= Html::encode($jobTitleName) ?></span>
                    </div>
                </div>
                <div class="emp-info-item">
                    <div class="emp-info-icon"><i class="fa fa-sitemap"></i></div>
                    <div class="emp-info-content">
                        <span class="emp-info-label">القسم</span>
                        <span class="emp-info-value"><?= Html::encode($departmentName) ?></span>
                    </div>
                </div>
                <div class="emp-info-item">
                    <div class="emp-info-icon"><i class="fa fa-map-marker"></i></div>
                    <div class="emp-info-content">
                        <span class="emp-info-label">الموقع</span>
                        <span class="emp-info-value"><?= Html::encode($locationName) ?></span>
                    </div>
                </div>
                <div class="emp-info-item">
                    <div class="emp-info-icon"><i class="fa fa-user-circle-o"></i></div>
                    <div class="emp-info-content">
                        <span class="emp-info-label">المدير المباشر</span>
                        <span class="emp-info-value"><?= Html::encode($reportingName) ?></span>
                    </div>
                </div>
                <div class="emp-info-item">
                    <div class="emp-info-icon"><i class="fa fa-clock-o"></i></div>
                    <div class="emp-info-content">
                        <span class="emp-info-label">نوع الدوام</span>
                        <span class="emp-info-value"><?= Html::encode($empStatusLabel) ?></span>
                    </div>
                </div>
                <div class="emp-info-item">
                    <div class="emp-info-icon"><i class="fa fa-calendar"></i></div>
                    <div class="emp-info-content">
                        <span class="emp-info-label">تاريخ التعيين</span>
                        <span class="emp-info-value"><?= Html::encode($model->date_of_hire ?: '—') ?></span>
                    </div>
                </div>
                <div class="emp-info-item">
                    <div class="emp-info-icon"><i class="fa fa-globe"></i></div>
                    <div class="emp-info-content">
                        <span class="emp-info-label">الجنسية</span>
                        <span class="emp-info-value"><?= Html::encode($nationalityName) ?></span>
                    </div>
                </div>
                <div class="emp-info-item">
                    <div class="emp-info-icon"><i class="fa fa-venus-mars"></i></div>
                    <div class="emp-info-content">
                        <span class="emp-info-label">الجنس</span>
                        <span class="emp-info-value"><?= Html::encode($genderLabel) ?></span>
                    </div>
                </div>
                <div class="emp-info-item">
                    <div class="emp-info-icon"><i class="fa fa-at"></i></div>
                    <div class="emp-info-content">
                        <span class="emp-info-label">اسم المستخدم</span>
                        <span class="emp-info-value" dir="ltr"><?= Html::encode($model->username) ?></span>
                    </div>
                </div>
            </div>
            <div class="emp-info-notice">
                <i class="fa fa-info-circle"></i>
                لتحديث البيانات الوظيفية، يرجى التواصل مع قسم الموارد البشرية.
            </div>
        </div>
    </div>

</div>

<?php
$js = <<<JS

// Avatar preview on file change
document.getElementById('emp-avatar-input').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (file && file.type.startsWith('image/')) {
        var reader = new FileReader();
        reader.onload = function(ev) {
            var frame = document.getElementById('emp-avatar-preview');
            frame.innerHTML = '<img src="' + ev.target.result + '" class="emp-avatar-img t-zoomable" style="cursor:zoom-in" alt="Preview">';
        };
        reader.readAsDataURL(file);
    }
});

JS;

$css = <<<CSS

/* ─── Alerts ─── */
.emp-alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-size: 13px;
}
.emp-alert-danger {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}
.emp-alert-danger i { font-size: 18px; margin-top: 2px; color: #dc2626; }
.emp-alert-success {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}
.emp-alert-success i { font-size: 18px; color: #059669; }
.emp-alert ul { margin: 6px 0 0; padding-right: 18px; }

/* ─── Profile Card ─── */
.emp-profile-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 28px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.04);
}
.emp-avatar-section {
    display: flex;
    align-items: center;
    gap: 20px;
}
.emp-avatar-frame {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #e2e8f0;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
}
.emp-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.emp-avatar-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
    color: #94a3b8;
    font-size: 32px;
}
.emp-avatar-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.emp-avatar-name {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}
.emp-avatar-role {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
}
.emp-avatar-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 4px;
}
.emp-avatar-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 10px;
    border-radius: 20px;
}
.emp-avatar-badge i { font-size: 6px; }
.emp-badge-active { background: #ecfdf5; color: #059669; }
.emp-badge-suspended { background: #fef2f2; color: #dc2626; }
.emp-avatar-dept {
    font-size: 12px;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 4px;
}
.emp-avatar-upload { flex-shrink: 0; }
.emp-upload-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: #f8fafc;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    color: #475569;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.emp-upload-btn:hover {
    background: #f1f5f9;
    border-color: #800020;
    color: #800020;
}
.emp-file-hidden-wrap {
    position: absolute;
    width: 1px; height: 1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0; padding: 0; margin: -1px;
}

/* ─── Sections ─── */
.emp-section {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 20px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
}
.emp-section-readonly {
    background: #fafbfc;
}
.emp-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
}
.emp-section-header i:first-child {
    color: #800020;
    font-size: 16px;
    width: 20px;
    text-align: center;
}
.emp-section-body {
    padding: 20px;
}

/* ─── Section Tags ─── */
.emp-section-tag {
    margin-right: auto;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.emp-tag-editable {
    background: #ecfdf5;
    color: #059669;
}
.emp-tag-readonly {
    background: #f1f5f9;
    color: #64748b;
}

/* ─── Form Fields ─── */
.emp-form-label {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
    display: block;
}
.emp-form-input {
    border-radius: 8px !important;
    border: 1px solid #d1d5db !important;
    font-size: 13px !important;
    padding: 8px 12px !important;
    height: auto !important;
    min-height: 38px;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.emp-form-input:focus {
    border-color: #800020 !important;
    box-shadow: 0 0 0 3px rgba(128, 0, 32, 0.08) !important;
}
.emp-form-textarea {
    border-radius: 8px !important;
    border: 1px solid #d1d5db !important;
    font-size: 13px !important;
    padding: 10px 14px !important;
    resize: vertical;
    min-height: 80px;
}
.emp-form-textarea:focus {
    border-color: #800020 !important;
    box-shadow: 0 0 0 3px rgba(128, 0, 32, 0.08) !important;
}
.emp-field { margin-bottom: 16px; }
.emp-field-error { font-size: 11px; color: #dc2626; margin-top: 4px; }

/* ─── Read-Only Info Grid ─── */
.emp-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0;
}
.emp-info-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s;
}
.emp-info-item:hover {
    background: #f8fafc;
}
.emp-info-item:nth-last-child(-n+2) {
    border-bottom: none;
}
.emp-info-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: #f1f5f9;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}
.emp-info-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}
.emp-info-label {
    font-size: 11px;
    color: #94a3b8;
    font-weight: 600;
}
.emp-info-value {
    font-size: 14px;
    color: #1e293b;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.emp-info-notice {
    margin-top: 16px;
    padding: 12px 16px;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 8px;
    font-size: 12px;
    color: #92400e;
    display: flex;
    align-items: center;
    gap: 8px;
}
.emp-info-notice i {
    color: #d97706;
    font-size: 14px;
    flex-shrink: 0;
}

/* ─── Form Actions ─── */
.emp-form-actions {
    display: flex;
    gap: 10px;
    padding: 8px 0 24px;
}
.emp-btn-primary {
    padding: 10px 28px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 10px;
    background: #800020 !important;
    color: #fff !important;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}
.emp-btn-primary:hover {
    background: #6b001a !important;
    color: #fff !important;
    box-shadow: 0 4px 12px rgba(128, 0, 32, 0.2);
}
.emp-btn-secondary {
    padding: 10px 24px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 10px;
    background: #fff !important;
    color: #475569 !important;
    border: 1px solid #d1d5db;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}
.emp-btn-secondary:hover {
    background: #f8fafc !important;
    border-color: #94a3b8;
    color: #1e293b !important;
}

/* ─── Responsive ─── */
@media (max-width: 768px) {
    .emp-profile-card {
        flex-direction: column;
        text-align: center;
        gap: 16px;
        padding: 20px;
    }
    .emp-avatar-section { flex-direction: column; }
    .emp-avatar-meta { justify-content: center; }
    .emp-section-body { padding: 16px; }
    .emp-info-grid { grid-template-columns: 1fr; }
    .emp-info-item:last-child { border-bottom: none; }
    .emp-info-item:nth-last-child(2) { border-bottom: 1px solid #f1f5f9; }
    .emp-form-actions { flex-direction: column; }
    .emp-form-actions .btn { width: 100%; justify-content: center; }
    .emp-section-tag { display: none; }
}

CSS;

$this->registerCss($css);
$this->registerJs($js, \yii\web\View::POS_END);
?>
