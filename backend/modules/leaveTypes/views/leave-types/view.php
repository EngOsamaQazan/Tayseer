<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\LeavePolicy */
?>
<div class="leave-policy-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'year',
            'leave_type',
            'total_days',
            'description',
            'department',
            'designation',
            'location',
            'gender',
            'marital_status',
            'status',
            'created_by',
            'created_at',
            'updated_at',
        ],
    ]) ?>

</div>
