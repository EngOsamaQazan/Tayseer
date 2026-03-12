<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use johnitvn\ajaxcrud\BulkButtonWidget;

/* @var $this yii\web\View */
/* @var $searchModel common\models\DocumentHolderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Document Holders');
$this->params['breadcrumbs'][] = $this->title;

CrudAsset::register($this);

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
<?php Modal::begin([
    "id" => "ajaxCrudModal",
    "footer" => "",// always need it for jquery plugin
]) ?>
<?php Modal::end(); ?>
<?php
$this->registerJs(<<<SCRIPT
$(document).on('click','#ready',function(){
let id = $(this).attr('data-id');
$.post('ready',{id:id},function(){
 location.reload();
})
});
SCRIPT
)
?>
