<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  تقييمات الأداء — Performance Evaluations Listing
 *  ──────────────────────────────────────
 *  قائمة تقييمات الأداء مع GridView وشارات الدرجات والحالات
 * ═══════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'تقييمات الأداء';

/* ─── Register HR CSS ─── */
$this->registerCssFile(Yii::getAlias('@web') . '/css/hr.css', ['depends' => ['yii\web\YiiAsset']]);

/* ─── Grade map ─── */
$gradeMap = [
    'A' => ['label' => 'ممتاز',   'color' => '#27ae60', 'bg' => '#eafaf1'],
    'B' => ['label' => 'جيد جداً', 'color' => '#3498db', 'bg' => '#ebf5fb'],
    'C' => ['label' => 'جيد',     'color' => '#f39c12', 'bg' => '#fef9e7'],
    'D' => ['label' => 'مقبول',   'color' => '#e67e22', 'bg' => '#fdebd0'],
    'F' => ['label' => 'ضعيف',    'color' => '#e74c3c', 'bg' => '#fdedec'],
];

/* ─── Status map ─── */
$statusMap = [
    'draft'     => ['label' => 'مسودة',  'color' => '#95a5a6', 'bg' => '#ecf0f1'],
    'submitted' => ['label' => 'مرسل',   'color' => '#3498db', 'bg' => '#ebf5fb'],
    'reviewed'  => ['label' => 'مراجع',  'color' => '#e67e22', 'bg' => '#fdebd0'],
    'approved'  => ['label' => 'معتمد',  'color' => '#27ae60', 'bg' => '#eafaf1'],
];
?>

<style>
/* ═══════════════════════════════════════
   Evaluations — Page-specific Styles
   ═══════════════════════════════════════ */
.eval-grade-badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 34px; height: 34px; border-radius: 8px;
    font-size: 16px; font-weight: 800; letter-spacing: -0.3px;
}
.eval-rating-label {
    display: inline-block; padding: 3px 12px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
}
.eval-status-badge {
    display: inline-block; padding: 3px 12px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
}
.eval-score-bar {
    display: flex; align-items: center; gap: 8px;
}
.eval-score-bar__track {
    flex: 1; height: 6px; background: #ecf0f1; border-radius: 3px;
    overflow: hidden; min-width: 60px;
}
.eval-score-bar__fill {
    height: 100%; border-radius: 3px; transition: width 0.4s ease;
}
.eval-score-bar__value {
    font-size: 13px; font-weight: 700; color: var(--hr-text, #2c3e50);
    min-width: 32px; text-align: left; direction: ltr;
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
</style>

<div class="hr-page">

    <!-- ╔═══════════════════════════════════════╗
         ║  العنوان وأزرار الإجراءات             ║
         ╚═══════════════════════════════════════╝ -->
    <div class="hr-header">
        <h1><i class="fa fa-star-half-o"></i> <?= Html::encode($this->title) ?></h1>
        <div style="display:flex;gap:8px;align-items:center">
            <?= Html::a(
                '<i class="fa fa-plus"></i> تقييم جديد',
                Url::to(['create']),
                ['class' => 'hr-btn hr-btn--primary']
            ) ?>
        </div>
    </div>

    <!-- ╔═══════════════════════════════════════╗
         ║  جدول التقييمات — GridView             ║
         ╚═══════════════════════════════════════╝ -->
    <?php Pjax::begin(['id' => 'evaluations-pjax', 'timeout' => 10000]); ?>

    <div class="hr-grid-card">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'responsive' => true,
            'hover' => true,
            'striped' => false,
            'condensed' => true,
            'pjax' => false,
            'summary' => '<div class="text-muted" style="padding:10px 16px;font-size:12px">عرض {begin}-{end} من {totalCount} تقييم</div>',
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
                        $name = $model->employee->name ?? ($model->employee->username ?? '—');
                        return '<strong>' . Html::encode($name) . '</strong>';
                    },
                ],
                [
                    'header' => 'المقيِّم',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $name = $model->evaluator->name ?? ($model->evaluator->username ?? '—');
                        return Html::encode($name);
                    },
                ],
                [
                    'header' => 'الفترة',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $period = $model->period ?? $model->evaluation_period ?? '—';
                        return '<span style="white-space:nowrap"><i class="fa fa-calendar-o" style="color:var(--hr-text-muted,#95a5a6);margin-left:4px"></i> ' . Html::encode($period) . '</span>';
                    },
                ],
                [
                    'header' => 'الدرجة',
                    'headerOptions' => ['style' => 'text-align:center;width:70px'],
                    'contentOptions' => ['style' => 'text-align:center'],
                    'format' => 'raw',
                    'value' => function ($model) use ($gradeMap) {
                        $grade = $model->grade ?? null;
                        if (!$grade) return '<span class="text-muted">—</span>';
                        $info = $gradeMap[$grade] ?? ['label' => $grade, 'color' => '#95a5a6', 'bg' => '#ecf0f1'];
                        return '<span class="eval-grade-badge" style="background:' . $info['bg'] . ';color:' . $info['color'] . '">' . Html::encode($grade) . '</span>';
                    },
                ],
                [
                    'header' => 'التقدير',
                    'headerOptions' => ['style' => 'text-align:center;width:120px'],
                    'contentOptions' => ['style' => 'text-align:center'],
                    'format' => 'raw',
                    'value' => function ($model) use ($gradeMap) {
                        $grade = $model->grade ?? null;
                        $score = isset($model->score) ? (float) $model->score : null;
                        if (!$grade) return '<span class="text-muted">—</span>';
                        $info = $gradeMap[$grade] ?? ['label' => $grade, 'color' => '#95a5a6', 'bg' => '#ecf0f1'];
                        $html = '<span class="eval-rating-label" style="background:' . $info['bg'] . ';color:' . $info['color'] . '">' . $info['label'] . '</span>';
                        if ($score !== null) {
                            $pct = min(100, max(0, $score));
                            $html .= '<div class="eval-score-bar" style="margin-top:4px">';
                            $html .= '  <div class="eval-score-bar__track"><div class="eval-score-bar__fill" style="width:' . $pct . '%;background:' . $info['color'] . '"></div></div>';
                            $html .= '  <span class="eval-score-bar__value">' . number_format($score, 0) . '%</span>';
                            $html .= '</div>';
                        }
                        return $html;
                    },
                ],
                [
                    'header' => 'الحالة',
                    'headerOptions' => ['style' => 'text-align:center;width:100px'],
                    'contentOptions' => ['style' => 'text-align:center'],
                    'format' => 'raw',
                    'value' => function ($model) use ($statusMap) {
                        $status = $model->status ?? 'draft';
                        $info = $statusMap[$status] ?? ['label' => $status, 'color' => '#95a5a6', 'bg' => '#ecf0f1'];
                        return '<span class="eval-status-badge" style="background:' . $info['bg'] . ';color:' . $info['color'] . '">' . $info['label'] . '</span>';
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
                                    'data-confirm' => 'هل أنت متأكد من حذف هذا التقييم؟',
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
