<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use kartik\grid\SerialColumn;
use kartik\grid\ActionColumn;
use yii\widgets\Pjax;
use backend\modules\accounting\models\Account;

$this->title = 'شجرة الحسابات';
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-sitemap"></i> <?= $this->title ?></h3>
        <div class="box-tools">
            <?= Html::a('<i class="fa fa-tree"></i> عرض شجري', ['tree'], ['class' => 'btn btn-info btn-sm']) ?>
            <?= Html::a('<i class="fa fa-plus"></i> إضافة حساب', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
        </div>
    </div>
    <div class="box-body">
        <?php Pjax::begin(['id' => 'accounts-pjax']); ?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'id' => 'accounts-grid',
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
                'maxButtonCount' => 5,
            ],
            'columns' => [
                ['class' => SerialColumn::class, 'header' => '#'],
                [
                    'attribute' => 'code',
                    'label' => 'رقم الحساب',
                    'headerOptions' => ['class' => 'text-center', 'style' => 'width:100px'],
                    'contentOptions' => ['class' => 'text-center', 'style' => 'font-weight:700; font-family:monospace; font-size:14px;'],
                ],
                [
                    'attribute' => 'name_ar',
                    'label' => 'اسم الحساب',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', max(0, $model->level - 1));
                        $icon = $model->is_parent ? '<i class="fa fa-folder-open text-warning"></i>' : '<i class="fa fa-file-text-o text-muted"></i>';
                        $name = Html::encode($model->name_ar);
                        if ($model->is_parent) {
                            $name = '<strong>' . $name . '</strong>';
                        }
                        return $indent . $icon . ' ' . $name;
                    },
                ],
                [
                    'attribute' => 'type',
                    'label' => 'النوع',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return $model->getTypeBadge();
                    },
                    'filter' => Account::getTypes(),
                    'contentOptions' => ['class' => 'text-center'],
                    'headerOptions' => ['class' => 'text-center'],
                ],
                [
                    'attribute' => 'nature',
                    'label' => 'الطبيعة',
                    'value' => function ($model) {
                        return Account::getNatures()[$model->nature] ?? $model->nature;
                    },
                    'filter' => Account::getNatures(),
                    'contentOptions' => ['class' => 'text-center'],
                    'headerOptions' => ['class' => 'text-center'],
                ],
                [
                    'attribute' => 'opening_balance',
                    'label' => 'الرصيد الافتتاحي',
                    'format' => ['decimal', 2],
                    'contentOptions' => ['class' => 'text-left', 'style' => 'font-weight:600;'],
                    'headerOptions' => ['class' => 'text-center'],
                ],
                [
                    'attribute' => 'is_active',
                    'label' => 'الحالة',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return $model->is_active
                            ? '<span class="badge bg-success">فعال</span>'
                            : '<span class="badge bg-danger">غير فعال</span>';
                    },
                    'filter' => [1 => 'فعال', 0 => 'غير فعال'],
                    'contentOptions' => ['class' => 'text-center'],
                    'headerOptions' => ['class' => 'text-center'],
                ],
                [
                    'class' => ActionColumn::class,
                    'header' => 'الإجراءات',
                    'template' => '{update} {toggle} {delete}',
                    'contentOptions' => ['class' => 'text-center', 'style' => 'white-space:nowrap;'],
                    'buttons' => [
                        'toggle' => function ($url, $model) {
                            $icon = $model->is_active ? 'ban' : 'check';
                            $title = $model->is_active ? 'تعطيل' : 'تفعيل';
                            return Html::a('<i class="fa fa-' . $icon . '"></i>', ['toggle-status', 'id' => $model->id], [
                                'class' => 'btn btn-xs btn-secondary',
                                'title' => $title,
                                'data' => ['method' => 'post'],
                            ]);
                        },
                    ],
                ],
            ],
        ]) ?>
        <?php Pjax::end(); ?>
    </div>
</div>
