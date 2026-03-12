<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\RejesterFollowUpType */
?>
<div class="rejester-follow-up-type-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
        ],
    ]) ?>

</div>
