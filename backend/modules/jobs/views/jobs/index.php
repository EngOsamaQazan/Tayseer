<?php
/**
 * شاشة جهات العمل — تصميم متوافق مع فلسفة النظام
 */
use yii\helpers\Url;
use yii\helpers\Html;
use kartik\grid\GridView;
use yii\helpers\ArrayHelper;
use backend\modules\jobs\models\JobsType;
use backend\widgets\ExportButtons;

$this->title = 'جهات العمل';
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/tayseer-gridview-responsive.css?v=1');
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/tayseer-gridview-modal.js?v=1', [
    'depends' => [\yii\web\JqueryAsset::class],
]);
?>

<div class="fin-page">
    <!-- ═══ شريط الأدوات ═══ -->
    <section class="fin-actions" aria-label="إجراءات">
        <div class="fin-act-group">
            <?= Html::a('<i class="fa fa-plus"></i> <span>إضافة جهة عمل</span>', ['create'], [
                'class' => 'fin-btn fin-btn--add', 'title' => 'إضافة جهة عمل جديدة',
            ]) ?>
        </div>
        <div class="fin-act-group">
            <?= Html::a('<i class="fa fa-refresh"></i> <span>تحديث</span>', ['index'], [
                'class' => 'fin-btn fin-btn--reset',
            ]) ?>
        </div>
    </section>

    <!-- ═══ بحث متقدم ═══ -->
    <?= $this->render('_search', ['model' => $searchModel]) ?>

    <!-- ═══ جدول البيانات ═══ -->
    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'summary' => '<span style="font-size:13px;color:#64748b"><i class="fa fa-building"></i> عرض <b>{begin}-{end}</b> من <b>{totalCount}</b> جهة عمل</span>',
            'pjax' => true,
            'pjaxSettings' => ['options' => ['id' => 'crud-datatable-pjax']],
            'columns' => require(__DIR__ . '/_columns.php'),
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
            'hover' => true,
            'panel' => [
                'type' => 'default',
                'heading' => '<i class="fa fa-building"></i> قائمة جهات العمل <span class="badge" style="background:#7c3aed;margin-right:6px">' . $dataProvider->totalCount . '</span>',
            ],
        ]) ?>
    </div>
</div>

<div class="modal fade" id="ajaxCrudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div style="text-align:center;padding:40px">
                    <i class="fa fa-spinner fa-spin" style="font-size:24px;color:var(--ty-clr-primary,#800020)"></i>
                </div>
            </div>
        </div>
    </div>
</div>
