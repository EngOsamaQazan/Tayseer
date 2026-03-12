<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Court */
?>
<div class="court-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'city',
            'adress',
            'phone_number',
            'created_by',
            'last_update_by',
            'created_at',
            'updates_at',
            'is_deleted',
        ],
    ]) ?>

</div>
