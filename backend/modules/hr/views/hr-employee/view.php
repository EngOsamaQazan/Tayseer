<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  ملف الموظف — عرض تفصيلي بالتبويبات
 *  ──────────────────────────────────────
 *  البيانات الشخصية | جهات الطوارئ | المستندات | الحضور | الراتب | الميداني
 * ═══════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\helpers\Url;
use common\helper\Permissions;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var backend\modules\hr\models\HrEmployeeExtended|null $extended */
/** @var backend\modules\hr\models\HrEmployeeDocument[] $documents */
/** @var backend\modules\hr\models\HrEmergencyContact[] $emergencyContacts */
/** @var array $attendanceSummary */
/** @var backend\modules\hr\models\HrEmployeeSalary[] $salaryComponents */
/** @var backend\modules\hr\models\HrAnnualIncrement[] $increments */

$this->title = 'ملف الموظف — ' . ($user->name ?: $user->username);

$increments = $increments ?? [];

/* ─── تسجيل CSS ─── */
$this->registerCssFile(Yii::getAlias('@web') . '/css/hr.css', ['depends' => ['yii\web\YiiAsset']]);

/* ─── صورة افتراضية ─── */
$defaultAvatar = Yii::getAlias('@web') . '/img/default-avatar.png';
$avatar = !empty($user->avatar) ? $user->avatar : $defaultAvatar;

/* ─── خريطة الحالات ─── */
$statusMap = [
    'Active'     => ['label' => 'نشط',       'class' => 'hr-status--active'],
    'On_Leave'   => ['label' => 'في إجازة',   'class' => 'hr-status--leave'],
    'Suspended'  => ['label' => 'موقوف',      'class' => 'hr-status--suspended'],
    'Terminated' => ['label' => 'منتهي',      'class' => 'hr-status--terminated'],
    'Resigned'   => ['label' => 'مستقيل',     'class' => 'hr-status--resigned'],
    'Probation'  => ['label' => 'تحت التجربة', 'class' => 'hr-status--probation'],
];

$contractTypeMap = [
    'permanent' => 'دائم',
    'contract'  => 'مؤقت/عقد',
    'probation' => 'تحت التجربة',
    'freelance'  => 'عمل حر',
];

$bloodGroupMap = [
    'A+'  => 'A+',
    'A-'  => 'A-',
    'B+'  => 'B+',
    'B-'  => 'B-',
    'AB+' => 'AB+',
    'AB-' => 'AB-',
    'O+'  => 'O+',
    'O-'  => 'O-',
];

/* ─── Helper: safe attribute access ─── */
$ext = function ($attr, $default = '—') use ($extended) {
    if ($extended === null) return $default;
    $val = $extended->$attr ?? null;
    return ($val !== null && $val !== '') ? $val : $default;
};

$userAttr = function ($attr, $default = '—') use ($user) {
    $val = $user->$attr ?? null;
    return ($val !== null && $val !== '') ? $val : $default;
};

$statusInfo = $statusMap[$user->employee_status ?? ''] ?? ['label' => $user->employee_status ?? '—', 'class' => ''];

/* ─── Department & Designation names ─── */
$departmentName = '—';
$designationName = '—';
if (!empty($user->department)) {
    try {
        $dept = Yii::$app->db->createCommand("SELECT title FROM {{%department}} WHERE id = :id", [':id' => $user->department])->queryScalar();
        if ($dept) $departmentName = $dept;
    } catch (\Exception $e) {}
}
if (!empty($user->job_title)) {
    try {
        $desig = Yii::$app->db->createCommand("SELECT title FROM {{%designation}} WHERE id = :id", [':id' => $user->job_title])->queryScalar();
        if ($desig) $designationName = $desig;
    } catch (\Exception $e) {}
}
?>

<div class="hr-page">

    <!-- ╔═══════════════════════════════════════╗
         ║  زر العودة                            ║
         ╚═══════════════════════════════════════╝ -->
    <div style="margin-bottom:16px;">
        <?= Html::a('<i class="fa fa-arrow-right"></i> العودة إلى سجل الموظفين', ['index'], ['class' => 'btn btn-default btn-sm', 'style' => 'border-radius:8px']) ?>
    </div>

    <!-- ╔═══════════════════════════════════════╗
         ║  بطاقة رأس الملف                     ║
         ╚═══════════════════════════════════════╝ -->
    <div class="hr-profile-header">
        <div class="hr-profile-avatar-wrap">
            <img class="hr-profile-avatar" src="<?= Html::encode($avatar) ?>"
                 onerror="this.src='<?= $defaultAvatar ?>'" alt="">
            <span class="hr-profile-status-dot <?= $statusInfo['class'] ?>"></span>
        </div>
        <div class="hr-profile-info">
            <h2 class="hr-profile-name"><?= Html::encode($user->name ?: $user->username) ?></h2>
            <div class="hr-profile-meta">
                <?php if ($extended && $extended->employee_code): ?>
                    <span class="hr-profile-badge hr-profile-badge--code">
                        <i class="fa fa-id-badge"></i>
                        <?= Html::encode($extended->employee_code) ?>
                    </span>
                <?php endif ?>
                <span class="hr-profile-badge hr-profile-badge--dept">
                    <i class="fa fa-building"></i>
                    <?= Html::encode($departmentName) ?>
                </span>
                <span class="hr-profile-badge hr-profile-badge--desig">
                    <i class="fa fa-briefcase"></i>
                    <?= Html::encode($designationName) ?>
                </span>
                <span class="hr-profile-badge <?= $statusInfo['class'] ?>">
                    <?= Html::encode($statusInfo['label']) ?>
                </span>
            </div>
            <div class="hr-profile-contact">
                <?php if (!empty($user->email)): ?>
                    <span><i class="fa fa-envelope-o"></i> <?= Html::encode($user->email) ?></span>
                <?php endif ?>
                <?php if (!empty($user->mobile)): ?>
                    <span><i class="fa fa-phone"></i> <?= Html::encode($user->mobile) ?></span>
                <?php endif ?>
                <?php if (!empty($user->date_of_hire)): ?>
                    <span><i class="fa fa-calendar"></i> تاريخ التعيين: <?= Html::encode($user->date_of_hire) ?></span>
                <?php endif ?>
            </div>
        </div>
        <div class="hr-profile-actions">
            <?php if ($extended): ?>
                <?php if (Permissions::can(Permissions::EMP_UPDATE)): ?>
                <?= Html::a('<i class="fa fa-pencil"></i> تعديل', ['update', 'id' => $extended->id], [
                    'class' => 'btn hr-btn-primary btn-sm',
                ]) ?>
                <?php endif ?>
            <?php else: ?>
                <?php if (Permissions::can(Permissions::EMP_CREATE)): ?>
                <?= Html::a('<i class="fa fa-plus"></i> إنشاء ملف موسع', ['create', 'user_id' => $user->id], [
                    'class' => 'btn hr-btn-primary btn-sm',
                ]) ?>
                <?php endif ?>
            <?php endif ?>
        </div>
    </div>

    <!-- ╔═══════════════════════════════════════╗
         ║  تبويبات المحتوى                      ║
         ╚═══════════════════════════════════════╝ -->
    <div class="hr-tabs-wrapper">
        <ul class="nav nav-tabs hr-nav-tabs" role="tablist">
            <li role="presentation" class="active">
                <a href="#tab-personal" aria-controls="tab-personal" role="tab" data-toggle="tab">
                    <i class="fa fa-user"></i> البيانات الشخصية
                </a>
            </li>
            <li role="presentation">
                <a href="#tab-emergency" aria-controls="tab-emergency" role="tab" data-toggle="tab">
                    <i class="fa fa-phone-square"></i> جهات الطوارئ
                    <?php if (!empty($emergencyContacts)): ?>
                        <span class="badge hr-tab-badge"><?= count($emergencyContacts) ?></span>
                    <?php endif ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#tab-documents" aria-controls="tab-documents" role="tab" data-toggle="tab">
                    <i class="fa fa-file-text"></i> المستندات
                    <?php if (!empty($documents)): ?>
                        <span class="badge hr-tab-badge"><?= count($documents) ?></span>
                    <?php endif ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#tab-attendance" aria-controls="tab-attendance" role="tab" data-toggle="tab">
                    <i class="fa fa-clock-o"></i> الحضور
                </a>
            </li>
            <li role="presentation">
                <a href="#tab-salary" aria-controls="tab-salary" role="tab" data-toggle="tab">
                    <i class="fa fa-money"></i> الراتب
                    <?php if (!empty($salaryComponents)): ?>
                        <span class="badge hr-tab-badge"><?= count($salaryComponents) ?></span>
                    <?php endif ?>
                </a>
            </li>
            <li role="presentation">
                <a href="#tab-increments" aria-controls="tab-increments" role="tab" data-toggle="tab">
                    <i class="fa fa-line-chart"></i> العلاوات السنوية
                    <?php if (!empty($increments)): ?>
                        <span class="badge hr-tab-badge"><?= count($increments) ?></span>
                    <?php endif ?>
                </a>
            </li>
            <?php if ($extended && $extended->is_field_staff): ?>
            <li role="presentation">
                <a href="#tab-field" aria-controls="tab-field" role="tab" data-toggle="tab">
                    <i class="fa fa-map-marker"></i> الميداني
                </a>
            </li>
            <?php endif ?>
        </ul>

        <div class="tab-content hr-tab-content">

            <!-- ═════════════════════════════════
                 تبويب: البيانات الشخصية
                 ═════════════════════════════════ -->
            <div role="tabpanel" class="tab-pane fade in active" id="tab-personal">
                <?php if ($extended === null): ?>
                    <div class="hr-empty-state">
                        <i class="fa fa-info-circle"></i>
                        <p>لا توجد بيانات موسعة لهذا الموظف بعد.</p>
                        <?php if (Permissions::can(Permissions::EMP_CREATE)): ?>
                        <?= Html::a('<i class="fa fa-plus"></i> إنشاء ملف موسع', ['create', 'user_id' => $user->id], ['class' => 'btn hr-btn-primary btn-sm']) ?>
                        <?php endif ?>
                    </div>
                <?php else: ?>
                    <div class="hr-info-grid">
                        <div class="hr-info-section">
                            <h4 class="hr-info-section-title"><i class="fa fa-id-card"></i> بيانات الهوية</h4>
                            <table class="table hr-info-table">
                                <tr>
                                    <th>رقم الهوية</th>
                                    <td><?= Html::encode($ext('national_id')) ?></td>
                                    <th>تاريخ الميلاد</th>
                                    <td><?= Html::encode($ext('date_of_birth')) ?></td>
                                </tr>
                                <tr>
                                    <th>فصيلة الدم</th>
                                    <td colspan="3">
                                        <?php
                                        $bg = $ext('blood_type');
                                        echo ($bg !== '—')
                                            ? '<span class="hr-badge hr-badge--blood">' . Html::encode($bg) . '</span>'
                                            : '—';
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="hr-info-section">
                            <h4 class="hr-info-section-title"><i class="fa fa-file-text-o"></i> بيانات العقد</h4>
                            <table class="table hr-info-table">
                                <tr>
                                    <th>نوع العقد</th>
                                    <td colspan="3">
                                        <?php
                                        $ct = $extended->contract_type ?? null;
                                        echo $ct ? Html::encode($contractTypeMap[$ct] ?? $ct) : '—';
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>بداية العقد</th>
                                    <td><?= Html::encode($ext('contract_start')) ?></td>
                                    <th>نهاية العقد</th>
                                    <td>
                                        <?php
                                        $cEnd = $ext('contract_end');
                                        echo Html::encode($cEnd);
                                        if ($cEnd !== '—' && strtotime($cEnd) < time()) {
                                            echo ' <span class="label label-warning" style="font-size:10px">منتهي</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>نهاية فترة التجربة</th>
                                    <td><?= Html::encode($ext('probation_end')) ?></td>
                                    <th>الدرجة الوظيفية</th>
                                    <td><?= Html::encode($extended->grade ? $extended->grade->name : '—') ?></td>
                                </tr>
                            </table>
                        </div>

                        <div class="hr-info-section">
                            <h4 class="hr-info-section-title"><i class="fa fa-university"></i> البيانات المالية</h4>
                            <table class="table hr-info-table">
                                <tr>
                                    <th>الراتب الأساسي</th>
                                    <td>
                                        <?php
                                        $salary = $ext('basic_salary');
                                        echo ($salary !== '—')
                                            ? '<strong style="color:#800020">' . number_format((float)$salary, 2) . '</strong> د.أ'
                                            : '—';
                                        ?>
                                    </td>
                                    <th>رقم الحساب البنكي</th>
                                    <td><?= Html::encode($ext('bank_account_no')) ?></td>
                                </tr>
                                <tr>
                                    <th>رقم الآيبان</th>
                                    <td colspan="3">
                                        <?php
                                        $iban = $ext('iban');
                                        echo ($iban !== '—')
                                            ? '<code class="hr-iban-code">' . Html::encode($iban) . '</code>'
                                            : '—';
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <?php if (!empty($extended->notes)): ?>
                        <div class="hr-info-section">
                            <h4 class="hr-info-section-title"><i class="fa fa-sticky-note-o"></i> ملاحظات</h4>
                            <div class="hr-notes-box">
                                <?= nl2br(Html::encode($extended->notes)) ?>
                            </div>
                        </div>
                        <?php endif ?>
                    </div>
                <?php endif ?>
            </div>

            <!-- ═════════════════════════════════
                 تبويب: جهات الطوارئ
                 ═════════════════════════════════ -->
            <div role="tabpanel" class="tab-pane fade" id="tab-emergency">
                <?php if (empty($emergencyContacts)): ?>
                    <div class="hr-empty-state">
                        <i class="fa fa-phone-square"></i>
                        <p>لا توجد جهات طوارئ مسجلة.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table hr-detail-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>اسم جهة الاتصال</th>
                                    <th>صلة القرابة</th>
                                    <th>رقم الهاتف</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>العنوان</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($emergencyContacts as $i => $contact): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><strong><?= Html::encode($contact->contact_name) ?></strong></td>
                                    <td><?= Html::encode($contact->relationship ?: '—') ?></td>
                                    <td>
                                        <a href="tel:<?= Html::encode($contact->phone) ?>" class="hr-phone-link">
                                            <i class="fa fa-phone"></i>
                                            <?= Html::encode($contact->phone) ?>
                                        </a>
                                    </td>
                                    <td><?= Html::encode($contact->email ?: '—') ?></td>
                                    <td><?= Html::encode($contact->address ?: '—') ?></td>
                                </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </div>

            <!-- ═════════════════════════════════
                 تبويب: المستندات
                 ═════════════════════════════════ -->
            <div role="tabpanel" class="tab-pane fade" id="tab-documents">
                <?php if (empty($documents)): ?>
                    <div class="hr-empty-state">
                        <i class="fa fa-file-text"></i>
                        <p>لا توجد مستندات مرفقة.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table hr-detail-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>نوع المستند</th>
                                    <th>اسم المستند</th>
                                    <th>رقم المستند</th>
                                    <th>تاريخ الإصدار</th>
                                    <th>تاريخ الانتهاء</th>
                                    <th>الحالة</th>
                                    <th>الملف</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $i => $doc): ?>
                                <?php
                                    $isExpired = !empty($doc->expiry_date) && strtotime($doc->expiry_date) < time();
                                    $isExpiringSoon = !empty($doc->expiry_date) && !$isExpired
                                        && strtotime($doc->expiry_date) < strtotime('+30 days');
                                ?>
                                <tr class="<?= $isExpired ? 'hr-row-expired' : ($isExpiringSoon ? 'hr-row-expiring' : '') ?>">
                                    <td><?= $i + 1 ?></td>
                                    <td><span class="hr-badge hr-badge--doc"><?= Html::encode($doc->doc_type) ?></span></td>
                                    <td><strong><?= Html::encode($doc->doc_name) ?></strong></td>
                                    <td><?= Html::encode($doc->doc_number ?: '—') ?></td>
                                    <td><?= Html::encode($doc->issue_date ?: '—') ?></td>
                                    <td><?= Html::encode($doc->expiry_date ?: '—') ?></td>
                                    <td>
                                        <?php if ($isExpired): ?>
                                            <span class="label label-danger">منتهي</span>
                                        <?php elseif ($isExpiringSoon): ?>
                                            <span class="label label-warning">ينتهي قريباً</span>
                                        <?php elseif (!empty($doc->expiry_date)): ?>
                                            <span class="label label-success">ساري</span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($doc->file_path)): ?>
                                            <?= Html::a('<i class="fa fa-download"></i>', $doc->file_path, [
                                                'class' => 'btn btn-xs btn-default',
                                                'target' => '_blank',
                                                'title' => 'تحميل',
                                                'style' => 'border-radius:6px',
                                            ]) ?>
                                        <?php else: ?>
                                            —
                                        <?php endif ?>
                                    </td>
                                </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </div>

            <!-- ═════════════════════════════════
                 تبويب: الحضور
                 ═════════════════════════════════ -->
            <div role="tabpanel" class="tab-pane fade" id="tab-attendance">
                <?php
                $hasSummary = $attendanceSummary && ($attendanceSummary['total_days'] ?? 0) > 0;
                ?>
                <?php if (!$hasSummary): ?>
                    <div class="hr-empty-state">
                        <i class="fa fa-clock-o"></i>
                        <p>لا توجد بيانات حضور للشهر الحالي.</p>
                    </div>
                <?php else: ?>
                    <h4 class="hr-section-subtitle">
                        <i class="fa fa-calendar"></i>
                        ملخص الحضور — <?= date('Y/m') ?>
                    </h4>
                    <div class="hr-attendance-cards">
                        <div class="hr-att-card hr-att-card--total">
                            <div class="hr-att-card-icon"><i class="fa fa-calendar-check-o"></i></div>
                            <div class="hr-att-card-value"><?= (int)($attendanceSummary['total_days'] ?? 0) ?></div>
                            <div class="hr-att-card-label">إجمالي الأيام</div>
                        </div>
                        <div class="hr-att-card hr-att-card--present">
                            <div class="hr-att-card-icon"><i class="fa fa-check-circle"></i></div>
                            <div class="hr-att-card-value"><?= (int)($attendanceSummary['present_days'] ?? 0) ?></div>
                            <div class="hr-att-card-label">حضور</div>
                        </div>
                        <div class="hr-att-card hr-att-card--absent">
                            <div class="hr-att-card-icon"><i class="fa fa-times-circle"></i></div>
                            <div class="hr-att-card-value"><?= (int)($attendanceSummary['absent_days'] ?? 0) ?></div>
                            <div class="hr-att-card-label">غياب</div>
                        </div>
                        <div class="hr-att-card hr-att-card--late">
                            <div class="hr-att-card-icon"><i class="fa fa-exclamation-circle"></i></div>
                            <div class="hr-att-card-value"><?= (int)($attendanceSummary['late_days'] ?? 0) ?></div>
                            <div class="hr-att-card-label">تأخير</div>
                        </div>
                        <div class="hr-att-card hr-att-card--leave">
                            <div class="hr-att-card-icon"><i class="fa fa-plane"></i></div>
                            <div class="hr-att-card-value"><?= (int)($attendanceSummary['leave_days'] ?? 0) ?></div>
                            <div class="hr-att-card-label">إجازة</div>
                        </div>
                        <div class="hr-att-card hr-att-card--hours">
                            <div class="hr-att-card-icon"><i class="fa fa-clock-o"></i></div>
                            <div class="hr-att-card-value"><?= number_format((float)($attendanceSummary['total_hours'] ?? 0), 1) ?></div>
                            <div class="hr-att-card-label">إجمالي الساعات</div>
                        </div>
                        <div class="hr-att-card hr-att-card--overtime">
                            <div class="hr-att-card-icon"><i class="fa fa-bolt"></i></div>
                            <div class="hr-att-card-value"><?= number_format((float)($attendanceSummary['overtime_hours'] ?? 0), 1) ?></div>
                            <div class="hr-att-card-label">ساعات إضافية</div>
                        </div>
                    </div>
                <?php endif ?>
            </div>

            <!-- ═════════════════════════════════
                 تبويب: الراتب
                 ═════════════════════════════════ -->
            <div role="tabpanel" class="tab-pane fade" id="tab-salary">
                <?php if (empty($salaryComponents)): ?>
                    <div class="hr-empty-state">
                        <i class="fa fa-money"></i>
                        <p>لا توجد مكونات راتب مسجلة.</p>
                    </div>
                <?php else: ?>
                    <?php
                    $totalEarnings = 0;
                    $totalDeductions = 0;
                    ?>
                    <div class="table-responsive">
                        <table class="table hr-detail-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>مكون الراتب</th>
                                    <th>النوع</th>
                                    <th>المبلغ</th>
                                    <th>العملة</th>
                                    <th>ساري من</th>
                                    <th>ساري إلى</th>
                                    <th>ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($salaryComponents as $i => $sc): ?>
                                <?php
                                    $component = $sc->component;
                                    $compName = $component ? $component->name : ('مكون #' . $sc->component_id);
                                    $compType = $component ? $component->component_type : '—';
                                    if ($compType === 'earning' || $compType === 'allowance') {
                                        $totalEarnings += $sc->amount;
                                    } else {
                                        $totalDeductions += $sc->amount;
                                    }
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><strong><?= Html::encode($compName) ?></strong></td>
                                    <td>
                                        <?php if ($compType === 'earning' || $compType === 'allowance'): ?>
                                            <span class="label label-success">استحقاق</span>
                                        <?php elseif ($compType === 'deduction'): ?>
                                            <span class="label label-danger">خصم</span>
                                        <?php else: ?>
                                            <span class="label label-default"><?= Html::encode($compType) ?></span>
                                        <?php endif ?>
                                    </td>
                                    <td><strong><?= number_format($sc->amount, 2) ?></strong></td>
                                    <td><?= Html::encode($sc->currency ?: 'ر.س') ?></td>
                                    <td><?= Html::encode($sc->effective_from ?: '—') ?></td>
                                    <td><?= Html::encode($sc->effective_to ?: 'مفتوح') ?></td>
                                    <td><?= Html::encode($sc->notes ?: '—') ?></td>
                                </tr>
                                <?php endforeach ?>
                            </tbody>
                            <tfoot>
                                <tr class="hr-salary-total">
                                    <td colspan="3"><strong>إجمالي الاستحقاقات</strong></td>
                                    <td colspan="5"><strong style="color:#166534"><?= number_format($totalEarnings, 2) ?></strong></td>
                                </tr>
                                <tr class="hr-salary-total">
                                    <td colspan="3"><strong>إجمالي الخصومات</strong></td>
                                    <td colspan="5"><strong style="color:#dc2626"><?= number_format($totalDeductions, 2) ?></strong></td>
                                </tr>
                                <tr class="hr-salary-net">
                                    <td colspan="3"><strong>صافي الراتب</strong></td>
                                    <td colspan="5"><strong style="color:#800020;font-size:16px"><?= number_format($totalEarnings - $totalDeductions, 2) ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif ?>
            </div>

            <!-- ═════════════════════════════════
                 تبويب: العلاوات السنوية
                 ═════════════════════════════════ -->
            <div role="tabpanel" class="tab-pane fade" id="tab-increments">
                <div style="margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                    <h4 class="hr-info-section-title" style="margin:0;"><i class="fa fa-line-chart"></i> العلاوات السنوية</h4>
                    <?= Html::a('<i class="fa fa-plus"></i> إضافة علاوة سنوية', ['/hr/hr-payroll/increment-create', 'user_id' => $user->id], ['class' => 'btn hr-btn-primary btn-sm', 'style' => 'border-radius:8px']) ?>
                </div>
                <?php if (empty($increments)): ?>
                    <div class="hr-empty-state">
                        <i class="fa fa-line-chart"></i>
                        <p>لا توجد علاوات سنوية مسجلة لهذا الموظف.</p>
                        <?= Html::a('<i class="fa fa-plus"></i> إضافة علاوة', ['/hr/hr-payroll/increment-create', 'user_id' => $user->id], ['class' => 'btn hr-btn-primary btn-sm']) ?>
                    </div>
                <?php else: ?>
                    <?php
                    $incStatusMap = ['pending' => 'بانتظار', 'approved' => 'معتمدة', 'applied' => 'مطبّقة', 'cancelled' => 'ملغية'];
                    $incTypeMap = ['fixed' => 'مبلغ ثابت', 'percentage' => 'نسبة %'];
                    ?>
                    <div class="table-responsive">
                        <table class="table hr-detail-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>سنة الخدمة / المرجع</th>
                                    <th>نوع العلاوة</th>
                                    <th>القيمة</th>
                                    <th>المبلغ المحسوب</th>
                                    <th>الراتب السابق → الجديد</th>
                                    <th>تاريخ السريان</th>
                                    <th>الحالة</th>
                                    <th>إجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($increments as $i => $inc): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><strong><?= $inc->getYearLabel() ?></strong></td>
                                    <td><?= $incTypeMap[$inc->increment_type] ?? $inc->increment_type ?></td>
                                    <td>
                                        <?php if ($inc->increment_type === 'percentage'): ?>
                                            <strong><?= $inc->amount ?>%</strong>
                                        <?php else: ?>
                                            <?= number_format($inc->amount, 2) ?>
                                        <?php endif ?>
                                    </td>
                                    <td><strong><?= number_format($inc->calculated_amount ?? 0, 2) ?></strong></td>
                                    <td>
                                        <span style="color:#94a3b8"><?= number_format($inc->previous_salary ?? 0, 2) ?></span>
                                        <i class="fa fa-arrow-left" style="margin:0 4px;color:#800020"></i>
                                        <strong style="color:#166534"><?= number_format($inc->new_salary ?? 0, 2) ?></strong>
                                    </td>
                                    <td><?= Html::encode($inc->effective_date) ?></td>
                                    <td>
                                        <?php
                                        $st = $incStatusMap[$inc->status] ?? $inc->status;
                                        $stClass = $inc->status === 'applied' ? 'label-success' : ($inc->status === 'pending' ? 'label-warning' : 'label-default');
                                        ?>
                                        <span class="label <?= $stClass ?>"><?= $st ?></span>
                                    </td>
                                    <td>
                                        <?php if ($inc->status === 'pending'): ?>
                                            <?= Html::a('<i class="fa fa-check"></i> تطبيق', ['/hr/hr-payroll/apply-increment', 'id' => $inc->id], [
                                                'class' => 'btn btn-xs btn-success',
                                                'data-confirm' => 'هل أنت متأكد من تطبيق هذه العلاوة؟ سيتم تحديث الراتب الأساسي.',
                                                'data-method' => 'post',
                                            ]) ?>
                                        <?php elseif ($inc->status === 'applied'): ?>
                                            <span class="text-muted"><i class="fa fa-check-circle"></i> مطبّقة</span>
                                        <?php endif ?>
                                    </td>
                                </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </div>

            <!-- ═════════════════════════════════
                 تبويب: الميداني
                 ═════════════════════════════════ -->
            <?php if ($extended && $extended->is_field_staff): ?>
            <div role="tabpanel" class="tab-pane fade" id="tab-field">
                <div class="hr-info-section">
                    <h4 class="hr-info-section-title"><i class="fa fa-map-marker"></i> بيانات العمل الميداني</h4>
                    <table class="table hr-info-table">
                        <tr>
                            <th>موظف ميداني</th>
                            <td>
                                <span class="label label-success"><i class="fa fa-check"></i> نعم</span>
                            </td>
                        </tr>
                        <?php if (!empty($extended->field_role)): ?>
                        <tr>
                            <th>الدور الميداني</th>
                            <td><?= Html::encode($extended->field_role) ?></td>
                        </tr>
                        <?php endif ?>
                        <?php if (!empty($extended->branch_id) && $extended->branch): ?>
                        <tr>
                            <th>الفرع</th>
                            <td><?= Html::encode($extended->branch->location ?? '—') ?></td>
                        </tr>
                        <?php endif ?>
                        <?php if (!empty($extended->shift_id) && $extended->shift): ?>
                        <tr>
                            <th>الوردية</th>
                            <td><?= Html::encode($extended->shift->name ?? '—') ?></td>
                        </tr>
                        <?php endif ?>
                    </table>
                </div>
            </div>
            <?php endif ?>

        </div><!-- /.tab-content -->
    </div><!-- /.hr-tabs-wrapper -->

</div><!-- /.hr-page -->


<?php
/* ═══════════════════════════════════════════════════════════════
 *  CSS
 * ═══════════════════════════════════════════════════════════════ */
$css = <<<CSS

/* ─── Profile Header ─── */
.hr-profile-header {
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.hr-profile-avatar-wrap {
    position: relative;
    flex-shrink: 0;
}
.hr-profile-avatar {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #f1f5f9;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.hr-profile-status-dot {
    position: absolute;
    bottom: 4px;
    left: 4px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 3px solid #fff;
    background: #94a3b8;
}
.hr-profile-status-dot.hr-status--active { background: #22c55e; }
.hr-profile-status-dot.hr-status--leave { background: #3b82f6; }
.hr-profile-status-dot.hr-status--suspended { background: #ef4444; }
.hr-profile-status-dot.hr-status--terminated { background: #6b7280; }
.hr-profile-status-dot.hr-status--resigned { background: #f59e0b; }
.hr-profile-status-dot.hr-status--probation { background: #8b5cf6; }

.hr-profile-info { flex: 1; min-width: 200px; }
.hr-profile-name {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 8px;
}
.hr-profile-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
}
.hr-profile-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    background: #f1f5f9;
    color: #475569;
}
.hr-profile-badge i { font-size: 11px; }
.hr-profile-badge--code { background: #eff6ff; color: #1d4ed8; font-family: monospace; }
.hr-profile-badge--dept { background: #f0fdf4; color: #166534; }
.hr-profile-badge--desig { background: #fefce8; color: #854d0e; }
.hr-profile-badge.hr-status--active { background: #dcfce7; color: #166534; }
.hr-profile-badge.hr-status--leave { background: #dbeafe; color: #1e40af; }
.hr-profile-badge.hr-status--suspended { background: #fee2e2; color: #991b1b; }
.hr-profile-badge.hr-status--terminated { background: #f3f4f6; color: #4b5563; }
.hr-profile-badge.hr-status--resigned { background: #fef3c7; color: #92400e; }
.hr-profile-badge.hr-status--probation { background: #ede9fe; color: #5b21b6; }

.hr-profile-contact {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 13px;
    color: #64748b;
}
.hr-profile-contact i {
    margin-left: 4px;
    color: #94a3b8;
}
.hr-profile-actions {
    flex-shrink: 0;
}

/* ─── Tabs ─── */
.hr-tabs-wrapper {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.hr-nav-tabs {
    border-bottom: 2px solid #e2e8f0;
    background: #f8fafc;
    padding: 0 16px;
    margin: 0;
    display: flex;
    flex-wrap: wrap;
}
.hr-nav-tabs > li > a {
    border: none !important;
    border-bottom: 3px solid transparent !important;
    border-radius: 0 !important;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    padding: 14px 18px;
    margin: 0;
    transition: all 0.2s;
}
.hr-nav-tabs > li > a:hover {
    background: transparent;
    color: #800020;
    border-bottom-color: rgba(128,0,32,0.3) !important;
}
.hr-nav-tabs > li.active > a,
.hr-nav-tabs > li.active > a:hover,
.hr-nav-tabs > li.active > a:focus {
    background: transparent !important;
    color: #800020 !important;
    border-bottom: 3px solid #800020 !important;
}
.hr-nav-tabs > li > a i {
    margin-left: 6px;
}
.hr-tab-badge {
    background: #800020;
    color: #fff;
    font-size: 10px;
    padding: 2px 7px;
    border-radius: 10px;
    margin-right: 4px;
    vertical-align: middle;
}
.hr-tab-content {
    padding: 24px;
    min-height: 200px;
}

/* ─── Info Grid ─── */
.hr-info-grid {
    display: flex;
    flex-direction: column;
    gap: 24px;
}
.hr-info-section {
    border: 1px solid #f1f5f9;
    border-radius: 12px;
    overflow: hidden;
}
.hr-info-section-title {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    padding: 12px 18px;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}
.hr-info-section-title i {
    margin-left: 8px;
    color: #800020;
}
.hr-info-table {
    margin: 0;
}
.hr-info-table th {
    background: #fafbfc;
    color: #64748b;
    font-weight: 600;
    font-size: 12px;
    width: 15%;
    padding: 10px 16px !important;
    white-space: nowrap;
}
.hr-info-table td {
    font-size: 13px;
    color: #334155;
    padding: 10px 16px !important;
}

/* ─── Detail Table ─── */
.hr-detail-table thead th {
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    font-size: 12px;
    padding: 10px 14px;
    white-space: nowrap;
    border-bottom: 2px solid #e2e8f0;
}
.hr-detail-table tbody td {
    padding: 10px 14px;
    font-size: 13px;
    color: #334155;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}
.hr-detail-table tbody tr:hover {
    background: #fefce8;
}

/* ─── Document Rows ─── */
.hr-row-expired { background: #fef2f2 !important; }
.hr-row-expiring { background: #fffbeb !important; }

/* ─── Badges ─── */
.hr-badge--blood {
    background: #fee2e2;
    color: #991b1b;
    padding: 3px 10px;
    border-radius: 6px;
    font-weight: 700;
}
.hr-badge--doc {
    background: #eff6ff;
    color: #1d4ed8;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}
.hr-phone-link {
    color: #0284c7;
    text-decoration: none;
}
.hr-phone-link:hover { text-decoration: underline; }
.hr-iban-code {
    background: #f1f5f9;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    letter-spacing: 1px;
    color: #334155;
}
.hr-notes-box {
    padding: 16px 18px;
    font-size: 13px;
    color: #475569;
    line-height: 1.7;
}

/* ─── Attendance Cards ─── */
.hr-section-subtitle {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 16px;
}
.hr-section-subtitle i {
    margin-left: 6px;
    color: #800020;
}
.hr-attendance-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 14px;
}
.hr-att-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    text-align: center;
    transition: transform 0.15s;
}
.hr-att-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
.hr-att-card-icon { font-size: 24px; margin-bottom: 8px; }
.hr-att-card-value { font-size: 28px; font-weight: 800; margin-bottom: 4px; }
.hr-att-card-label { font-size: 12px; color: #64748b; font-weight: 600; }

.hr-att-card--total .hr-att-card-icon { color: #6366f1; }
.hr-att-card--total .hr-att-card-value { color: #4338ca; }
.hr-att-card--present .hr-att-card-icon { color: #22c55e; }
.hr-att-card--present .hr-att-card-value { color: #166534; }
.hr-att-card--absent .hr-att-card-icon { color: #ef4444; }
.hr-att-card--absent .hr-att-card-value { color: #991b1b; }
.hr-att-card--late .hr-att-card-icon { color: #f59e0b; }
.hr-att-card--late .hr-att-card-value { color: #92400e; }
.hr-att-card--leave .hr-att-card-icon { color: #3b82f6; }
.hr-att-card--leave .hr-att-card-value { color: #1e40af; }
.hr-att-card--hours .hr-att-card-icon { color: #8b5cf6; }
.hr-att-card--hours .hr-att-card-value { color: #6d28d9; }
.hr-att-card--overtime .hr-att-card-icon { color: #ec4899; }
.hr-att-card--overtime .hr-att-card-value { color: #be185d; }

/* ─── Salary Footer ─── */
.hr-salary-total td {
    background: #f8fafc !important;
    padding: 10px 14px !important;
}
.hr-salary-net td {
    background: #fef2f2 !important;
    padding: 12px 14px !important;
    border-top: 2px solid #e2e8f0;
}

/* ─── Empty State ─── */
.hr-empty-state {
    text-align: center;
    padding: 48px 20px;
    color: #94a3b8;
}
.hr-empty-state i {
    font-size: 48px;
    display: block;
    margin-bottom: 12px;
    opacity: 0.5;
}
.hr-empty-state p {
    font-size: 14px;
    margin-bottom: 16px;
}

CSS;

$this->registerCss($css);
?>
