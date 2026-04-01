<?php

use yii\helpers\Html;
use yii\helpers\Url;
use backend\modules\sharedExpenses\models\SharedExpenseAllocation;
use common\helper\Permissions;

/** @var yii\web\View $this */
/** @var backend\modules\sharedExpenses\models\SharedExpenseAllocation $model */

$this->title = 'عرض التوزيع: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'توزيع المصاريف المشتركة', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->name;

$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCss('.content-header,.page-header { display: none !important; }');

$methods = SharedExpenseAllocation::getAllocationMethods();
$lines = $model->lines;
$totalAllocated = array_sum(array_map(function($l) { return (float) $l->allocated_amount; }, $lines));
$totalPct = array_sum(array_map(function($l) { return (float) $l->percentage; }, $lines));
?>

<style>
:root {
    --se-primary: #8b5cf6;
    --se-primary-light: #ede9fe;
    --se-primary-dark: #7c3aed;
    --se-border: #e2e8f0;
    --se-bg: #f8fafc;
    --se-r: 12px;
    --se-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}
.se-view { max-width: 1000px; margin: 0 auto; padding: 20px; }
.se-view-card { background: #fff; border-radius: var(--se-r); box-shadow: var(--se-shadow); border: 1px solid var(--se-border); margin-bottom: 18px; overflow: hidden; }

.se-view-header { display: flex; align-items: center; justify-content: space-between; padding: 24px; background: var(--se-bg); border-bottom: 1px solid var(--se-border); flex-wrap: wrap; gap: 12px; }
.se-view-header-info { display: flex; align-items: center; gap: 16px; }
.se-view-avatar { width: 56px; height: 56px; border-radius: 14px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 22px; flex-shrink: 0; }
.se-view-name { font-size: 20px; font-weight: 700; color: #1e293b; }
.se-view-sub { font-size: 13px; color: #64748b; margin-top: 2px; display: flex; align-items: center; gap: 8px; }
.se-badge { display: inline-block; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 6px; }
.se-badge-draft { background: #fef3c7; color: #92400e; }
.se-badge-approved { background: #dcfce7; color: #15803d; }
.se-badge-method { background: var(--se-primary-light); color: var(--se-primary-dark); }

.se-view-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0; }
.se-view-item { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; border-left: 1px solid #f1f5f9; }
.se-view-label { font-size: 12px; color: #94a3b8; margin-bottom: 2px; }
.se-view-value { font-size: 14px; color: #1e293b; font-weight: 500; }

.se-card-title { font-size: 15px; font-weight: 700; color: #1e293b; padding: 16px 20px; background: var(--se-bg); border-bottom: 1px solid var(--se-border); display: flex; align-items: center; gap: 8px; }
.se-card-title i { color: var(--se-primary); }

.se-lines-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.se-lines-table thead th { background: var(--se-bg); padding: 10px 14px; font-weight: 600; color: #475569; border-bottom: 2px solid var(--se-border); text-align: right; font-size: 12px; }
.se-lines-table tbody tr { border-bottom: 1px solid #f1f5f9; }
.se-lines-table tbody tr:hover { background: #faf5ff; }
.se-lines-table tbody td { padding: 12px 14px; color: #334155; vertical-align: middle; }
.se-lines-table tfoot td { padding: 12px 14px; font-weight: 700; color: #1e293b; background: var(--se-bg); border-top: 2px solid var(--se-border); }

.se-pct-bar { height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; width: 80px; display: inline-block; vertical-align: middle; margin-right: 8px; }
.se-pct-bar-fill { height: 100%; background: var(--se-primary); border-radius: 3px; }

.se-view-notes { padding: 16px 20px; }
.se-view-notes-title { font-size: 13px; color: #94a3b8; margin-bottom: 6px; }
.se-view-notes-text { font-size: 14px; color: #334155; line-height: 1.7; white-space: pre-wrap; }

.se-view-actions { padding: 16px 20px; display: flex; gap: 8px; justify-content: flex-end; background: var(--se-bg); border-top: 1px solid var(--se-border); flex-wrap: wrap; }
.se-view-actions .btn { border-radius: 8px; font-size: 13px; font-weight: 600; }
.se-btn-approve { background: #059669; color: #fff !important; border: none; padding: 8px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; }
.se-btn-approve:hover { background: #047857; color: #fff !important; }

@media (max-width: 768px) {
    .se-view-grid { grid-template-columns: 1fr 1fr; }
    .se-view-header { flex-direction: column; align-items: flex-start; }
}
@media (max-width: 500px) {
    .se-view-grid { grid-template-columns: 1fr; }
}
</style>

<div class="se-view">

    <div class="se-view-card">
        <div class="se-view-header">
            <div class="se-view-header-info">
                <div class="se-view-avatar"><i class="fa fa-share-alt"></i></div>
                <div>
                    <div class="se-view-name"><?= Html::encode($model->name) ?></div>
                    <div class="se-view-sub">
                        توزيع #<?= $model->id ?>
                        <?php if ($model->status === 'معتمد'): ?>
                            <span class="se-badge se-badge-approved">معتمد</span>
                        <?php else: ?>
                            <span class="se-badge se-badge-draft">مسودة</span>
                        <?php endif ?>
                        <span class="se-badge se-badge-method"><?= Html::encode($methods[$model->allocation_method] ?? $model->allocation_method) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="se-view-grid">
            <div class="se-view-item">
                <div class="se-view-label">المبلغ الإجمالي</div>
                <div class="se-view-value" style="color:var(--se-primary);font-weight:700;font-size:18px"><?= number_format($model->total_amount, 2) ?></div>
            </div>
            <div class="se-view-item">
                <div class="se-view-label">تاريخ التوزيع</div>
                <div class="se-view-value"><?= Html::encode($model->allocation_date) ?></div>
            </div>
            <div class="se-view-item">
                <div class="se-view-label">طريقة التوزيع</div>
                <div class="se-view-value"><?= Html::encode($methods[$model->allocation_method] ?? $model->allocation_method) ?></div>
            </div>
            <div class="se-view-item">
                <div class="se-view-label">الفترة من</div>
                <div class="se-view-value"><?= Html::encode($model->period_from ?: '—') ?></div>
            </div>
            <div class="se-view-item">
                <div class="se-view-label">الفترة إلى</div>
                <div class="se-view-value"><?= Html::encode($model->period_to ?: '—') ?></div>
            </div>
            <div class="se-view-item">
                <div class="se-view-label">تاريخ الإنشاء</div>
                <div class="se-view-value"><?= $model->created_at ? date('Y-m-d H:i', $model->created_at) : '—' ?></div>
            </div>
            <div class="se-view-item">
                <div class="se-view-label">أنشئ بواسطة</div>
                <div class="se-view-value"><?= Html::encode($model->createdByUser->username ?? '—') ?></div>
            </div>
            <?php if ($model->status === 'معتمد'): ?>
                <div class="se-view-item">
                    <div class="se-view-label">اعتمد بواسطة</div>
                    <div class="se-view-value"><?= Html::encode($model->approvedByUser->username ?? '—') ?></div>
                </div>
                <div class="se-view-item">
                    <div class="se-view-label">تاريخ الاعتماد</div>
                    <div class="se-view-value"><?= $model->approved_at ? date('Y-m-d H:i', $model->approved_at) : '—' ?></div>
                </div>
            <?php endif ?>
        </div>

        <?php if (!empty($model->notes)): ?>
            <div class="se-view-notes">
                <div class="se-view-notes-title">ملاحظات</div>
                <div class="se-view-notes-text"><?= Html::encode($model->notes) ?></div>
            </div>
        <?php endif ?>
    </div>

    <?php if (!empty($lines)): ?>
        <div class="se-view-card">
            <div class="se-card-title"><i class="fa fa-list"></i> تفاصيل التوزيع (<?= count($lines) ?> محفظة)</div>
            <table class="se-lines-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>المحفظة</th>
                        <th>القيمة المرجعية</th>
                        <th>النسبة</th>
                        <th>المبلغ الموزّع</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lines as $i => $line): ?>
                        <tr>
                            <td style="color:#94a3b8"><?= $i + 1 ?></td>
                            <td style="font-weight:600"><?= Html::encode($line->company->name ?? '—') ?></td>
                            <td><?= number_format($line->metric_value, 2) ?></td>
                            <td>
                                <span class="se-pct-bar"><span class="se-pct-bar-fill" style="width:<?= min($line->percentage, 100) ?>%"></span></span>
                                <?= number_format($line->percentage, 2) ?>%
                            </td>
                            <td style="font-weight:600;color:var(--se-primary)"><?= number_format($line->allocated_amount, 2) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">الإجمالي</td>
                        <td><?= number_format($totalPct, 2) ?>%</td>
                        <td style="color:var(--se-primary)"><?= number_format($totalAllocated, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif ?>

    <div class="se-view-card">
        <div class="se-view-actions">
            <?= Html::a('<i class="fa fa-arrow-right"></i> العودة', ['index'], ['class' => 'btn btn-secondary']) ?>
            <?php if ($model->status === SharedExpenseAllocation::STATUS_DRAFT): ?>
                <?= Html::a('<i class="fa fa-pencil"></i> تعديل', ['update', 'id' => $model->id], ['class' => 'btn btn-primary', 'style' => 'background:var(--se-primary);border-color:var(--se-primary)']) ?>
                <?= Html::a('<i class="fa fa-check"></i> اعتماد', ['approve', 'id' => $model->id], [
                    'class' => 'se-btn-approve',
                    'data-method' => 'post',
                    'data-confirm' => 'هل أنت متأكد من اعتماد هذا التوزيع؟ لن يمكن التعديل عليه بعد الاعتماد.',
                ]) ?>
            <?php endif ?>
        </div>
    </div>

</div>
