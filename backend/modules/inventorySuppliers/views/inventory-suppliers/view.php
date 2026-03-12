<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\InventorySuppliers */
?>
<div class="inventory-suppliers-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'company_id',
            'name',
            'adress',
            'phone_number',
            'created_by',
            'created_at',
            'updated_at',
            'last_update_by',
            'is_deleted',
        ],
    ]) ?>

</div>
