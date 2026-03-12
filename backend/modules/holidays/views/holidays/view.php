<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Holidays */
?>
<div class="holidays-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'title',
            'start_at',
            'end_at',
            'created_by',
            'created_at',
            'updated_at',
        ],
    ]) ?>

</div>
