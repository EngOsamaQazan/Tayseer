<?php

namespace backend\services;

use Yii;
use backend\models\JudiciaryDeadline;
use backend\models\JudiciaryDefendantStage;
use backend\modules\judiciary\models\Judiciary;
use backend\modules\diwan\models\DiwanCorrespondence;
use backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions;

/**
 * Handles the execution workflow automation:
 * Registration → Notification (3wd) → Wait 16 days → Comprehensive Request → Letters (3wd)
 */
class JudiciaryExecutionService
{
    private $deadlineService;

    public function __construct()
    {
        $this->deadlineService = new JudiciaryDeadlineService();
    }

    /**
     * Mark a defendant as notified on a given date and trigger the automation chain:
     * 1) Save notification_date on defendant stage
     * 2) Advance defendant to STAGE_NOTIFICATION
     * 3) Create 16-day deadline for comprehensive request submission
     */
    public function markDefendantNotified(int $judiciaryId, int $customerId, string $notificationDate): array
    {
        $ds = JudiciaryDefendantStage::find()
            ->where(['judiciary_id' => $judiciaryId, 'customer_id' => $customerId])
            ->one();

        if (!$ds) {
            $ds = new JudiciaryDefendantStage([
                'judiciary_id' => $judiciaryId,
                'customer_id' => $customerId,
                'current_stage' => Judiciary::STAGE_CASE_PREPARATION,
                'stage_updated_at' => date('Y-m-d H:i:s'),
            ]);
            $ds->save(false);
        }

        $ds->notification_date = $notificationDate;
        $ds->stage_updated_at = date('Y-m-d H:i:s');
        $ds->save(false, ['notification_date', 'stage_updated_at']);

        $workflow = new JudiciaryWorkflowService();
        $currentRank = Judiciary::getStageRank($ds->current_stage);
        $notifRank = Judiciary::getStageRank(Judiciary::STAGE_NOTIFICATION);
        if ($currentRank < $notifRank) {
            $workflow->advanceDefendant($judiciaryId, $customerId, Judiciary::STAGE_NOTIFICATION);
        }

        $comprehensiveDate = $this->deadlineService->addCalendarDaysWithHolidayCheck($notificationDate, 16);

        $dup = JudiciaryDeadline::find()
            ->where([
                'judiciary_id' => $judiciaryId,
                'customer_id' => $customerId,
                'deadline_type' => JudiciaryDeadline::TYPE_COMPREHENSIVE_16CD,
                'is_deleted' => 0,
            ])
            ->andWhere(['!=', 'status', JudiciaryDeadline::STATUS_COMPLETED])
            ->exists();

        $deadlineCreated = false;
        if (!$dup) {
            $dl = new JudiciaryDeadline([
                'judiciary_id' => $judiciaryId,
                'customer_id' => $customerId,
                'deadline_type' => JudiciaryDeadline::TYPE_COMPREHENSIVE_16CD,
                'day_type' => JudiciaryDeadline::DAY_CALENDAR,
                'label' => 'موعد تقديم الطلب الشامل',
                'start_date' => $notificationDate,
                'deadline_date' => $comprehensiveDate,
                'status' => JudiciaryDeadline::STATUS_PENDING,
            ]);
            $deadlineCreated = $dl->save();
        }

        return [
            'success' => true,
            'notification_date' => $notificationDate,
            'comprehensive_date' => $comprehensiveDate,
            'deadline_created' => $deadlineCreated,
        ];
    }

    /**
     * Record that a comprehensive request was submitted for a defendant.
     * Creates a 3-day deadline for letters issuance.
     */
    public function recordComprehensiveRequest(int $judiciaryId, int $customerId, string $requestDate): array
    {
        $ds = JudiciaryDefendantStage::find()
            ->where(['judiciary_id' => $judiciaryId, 'customer_id' => $customerId])
            ->one();

        if ($ds) {
            $ds->comprehensive_request_date = $requestDate;
            $ds->save(false, ['comprehensive_request_date']);
        }

        $workflow = new JudiciaryWorkflowService();
        $workflow->advanceDefendant($judiciaryId, $customerId, Judiciary::STAGE_PROCEDURAL_REQUESTS);

        JudiciaryDeadline::updateAll(
            ['status' => JudiciaryDeadline::STATUS_COMPLETED],
            ['AND',
                ['judiciary_id' => $judiciaryId, 'customer_id' => $customerId],
                ['deadline_type' => JudiciaryDeadline::TYPE_COMPREHENSIVE_16CD],
                ['is_deleted' => 0],
                ['!=', 'status', JudiciaryDeadline::STATUS_COMPLETED],
            ]
        );

        $lettersDate = $this->deadlineService->addWorkingDays($requestDate, 3);

        $dup = JudiciaryDeadline::find()
            ->where([
                'judiciary_id' => $judiciaryId,
                'customer_id' => $customerId,
                'deadline_type' => JudiciaryDeadline::TYPE_LETTERS_ISSUANCE_3WD,
                'is_deleted' => 0,
            ])
            ->andWhere(['!=', 'status', JudiciaryDeadline::STATUS_COMPLETED])
            ->exists();

        if (!$dup) {
            $dl = new JudiciaryDeadline([
                'judiciary_id' => $judiciaryId,
                'customer_id' => $customerId,
                'deadline_type' => JudiciaryDeadline::TYPE_LETTERS_ISSUANCE_3WD,
                'day_type' => JudiciaryDeadline::DAY_WORKING,
                'label' => 'إصدار الكتب للجهات المعنية',
                'start_date' => $requestDate,
                'deadline_date' => $lettersDate,
                'status' => JudiciaryDeadline::STATUS_PENDING,
            ]);
            $dl->save();
        }

        return [
            'success' => true,
            'letters_deadline' => $lettersDate,
        ];
    }

    /**
     * Generate bulk correspondence records for all relevant authorities
     * after the comprehensive request is approved.
     */
    public function generateBulkCorrespondence(int $judiciaryId, int $customerId, array $options = []): array
    {
        $judiciary = Judiciary::findOne($judiciaryId);
        if (!$judiciary) {
            return ['success' => false, 'message' => 'القضية غير موجودة'];
        }

        $customer = \backend\modules\customers\models\Customers::findOne($customerId);
        if (!$customer) {
            return ['success' => false, 'message' => 'المحكوم عليه غير موجود'];
        }

        $db = Yii::$app->db;
        $sendDate = $options['send_date'] ?? date('Y-m-d');
        $deliveryMethod = $options['delivery_method'] ?? 'hand';
        $created = [];

        $baseAttrs = [
            'communication_type' => DiwanCorrespondence::TYPE_OUTGOING_LETTER,
            'related_module' => 'judiciary',
            'related_record_id' => $judiciaryId,
            'customer_id' => $customerId,
            'direction' => 'outgoing',
            'delivery_method' => $deliveryMethod,
            'correspondence_date' => $sendDate,
            'delivery_date' => $sendDate,
            'status' => DiwanCorrespondence::STATUS_SENT,
            'company_id' => $judiciary->company_id,
        ];

        $transaction = $db->beginTransaction();
        try {
            // 1) Salary garnishment — employer
            if ($customer->job_title) {
                $corr = new DiwanCorrespondence(array_merge($baseAttrs, [
                    'recipient_type' => DiwanCorrespondence::RECIPIENT_EMPLOYER,
                    'job_id' => $customer->job_title,
                    'purpose' => 'salary_deduction',
                    'content_summary' => 'حجز ثلث مجموع ما يتقاضاه لدى جهة عمله',
                ]));
                if ($corr->save(false)) $created[] = ['type' => 'employer', 'id' => $corr->id];
            }

            // 2) Bank freezes — all banks
            $banks = $db->createCommand("SELECT id, name FROM {{%bancks}} WHERE (is_deleted = 0 OR is_deleted IS NULL)")->queryAll();
            foreach ($banks as $bank) {
                $corr = new DiwanCorrespondence(array_merge($baseAttrs, [
                    'recipient_type' => DiwanCorrespondence::RECIPIENT_BANK,
                    'bank_id' => (int) $bank['id'],
                    'purpose' => 'account_freeze',
                    'content_summary' => 'حجز حساباته البنكية لدى ' . $bank['name'],
                ]));
                if ($corr->save(false)) $created[] = ['type' => 'bank', 'id' => $corr->id, 'name' => $bank['name']];
            }

            // 3) Companies registry (دائرة مراقبة الشركات)
            $companiesAuth = \backend\models\JudiciaryAuthority::find()
                ->where(['authority_type' => \backend\models\JudiciaryAuthority::TYPE_COMPANIES_REGISTRY])
                ->one();
            if (!$companiesAuth) {
                $companiesAuth = \backend\models\JudiciaryAuthority::find()
                    ->where(['like', 'name', 'مراقبة الشركات'])->one();
            }
            $corr = new DiwanCorrespondence(array_merge($baseAttrs, [
                'recipient_type' => DiwanCorrespondence::RECIPIENT_ADMINISTRATIVE,
                'authority_id' => $companiesAuth ? $companiesAuth->id : null,
                'purpose' => 'asset_seizure',
                'content_summary' => 'حجز تنفيذي على شركات ومؤسسات فردية',
            ]));
            if ($corr->save(false)) $created[] = ['type' => 'companies_registry', 'id' => $corr->id];

            // 4) Ministry of Industry & Trade (وزارة الصناعة والتجارة)
            $industryAuth = \backend\models\JudiciaryAuthority::find()
                ->where(['authority_type' => \backend\models\JudiciaryAuthority::TYPE_INDUSTRY_TRADE])
                ->one();
            if (!$industryAuth) {
                $industryAuth = \backend\models\JudiciaryAuthority::find()
                    ->where(['like', 'name', 'الصناعة والتجارة'])->one();
            }
            if ($industryAuth) {
                $corr = new DiwanCorrespondence(array_merge($baseAttrs, [
                    'recipient_type' => DiwanCorrespondence::RECIPIENT_ADMINISTRATIVE,
                    'authority_id' => $industryAuth->id,
                    'purpose' => 'asset_seizure',
                    'content_summary' => 'حجز تنفيذي على مؤسسات تجارية',
                ]));
                if ($corr->save(false)) $created[] = ['type' => 'industry_ministry', 'id' => $corr->id];
            }

            // 5) E-wallets & payment providers (المحافظ الإلكترونية)
            $ewalletAuths = \backend\models\JudiciaryAuthority::find()
                ->where(['like', 'name', 'محفظة'])
                ->orWhere(['like', 'name', 'دفع إلكتروني'])
                ->orWhere(['like', 'name', 'e-wallet'])
                ->orWhere(['like', 'name', 'زين كاش'])
                ->orWhere(['like', 'name', 'أورنج'])
                ->orWhere(['like', 'name', 'أمنية'])
                ->all();
            foreach ($ewalletAuths as $ewa) {
                $corr = new DiwanCorrespondence(array_merge($baseAttrs, [
                    'recipient_type' => DiwanCorrespondence::RECIPIENT_ADMINISTRATIVE,
                    'authority_id' => $ewa->id,
                    'purpose' => 'account_freeze',
                    'content_summary' => 'حجز حساباته لدى ' . $ewa->name,
                ]));
                if ($corr->save(false)) $created[] = ['type' => 'e_wallet', 'id' => $corr->id, 'name' => $ewa->name];
            }

            // 6) Seizure on movable/immovable assets via employer (حجز أموال لدى الغير)
            if ($customer->job_title) {
                $corr = new DiwanCorrespondence(array_merge($baseAttrs, [
                    'recipient_type' => DiwanCorrespondence::RECIPIENT_EMPLOYER,
                    'job_id' => $customer->job_title,
                    'purpose' => 'third_party_seizure',
                    'content_summary' => 'حجز أموال المدين لدى الغير',
                ]));
                if ($corr->save(false)) $created[] = ['type' => 'third_party', 'id' => $corr->id];
            }

            JudiciaryDeadline::updateAll(
                ['status' => JudiciaryDeadline::STATUS_COMPLETED],
                ['AND',
                    ['judiciary_id' => $judiciaryId, 'customer_id' => $customerId],
                    ['deadline_type' => JudiciaryDeadline::TYPE_LETTERS_ISSUANCE_3WD],
                    ['is_deleted' => 0],
                    ['!=', 'status', JudiciaryDeadline::STATUS_COMPLETED],
                ]
            );

            $workflow = new JudiciaryWorkflowService();
            $workflow->advanceDefendant($judiciaryId, $customerId, Judiciary::STAGE_CORRESPONDENCE);

            $transaction->commit();

            return [
                'success' => true,
                'message' => 'تم إنشاء ' . count($created) . ' كتاب/مراسلة',
                'created' => $created,
                'count' => count($created),
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => 'خطأ: ' . $e->getMessage()];
        }
    }

    /**
     * Get the execution progress summary for a case.
     */
    public static function getExecutionSummary(int $judiciaryId): array
    {
        $judiciary = Judiciary::findOne($judiciaryId);
        if (!$judiciary) return [];

        $defendants = JudiciaryDefendantStage::find()
            ->where(['judiciary_id' => $judiciaryId])
            ->with('customer')
            ->all();

        $totalDefendants = count($defendants);
        $notified = 0;
        $requestSubmitted = 0;
        $pendingNotification = 0;
        $pendingRequest = [];
        $readyForLetters = [];

        $today = date('Y-m-d');
        $deadlineService = new JudiciaryDeadlineService();

        $defendantDetails = [];
        foreach ($defendants as $ds) {
            $detail = [
                'id' => $ds->customer_id,
                'name' => $ds->customer ? $ds->customer->name : ('عميل #' . $ds->customer_id),
                'stage' => $ds->current_stage,
                'stage_label' => Judiciary::getStageLabel($ds->current_stage),
                'notification_date' => $ds->notification_date,
                'comprehensive_request_date' => $ds->comprehensive_request_date,
                'next_action' => null,
                'next_action_date' => null,
                'next_action_type' => null,
                'days_remaining' => null,
                'status_class' => 'pending',
            ];

            if (empty($ds->notification_date)) {
                $pendingNotification++;
                $detail['next_action'] = 'متابعة التبليغ';
                $detail['next_action_type'] = 'notification';
                $detail['status_class'] = 'warning';
            } elseif (empty($ds->comprehensive_request_date)) {
                $notified++;
                $comprehensiveDate = $deadlineService->addCalendarDaysWithHolidayCheck($ds->notification_date, 16);
                $daysLeft = (int) ((strtotime($comprehensiveDate) - strtotime($today)) / 86400);

                if ($daysLeft <= 0) {
                    $detail['next_action'] = 'تقديم الطلب الشامل (متأخر ' . abs($daysLeft) . ' يوم)';
                    $detail['status_class'] = 'danger';
                    $pendingRequest[] = $ds->customer_id;
                } elseif ($daysLeft <= 3) {
                    $detail['next_action'] = 'تقديم الطلب الشامل (باقي ' . $daysLeft . ' يوم)';
                    $detail['status_class'] = 'warning';
                } else {
                    $detail['next_action'] = 'انتظار مدة التبليغ (باقي ' . $daysLeft . ' يوم)';
                    $detail['status_class'] = 'info';
                }
                $detail['next_action_date'] = $comprehensiveDate;
                $detail['days_remaining'] = $daysLeft;
                $detail['next_action_type'] = 'comprehensive_request';
            } else {
                $notified++;
                $requestSubmitted++;
                $lettersDate = $deadlineService->addWorkingDays($ds->comprehensive_request_date, 3);

                $lettersCount = DiwanCorrespondence::find()
                    ->where([
                        'related_module' => 'judiciary',
                        'related_record_id' => $judiciaryId,
                        'customer_id' => $ds->customer_id,
                        'communication_type' => DiwanCorrespondence::TYPE_OUTGOING_LETTER,
                    ])
                    ->count();

                if ($lettersCount > 0) {
                    $detail['next_action'] = 'تم إصدار ' . $lettersCount . ' كتاب — متابعة الردود';
                    $detail['next_action_type'] = 'follow_up';
                    $detail['status_class'] = 'success';
                } else {
                    $detail['next_action'] = 'إصدار الكتب للجهات المعنية';
                    $detail['next_action_type'] = 'letters';
                    $detail['status_class'] = 'info';
                    $readyForLetters[] = $ds->customer_id;
                }
                $detail['next_action_date'] = $lettersDate;
            }

            $defendantDetails[] = $detail;
        }

        $totalCorrespondence = DiwanCorrespondence::find()
            ->where(['related_module' => 'judiciary', 'related_record_id' => $judiciaryId])
            ->andWhere(['communication_type' => DiwanCorrespondence::TYPE_OUTGOING_LETTER])
            ->count();

        $respondedCorrespondence = DiwanCorrespondence::find()
            ->where(['related_module' => 'judiciary', 'related_record_id' => $judiciaryId])
            ->andWhere(['status' => DiwanCorrespondence::STATUS_RESPONDED])
            ->count();

        return [
            'total_defendants' => $totalDefendants,
            'notified' => $notified,
            'pending_notification' => $pendingNotification,
            'request_submitted' => $requestSubmitted,
            'pending_request' => $pendingRequest,
            'ready_for_letters' => $readyForLetters,
            'total_letters' => (int) $totalCorrespondence,
            'responded_letters' => (int) $respondedCorrespondence,
            'defendants' => $defendantDetails,
        ];
    }
}
