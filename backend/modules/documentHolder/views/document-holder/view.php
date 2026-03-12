<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\DocumentHolder */
?>
<div class="document-holder-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
            'approved_by_manager',
            'approved_by_employee',
            'approved_at',
            'reason:ntext',
            'ready',
            'contract_id',
            'status',
            'type',
        ],
    ]) ?>

</div>
