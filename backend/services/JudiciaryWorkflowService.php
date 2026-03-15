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
}
