<?php
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use common\helper\Permissions;
use backend\widgets\ExportButtons;

$this->title = 'إدارة المخزون';
CrudAsset::register($this);
$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);
?>

<?= $this->render('@app/views/layouts/_inventory-tabs', ['activeTab' => 'locations']) ?>

<style>
.inv-page .panel { border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.inv-page .panel-heading { background: #f8fafc !important; border-bottom: 1px solid #e2e8f0; font-weight: 700; color: #334155; border-radius: 10px 10px 0 0 !important; }
.inv-page .kv-grid-table th { background: #f8fafc; font-weight: 700; font-size: 13px; color: #334155; }
.inv-page .kv-grid-table td { font-size: 13.5px; vertical-align: middle; }
</style>

<div class="inv-page">
    <section class="fin-actions" aria-label="إجراءات" style="margin-bottom:16px">
        <?php if (Yii::$app->user->can(Permissions::INVENTORY_STOCK_LOCATIONS)): ?>
        <div class="fin-act-group">
            <?= Html::a('<i class="fa fa-plus"></i> <span>موقع جديد</span>', ['create'], [
                'class' => 'fin-btn fin-btn--add', 'title' => 'إضافة موقع تخزين جديد', 'role' => 'modal-remote',
            ]) ?>
        </div>
        <?php endif ?>
        <div class="fin-act-group">
            <?= Html::a('<i class="fa fa-refresh"></i> <span>تحديث</span>', ['index'], [
                'class' => 'fin-btn fin-btn--reset',
            ]) ?>
        </div>
    </section>

    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'columns' => require(__DIR__ . '/_columns.php'),
            'summary' => '<span style="font-size:13px;color:#64748b"><i class="fa fa-table"></i> عرض <b>{begin}-{end}</b> من <b>{totalCount}</b> موقع</span>',
            'toolbar' => [['content' =>
                '{toggleData}' .
                ExportButtons::widget([
                    'excelRoute' => ['export-excel'],
                    'pdfRoute' => ['export-pdf'],
                ])
            ]],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'type' => 'default',
                'heading' => '<i class="fa fa-map-marker"></i> مواقع التخزين <span class="badge" style="background:#0369a1;margin-right:6px">' . $searchCounter . '</span>',
            ],
            'pjax' => true,
            'pjaxSettings' => ['options' => ['id' => 'crud-datatable-pjax']],
        ]) ?>
    </div>
</div>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '', 'size' => Modal::SIZE_LARGE]) ?>
<?php Modal::end(); ?>
