<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Designation */
?>
<div class="designation-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'title',
            'description',
            'status',
            'created_by',
            'created_at',
            'updated_at',
        ],
    ]) ?>

</div>
