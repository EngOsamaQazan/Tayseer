<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Location */
?>
<div class="location-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'location',
            'description',
            'status',
            'created_by',
            'created_at',
            'updated_at',
        ],
    ]) ?>

</div>
