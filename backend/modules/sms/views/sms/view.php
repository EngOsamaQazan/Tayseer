<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\sms\models\Sms */
?>
<div class="sms-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'date',
            'is_send',
        ],
    ]) ?>

</div>
