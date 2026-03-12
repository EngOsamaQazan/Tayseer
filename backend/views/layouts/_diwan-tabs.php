<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  شريط تبويبات قسم الديوان — يُعرض في أعلى جميع شاشات الديوان
 *  ─────────────────────────────────────────────────────────────────
 *  يفحص صلاحية كل تبويب قبل إظهاره
 *  المتغيرات المطلوبة:
 *    $activeTab — 'dashboard' | 'create' | 'transactions' | 'search' | 'reports'
 * ═══════════════════════════════════════════════════════════════════
 */

use yii\helpers\Url;
use common\helper\Permissions;

$u = Yii::$app->user;

/* ═══ تعريف التبويبات — كل تبويب مرتبط بصلاحيته ═══ */
$tabs = [];

if ($u->can(Permissions::DIWAN)) {
    $tabs[] = [
        'id'    => 'dashboard',
        'label' => 'لوحة المعلومات',
        'icon'  => 'fa-tachometer',
        'url'   => Url::to(['/diwan/diwan/index']),
    ];
    $tabs[] = [
        'id'    => 'create',
        'label' => 'معاملة جديدة',
        'icon'  => 'fa-plus-circle',
        'url'   => Url::to(['/diwan/diwan/create']),
    ];
    $tabs[] = [
        'id'    => 'transactions',
        'label' => 'المعاملات',
        'icon'  => 'fa-exchange',
        'url'   => Url::to(['/diwan/diwan/transactions']),
    ];
    $tabs[] = [
        'id'    => 'search',
        'label' => 'بحث الوثائق',
        'icon'  => 'fa-search',
        'url'   => Url::to(['/diwan/diwan/search']),
    ];
}

if ($u->can(Permissions::DIWAN_REPORTS) || $u->can(Permissions::DIWAN)) {
    $tabs[] = [
        'id'    => 'reports',
        'label' => 'التقارير',
        'icon'  => 'fa-bar-chart',
        'url'   => Url::to(['/diwan/diwan/reports']),
    ];
}

/* لا نعرض الشريط إذا المستخدم يملك صلاحية واحدة فقط */
if (count($tabs) <= 1) return;
?>

<nav class="fin-tabs-bar" aria-label="قسم الديوان">
    <?php foreach ($tabs as $tab): ?>
        <a href="<?= $tab['url'] ?>"
           class="fin-tab <?= ($activeTab === $tab['id']) ? 'fin-tab--active' : '' ?>"
           <?= ($activeTab === $tab['id']) ? 'aria-current="page"' : '' ?>>
            <i class="fa <?= $tab['icon'] ?>"></i>
            <span><?= $tab['label'] ?></span>
        </a>
    <?php endforeach ?>
</nav>
