<?php
/**
 * العلاوات السنوية — قائمة
 *
 * @var $dataProvider yii\data\ActiveDataProvider
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'العلاوات السنوية';

echo $this->render('@backend/modules/hr/views/_section_tabs', [
    'group' => 'payroll',
    'tabs'  => [
        ['label' => 'مسيرات الرواتب',   'icon' => 'fa-money',       'url' => ['/hr/hr-payroll/index']],
        ['label' => 'العلاوات السنوية', 'icon' => 'fa-line-chart',  'url' => ['/hr/hr-payroll/increments']],
        ['label' => 'السلف والقروض',    'icon' => 'fa-credit-card', 'url' => ['/hr/hr-loan/index']],
    ],
]);

$statusMap = [
    'pending'   => ['label' => 'بانتظار', 'class' => 'label-warning'],
    'approved'  => ['label' => 'معتمدة',  'class' => 'label-info'],
    'applied'   => ['label' => 'مطبّقة',  'class' => 'label-success'],
    'cancelled' => ['label' => 'ملغية',   'class' => 'label-danger'],
];

$typeMap = [
    'fixed'      => 'مبلغ ثابت',
    'percentage' => 'نسبة %',
];
?>

<style>
.inc-page { padding: 20px; }
.inc-header {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px; margin-bottom: 20px;
}
.inc-header h1 { font-size: 22px; font-weight: 700; color: #800020; margin: 0; }
.inc-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
.inc-table { width: 100%; border-collapse: collapse; }
.inc-table thead th {
    background: #f8fafc; color: #475569; font-size: 12px; font-weight: 700;
    padding: 12px 14px; border-bottom: 2px solid #e2e8f0; text-align: right;
}
.inc-table tbody td {
    padding: 10px 14px; font-size: 13px; border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.inc-table tbody tr:hover { background: #fefce8; }
.inc-empty {
    text-align: center; padding: 48px 20px; color: #94a3b8;
}
.inc-empty i { font-size: 40px; display: block; margin-bottom: 12px; opacity: 0.4; }
.inc-salary-change {
    display: flex; align-items: center; gap: 6px; font-size: 12px;
}
.inc-salary-change .old { color: #94a3b8; text-decoration: line-through; }
.inc-salary-change .arrow { color: #800020; }
.inc-salary-change .new { color: #166534; font-weight: 700; }
</style>

<div class="inc-page">
    <div class="inc-header">
        <h1><i class="fa fa-line-chart"></i> العلاوات السنوية</h1>
        <div>
            <?= Html::a('<i class="fa fa-plus"></i> علاوة يدوية', ['increment-create'], [
                'class' => 'btn btn-sm',
                'style' => 'background:#800020;color:#fff;border-radius:8px',
            ]) ?>
            <?= Html::a('<i class="fa fa-magic"></i> علاوة تلقائية', ['increment-bulk'], [
                'class' => 'btn btn-sm btn-success',
                'style' => 'border-radius:8px;margin-right:6px',
            ]) ?>
            <?= Html::a('<i class="fa fa-arrow-right"></i> مسيرات الرواتب', ['index'], [
                'class' => 'btn btn-default btn-sm',
                'style' => 'border-radius:8px;margin-right:8px',
            ]) ?>
        </div>
    </div>

    <div class="inc-card">
        <?php $models = $dataProvider->getModels(); ?>
        <?php if (empty($models)): ?>
            <div class="inc-empty">
                <i class="fa fa-line-chart"></i>
                <p>لا توجد علاوات سنوية مسجلة بعد.</p>
            </div>
        <?php else: ?>
            <table class="inc-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الموظف</th>
                        <th>سنة الخدمة / المرجع</th>
                        <th>نوع العلاوة</th>
                        <th>القيمة</th>
                        <th>المبلغ المحسوب</th>
                        <th>الراتب</th>
                        <th>تاريخ السريان</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($models as $i => $inc): ?>
                    <?php
                        $user = $inc->user;
                        $userName = $user ? ($user->name ?: $user->username) : '#' . $inc->user_id;
                        $st = $statusMap[$inc->status] ?? ['label' => $inc->status, 'class' => 'label-default'];
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= Html::encode($userName) ?></strong></td>
                        <td><?= $inc->getYearLabel() ?></td>
                        <td><?= $typeMap[$inc->increment_type] ?? $inc->increment_type ?></td>
                        <td>
                            <?php if ($inc->increment_type === 'percentage'): ?>
                                <span style="color:#1d4ed8;font-weight:700"><?= $inc->amount ?>%</span>
                            <?php else: ?>
                                <span style="font-weight:700"><?= number_format($inc->amount, 2) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= number_format($inc->calculated_amount ?? 0, 2) ?></strong></td>
                        <td>
                            <div class="inc-salary-change">
                                <span class="old"><?= number_format($inc->previous_salary ?? 0, 2) ?></span>
                                <span class="arrow">←</span>
                                <span class="new"><?= number_format($inc->new_salary ?? 0, 2) ?></span>
                            </div>
                        </td>
                        <td><?= Html::encode($inc->effective_date) ?></td>
                        <td><span class="label <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
                        <td>
                            <?php if ($inc->status === 'pending'): ?>
                                <?= Html::a('<i class="fa fa-check"></i> تطبيق', ['apply-increment', 'id' => $inc->id], [
                                    'class' => 'btn btn-xs btn-success',
                                    'style' => 'border-radius:6px',
                                    'data-confirm' => 'هل أنت متأكد من تطبيق هذه العلاوة؟ سيتم تحديث الراتب الأساسي للموظف.',
                                    'data-method' => 'post',
                                ]) ?>
                            <?php elseif ($inc->status === 'applied'): ?>
                                <span class="text-success"><i class="fa fa-check-circle"></i> تم التطبيق</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
