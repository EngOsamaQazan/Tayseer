<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\divisionsCollection\models\DivisionsCollection */
?>
<div class="divisions-collection-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'collection_id',
            'month',
            'amount',
            'created_at',
            'updated_at',
            'created_by',
            'last_updated_by',
            'is_deleted',
        ],
    ]) ?>

</div>
