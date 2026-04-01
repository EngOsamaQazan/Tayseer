<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel common\models\DocumentHolderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Document Holders');
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile(Yii::$app->request->baseUrl . '/css/tayseer-gridview-responsive.css?v=1');
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/tayseer-gridview-modal.js?v=1', [
    'depends' => [\yii\web\JqueryAsset::class],
]);

?>
<div class="document-holder-index">
    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'summary' => '',
            'columns' => require(__DIR__ . '/_archives_columns.php'),
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'type' => 'default',
            ]
        ]) ?>
    </div>
</div>
<div class="modal fade" id="ajaxCrudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
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
            <div class="modal-footer"></div>
        </div>
    </div>
</div>
<?php
$this->registerJs(<<<SCRIPT
$(document).on('click','.js-doc-holder-ready',function(){
let id = $(this).attr('data-id');
$.post('ready',{id:id},function(){
 location.reload();
})
});
SCRIPT
)
?>
