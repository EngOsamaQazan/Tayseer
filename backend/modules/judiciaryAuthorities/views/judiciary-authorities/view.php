<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use backend\models\JudiciaryAuthority;

/* @var $this yii\web\View */
/* @var $model backend\models\JudiciaryAuthority */
?>
<div class="judiciary-authorities-view">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?= Html::encode($model->name) ?></h5>
            <div>
                <?= Html::a('<i class="fa fa-arrow-right"></i> رجوع', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
                <?= Html::a('<i class="fa fa-pencil"></i> تعديل', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
        <div class="card-body">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'name',
                    [
                        'attribute' => 'authority_type',
                        'value' => function ($model) {
                            $list = JudiciaryAuthority::getTypeList();
                            return $list[$model->authority_type] ?? $model->authority_type;
                        },
                    ],
                    [
                        'attribute' => 'notes',
                        'value' => $model->notes ?: '—',
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>
