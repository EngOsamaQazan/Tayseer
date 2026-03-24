<?php
use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $status string valid|expired|invalid */
/* @var $label string */
/* @var $message string */
/* @var $contract_id int|null */
/* @var $statementDate string|null */
/* @var $statementData array|null */

$this->title = 'تحقق من كشف الحساب';
$this->registerCssFile(Yii::getAlias('@web') . '/css/follow-up-statement.css', ['depends' => ['yii\web\YiiAsset']]);

$statusClass = [
    'valid'   => 'tayseer-verify--valid',
    'expired' => 'tayseer-verify--expired',
    'invalid' => 'tayseer-verify--invalid',
][$status] ?? 'tayseer-verify--invalid';

$statusIcon = [
    'valid'   => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0F7B3D" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
    'expired' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#b8860b" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    'invalid' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#B42318" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
][$status] ?? '';

$fmtNum = function ($n) {
    if ($n === null || $n === '' || !is_numeric($n)) return $n;
    return number_format((float) $n, 2, '.', ',');
};
?>
<div class="tayseer-statement tayseer-verify <?= $statusClass ?>">

    <!-- Status Banner -->
    <div class="tv-banner">
        <div class="tv-banner__icon"><?= $statusIcon ?></div>
        <div class="tv-banner__content">
            <h1 class="tv-banner__title">تحقق من كشف الحساب</h1>
            <p class="tv-banner__label"><?= Html::encode($label) ?></p>
            <p class="tv-banner__message"><?= Html::encode($message) ?></p>
        </div>
    </div>

    <?php if (!empty($statementData)): ?>

    <!-- Verifier Warning -->
    <div class="tv-alert">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <p>تحقق من مطابقة البيانات أدناه مع الكشف المقدم لك. في حال وجود أي اختلاف، يُعتبر الكشف مزوراً.</p>
    </div>

    <!-- Statement Header -->
    <div class="tv-header">
        <div class="tv-header__company"><?= Html::encode($statementData['companyName']) ?></div>
        <div class="tv-header__meta">
            <span>رقم العقد: <strong class="en"><?= (int) $contract_id ?></strong></span>
            <?php if (!empty($statementDate)): ?>
            <span class="tv-header__sep">|</span>
            <span>تاريخ الإصدار: <strong class="en"><?= Html::encode($statementDate) ?></strong></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Contract Info -->
    <div class="tv-info">
        <div class="tv-info__group">
            <div class="tv-info__row">
                <span class="tv-info__label">اسم العميل</span>
                <span class="tv-info__value"><?= Html::encode(implode(' ، ', $statementData['clientNames'])) ?></span>
            </div>
            <?php if (!empty($statementData['guarantorNames']) && implode('', $statementData['guarantorNames'])): ?>
            <div class="tv-info__row">
                <span class="tv-info__label">أسماء الكفلاء</span>
                <span class="tv-info__value"><?= Html::encode(implode(' ، ', $statementData['guarantorNames'])) ?></span>
            </div>
            <?php endif; ?>
            <div class="tv-info__row">
                <span class="tv-info__label">تاريخ البيع</span>
                <span class="tv-info__value en"><?= Html::encode($statementData['dateSale']) ?></span>
            </div>
            <div class="tv-info__row">
                <span class="tv-info__label">القسط الشهري</span>
                <span class="tv-info__value en"><?= $fmtNum($statementData['monthlyInst']) ?></span>
            </div>
        </div>
    </div>

    <!-- Financial Summary Cards -->
    <div class="tv-cards">
        <div class="tv-card tv-card--neutral">
            <span class="tv-card__label">إجمالي العقد</span>
            <span class="tv-card__amount en"><?= $fmtNum($statementData['totalValue']) ?></span>
        </div>
        <div class="tv-card tv-card--success">
            <span class="tv-card__label">المدفوع</span>
            <span class="tv-card__amount en"><?= $fmtNum($statementData['paidAmount']) ?></span>
        </div>
        <div class="tv-card tv-card--danger">
            <span class="tv-card__label">المتبقي</span>
            <span class="tv-card__amount en"><?= $fmtNum($statementData['remainingBalance']) ?></span>
        </div>
    </div>

    <!-- Movements Table -->
    <?php if (!empty($statementData['movements'])): ?>
    <div class="tv-section">
        <h3 class="tv-section__title">الحركات المالية</h3>
        <div class="tv-table-wrap">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>التاريخ</th>
                        <th>البيان</th>
                        <th>مدين</th>
                        <th>دائن</th>
                        <th>الرصيد</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $runningBalance = 0;
                    $rowIndex = 0;
                    foreach ($statementData['movements'] as $m):
                        $rowIndex++;
                        $amount = (float)($m['amount'] ?? 0);
                        $isDebit  = ($m['type'] ?? '') === 'مدين';
                        $isCredit = ($m['type'] ?? '') === 'دائن';
                        if ($isDebit)  $runningBalance += $amount;
                        if ($isCredit) $runningBalance -= $amount;
                        $dateStr = !empty($m['date']) ? substr($m['date'], 0, 10) : '';
                    ?>
                    <tr>
                        <td class="en"><?= $rowIndex ?></td>
                        <td class="en"><?= Html::encode($dateStr) ?: 'غير محدد' ?></td>
                        <td><?= Html::encode($m['description'] ?? '') ?><?php if (!empty($m['notes'])): ?> <small>(<?= Html::encode($m['notes']) ?>)</small><?php endif; ?></td>
                        <td class="en"><?= $isDebit  ? $fmtNum($amount) : '' ?></td>
                        <td class="en"><?= $isCredit ? $fmtNum($amount) : '' ?></td>
                        <td class="en"><strong><?= $fmtNum($runningBalance) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="tv-summary">
            <span>الرصيد النهائي: <strong class="en"><?= $fmtNum($runningBalance) ?></strong></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer Note -->
    <div class="tv-footer">
        <p>هذا الكشف موثق إلكترونياً عبر نظام تيسير ERP.</p>
        <p><?= Html::encode($statementData['companyName']) ?> مسؤولة عن صحة بيانات هذا الكشف حتى تاريخه.</p>
    </div>

    <?php endif; ?>

</div>
