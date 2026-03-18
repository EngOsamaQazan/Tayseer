<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use kartik\grid\SerialColumn;
use kartik\grid\ActionColumn;
use yii\widgets\Pjax;

$this->title = 'السنوات المالية';
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-calendar"></i> <?= $this->title ?></h3>
        <div class="box-tools">
            <?= Html::a('<i class="fa fa-plus"></i> إضافة سنة مالية', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
        </div>
    </div>
    <div class="box-body">
        <?php Pjax::begin(['id' => 'fiscal-years-pjax']); ?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'id' => 'fiscal-years-grid',
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'hover' => true,
            'toggleData' => false,
            'summary' => '<span class="text-muted">عرض {begin}-{end} من {totalCount}</span>',
            'pager' => [
                'firstPageLabel' => 'الأولى',
                'lastPageLabel' => 'الأخيرة',
                'prevPageLabel' => 'السابق',
                'nextPageLabel' => 'التالي',
            ],
            'columns' => [
                ['class' => SerialColumn::class, 'header' => '#'],
                [
                    'attribute' => 'name',
                    'label' => 'السنة المالية',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $badge = $model->is_current ? ' <span class="label label-primary">الحالية</span>' : '';
                        return '<strong>' . Html::encode($model->name) . '</strong>' . $badge;
                    },
                ],
                [
                    'attribute' => 'start_date',
                    'label' => 'من',
                    'contentOptions' => ['class' => 'text-center'],
                    'headerOptions' => ['class' => 'text-center'],
                ],
                [
                    'attribute' => 'end_date',
                    'label' => 'إلى',
                    'contentOptions' => ['class' => 'text-center'],
                    'headerOptions' => ['class' => 'text-center'],
                ],
                [
                    'attribute' => 'status',
                    'label' => 'الحالة',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return $model->getStatusBadge();
                    },
                    'contentOptions' => ['class' => 'text-center'],
                    'headerOptions' => ['class' => 'text-center'],
                ],
                [
                    'label' => 'الفترات',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $total = count($model->periods);
                        $closed = 0;
                        foreach ($model->periods as $p) {
                            if ($p->status === 'closed') $closed++;
                        }
                        return '<span class="badge">' . $closed . '/' . $total . ' مغلقة</span>';
                    },
                    'contentOptions' => ['class' => 'text-center'],
                    'headerOptions' => ['class' => 'text-center'],
                ],
                [
                    'class' => ActionColumn::class,
                    'header' => 'الإجراءات',
                    'template' => '{view} {update}',
                    'contentOptions' => ['class' => 'text-center', 'style' => 'white-space:nowrap;'],
                ],
            ],
        ]) ?>
        <?php Pjax::end(); ?>
    </div>
</div>
