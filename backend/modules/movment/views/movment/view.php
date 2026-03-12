<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Movment */
?>
<div class="movment-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'user_id',
            'movement_number',
            'bank_receipt_number',
            'financial_value',
            'receipt_image',
        ],
    ]) ?>

</div>
