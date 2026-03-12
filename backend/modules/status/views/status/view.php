<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\status\models\Status */
?>
<div class="status-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'created_at',
            'updated_at',
            'created_by',
            'last_updated_by',
            'is_deleted',
        ],
    ]) ?>

</div>
