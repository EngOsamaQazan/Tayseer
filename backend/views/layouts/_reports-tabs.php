<?php
/**
 * شريط تبويبات التقارير v2 — تصميم احترافي موحد
 */
use yii\helpers\Url;
use common\helper\Permissions;

$u = Yii::$app->user;

$tabs = [];

if ($u->can(Permissions::REPORTS)) {
    $tabs[] = ['id' => 'overview',  'label' => 'نظرة عامة',        'icon' => 'fa-tachometer',   'url' => Url::to(['/reports/reports/index'])];
    $tabs[] = ['id' => 'income',    'label' => 'الإيرادات',         'icon' => 'fa-money',        'url' => Url::to(['/reports/reports/total-customer-payments-index'])];
    $tabs[] = ['id' => 'followup',  'label' => 'تقارير المتابعة',   'icon' => 'fa-phone',        'url' => Url::to(['/reports/reports/index2'])];
    $tabs[] = ['id' => 'judiciary', 'label' => 'التقارير القضائية', 'icon' => 'fa-gavel',        'url' => Url::to(['/reports/reports/judiciary-index'])];
    $tabs[] = ['id' => 'actions',   'label' => 'الحركات القضائية', 'icon' => 'fa-balance-scale', 'url' => Url::to(['/reports/reports/customers-judiciary-actions'])];
}

if (count($tabs) <= 1) return;
?>

<nav class="fin-tabs-bar" aria-label="التقارير">
    <?php foreach ($tabs as $tab): ?>
        <a href="<?= $tab['url'] ?>"
           class="fin-tab <?= ($activeTab === $tab['id']) ? 'fin-tab--active' : '' ?>"
           <?= ($activeTab === $tab['id']) ? 'aria-current="page"' : '' ?>>
            <i class="fa <?= $tab['icon'] ?>"></i>
            <span><?= $tab['label'] ?></span>
        </a>
    <?php endforeach ?>
</nav>
