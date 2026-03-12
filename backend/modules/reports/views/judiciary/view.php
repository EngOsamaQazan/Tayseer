<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\judiciary\models\Judiciary */
?>
<div class="judiciary-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'court_id',
            'type_id',
            'case_cost',
            'lawyer_cost',
            'lawyer_id',
            'created_at',
            'updated_at',
            'created_by',
            'last_update_by',
            'is_deleted',
        ],
    ]) ?>

</div>
