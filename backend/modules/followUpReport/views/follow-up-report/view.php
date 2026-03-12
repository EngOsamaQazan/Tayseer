<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\contracts */
?>
<div class="contracts-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'seller_name',
            'customers_id',
            'Date_of_sale',
            'total_value',
            'first_installment_value',
            'first_installment_date',
            'monthly_installment_value',
            'notes:ntext',
            'updated_at',
            'is_deleted',
        ],
    ]) ?>

</div>
