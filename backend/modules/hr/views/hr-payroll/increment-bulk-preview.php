<?php
/**
 * معاينة العلاوة التلقائية — قائمة الموظفين وسنوات الخدمة وإجمالي العلاوة.
 * اعتماد أو رفض قبل إنشاء السجلات.
 *
 * @var $preview array ['rows' => [...], 'grand_total' => float, 'skipped' => int]
 * @var $incrementType string
 * @var $amount float
 * @var $effectiveDate string
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'معاينة العلاوة التلقائية';

$typeLabel = $incrementType === 'percentage' ? $amount . '%' : number_format($amount, 2);
?>
<style>
.inc-preview-page { padding: 20px; }
.inc-preview-header {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px; margin-bottom: 20px;
}
.inc-preview-header h1 { font-size: 22px; font-weight: 700; color: #800020; margin: 0; }
.inc-preview-summary {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 16px 20px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 24px; align-items: center;
}
.inc-preview-summary .item { font-size: 14px; }
.inc-preview-summary .item strong { color: #1e293b; }
.inc-preview-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.04); margin-bottom: 20px;
}
.inc-preview-table { width: 100%; border-collapse: collapse; }
.inc-preview-table thead th {
    background: #f8fafc; color: #475569; font-size: 12px; font-weight: 700;
    padding: 12px 14px; border-bottom: 2px solid #e2e8f0; text-align: right;
}
.inc-preview-table tbody td {
    padding: 10px 14px; font-size: 13px; border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.inc-preview-table tbody tr:hover { background: #fefce8; }
.inc-preview-table .amount { font-weight: 700; direction: ltr; text-align: left; }
.inc-preview-table tfoot td {
    background: #fef2f2; font-weight: 700; padding: 14px; border-top: 2px solid #e2e8f0;
    font-size: 15px; color: #800020;
}
.inc-preview-actions {
    display: flex; gap: 12px; justify-content: flex-end; flex-wrap: wrap;
    padding: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
}
.inc-preview-empty {
    text-align: center; padding: 48px 20px; color: #64748b;
}
.inc-preview-empty i { font-size: 48px; display: block; margin-bottom: 12px; opacity: 0.5; }
.breakdown-list { font-size: 11px; color: #64748b; }
</style>

<div class="inc-preview-page">
    <div class="inc-preview-header">
        <h1><i class="fa fa-eye"></i> <?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="fa fa-arrow-right"></i> العودة للقائمة', ['increments'], ['class' => 'btn btn-default btn-sm', 'style' => 'border-radius:8px']) ?>
    </div>

    <div class="inc-preview-summary">
        <span class="item"><strong>تاريخ السريان:</strong> <?= Html::encode($effectiveDate) ?></span>
        <span class="item"><strong>نوع العلاوة:</strong> <?= $incrementType === 'percentage' ? 'نسبة ' . $amount . '%' : 'مبلغ ثابت ' . number_format($amount, 2) ?></span>
        <span class="item"><strong>عدد الموظفين المستحقين:</strong> <?= count($preview['rows']) ?></span>
        <span class="item"><strong>إجمالي مبلغ العلاوات:</strong> <span style="color:#800020;font-size:18px"><?= number_format($preview['grand_total'], 2) ?></span></span>
        <?php if ($preview['skipped'] > 0): ?>
            <span class="item" style="color:#64748b">(تم تخطي <?= $preview['skipped'] ?> موظف)</span>
        <?php endif; ?>
    </div>

    <div class="inc-preview-card">
        <?php if (empty($preview['rows'])): ?>
            <div class="inc-preview-empty">
                <i class="fa fa-users"></i>
                <p>لا يوجد موظفون مستحقون للعلاوة حسب المعطيات المدخلة (سنوات الخدمة حتى تاريخ السريان، ووجود راتب أساسي).</p>
                <?= Html::a('تعديل المعطيات', ['increment-bulk'], ['class' => 'btn btn-default', 'style' => 'border-radius:8px']) ?>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table class="inc-preview-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الموظف</th>
                            <th>سنوات الخدمة</th>
                            <th>عدد العلاوات (سنوات جديدة)</th>
                            <th>تفصيل العلاوة</th>
                            <th style="text-align:left">إجمالي العلاوة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview['rows'] as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= Html::encode($row['name']) ?></strong><br><span style="font-size:11px;color:#94a3b8">#<?= $row['user_id'] ?></span></td>
                            <td><?= $row['years_served'] ?></td>
                            <td><?= $row['increments_to_create'] ?></td>
                            <td>
                                <div class="breakdown-list">
                                    <?php foreach ($row['breakdown'] as $sv => $amt): ?>
                                        سنة <?= $sv ?>: <?= number_format($amt, 2) ?><br>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="amount" style="color:#166534;font-size:14px"><?= number_format($row['total_increment'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" style="text-align:left">الإجمالي الكلي للعلاوات</td>
                            <td class="amount" style="font-size:16px"><?= number_format($preview['grand_total'], 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="inc-preview-actions">
                <?= Html::a('<i class="fa fa-times"></i> رفض / إلغاء', ['increment-bulk'], [
                    'class' => 'btn btn-default',
                    'style' => 'border-radius:8px',
                ]) ?>
                <?= Html::beginForm(['increment-bulk'], 'post', ['style' => 'display:inline']) ?>
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <?= Html::hiddenInput('confirm', '1') ?>
                    <?= Html::hiddenInput('increment_type', $incrementType) ?>
                    <?= Html::hiddenInput('amount', $amount) ?>
                    <?= Html::hiddenInput('effective_date', $effectiveDate) ?>
                    <button type="submit" class="btn" style="background:#166534;color:#fff;border-radius:8px">
                        <i class="fa fa-check"></i> اعتماد وإنشاء العلاوات
                    </button>
                <?= Html::endForm() ?>
            </div>
        <?php endif; ?>
    </div>
</div>
