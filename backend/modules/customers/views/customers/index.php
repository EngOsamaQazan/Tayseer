<?php
/**
 * قائمة العملاء — V2
 * NO Bootstrap 3, NO CrudAsset, NO inline styles
 * All CSS in customers-v2.css, all JS in customers-v2.js
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\Pjax;
use kartik\grid\GridView;
use common\helper\Permissions;
use backend\widgets\ExportButtons;

$this->title = 'العملاء';
$this->params['breadcrumbs'][] = $this->title;
$this->registerCss('.content-header,.page-header{display:none!important}');

$this->registerCssFile(Yii::$app->request->baseUrl . '/css/contracts-v2.css?v=' . time());
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/customers-v2.css?v=' . time());

$this->registerJsFile(Yii::$app->request->baseUrl . '/js/customers-v2.js?v=' . time(), [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_END,
]);
?>

<div class="cust-page">

    <!-- Stats Cards -->
    <div class="cust-stats">
        <div class="cust-stat">
            <div class="cust-stat-icon" style="background:#FDF2F4;color:#800020"><i class="fa fa-users"></i></div>
            <div>
                <div class="cust-stat-val" style="color:#800020"><?= number_format($searchCounter) ?></div>
                <div class="cust-stat-lbl">إجمالي العملاء</div>
            </div>
        </div>
        <div class="cust-stat">
            <div class="cust-stat-icon" style="background:#ECFDF5;color:#059669"><i class="fa fa-file-text-o"></i></div>
            <div>
                <div class="cust-stat-val" style="color:#059669"><?= number_format($dataProvider->getTotalCount()) ?></div>
                <div class="cust-stat-lbl">نتائج البحث</div>
            </div>
        </div>
        <div class="cust-stat">
            <div class="cust-stat-icon" style="background:#EFF6FF;color:#2563EB"><i class="fa fa-user-plus"></i></div>
            <div>
                <div class="cust-stat-val" style="color:#2563EB">—</div>
                <div class="cust-stat-lbl">عملاء اليوم</div>
            </div>
        </div>
        <div class="cust-stat">
            <div class="cust-stat-icon" style="background:#FEF3C7;color:#D97706"><i class="fa fa-balance-scale"></i></div>
            <div>
                <div class="cust-stat-val" style="color:#D97706">—</div>
                <div class="cust-stat-lbl">مشتكى عليهم</div>
            </div>
        </div>
    </div>

    <!-- Mobile Filter Toggle -->
    <button class="ct-btn ct-btn-outline ct-show-sm ct-filter-open-btn"
            onclick="document.getElementById('ctFilterWrap').classList.add('open')"
            style="margin-bottom:10px;width:100%">
        <i class="fa fa-search"></i> بحث وفلترة
    </button>

    <!-- Search Panel -->
    <?= $this->render('_search', ['model' => $searchModel]) ?>

    <!-- Grid with built-in toolbar (same pattern as judiciary) -->
    <?php Pjax::begin(['id' => 'customers-grid-pjax', 'timeout' => 5000]); ?>
    <?= GridView::widget([
        'id' => 'crud-datatable',
        'dataProvider' => $dataProvider,
        'pjax' => false,
        'summary' => '<span class="text-muted" style="font-size:12px">عرض {begin}-{end} من أصل {totalCount} عميل</span>',
        'pager' => [
            'firstPageLabel' => 'الأولى',
            'lastPageLabel' => 'الأخيرة',
            'prevPageLabel' => 'السابق',
            'nextPageLabel' => 'التالي',
            'maxButtonCount' => 5,
        ],
        'columns' => require __DIR__ . '/_columns.php',
        'toolbar' => [
            [
                'content' =>
                    (Permissions::can(Permissions::CUST_CREATE)
                        ? Html::a('<i class="fa fa-plus"></i> إضافة عميل', ['create'], ['class' => 'btn btn-success', 'style' => 'font-weight:600'])
                        : '') .
                    Html::a('<i class="fa fa-refresh"></i>', [''], ['data-pjax' => 1, 'class' => 'btn btn-default', 'title' => 'تحديث']) .
                    (Permissions::can(Permissions::CUST_EXPORT)
                        ? ExportButtons::widget(['excelRoute' => ['export-excel'], 'pdfRoute' => ['export-pdf']])
                        : '')
            ],
        ],
        'striped' => true,
        'condensed' => true,
        'responsive' => true,
        'hover' => true,
        'toggleData' => false,
        'panel' => [
            'heading' => '<i class="fa fa-users"></i> العملاء <span class="badge">' . number_format($searchCounter) . '</span>',
        ],
    ]) ?>
    <?php Pjax::end(); ?>
</div>

<!-- Modal (Bootstrap 5) -->
<div class="modal fade" id="ajaxCrudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer"></div>
        </div>
    </div>
</div>
