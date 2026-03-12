<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\InventoryItemQuantities */
?>
<div class="inventory-item-quantities-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'item_id',
            'locations_id',
            'suppliers_id',
            'quantity',
            'created_at',
            'created_by',
        ],
    ]) ?>

</div>
