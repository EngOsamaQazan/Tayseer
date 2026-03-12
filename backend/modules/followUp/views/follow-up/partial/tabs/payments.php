<?php
use yii\helpers\Html;

$incomes = \backend\modules\income\models\Income::find()
    ->where(['contract_id' => $contract_id])
    ->orderBy(['date' => SORT_DESC])
    ->all();

$expenses = \backend\modules\expenses\models\Expenses::find()
    ->where(['contract_id' => $contract_id])
    ->orderBy(['expenses_date' => SORT_DESC])
    ->all();

$paymentTypeCache = [];
$getPaymentType = function($id) use (&$paymentTypeCache) {
    if (!$id) return 'غير محدد';
    if (!isset($paymentTypeCache[$id])) {
        $pt = \backend\modules\paymentType\models\PaymentType::findOne($id);
        $paymentTypeCache[$id] = $pt ? $pt->name : '#' . $id;
    }
    return $paymentTypeCache[$id];
};

$totalIncome = 0;
foreach ($incomes as $inc) $totalIncome += (float)$inc->amount;
$totalExpense = 0;
foreach ($expenses as $exp) $totalExpense += (float)$exp->amount;
?>

<style>
.pay-panel { direction:rtl;font-family:var(--ocp-font, 'Tajawal', sans-serif);font-size:13px; }

.pay-summary { display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap; }
.pay-summary-card {
    flex:1;min-width:140px;padding:14px 16px;border-radius:10px;text-align:center;
    border:1px solid #E2E8F0;background:#fff;
}
.pay-summary-card__val { font-size:20px;font-weight:800;font-family:'Courier New',monospace; }
.pay-summary-card__lbl { font-size:11px;color:#64748B;margin-top:2px; }

.pay-sec-title {
    display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;
    margin-bottom:10px;padding-bottom:6px;border-bottom:2px solid #E2E8F0;color:#334155;
}
.pay-sec-title i { font-size:14px; }
.pay-sec-title .pay-count {
    margin-right:auto;font-size:11px;font-weight:400;color:#94A3B8;
    background:#F1F5F9;padding:1px 8px;border-radius:10px;
}

.pay-list { display:flex;flex-direction:column;gap:6px;margin-bottom:18px; }
.pay-item {
    display:flex;align-items:center;gap:10px;padding:10px 14px;
    border-radius:10px;border:1px solid #E2E8F0;background:#fff;
    transition:all .15s;
}
.pay-item:hover { border-color:#CBD5E1;box-shadow:0 1px 4px rgba(0,0,0,.04); }

.pay-item__icon {
    width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;
    font-size:14px;flex-shrink:0;
}
.pay-item__icon--in { background:#DCFCE7;color:#16A34A; }
.pay-item__icon--out { background:#FEE2E2;color:#DC2626; }

.pay-item__body { flex:1;min-width:0; }
.pay-item__top { display:flex;align-items:center;gap:6px;flex-wrap:wrap; }
.pay-item__amount { font-size:15px;font-weight:800;font-family:'Courier New',monospace; }
.pay-item__amount--in { color:#16A34A; }
.pay-item__amount--out { color:#DC2626; }
.pay-item__type {
    font-size:10px;padding:1px 6px;border-radius:5px;font-weight:600;
    background:#F1F5F9;color:#475569;white-space:nowrap;
}
.pay-item__meta { display:flex;align-items:center;gap:8px;margin-top:3px;font-size:11px;color:#94A3B8;flex-wrap:wrap; }
.pay-item__meta i { font-size:10px; }

.pay-item__date {
    font-size:11px;color:#64748B;font-family:'Courier New',monospace;
    white-space:nowrap;flex-shrink:0;
}

.pay-empty {
    text-align:center;padding:24px;color:#94A3B8;font-size:12px;
    background:#FAFBFC;border-radius:10px;border:1px dashed #E2E8F0;
}
</style>

<div class="pay-panel">
    <div class="pay-summary">
        <div class="pay-summary-card" style="border-color:#DCFCE7">
            <div class="pay-summary-card__val" style="color:#16A34A">+<?= number_format($totalIncome) ?></div>
            <div class="pay-summary-card__lbl">إجمالي المدفوعات</div>
        </div>
        <div class="pay-summary-card" style="border-color:#FEE2E2">
            <div class="pay-summary-card__val" style="color:#DC2626">-<?= number_format($totalExpense) ?></div>
            <div class="pay-summary-card__lbl">إجمالي المصاريف</div>
        </div>
        <div class="pay-summary-card">
            <div class="pay-summary-card__val" style="color:#1E293B"><?= count($incomes) ?></div>
            <div class="pay-summary-card__lbl">عدد الدفعات</div>
        </div>
        <div class="pay-summary-card">
            <div class="pay-summary-card__val" style="color:#1E293B"><?= count($expenses) ?></div>
            <div class="pay-summary-card__lbl">عدد المصاريف</div>
        </div>
    </div>

    <div class="pay-sec-title" style="color:#16A34A;border-color:#DCFCE7">
        <i class="fa fa-arrow-down"></i> المدفوعات الواردة
        <span class="pay-count"><?= count($incomes) ?> سجل</span>
    </div>
    <?php if (empty($incomes)): ?>
        <div class="pay-empty"><i class="fa fa-inbox" style="font-size:18px;display:block;margin-bottom:6px"></i>لا توجد مدفوعات مسجلة</div>
    <?php else: ?>
    <div class="pay-list">
        <?php foreach ($incomes as $inc): ?>
        <div class="pay-item">
            <div class="pay-item__icon pay-item__icon--in"><i class="fa fa-arrow-down"></i></div>
            <div class="pay-item__body">
                <div class="pay-item__top">
                    <span class="pay-item__amount pay-item__amount--in">+<?= number_format((float)$inc->amount) ?></span>
                    <span class="pay-item__type"><?= Html::encode($getPaymentType($inc->payment_type)) ?></span>
                </div>
                <div class="pay-item__meta">
                    <?php if ($inc->_by): ?>
                        <span><i class="fa fa-user"></i> <?= Html::encode($inc->_by) ?></span>
                    <?php endif; ?>
                    <?php if ($inc->created && $inc->created->username): ?>
                        <span><i class="fa fa-pencil"></i> <?= Html::encode($inc->created->username) ?></span>
                    <?php endif; ?>
                    <?php if ($inc->notes): ?>
                        <span><i class="fa fa-comment-o"></i> <?= Html::encode(mb_substr($inc->notes, 0, 40)) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="pay-item__date"><?= $inc->date ? date('Y/m/d', strtotime($inc->date)) : '—' ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="pay-sec-title" style="color:#DC2626;border-color:#FEE2E2;margin-top:8px">
        <i class="fa fa-arrow-up"></i> المصاريف
        <span class="pay-count"><?= count($expenses) ?> سجل</span>
    </div>
    <?php if (empty($expenses)): ?>
        <div class="pay-empty"><i class="fa fa-inbox" style="font-size:18px;display:block;margin-bottom:6px"></i>لا توجد مصاريف مسجلة</div>
    <?php else: ?>
    <div class="pay-list">
        <?php foreach ($expenses as $exp): ?>
        <div class="pay-item">
            <div class="pay-item__icon pay-item__icon--out"><i class="fa fa-arrow-up"></i></div>
            <div class="pay-item__body">
                <div class="pay-item__top">
                    <span class="pay-item__amount pay-item__amount--out">-<?= number_format((float)$exp->amount) ?></span>
                    <?php if ($exp->category): ?>
                    <span class="pay-item__type"><?= Html::encode($exp->category->name) ?></span>
                    <?php endif; ?>
                    <?php if ($exp->document_number): ?>
                    <span class="pay-item__type" style="background:#EFF6FF;color:#2563EB">#<?= Html::encode($exp->document_number) ?></span>
                    <?php endif; ?>
                </div>
                <div class="pay-item__meta">
                    <?php if ($exp->description): ?>
                        <span><i class="fa fa-info-circle"></i> <?= Html::encode(mb_substr($exp->description, 0, 50)) ?></span>
                    <?php endif; ?>
                    <?php if ($exp->createdBy && $exp->createdBy->username): ?>
                        <span><i class="fa fa-pencil"></i> <?= Html::encode($exp->createdBy->username) ?></span>
                    <?php endif; ?>
                    <?php if ($exp->receiver_number): ?>
                        <span><i class="fa fa-hashtag"></i> <?= Html::encode($exp->receiver_number) ?></span>
                    <?php endif; ?>
                    <?php if ($exp->notes): ?>
                        <span><i class="fa fa-comment-o"></i> <?= Html::encode(mb_substr($exp->notes, 0, 40)) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="pay-item__date"><?= $exp->expenses_date ? date('Y/m/d', strtotime($exp->expenses_date)) : '—' ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
