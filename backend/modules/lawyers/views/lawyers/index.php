<?php

use yii\helpers\Url;
use yii\helpers\Html;
use kartik\grid\GridView;
use backend\widgets\ExportButtons;

$this->title = 'المفوضين والوكلاء';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile(Yii::$app->request->baseUrl . '/css/tayseer-gridview-responsive.css?v=1');
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/tayseer-gridview-modal.js?v=1', [
    'depends' => [\yii\web\JqueryAsset::class],
]);
?>

<style>
.lw-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;white-space:nowrap}
.lw-badge--lawyer{background:rgba(128,0,32,.1);color:#800020}
.lw-badge--delegate{background:rgba(100,116,139,.1);color:#475569}
.lw-sig-ok{color:#28a745;font-size:12px}
.lw-sig-no{color:#cbd5e1;font-size:12px}
</style>

<?= $this->render('_search', ['model' => $searchModel]) ?>
<div class="lawyers-index">
    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'summary' => '',
            'columns' => require(__DIR__ . '/_columns.php'),
            'toolbar' => [
                ['content' =>
                    Html::a('<i class="fa fa-plus"></i>', ['create'],
                        ['title' => 'إضافة مفوض / وكيل', 'class' => 'btn btn-secondary']) .
                    Html::a('<i class="fa fa-refresh"></i>', [''],
                        ['data-pjax' => 1, 'class' => 'btn btn-secondary', 'title' => 'إعادة تعيين']) .
                    '{toggleData}' .
                    ExportButtons::widget([
                        'excelRoute' => ['export-excel'],
                        'pdfRoute' => ['export-pdf'],
                    ])
                ],
            ],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'type' => 'default',
                'heading' => 'عدد العناصر:' . $searchCounter,
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
                    <i class="fa fa-spinner fa-spin" style="font-size:24px;color:#800020"></i>
                </div>
            </div>
        </div>
    </div>
</div>
