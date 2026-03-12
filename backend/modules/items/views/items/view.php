<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Items */
?>
<div class="items-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'cost',
            'price',
            'invoice_number',
            'notes',
            'is_sold',
            'sold_to',
        ],
    ]) ?>

</div>
