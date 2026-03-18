<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use kartik\grid\SerialColumn;
use yii\widgets\Pjax;
use backend\modules\accounting\models\JournalEntry;

$this->title = 'القيود اليومية';
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-book"></i> <?= $this->title ?></h3>
        <div class="box-tools">
            <?= Html::a('<i class="fa fa-plus"></i> قيد جديد', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
        </div>
    </div>
    <div class="box-body">
        <?php Pjax::begin(['id' => 'journal-pjax']); ?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'id' => 'journal-grid',
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'hover' => true,
            'toggleData' => false,
            'showPageSummary' => true,
            'summary' => '<span class="text-muted">عرض {begin}-{end} من {totalCount}</span>',
            'pager' => [
                'firstPageLabel' => 'الأولى',
                'lastPageLabel' => 'الأخيرة',
                'prevPageLabel' => 'السابق',
                'nextPageLabel' => 'التالي',
                'maxButtonCount' => 5,
            ],
            'columns' => [
                ['class' => SerialColumn::class, 'header' => '#'],
                [
                    'attribute' => 'entry_number',
                    'label' => 'رقم القيد',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return Html::a('<strong>' . Html::encode($model->entry_number) . '</strong>', ['view', 'id' => $model->id]);
                    },
                    'contentOptions' => ['style' => 'font-family:monospace; font-size:14px;'],
                ],
                [
                    'attribute' => 'entry_date',
                    'label' => 'التاريخ',
                    'contentOptions' => ['class' => 'text-center'],
                    'headerOptions' => ['class' => 'text-center'],
                ],
                [
                    'attribute' => 'description',
                    'label' => 'البيان',
                    'value' => function ($model) {
                        return mb_substr($model->description, 0, 60) . (mb_strlen($model->description) > 60 ? '...' : '');
                    },
                ],
                [
                    'attribute' => 'total_debit',
                    'label' => 'المدين',
                    'format' => ['decimal', 2],
                    'contentOptions' => ['class' => 'text-left', 'style' => 'font-weight:600;'],
                    'headerOptions' => ['class' => 'text-center'],
                    'pageSummary' => true,
                    'pageSummaryFunc' => GridView::F_SUM,
                ],
                [
                    'attribute' => 'total_credit',
                    'label' => 'الدائن',
                    'format' => ['decimal', 2],
                    'contentOptions' => ['class' => 'text-left', 'style' => 'font-weight:600;'],
                    'headerOptions' => ['class' => 'text-center'],
                    'pageSummary' => true,
                    'pageSummaryFunc' => GridView::F_SUM,
                ],
                [
                    'attribute' => 'status',
                    'label' => 'الحالة',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return $model->getStatusBadge();
                    },
                    'filter' => JournalEntry::getStatuses(),
                    'contentOptions' => ['class' => 'text-center'],
                    'headerOptions' => ['class' => 'text-center'],
                ],
                [
                    'attribute' => 'reference_type',
                    'label' => 'المرجع',
                    'value' => function ($model) {
                        $types = JournalEntry::getReferenceTypes();
                        return $types[$model->reference_type] ?? $model->reference_type;
                    },
                    'filter' => JournalEntry::getReferenceTypes(),
                    'contentOptions' => ['class' => 'text-center'],
                    'headerOptions' => ['class' => 'text-center'],
                ],
                [
                    'header' => 'الإجراءات',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $buttons = Html::a('<i class="fa fa-eye"></i>', ['view', 'id' => $model->id], ['class' => 'btn btn-xs btn-info', 'title' => 'عرض']);
                        if ($model->status === 'draft') {
                            $buttons .= ' ' . Html::a('<i class="fa fa-edit"></i>', ['update', 'id' => $model->id], ['class' => 'btn btn-xs btn-primary', 'title' => 'تعديل']);
                        }
                        return $buttons;
                    },
                    'contentOptions' => ['class' => 'text-center', 'style' => 'white-space:nowrap;'],
                ],
            ],
        ]) ?>
        <?php Pjax::end(); ?>
    </div>
</div>
