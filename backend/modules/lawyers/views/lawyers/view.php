<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Lawyers */
?>
<div class="lawyers-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'address',
            'phone_number',
            'status',
            'created_at',
            'updated_at',
            'created_by',
            'last_update_by',
            'is_deleted',
            'notes:ntext',
        ],
    ]) ?>

</div>
