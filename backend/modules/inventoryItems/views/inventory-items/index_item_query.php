<?php
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use backend\widgets\ExportButtons;

$this->title = 'إدارة المخزون';
CrudAsset::register($this);
$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);
?>

<?= $this->render('@app/views/layouts/_inventory-tabs', ['activeTab' => 'query']) ?>

<style>
.inv-page .panel { border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.inv-page .panel-heading { background: #f8fafc !important; border-bottom: 1px solid #e2e8f0; font-weight: 700; color: #334155; border-radius: 10px 10px 0 0 !important; }
.inv-page .kv-grid-table th { background: #f8fafc; font-weight: 700; font-size: 13px; color: #334155; }
.inv-page .kv-grid-table td { font-size: 13.5px; vertical-align: middle; }
</style>

<div class="inv-page">
    <?= $this->render('_search', ['model' => $searchModel]); ?>

    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable-1',
            'dataProvider' => $dataProvider,
            'columns' => require(__DIR__ . '/_columns_item_query.php'),
            'summary' => '<span style="font-size:13px;color:#64748b"><i class="fa fa-search"></i> عرض <b>{begin}-{end}</b> من <b>{totalCount}</b> صنف</span>',
            'toolbar' => [['content' =>
                ExportButtons::widget([
                    'excelRoute' => ['export-query-excel'],
                    'pdfRoute'   => ['export-query-pdf'],
                ]) . ' {toggleData}'
            ]],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'type' => 'default',
                'heading' => '<i class="fa fa-search"></i> استعلام الأصناف والكميات المتبقية',
            ],
        ]) ?>
    </div>
</div>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '', 'size' => Modal::SIZE_LARGE]) ?>
<?php Modal::end(); ?>
