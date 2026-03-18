<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use kartik\grid\SerialColumn;
use backend\modules\accounting\models\Budget;

$this->title = 'الموازنات';
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-pie-chart"></i> <?= $this->title ?></h3>
        <div class="box-tools">
            <?= Html::a('<i class="fa fa-plus"></i> موازنة جديدة', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
        </div>
    </div>
    <div class="box-body">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'id' => 'budgets-grid',
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'hover' => true,
            'toggleData' => false,
            'summary' => '<span class="text-muted">عرض {begin}-{end} من {totalCount}</span>',
            'columns' => [
                ['class' => SerialColumn::class, 'header' => '#'],
                [
                    'attribute' => 'name',
                    'label' => 'اسم الموازنة',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return Html::a(Html::encode($model->name), ['view', 'id' => $model->id], ['style' => 'font-weight:700;']);
                    },
                ],
                [
                    'label' => 'السنة المالية',
                    'value' => function ($model) {
                        return $model->fiscalYear ? $model->fiscalYear->name : '—';
                    },
                ],
                [
                    'attribute' => 'total_amount',
                    'label' => 'الإجمالي',
                    'format' => ['decimal', 2],
                    'contentOptions' => ['class' => 'text-left', 'style' => 'font-weight:700;'],
                ],
                [
                    'attribute' => 'status',
                    'label' => 'الحالة',
                    'format' => 'raw',
                    'value' => function ($model) { return $model->getStatusBadge(); },
                    'contentOptions' => ['class' => 'text-center'],
                ],
                [
                    'attribute' => 'created_at',
                    'label' => 'تاريخ الإنشاء',
                    'value' => function ($model) {
                        return $model->created_at ? date('Y-m-d', $model->created_at) : '';
                    },
                    'contentOptions' => ['class' => 'text-center'],
                ],
                [
                    'header' => 'الإجراءات',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $btns = Html::a('<i class="fa fa-eye"></i>', ['view', 'id' => $model->id], ['class' => 'btn btn-xs btn-primary', 'title' => 'عرض']);
                        $btns .= ' ' . Html::a('<i class="fa fa-bar-chart"></i>', ['variance', 'id' => $model->id], ['class' => 'btn btn-xs btn-info', 'title' => 'تقرير الانحراف']);
                        if ($model->status === 'draft') {
                            $btns .= ' ' . Html::a('<i class="fa fa-edit"></i>', ['update', 'id' => $model->id], ['class' => 'btn btn-xs btn-warning', 'title' => 'تعديل']);
                        }
                        return $btns;
                    },
                    'contentOptions' => ['class' => 'text-center', 'style' => 'white-space:nowrap;'],
                ],
            ],
        ]) ?>
    </div>
</div>
