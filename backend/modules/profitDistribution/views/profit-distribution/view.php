<?php

use yii\helpers\Url;
use yii\helpers\Html;
use backend\modules\profitDistribution\models\ProfitDistributionModel;
use backend\modules\profitDistribution\models\ProfitDistributionLine;
use backend\modules\companies\models\Companies;
use common\helper\Permissions;

/** @var yii\web\View $this */
/** @var backend\modules\profitDistribution\models\ProfitDistributionModel $model */

$this->title = 'عرض التوزيع #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'توزيع الأرباح', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCss('.content-header,.page-header { display: none !important; }');

$canManage = Permissions::can('المستثمرين');
$isPortfolio = $model->distribution_type === ProfitDistributionModel::TYPE_PORTFOLIO;
$isShareholders = $model->distribution_type === ProfitDistributionModel::TYPE_SHAREHOLDERS;
$lines = $model->lines;
?>

<style>
:root {
    --pd-primary: #059669;
    --pd-primary-light: #d1fae5;
    --pd-border: #e2e8f0;
    --pd-bg: #f8fafc;
    --pd-r: 12px;
    --pd-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}
.pd-view { max-width: 1000px; margin: 0 auto; padding: 24px; font-family: 'Segoe UI', Tahoma, sans-serif; }

.pd-view-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
.pd-view-header h1 { font-size: 22px; font-weight: 700; color: #1e293b; margin: 0; }
.pd-back { color: #64748b; text-decoration: none; font-size: 13px; margin-right: auto; display: flex; align-items: center; gap: 4px; }
.pd-back:hover { color: #1e293b; }

.pd-badge { display: inline-block; font-size: 11px; font-weight: 600; padding: 3px 12px; border-radius: 6px; }
.pd-badge-portfolio { background: #dbeafe; color: #1d4ed8; }
.pd-badge-shareholders { background: #f3e8ff; color: #7c3aed; }
.pd-badge-draft { background: #fef3c7; color: #b45309; }
.pd-badge-approved { background: #dcfce7; color: #15803d; }
.pd-badge-distributed { background: #d1fae5; color: #059669; }

.pd-card { background: #fff; border-radius: var(--pd-r); box-shadow: var(--pd-shadow); border: 1px solid var(--pd-border); margin-bottom: 20px; overflow: hidden; }
.pd-card-head { background: var(--pd-bg); padding: 14px 20px; border-bottom: 1px solid var(--pd-border); font-size: 14px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; }
.pd-card-head i { color: var(--pd-primary); }
.pd-card-body { padding: 20px; }

.pd-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
.pd-info-item { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; }
.pd-info-item:nth-child(odd) { border-left: 1px solid #f1f5f9; }
.pd-info-label { font-size: 12px; color: #94a3b8; margin-bottom: 2px; }
.pd-info-value { font-size: 14px; color: #1e293b; font-weight: 500; }

.pd-pnl-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-bottom: 1px solid #f1f5f9; }
.pd-pnl-row:last-child { border-bottom: none; }
.pd-pnl-label { font-size: 14px; color: #475569; display: flex; align-items: center; gap: 8px; }
.pd-pnl-value { font-size: 16px; font-weight: 700; }
.pd-pnl-green { color: #059669; }
.pd-pnl-red { color: #dc2626; }
.pd-pnl-bold { background: #f0fdf4; }
.pd-pnl-bold .pd-pnl-label { font-weight: 700; color: #1e293b; font-size: 15px; }
.pd-pnl-bold .pd-pnl-value { font-size: 20px; }
.pd-pnl-split { background: var(--pd-bg); }

.pd-roi-card { background: linear-gradient(135deg, #059669, #047857); color: #fff; border-radius: var(--pd-r); padding: 24px; text-align: center; margin-bottom: 20px; }
.pd-roi-label { font-size: 14px; opacity: .85; }
.pd-roi-value { font-size: 36px; font-weight: 700; margin: 6px 0; }
.pd-roi-sub { font-size: 12px; opacity: .7; }

.pd-lines-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.pd-lines-table thead th { background: var(--pd-bg); padding: 12px 16px; font-weight: 600; color: #475569; border-bottom: 2px solid var(--pd-border); text-align: right; font-size: 12px; text-transform: uppercase; }
.pd-lines-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.pd-lines-table tbody tr:hover { background: #f0fdf4; }
.pd-lines-table tbody td { padding: 12px 16px; color: #334155; vertical-align: middle; }
.pd-lines-name { font-weight: 600; color: #1e293b; }
.pd-lines-pct { color: #7c3aed; font-weight: 600; }
.pd-lines-amount { color: var(--pd-primary); font-weight: 700; }

.pd-payment-pending { display: inline-block; background: #fef3c7; color: #b45309; font-size: 11px; font-weight: 600; padding: 2px 10px; border-radius: 6px; }
.pd-payment-paid { display: inline-block; background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 600; padding: 2px 10px; border-radius: 6px; }

.pd-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; transition: all .2s; text-decoration: none !important; }
.pd-btn-primary { background: var(--pd-primary); color: #fff !important; }
.pd-btn-primary:hover { background: #047857; }
.pd-btn-warning { background: #f59e0b; color: #fff !important; }
.pd-btn-warning:hover { background: #d97706; }
.pd-btn-sm { padding: 5px 12px; font-size: 12px; }
.pd-btn-outline { background: #fff; color: #475569 !important; border: 1px solid var(--pd-border); }
.pd-btn-outline:hover { background: var(--pd-bg); }

.pd-actions-bar { display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap; margin-bottom: 20px; }

.pd-notes { padding: 16px 20px; }
.pd-notes-title { font-size: 13px; color: #94a3b8; margin-bottom: 6px; }
.pd-notes-text { font-size: 14px; color: #334155; line-height: 1.7; white-space: pre-wrap; }

.pd-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,.4); z-index: 9999; align-items: center; justify-content: center; }
.pd-modal-overlay.active { display: flex; }
.pd-modal { background: #fff; border-radius: var(--pd-r); padding: 24px; width: 400px; max-width: 90vw; box-shadow: 0 20px 60px rgba(0,0,0,.15); }
.pd-modal h3 { margin: 0 0 16px; font-size: 16px; color: #1e293b; }
.pd-modal .form-group { margin-bottom: 12px; }
.pd-modal .form-group label { font-size: 13px; color: #475569; margin-bottom: 4px; display: block; }
.pd-modal .form-group input { width: 100%; border: 1px solid var(--pd-border); border-radius: 8px; padding: 8px 12px; font-size: 13px; }
.pd-modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 16px; }

@media (max-width: 768px) {
    .pd-view { padding: 12px; }
    .pd-info-grid { grid-template-columns: 1fr; }
}
</style>

<div class="pd-view">

    <div class="pd-view-header">
        <h1>
            <i class="fa fa-<?= $isPortfolio ? 'briefcase' : 'users' ?>" style="color:var(--pd-primary)"></i>
            <?= $this->title ?>
        </h1>
        <?php
        $typeBadge = $isPortfolio
            ? '<span class="pd-badge pd-badge-portfolio">محفظة</span>'
            : '<span class="pd-badge pd-badge-shareholders">مساهمين</span>';
        $statusBadge = '';
        if ($model->status === 'مسودة') $statusBadge = '<span class="pd-badge pd-badge-draft">مسودة</span>';
        elseif ($model->status === 'معتمد') $statusBadge = '<span class="pd-badge pd-badge-approved">معتمد</span>';
        elseif ($model->status === 'موزّع') $statusBadge = '<span class="pd-badge pd-badge-distributed">موزّع</span>';
        ?>
        <?= $typeBadge ?> <?= $statusBadge ?>
        <?= Html::a('<i class="fa fa-arrow-right"></i> العودة', ['index'], ['class' => 'pd-back']) ?>
    </div>

    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div style="background:#dcfce7;color:#15803d;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px;border:1px solid #bbf7d0">
            <i class="fa fa-check-circle"></i> <?= Yii::$app->session->getFlash('success') ?>
        </div>
    <?php endif ?>
    <?php if (Yii::$app->session->hasFlash('warning')): ?>
        <div style="background:#fef3c7;color:#b45309;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px;border:1px solid #fde68a">
            <i class="fa fa-exclamation-triangle"></i> <?= Yii::$app->session->getFlash('warning') ?>
        </div>
    <?php endif ?>
    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div style="background:#fef2f2;color:#dc2626;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px;border:1px solid #fecaca">
            <i class="fa fa-times-circle"></i> <?= Yii::$app->session->getFlash('error') ?>
        </div>
    <?php endif ?>

    <?php if ($canManage && $model->status === ProfitDistributionModel::STATUS_DRAFT): ?>
        <div class="pd-actions-bar">
            <?= Html::a('<i class="fa fa-check"></i> اعتماد التوزيع', ['approve', 'id' => $model->id], [
                'class' => 'pd-btn pd-btn-primary',
                'data-method' => 'post',
                'data-confirm' => 'هل أنت متأكد من اعتماد هذا التوزيع؟',
            ]) ?>
        </div>
    <?php endif ?>

    <!-- Basic Info -->
    <div class="pd-card">
        <div class="pd-card-head"><i class="fa fa-info-circle"></i> بيانات التوزيع</div>
        <div class="pd-info-grid">
            <div class="pd-info-item">
                <div class="pd-info-label">نوع التوزيع</div>
                <div class="pd-info-value"><?= Html::encode($model->distribution_type) ?></div>
            </div>
            <div class="pd-info-item">
                <div class="pd-info-label">الحالة</div>
                <div class="pd-info-value"><?= $statusBadge ?></div>
            </div>
            <?php if ($isPortfolio && $model->company): ?>
                <div class="pd-info-item">
                    <div class="pd-info-label">المحفظة / المُستثمر</div>
                    <div class="pd-info-value"><?= Html::encode($model->company->name) ?></div>
                </div>
            <?php endif ?>
            <div class="pd-info-item">
                <div class="pd-info-label">الفترة</div>
                <div class="pd-info-value"><?= Html::encode($model->period_from) ?> — <?= Html::encode($model->period_to) ?></div>
            </div>
            <div class="pd-info-item">
                <div class="pd-info-label">أنشئ بواسطة</div>
                <div class="pd-info-value"><?= Html::encode($model->createdByUser->username ?? '—') ?></div>
            </div>
            <div class="pd-info-item">
                <div class="pd-info-label">تاريخ الإنشاء</div>
                <div class="pd-info-value"><?= $model->created_at ? date('Y-m-d H:i', $model->created_at) : '—' ?></div>
            </div>
            <?php if ($model->approved_by): ?>
                <div class="pd-info-item">
                    <div class="pd-info-label">اعتمد بواسطة</div>
                    <div class="pd-info-value"><?= Html::encode($model->approvedByUser->username ?? '—') ?></div>
                </div>
                <div class="pd-info-item">
                    <div class="pd-info-label">تاريخ الاعتماد</div>
                    <div class="pd-info-value"><?= $model->approved_at ? date('Y-m-d H:i', $model->approved_at) : '—' ?></div>
                </div>
            <?php endif ?>
        </div>
        <?php if (!empty($model->notes)): ?>
            <div class="pd-notes">
                <div class="pd-notes-title">ملاحظات</div>
                <div class="pd-notes-text"><?= Html::encode($model->notes) ?></div>
            </div>
        <?php endif ?>
    </div>

    <?php if ($isPortfolio): ?>
        <!-- P&L Breakdown -->
        <div class="pd-card">
            <div class="pd-card-head"><i class="fa fa-bar-chart"></i> قائمة الأرباح والخسائر</div>
            <div>
                <div class="pd-pnl-row">
                    <span class="pd-pnl-label"><i class="fa fa-arrow-down" style="color:#059669"></i> الإيرادات</span>
                    <span class="pd-pnl-value pd-pnl-green"><?= number_format((float) $model->total_revenue, 2) ?></span>
                </div>
                <div class="pd-pnl-row">
                    <span class="pd-pnl-label"><i class="fa fa-arrow-up" style="color:#dc2626"></i> المصاريف المباشرة</span>
                    <span class="pd-pnl-value pd-pnl-red">(<?= number_format((float) $model->direct_expenses, 2) ?>)</span>
                </div>
                <div class="pd-pnl-row">
                    <span class="pd-pnl-label"><i class="fa fa-arrow-up" style="color:#dc2626"></i> حصة المصاريف المشتركة</span>
                    <span class="pd-pnl-value pd-pnl-red">(<?= number_format((float) $model->shared_expenses, 2) ?>)</span>
                </div>
                <div class="pd-pnl-row pd-pnl-bold">
                    <span class="pd-pnl-label"><i class="fa fa-line-chart"></i> صافي الربح</span>
                    <span class="pd-pnl-value <?= (float) $model->net_profit >= 0 ? 'pd-pnl-green' : 'pd-pnl-red' ?>">
                        <?= number_format((float) $model->net_profit, 2) ?>
                    </span>
                </div>
                <div class="pd-pnl-row pd-pnl-split">
                    <span class="pd-pnl-label"><i class="fa fa-user" style="color:#2563eb"></i> حصة المُستثمر (<?= (float) $model->investor_share_pct ?>%)</span>
                    <span class="pd-pnl-value" style="color:#2563eb"><?= number_format((float) $model->investor_amount, 2) ?></span>
                </div>
                <div class="pd-pnl-row pd-pnl-split">
                    <span class="pd-pnl-label"><i class="fa fa-building" style="color:#7c3aed"></i> حصة الشركة الأم (<?= 100 - (float) $model->investor_share_pct ?>%)</span>
                    <span class="pd-pnl-value" style="color:#7c3aed"><?= number_format((float) $model->parent_amount, 2) ?></span>
                </div>
            </div>
        </div>

        <?php
        $investedCapital = $model->company ? (float) $model->company->invested_capital : 0;
        if ($investedCapital > 0):
            $roi = round(((float) $model->investor_amount / $investedCapital) * 100, 2);
        ?>
            <div class="pd-roi-card">
                <div class="pd-roi-label">العائد على الاستثمار (ROI)</div>
                <div class="pd-roi-value"><?= $roi ?>%</div>
                <div class="pd-roi-sub">حصة المُستثمر <?= number_format((float) $model->investor_amount, 2) ?> / رأس المال <?= number_format($investedCapital, 2) ?></div>
            </div>
        <?php endif ?>
    <?php endif ?>

    <?php if ($isShareholders && !empty($lines)): ?>
        <!-- Shareholder Distribution Lines -->
        <div class="pd-card">
            <div class="pd-card-head"><i class="fa fa-users"></i> تفاصيل التوزيع على المساهمين</div>
            <div style="overflow-x:auto">
                <table class="pd-lines-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>المساهم</th>
                            <th>عدد الأسهم</th>
                            <th>النسبة %</th>
                            <th>المبلغ المستحق</th>
                            <th>حالة الدفع</th>
                            <th>تاريخ الدفع</th>
                            <?php if ($canManage): ?><th style="width:100px">إجراءات</th><?php endif ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totalAmount = 0;
                        $paidCount = 0;
                        foreach ($lines as $i => $line):
                            $totalAmount += (float) $line->amount;
                            if ($line->payment_status === ProfitDistributionLine::PAYMENT_PAID) $paidCount++;
                        ?>
                            <tr>
                                <td style="color:#94a3b8;font-size:12px"><?= $i + 1 ?></td>
                                <td><span class="pd-lines-name"><?= Html::encode($line->shareholder->name ?? '—') ?></span></td>
                                <td><?= number_format((int) $line->share_count_snapshot) ?></td>
                                <td><span class="pd-lines-pct"><?= $line->percentage ?>%</span></td>
                                <td><span class="pd-lines-amount"><?= number_format((float) $line->amount, 2) ?></span></td>
                                <td>
                                    <?php if ($line->payment_status === ProfitDistributionLine::PAYMENT_PAID): ?>
                                        <span class="pd-payment-paid">مدفوع</span>
                                    <?php else: ?>
                                        <span class="pd-payment-pending">معلّق</span>
                                    <?php endif ?>
                                </td>
                                <td style="font-size:12px;color:#64748b"><?= Html::encode($line->payment_date ?: '—') ?></td>
                                <?php if ($canManage): ?>
                                    <td>
                                        <?php if ($line->payment_status !== ProfitDistributionLine::PAYMENT_PAID && $model->status === ProfitDistributionModel::STATUS_APPROVED): ?>
                                            <button type="button" class="pd-btn pd-btn-warning pd-btn-sm pay-btn" data-line-id="<?= $line->id ?>" data-name="<?= Html::encode($line->shareholder->name ?? '') ?>">
                                                <i class="fa fa-money"></i> تسديد
                                            </button>
                                        <?php endif ?>
                                    </td>
                                <?php endif ?>
                            </tr>
                        <?php endforeach ?>
                        <tr style="background:#f0fdf4;font-weight:700">
                            <td colspan="3"></td>
                            <td>الإجمالي</td>
                            <td><span class="pd-lines-amount"><?= number_format($totalAmount, 2) ?></span></td>
                            <td colspan="<?= $canManage ? 3 : 2 ?>">
                                <span style="font-size:12px;color:#64748b">مدفوع: <?= $paidCount ?> / <?= count($lines) ?></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif ?>

</div>

<!-- Payment Modal -->
<div class="pd-modal-overlay" id="payModal">
    <div class="pd-modal">
        <h3><i class="fa fa-money" style="color:var(--pd-primary)"></i> تسجيل دفع — <span id="payModalName"></span></h3>
        <form id="payForm" method="post">
            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
            <div class="form-group">
                <label>طريقة الدفع</label>
                <input type="text" name="payment_method" placeholder="مثال: تحويل بنكي، شيك...">
            </div>
            <div class="form-group">
                <label>مرجع الدفع</label>
                <input type="text" name="payment_reference" placeholder="رقم الحوالة أو الشيك...">
            </div>
            <div class="pd-modal-actions">
                <button type="button" class="pd-btn pd-btn-outline" onclick="$('#payModal').removeClass('active')">إلغاء</button>
                <button type="submit" class="pd-btn pd-btn-primary"><i class="fa fa-check"></i> تأكيد الدفع</button>
            </div>
        </form>
    </div>
</div>

<?php
$markPaidUrl = Url::to(['mark-paid', 'lineId' => '__ID__']);
$js = <<<JS
$('.pay-btn').on('click', function() {
    var lineId = $(this).data('line-id');
    var name = $(this).data('name');
    $('#payModalName').text(name);
    var url = '{$markPaidUrl}'.replace('__ID__', lineId);
    $('#payForm').attr('action', url);
    $('#payModal').addClass('active');
});
$('#payModal').on('click', function(e) {
    if (e.target === this) $(this).removeClass('active');
});
JS;
$this->registerJs($js, \yii\web\View::POS_READY);
?>
