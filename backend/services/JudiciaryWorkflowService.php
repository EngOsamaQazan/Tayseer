<?php

namespace backend\services;

use Yii;
use backend\modules\judiciary\models\Judiciary;
use backend\models\JudiciaryDefendantStage;

/**
 * Manages process stage transitions for judiciary cases.
 * Enforces allowed transitions and triggers side effects.
 */
class JudiciaryWorkflowService
{
    /**
     * Allowed forward transitions: current_stage => [allowed next stages]
     */
    private static $transitions = [
        Judiciary::STAGE_CASE_PREPARATION    => [Judiciary::STAGE_FEE_PAYMENT],
        Judiciary::STAGE_FEE_PAYMENT         => [Judiciary::STAGE_CASE_REGISTRATION],
        Judiciary::STAGE_CASE_REGISTRATION   => [Judiciary::STAGE_NOTIFICATION],
        Judiciary::STAGE_NOTIFICATION        => [Judiciary::STAGE_PROCEDURAL_REQUESTS],
        Judiciary::STAGE_PROCEDURAL_REQUESTS => [Judiciary::STAGE_CORRESPONDENCE],
        Judiciary::STAGE_CORRESPONDENCE      => [Judiciary::STAGE_FOLLOW_UP],
        Judiciary::STAGE_FOLLOW_UP           => [Judiciary::STAGE_PAYMENT_SETTLEMENT, Judiciary::STAGE_CORRESPONDENCE],
        Judiciary::STAGE_PAYMENT_SETTLEMENT  => [Judiciary::STAGE_CASE_CLOSURE],
    ];

    /**
     * Can a defendant move from current stage to the target stage?
     */
    public function canTransition(string $currentStage, string $targetStage): bool
    {
        if ($targetStage === Judiciary::STAGE_CASE_CLOSURE) {
            return true;
        }

        $allowed = self::$transitions[$currentStage] ?? [];
        return in_array($targetStage, $allowed, true);
    }

    /**
     * Advance a defendant to a new stage, with validation.
     * Returns true on success, string error message on failure.
     * @return bool|string
     */
    public function advanceDefendant(int $judiciaryId, int $customerId, string $targetStage)
    {
        $stage = JudiciaryDefendantStage::find()
            ->where(['judiciary_id' => $judiciaryId, 'customer_id' => $customerId])
            ->one();

        if (!$stage) {
            $stage = new JudiciaryDefendantStage([
                'judiciary_id' => $judiciaryId,
                'customer_id' => $customerId,
                'current_stage' => Judiciary::STAGE_CASE_PREPARATION,
            ]);
            $stage->save(false);
        }

        if (!$this->canTransition($stage->current_stage, $targetStage)) {
            return 'لا يمكن الانتقال من "' . Judiciary::getStageLabel($stage->current_stage)
                 . '" إلى "' . Judiciary::getStageLabel($targetStage) . '"';
        }

        return $stage->advanceTo($targetStage);
    }

    /**
     * Initialize defendant stages for all defendants in a case.
     */
    public function initializeDefendantStages(int $judiciaryId)
    {
        $judiciary = Judiciary::findOne($judiciaryId);
        if (!$judiciary) {
            return;
        }

        $defendants = $judiciary->getCustomersAndGuarantor()->all();
        foreach ($defendants as $defendant) {
            $exists = JudiciaryDefendantStage::find()
                ->where(['judiciary_id' => $judiciaryId, 'customer_id' => $defendant->id])
                ->exists();

            if (!$exists) {
                $stage = new JudiciaryDefendantStage([
                    'judiciary_id' => $judiciaryId,
                    'customer_id' => $defendant->id,
                    'current_stage' => Judiciary::STAGE_CASE_PREPARATION,
                    'stage_updated_at' => date('Y-m-d H:i:s'),
                ]);
                $stage->save(false);
            }
        }

        $judiciary->refreshCaseStages();
    }

    /**
     * Trigger case closure for a specific defendant.
     * Releases all seized assets and prompts for release letters.
     */
    public function triggerDefendantClosure(int $judiciaryId, int $customerId)
    {
        $this->advanceDefendant($judiciaryId, $customerId, Judiciary::STAGE_CASE_CLOSURE);

        \backend\models\JudiciarySeizedAsset::updateAll(
            ['status' => \backend\models\JudiciarySeizedAsset::STATUS_RELEASED],
            ['judiciary_id' => $judiciaryId, 'customer_id' => $customerId, 'is_deleted' => 0]
        );
    }

    private static $actionTypeToStage = [
        'case_preparation'     => Judiciary::STAGE_CASE_PREPARATION,
        'fee_registration'     => Judiciary::STAGE_CASE_REGISTRATION,
        'notification'         => Judiciary::STAGE_NOTIFICATION,
        'procedural_requests'  => Judiciary::STAGE_PROCEDURAL_REQUESTS,
        'correspondence'       => Judiciary::STAGE_CORRESPONDENCE,
        'follow_up'            => Judiciary::STAGE_FOLLOW_UP,
        'settlement_closure'   => Judiciary::STAGE_PAYMENT_SETTLEMENT,
        'appeal_cancellation'  => Judiciary::STAGE_FOLLOW_UP,
        'case_registration'    => Judiciary::STAGE_CASE_REGISTRATION,
        'salary_deduction'     => Judiciary::STAGE_CORRESPONDENCE,
        'arrest_detention'     => Judiciary::STAGE_FOLLOW_UP,
        'asset_seizure'        => Judiciary::STAGE_FOLLOW_UP,
        'court_decision'       => Judiciary::STAGE_PROCEDURAL_REQUESTS,
    ];

    /**
     * Recalculate defendant stages from existing actions for a given case.
     * Scans all non-deleted actions and advances each defendant to the
     * highest implied stage based on action_type mapping.
     */
    public function refreshStagesFromActions(int $judiciaryId)
    {
        $db = \Yii::$app->db;
        $prefix = $db->tablePrefix;

        $rows = $db->createCommand(
            "SELECT jca.customers_id, ja.action_type
             FROM {$prefix}judiciary_customers_actions jca
             INNER JOIN {$prefix}judiciary_actions ja ON ja.id = jca.judiciary_actions_id
             WHERE jca.judiciary_id = :jid
               AND (jca.is_deleted = 0 OR jca.is_deleted IS NULL)
             ORDER BY jca.action_date ASC",
            [':jid' => $judiciaryId]
        )->queryAll();

        $customerMaxStage = [];
        foreach ($rows as $r) {
            $cid = $r['customers_id'];
            $targetStage = self::$actionTypeToStage[$r['action_type']] ?? null;
            if (!$targetStage) continue;

            $targetRank = Judiciary::getStageRank($targetStage);
            if (!isset($customerMaxStage[$cid]) || $targetRank > $customerMaxStage[$cid]['rank']) {
                $customerMaxStage[$cid] = ['stage' => $targetStage, 'rank' => $targetRank];
            }
        }

        foreach ($customerMaxStage as $cid => $info) {
            $ds = JudiciaryDefendantStage::find()
                ->where(['judiciary_id' => $judiciaryId, 'customer_id' => $cid])
                ->one();

            if (!$ds) {
                $ds = new JudiciaryDefendantStage([
                    'judiciary_id' => $judiciaryId,
                    'customer_id' => $cid,
                    'current_stage' => Judiciary::STAGE_CASE_PREPARATION,
                    'stage_updated_at' => date('Y-m-d H:i:s'),
                ]);
                $ds->save(false);
            }

            $ds->advanceTo($info['stage']);
        }

        $judiciary = Judiciary::findOne($judiciaryId);
        if (!$judiciary) {
            return;
        }

        // If contract is fully paid, advance all defendants to closure
        $contract = $judiciary->contract;
        if ($contract) {
            try {
                if ($contract->isJudiciaryPaid()) {
                    $allDs = JudiciaryDefendantStage::find()
                        ->where(['judiciary_id' => $judiciaryId])
                        ->all();
                    foreach ($allDs as $ds) {
                        $ds->advanceTo(Judiciary::STAGE_CASE_CLOSURE);
                    }
                    if ($judiciary->case_status !== 'closed' && $judiciary->case_status !== 'archived') {
                        $judiciary->case_status = 'closed';
                        $judiciary->save(false, ['case_status']);
                    }
                }
            } catch (\Exception $e) {
                \Yii::warning('Failed to check contract payment: ' . $e->getMessage(), __METHOD__);
            }
        }

        $judiciary->refreshCaseStages();
    }
}
