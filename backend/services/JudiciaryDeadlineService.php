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

    /* ─── Deadline status refresh ─── */

    /**
     * Refresh statuses for all active deadlines (call via cron or admin action).
     */
    public static function refreshAllStatuses(): int
    {
        $today = date('Y-m-d');
        $approaching = date('Y-m-d', strtotime('+3 days'));
        $updated = 0;

        $updated += JudiciaryDeadline::updateAll(
            ['status' => JudiciaryDeadline::STATUS_EXPIRED],
            ['AND',
                ['status' => [JudiciaryDeadline::STATUS_PENDING, JudiciaryDeadline::STATUS_APPROACHING]],
                ['<', 'deadline_date', $today],
                ['is_deleted' => 0],
            ]
        );

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
