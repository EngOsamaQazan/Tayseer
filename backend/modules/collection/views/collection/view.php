<?php

use yii\widgets\DetailView;
use backend\modules\divisionsCollection\models\DivisionsCollection;

use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use common\helper\Permissions;
use backend\widgets\ExportButtons;

/* @var $this yii\web\View */
/* @var $model common\models\Collection */
$d1 = new DateTime($model->date);
$d2 = new DateTime(date('Y-m-d'));
$interval = $d1->diff($d2);
$diffInMonths = $interval->m; //4

$revares_courts = backend\modules\financialTransaction\models\FinancialTransaction::find()->where(['contract_id' => $model->contract_id])->andWhere(['income_type' => 11])->all();
$revares = 0;
foreach ($revares_courts as $revares_court) {
    $revares = $revares + $revares_court->amount;
}
$diffInMonths = $diffInMonths + 1;
$value = ($diffInMonths * $model->amount) - $revares;
?>
<div class="questions-bank box box-primary">
    <div class="row">
        <div class="col-lg-3">

        </div>
        <div class="col-lg-4">
            <h4 style="color: brown">
               اسم العميل :<?= $custamer_name?>
            </h4>
        </div>
        <div class="col-lg-3">
            <h4 style="color: brown">
                قيم الحسم الشهري :<?= $model->amount ?>
            </h4>

        </div>
        <div class="col-lg-3">
            <h4 style="color: brown">
                المتاح للقبض :<?= $value ?>
            </h4>

        </div>
    </div>
</div>
<div class="collection-view">

    <?php

    $query = DivisionsCollection::find()->where(['collection_id' => $model->id]);

    $divisionsCollection = new ActiveDataProvider([
        'query' => $query,
    ]);

    echo kartik\grid\GridView::widget([
        'id' => 'crud-datatable',
        'dataProvider' => $divisionsCollection,
        'pjax' => true,
        'columns' => [
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'collection_id',
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'month',
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'year',
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'amount',
            ],
        ],
        'toolbar' => [
            ['content' =>
                Html::a('<i class="glyphicon glyphicon-repeat"></i>', [''],
                    ['data-pjax' => 1, 'class' => 'btn btn-default', 'title' => 'Reset Grid']) .
                '{toggleData}' .
                ExportButtons::widget([
                    'excelRoute' => ['export-view-excel', 'id' => $model->id],
                    'pdfRoute' => ['export-view-pdf', 'id' => $model->id],
                ])
            ],
        ],
        'striped' => false,
        'condensed' => false,
        'responsive' => false,
        'panel' => [
            'type' => false,
            'heading' => false,
            'after' => false,

        ]
    ]);
    ?>

</div>
