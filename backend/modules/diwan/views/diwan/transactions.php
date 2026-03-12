<?php
/**
 * قسم الديوان — قائمة المعاملات
 */

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;
use backend\modules\diwan\models\DiwanTransaction;
use common\helper\Permissions;

$this->title = 'قسم الديوان';

$baseDiwan      = Permissions::DIWAN;
$canDiwanDelete = Permissions::can(Permissions::DIWAN_DELETE) || Yii::$app->user->can($baseDiwan);
?>

<?= $this->render('@app/views/layouts/_diwan-tabs', ['activeTab' => 'transactions']) ?>

<style>
.dw-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.dw-badge--recv { background: #e8f5e9; color: #2e7d32; }
.dw-badge--dlvr { background: #fff3e0; color: #e65100; }
</style>

<div class="diwan-transactions">

    <?= GridView::widget([
        'id' => 'diwan-transactions-grid',
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'responsive' => true,
        'hover' => true,
        'striped' => false,
        'bordered' => false,
        'tableOptions' => ['class' => 'table table-hover', 'style' => 'margin-bottom:0;'],
        'panel' => [
            'type' => GridView::TYPE_DEFAULT,
            'heading' => false,
        ],
        'pjax' => true,
        'pjaxSettings' => [
            'options' => ['id' => 'diwan-pjax'],
            'neverTimeout' => true,
        ],
        'columns' => [
            [
                'attribute' => 'id',
                'label' => '#',
                'headerOptions' => ['style' => 'width:60px'],
                'contentOptions' => ['style' => 'font-weight:700'],
            ],
            [
                'attribute' => 'transaction_type',
                'label' => 'النوع',
                'filter' => DiwanTransaction::getTypeList(),
                'format' => 'raw',
                'value' => function ($m) {
                    $cls = $m->transaction_type === 'استلام' ? 'dw-badge--recv' : 'dw-badge--dlvr';
                    return '<span class="dw-badge ' . $cls . '">' . Html::encode($m->transaction_type) . '</span>';
                },
            ],
            [
                'attribute' => 'from_employee_id',
                'label' => 'من',
                'filter' => $employees,
                'value' => function ($m) {
                    return $m->fromEmployee ? ($m->fromEmployee->name ?: $m->fromEmployee->username) : '—';
                },
            ],
            [
                'attribute' => 'to_employee_id',
                'label' => 'إلى',
                'filter' => $employees,
                'value' => function ($m) {
                    return $m->toEmployee ? ($m->toEmployee->name ?: $m->toEmployee->username) : '—';
                },
            ],
            [
                'label' => 'العقود',
                'format' => 'raw',
                'value' => function ($m) {
                    $count = count($m->details);
                    return '<span class="badge" style="background:var(--fin-primary,#800020)">' . $count . '</span>';
                },
            ],
            [
                'attribute' => 'transaction_date',
                'label' => 'التاريخ',
                'format' => ['datetime', 'php:Y/m/d h:i A'],
                'filter' => Html::activeTextInput($searchModel, 'transaction_date', [
                    'class' => 'form-control', 'type' => 'date',
                ]),
                'contentOptions' => ['style' => 'font-size:12px;'],
            ],
            [
                'attribute' => 'receipt_number',
                'label' => 'الإيصال',
                'format' => 'raw',
                'value' => function ($m) {
                    return '<code style="font-size:11px">' . Html::encode($m->receipt_number) . '</code>';
                },
            ],
            [
                'class' => 'kartik\grid\ActionColumn',
                'header' => '',
                'template' => $canDiwanDelete ? '{view} {receipt} {delete}' : '{view} {receipt}',
                'buttons' => [
                    'view' => function ($url, $m) {
                        return Html::a('<i class="fa fa-eye"></i>', ['view', 'id' => $m->id], [
                            'class' => 'btn btn-xs btn-default', 'title' => 'عرض',
                        ]);
                    },
                    'receipt' => function ($url, $m) {
                        return Html::a('<i class="fa fa-print"></i>', ['receipt', 'id' => $m->id], [
                            'class' => 'btn btn-xs btn-default', 'title' => 'إيصال', 'target' => '_blank',
                        ]);
                    },
                    'delete' => function ($url, $m) {
                        return Html::a('<i class="fa fa-trash"></i>', ['delete', 'id' => $m->id], [
                            'class' => 'btn btn-xs btn-danger',
                            'title' => 'حذف',
                            'data-method' => 'post',
                            'data-confirm' => 'هل أنت متأكد من حذف هذه المعاملة؟',
                        ]);
                    },
                ],
                'headerOptions' => ['style' => 'width:120px'],
            ],
        ],
        'summary' => '<span style="font-size:12px; color:#888;">عرض {begin}-{end} من {totalCount} معاملة</span>',
    ]) ?>
</div>
