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
            'dataProvider' => $due_installment,
            'columns' => [
                'total_sum',
                'total_installment',
                'date',
                [
                    'label' => 'name',
                    'format' => 'raw',
                    'value' => function ($data) {
                        return Html::a($data['name'], ['Income/index', 'page' => 1, 'customer_id' => $data['customer_id']]);
                    },
                        ],
                        'notes'
                    ],
                ]);
                ?>
    </div>
</div>
