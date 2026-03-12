<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Notification */
?>
<div class="notification-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'sender_id',
            'recipient_id',
            'type_of_notification',
            'title_html',
            'body_html:ntext',
            'href',
            'is_unread',
            'is_hidden',
            'created_time:datetime',
        ],
    ]) ?>

</div>
