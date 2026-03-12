<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Attendance */
?>
<div class="attendance-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'user_id',
            'location_id',
            'check_in_time',
            'check_out_time',
            'manual_checked_in_by',
            'manual_checked_out_by',
            'is_manual_actions',
        ],
    ]) ?>

</div>
