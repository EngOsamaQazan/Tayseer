<?php
/**
 * Sidebar — Vuexy vertical menu layout
 */

use yii\helpers\Html;
use yii\helpers\Url;
use common\components\CompanyChecked;

$CompanyChecked = new CompanyChecked();
$primary_company = $CompanyChecked->findPrimaryCompany();
if ($primary_company == '') {
    $logo = Yii::$app->params['companies_logo'];
    $companyName = 'تيسير';
} else {
    $logo = $primary_company->logo;
    $companyName = !empty($primary_company->name) ? $primary_company->name : 'تيسير';
}

Yii::$app->view->registerJsVar('base_url', Yii::$app->request->hostInfo . Yii::$app->getUrlManager()->getBaseUrl());

$menuItems = require '_menu_items.php';
$currentUrl = '/' . ltrim(Yii::$app->request->getPathInfo(), '/');

$faMap = [
    'users' => 'fa-users', 'file-text' => 'fa-file-lines', 'phone' => 'fa-phone',
    'money' => 'fa-money-bill', 'gavel' => 'fa-gavel', 'handshake-o' => 'fa-handshake',
    'bar-chart' => 'fa-chart-bar', 'id-card' => 'fa-id-card', 'tachometer' => 'fa-gauge-high',
    'clock-o' => 'fa-clock', 'calendar' => 'fa-calendar', 'star-half-o' => 'fa-star-half-stroke',
    'cubes' => 'fa-cubes', 'building' => 'fa-building', 'archive' => 'fa-box-archive',
    'shield' => 'fa-shield-halved', 'user-circle' => 'fa-circle-user', 'cogs' => 'fa-gears',
];

function renderMenuIcon($icon, $faMap) {
    $class = isset($faMap[$icon]) ? $faMap[$icon] : 'fa-' . $icon;
    return '<i class="menu-icon fa-solid ' . $class . '"></i>';
}

function isMenuActive($item, $currentUrl) {
    if (isset($item['url']) && is_array($item['url'])) {
        $url = Url::to($item['url']);
        if ($currentUrl === $url || strpos($currentUrl, $url) === 0) return true;
    }
    if (!empty($item['items'])) {
        foreach ($item['items'] as $sub) {
            if (isMenuActive($sub, $currentUrl)) return true;
        }
    }
    return false;
}
?>

<aside id="layout-menu" class="layout-menu menu-vertical menu" data-bs-theme="dark">

    <!-- Brand -->
    <div class="app-brand demo">
        <a href="<?= Yii::$app->homeUrl ?>" class="app-brand-link">
            <span class="app-brand-logo demo">
                <?php if (!empty($logo) && $logo !== Yii::$app->params['companies_logo']): ?>
                    <img src="/<?= Html::encode($logo) ?>" alt="<?= Html::encode($companyName) ?>" style="max-height:32px;max-width:32px;object-fit:contain">
                <?php else: ?>
                    <span class="d-flex align-items-center justify-content-center rounded bg-primary text-white fw-bold" style="width:32px;height:32px;font-size:16px"><?= mb_substr($companyName, 0, 1) ?></span>
                <?php endif ?>
            </span>
            <span class="app-brand-text demo menu-text fw-bold ms-3"><?= Html::encode($companyName) ?></span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="fa-solid fa-angles-left d-none d-xl-block" style="font-size:14px"></i>
            <i class="fa-solid fa-xmark d-block d-xl-none" style="font-size:16px"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <!-- Menu -->
    <ul class="menu-inner py-1">
        <?php foreach ($menuItems as $item): ?>
            <?php if (isset($item['visible']) && !$item['visible']) continue; ?>

            <?php if (!empty($item['header'])): ?>
                <li class="menu-header small text-uppercase">
                    <span class="menu-header-text"><?= Html::encode($item['label']) ?></span>
                </li>

            <?php elseif (!empty($item['items'])): ?>
                <?php $subActive = isMenuActive($item, $currentUrl); ?>
                <li class="menu-item<?= $subActive ? ' active open' : '' ?>">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <?= renderMenuIcon($item['icon'] ?? 'circle', $faMap) ?>
                        <div><?= Html::encode($item['label']) ?></div>
                    </a>
                    <ul class="menu-sub">
                        <?php foreach ($item['items'] as $sub): ?>
                            <?php
                            if (isset($sub['visible']) && !$sub['visible']) continue;
                            $subUrl = isset($sub['url']) ? Url::to($sub['url']) : '#';
                            $isSubActive = isset($sub['url']) && ($currentUrl === $subUrl || strpos($currentUrl, $subUrl) === 0);
                            ?>
                            <li class="menu-item<?= $isSubActive ? ' active' : '' ?>">
                                <a href="<?= $subUrl ?>" class="menu-link">
                                    <div><?= Html::encode($sub['label']) ?></div>
                                </a>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </li>

            <?php else: ?>
                <?php
                $url = isset($item['url']) ? Url::to($item['url']) : '#';
                $active = isMenuActive($item, $currentUrl);
                ?>
                <li class="menu-item<?= $active ? ' active' : '' ?>">
                    <a href="<?= $url ?>" class="menu-link">
                        <?= renderMenuIcon($item['icon'] ?? 'circle', $faMap) ?>
                        <div><?= Html::encode($item['label']) ?></div>
                    </a>
                </li>
            <?php endif ?>

        <?php endforeach ?>
    </ul>
</aside>
