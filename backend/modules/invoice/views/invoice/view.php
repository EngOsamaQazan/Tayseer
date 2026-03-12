<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Invoice */
?>
<div class="invoice-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'title',
            'number',
            'date',
            'total',
        ],
    ]) ?>

</div>
