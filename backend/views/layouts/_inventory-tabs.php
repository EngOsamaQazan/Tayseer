<?php
/**
 * شريط تبويبات إدارة المخزون v2 — تصميم احترافي
 * التبويبات: لوحة التحكم | أوامر الشراء | حركات المخزون | الأصناف | الإعدادات
 */

use yii\helpers\Url;
use common\helper\Permissions;

$u = Yii::$app->user;

$tabs = [];

/* لوحة التحكم — متاحة لأي صلاحية مخزون */
if ($u->can(Permissions::INVENTORY_ITEMS) || $u->can(Permissions::INVENTORY_INVOICES)) {
    $tabs[] = [
        'id'    => 'dashboard',
        'label' => 'لوحة التحكم',
        'icon'  => 'fa-tachometer',
        'url'   => Url::to(['/inventoryItems/inventory-items/index']),
    ];
}

/* أوامر الشراء */
if ($u->can(Permissions::INVENTORY_INVOICES)) {
    $tabs[] = [
        'id'    => 'invoices',
        'label' => 'أوامر الشراء',
        'icon'  => 'fa-shopping-cart',
        'url'   => Url::to(['/inventoryInvoices/inventory-invoices/index']),
    ];
}

/* حركات المخزون */
if ($u->can(Permissions::INVENTORY_ITEMS) || $u->can(Permissions::INVENTORY_ITEMS_QUANTITY)) {
    $tabs[] = [
        'id'    => 'movements',
        'label' => 'حركات المخزون',
        'icon'  => 'fa-exchange',
        'url'   => Url::to(['/inventoryItems/inventory-items/movements']),
    ];
}

/* الأصناف */
if ($u->can(Permissions::INVENTORY_ITEMS)) {
    $tabs[] = [
        'id'    => 'items',
        'label' => 'الأصناف',
        'icon'  => 'fa-cube',
        'url'   => Url::to(['/inventoryItems/inventory-items/items']),
    ];
}

/* الأرقام التسلسلية */
if ($u->can(Permissions::INVENTORY_ITEMS)) {
    $tabs[] = [
        'id'    => 'serials',
        'label' => 'الأرقام التسلسلية',
        'icon'  => 'fa-barcode',
        'url'   => Url::to(['/inventoryItems/inventory-items/serial-numbers']),
    ];
}

/* الإعدادات — الموردين + المواقع */
if ($u->can(Permissions::INVENTORY_SUPPLIERS) || $u->can(Permissions::INVENTORY_STOCK_LOCATIONS)) {
    $tabs[] = [
        'id'    => 'settings',
        'label' => 'الإعدادات',
        'icon'  => 'fa-cog',
        'url'   => Url::to(['/inventoryItems/inventory-items/settings']),
    ];
}

if (count($tabs) <= 1) return;
?>

<nav class="fin-tabs-bar" aria-label="إدارة المخزون">
    <?php foreach ($tabs as $tab): ?>
        <a href="<?= $tab['url'] ?>"
           class="fin-tab <?= ($activeTab === $tab['id']) ? 'fin-tab--active' : '' ?>"
           <?= ($activeTab === $tab['id']) ? 'aria-current="page"' : '' ?>>
            <i class="fa <?= $tab['icon'] ?>"></i>
            <span><?= $tab['label'] ?></span>
        </a>
    <?php endforeach ?>
</nav>
