<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Workdays */
?>
<div class="workdays-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'day_name',
            'start_at',
            'end_at',
            'status',
            'created_by',
            'created_at',
            'updated_at',
        ],
    ]) ?>

</div>
