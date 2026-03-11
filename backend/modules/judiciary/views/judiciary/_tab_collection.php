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

<style>
.coll-stats {
    display: flex; align-items: center; gap: 24px; flex-wrap: wrap;
    padding: 14px 16px; border-bottom: 1px solid #E2E8F0; margin-bottom: 4px;
}
.coll-stat {
    display: flex; align-items: center; gap: 10px;
    background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px;
    padding: 10px 16px;
}
.coll-stat-icon {
    width: 36px; height: 36px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; flex-shrink: 0;
}
.coll-stat-val { font-size: 18px; font-weight: 700; line-height: 1.2; }
.coll-stat-lbl { font-size: 11px; color: #64748B; }

/* Table fixes for this tab */
#crud-datatable-collection .kv-grid-table { table-layout: fixed !important; width: 100% !important; }
#crud-datatable-collection .kv-grid-table td,
#crud-datatable-collection .kv-grid-table th { word-wrap: break-word; overflow-wrap: break-word; }
#crud-datatable-collection .coll-notes-cell {
    max-width: 200px; overflow: hidden; text-overflow: ellipsis;
    white-space: nowrap; cursor: pointer; font-size: 11px; color: #475569;
}
#crud-datatable-collection .coll-notes-cell:hover { white-space: normal; background: #FFFBEB; }
#crud-datatable-collection .coll-amount-cell { font-weight: 700; white-space: nowrap; }
#crud-datatable-collection .coll-amount-neg { color: #DC2626; }
#crud-datatable-collection .coll-amount-pos { color: #059669; }

/* ═══ Responsive ═══ */
@media (max-width:992px) {
    #crud-datatable-collection .kv-grid-container { overflow-x:auto !important; -webkit-overflow-scrolling:touch; }
    #crud-datatable-collection .kv-grid-table { min-width:550px; }
}
@media (max-width:767px) {
    .coll-stats { gap:8px; padding:10px 12px; }
    .coll-stat { padding:8px 12px; flex:1; min-width:120px; }
    .coll-stat-val { font-size:15px; }
    .coll-stat-icon { width:30px; height:30px; font-size:13px; }
    .coll-stat-lbl { font-size:10px; }

    #crud-datatable-collection .kv-grid-container { overflow:visible !important; }
    #crud-datatable-collection .kv-grid-table { min-width:0; table-layout:auto !important; }
    #crud-datatable-collection .kv-grid-table thead { display:none; }
    #crud-datatable-collection .kv-grid-table tbody tr {
        display:block; background:#fff; border:1px solid #E2E8F0;
        border-radius:10px; margin-bottom:8px; padding:10px 12px;
        box-shadow:0 1px 3px rgba(0,0,0,.04);
    }
    #crud-datatable-collection .kv-grid-table tbody tr:hover { background:#FFFBEB; }
    #crud-datatable-collection .kv-grid-table tbody td {
        display:flex; justify-content:space-between; align-items:center;
        padding:3px 0 !important; border:none !important; font-size:12px;
        white-space:normal !important; max-width:none !important;
    }
    #crud-datatable-collection .kv-grid-table tbody td::before {
        content:attr(data-label); font-weight:600; color:#64748B;
        font-size:11px; min-width:70px; flex-shrink:0;
    }
    #crud-datatable-collection .kv-grid-table tbody td:last-child {
        justify-content:flex-end; padding-top:6px !important;
        margin-top:4px; border-top:1px solid #F1F5F9 !important;
    }
    #crud-datatable-collection .kv-grid-table .filters { display:none; }
    #crud-datatable-collection .panel-heading { font-size:12px; padding:8px 10px !important; }
    #crud-datatable-collection .panel-heading .pull-right {
        float:none !important; margin-top:6px; display:flex; flex-wrap:wrap; gap:3px;
    }
    #crud-datatable-collection .pagination { flex-wrap:wrap; justify-content:center; gap:2px; }
    #crud-datatable-collection .pagination>li>a,
    #crud-datatable-collection .pagination>li>span {
        padding:3px 6px; font-size:10px; min-width:28px; min-height:28px;
        display:inline-flex; align-items:center; justify-content:center;
    }
}
@media (max-width:480px) {
    .coll-stats { flex-direction:column; }
    .coll-stat { min-width:0; width:100%; }
    #crud-datatable-collection .kv-grid-table tbody td { font-size:11px; }
    #crud-datatable-collection .kv-grid-table tbody td::before { font-size:10px; min-width:60px; }
}
</style>

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
