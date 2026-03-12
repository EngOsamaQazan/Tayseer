<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  السلف والقروض — Loans & Advances Listing
 *  ──────────────────────────────────────
 *  بطاقات ملخص + قائمة GridView مع شارات النوع والحالة
 * ═══════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;
use yii\widgets\Pjax;
use backend\helpers\NameHelper;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var float $totalAmount */
/** @var float $totalRepaid */
/** @var int $activeCount */

$this->title = 'السلف والقروض';

echo $this->render('@backend/modules/hr/views/_section_tabs', [
    'group' => 'payroll',
    'tabs'  => [
        ['label' => 'مسيرات الرواتب',   'icon' => 'fa-money',       'url' => ['/hr/hr-payroll/index']],
        ['label' => 'العلاوات السنوية', 'icon' => 'fa-line-chart',  'url' => ['/hr/hr-payroll/increments']],
        ['label' => 'السلف والقروض',    'icon' => 'fa-credit-card', 'url' => ['/hr/hr-loan/index']],
    ],
]);

/* ─── Register HR CSS ─── */
$this->registerCssFile(Yii::getAlias('@web') . '/css/hr.css', ['depends' => ['yii\web\YiiAsset']]);

/* ─── Safe defaults ─── */
$totalAmount = isset($totalAmount) ? (float) $totalAmount : 0;
$totalRepaid = isset($totalRepaid) ? (float) $totalRepaid : 0;
$activeCount = isset($activeCount) ? (int) $activeCount   : 0;
$remaining   = $totalAmount - $totalRepaid;

/* ─── Type map ─── */
$typeMap = [
    'advance' => ['label' => 'سلفة',   'color' => '#3498db', 'bg' => '#ebf5fb'],
    'loan'    => ['label' => 'قرض',    'color' => '#9b59b6', 'bg' => '#f4ecf7'],
    'penalty' => ['label' => 'جزاء',   'color' => '#e74c3c', 'bg' => '#fdedec'],
];

/* ─── Status map ─── */
$statusMap = [
    'pending'   => ['label' => 'معلق',    'color' => '#f39c12', 'bg' => '#fef9e7'],
    'active'    => ['label' => 'نشط',     'color' => '#27ae60', 'bg' => '#eafaf1'],
    'completed' => ['label' => 'مكتمل',   'color' => '#95a5a6', 'bg' => '#ecf0f1'],
    'cancelled' => ['label' => 'ملغي',    'color' => '#e74c3c', 'bg' => '#fdedec'],
];
?>

<style>
/* ═══════════════════════════════════════
   Loans — Page-specific Styles
   ═══════════════════════════════════════ */
.loan-type-badge {
    display: inline-block; padding: 3px 12px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
}
.loan-status-badge {
    display: inline-block; padding: 3px 12px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
}
.loan-amount {
    font-weight: 700; direction: ltr; text-align: left; white-space: nowrap;
}

.hr-grid-card {
    background: var(--hr-card-bg, #fff);
    border-radius: var(--hr-radius-md, 10px);
    box-shadow: var(--hr-shadow-sm); overflow: hidden;
}
.hr-grid-card .kv-grid-table th {
    background: #f8f9fa; font-size: 12px; font-weight: 700;
    color: var(--hr-text-muted, #6c757d); text-align: right;
}
.hr-grid-card .kv-grid-table td { font-size: 13px; vertical-align: middle; }

/* Progress mini-bar for repayment */
.loan-progress {
    display: flex; align-items: center; gap: 6px;
}
.loan-progress__bar {
    flex: 1; height: 5px; background: #ecf0f1; border-radius: 3px;
    overflow: hidden; min-width: 50px;
}
.loan-progress__fill {
    height: 100%; border-radius: 3px; background: var(--hr-success, #27ae60);
    transition: width 0.4s ease;
}
</style>

<div class="hr-page">

    <!-- ╔═══════════════════════════════════════╗
         ║  العنوان وأزرار الإجراءات             ║
         ╚═══════════════════════════════════════╝ -->
    <div class="hr-header">
        <h1><i class="fa fa-credit-card"></i> <?= Html::encode($this->title) ?></h1>
        <div style="display:flex;gap:8px;align-items:center">
            <?= Html::a(
                '<i class="fa fa-plus"></i> سلفة جديدة',
                Url::to(['create']),
                ['class' => 'hr-btn hr-btn--primary']
            ) ?>
        </div>
    </div>

    <!-- ╔═══════════════════════════════════════╗
         ║  بطاقات ملخص                          ║
         ╚═══════════════════════════════════════╝ -->
    <div class="hr-kpi-grid hr-kpi-grid--4">
        <!-- إجمالي السلف -->
        <div class="hr-kpi-card" style="--kpi-accent: var(--hr-primary, #800020)">
            <div class="hr-kpi-card__icon"><i class="fa fa-database"></i></div>
            <div class="hr-kpi-card__body">
                <span class="hr-kpi-card__label">إجمالي السلف</span>
                <span class="hr-kpi-card__value" style="direction:ltr;text-align:right"><?= number_format($totalAmount, 2) ?></span>
                <span class="hr-kpi-card__sub">د.أ</span>
            </div>
        </div>

        <!-- سلف نشطة -->
        <div class="hr-kpi-card" style="--kpi-accent: var(--hr-info, #3498db)">
            <div class="hr-kpi-card__icon"><i class="fa fa-refresh"></i></div>
            <div class="hr-kpi-card__body">
                <span class="hr-kpi-card__label">سلف نشطة</span>
                <span class="hr-kpi-card__value"><?= number_format($activeCount) ?></span>
            </div>
        </div>

        <!-- مبلغ مسدد -->
        <div class="hr-kpi-card" style="--kpi-accent: var(--hr-success, #27ae60)">
            <div class="hr-kpi-card__icon"><i class="fa fa-check-circle"></i></div>
            <div class="hr-kpi-card__body">
                <span class="hr-kpi-card__label">مبلغ مسدد</span>
                <span class="hr-kpi-card__value" style="direction:ltr;text-align:right"><?= number_format($totalRepaid, 2) ?></span>
                <span class="hr-kpi-card__sub">د.أ</span>
            </div>
        </div>

        <!-- مبلغ متبقي -->
        <div class="hr-kpi-card" style="--kpi-accent: var(--hr-warning, #f39c12)">
            <div class="hr-kpi-card__icon"><i class="fa fa-hourglass-half"></i></div>
            <div class="hr-kpi-card__body">
                <span class="hr-kpi-card__label">مبلغ متبقي</span>
                <span class="hr-kpi-card__value" style="direction:ltr;text-align:right"><?= number_format($remaining, 2) ?></span>
                <span class="hr-kpi-card__sub">د.أ</span>
            </div>
        </div>
    </div>

    <!-- ╔═══════════════════════════════════════╗
         ║  جدول السلف — GridView                 ║
         ╚═══════════════════════════════════════╝ -->
    <?php Pjax::begin(['id' => 'loans-pjax', 'timeout' => 10000]); ?>

    <div class="hr-grid-card">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'responsive' => true,
            'hover' => true,
            'striped' => false,
            'condensed' => true,
            'pjax' => false,
            'summary' => '<div class="text-muted" style="padding:10px 16px;font-size:12px">عرض {begin}-{end} من {totalCount} سجل</div>',
            'layout' => "{summary}\n{items}\n<div style='display:flex;justify-content:center;padding:12px 0'>{pager}</div>",
            'tableOptions' => ['class' => 'kv-grid-table table table-bordered'],
            'pager' => [
                'options' => ['class' => 'pagination hr-pagination'],
                'maxButtonCount' => 7,
                'firstPageLabel' => '<i class="fa fa-angle-double-right"></i>',
                'lastPageLabel'  => '<i class="fa fa-angle-double-left"></i>',
                'prevPageLabel'  => '<i class="fa fa-angle-right"></i>',
                'nextPageLabel'  => '<i class="fa fa-angle-left"></i>',
            ],
            'columns' => [
                [
                    'class' => 'kartik\grid\SerialColumn',
                    'header' => '#',
                    'headerOptions' => ['style' => 'width:50px;text-align:center'],
                    'contentOptions' => ['style' => 'text-align:center;font-weight:600;color:#6b7280'],
                ],
                [
                    'header' => 'الموظف',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $user = $model->user;
                        $name = $user ? ($user->name ? NameHelper::short($user->name) : ($user->username ?? '—')) : '—';
                        return '<strong>' . Html::encode($name) . '</strong>';
                    },
                ],
                [
                    'header' => 'النوع',
                    'headerOptions' => ['style' => 'text-align:center;width:90px'],
                    'contentOptions' => ['style' => 'text-align:center'],
                    'format' => 'raw',
                    'value' => function ($model) use ($typeMap) {
                        $type = $model->type ?? $model->loan_type ?? 'advance';
                        $info = $typeMap[$type] ?? ['label' => $type, 'color' => '#95a5a6', 'bg' => '#ecf0f1'];
                        return '<span class="loan-type-badge" style="background:' . $info['bg'] . ';color:' . $info['color'] . '">' . $info['label'] . '</span>';
                    },
                ],
                [
                    'header' => 'المبلغ',
                    'headerOptions' => ['style' => 'text-align:center;width:110px'],
                    'contentOptions' => ['style' => 'text-align:center'],
                    'format' => 'raw',
                    'value' => function ($model) {
                        $amount = (float) ($model->amount ?? 0);
                        return '<span class="loan-amount" style="color:var(--hr-primary,#800020)">' . number_format($amount, 2) . '</span>';
                    },
                ],
                [
                    'header' => 'المسدد',
                    'headerOptions' => ['style' => 'text-align:center;width:130px'],
                    'contentOptions' => ['style' => 'text-align:center'],
                    'format' => 'raw',
                    'value' => function ($model) {
                        $amount = (float) ($model->amount ?? 0);
                        $repaid = (float) ($model->total_repaid ?? $model->repaid_amount ?? 0);
                        $pct = $amount > 0 ? min(100, round(($repaid / $amount) * 100)) : 0;
                        $html  = '<span class="loan-amount" style="color:var(--hr-success,#27ae60)">' . number_format($repaid, 2) . '</span>';
                        $html .= '<div class="loan-progress" style="margin-top:3px">';
                        $html .= '  <div class="loan-progress__bar"><div class="loan-progress__fill" style="width:' . $pct . '%"></div></div>';
                        $html .= '  <small style="color:var(--hr-text-muted,#95a5a6);font-size:11px">' . $pct . '%</small>';
                        $html .= '</div>';
                        return $html;
                    },
                ],
                [
                    'header' => 'القسط الشهري',
                    'headerOptions' => ['style' => 'text-align:center;width:100px'],
                    'contentOptions' => ['style' => 'text-align:center'],
                    'format' => 'raw',
                    'value' => function ($model) {
                        $installment = (float) ($model->monthly_installment ?? $model->installment_amount ?? 0);
                        if ($installment > 0) {
                            return '<span class="loan-amount">' . number_format($installment, 2) . '</span>';
                        }
                        return '<span class="text-muted">—</span>';
                    },
                ],
                [
                    'header' => 'الأقساط المتبقية',
                    'headerOptions' => ['style' => 'text-align:center;width:100px'],
                    'contentOptions' => ['style' => 'text-align:center;font-weight:600'],
                    'format' => 'raw',
                    'value' => function ($model) {
                        $remaining = $model->remaining_installments ?? null;
                        if ($remaining !== null) {
                            $color = $remaining > 0 ? 'var(--hr-warning,#f39c12)' : 'var(--hr-success,#27ae60)';
                            return '<span style="color:' . $color . '">' . (int) $remaining . '</span>';
                        }
                        return '<span class="text-muted">—</span>';
                    },
                ],
                [
                    'header' => 'الحالة',
                    'headerOptions' => ['style' => 'text-align:center;width:90px'],
                    'contentOptions' => ['style' => 'text-align:center'],
                    'format' => 'raw',
                    'value' => function ($model) use ($statusMap) {
                        $status = $model->status ?? 'pending';
                        $info = $statusMap[$status] ?? ['label' => $status, 'color' => '#95a5a6', 'bg' => '#ecf0f1'];
                        return '<span class="loan-status-badge" style="background:' . $info['bg'] . ';color:' . $info['color'] . '">' . $info['label'] . '</span>';
                    },
                ],
                [
                    'class' => 'kartik\grid\ActionColumn',
                    'header' => 'إجراءات',
                    'headerOptions' => ['style' => 'text-align:center;width:120px'],
                    'contentOptions' => ['style' => 'text-align:center;white-space:nowrap'],
                    'template' => '{view} {update} {delete}',
                    'buttons' => [
                        'view' => function ($url, $model) {
                            return Html::a(
                                '<i class="fa fa-eye"></i>',
                                ['view', 'id' => $model->id],
                                [
                                    'class' => 'btn btn-sm btn-default',
                                    'title' => 'عرض',
                                    'style' => 'border-radius:8px;width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;color:#0284c7',
                                    'data-toggle' => 'tooltip',
                                ]
                            );
                        },
                        'update' => function ($url, $model) {
                            return Html::a(
                                '<i class="fa fa-pencil"></i>',
                                ['update', 'id' => $model->id],
                                [
                                    'class' => 'btn btn-sm btn-default',
                                    'title' => 'تعديل',
                                    'style' => 'border-radius:8px;width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;color:#d97706',
                                    'data-toggle' => 'tooltip',
                                ]
                            );
                        },
                        'delete' => function ($url, $model) {
                            return Html::a(
                                '<i class="fa fa-trash"></i>',
                                ['delete', 'id' => $model->id],
                                [
                                    'class' => 'btn btn-sm btn-default',
                                    'title' => 'حذف',
                                    'style' => 'border-radius:8px;width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;color:#dc2626',
                                    'data-toggle' => 'tooltip',
                                    'data-confirm' => 'هل أنت متأكد من حذف هذا السجل؟',
                                    'data-method' => 'post',
                                    'data-pjax' => '1',
                                ]
                            );
                        },
                    ],
                ],
            ],
        ]); ?>
    </div>

    <?php Pjax::end(); ?>

</div><!-- /.hr-page -->

<?php
$js = <<<JS
$('[data-toggle="tooltip"]').tooltip({container: 'body', placement: 'top'});
$(document).on('pjax:complete', function() {
    $('[data-toggle="tooltip"]').tooltip({container: 'body', placement: 'top'});
});
JS;
$this->registerJs($js, \yii\web\View::POS_READY);
?>
