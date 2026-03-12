<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use johnitvn\ajaxcrud\BulkButtonWidget;

/* @var $this yii\web\View */
/* @var $searchModel common\models\NotificationSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Notifications');
$this->params['breadcrumbs'][] = $this->title;

CrudAsset::register($this);

?>
<div class="questions-bank box box-primary">
    <div class="notification-index">
        <div id="ajaxCrudDatatable">
            <?= GridView::widget([
                'id' => 'crud-datatable',
                'dataProvider' => $dataProvider,
                'pjax' => true,
                'summary' => false,
                'columns' => require(__DIR__ . '/_user-columns.php'),
                'striped' => true,
                'condensed' => true,
                'responsive' => true,

            ]) ?>
        </div>
    </div>
    <?php Modal::begin(["id" => "ajaxCrudModal",
        "footer" => "",// always need it for jquery plugin
    ]) ?>
    <?php Modal::end(); ?>
</div>