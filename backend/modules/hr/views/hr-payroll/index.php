<?php
/**
 * مسيرات الرواتب — Payroll Runs Listing
 */

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;

$this->title = 'مسيرات الرواتب';

echo $this->render('@backend/modules/hr/views/_section_tabs', [
    'group' => 'payroll',
    'tabs'  => [
        ['label' => 'مسيرات الرواتب',   'icon' => 'fa-money',       'url' => ['/hr/hr-payroll/index']],
        ['label' => 'العلاوات السنوية', 'icon' => 'fa-line-chart',  'url' => ['/hr/hr-payroll/increments']],
        ['label' => 'السلف والقروض',    'icon' => 'fa-credit-card', 'url' => ['/hr/hr-loan/index']],
    ],
]);

$arabicMonths = [
    1  => 'يناير',  2  => 'فبراير', 3  => 'مارس',
    4  => 'أبريل',  5  => 'مايو',   6  => 'يونيو',
    7  => 'يوليو',  8  => 'أغسطس',  9  => 'سبتمبر',
    10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
];

$statusMap = [
    'draft'      => ['label' => 'مسودة',   'color' => '#6c757d'],
    'preview'    => ['label' => 'محسوبة',  'color' => '#17a2b8'],
    'calculated' => ['label' => 'محسوبة',  'color' => '#17a2b8'],
    'reviewed'   => ['label' => 'مراجعة',  'color' => '#fd7e14'],
    'approved'   => ['label' => 'معتمدة',  'color' => '#28a745'],
    'paid'       => ['label' => 'مدفوعة',  'color' => '#20c997'],
    'cancelled'  => ['label' => 'ملغاة',   'color' => '#dc3545'],
];
?>

<style>
.hr-page { padding: 20px; }
.hr-page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.hr-page-header h1 {
    font-size: 22px; font-weight: 700; color: var(--clr-primary, #800020); margin: 0;
}

.pay-status-badge {
    display: inline-block; padding: 3px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600; color: #fff;
}

.hr-grid-card {
    background: var(--clr-surface, #fff);
    border-radius: var(--radius-md, 10px);
    box-shadow: var(--shadow-sm); overflow: hidden;
}
.hr-grid-card .kv-grid-table th {
    background: #f8f9fa; font-size: 12px; font-weight: 700;
    color: var(--clr-text-muted, #6c757d); text-align: right;
}
.hr-grid-card .kv-grid-table td { font-size: 13px; vertical-align: middle; }
.hr-grid-card .kv-grid-table .amount-cell {
    font-weight: 700; direction: ltr; text-align: left;
}
</style>

<div class="hr-page">
    <!-- Header -->
    <div class="hr-page-header">
        <h1><i class="fa fa-money"></i> <?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a('<i class="fa fa-plus"></i> مسيرة جديدة', ['create'], [
                'class' => 'btn btn-primary btn-sm',
            ]) ?>
        </div>
    </div>

    <!-- Payroll Runs GridView -->
    <div class="hr-grid-card">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'responsive' => true,
            'hover' => true,
            'striped' => false,
            'condensed' => true,
            'summary' => '<div class="text-muted" style="padding:10px 16px;font-size:12px">عرض {begin}-{end} من {totalCount} مسيرة</div>',
            'tableOptions' => ['class' => 'kv-grid-table table table-bordered'],
            'columns' => [
                [
                    'attribute' => 'run_code',
                    'header' => 'رمز المسيرة',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return Html::a(
                            '<strong>' . Html::encode($model->run_code) . '</strong>',
                            ['view', 'id' => $model->id],
                            ['style' => 'color:var(--clr-primary,#800020);text-decoration:none;font-weight:700']
                        );
                    },
                ],
                [
                    'header' => 'الشهر / السنة',
                    'format' => 'raw',
                    'value' => function ($model) use ($arabicMonths) {
                        $monthName = $arabicMonths[(int) $model->period_month] ?? $model->period_month;
                        return '<i class="fa fa-calendar-o"></i> ' . Html::encode($monthName) . ' ' . Html::encode($model->period_year);
                    },
                ],
                [
                    'attribute' => 'total_employees',
                    'header' => 'عدد الموظفين',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $count = $model->total_employees ?? 0;
                        return '<span style="font-weight:700">' . number_format($count) . '</span> <i class="fa fa-user" style="color:var(--clr-text-muted,#6c757d)"></i>';
                    },
                ],
                [
                    'attribute' => 'total_amount',
                    'header' => 'إجمالي',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $val = (float) ($model->total_amount ?? 0);
                        if ($val > 0) {
                            return '<span class="amount-cell">' . number_format($val, 2) . '</span>';
                        }
                        return '<span class="text-muted">—</span>';
                    },
                    'contentOptions' => ['class' => 'amount-cell'],
                ],
                [
                    'attribute' => 'status',
                    'header' => 'الحالة',
                    'format' => 'raw',
                    'value' => function ($model) use ($statusMap) {
                        $st = $model->status ?? 'draft';
                        $info = $statusMap[$st] ?? ['label' => $st, 'color' => '#999'];
                        return '<span class="pay-status-badge" style="background:' . $info['color'] . '">' . $info['label'] . '</span>';
                    },
                ],
                [
                    'header' => 'تاريخ الإنشاء',
                    'value' => function ($model) {
                        return $model->created_at ? date('Y-m-d', $model->created_at) : '—';
                    },
                ],
                [
                    'class' => 'kartik\grid\ActionColumn',
                    'header' => 'إجراءات',
                    'template' => '{view}',
                    'buttons' => [
                        'view' => function ($url, $model) {
                            return Html::a(
                                '<i class="fa fa-eye"></i> عرض',
                                ['view', 'id' => $model->id],
                                ['class' => 'btn btn-xs btn-primary']
                            );
                        },
                    ],
                ],
            ],
        ]); ?>
    </div>
</div>
