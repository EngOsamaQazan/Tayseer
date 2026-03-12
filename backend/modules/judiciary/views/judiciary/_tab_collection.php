<?php
/**
 * تبويب قسم الحسم — داخل شاشة القسم القانوني
 */
use yii\helpers\Url;
use yii\helpers\Html;
use kartik\grid\GridView;
use common\helper\Permissions;
use backend\widgets\ExportButtons;

/* @var $searchModel \backend\modules\collection\models\CollectionSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $amount float */
/* @var $count_contract int */
?>

<div class="coll-stats">
    <div class="coll-stat">
        <div class="coll-stat-icon" style="background:#FDF2F4;color:#800020">
            <i class="fa fa-gavel"></i>
        </div>
        <div>
            <div class="coll-stat-val" style="color:#1E293B"><?= $count_contract ?></div>
            <div class="coll-stat-lbl">عدد قضايا الحسم</div>
        </div>
    </div>
    <div class="coll-stat">
        <div class="coll-stat-icon" style="background:#ECFDF5;color:#059669">
            <i class="fa fa-money"></i>
        </div>
        <div>
            <div class="coll-stat-val" style="color:#059669"><?= Yii::$app->formatter->asDecimal($amount, 2) ?></div>
            <div class="coll-stat-lbl">المتاح للقبض</div>
        </div>
    </div>
</div>

<div class="collection-index" style="padding:0 4px">
    <div id="ajaxCrudDatatable-collection">
        <?= GridView::widget([
            'id' => 'crud-datatable-collection',
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'summary' => '<span style="font-size:12px;color:#64748B">عرض {begin}–{end} من {totalCount} — قسم الحسم</span>',
            'columns' => [
                [
                    'class' => '\kartik\grid\DataColumn',
                    'attribute' => 'contract_id',
                    'label' => 'رقم العقد',
                    'headerOptions' => ['style' => 'width:80px'],
                    'contentOptions' => ['style' => 'font-weight:700;white-space:nowrap', 'data-label' => 'رقم العقد'],
                ],
                [
                    'class' => '\kartik\grid\DataColumn',
                    'attribute' => 'date',
                    'label' => 'التاريخ',
                    'headerOptions' => ['style' => 'width:100px'],
                    'contentOptions' => ['style' => 'white-space:nowrap;font-size:12px', 'data-label' => 'التاريخ'],
                ],
                [
                    'class' => '\kartik\grid\DataColumn',
                    'attribute' => 'amount',
                    'label' => 'المبلغ',
                    'headerOptions' => ['style' => 'width:80px'],
                    'contentOptions' => ['style' => 'font-weight:600;white-space:nowrap', 'data-label' => 'المبلغ'],
                    'format' => ['decimal', 2],
                ],
                [
                    'class' => '\kartik\grid\DataColumn',
                    'attribute' => 'notes',
                    'label' => 'ملاحظات',
                    'headerOptions' => ['style' => 'width:220px'],
                    'contentOptions' => ['class' => 'coll-notes-cell', 'data-label' => 'ملاحظات'],
                    'format' => 'text',
                ],
                [
                    'class' => '\kartik\grid\DataColumn',
                    'attribute' => 'created_by',
                    'label' => 'اسم الموظف',
                    'value' => 'createdBy.username',
                    'headerOptions' => ['style' => 'width:100px'],
                    'contentOptions' => ['style' => 'white-space:nowrap;font-size:12px', 'data-label' => 'الموظف'],
                ],
                [
                    'class' => '\kartik\grid\DataColumn',
                    'label' => 'المتاح للقبض',
                    'headerOptions' => ['style' => 'width:110px'],
                    'contentOptions' => function ($model) {
                        $d1 = new DateTime($model->date);
                        $d2 = new DateTime(date('Y-m-d'));
                        $interval = $d1->diff($d2);
                        $diffInMonths = $interval->m + 1;
                        $revares_courts = backend\modules\financialTransaction\models\FinancialTransaction::find()
                            ->where(['contract_id' => $model->contract_id])
                            ->andWhere(['income_type' => 11])->all();
                        $revares = 0;
                        foreach ($revares_courts as $r) $revares += $r->amount;
                        $value = ($diffInMonths * $model->amount) - $revares;
                        $cls = $value < 0 ? 'coll-amount-cell coll-amount-neg' : 'coll-amount-cell coll-amount-pos';
                        return ['class' => $cls, 'data-label' => 'المتاح للقبض'];
                    },
                    'value' => function ($model) {
                        $d1 = new DateTime($model->date);
                        $d2 = new DateTime(date('Y-m-d'));
                        $interval = $d1->diff($d2);
                        $diffInMonths = $interval->m + 1;
                        $revares_courts = backend\modules\financialTransaction\models\FinancialTransaction::find()
                            ->where(['contract_id' => $model->contract_id])
                            ->andWhere(['income_type' => 11])->all();
                        $revares = 0;
                        foreach ($revares_courts as $r) $revares += $r->amount;
                        return ($diffInMonths * $model->amount) - $revares;
                    },
                    'format' => ['decimal', 2],
                ],
                [
                    'class' => 'kartik\grid\ActionColumn',
                    'dropdown' => false,
                    'vAlign' => 'middle',
                    'headerOptions' => ['style' => 'width:90px'],
                    'contentOptions' => ['style' => 'white-space:nowrap', 'data-label' => ''],
                    'template' => (Permissions::can(Permissions::COLL_VIEW) ? '{view}' : '')
                        . (Permissions::can(Permissions::COLL_UPDATE) ? '{update}' : '')
                        . (Permissions::can(Permissions::COLL_DELETE) ? '{delete}' : ''),
                    'urlCreator' => function ($action, $model, $key, $index) {
                        return Url::to(['/collection/collection/' . $action, 'id' => $key]);
                    },
                    'viewOptions' => ['title' => 'عرض', 'data-toggle' => 'tooltip'],
                    'updateOptions' => ['title' => 'تعديل', 'data-toggle' => 'tooltip'],
                    'deleteOptions' => [
                        'title' => 'حذف',
                        'data-confirm' => false, 'data-method' => false,
                        'data-request-method' => 'post',
                        'data-toggle' => 'tooltip',
                        'data-confirm-title' => 'هل أنت متأكد؟',
                        'data-confirm-message' => 'هل تريد حذف هذا العنصر؟',
                    ],
                ],
            ],
            'toolbar' => [
                ['content' =>
                    Html::a('<i class="fa fa-refresh"></i>', Url::to(['/judiciary/judiciary/index', 'tab' => 'collection']),
                        ['data-pjax' => 1, 'class' => 'btn btn-default', 'title' => 'تحديث']) .
                    '{toggleData}' .
                    ExportButtons::widget([
                        'excelRoute' => ['/collection/collection/export-excel'],
                        'pdfRoute' => ['/collection/collection/export-pdf'],
                    ])
                ],
            ],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'type' => 'default',
                'heading' => '<i class="fa fa-handshake-o"></i> قسم الحسم',
            ],
        ]) ?>
    </div>
</div>
