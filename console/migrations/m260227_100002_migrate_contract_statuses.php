<?php

use yii\db\Migration;

/**
 * Data migration:
 * 1. Insert free_discount adjustments for finished contracts with remaining balance
 * 2. Convert pending/refused → active
 * 3. Recalculate all contract statuses
 */
class m260227_100002_migrate_contract_statuses extends Migration
{
    public function safeUp()
    {
        $db = $this->db;

        // --- Step 1: Handle finished contracts with remaining balance ---
        $finishedContracts = $db->createCommand(
            "SELECT id, total_value FROM {{%contracts}} WHERE status = 'finished' AND (is_deleted = 0 OR is_deleted IS NULL)"
        )->queryAll();

        $insertCount = 0;
        foreach ($finishedContracts as $c) {
            $contractId = (int)$c['id'];
            $totalValue = (float)$c['total_value'];

            $expenses = (float)$db->createCommand(
                "SELECT COALESCE(SUM(amount), 0) FROM {{%expenses}} WHERE contract_id = :cid AND (is_deleted = 0 OR is_deleted IS NULL)",
                [':cid' => $contractId]
            )->queryScalar();

            $lawyerCosts = (float)$db->createCommand(
                "SELECT COALESCE(SUM(lawyer_cost), 0) FROM {{%judiciary}} WHERE contract_id = :cid AND (is_deleted = 0 OR is_deleted IS NULL)",
                [':cid' => $contractId]
            )->queryScalar();

            $paid = (float)$db->createCommand(
                "SELECT COALESCE(SUM(amount), 0) FROM {{%income}} WHERE contract_id = :cid",
                [':cid' => $contractId]
            )->queryScalar();

            $totalDebt = $totalValue + $expenses + $lawyerCosts;
            $remaining = $totalDebt - $paid;

            if ($remaining > 0.01) {
                $this->insert('{{%contract_adjustments}}', [
                    'contract_id' => $contractId,
                    'type'        => 'free_discount',
                    'amount'      => round($remaining, 2),
                    'reason'      => 'خصم مجاني تلقائي — العقد كان مسجلاً كمنتهي مع وجود رصيد متبقي',
                    'approved_by' => null,
                    'created_by'  => null,
                    'is_deleted'  => 0,
                ]);
                $insertCount++;
            }
        }
        echo "    > Inserted {$insertCount} free_discount adjustments for finished contracts with remaining balance.\n";

        // --- Step 2: Convert pending/refused to active ---
        $affected = $db->createCommand(
            "UPDATE {{%contracts}} SET status = 'active' WHERE status IN ('pending', 'refused')"
        )->execute();
        echo "    > Converted {$affected} contracts from pending/refused to active.\n";

        // --- Step 3: Recalculate all non-canceled contract statuses ---
        $contractIds = $db->createCommand(
            "SELECT id FROM {{%contracts}} WHERE status <> 'canceled' AND (is_deleted = 0 OR is_deleted IS NULL)"
        )->queryColumn();

        $updated = 0;
        foreach ($contractIds as $cid) {
            $cid = (int)$cid;

            $hasJudiciary = (bool)$db->createCommand(
                "SELECT EXISTS(SELECT 1 FROM {{%judiciary}} WHERE contract_id = :cid AND (is_deleted = 0 OR is_deleted IS NULL))",
                [':cid' => $cid]
            )->queryScalar();

            if ($hasJudiciary) {
                $newStatus = 'judiciary';
            } else {
                $hasSettlement = (bool)$db->createCommand(
                    "SELECT EXISTS(SELECT 1 FROM {{%loan_scheduling}} WHERE contract_id = :cid AND (is_deleted = 0 OR is_deleted IS NULL))",
                    [':cid' => $cid]
                )->queryScalar();

                if ($hasSettlement) {
                    $newStatus = 'settlement';
                } else {
                    $isLegal = (int)$db->createCommand(
                        "SELECT COALESCE(is_legal_department, 0) FROM {{%contracts}} WHERE id = :cid",
                        [':cid' => $cid]
                    )->queryScalar();

                    if ($isLegal) {
                        $newStatus = 'legal_department';
                    } else {
                        $totalValue = (float)$db->createCommand(
                            "SELECT COALESCE(total_value, 0) FROM {{%contracts}} WHERE id = :cid",
                            [':cid' => $cid]
                        )->queryScalar();

                        $expenses = (float)$db->createCommand(
                            "SELECT COALESCE(SUM(amount), 0) FROM {{%expenses}} WHERE contract_id = :cid AND (is_deleted = 0 OR is_deleted IS NULL)",
                            [':cid' => $cid]
                        )->queryScalar();

                        $lawyerCosts = (float)$db->createCommand(
                            "SELECT COALESCE(SUM(lawyer_cost), 0) FROM {{%judiciary}} WHERE contract_id = :cid AND (is_deleted = 0 OR is_deleted IS NULL)",
                            [':cid' => $cid]
                        )->queryScalar();

                        $adjustments = (float)$db->createCommand(
                            "SELECT COALESCE(SUM(amount), 0) FROM {{%contract_adjustments}} WHERE contract_id = :cid AND is_deleted = 0",
                            [':cid' => $cid]
                        )->queryScalar();

                        $paid = (float)$db->createCommand(
                            "SELECT COALESCE(SUM(amount), 0) FROM {{%income}} WHERE contract_id = :cid",
                            [':cid' => $cid]
                        )->queryScalar();

                        $remaining = ($totalValue + $expenses + $lawyerCosts - $adjustments) - $paid;
                        $newStatus = ($remaining <= 0.01) ? 'finished' : 'active';
                    }
                }
            }

            $db->createCommand(
                "UPDATE {{%contracts}} SET status = :status WHERE id = :cid",
                [':status' => $newStatus, ':cid' => $cid]
            )->execute();
            $updated++;
        }
        echo "    > Recalculated status for {$updated} contracts.\n";
    }

    public function safeDown()
    {
        echo "    > This migration cannot be safely reverted (data migration).\n";
        return true;
    }
}
