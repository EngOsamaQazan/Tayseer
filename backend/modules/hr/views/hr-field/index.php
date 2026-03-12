<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  لوحة المهام الميدانية — Field Tasks Board (Kanban)
 *  ──────────────────────────────────────
 *  عرض كانبان للمهام الميدانية مع فلترة وبطاقات حسب الحالة
 * ═══════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $tasks — grouped by status: ['pending' => [...], 'in_progress' => [...], 'completed' => [...]] */

$this->title = 'لوحة المهام الميدانية';

/* ─── Register HR CSS ─── */
$this->registerCssFile(Yii::getAlias('@web') . '/css/hr.css', ['depends' => ['yii\web\YiiAsset']]);

/* ─── Safe defaults ─── */
$tasks = isset($tasks) ? $tasks : [];
$pending    = isset($tasks['pending']) ? $tasks['pending'] : [];
$inProgress = array_merge(
    isset($tasks['in_progress']) ? $tasks['in_progress'] : [],
    isset($tasks['en_route']) ? $tasks['en_route'] : [],
    isset($tasks['arrived']) ? $tasks['arrived'] : []
);
$completed = isset($tasks['completed']) ? $tasks['completed'] : [];

/* ─── Priority map ─── */
$priorityMap = [
    'low'      => ['label' => 'منخفضة', 'class' => 'priority-low'],
    'medium'   => ['label' => 'متوسطة', 'class' => 'priority-medium'],
    'high'     => ['label' => 'عالية',  'class' => 'priority-high'],
    'urgent'   => ['label' => 'عاجلة',  'class' => 'priority-urgent'],
];

/* ─── Task type map ─── */
$taskTypeMap = [
    'visit'       => ['label' => 'زيارة',     'color' => '#3498db'],
    'delivery'    => ['label' => 'توصيل',     'color' => '#27ae60'],
    'inspection'  => ['label' => 'تفتيش',     'color' => '#f39c12'],
    'maintenance' => ['label' => 'صيانة',     'color' => '#e74c3c'],
    'survey'      => ['label' => 'مسح',       'color' => '#9b59b6'],
    'collection'  => ['label' => 'تحصيل',     'color' => '#1abc9c'],
    'other'       => ['label' => 'أخرى',      'color' => '#95a5a6'],
];

/* ─── Helper: render task card ─── */
$renderCard = function ($task) use ($priorityMap, $taskTypeMap) {
    $title    = Html::encode($task['title'] ?? '—');
    $employee = Html::encode($task['employee_name'] ?? '—');
    $dueDate  = Html::encode($task['due_date'] ?? '');
    $priority = $task['priority'] ?? 'medium';
    $type     = $task['task_type'] ?? 'other';
    $id       = $task['id'] ?? 0;

    $pInfo = $priorityMap[$priority] ?? ['label' => $priority, 'class' => 'priority-medium'];
    $tInfo = $taskTypeMap[$type] ?? ['label' => $type, 'color' => '#95a5a6'];

    $html  = '<div class="kanban-card" data-id="' . $id . '">';
    $html .= '  <div class="kanban-card__header">';
    $html .= '    <span class="kanban-card__type" style="background:' . $tInfo['color'] . '">' . $tInfo['label'] . '</span>';
    $html .= '    <span class="kanban-card__priority ' . $pInfo['class'] . '">' . $pInfo['label'] . '</span>';
    $html .= '  </div>';
    $html .= '  <h4 class="kanban-card__title">' . $title . '</h4>';
    $html .= '  <div class="kanban-card__meta">';
    $html .= '    <span><i class="fa fa-user"></i> ' . $employee . '</span>';
    if ($dueDate) {
        $html .= '    <span><i class="fa fa-calendar"></i> ' . $dueDate . '</span>';
    }
    $html .= '  </div>';
    $html .= '</div>';
    return $html;
};
?>

<style>
/* ═══════════════════════════════════════
   Field Tasks Board — Kanban Styles
   ═══════════════════════════════════════ */

/* Filter bar */
.field-filter-bar {
    display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;
    margin-bottom: 24px; padding: 16px 20px;
    background: var(--hr-card-bg, #fff); border-radius: var(--hr-radius-md, 10px);
    box-shadow: var(--hr-shadow-sm);
}
.field-filter-bar .filter-group {
    display: flex; flex-direction: column; gap: 4px;
}
.field-filter-bar .filter-group label {
    font-size: 12px; font-weight: 600; color: var(--hr-text-muted, #6c757d);
}
.field-filter-bar .filter-group select,
.field-filter-bar .filter-group input {
    border-radius: 8px; border: 1px solid var(--hr-border, #e2e8f0);
    font-size: 13px; height: 38px; padding: 4px 12px; min-width: 160px;
}
.field-filter-bar .filter-group select:focus,
.field-filter-bar .filter-group input:focus {
    border-color: var(--hr-primary, #800020);
    box-shadow: 0 0 0 3px rgba(128,0,32,0.08);
    outline: none;
}

/* Kanban layout */
.kanban-board {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    min-height: 400px;
}
@media (max-width: 992px) {
    .kanban-board { grid-template-columns: 1fr; }
}

.kanban-column {
    background: var(--hr-bg, #f4f6f9);
    border-radius: var(--hr-radius-md, 10px);
    padding: 16px;
    min-height: 300px;
}
.kanban-column__header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px; padding-bottom: 12px;
    border-bottom: 2px solid var(--col-color, var(--hr-border, #e0e0e0));
}
.kanban-column__title {
    font-size: 15px; font-weight: 700; color: var(--hr-text, #2c3e50);
    display: flex; align-items: center; gap: 8px;
}
.kanban-column__title i { color: var(--col-color, var(--hr-primary, #800020)); font-size: 16px; }
.kanban-column__count {
    background: var(--col-color, var(--hr-primary));
    color: #fff; font-size: 12px; font-weight: 700;
    width: 26px; height: 26px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
}

/* Task card */
.kanban-card {
    background: var(--hr-card-bg, #fff);
    border-radius: var(--hr-radius-sm, 6px);
    padding: 14px 16px;
    margin-bottom: 12px;
    box-shadow: var(--hr-shadow-sm);
    transition: var(--hr-transition);
    border-right: 3px solid var(--col-color, var(--hr-border));
    cursor: pointer;
}
.kanban-card:hover {
    box-shadow: var(--hr-shadow-hover);
    transform: translateY(-2px);
}
.kanban-card__header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 8px; gap: 6px;
}
.kanban-card__type {
    display: inline-block; padding: 2px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600; color: #fff;
}
.kanban-card__priority {
    font-size: 11px; font-weight: 600; padding: 2px 10px; border-radius: 20px;
}
.priority-low    { background: #eafaf1; color: #27ae60; }
.priority-medium { background: #fef9e7; color: #f39c12; }
.priority-high   { background: #fdedec; color: #e74c3c; }
.priority-urgent { background: #e74c3c; color: #fff; }

.kanban-card__title {
    font-size: 14px; font-weight: 600; color: var(--hr-text, #2c3e50);
    margin: 0 0 8px; line-height: 1.5;
}
.kanban-card__meta {
    display: flex; flex-direction: column; gap: 4px;
    font-size: 12px; color: var(--hr-text-light, #7f8c8d);
}
.kanban-card__meta i {
    width: 16px; text-align: center; margin-left: 4px;
    color: var(--hr-text-muted, #95a5a6);
}

/* Empty column state */
.kanban-empty {
    text-align: center; padding: 30px 10px; color: var(--hr-text-muted, #95a5a6);
}
.kanban-empty i { font-size: 28px; opacity: 0.35; display: block; margin-bottom: 8px; }
.kanban-empty p { font-size: 13px; margin: 0; }
</style>

<div class="hr-page">

    <!-- ╔═══════════════════════════════════════╗
         ║  العنوان وأزرار الإجراءات             ║
         ╚═══════════════════════════════════════╝ -->
    <div class="hr-header">
        <h1><i class="fa fa-map-signs"></i> <?= Html::encode($this->title) ?></h1>
        <div style="display:flex;gap:8px;align-items:center">
            <?= Html::a(
                '<i class="fa fa-map"></i> الخريطة',
                Url::to(['map']),
                ['class' => 'hr-btn hr-btn--info hr-btn--sm']
            ) ?>
            <?= Html::a(
                '<i class="fa fa-plus"></i> مهمة جديدة',
                Url::to(['create']),
                ['class' => 'hr-btn hr-btn--primary']
            ) ?>
        </div>
    </div>

    <!-- ╔═══════════════════════════════════════╗
         ║  شريط الفلترة                          ║
         ╚═══════════════════════════════════════╝ -->
    <?= Html::beginForm(Url::to(['index']), 'get') ?>
    <div class="field-filter-bar">
        <div class="filter-group">
            <label>الحالة</label>
            <?= Html::dropDownList('status', Yii::$app->request->get('status'), [
                'pending'     => 'معلقة',
                'in_progress' => 'قيد التنفيذ',
                'en_route'    => 'في الطريق',
                'arrived'     => 'وصل',
                'completed'   => 'مكتملة',
            ], [
                'class' => 'form-control',
                'prompt' => '— جميع الحالات —',
            ]) ?>
        </div>

        <div class="filter-group">
            <label>الأولوية</label>
            <?= Html::dropDownList('priority', Yii::$app->request->get('priority'), [
                'low'    => 'منخفضة',
                'medium' => 'متوسطة',
                'high'   => 'عالية',
                'urgent' => 'عاجلة',
            ], [
                'class' => 'form-control',
                'prompt' => '— جميع الأولويات —',
            ]) ?>
        </div>

        <div class="filter-group">
            <label>من تاريخ</label>
            <?= Html::input('date', 'date_from', Yii::$app->request->get('date_from'), [
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label>إلى تاريخ</label>
            <?= Html::input('date', 'date_to', Yii::$app->request->get('date_to'), [
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group" style="justify-content:flex-end">
            <?= Html::submitButton('<i class="fa fa-filter"></i> فلترة', [
                'class' => 'hr-btn hr-btn--primary hr-btn--sm',
            ]) ?>
            <?= Html::a('<i class="fa fa-times"></i> مسح', Url::to(['index']), [
                'class' => 'hr-btn hr-btn--outline-primary hr-btn--sm',
            ]) ?>
        </div>
    </div>
    <?= Html::endForm() ?>

    <!-- ╔═══════════════════════════════════════╗
         ║  لوحة كانبان                           ║
         ╚═══════════════════════════════════════╝ -->
    <div class="kanban-board">

        <!-- عمود: معلقة -->
        <div class="kanban-column" style="--col-color: var(--hr-warning, #f39c12)">
            <div class="kanban-column__header">
                <span class="kanban-column__title"><i class="fa fa-clock-o"></i> معلقة</span>
                <span class="kanban-column__count"><?= count($pending) ?></span>
            </div>
            <?php if (!empty($pending)): ?>
                <?php foreach ($pending as $task): ?>
                    <?= $renderCard($task) ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="kanban-empty">
                    <i class="fa fa-inbox"></i>
                    <p>لا توجد مهام معلقة</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- عمود: قيد التنفيذ -->
        <div class="kanban-column" style="--col-color: var(--hr-info, #3498db)">
            <div class="kanban-column__header">
                <span class="kanban-column__title"><i class="fa fa-spinner"></i> قيد التنفيذ</span>
                <span class="kanban-column__count"><?= count($inProgress) ?></span>
            </div>
            <?php if (!empty($inProgress)): ?>
                <?php foreach ($inProgress as $task): ?>
                    <?= $renderCard($task) ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="kanban-empty">
                    <i class="fa fa-tasks"></i>
                    <p>لا توجد مهام قيد التنفيذ</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- عمود: مكتملة -->
        <div class="kanban-column" style="--col-color: var(--hr-success, #27ae60)">
            <div class="kanban-column__header">
                <span class="kanban-column__title"><i class="fa fa-check-circle"></i> مكتملة</span>
                <span class="kanban-column__count"><?= count($completed) ?></span>
            </div>
            <?php if (!empty($completed)): ?>
                <?php foreach ($completed as $task): ?>
                    <?= $renderCard($task) ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="kanban-empty">
                    <i class="fa fa-check"></i>
                    <p>لا توجد مهام مكتملة</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div><!-- /.hr-page -->
