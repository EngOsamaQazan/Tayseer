<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use kartik\grid\SerialColumn;
use kartik\grid\ActionColumn;
use yii\widgets\Pjax;

$this->title = 'مراكز التكلفة';
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-building"></i> <?= $this->title ?></h3>
        <div class="box-tools">
            <?= Html::a('<i class="fa fa-plus"></i> إضافة مركز تكلفة', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
        </div>
    </div>
    <div class="box-body">
        <?php Pjax::begin(['id' => 'cost-centers-pjax']); ?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'id' => 'cost-centers-grid',
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
                    'attribute' => 'code',
                    'label' => 'الرمز',
                    'contentOptions' => ['class' => 'text-center', 'style' => 'font-weight:700; font-family:monospace;'],
                    'headerOptions' => ['class' => 'text-center'],
                ],
                [
                    'attribute' => 'name',
                    'label' => 'اسم المركز',
                ],
                [
                    'attribute' => 'parent_id',
                    'label' => 'المركز الرئيسي',
                    'value' => function ($model) {
                        return $model->parent ? $model->parent->code . ' - ' . $model->parent->name : '—';
                    },
                ],
                [
                    'attribute' => 'is_active',
                    'label' => 'الحالة',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return $model->is_active
                            ? '<span class="label label-success">فعال</span>'
                            : '<span class="label label-danger">غير فعال</span>';
                    },
                    'filter' => [1 => 'فعال', 0 => 'غير فعال'],
                    'contentOptions' => ['class' => 'text-center'],
                    'headerOptions' => ['class' => 'text-center'],
                ],
                [
                    'class' => ActionColumn::class,
                    'header' => 'الإجراءات',
                    'template' => '{update} {delete}',
                    'contentOptions' => ['class' => 'text-center', 'style' => 'white-space:nowrap;'],
                ],
            ],
        ]) ?>
        <?php Pjax::end(); ?>
    </div>
</div>
