<?php
/**
 * قائمة العملاء
 * يعرض جدول العملاء مع بحث متقدم وأدوات تصدير
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use common\helper\Permissions;
use backend\widgets\ExportButtons;

CrudAsset::register($this);
$this->title = 'العملاء';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile(Yii::$app->request->baseUrl . '/css/contracts-v2.css?v=' . time());

$this->registerCss('
    .customers-index .panel,
    .customers-index .panel-body,
    .customers-index .kv-grid-container,
    .customers-index .table-responsive,
    .customers-index .grid-view,
    .customers-index #ajaxCrudDatatable,
    .customers-index .table-bordered {
        overflow: visible !important;
    }
    .customers-index .dropdown-menu {
        left: 0 !important;
        right: auto !important;
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        border-radius: 6px;
        z-index: 9999;
    }
    .customers-index .btn-group .dropdown-toggle {
        background: #fdf0f3;
        border: 1px solid #f0c0cc;
        color: #800020;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        font-size: 14px;
        transition: all 0.2s ease;
    }
    .customers-index .btn-group .dropdown-toggle:hover,
    .customers-index .btn-group .dropdown-toggle:focus {
        background: #800020;
        color: #fff;
        border-color: #800020;
    }
    .customers-index .btn-group .dropdown-toggle .caret {
        display: none;
    }
    .customers-index .dropdown-menu > li > a {
        padding: 8px 16px;
        font-size: 13px;
        transition: background 0.15s ease;
    }
    .customers-index .dropdown-menu > li > a:hover {
        background: #fdf0f3;
        color: #800020;
    }

    /* ---- Toolbar button styles ---- */
    .customers-index .kv-panel-before {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        padding: 8px 12px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .customers-index .kv-panel-before .btn {
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        padding: 6px 14px;
        transition: all 0.2s ease;
        border: 1.5px solid transparent;
    }
    .customers-index .kv-panel-before .btn-success {
        background: #800020;
        border-color: #800020;
        color: #fff;
    }
    .customers-index .kv-panel-before .btn-success:hover {
        background: #5c0017;
        border-color: #5c0017;
    }
    .customers-index .kv-panel-before .btn-default {
        background: #fff;
        border-color: #e2e8f0;
        color: #64748b;
    }
    .customers-index .kv-panel-before .btn-default:hover {
        background: #f1f5f9;
        border-color: #800020;
        color: #800020;
    }
    .customers-index .kv-panel-before .btn-success.btn-sm,
    .customers-index .kv-panel-before .btn-danger.btn-sm {
        background: #fff;
        border-color: #e2e8f0;
        color: #334155;
        font-weight: 500;
    }
    .customers-index .kv-panel-before .btn-success.btn-sm:hover {
        background: #ecfdf5;
        border-color: #059669;
        color: #059669;
    }
    .customers-index .kv-panel-before .btn-danger.btn-sm:hover {
        background: #fef2f2;
        border-color: #dc2626;
        color: #dc2626;
    }
    .customers-index .panel-heading {
        background: linear-gradient(135deg, #800020, #a0334d) !important;
        color: #fff !important;
        border-radius: 10px 10px 0 0;
        padding: 10px 16px;
        font-size: 14px;
        font-weight: 600;
    }
    .customers-index .panel-heading .badge {
        background: rgba(255,255,255,0.25);
        color: #fff;
        font-size: 12px;
        padding: 3px 8px;
        border-radius: 10px;
    }
    .customers-index .panel {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
');
?>

<div class="customers-index">

    <button class="ct-btn ct-btn-outline ct-show-sm ct-filter-open-btn"
            onclick="document.getElementById('ctFilterWrap').classList.add('open')"
            style="margin-bottom:10px;width:100%">
        <i class="fa fa-search"></i> بحث وفلترة
    </button>

    <?= $this->render('_search', ['model' => $searchModel]) ?>

    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'pjax' => false,
            'summary' => '<span class="text-muted" style="font-size:12px">عرض {begin}-{end} من أصل {totalCount} عميل</span>',
            'columns' => require __DIR__ . '/_columns.php',
            'toolbar' => [
                [
                    'content' =>
                        (Permissions::can(Permissions::CUST_CREATE) ?
                            Html::a('<i class="fa fa-plus"></i> إضافة عميل', ['create'], [
                                'class' => 'btn btn-success',
                            ]) : '') .
                        Html::a('<i class="fa fa-refresh"></i>', [''], [
                            'data-pjax' => 1,
                            'class' => 'btn btn-default',
                            'title' => 'تحديث',
                        ]) .
                        '{toggleData}' .
                        (Permissions::can(Permissions::CUST_EXPORT)
                            ? ExportButtons::widget(['excelRoute' => ['export-excel'], 'pdfRoute' => ['export-pdf']])
                            : '')
                ],
            ],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'heading' => '<i class="fa fa-users"></i> العملاء <span class="badge">' . $searchCounter . '</span>',
            ],
        ]) ?>
    </div>
</div>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '']) ?>
<?php Modal::end() ?>
