<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\LeaveRequest */
?>
<div class="leave-request-view">

    <?=
    DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'reason',
            'start_at',
            'end_at',
            'attachment',
            'leave_policy',
            'approved_by',
            'created_by',
            'status',
            'created_at',
            'updated_at',
        ],
    ])
    ?>

</div>
