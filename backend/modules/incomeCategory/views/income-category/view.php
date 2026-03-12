<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\incomeCategory\models\IncomeCategory */
?>
<div class="income-category-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'created_at',
            'created_by',
            'updated_at',
            'last_updated_by',
            'is_deleted',
            'description:ntext',
        ],
    ]) ?>

</div>
