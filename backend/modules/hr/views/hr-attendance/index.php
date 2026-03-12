<?php
/**
 * لوحة الحضور والانصراف — Attendance Board
 */

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;
use backend\helpers\FlatpickrWidget;
use yii\bootstrap\Modal;
use backend\helpers\NameHelper;

$this->title = 'لوحة الحضور والانصراف';

$presentCount = (int) ($todayStats['present'] ?? 0);
$absentCount  = (int) ($todayStats['absent'] ?? 0);
$leaveCount   = (int) ($todayStats['leave'] ?? 0);
$holidayCount = (int) ($todayStats['holiday'] ?? 0);
$halfDayCount = (int) ($todayStats['half_day'] ?? 0);
$remoteCount  = (int) ($todayStats['remote'] ?? 0);
$totalCount   = (int) ($todayStats['total_records'] ?? 0);

$statusMap = [
    'present'    => ['label' => 'حاضر',     'color' => '#28a745'],
    'absent'     => ['label' => 'غائب',     'color' => '#dc3545'],
    'leave'      => ['label' => 'إجازة',    'color' => '#17a2b8'],
    'holiday'    => ['label' => 'عطلة',     'color' => '#adb5bd'],
    'half_day'   => ['label' => 'نصف يوم',  'color' => '#6f42c1'],
    'remote'     => ['label' => 'عن بُعد',  'color' => '#6c757d'],
];
?>

<style>
/* ═══════════════════════════════════════
   HR Attendance Board Styles
   ═══════════════════════════════════════ */
.hr-page { padding: 20px; }
.hr-page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.hr-page-header h1 {
    font-size: 22px; font-weight: 700; color: var(--clr-primary, #800020); margin: 0;
}
.hr-page-header .hr-actions { display: flex; gap: 8px; flex-wrap: wrap; }

/* Filter bar */
.hr-filter-bar {
    display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;
    margin-bottom: 20px; padding: 16px 20px;
    background: var(--clr-surface, #fff); border-radius: var(--radius-md, 10px);
    box-shadow: var(--shadow-sm);
}
.hr-filter-bar .filter-group { display: flex; flex-direction: column; gap: 4px; }
.hr-filter-bar .filter-group label {
    font-size: 12px; font-weight: 600; color: var(--clr-text-muted, #6c757d);
}

/* Summary cards */
.hr-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 14px; margin-bottom: 24px;
}
.hr-summary-card {
    background: var(--clr-surface, #fff);
    border-radius: var(--radius-md, 10px);
    padding: 18px; text-align: center;
    box-shadow: var(--shadow-sm);
    border-top: 4px solid var(--card-color, #800020);
    transition: var(--transition);
}
.hr-summary-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.hr-summary-card .card-icon {
    width: 42px; height: 42px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 18px; color: #fff; margin-bottom: 8px;
    background: var(--card-color, #800020);
}
.hr-summary-card .card-value {
    font-size: 28px; font-weight: 800; color: var(--clr-text, #212529);
    line-height: 1.1;
}
.hr-summary-card .card-label {
    font-size: 13px; color: var(--clr-text-muted, #6c757d); margin-top: 4px;
}

/* Status badge */
.att-status-badge {
    display: inline-block; padding: 3px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600; color: #fff;
}

/* Grid card */
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
</style>

<div class="hr-page">
    <!-- Header -->
    <div class="hr-page-header">
        <h1><i class="fa fa-clock-o"></i> <?= Html::encode($this->title) ?></h1>
        <div class="hr-actions">
            <?= Html::button('<i class="fa fa-plus"></i> تسجيل حضور يدوي', [
                'class' => 'btn btn-primary btn-sm',
                'data-toggle' => 'modal',
                'data-target' => '#manual-attendance-modal',
            ]) ?>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="hr-filter-bar">
        <?php $form = \yii\widgets\ActiveForm::begin([
            'method' => 'get',
            'action' => Url::to(['index']),
            'options' => ['class' => 'hr-filter-bar', 'style' => 'margin:0;padding:0;box-shadow:none;background:none;width:100%'],
        ]); ?>

        <div class="filter-group">
            <label>التاريخ</label>
            <?= FlatpickrWidget::widget([
                'name' => 'date',
                'value' => $filterDate,
                'pluginOptions' => [
                    'dateFormat' => 'Y-m-d',
                ],
                'options' => ['class' => 'form-control', 'style' => 'width:180px'],
            ]) ?>
        </div>

        <div class="filter-group">
            <label>القسم</label>
            <?= Html::dropDownList('department', $filterDepartment, $departments, [
                'class' => 'form-control',
                'prompt' => '— جميع الأقسام —',
                'style' => 'width:180px',
            ]) ?>
        </div>

        <div class="filter-group">
            <label>الحالة</label>
            <?= Html::dropDownList('status', $filterStatus, [
                'present'    => 'حاضر',
                'absent'     => 'غائب',
                'leave'      => 'إجازة',
                'holiday'    => 'عطلة',
                'half_day'   => 'نصف يوم',
                'remote'     => 'عن بُعد',
            ], [
                'class' => 'form-control',
                'prompt' => '— جميع الحالات —',
                'style' => 'width:160px',
            ]) ?>
        </div>

        <div class="filter-group">
            <?= Html::submitButton('<i class="fa fa-search"></i> بحث', [
                'class' => 'btn btn-primary btn-sm',
            ]) ?>
        </div>

        <?php \yii\widgets\ActiveForm::end(); ?>
    </div>

    <!-- Summary Cards -->
    <div class="hr-summary-grid">
        <div class="hr-summary-card" style="--card-color:#28a745">
            <div class="card-icon"><i class="fa fa-check"></i></div>
            <div class="card-value"><?= number_format($presentCount) ?></div>
            <div class="card-label">حاضر</div>
        </div>
        <div class="hr-summary-card" style="--card-color:#dc3545">
            <div class="card-icon"><i class="fa fa-times"></i></div>
            <div class="card-value"><?= number_format($absentCount) ?></div>
            <div class="card-label">غائب</div>
        </div>
        <div class="hr-summary-card" style="--card-color:#6f42c1">
            <div class="card-icon"><i class="fa fa-clock-o"></i></div>
            <div class="card-value"><?= number_format($halfDayCount) ?></div>
            <div class="card-label">نصف يوم</div>
        </div>
        <div class="hr-summary-card" style="--card-color:#17a2b8">
            <div class="card-icon"><i class="fa fa-plane"></i></div>
            <div class="card-value"><?= number_format($leaveCount) ?></div>
            <div class="card-label">إجازة</div>
        </div>
        <div class="hr-summary-card" style="--card-color:#6c757d">
            <div class="card-icon"><i class="fa fa-laptop"></i></div>
            <div class="card-value"><?= number_format($remoteCount) ?></div>
            <div class="card-label">عن بُعد</div>
        </div>
        <div class="hr-summary-card" style="--card-color:#adb5bd">
            <div class="card-icon"><i class="fa fa-calendar"></i></div>
            <div class="card-value"><?= number_format($holidayCount) ?></div>
            <div class="card-label">عطلة</div>
        </div>
        <div class="hr-summary-card" style="--card-color:#800020">
            <div class="card-icon"><i class="fa fa-users"></i></div>
            <div class="card-value"><?= number_format($totalCount) ?></div>
            <div class="card-label">إجمالي</div>
        </div>
    </div>

    <!-- Attendance GridView -->
    <div class="hr-grid-card">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'responsive' => true,
            'hover' => true,
            'striped' => false,
            'condensed' => true,
            'summary' => '<div class="text-muted" style="padding:10px 16px;font-size:12px">عرض {begin}-{end} من {totalCount} سجل</div>',
            'tableOptions' => ['class' => 'kv-grid-table table table-bordered'],
            'columns' => [
                [
                    'attribute' => 'user_id',
                    'header' => 'الموظف',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $user = $model->user;
                        if ($user) {
                            $name = $user->name ? NameHelper::short($user->name) : $user->username;
                            return '<strong>' . Html::encode($name) . '</strong>';
                        }
                        return '<span class="text-muted">—</span>';
                    },
                ],
                [
                    'attribute' => 'check_in_time',
                    'header' => 'الدخول',
                    'format' => 'raw',
                    'value' => function ($model) {
                        if ($model->check_in_time) {
                            return '<i class="fa fa-sign-in text-success"></i> ' . Yii::$app->formatter->asTime($model->check_in_time, 'HH:mm');
                        }
                        return '<span class="text-muted">—</span>';
                    },
                ],
                [
                    'attribute' => 'check_out_time',
                    'header' => 'الخروج',
                    'format' => 'raw',
                    'value' => function ($model) {
                        if ($model->check_out_time) {
                            return '<i class="fa fa-sign-out text-danger"></i> ' . Yii::$app->formatter->asTime($model->check_out_time, 'HH:mm');
                        }
                        return '<span class="text-muted">—</span>';
                    },
                ],
                [
                    'attribute' => 'total_hours',
                    'header' => 'الساعات',
                    'format' => 'raw',
                    'value' => function ($model) {
                        if ($model->total_hours !== null) {
                            return '<strong>' . number_format((float) $model->total_hours, 1) . '</strong> ساعة';
                        }
                        return '<span class="text-muted">—</span>';
                    },
                ],
                [
                    'attribute' => 'late_minutes',
                    'header' => 'التأخير (دقيقة)',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $val = (int) ($model->late_minutes ?? 0);
                        if ($val > 0) {
                            return '<span style="color:#dc3545;font-weight:600">' . $val . '</span>';
                        }
                        return '<span class="text-muted">0</span>';
                    },
                ],
                [
                    'attribute' => 'status',
                    'header' => 'الحالة',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $statusMap = [
                            'present'    => ['label' => 'حاضر',     'color' => '#28a745'],
                            'absent'     => ['label' => 'غائب',     'color' => '#dc3545'],
                            'leave'      => ['label' => 'إجازة',    'color' => '#17a2b8'],
                            'holiday'    => ['label' => 'عطلة',     'color' => '#adb5bd'],
                            'half_day'   => ['label' => 'نصف يوم',  'color' => '#6f42c1'],
                            'remote'     => ['label' => 'عن بُعد',  'color' => '#6c757d'],
                        ];
                        $st = $model->status ?? 'absent';
                        $info = $statusMap[$st] ?? ['label' => $st, 'color' => '#999'];
                        return '<span class="att-status-badge" style="background:' . $info['color'] . '">' . $info['label'] . '</span>';
                    },
                ],
                [
                    'attribute' => 'notes',
                    'header' => 'ملاحظات',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $notes = $model->notes;
                        if (!empty($notes)) {
                            return '<span title="' . Html::encode($notes) . '">' . Html::encode(mb_substr($notes, 0, 40)) . (mb_strlen($notes) > 40 ? '…' : '') . '</span>';
                        }
                        return '<span class="text-muted">—</span>';
                    },
                ],
                [
                    'class' => 'kartik\grid\ActionColumn',
                    'header' => '',
                    'template' => '{update}',
                    'buttons' => [
                        'update' => function ($url, $model) {
                            return Html::a('<i class="fa fa-pencil"></i>', ['update', 'id' => $model->id], [
                                'class' => 'btn btn-xs btn-default',
                                'title' => 'تعديل',
                            ]);
                        },
                    ],
                ],
            ],
        ]); ?>
    </div>
</div>

<!-- Manual Attendance Modal -->
<?php Modal::begin([
    'id' => 'manual-attendance-modal',
    'header' => '<h4 class="modal-title"><i class="fa fa-plus-circle"></i> تسجيل حضور يدوي</h4>',
    'size' => Modal::SIZE_DEFAULT,
]); ?>
<div id="manual-attendance-modal-content">
    <div class="text-center" style="padding:30px">
        <i class="fa fa-spinner fa-spin fa-2x"></i>
        <p style="margin-top:10px">جارٍ التحميل...</p>
    </div>
</div>
<?php Modal::end(); ?>

<?php
$createUrl = Url::to(['create']);
$js = <<<JS
$('#manual-attendance-modal').on('show.bs.modal', function () {
    $.ajax({
        url: '{$createUrl}',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.content) {
                $('#manual-attendance-modal-content').html(data.content);
            }
        },
        error: function() {
            $('#manual-attendance-modal-content').html('<div class="alert alert-danger">حدث خطأ أثناء تحميل النموذج.</div>');
        }
    });
});
JS;
$this->registerJs($js);
?>
