<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = 'فرع: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'الفروع', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="branch-view">
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'code',
            'name',
            [
                'attribute' => 'branch_type',
                'value' => $model->getTypeLabel(),
            ],
            'description',
            'address',
            'phone',
            [
                'attribute' => 'latitude',
                'format' => 'raw',
                'value' => $model->latitude ? number_format($model->latitude, 6) : '—',
            ],
            [
                'attribute' => 'longitude',
                'format' => 'raw',
                'value' => $model->longitude ? number_format($model->longitude, 6) : '—',
            ],
            'radius_meters',
            'wifi_ssid',
            [
                'attribute' => 'manager_id',
                'value' => $model->manager ? $model->manager->username : '—',
            ],
            [
                'attribute' => 'is_active',
                'format' => 'raw',
                'value' => $model->is_active
                    ? '<span class="badge bg-success">فعّال</span>'
                    : '<span class="badge bg-secondary">معطّل</span>',
            ],
            'sort_order',
            [
                'attribute' => 'created_by',
                'value' => $model->createdByUser ? $model->createdByUser->username : '—',
            ],
            'created_at',
            'updated_at',
        ],
    ]) ?>
</div>
