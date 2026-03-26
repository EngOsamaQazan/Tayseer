<?php

namespace backend\services;

use Yii;
use backend\models\JudiciaryDeadline;
use backend\modules\diwan\models\DiwanCorrespondence;

class JudiciaryDeadlineService
{
    /** @var HolidayService */
    private $holidayService;

    public function __construct()
    {
        $this->holidayService = new HolidayService();
    }

    /**
     * Add N working days to a start date (skipping Fri/Sat + holidays).
     */
    public function addWorkingDays(string $startDate, int $days): string
    {
        $this->holidayService->ensureYearLoaded((int)date('Y', strtotime($startDate)));

        $current = strtotime($startDate);
        $added = 0;

        while ($added < $days) {
            $current = strtotime('+1 day', $current);
            $yearCheck = (int)date('Y', $current);
            $this->holidayService->ensureYearLoaded($yearCheck);

            if (!$this->holidayService->isHoliday($current)) {
                $added++;
            }
        }

        return date('Y-m-d', $current);
    }

    /**
     * Add N calendar days, but if the final day lands on a holiday/weekend,
     * extend to the next working day.
     */
    public function addCalendarDaysWithHolidayCheck(string $startDate, int $days): string
    {
        $target = strtotime("+{$days} days", strtotime($startDate));
        $this->holidayService->ensureYearLoaded((int)date('Y', $target));

        return $this->holidayService->getNextWorkingDay($target);
    }

    /**
     * Add N calendar months.
     */
    public function addMonths(string $startDate, int $months): string
    {
        return date('Y-m-d', strtotime("+{$months} months", strtotime($startDate)));
    }

    /* ─── Specific deadline creators ─── */

    /** 3 working days after registration — to check notification status */
    public function createRegistrationCheckDeadline(int $judiciaryId): ?JudiciaryDeadline
    {
        $startDate = date('Y-m-d');
        return $this->createDeadline(
            $judiciaryId, null,
            JudiciaryDeadline::TYPE_REGISTRATION_3WD,
            JudiciaryDeadline::DAY_WORKING,
            'فحص حالة التبليغ بعد التسجيل',
            $startDate,
            $this->addWorkingDays($startDate, 3)
        );
    }

    /** 3 working days — to check if notification was delivered */
    public function createNotificationCheckDeadline(int $communicationId): ?JudiciaryDeadline
    {
        $comm = DiwanCorrespondence::findOne($communicationId);
        if (!$comm) return null;

        $startDate = $comm->correspondence_date;
        return $this->createDeadline(
            $comm->related_record_id, $comm->customer_id,
            JudiciaryDeadline::TYPE_NOTIFICATION_CHECK,
            JudiciaryDeadline::DAY_WORKING,
            'فحص نتيجة التبليغ',
            $startDate,
            $this->addWorkingDays($startDate, 3),
            $communicationId
        );
    }

    /** 16 calendar days — notification waiting period after delivery */
    public function createNotificationPeriodDeadline(int $communicationId): ?JudiciaryDeadline
    {
        $comm = DiwanCorrespondence::findOne($communicationId);
        if (!$comm || !$comm->delivery_date) return null;

        $startDate = $comm->delivery_date;
        return $this->createDeadline(
            $comm->related_record_id, $comm->customer_id,
            JudiciaryDeadline::TYPE_NOTIFICATION_16CD,
            JudiciaryDeadline::DAY_CALENDAR,
            'انتهاء مدة التبليغ (16 يوم)',
            $startDate,
            $this->addCalendarDaysWithHolidayCheck($startDate, 16),
            $communicationId
        );
    }

    /** 3 working days — judge decision on a submitted request */
    public function createRequestDecisionDeadline(int $actionId): ?JudiciaryDeadline
    {
        $action = \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions::findOne($actionId);
        if (!$action) return null;

        $startDate = $action->action_date ?: date('Y-m-d');
        return $this->createDeadline(
            $action->judiciary_id, $action->customers_id,
            JudiciaryDeadline::TYPE_REQUEST_DECISION,
            JudiciaryDeadline::DAY_WORKING,
            'قرار القاضي على الطلب',
            $startDate,
            $this->addWorkingDays($startDate, 3),
            null, $actionId
        );
    }

    /** 10 working days — authority response to outgoing letter */
    public function createCorrespondenceResponseDeadline(int $communicationId): ?JudiciaryDeadline
    {
        $comm = DiwanCorrespondence::findOne($communicationId);
        if (!$comm) return null;

        $startDate = $comm->correspondence_date;
        return $this->createDeadline(
            $comm->related_record_id, $comm->customer_id,
            JudiciaryDeadline::TYPE_CORRESPONDENCE_10WD,
            JudiciaryDeadline::DAY_WORKING,
            'رد الجهة على الكتاب',
            $startDate,
            $this->addWorkingDays($startDate, 10),
            $communicationId
        );
    }

    /** 7 calendar days — property notification period */
    public function createPropertyNotificationDeadline(int $communicationId): ?JudiciaryDeadline
    {
        $comm = DiwanCorrespondence::findOne($communicationId);
        if (!$comm) return null;

        $startDate = $comm->correspondence_date;
        return $this->createDeadline(
            $comm->related_record_id, $comm->customer_id,
            JudiciaryDeadline::TYPE_PROPERTY_7CD,
            JudiciaryDeadline::DAY_CALENDAR,
            'إخطار عقار (7 أيام)',
            $startDate,
            $this->addCalendarDaysWithHolidayCheck($startDate, 7),
            $communicationId
        );
    }

    /** 3 months — salary re-issue after insufficient response */
    public function createSalaryRetryDeadline(int $communicationId): ?JudiciaryDeadline
    {
        $comm = DiwanCorrespondence::findOne($communicationId);
        if (!$comm) return null;

        $startDate = $comm->correspondence_date;
        return $this->createDeadline(
            $comm->related_record_id, $comm->customer_id,
            JudiciaryDeadline::TYPE_SALARY_3M,
            JudiciaryDeadline::DAY_CALENDAR,
            'إعادة كتاب حسم راتب (3 أشهر)',
            $startDate,
            $this->addMonths($startDate, 3),
            $communicationId
        );
    }

    /* ─── Core creation method ─── */

    private function createDeadline(
        ?int $judiciaryId, ?int $customerId,
        string $type, string $dayType, string $label,
        string $startDate, string $deadlineDate,
        ?int $communicationId = null, ?int $actionId = null
    ): ?JudiciaryDeadline {
        if (!$judiciaryId) return null;

        $dup = JudiciaryDeadline::find()
            ->where(['judiciary_id' => $judiciaryId, 'deadline_type' => $type, 'is_deleted' => 0])
            ->andFilterWhere(['related_customer_action_id' => $actionId])
            ->andFilterWhere(['related_communication_id' => $communicationId])
            ->exists();
        if ($dup) return null;

        $deadline = new JudiciaryDeadline([
            'judiciary_id' => $judiciaryId,
            'customer_id' => $customerId,
            'deadline_type' => $type,
            'day_type' => $dayType,
            'label' => $label,
            'start_date' => $startDate,
            'deadline_date' => $deadlineDate,
            'status' => JudiciaryDeadline::STATUS_PENDING,
            'related_communication_id' => $communicationId,
            'related_customer_action_id' => $actionId,
        ]);

        return $deadline->save() ? $deadline : null;
    }

    /* ─── Generate missing deadlines for existing data ─── */

    /**
     * Scan all existing judiciary cases and actions, create deadline records
     * that should exist but were never generated (retroactive bulk creation).
     * Safe to call multiple times — skips duplicates.
     * Processes in batches of $batchSize per call to avoid request timeout.
     */
    public static function generateMissingDeadlines(int $batchSize = 500): int
    {
        $db     = \Yii::$app->db;
        $prefix = $db->tablePrefix;
        $svc    = new self();
        $count  = 0;
        $now    = time();

        // 1) Request-decision deadlines for request actions without one
        $reqActions = $db->createCommand(
            "SELECT jca.id, jca.judiciary_id, jca.customers_id, jca.action_date, jca.created_at
             FROM {$prefix}judiciary_customers_actions jca
             INNER JOIN {$prefix}judiciary_actions ja ON ja.id = jca.judiciary_actions_id
             WHERE ja.action_nature = 'request'
               AND (jca.is_deleted = 0 OR jca.is_deleted IS NULL)
               AND NOT EXISTS (
                   SELECT 1 FROM {$prefix}judiciary_deadlines dl
                   WHERE dl.related_customer_action_id = jca.id
                     AND dl.deadline_type IN ('" . JudiciaryDeadline::TYPE_REQUEST_DECISION . "', 'request_decision')
                     AND dl.is_deleted = 0
               )
             LIMIT {$batchSize}"
        )->queryAll();

        if (!empty($reqActions)) {
            $rows = [];
            foreach ($reqActions as $a) {
                $startDate = $a['action_date'] ?: date('Y-m-d', is_numeric($a['created_at']) ? (int)$a['created_at'] : strtotime($a['created_at']));
                $deadlineDate = $svc->addWorkingDays($startDate, 3);
                $rows[] = [
                    (int)$a['judiciary_id'],
                    $a['customers_id'] ? (int)$a['customers_id'] : null,
                    JudiciaryDeadline::TYPE_REQUEST_DECISION,
                    JudiciaryDeadline::DAY_WORKING,
                    'قرار القاضي على الطلب',
                    $startDate,
                    $deadlineDate,
                    JudiciaryDeadline::STATUS_PENDING,
                    null,
                    (int)$a['id'],
                    null,
                    0,
                    $now,
                    $now,
                ];
            }
            $db->createCommand()->batchInsert(
                JudiciaryDeadline::tableName(),
                ['judiciary_id', 'customer_id', 'deadline_type', 'day_type', 'label',
                 'start_date', 'deadline_date', 'status', 'related_communication_id',
                 'related_customer_action_id', 'notes', 'is_deleted', 'created_at', 'updated_at'],
                $rows
            )->execute();
            $count += count($rows);
        }

        // 2) Registration-check deadline for cases without one
        $casesWithout = $db->createCommand(
            "SELECT j.id, j.created_at
             FROM {$prefix}judiciary j
             WHERE (j.is_deleted = 0 OR j.is_deleted IS NULL)
               AND NOT EXISTS (
                   SELECT 1 FROM {$prefix}judiciary_deadlines dl
                   WHERE dl.judiciary_id = j.id
                     AND dl.deadline_type IN ('" . JudiciaryDeadline::TYPE_REGISTRATION_3WD . "', 'registration')
                     AND dl.is_deleted = 0
               )
             LIMIT {$batchSize}"
        )->queryAll();

        if (!empty($casesWithout)) {
            $rows = [];
            foreach ($casesWithout as $c) {
                $startDate = date('Y-m-d', is_numeric($c['created_at']) ? (int)$c['created_at'] : strtotime($c['created_at']));
                $deadlineDate = $svc->addWorkingDays($startDate, 3);
                $rows[] = [
                    (int)$c['id'],
                    null,
                    JudiciaryDeadline::TYPE_REGISTRATION_3WD,
                    JudiciaryDeadline::DAY_WORKING,
                    'فحص حالة التبليغ بعد التسجيل',
                    $startDate,
                    $deadlineDate,
                    JudiciaryDeadline::STATUS_PENDING,
                    null,
                    null,
                    null,
                    0,
                    $now,
                    $now,
                ];
            }
            $db->createCommand()->batchInsert(
                JudiciaryDeadline::tableName(),
                ['judiciary_id', 'customer_id', 'deadline_type', 'day_type', 'label',
                 'start_date', 'deadline_date', 'status', 'related_communication_id',
                 'related_customer_action_id', 'notes', 'is_deleted', 'created_at', 'updated_at'],
                $rows
            )->execute();
            $count += count($rows);
        }

        return $count;
    }

    /* ─── Auto-complete milestone deadlines when new action is added ─── */

    /**
     * Milestone deadline types that get auto-completed when any new action
     * is added to the case (تجهيز، رسوم، تسجيل).
     * Does NOT include task-specific deadlines like correspondence replies,
     * judge decisions, notification periods, etc.
     */
    private static $milestoneTypes = [
        JudiciaryDeadline::TYPE_REGISTRATION_3WD,
        'registration',
    ];

    /**
     * Mark milestone-stage deadlines as completed when new action is added.
     * Only affects registration/preparation type deadlines, NOT:
     * - correspondence_10wd (رد جهة على كتاب)
     * - request_decision_3wd (قرار القاضي على طلب)
     * - notification_check_3wd / notification_16cd (تبليغ)
     * - property_7cd / salary_3m (إخطارات)
     */
    public static function completeMilestoneDeadlines(int $judiciaryId, ?string $notes = null): int
    {
        $attrs = ['status' => JudiciaryDeadline::STATUS_COMPLETED];
        if ($notes) {
            $attrs['notes'] = $notes;
        }

        return JudiciaryDeadline::updateAll(
            $attrs,
            ['AND',
                ['judiciary_id' => $judiciaryId],
                ['in', 'deadline_type', self::$milestoneTypes],
                ['in', 'status', [
                    JudiciaryDeadline::STATUS_PENDING,
                    JudiciaryDeadline::STATUS_APPROACHING,
                    JudiciaryDeadline::STATUS_EXPIRED,
                ]],
                ['is_deleted' => 0],
            ]
        );
    }

    /* ─── Deadline status refresh ─── */

    /**
     * Refresh statuses for all active deadlines (call via cron or admin action).
     * 0) Auto-complete ALL deadlines for closed/archived cases
     * 1) Auto-complete deadlines whose related action was deleted
     * 2) Auto-complete milestone deadlines when a subsequent action exists on the case
     * 3) Mark overdue deadlines as expired
     * 4) Mark deadlines within 3 days as approaching
     */
    public static function refreshAllStatuses(): int
    {
        $db = \Yii::$app->db;
        $prefix = $db->tablePrefix;
        $updated = 0;

        // --- Revert: undo incorrect 180-day stale auto-completion ---
        JudiciaryDeadline::updateAll(
            ['status' => JudiciaryDeadline::STATUS_EXPIRED, 'notes' => null],
            ['AND',
                ['status' => JudiciaryDeadline::STATUS_COMPLETED],
                ['or', ['notes' => 'تم إنجازه — منتهي أكثر من 6 أشهر بدون نشاط'], ['notes' => 'ترحيل تلقائي']],
                ['is_deleted' => 0],
            ]
        );

        // --- Check 0a: complete ALL deadlines for closed/archived cases ---
        $closedCaseIds = $db->createCommand(
            "SELECT d.id FROM {$prefix}judiciary_deadlines d
             INNER JOIN {$prefix}judiciary j ON j.id = d.judiciary_id
             WHERE d.is_deleted = 0
               AND d.status IN ('pending', 'approaching', 'expired')
               AND j.case_status IN ('closed', 'archived')"
        )->queryColumn();

        if (!empty($closedCaseIds)) {
            $updated += JudiciaryDeadline::updateAll(
                ['status' => JudiciaryDeadline::STATUS_COMPLETED, 'notes' => 'تم إنجازه — القضية مغلقة / مؤرشفة'],
                ['id' => $closedCaseIds]
            );
        }

        // --- Check 0b: complete ALL deadlines for fully-paid judiciary contracts ---
        $paidJudiciaryIds = $db->createCommand(
            "SELECT DISTINCT d.judiciary_id FROM {$prefix}judiciary_deadlines d
             INNER JOIN {$prefix}judiciary j ON j.id = d.judiciary_id
             WHERE d.is_deleted = 0
               AND d.status IN ('pending', 'approaching', 'expired')
               AND (j.case_status IS NULL OR j.case_status NOT IN ('closed', 'archived'))"
        )->queryColumn();

        if (!empty($paidJudiciaryIds)) {
            $paidDeadlineIds = [];
            foreach ($paidJudiciaryIds as $jid) {
                $judiciary = \backend\modules\judiciary\models\Judiciary::findOne($jid);
                if ($judiciary && $judiciary->contract) {
                    try {
                        if ($judiciary->contract->isJudiciaryPaid()) {
                            $ids = $db->createCommand(
                                "SELECT id FROM {$prefix}judiciary_deadlines
                                 WHERE judiciary_id = :jid AND is_deleted = 0
                                   AND status IN ('pending', 'approaching', 'expired')",
                                [':jid' => $jid]
                            )->queryColumn();
                            $paidDeadlineIds = array_merge($paidDeadlineIds, $ids);
                        }
                    } catch (\Exception $e) {
                        // skip if calculation fails
                    }
                }
            }
            if (!empty($paidDeadlineIds)) {
                $updated += JudiciaryDeadline::updateAll(
                    ['status' => JudiciaryDeadline::STATUS_COMPLETED, 'notes' => 'تم إنجازه — العقد مسدد بالكامل'],
                    ['id' => $paidDeadlineIds]
                );
            }
        }

        // --- Check A: complete deadlines whose related action was soft-deleted ---
        $deletedActionIds = $db->createCommand(
            "SELECT d.id FROM {$prefix}judiciary_deadlines d
             INNER JOIN {$prefix}judiciary_customers_actions a ON a.id = d.related_customer_action_id
             WHERE d.related_customer_action_id IS NOT NULL
               AND d.is_deleted = 0
               AND d.status IN ('pending', 'approaching', 'expired')
               AND a.is_deleted = 1"
        )->queryColumn();

        if (!empty($deletedActionIds)) {
            $updated += JudiciaryDeadline::updateAll(
                ['status' => JudiciaryDeadline::STATUS_COMPLETED, 'notes' => 'تم إنجازه — الإجراء المرتبط محذوف'],
                ['id' => $deletedActionIds]
            );
        }

        // --- Check B: complete milestone deadlines when subsequent actions exist ---
        $milestoneIds = $db->createCommand(
            "SELECT d.id FROM {$prefix}judiciary_deadlines d
             WHERE d.is_deleted = 0
               AND d.status IN ('pending', 'approaching', 'expired')
               AND d.deadline_type IN ('" . implode("','", self::$milestoneTypes) . "')
               AND EXISTS (
                   SELECT 1 FROM {$prefix}judiciary_customers_actions a
                   WHERE a.judiciary_id = d.judiciary_id
                     AND (a.is_deleted = 0 OR a.is_deleted IS NULL)
                     AND a.action_date > d.start_date
               )"
        )->queryColumn();

        if (!empty($milestoneIds)) {
            $updated += JudiciaryDeadline::updateAll(
                ['status' => JudiciaryDeadline::STATUS_COMPLETED, 'notes' => 'تم إنجازه تلقائياً — يوجد إجراء لاحق'],
                ['id' => $milestoneIds]
            );
        }

        // --- Check C: complete request_decision deadlines when request was decided or has child actions ---
        $reqDecisionTypes = [
            JudiciaryDeadline::TYPE_REQUEST_DECISION,
            'request_decision',
        ];
        // C1: request explicitly approved/rejected
        $decidedReqIds = $db->createCommand(
            "SELECT d.id FROM {$prefix}judiciary_deadlines d
             INNER JOIN {$prefix}judiciary_customers_actions a ON a.id = d.related_customer_action_id
             WHERE d.is_deleted = 0
               AND d.status IN ('pending', 'approaching', 'expired')
               AND d.deadline_type IN ('" . implode("','", $reqDecisionTypes) . "')
               AND d.related_customer_action_id IS NOT NULL
               AND a.request_status IN ('approved', 'rejected')"
        )->queryColumn();

        if (!empty($decidedReqIds)) {
            $updated += JudiciaryDeadline::updateAll(
                ['status' => JudiciaryDeadline::STATUS_COMPLETED, 'notes' => 'تم إنجازه — تم البت في الطلب'],
                ['id' => $decidedReqIds]
            );
        }

        // C2: request has child actions (e.g. طلب حبس → مذكرة احضار = implicit approval)
        $implicitApprovalIds = $db->createCommand(
            "SELECT d.id FROM {$prefix}judiciary_deadlines d
             WHERE d.is_deleted = 0
               AND d.status IN ('pending', 'approaching', 'expired')
               AND d.deadline_type IN ('" . implode("','", $reqDecisionTypes) . "')
               AND d.related_customer_action_id IS NOT NULL
               AND EXISTS (
                   SELECT 1 FROM {$prefix}judiciary_customers_actions child
                   WHERE child.parent_id = d.related_customer_action_id
                     AND (child.is_deleted = 0 OR child.is_deleted IS NULL)
               )"
        )->queryColumn();

        if (!empty($implicitApprovalIds)) {
            $updated += JudiciaryDeadline::updateAll(
                ['status' => JudiciaryDeadline::STATUS_COMPLETED, 'notes' => 'تم إنجازه — موافقة ضمنية (صدر إجراء مرتبط بالطلب)'],
                ['id' => $implicitApprovalIds]
            );
        }

        // --- Check D: complete correspondence deadlines when subsequent actions exist after deadline ---
        $corrDeadlineTypes = [
            JudiciaryDeadline::TYPE_CORRESPONDENCE_10WD,
            'correspondence',
        ];
        $staleTaskIds = $db->createCommand(
            "SELECT d.id FROM {$prefix}judiciary_deadlines d
             WHERE d.is_deleted = 0
               AND d.status IN ('pending', 'approaching', 'expired')
               AND d.deadline_type IN ('" . implode("','", $corrDeadlineTypes) . "')
               AND EXISTS (
                   SELECT 1 FROM {$prefix}judiciary_customers_actions a
                   WHERE a.judiciary_id = d.judiciary_id
                     AND (a.is_deleted = 0 OR a.is_deleted IS NULL)
                     AND a.action_date > d.deadline_date
               )"
        )->queryColumn();

        if (!empty($staleTaskIds)) {
            $updated += JudiciaryDeadline::updateAll(
                ['status' => JudiciaryDeadline::STATUS_COMPLETED, 'notes' => 'تم إنجازه — يوجد إجراءات لاحقة بعد انتهاء المهلة'],
                ['id' => $staleTaskIds]
            );
        }

        // --- Mark overdue as expired ---
        $today = date('Y-m-d');
        $approaching = date('Y-m-d', strtotime('+3 days'));

        $updated += JudiciaryDeadline::updateAll(
            ['status' => JudiciaryDeadline::STATUS_EXPIRED],
            ['AND',
                ['status' => [JudiciaryDeadline::STATUS_PENDING, JudiciaryDeadline::STATUS_APPROACHING]],
                ['<', 'deadline_date', $today],
                ['is_deleted' => 0],
            ]
        );

        // --- Mark approaching ---
        $updated += JudiciaryDeadline::updateAll(
            ['status' => JudiciaryDeadline::STATUS_APPROACHING],
            ['AND',
                ['status' => JudiciaryDeadline::STATUS_PENDING],
                ['<=', 'deadline_date', $approaching],
                ['>=', 'deadline_date', $today],
                ['is_deleted' => 0],
            ]
        );

        return $updated;
    }
}
