<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\ExpenseCategories */
?>
<div class="expense-categories-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'type',
            'created_at',
            'created_by',
            'updated_at',
            'last_updated_by',
            'is_deleted',
            'description:ntext',
        ],
    ]) ?>

</div>
