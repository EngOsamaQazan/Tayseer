<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\InventoryStockLocations */
?>
<div class="inventory-stock-locations-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'locations_name',
            'company_id',
            'created_by',
            'created_at',
            'updated_at',
            'last_update_by',
            'is_deleted',
        ],
    ]) ?>

</div>
