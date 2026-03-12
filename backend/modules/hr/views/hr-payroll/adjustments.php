<?php
/**
 * شاشة التعديلات/العمولات الشهرية على مستوى المسيرة
 *
 * @var $run \backend\modules\hr\models\HrPayrollRun
 * @var $employees array
 * @var $adjustmentsByUser array
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'عمولات وتعديلات — ' . $run->run_code;

$arabicMonths = [
    1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
    5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
    9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
];
$monthName = $arabicMonths[(int)$run->period_month] ?? $run->period_month;

$typeOptions = [
    'commission' => 'عمولة',
    'bonus'      => 'مكافأة',
    'deduction'  => 'خصم',
    'other'      => 'أخرى',
];
?>

<style>
.adj-page { padding: 20px; }
.adj-header {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px; margin-bottom: 20px;
}
.adj-header h1 { font-size: 22px; font-weight: 700; color: #800020; margin: 0; }
.adj-period-badge {
    background: #fef2f2; color: #800020; padding: 6px 16px;
    border-radius: 20px; font-size: 13px; font-weight: 600;
}
.adj-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
.adj-table { width: 100%; border-collapse: collapse; }
.adj-table thead th {
    background: #f8fafc; color: #475569; font-size: 12px; font-weight: 700;
    padding: 12px 14px; border-bottom: 2px solid #e2e8f0; text-align: right;
}
.adj-table tbody td {
    padding: 10px 14px; font-size: 13px; border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.adj-table tbody tr:hover { background: #fefce8; }
.adj-table tbody tr.has-value { background: #f0fdf4; }
.adj-input {
    width: 120px; padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 8px;
    font-size: 13px; text-align: center; direction: ltr;
}
.adj-input:focus { border-color: #800020; outline: none; box-shadow: 0 0 0 3px rgba(128,0,32,0.1); }
.adj-select {
    padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 8px;
    font-size: 12px; background: #fff;
}
.adj-desc-input {
    width: 200px; padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 8px;
    font-size: 12px;
}
.adj-actions { padding: 16px 20px; display: flex; gap: 10px; justify-content: flex-end; background: #f8fafc; border-top: 1px solid #e2e8f0; }
.emp-name { font-weight: 600; color: #1e293b; }
.emp-id { font-size: 11px; color: #94a3b8; margin-right: 6px; }
.adj-status-note {
    background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px;
    padding: 12px 18px; margin-bottom: 16px; font-size: 13px; color: #1e40af;
}
.adj-status-note i { margin-left: 6px; }
</style>

<div class="adj-page">
    <div class="adj-header">
        <h1><i class="fa fa-sliders"></i> عمولات وتعديلات — <?= Html::encode($run->run_code) ?></h1>
        <div>
            <span class="adj-period-badge">
                <i class="fa fa-calendar"></i> <?= Html::encode($monthName . ' ' . $run->period_year) ?>
            </span>
            <?= Html::a('<i class="fa fa-arrow-right"></i> العودة للمسيرة', ['view', 'id' => $run->id], ['class' => 'btn btn-default btn-sm', 'style' => 'border-radius:8px;margin-right:8px']) ?>
        </div>
    </div>

    <?php if (!in_array($run->status, ['draft', 'calculated'])): ?>
        <div class="adj-status-note" style="background:#fef2f2;border-color:#fecaca;color:#991b1b">
            <i class="fa fa-lock"></i>
            المسيرة بحالة "<?= Html::encode($run->status) ?>" — لا يمكن تعديل العمولات.
        </div>
    <?php else: ?>
        <div class="adj-status-note">
            <i class="fa fa-info-circle"></i>
            أدخل مبالغ العمولات/التعديلات لكل موظف. الحقول الفارغة أو بقيمة 0 لن تُحفظ.
            بعد الحفظ، اضغط "حساب" على المسيرة لاحتساب التعديلات.
        </div>
    <?php endif; ?>

    <?= Html::beginForm(['save-adjustments', 'id' => $run->id], 'post') ?>

    <div class="adj-card">
        <table class="adj-table">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th style="width:25%">الموظف</th>
                    <th style="width:15%">النوع</th>
                    <th style="width:15%">المبلغ</th>
                    <th style="width:25%">الوصف</th>
                    <th style="width:15%">الحالي</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $i => $emp):
                    $uid = $emp['id'];
                    $existing = $adjustmentsByUser[$uid][0] ?? null;
                    $hasVal = $existing && (float)$existing['amount'] > 0;
                ?>
                <tr class="<?= $hasVal ? 'has-value' : '' ?>">
                    <td><?= $i + 1 ?></td>
                    <td>
                        <span class="emp-name"><?= Html::encode($emp['name'] ?: $emp['username']) ?></span>
                        <span class="emp-id">#<?= $uid ?></span>
                    </td>
                    <td>
                        <select name="Adjustment[<?= $uid ?>][type]" class="adj-select"
                            <?= !in_array($run->status, ['draft', 'calculated']) ? 'disabled' : '' ?>>
                            <?php foreach ($typeOptions as $val => $label): ?>
                                <option value="<?= $val ?>" <?= ($existing && $existing['adjustment_type'] === $val) ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0"
                               name="Adjustment[<?= $uid ?>][amount]"
                               class="adj-input"
                               value="<?= $existing ? (float)$existing['amount'] : '' ?>"
                               placeholder="0.00"
                               <?= !in_array($run->status, ['draft', 'calculated']) ? 'disabled' : '' ?>>
                    </td>
                    <td>
                        <input type="text"
                               name="Adjustment[<?= $uid ?>][description]"
                               class="adj-desc-input"
                               value="<?= Html::encode($existing['description'] ?? '') ?>"
                               placeholder="وصف اختياري..."
                               <?= !in_array($run->status, ['draft', 'calculated']) ? 'disabled' : '' ?>>
                    </td>
                    <td>
                        <?php if ($hasVal): ?>
                            <span style="color:#166534;font-weight:700">
                                <?= number_format((float)$existing['amount'], 2) ?>
                            </span>
                        <?php else: ?>
                            <span style="color:#94a3b8">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (in_array($run->status, ['draft', 'calculated'])): ?>
        <div class="adj-actions">
            <?= Html::a('إلغاء', ['view', 'id' => $run->id], ['class' => 'btn btn-default btn-sm', 'style' => 'border-radius:8px']) ?>
            <?= Html::submitButton('<i class="fa fa-save"></i> حفظ التعديلات', [
                'class' => 'btn btn-primary btn-sm',
                'style' => 'background:#800020;border-color:#800020;border-radius:8px',
            ]) ?>
        </div>
        <?php endif; ?>
    </div>

    <?= Html::endForm() ?>
</div>
