<?php

namespace backend\modules\followUp\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use backend\modules\contracts\models\Contracts;

/**
 * OCP Kanban Task Model
 *
 * @property int $id
 * @property int $contract_id
 * @property string $title
 * @property string $description
 * @property string $stage
 * @property string $priority
 * @property string $due_date
 * @property int $assigned_to
 * @property string $action_type
 * @property string $status
 * @property int $sort_order
 * @property string $escalation_reason
 * @property string $escalation_type
 * @property int $requires_approval
 * @property int $approved_by
 * @property string $approved_at
 * @property string $completed_at
 * @property int $created_by
 * @property string $created_at
 * @property string $updated_at
 */
class FollowUpTask extends ActiveRecord
{
    // Stages
    const STAGE_NEW = 'new';
    const STAGE_FIRST_CALL = 'first_call';
    const STAGE_PROMISE = 'promise';
    const STAGE_POST_PROMISE = 'post_promise';
    const STAGE_LATE = 'late';
    const STAGE_ESCALATION = 'escalation';
    const STAGE_LEGAL = 'legal';
    const STAGE_CLOSED = 'closed';

    // Priorities
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_DONE = 'done';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';

    public static function tableName()
    {
        return '{{%follow_up_tasks}}';
    }

    public function rules()
    {
        return [
            [['contract_id', 'title', 'created_by'], 'required'],
            [['contract_id', 'assigned_to', 'sort_order', 'requires_approval', 'approved_by', 'created_by'], 'integer'],
            [['description', 'escalation_reason'], 'string'],
            [['due_date', 'approved_at', 'completed_at', 'created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['stage'], 'in', 'range' => ['new', 'first_call', 'promise', 'post_promise', 'late', 'escalation', 'legal', 'closed']],
            [['priority'], 'in', 'range' => ['low', 'medium', 'high', 'critical']],
            [['status'], 'in', 'range' => ['pending', 'in_progress', 'done', 'overdue', 'cancelled']],
            [['action_type'], 'string', 'max' => 50],
            [['escalation_type'], 'string', 'max' => 50],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'contract_id' => 'العقد',
            'title' => 'العنوان',
            'description' => 'الوصف',
            'stage' => 'المرحلة',
            'priority' => 'الأولوية',
            'due_date' => 'تاريخ الاستحقاق',
            'assigned_to' => 'المسؤول',
            'action_type' => 'نوع الإجراء',
            'status' => 'الحالة',
            'escalation_reason' => 'سبب التصعيد',
            'escalation_type' => 'نوع التصعيد',
        ];
    }

    public function getContract()
    {
        return $this->hasOne(Contracts::class, ['id' => 'contract_id']);
    }

    public function getAssignedUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'assigned_to']);
    }

    public function getCreator()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'created_by']);
    }

    /**
     * Check if task is overdue
     */
    public function getIsOverdue()
    {
        if ($this->status === self::STATUS_DONE || $this->status === self::STATUS_CANCELLED) {
            return false;
        }
        if (empty($this->due_date)) {
            return false;
        }
        return strtotime($this->due_date) < strtotime('today');
    }

    /**
     * Get tasks grouped by stage for Kanban view
     */
    public static function getKanbanData($contract_id)
    {
        $stages = [
            self::STAGE_NEW => ['title' => 'جديد/مفتوح', 'tasks' => [], 'total' => 0, 'overdue' => 0],
            self::STAGE_FIRST_CALL => ['title' => 'اتصال أول', 'tasks' => [], 'total' => 0, 'overdue' => 0],
            self::STAGE_PROMISE => ['title' => 'وعد دفع', 'tasks' => [], 'total' => 0, 'overdue' => 0],
            self::STAGE_POST_PROMISE => ['title' => 'متابعة بعد وعد', 'tasks' => [], 'total' => 0, 'overdue' => 0],
            self::STAGE_LATE => ['title' => 'متأخر', 'tasks' => [], 'total' => 0, 'overdue' => 0],
            self::STAGE_ESCALATION => ['title' => 'تصعيد', 'tasks' => [], 'total' => 0, 'overdue' => 0],
            self::STAGE_LEGAL => ['title' => 'قضائي', 'tasks' => [], 'total' => 0, 'overdue' => 0],
            self::STAGE_CLOSED => ['title' => 'مغلق/منتهي', 'tasks' => [], 'total' => 0, 'overdue' => 0],
        ];

        $tasks = self::find()
            ->where(['contract_id' => $contract_id])
            ->andWhere(['!=', 'status', self::STATUS_CANCELLED])
            ->orderBy(['sort_order' => SORT_ASC, 'created_at' => SORT_DESC])
            ->all();

        foreach ($tasks as $task) {
            if (isset($stages[$task->stage])) {
                $stages[$task->stage]['tasks'][] = $task;
                $stages[$task->stage]['total']++;
                if ($task->isOverdue) {
                    $stages[$task->stage]['overdue']++;
                }
            }
        }

        return $stages;
    }

    /**
     * Stage labels for display
     */
    public static function stageLabels()
    {
        return [
            self::STAGE_NEW => 'جديد/مفتوح',
            self::STAGE_FIRST_CALL => 'اتصال أول',
            self::STAGE_PROMISE => 'وعد دفع',
            self::STAGE_POST_PROMISE => 'متابعة بعد وعد',
            self::STAGE_LATE => 'متأخر',
            self::STAGE_ESCALATION => 'تصعيد',
            self::STAGE_LEGAL => 'قضائي',
            self::STAGE_CLOSED => 'مغلق/منتهي',
        ];
    }

    /**
     * Convert to array for JSON/Kanban rendering
     */
    public function toKanbanArray()
    {
        $assignee = $this->assignedUser;
        return [
            'id' => $this->id,
            'title' => $this->title,
            'stage' => $this->stage,
            'priority' => $this->priority,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'action_type' => $this->action_type,
            'is_overdue' => $this->isOverdue,
            'assignee_name' => $assignee ? $assignee->username : null,
            'sort_order' => $this->sort_order,
        ];
    }
}
