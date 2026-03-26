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
    public static function generateMissingDeadlines(): int
    {
        $cache = \Yii::$app->cache;
        $cacheKey = 'deadline_gen_done';
        if ($cache && $cache->get($cacheKey)) {
            return 0;
        }

        $db     = \Yii::$app->db;
        $prefix = $db->tablePrefix;
        $now    = time();
        $count  = 0;
        $reqType  = JudiciaryDeadline::TYPE_REQUEST_DECISION;
        $regType  = JudiciaryDeadline::TYPE_REGISTRATION_3WD;

        // 1) Request-decision deadlines — pure SQL INSERT ... SELECT (no PHP loop)
        $count += (int)$db->createCommand(
            "INSERT INTO {$prefix}judiciary_deadlines
                (judiciary_id, customer_id, deadline_type, day_type, label,
                 start_date, deadline_date, status,
                 related_communication_id, related_customer_action_id,
                 notes, is_deleted, created_at, updated_at)
             SELECT
                jca.judiciary_id,
                jca.customers_id,
                '{$reqType}',
                'working',
                'قرار القاضي على الطلب',
                COALESCE(jca.action_date, FROM_UNIXTIME(jca.created_at, '%Y-%m-%d')),
                DATE_ADD(COALESCE(jca.action_date, FROM_UNIXTIME(jca.created_at, '%Y-%m-%d')), INTERVAL 5 DAY),
                'pending',
                NULL,
                jca.id,
                NULL, 0, {$now}, {$now}
             FROM {$prefix}judiciary_customers_actions jca
             INNER JOIN {$prefix}judiciary_actions ja ON ja.id = jca.judiciary_actions_id
             WHERE ja.action_nature = 'request'
               AND (jca.is_deleted = 0 OR jca.is_deleted IS NULL)
               AND NOT EXISTS (
                   SELECT 1 FROM {$prefix}judiciary_deadlines dl
                   WHERE dl.related_customer_action_id = jca.id
                     AND dl.deadline_type IN ('{$reqType}', 'request_decision')
                     AND dl.is_deleted = 0
               )"
        )->execute();

        // 2) Registration-check deadline — pure SQL INSERT ... SELECT
        $count += (int)$db->createCommand(
            "INSERT INTO {$prefix}judiciary_deadlines
                (judiciary_id, customer_id, deadline_type, day_type, label,
                 start_date, deadline_date, status,
                 related_communication_id, related_customer_action_id,
                 notes, is_deleted, created_at, updated_at)
             SELECT
                j.id,
                NULL,
                '{$regType}',
                'working',
                'فحص حالة التبليغ بعد التسجيل',
                FROM_UNIXTIME(j.created_at, '%Y-%m-%d'),
                DATE_ADD(FROM_UNIXTIME(j.created_at, '%Y-%m-%d'), INTERVAL 5 DAY),
                'pending',
                NULL, NULL, NULL, 0, {$now}, {$now}
             FROM {$prefix}judiciary j
             WHERE (j.is_deleted = 0 OR j.is_deleted IS NULL)
               AND NOT EXISTS (
                   SELECT 1 FROM {$prefix}judiciary_deadlines dl
                   WHERE dl.judiciary_id = j.id
                     AND dl.deadline_type IN ('{$regType}', 'registration')
                     AND dl.is_deleted = 0
               )"
        )->execute();

        if ($count === 0 && $cache) {
            $cache->set($cacheKey, true, 3600);
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
     * Throttled wrapper — runs the full refresh at most once every $ttl seconds.
     * Pass $force=true to bypass the throttle (e.g., explicit refresh button).
     */
    public static function refreshAllStatuses(bool $force = false): int
    {
        $cache = \Yii::$app->cache;
        $cacheKey = 'deadline_refresh_ts';
        $ttl = 600; // 10 minutes

        if (!$force && $cache) {
            $lastRun = $cache->get($cacheKey);
            if ($lastRun && (time() - $lastRun) < $ttl) {
                return 0;
            }
        }

        $result = self::doRefreshAllStatuses();

        if ($cache) {
            $cache->set($cacheKey, time(), $ttl + 60);
        }

        return $result;
    }

    /**
     * Invalidate the refresh cache so next call runs immediately.
     */
    public static function invalidateRefreshCache(): void
    {
        $cache = \Yii::$app->cache;
        if ($cache) {
            $cache->delete('deadline_refresh_ts');
        }
    }

    /* ─── Live-status VIEW (replaces refreshAllStatuses for reads) ─── */

    public static function ensureLiveView(): void
    {
        $cache = \Yii::$app->cache;
        $cacheKey = 'deadline_view_created';
        if ($cache && $cache->get($cacheKey)) {
            return;
        }

        $p = \Yii::$app->db->tablePrefix;

        \Yii::$app->db->createCommand("
            CREATE OR REPLACE VIEW {$p}v_deadline_live AS
            SELECT
                d.id,
                d.judiciary_id,
                d.customer_id,
                d.deadline_type,
                d.day_type,
                d.label,
                d.start_date,
                d.deadline_date,
                d.related_communication_id,
                d.related_customer_action_id,
                d.notes,
                d.is_deleted,
                d.created_at,
                d.updated_at,
                d.created_by,

                CASE
                    WHEN j.case_status IN ('closed','archived')
                        THEN 'completed'

                    WHEN j.contract_id IS NOT NULL
                         AND cb.status = 'judiciary'
                         AND cb.remaining_balance <= 0
                        THEN 'completed'

                    WHEN d.related_customer_action_id IS NOT NULL
                         AND ra.is_deleted = 1
                        THEN 'completed'

                    WHEN d.deadline_type IN ('registration_3wd','registration')
                         AND EXISTS (
                             SELECT 1 FROM {$p}judiciary_customers_actions s
                             WHERE s.judiciary_id = d.judiciary_id
                               AND (s.is_deleted = 0 OR s.is_deleted IS NULL)
                               AND s.action_date > d.start_date
                         )
                        THEN 'completed'

                    WHEN d.deadline_type IN ('request_decision_3wd','request_decision')
                         AND d.related_customer_action_id IS NOT NULL
                         AND ra.request_status IN ('approved','rejected')
                        THEN 'completed'

                    WHEN d.deadline_type IN ('request_decision_3wd','request_decision')
                         AND d.related_customer_action_id IS NOT NULL
                         AND EXISTS (
                             SELECT 1 FROM {$p}judiciary_customers_actions ch
                             WHERE ch.parent_id = d.related_customer_action_id
                               AND (ch.is_deleted = 0 OR ch.is_deleted IS NULL)
                         )
                        THEN 'completed'

                    WHEN d.deadline_type IN ('correspondence_10wd','correspondence')
                         AND EXISTS (
                             SELECT 1 FROM {$p}judiciary_customers_actions s2
                             WHERE s2.judiciary_id = d.judiciary_id
                               AND (s2.is_deleted = 0 OR s2.is_deleted IS NULL)
                               AND s2.action_date > d.deadline_date
                         )
                        THEN 'completed'

                    WHEN d.deadline_date < CURDATE()
                        THEN 'expired'

                    WHEN d.deadline_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                        THEN 'approaching'

                    ELSE 'pending'
                END AS live_status

            FROM {$p}judiciary_deadlines d
            LEFT JOIN {$p}judiciary j ON j.id = d.judiciary_id
            LEFT JOIN {$p}judiciary_customers_actions ra
                   ON ra.id = d.related_customer_action_id
            LEFT JOIN {$p}vw_contract_balance cb ON cb.contract_id = j.contract_id

            WHERE d.is_deleted = 0
        ")->execute();

        if ($cache) {
            $cache->set($cacheKey, true, 86400);
        }
    }

    /**
     * Query the live VIEW for dashboard data.
     * Returns ['expired' => [...], 'approaching' => [...], 'pending' => [...]]
     */
    /**
     * Fast dashboard counts — single query on composite index, no VIEW.
     * Temporal status (expired/approaching/pending) derived from deadline_date.
     */
    public static function getDashboardCounts(): array
    {
        $p = \Yii::$app->db->tablePrefix;
        $row = \Yii::$app->db->createCommand("
            SELECT
                SUM(deadline_date < CURDATE()) AS expired,
                SUM(deadline_date >= CURDATE() AND deadline_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)) AS approaching,
                SUM(deadline_date > DATE_ADD(CURDATE(), INTERVAL 3 DAY)) AS pending
            FROM {$p}judiciary_deadlines
            WHERE is_deleted = 0 AND status != 'completed'
        ")->queryOne();

        return [
            'expired'     => (int) ($row['expired'] ?? 0),
            'approaching' => (int) ($row['approaching'] ?? 0),
            'pending'     => (int) ($row['pending'] ?? 0),
        ];
    }

    /**
     * Paginated dashboard data — direct indexed query, no VIEW.
     * JOINs only for display labels (judiciary_number, action_name, customer_name).
     */
    public static function getDashboardPage(string $status, int $page = 1, int $perPage = 50): array
    {
        $p = \Yii::$app->db->tablePrefix;
        $db = \Yii::$app->db;
        $offset = ($page - 1) * $perPage;

        $dateConditions = [
            'expired'     => 'd.deadline_date < CURDATE()',
            'approaching' => 'd.deadline_date >= CURDATE() AND d.deadline_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)',
            'pending'     => 'd.deadline_date > DATE_ADD(CURDATE(), INTERVAL 3 DAY)',
        ];
        $dateCond = $dateConditions[$status] ?? $dateConditions['expired'];

        $rows = $db->createCommand("
            SELECT d.id, d.judiciary_id, d.customer_id, d.deadline_type,
                   d.label, d.deadline_date, d.related_customer_action_id,
                   j.judiciary_number,
                   ja.name AS action_name,
                   cu.name AS customer_name
            FROM {$p}judiciary_deadlines d
            LEFT JOIN {$p}judiciary j ON j.id = d.judiciary_id
            LEFT JOIN {$p}judiciary_customers_actions jca ON jca.id = d.related_customer_action_id
            LEFT JOIN {$p}judiciary_actions ja ON ja.id = jca.judiciary_actions_id
            LEFT JOIN {$p}customers cu ON cu.id = jca.customers_id
            WHERE d.is_deleted = 0 AND d.status != 'completed'
              AND {$dateCond}
            ORDER BY d.deadline_date ASC
            LIMIT {$perPage} OFFSET {$offset}
        ")->queryAll();

        return $rows;
    }

    /* ─── Completion helpers for afterSave hooks ─── */

    public static function completeDeadlinesForCase(int $judiciaryId): int
    {
        return JudiciaryDeadline::updateAll(
            ['status' => JudiciaryDeadline::STATUS_COMPLETED],
            ['AND',
                ['judiciary_id' => $judiciaryId],
                ['is_deleted' => 0],
                ['!=', 'status', JudiciaryDeadline::STATUS_COMPLETED],
            ]
        );
    }

    public static function completeDeadlineForRequest(int $actionId): int
    {
        return JudiciaryDeadline::updateAll(
            ['status' => JudiciaryDeadline::STATUS_COMPLETED],
            ['AND',
                ['related_customer_action_id' => $actionId],
                ['in', 'deadline_type', ['request_decision_3wd', 'request_decision']],
                ['is_deleted' => 0],
                ['!=', 'status', JudiciaryDeadline::STATUS_COMPLETED],
            ]
        );
    }

    private static function doRefreshAllStatuses(): int
    {
        $db = \Yii::$app->db;
        $prefix = $db->tablePrefix;
        $updated = 0;
        $activeFilter = "d.is_deleted = 0 AND d.status IN ('pending','approaching','expired')";

        // --- Revert: undo incorrect 180-day stale auto-completion (one-time migration) ---
        $staleCount = (int)$db->createCommand(
            "SELECT COUNT(*) FROM {$prefix}judiciary_deadlines
             WHERE is_deleted = 0 AND status = 'completed'
               AND (notes = 'تم إنجازه — منتهي أكثر من 6 أشهر بدون نشاط' OR notes = 'ترحيل تلقائي')"
        )->queryScalar();
        if ($staleCount > 0) {
            JudiciaryDeadline::updateAll(
                ['status' => JudiciaryDeadline::STATUS_EXPIRED, 'notes' => null],
                ['AND',
                    ['status' => JudiciaryDeadline::STATUS_COMPLETED],
                    ['or', ['notes' => 'تم إنجازه — منتهي أكثر من 6 أشهر بدون نشاط'], ['notes' => 'ترحيل تلقائي']],
                    ['is_deleted' => 0],
                ]
            );
        }

        // --- Check 0a: complete deadlines for closed/archived cases ---
        $updated += (int)$db->createCommand(
            "UPDATE {$prefix}judiciary_deadlines d
             INNER JOIN {$prefix}judiciary j ON j.id = d.judiciary_id
             SET d.status = 'completed', d.notes = 'تم إنجازه — القضية مغلقة / مؤرشفة'
             WHERE {$activeFilter}
               AND j.case_status IN ('closed','archived')"
        )->execute();

        // --- Check 0b: complete deadlines for fully-paid judiciary contracts (batch) ---
        $judiciaryContractMap = $db->createCommand(
            "SELECT DISTINCT j.id AS jid, j.contract_id
             FROM {$prefix}judiciary_deadlines d
             INNER JOIN {$prefix}judiciary j ON j.id = d.judiciary_id
             WHERE {$activeFilter}
               AND j.contract_id IS NOT NULL
               AND (j.case_status IS NULL OR j.case_status NOT IN ('closed','archived'))"
        )->queryAll();

        if (!empty($judiciaryContractMap)) {
            $contractIds = array_unique(array_column($judiciaryContractMap, 'contract_id'));
            $batch = \backend\modules\followUp\helper\ContractCalculations::batchPreload($contractIds);

            $paidJudiciaryIds = [];
            foreach ($judiciaryContractMap as $row) {
                $cid = $row['contract_id'];
                if (!empty($batch[$cid]['isJudiciaryPaid'])) {
                    $paidJudiciaryIds[] = (int)$row['jid'];
                }
            }

            if (!empty($paidJudiciaryIds)) {
                $idList = implode(',', $paidJudiciaryIds);
                $updated += (int)$db->createCommand(
                    "UPDATE {$prefix}judiciary_deadlines
                     SET status = 'completed', notes = 'تم إنجازه — العقد مسدد بالكامل'
                     WHERE is_deleted = 0
                       AND status IN ('pending','approaching','expired')
                       AND judiciary_id IN ({$idList})"
                )->execute();
            }
        }

        // --- Check A: complete deadlines whose related action was soft-deleted ---
        $updated += (int)$db->createCommand(
            "UPDATE {$prefix}judiciary_deadlines d
             INNER JOIN {$prefix}judiciary_customers_actions a ON a.id = d.related_customer_action_id
             SET d.status = 'completed', d.notes = 'تم إنجازه — الإجراء المرتبط محذوف'
             WHERE d.related_customer_action_id IS NOT NULL
               AND d.is_deleted = 0
               AND d.status IN ('pending','approaching','expired')
               AND a.is_deleted = 1"
        )->execute();

        // --- Check B: complete milestone deadlines when subsequent actions exist ---
        $mlTypes = "'" . implode("','", self::$milestoneTypes) . "'";
        $updated += (int)$db->createCommand(
            "UPDATE {$prefix}judiciary_deadlines d
             SET d.status = 'completed', d.notes = 'تم إنجازه تلقائياً — يوجد إجراء لاحق'
             WHERE d.is_deleted = 0
               AND d.status IN ('pending','approaching','expired')
               AND d.deadline_type IN ({$mlTypes})
               AND EXISTS (
                   SELECT 1 FROM {$prefix}judiciary_customers_actions a
                   WHERE a.judiciary_id = d.judiciary_id
                     AND (a.is_deleted = 0 OR a.is_deleted IS NULL)
                     AND a.action_date > d.start_date
               )"
        )->execute();

        // --- Check C1: request explicitly approved/rejected ---
        $reqTypes = "'" . JudiciaryDeadline::TYPE_REQUEST_DECISION . "','request_decision'";
        $updated += (int)$db->createCommand(
            "UPDATE {$prefix}judiciary_deadlines d
             INNER JOIN {$prefix}judiciary_customers_actions a ON a.id = d.related_customer_action_id
             SET d.status = 'completed', d.notes = 'تم إنجازه — تم البت في الطلب'
             WHERE d.is_deleted = 0
               AND d.status IN ('pending','approaching','expired')
               AND d.deadline_type IN ({$reqTypes})
               AND d.related_customer_action_id IS NOT NULL
               AND a.request_status IN ('approved','rejected')"
        )->execute();

        // --- Check C2: request has child actions (implicit approval) ---
        $updated += (int)$db->createCommand(
            "UPDATE {$prefix}judiciary_deadlines d
             SET d.status = 'completed', d.notes = 'تم إنجازه — موافقة ضمنية (صدر إجراء مرتبط بالطلب)'
             WHERE d.is_deleted = 0
               AND d.status IN ('pending','approaching','expired')
               AND d.deadline_type IN ({$reqTypes})
               AND d.related_customer_action_id IS NOT NULL
               AND EXISTS (
                   SELECT 1 FROM {$prefix}judiciary_customers_actions child
                   WHERE child.parent_id = d.related_customer_action_id
                     AND (child.is_deleted = 0 OR child.is_deleted IS NULL)
               )"
        )->execute();

        // --- Check D: complete correspondence deadlines with subsequent actions ---
        $corrTypes = "'" . JudiciaryDeadline::TYPE_CORRESPONDENCE_10WD . "','correspondence'";
        $updated += (int)$db->createCommand(
            "UPDATE {$prefix}judiciary_deadlines d
             SET d.status = 'completed', d.notes = 'تم إنجازه — يوجد إجراءات لاحقة بعد انتهاء المهلة'
             WHERE d.is_deleted = 0
               AND d.status IN ('pending','approaching','expired')
               AND d.deadline_type IN ({$corrTypes})
               AND EXISTS (
                   SELECT 1 FROM {$prefix}judiciary_customers_actions a
                   WHERE a.judiciary_id = d.judiciary_id
                     AND (a.is_deleted = 0 OR a.is_deleted IS NULL)
                     AND a.action_date > d.deadline_date
               )"
        )->execute();

        // --- Mark overdue as expired ---
        $today = date('Y-m-d');
        $approaching = date('Y-m-d', strtotime('+3 days'));

        $updated += (int)$db->createCommand(
            "UPDATE {$prefix}judiciary_deadlines
             SET status = 'expired'
             WHERE is_deleted = 0
               AND status IN ('pending','approaching')
               AND deadline_date < '{$today}'"
        )->execute();

        // --- Mark approaching ---
        $updated += (int)$db->createCommand(
            "UPDATE {$prefix}judiciary_deadlines
             SET status = 'approaching'
             WHERE is_deleted = 0
               AND status = 'pending'
               AND deadline_date <= '{$approaching}'
               AND deadline_date >= '{$today}'"
        )->execute();

        return $updated;
    }
}
