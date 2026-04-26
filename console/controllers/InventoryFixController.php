<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;
use yii\helpers\Console;

/**
 * Inventory data fix utilities.
 *
 * Usage:
 *   php yii inventory-fix/recalculate-quantities          # dry-run report
 *   php yii inventory-fix/recalculate-quantities --apply  # apply fix
 *
 * Recalculation formula per item:
 *   correct_quantity = SUM(items_inventory_invoices.number where is_deleted=0)
 *                    - COUNT(contract_inventory_item where is_deleted=0)
 *
 * The script then rewrites os_inventory_item_quantities to reflect the
 * correct value, preserving location_id/supplier_id/company_id from
 * an existing record (or the latest purchase invoice).
 */
class InventoryFixController extends Controller
{
    public $apply = false;

    public function options($actionID)
    {
        return ['apply'];
    }

    public function optionAliases()
    {
        return ['a' => 'apply'];
    }

    /**
     * Recalculate inventory_item_quantities from purchases - sales.
     */
    public function actionRecalculateQuantities()
    {
        $db = Yii::$app->db;

        $this->stdout("\n=== Inventory Quantities Recalculation ===\n", Console::BOLD);
        $this->stdout("Mode: " . ($this->apply ? 'APPLY' : 'DRY-RUN') . "\n\n", $this->apply ? Console::FG_RED : Console::FG_YELLOW);

        // 1. fetch all items
        $items = (new Query())
            ->select(['id', 'item_name'])
            ->from('os_inventory_items')
            ->where(['is_deleted' => 0])
            ->all($db);

        if (empty($items)) {
            $this->stdout("No items found.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $this->stdout(sprintf("Items to process: %d\n\n", count($items)));

        $changedCount = 0;
        $totalDelta = 0;

        $this->stdout(str_pad('ID', 6) . str_pad('Item', 32) . str_pad('Purchased', 12) . str_pad('Sold', 8) . str_pad('Correct', 10) . str_pad('Current', 10) . "Action\n", Console::FG_CYAN);
        $this->stdout(str_repeat('-', 95) . "\n");

        $tx = $this->apply ? $db->beginTransaction() : null;

        try {
            foreach ($items as $item) {
                $itemId = (int) $item['id'];

                // purchased = SUM(line_items.number) where is_deleted=0
                $purchased = (int) (new Query())
                    ->from('os_items_inventory_invoices')
                    ->where(['inventory_items_id' => $itemId, 'is_deleted' => 0])
                    ->sum('number', $db);

                // sold = COUNT(contract_inventory_item joined with non-canceled, non-deleted contracts)
                // Table os_contract_inventory_item itself is hard-deleted (no is_deleted column).
                $sold = (int) (new Query())
                    ->from(['ci' => 'os_contract_inventory_item'])
                    ->leftJoin(['c' => 'os_contracts'], 'c.id = ci.contract_id')
                    ->where(['ci.item_id' => $itemId])
                    ->andWhere(['or',
                        ['c.is_deleted' => 0],
                        ['c.is_deleted' => null], // legacy rows with no is_deleted
                    ])
                    ->andWhere(['or',
                        ['<>', 'c.status', 'canceled'],
                        ['c.status' => null],
                    ])
                    ->count('*', $db);

                $correct = max(0, $purchased - $sold);

                // current = SUM(quantity) from inventory_item_quantities
                $current = (int) (new Query())
                    ->from('os_inventory_item_quantities')
                    ->where(['item_id' => $itemId, 'is_deleted' => 0])
                    ->sum('quantity', $db);

                $action = '—';
                if ($correct !== $current) {
                    $delta = $correct - $current;
                    $totalDelta += abs($delta);
                    $changedCount++;
                    $action = sprintf('%s%d', $delta > 0 ? '+' : '', $delta);

                    if ($this->apply) {
                        $this->rewriteItemQuantity($db, $itemId, $correct);
                    }
                }

                $name = mb_substr($item['item_name'] ?? '', 0, 28);
                $this->stdout(
                    str_pad((string) $itemId, 6)
                    . str_pad($name, 32)
                    . str_pad((string) $purchased, 12)
                    . str_pad((string) $sold, 8)
                    . str_pad((string) $correct, 10)
                    . str_pad((string) $current, 10)
                    . $action
                    . "\n",
                    $action !== '—' ? Console::FG_YELLOW : null
                );
            }

            if ($tx) {
                $tx->commit();
            }
        } catch (\Throwable $e) {
            if ($tx) $tx->rollBack();
            $this->stderr("ERROR: " . $e->getMessage() . "\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("\n" . str_repeat('=', 60) . "\n", Console::BOLD);
        $this->stdout(sprintf("Items needing correction: %d\n", $changedCount), Console::FG_CYAN);
        $this->stdout(sprintf("Total absolute delta:     %d units\n", $totalDelta), Console::FG_CYAN);

        if ($this->apply) {
            $this->stdout("Changes applied successfully.\n", Console::FG_GREEN);
        } else {
            $this->stdout("DRY-RUN complete. Run with --apply to commit.\n", Console::FG_YELLOW);
        }

        return ExitCode::OK;
    }

    /**
     * Rewrite quantity records for a single item to match the correct total.
     */
    private function rewriteItemQuantity($db, int $itemId, int $correctQty): void
    {
        $existing = (new Query())
            ->from('os_inventory_item_quantities')
            ->where(['item_id' => $itemId, 'is_deleted' => 0])
            ->orderBy(['id' => SORT_ASC])
            ->all($db);

        if ($correctQty <= 0) {
            // soft-delete all quantity records
            $db->createCommand()->update(
                'os_inventory_item_quantities',
                ['is_deleted' => 1],
                ['item_id' => $itemId, 'is_deleted' => 0]
            )->execute();
            return;
        }

        if (!empty($existing)) {
            $first = $existing[0];
            $db->createCommand()->update(
                'os_inventory_item_quantities',
                ['quantity' => $correctQty],
                ['id' => $first['id']]
            )->execute();

            // soft-delete the rest
            $otherIds = array_slice(array_column($existing, 'id'), 1);
            if (!empty($otherIds)) {
                $db->createCommand()->update(
                    'os_inventory_item_quantities',
                    ['is_deleted' => 1],
                    ['id' => $otherIds]
                )->execute();
            }
            return;
        }

        // no existing record → derive supplier/location from latest purchase invoice
        $latestInvoiceLine = (new Query())
            ->select(['ii.suppliers_id', 'ii.branch_id', 'ii.company_id'])
            ->from(['li' => 'os_items_inventory_invoices'])
            ->leftJoin(['ii' => 'os_inventory_invoices'], 'ii.id = li.inventory_invoices_id')
            ->where(['li.inventory_items_id' => $itemId, 'li.is_deleted' => 0])
            ->orderBy(['li.id' => SORT_DESC])
            ->one($db);

        $companyId  = (int) ($latestInvoiceLine['company_id']  ?? 0);
        $supplierId = (int) ($latestInvoiceLine['suppliers_id'] ?? 0);
        $branchId   = (int) ($latestInvoiceLine['branch_id']   ?? 0);

        $db->createCommand()->insert('os_inventory_item_quantities', [
            'item_id'      => $itemId,
            'quantity'     => $correctQty,
            'company_id'   => $companyId,
            'suppliers_id' => $supplierId,
            'locations_id' => $branchId,
            'is_deleted'   => 0,
            'created_at'   => time(),
            'created_by'   => 1,
        ])->execute();
    }
}
