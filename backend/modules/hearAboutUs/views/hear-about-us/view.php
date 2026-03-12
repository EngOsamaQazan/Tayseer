<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\hearAboutUs\models\HearAboutUs */
?>
<div class="hear-about-us-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'created_at',
            'updated_at',
            'created_by',
            'last_updated_by',
            'is_deleted',
        ],
    ]) ?>

</div>
