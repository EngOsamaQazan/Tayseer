<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\connectionResponse\models\ConnectionResponse */
?>
<div class="connection-response-view">
 
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
