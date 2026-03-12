<?php

use yii\grid\GridView;

$this->title = Yii::t('app', 'monthly Income beer user');
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="invoice-index">
    <div id="ajaxCrudDatatable">

        <?=
        GridView::widget([
            'dataProvider' => $monthly_installment_monthly_beer_user,
            'columns' => [
                'SUM',
                'YEAR',
                'MONTH',
                'NAME',
            ],
        ]);
        ?>
    </div>
</div>