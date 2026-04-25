<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  Inventory Tabs Bar — Pro Redesign
 *  Tayseer ERP — نظام تيسير
 *  Replaces .fin-tabs-bar with .inv-tabs-pro (gradient brand active state)
 * ═══════════════════════════════════════════════════════════════
 */
use yii\helpers\Url;
use common\helper\Permissions;

$u = Yii::$app->user;

$tabs = [];

if ($u->can(Permissions::INVENTORY_ITEMS) || $u->can(Permissions::INVENTORY_INVOICES)) {
    $tabs[] = [
        'id'    => 'dashboard',
        'label' => 'لوحة التحكم',
        'icon'  => 'fa-tachometer',
        'url'   => Url::to(['/inventoryItems/inventory-items/index']),
    ];
}

if ($u->can(Permissions::INVENTORY_INVOICES)) {
    $tabs[] = [
        'id'    => 'invoices',
        'label' => 'أوامر الشراء',
        'icon'  => 'fa-shopping-cart',
        'url'   => Url::to(['/inventoryInvoices/inventory-invoices/index']),
    ];
}

if ($u->can(Permissions::INVENTORY_ITEMS) || $u->can(Permissions::INVENTORY_ITEMS_QUANTITY)) {
    $tabs[] = [
        'id'    => 'movements',
        'label' => 'حركات المخزون',
        'icon'  => 'fa-exchange',
        'url'   => Url::to(['/inventoryItems/inventory-items/movements']),
    ];
}

if ($u->can(Permissions::INVENTORY_ITEMS)) {
    $tabs[] = [
        'id'    => 'items',
        'label' => 'الأصناف',
        'icon'  => 'fa-cube',
        'url'   => Url::to(['/inventoryItems/inventory-items/items']),
    ];
}

if ($u->can(Permissions::INVENTORY_ITEMS)) {
    $tabs[] = [
        'id'    => 'serials',
        'label' => 'الأرقام التسلسلية',
        'icon'  => 'fa-barcode',
        'url'   => Url::to(['/inventoryItems/inventory-items/serial-numbers']),
    ];
}

if ($u->can(Permissions::INVENTORY_SUPPLIERS) || $u->can(Permissions::INVENTORY_STOCK_LOCATIONS)) {
    $tabs[] = [
        'id'    => 'settings',
        'label' => 'الإعدادات',
        'icon'  => 'fa-cog',
        'url'   => Url::to(['/inventoryItems/inventory-items/settings']),
    ];
}

if (count($tabs) <= 1) return;

$baseUrl = Yii::$app->request->baseUrl;
$this->registerCssFile($baseUrl . '/css/inv-items-pro.css?v=2');
?>

<nav class="inv-tabs-pro" aria-label="إدارة المخزون" role="tablist">
    <?php foreach ($tabs as $tab): ?>
        <a href="<?= $tab['url'] ?>"
           class="inv-tab-pro <?= ($activeTab === $tab['id']) ? 'inv-tab-pro--active' : '' ?>"
           role="tab"
           <?= ($activeTab === $tab['id']) ? 'aria-current="page" aria-selected="true"' : 'aria-selected="false"' ?>>
            <i class="fa <?= $tab['icon'] ?>" aria-hidden="true"></i>
            <span><?= $tab['label'] ?></span>
        </a>
    <?php endforeach ?>
</nav>
