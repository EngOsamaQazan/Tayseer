<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Department */
?>
<div class="department-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'title',
            'description',
            'lead_by',
            'status',
            'created_by',
            'created_at',
            'updated_at',
        ],
    ]) ?>

</div>
