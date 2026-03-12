<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\itemsInventoryInvoices\models\ItemsInventoryInvoices */
?>
<div class="items-inventory-invoices-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'number',
            'single_price',
            'created_at',
            'updated_at',
            'created_by',
            'last_updated_by',
            'is_deleted',
        ],
    ]) ?>

</div>
