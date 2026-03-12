<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\reports\models\FollowUpReports */
?>
<div class="follow-up-reports-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'contract_id',
            'date_time',
            'notes:ntext',
            'feeling',
            'created_by',
            'connection_goal',
            'reminder',
            'promise_to_pay_at',
        ],
    ]) ?>

</div>
