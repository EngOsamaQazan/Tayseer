<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  شريط تبويبات الإدارة المالية — يُعرض في أعلى الشاشات الثلاث
 *  ─────────────────────────────────────────────────────────────────
 *  يفحص صلاحية كل تبويب قبل إظهاره
 *  المتغيرات المطلوبة:
 *    $activeTab — 'transactions' | 'payments' | 'expenses'
 * ═══════════════════════════════════════════════════════════════════
 */

use yii\helpers\Url;
use common\helper\Permissions;

$u = Yii::$app->user;

/* ═══ تعريف التبويبات — كل تبويب مرتبط بصلاحيته ═══ */
$tabs = [];

if ($u->can(Permissions::FINANCIAL_TRANSACTION)) {
    $tabs[] = [
        'id'    => 'transactions',
        'label' => 'الحركات المالية',
        'icon'  => 'fa-exchange',
        'url'   => Url::to(['/financialTransaction/financial-transaction/index']),
    ];
}

if ($u->can(Permissions::INCOME)) {
    $tabs[] = [
        'id'    => 'payments',
        'label' => 'الدفعات',
        'icon'  => 'fa-money',
        'url'   => Url::to(['/income/income/income-list']),
    ];
}

if ($u->can(Permissions::EXPENSES)) {
    $tabs[] = [
        'id'    => 'expenses',
        'label' => 'المصاريف',
        'icon'  => 'fa-credit-card',
        'url'   => Url::to(['/expenses/expenses/index']),
    ];
}

if ($u->can(Permissions::LOAN_SCHEDULING)) {
    $tabs[] = [
        'id'    => 'settlements',
        'label' => 'التسويات',
        'icon'  => 'fa-balance-scale',
        'url'   => Url::to(['/loanScheduling/loan-scheduling/index']),
    ];
}

/* لا نعرض الشريط إذا المستخدم يملك صلاحية واحدة فقط */
if (count($tabs) <= 1) return;
?>

<nav class="fin-tabs-bar" aria-label="الإدارة المالية">
    <?php foreach ($tabs as $tab): ?>
        <a href="<?= $tab['url'] ?>"
           class="fin-tab <?= ($activeTab === $tab['id']) ? 'fin-tab--active' : '' ?>"
           <?= ($activeTab === $tab['id']) ? 'aria-current="page"' : '' ?>>
            <i class="fa <?= $tab['icon'] ?>"></i>
            <span><?= $tab['label'] ?></span>
        </a>
    <?php endforeach ?>
</nav>
