<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\address */
?>
<div class="address-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'customers_id',
            'address',
            'created_at',
            'updated_at',
            'is_deleted',
        ],
    ]) ?>

</div>
