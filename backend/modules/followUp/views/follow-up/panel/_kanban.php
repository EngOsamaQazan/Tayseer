<?php

use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var array $kanbanData
 * @var backend\modules\contracts\models\Contracts $contract
 */

$columnIcons = [
    'new' => 'fa-plus-circle',
    'first_call' => 'fa-phone',
    'promise' => 'fa-handshake-o',
    'post_promise' => 'fa-clock-o',
    'late' => 'fa-exclamation-circle',
    'escalation' => 'fa-arrow-up',
    'legal' => 'fa-gavel',
    'closed' => 'fa-check-circle',
];

// Column CSS class mapping (for border colors)
$columnClasses = [
    'new' => 'new',
    'first_call' => 'first-call',
    'promise' => 'promise',
    'post_promise' => 'post-promise',
    'late' => 'late',
    'escalation' => 'escalation',
    'legal' => 'legal',
    'closed' => 'closed',
];
?>

<div class="ocp-kanban">
    <div class="ocp-flex-between" style="margin-bottom:var(--ocp-space-lg)">
        <div class="ocp-section-title" style="margin-bottom:0">
            <i class="fa fa-columns"></i>
            سير العمل (Kanban)
        </div>
        <button class="ocp-btn ocp-btn--outline ocp-btn--sm" onclick="OCP.openPanel('create-task')">
            <i class="fa fa-plus"></i> مهمة جديدة
        </button>
    </div>

    <div class="ocp-kanban__board" id="ocp-kanban-board">
        <?php foreach ($kanbanData as $stageKey => $column): 
            $colClass = $columnClasses[$stageKey] ?? 'new';
            $icon = $columnIcons[$stageKey] ?? 'fa-circle';
        ?>
        <div class="ocp-kanban__column ocp-kanban__column--<?= $colClass ?>" data-stage="<?= $stageKey ?>">
            <div class="ocp-kanban__column-header">
                <div>
                    <span class="ocp-kanban__column-title">
                        <i class="fa <?= $icon ?>" style="font-size:12px;opacity:0.7"></i>
                        <?= Html::encode($column['title']) ?>
                    </span>
                </div>
                <div class="ocp-kanban__column-stats">
                    <span class="ocp-kanban__stat ocp-kanban__stat--total"><?= $column['total'] ?></span>
                    <?php if ($column['overdue'] > 0): ?>
                    <span class="ocp-kanban__stat ocp-kanban__stat--overdue"><?= $column['overdue'] ?> متأخر</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ocp-kanban__column-body"
                 data-stage="<?= $stageKey ?>"
                 data-sortable="true"
                 data-sortable-group="kanban"
                 ondragover="OCP.kanbanDragOver(event)"
                 ondragleave="OCP.kanbanDragLeave(event)"
                 ondrop="OCP.kanbanDrop(event)">
                
                <?php if (empty($column['tasks'])): ?>
                    <div style="text-align:center;padding:var(--ocp-space-lg);color:var(--ocp-text-muted);font-size:var(--ocp-font-size-xs)">
                        لا توجد مهام
                    </div>
                <?php else: ?>
                    <?php foreach ($column['tasks'] as $task): 
                        $isOverdue = $task->isOverdue;
                        $assignee = $task->assignedUser;
                    ?>
                    <div class="ocp-kanban-card <?= $isOverdue ? 'ocp-kanban-card--overdue' : '' ?>"
                         draggable="true"
                         data-task-id="<?= $task->id ?>"
                         data-stage="<?= $task->stage ?>"
                         ondragstart="OCP.kanbanDragStart(event)">
                        
                        <div class="ocp-kanban-card__title"><?= Html::encode($task->title) ?></div>
                        
                        <div class="ocp-kanban-card__meta">
                            <span class="ocp-kanban-card__due <?= $isOverdue ? 'ocp-kanban-card__due--overdue' : '' ?>">
                                <?php if ($task->due_date): ?>
                                    <i class="fa fa-calendar-o" style="font-size:10px"></i>
                                    <?= date('m/d', strtotime($task->due_date)) ?>
                                <?php endif; ?>
                            </span>
                            <?php if ($assignee): ?>
                            <span class="ocp-kanban-card__assignee-avatar" title="<?= Html::encode($assignee->username) ?>">
                                <?= mb_substr($assignee->username, 0, 1) ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <div class="ocp-kanban-card__tags">
                            <?php if ($task->action_type): ?>
                            <span class="ocp-kanban-card__tag"><?= Html::encode($task->action_type) ?></span>
                            <?php endif; ?>
                            <?php if ($task->priority === 'high' || $task->priority === 'critical'): ?>
                            <span class="ocp-kanban-card__tag ocp-kanban-card__priority--<?= $task->priority === 'critical' ? 'high' : 'med' ?>">
                                <?= $task->priority === 'critical' ? 'حرج' : 'مرتفع' ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($task->status === 'done'): ?>
                            <span class="ocp-kanban-card__tag" style="background:var(--ocp-success-bg);color:var(--ocp-success)">منجز</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <button class="ocp-kanban__add-task" onclick="OCP.quickCreateTask('<?= $stageKey ?>')">
                    <i class="fa fa-plus"></i> إضافة مهمة
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Sortable === 'undefined') return;
    document.querySelectorAll('.ocp-kanban__column-body[data-sortable-group="kanban"]').forEach(function(col) {
        Sortable.create(col, {
            group: 'kanban-board',
            animation: 200,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            draggable: '.ocp-kanban-card',
            filter: '.ocp-kanban__add-task',
            onEnd: function(evt) {
                var taskId = evt.item.dataset.taskId;
                var newStage = evt.to.dataset.stage;
                if (typeof OCP !== 'undefined' && OCP.moveTask) {
                    OCP.moveTask(taskId, newStage);
                }
            }
        });
    });
});
</script>
