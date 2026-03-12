<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use johnitvn\ajaxcrud\BulkButtonWidget;
use common\helper\Permissions;
use backend\widgets\ExportButtons;

/* @var $this yii\web\View */
/* @var $searchModel common\models\CollectionSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Collections');
$this->params['breadcrumbs'][] = $this->title;

CrudAsset::register($this);
?>

<div class="ty-stats-grid" style="margin-bottom:20px">
    <div class="ty-stat-card">
        <div class="ty-stat-value" style="color:var(--bs-primary)"><?= $count_contract ?></div>
        <div class="ty-stat-label">عدد قضايا الحسم</div>
    </div>
    <div class="ty-stat-card">
        <div class="ty-stat-value" style="color:var(--bs-success)"><?= $amount ?></div>
        <div class="ty-stat-label">المتاح للقبض</div>
    </div>
</div>

<div class="collection-index">
    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'summary' => '',
            'columns' => require(__DIR__ . '/_columns.php'),
            'toolbar' => [
                ['content' =>
                    Html::a('<i class="fa fa-sync"></i>', [''],
                        ['data-pjax' => 1, 'class' => 'btn btn-default', 'title' => 'تحديث']) .
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
            ]
        ]) ?>
    </div>
</div>
<?php Modal::begin([
    "id" => "ajaxCrudModal",
    "footer" => "",
]) ?>
<?php Modal::end(); ?>
