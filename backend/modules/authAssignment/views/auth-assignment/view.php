<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\AuthAssignment */
?>
<div class="auth-assignment-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'item_name',
            'user_id',
            'created_at',
        ],
    ]) ?>

</div>
