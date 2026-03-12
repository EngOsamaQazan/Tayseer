<?php

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = Yii::t('app', 'monthly Income');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="invoice-index">
    <div id="ajaxCrudDatatable">

        <?=
        GridView::widget([
            'dataProvider' => $monthly_installment,
            'columns' => [
                'SUM',
                'YEAR',
                'MONTH',
                [
                    'label' => 'details',
                    'format' => 'raw',
                    'value' => function ($data) {
                        return Html::a('details', ['reports/this-month-installments', 'date' => $data['YEAR'] . '-' . $data['MONTH'] . '-15']);
                    },
                        ],
                    ],
                ]);
                ?>
    </div>
</div>
