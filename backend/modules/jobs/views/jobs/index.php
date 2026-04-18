<?php
/**
 * شاشة جهات العمل — تصميم 2026 (إعادة تصميم كاملة)
 *  - رأس بطاقي مع KPIs مباشرة من قاعدة البيانات
 *  - شريط أدوات ذكي: بحث فوري + شرائح حالة + قائمة نوع + تبديل عرض
 *  - شبكة بيانات متجاوبة (جدول على الديسكتوب — بطاقات على الموبايل)
 *  - متوافق مع WCAG 2.2 AA + ISO 9241 + RTL Arabic
 *
 * @var yii\web\View $this
 * @var backend\modules\jobs\models\JobsSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use kartik\grid\GridView;
use backend\modules\jobs\models\JobsType;
use backend\modules\jobs\models\JobsSearch;
use backend\widgets\ExportButtons;

$this->title = 'جهات العمل';
$this->params['breadcrumbs'][] = $this->title;

// Use the file's own mtime for the CSS so editing jobs-index.css always
// busts the browser cache regardless of the global assetVersion bump.
$baseUrl  = Yii::$app->request->baseUrl;
$cssMtime = @filemtime(Yii::getAlias('@webroot') . '/css/jobs-index.css') ?: 1;
$jsMtime  = @filemtime(Yii::getAlias('@webroot') . '/js/jobs-index.js')  ?: 1;
$this->registerCssFile($baseUrl . '/css/jobs-index.css?v=' . $cssMtime);
$this->registerJsFile(
    $baseUrl . '/js/jobs-index.js?v=' . $jsMtime,
    ['depends' => [\yii\web\JqueryAsset::class, \yii\widgets\PjaxAsset::class]]
);

$stats        = JobsSearch::getDashboardStats();
$jobTypes     = ArrayHelper::map(JobsType::find()->orderBy('name')->all(), 'id', 'name');
$currentName  = (string) ($searchModel->name ?? '');
$currentType  = (string) ($searchModel->job_type ?? '');
$currentCity  = (string) ($searchModel->address_city ?? '');
$currentStat  = ($searchModel->status === null || $searchModel->status === '') ? '' : (string) $searchModel->status;
$totalCount   = (int) $dataProvider->totalCount;

$activeFilters = [];
if ($currentName !== '') {
    $activeFilters[] = ['icon' => 'search',       'label' => 'الاسم: ' . $currentName,                          'reset' => 'name'];
}
if ($currentType !== '' && isset($jobTypes[$currentType])) {
    $activeFilters[] = ['icon' => 'tag',          'label' => 'النوع: ' . $jobTypes[$currentType],               'reset' => 'job_type'];
}
if ($currentCity !== '') {
    $activeFilters[] = ['icon' => 'map-marker',   'label' => 'المدينة: ' . $currentCity,                        'reset' => 'address_city'];
}
if ($currentStat !== '') {
    $activeFilters[] = ['icon' => 'toggle-on',    'label' => 'الحالة: ' . ($currentStat === '1' ? 'فعال' : 'غير فعال'), 'reset' => 'status'];
}

$buildResetUrl = function (string $key) use ($searchModel): string {
    $params = Yii::$app->request->queryParams;
    if (isset($params['JobsSearch'][$key])) {
        unset($params['JobsSearch'][$key]);
    }
    return Url::to(array_merge(['index'], $params));
};
?>

<div class="jobs-page" dir="rtl">

    <!-- ═══════════════════ HERO HEADER ═══════════════════ -->
    <header class="jp-hero" role="banner" aria-labelledby="jp-page-title">
        <div class="jp-hero-row">
            <div>
                <h1 class="jp-hero-title" id="jp-page-title">
                    <span class="jp-hero-icon" aria-hidden="true"><i class="fa fa-building"></i></span>
                    <span>جهات العمل</span>
                </h1>
                <p class="jp-hero-sub">إدارة جهات العمل المسجلة في النظام، مع بياناتها وعملائها وتقييماتها.</p>
            </div>
            <div class="jp-hero-actions">
                <?= Html::a(
                    '<i class="fa fa-refresh" aria-hidden="true"></i><span>تحديث</span>',
                    ['index'],
                    ['class' => 'jp-btn', 'title' => 'إعادة تعيين الفلاتر']
                ) ?>
                <?= ExportButtons::widget([
                    'excelRoute'    => ['export-excel'],
                    'pdfRoute'      => ['export-pdf'],
                    'excelBtnClass' => 'jp-btn',
                    'pdfBtnClass'   => 'jp-btn',
                ]) ?>
                <?= Html::a(
                    '<i class="fa fa-plus" aria-hidden="true"></i><span>إضافة جهة عمل</span>',
                    ['create'],
                    ['class' => 'jp-btn jp-btn--primary', 'title' => 'إضافة جهة عمل جديدة']
                ) ?>
            </div>
        </div>

        <!-- KPI strip -->
        <div class="jp-kpis" role="list" aria-label="ملخص جهات العمل">
            <div class="jp-kpi jp-kpi--total" role="listitem">
                <div class="jp-kpi-ic" aria-hidden="true"><i class="fa fa-building"></i></div>
                <div class="jp-kpi-body">
                    <div class="jp-kpi-val"><?= Yii::$app->formatter->asInteger($stats['total']) ?></div>
                    <div class="jp-kpi-lbl">إجمالي الجهات</div>
                </div>
            </div>
            <div class="jp-kpi jp-kpi--active" role="listitem">
                <div class="jp-kpi-ic" aria-hidden="true"><i class="fa fa-check-circle"></i></div>
                <div class="jp-kpi-body">
                    <div class="jp-kpi-val"><?= Yii::$app->formatter->asInteger($stats['active']) ?></div>
                    <div class="jp-kpi-lbl">فعّالة</div>
                </div>
            </div>
            <div class="jp-kpi jp-kpi--inactive" role="listitem">
                <div class="jp-kpi-ic" aria-hidden="true"><i class="fa fa-pause-circle"></i></div>
                <div class="jp-kpi-body">
                    <div class="jp-kpi-val"><?= Yii::$app->formatter->asInteger($stats['inactive']) ?></div>
                    <div class="jp-kpi-lbl">غير فعّالة</div>
                </div>
            </div>
            <div class="jp-kpi jp-kpi--city" role="listitem">
                <div class="jp-kpi-ic" aria-hidden="true"><i class="fa fa-map-marker"></i></div>
                <div class="jp-kpi-body">
                    <div class="jp-kpi-val">
                        <?= $stats['top_city']
                            ? Html::encode($stats['top_city'])
                            : '<span style="opacity:.6">—</span>' ?>
                    </div>
                    <div class="jp-kpi-lbl">
                        المدينة الأكثر تمثيلاً
                        <?php if ($stats['top_city_count'] > 0): ?>
                            (<?= (int) $stats['top_city_count'] ?>)
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ═══════════════════ TOOLBAR ═══════════════════ -->
    <section class="jp-toolbar" aria-label="فلاتر البحث">
        <form id="jp-filter-form" action="<?= Url::to(['index']) ?>" method="get" data-pjax="1" role="search">
            <div class="jp-toolbar-row">

                <!-- Smart search -->
                <label class="jp-search" aria-label="بحث في جهات العمل">
                    <i class="fa fa-search" aria-hidden="true"></i>
                    <input
                        type="text"
                        id="jp-search-input"
                        name="JobsSearch[name]"
                        value="<?= Html::encode($currentName) ?>"
                        placeholder="ابحث باسم جهة العمل..."
                        autocomplete="off"
                        spellcheck="false"
                        inputmode="search"
                    >
                    <button type="button" class="jp-search-clear" aria-label="مسح البحث">
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </button>
                </label>

                <!-- Job type -->
                <label class="jp-select" aria-label="تصفية حسب نوع جهة العمل">
                    <select id="jp-type-select" name="JobsSearch[job_type]">
                        <option value="">كل الأنواع</option>
                        <?php foreach ($jobTypes as $id => $name): ?>
                            <option value="<?= (int) $id ?>" <?= $currentType !== '' && (int) $currentType === (int) $id ? 'selected' : '' ?>>
                                <?= Html::encode($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <!-- City -->
                <label class="jp-search" aria-label="تصفية حسب المدينة" style="grid-column: auto;">
                    <i class="fa fa-map-marker" aria-hidden="true"></i>
                    <input
                        type="text"
                        id="jp-city-input"
                        name="JobsSearch[address_city]"
                        value="<?= Html::encode($currentCity) ?>"
                        placeholder="المدينة"
                        autocomplete="off"
                    >
                </label>

                <!-- Status chips (radio-like) -->
                <div class="jp-chips" role="radiogroup" aria-label="تصفية حسب الحالة">
                    <input type="hidden" id="jp-status-input" name="JobsSearch[status]" value="<?= Html::encode($currentStat) ?>">
                    <button type="button" class="jp-chip" data-value=""  aria-pressed="<?= $currentStat === ''  ? 'true' : 'false' ?>" role="radio">الكل</button>
                    <button type="button" class="jp-chip jp-chip--success" data-value="1" aria-pressed="<?= $currentStat === '1' ? 'true' : 'false' ?>" role="radio">
                        <i class="fa fa-check" aria-hidden="true"></i> فعّال
                    </button>
                    <button type="button" class="jp-chip jp-chip--danger"  data-value="0" aria-pressed="<?= $currentStat === '0' ? 'true' : 'false' ?>" role="radio">
                        <i class="fa fa-pause" aria-hidden="true"></i> غير فعّال
                    </button>
                </div>

                <!-- Trailing tools -->
                <div class="jp-tools-end">
                    <button type="submit" class="jp-icon-btn" title="تطبيق البحث" aria-label="تطبيق البحث">
                        <i class="fa fa-paper-plane" aria-hidden="true"></i>
                    </button>
                    <a href="<?= Url::to(['index']) ?>" class="jp-icon-btn" title="مسح كل الفلاتر" aria-label="مسح كل الفلاتر" data-pjax="0">
                        <i class="fa fa-eraser" aria-hidden="true"></i>
                    </a>

                    <div class="jp-view-toggle" role="group" aria-label="طريقة العرض" style="display:inline-flex;gap:6px;margin-inline-start:6px">
                        <button type="button" class="jp-icon-btn" data-view="list"  aria-pressed="true"  title="عرض قائمة" aria-label="عرض قائمة">
                            <i class="fa fa-list" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="jp-icon-btn" data-view="cards" aria-pressed="false" title="عرض بطاقات" aria-label="عرض بطاقات">
                            <i class="fa fa-th-large" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>

            <?php if (!empty($activeFilters)): ?>
                <div class="jp-active-filters" aria-label="الفلاتر النشطة">
                    <?php foreach ($activeFilters as $f): ?>
                        <span class="jp-pill">
                            <i class="fa fa-<?= Html::encode($f['icon']) ?>" aria-hidden="true"></i>
                            <?= Html::encode($f['label']) ?>
                            <a href="<?= Html::encode($buildResetUrl($f['reset'])) ?>" title="إزالة" aria-label="إزالة هذا الفلتر" data-pjax="0">
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </a>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </form>
    </section>

    <!-- ═══════════════════ RESULTS ═══════════════════ -->
    <section id="jp-results" class="jp-results" aria-label="قائمة جهات العمل">
        <div class="jp-results-head">
            <div class="jp-results-summary">
                <i class="fa fa-list-ul" aria-hidden="true"></i>
                النتائج: <b id="jp-result-count"><?= Yii::$app->formatter->asInteger($totalCount) ?></b> جهة عمل
            </div>
        </div>

        <div id="ajaxCrudDatatable">
            <?= GridView::widget([
                'id'            => 'crud-datatable',
                'dataProvider'  => $dataProvider,
                'filterModel'   => null,
                'showHeader'    => true,
                'tableOptions'  => ['class' => 'kv-grid-table'],
                'summary'       => '<span><i class="fa fa-eye"></i> عرض <b>{begin}-{end}</b> من <b>{totalCount}</b></span>',
                'emptyText'     => '<div class="jp-empty">'
                    . '<div class="jp-empty-icon"><i class="fa fa-building-o" aria-hidden="true"></i></div>'
                    . '<h3>لا توجد جهات عمل مطابقة</h3>'
                    . '<p>جرّب تعديل الفلاتر أو ابحث بكلمة مفتاحية مختلفة.</p>'
                    . Html::a('<i class="fa fa-plus"></i> أضف جهة عمل جديدة', ['create'], ['class' => 'jp-btn jp-btn--primary', 'style' => 'background:var(--jp-primary);color:#fff;border-color:var(--jp-primary)'])
                    . '</div>',
                'pjax'          => true,
                'pjaxSettings'  => [
                    'options' => ['id' => 'crud-datatable-pjax', 'enablePushState' => true, 'timeout' => 8000],
                ],
                'columns'       => require(__DIR__ . '/_columns.php'),
                'striped'       => false,
                'condensed'     => false,
                'responsive'    => false,
                'hover'         => true,
                'panel'         => false,
                'toolbar'       => false,
                'pager'         => [
                    'firstPageLabel' => 'الأولى',
                    'lastPageLabel'  => 'الأخيرة',
                    'prevPageLabel'  => '<i class="fa fa-angle-right"></i>',
                    'nextPageLabel'  => '<i class="fa fa-angle-left"></i>',
                    'maxButtonCount' => 5,
                ],
            ]) ?>
        </div>
    </section>

</div>

<!-- Modal placeholder for any AJAX-driven actions -->
<div class="modal fade" id="ajaxCrudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div style="text-align:center;padding:40px">
                    <i class="fa fa-spinner fa-spin" style="font-size:24px;color:var(--jp-primary,#800020)" aria-hidden="true"></i>
                </div>
            </div>
            <div class="modal-footer"></div>
        </div>
    </div>
</div>
