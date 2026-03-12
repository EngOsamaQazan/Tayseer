<?php

use yii\helpers\Html;
use common\helper\Permissions;

/** @var yii\web\View $this */
/** @var backend\modules\capitalTransactions\models\CapitalTransactions $model */

$this->title = 'عرض حركة رأس مال #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'المحافظ', 'url' => ['/companies/companies/index']];
if ($model->company) {
    $this->params['breadcrumbs'][] = ['label' => $model->company->name, 'url' => ['/companies/companies/view', 'id' => $model->company_id]];
    $this->params['breadcrumbs'][] = ['label' => 'حركات رأس المال', 'url' => ['index', 'company_id' => $model->company_id]];
}
$this->params['breadcrumbs'][] = 'عرض الحركة';

$canManage = Permissions::can('المستثمرين');

$isDeposit = $model->transaction_type === 'إيداع';
$isWithdraw = $model->transaction_type === 'سحب';
$isReturn = $model->transaction_type === 'إعادة_رأس_مال';

if ($isDeposit) {
    $typeBadge = '<span style="background:#dcfce7;color:#15803d;font-size:13px;font-weight:600;padding:4px 14px;border-radius:6px">إيداع</span>';
    $amountColor = '#059669';
    $amountPrefix = '+';
} elseif ($isWithdraw) {
    $typeBadge = '<span style="background:#fee2e2;color:#dc2626;font-size:13px;font-weight:600;padding:4px 14px;border-radius:6px">سحب</span>';
    $amountColor = '#dc2626';
    $amountPrefix = '-';
} else {
    $typeBadge = '<span style="background:#dbeafe;color:#1d4ed8;font-size:13px;font-weight:600;padding:4px 14px;border-radius:6px">إعادة رأس مال</span>';
    $amountColor = '#dc2626';
    $amountPrefix = '-';
}
?>

<style>
:root {
    --ct-primary: #f59e0b;
    --ct-border: #e2e8f0;
    --ct-bg: #f8fafc;
    --ct-r: 12px;
    --ct-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}
.ct-view { max-width: 900px; margin: 0 auto; padding: 20px; }
.ct-view-card { background: #fff; border-radius: var(--ct-r); box-shadow: var(--ct-shadow); border: 1px solid var(--ct-border); margin-bottom: 18px; overflow: hidden; }
.ct-view-header { display: flex; align-items: center; gap: 20px; padding: 24px; background: linear-gradient(135deg, #fffbeb, #fef3c7); border-bottom: 1px solid var(--ct-border); }
.ct-view-icon { width: 64px; height: 64px; border-radius: 16px; background: linear-gradient(135deg, #f59e0b, #d97706); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 26px; flex-shrink: 0; }
.ct-view-title { font-size: 20px; font-weight: 700; color: #1e293b; }
.ct-view-sub { font-size: 13px; color: #64748b; margin-top: 4px; }
.ct-view-amount { font-size: 28px; font-weight: 800; margin-top: 6px; }
.ct-view-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
.ct-view-item { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; }
.ct-view-item:nth-child(odd) { border-left: 1px solid #f1f5f9; }
.ct-view-label { font-size: 12px; color: #94a3b8; margin-bottom: 2px; }
.ct-view-value { font-size: 14px; color: #1e293b; font-weight: 500; }
.ct-view-notes { padding: 16px 20px; }
.ct-view-notes-title { font-size: 13px; color: #94a3b8; margin-bottom: 6px; }
.ct-view-notes-text { font-size: 14px; color: #334155; line-height: 1.7; white-space: pre-wrap; }
.ct-view-actions { padding: 16px 20px; display: flex; gap: 8px; justify-content: flex-end; background: var(--ct-bg); border-top: 1px solid var(--ct-border); }
.ct-view-actions .btn { border-radius: 8px; font-size: 13px; font-weight: 600; }
@media (max-width: 600px) { .ct-view-grid { grid-template-columns: 1fr; } }
</style>

<div class="ct-view">
    <div class="ct-view-card">
        <div class="ct-view-header">
            <div class="ct-view-icon"><i class="fa fa-exchange"></i></div>
            <div>
                <div class="ct-view-title">
                    حركة #<?= $model->id ?> — <?= $typeBadge ?>
                </div>
                <div class="ct-view-sub"><?= Html::encode($model->company ? $model->company->name : '—') ?></div>
                <div class="ct-view-amount" style="color:<?= $amountColor ?>"><?= $amountPrefix ?> <?= number_format($model->amount, 2) ?></div>
            </div>
        </div>

        <div class="ct-view-grid">
            <div class="ct-view-item">
                <div class="ct-view-label">المحفظة</div>
                <div class="ct-view-value"><?= Html::encode($model->company ? $model->company->name : '—') ?></div>
            </div>
            <div class="ct-view-item">
                <div class="ct-view-label">نوع العملية</div>
                <div class="ct-view-value"><?= $typeBadge ?></div>
            </div>
            <div class="ct-view-item">
                <div class="ct-view-label">المبلغ</div>
                <div class="ct-view-value" style="color:<?= $amountColor ?>;font-weight:700;font-size:16px"><?= $amountPrefix ?> <?= number_format($model->amount, 2) ?></div>
            </div>
            <div class="ct-view-item">
                <div class="ct-view-label">الرصيد بعد العملية</div>
                <div class="ct-view-value" style="font-weight:700"><?= $model->balance_after !== null ? number_format($model->balance_after, 2) : '—' ?></div>
            </div>
            <div class="ct-view-item">
                <div class="ct-view-label">التاريخ</div>
                <div class="ct-view-value"><?= Html::encode($model->transaction_date) ?></div>
            </div>
            <div class="ct-view-item">
                <div class="ct-view-label">طريقة الدفع</div>
                <div class="ct-view-value"><?= Html::encode($model->payment_method ?: '—') ?></div>
            </div>
            <div class="ct-view-item">
                <div class="ct-view-label">رقم المرجع</div>
                <div class="ct-view-value"><?= Html::encode($model->reference_number ?: '—') ?></div>
            </div>
            <div class="ct-view-item">
                <div class="ct-view-label">تاريخ الإنشاء</div>
                <div class="ct-view-value"><?= $model->created_at ? date('Y-m-d H:i', $model->created_at) : '—' ?></div>
            </div>
        </div>

        <?php if (!empty($model->notes)): ?>
            <div class="ct-view-notes">
                <div class="ct-view-notes-title">ملاحظات</div>
                <div class="ct-view-notes-text"><?= Html::encode($model->notes) ?></div>
            </div>
        <?php endif ?>

        <div class="ct-view-actions">
            <?= Html::a('<i class="fa fa-arrow-right"></i> العودة', ['index', 'company_id' => $model->company_id], ['class' => 'btn btn-default']) ?>
            <?php if ($canManage): ?>
                <?= Html::a('<i class="fa fa-pencil"></i> تعديل', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                <?= Html::a('<i class="fa fa-trash"></i> حذف', ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger',
                    'data-method' => 'post',
                    'data-confirm' => 'هل أنت متأكد من حذف هذه الحركة؟',
                ]) ?>
            <?php endif ?>
        </div>
    </div>
</div>
