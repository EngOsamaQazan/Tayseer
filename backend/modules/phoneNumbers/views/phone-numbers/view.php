<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\PhoneNumbers */
?>
<div class="phone-numbers-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'customers_id',
            'phone_number',
            'created_at',
            'updated_at',
            'is_deleted',
            'phone_number_owner',
            'owner_name',
        ],
    ]) ?>

</div>
