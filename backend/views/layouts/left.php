<?php
/**
 * Tabler — Vertical Sidebar
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

function renderIcon($icon, $faMap) {
    $class = isset($faMap[$icon]) ? $faMap[$icon] : 'fa-' . $icon;
    return '<span class="nav-link-icon d-md-none d-lg-inline-block"><i class="fa-solid ' . $class . '"></i></span>';
}

function isActive($item, $currentUrl) {
    if (isset($item['url']) && is_array($item['url'])) {
        $url = Url::to($item['url']);
        if ($currentUrl === $url || strpos($currentUrl, $url) === 0) return true;
    }
    if (!empty($item['items'])) {
        foreach ($item['items'] as $sub) {
            if (isActive($sub, $currentUrl)) return true;
        }
    }
    return false;
}
?>

<aside class="navbar navbar-vertical navbar-expand-lg tayseer-sidebar" data-bs-theme="dark">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand" href="<?= Yii::$app->homeUrl ?>" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            <?php if (!empty($logo) && $logo !== Yii::$app->params['companies_logo']): ?>
                <img src="/<?= Html::encode($logo) ?>" alt="<?= Html::encode($companyName) ?>"
                     class="navbar-brand-image rounded-circle"
                     style="width:32px;height:32px;object-fit:cover;flex-shrink:0">
            <?php else: ?>
                <span class="sidebar-brand-icon" style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#fff2,#fff1);color:#fff;font-weight:900;font-size:16px;font-family:'Cairo',sans-serif;flex-shrink:0"><?= mb_substr($companyName, 0, 1) ?></span>
            <?php endif ?>
            <span class="sidebar-brand-text" style="margin-right:8px;font-weight:700;font-size:14px;font-family:'Cairo',sans-serif;overflow:hidden;text-overflow:ellipsis"><?= Html::encode($companyName) ?></span>
        </a>

        <!-- Desktop: Hide sidebar toggle (visibility controlled by CSS media query) -->
        <a href="#" id="sidebarMiniToggle" title="إخفاء القائمة"
           style="align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;color:rgba(255,255,255,.5);transition:all .2s;flex-shrink:0;text-decoration:none">
            <i class="fa-solid fa-xmark" style="font-size:14px"></i>
        </a>

        <!-- Mobile toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
                aria-controls="sidebar-menu" aria-expanded="false" aria-label="القائمة">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Sidebar menu -->
        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                <?php foreach ($menuItems as $item): ?>
                    <?php if (!empty($item['header'])): ?>
                        <li class="nav-item nav-item-header pt-3 pb-1">
                            <span class="nav-link text-uppercase text-muted" style="font-size:11px;font-weight:700;letter-spacing:.5px;opacity:.6;cursor:default">
                                <?= Html::encode($item['label']) ?>
                            </span>
                        </li>
                    <?php elseif (!empty($item['items'])): ?>
                        <?php $subActive = isActive($item, $currentUrl); ?>
                        <li class="nav-item dropdown<?= $subActive ? ' active' : '' ?>">
                            <a class="nav-link dropdown-toggle<?= $subActive ? ' show' : '' ?>" href="#navbar-<?= md5($item['label']) ?>"
                               data-bs-toggle="dropdown" data-bs-auto-close="false"
                               role="button" aria-expanded="<?= $subActive ? 'true' : 'false' ?>">
                                <?= renderIcon($item['icon'] ?? 'circle', $faMap) ?>
                                <span class="nav-link-title"><?= Html::encode($item['label']) ?></span>
                            </a>
                            <div class="dropdown-menu<?= $subActive ? ' show' : '' ?>">
                                <?php foreach ($item['items'] as $sub): ?>
                                    <?php
                                    $subUrl = isset($sub['url']) ? Url::to($sub['url']) : '#';
                                    $isSubActive = isset($sub['url']) && ($currentUrl === $subUrl || strpos($currentUrl, $subUrl) === 0);
                                    ?>
                                    <a class="dropdown-item<?= $isSubActive ? ' active' : '' ?>" href="<?= $subUrl ?>">
                                        <?php if (!empty($sub['icon'])): ?>
                                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                <i class="fa-solid <?= isset($faMap[$sub['icon']]) ? $faMap[$sub['icon']] : 'fa-' . $sub['icon'] ?>"></i>
                                            </span>
                                        <?php endif ?>
                                        <?= Html::encode($sub['label']) ?>
                                    </a>
                                <?php endforeach ?>
                            </div>
                        </li>
                    <?php else: ?>
                        <?php
                        $url = isset($item['url']) ? Url::to($item['url']) : '#';
                        $active = isActive($item, $currentUrl);
                        ?>
                        <li class="nav-item<?= $active ? ' active' : '' ?>">
                            <a class="nav-link<?= $active ? ' active' : '' ?>" href="<?= $url ?>">
                                <?= renderIcon($item['icon'] ?? 'circle', $faMap) ?>
                                <span class="nav-link-title"><?= Html::encode($item['label']) ?></span>
                            </a>
                        </li>
                    <?php endif ?>
                <?php endforeach ?>
            </ul>

        </div>
    </div>
</aside>
