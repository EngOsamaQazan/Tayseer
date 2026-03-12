<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Income */
?>
<div class="installment-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'item_id',
            'customer_id',
            'date',
            'cheque_number',
            'notes',
            'is_made_payment',
            'total',

        ],
    ]) ?>

</div>
