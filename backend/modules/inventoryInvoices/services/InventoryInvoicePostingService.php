<?php

namespace backend\modules\inventoryInvoices\services;

use Yii;
use backend\modules\inventoryInvoices\models\InventoryInvoices;
use backend\modules\itemsInventoryInvoices\models\ItemsInventoryInvoices;
use backend\modules\inventoryItemQuantities\models\InventoryItemQuantities;
use backend\modules\inventoryItems\models\StockMovement;

/**
 * Idempotent posting of an approved invoice to stock: update quantities, record movements, set posted_at.
 */
class InventoryInvoicePostingService
{
    /**
     * Post invoice to stock. Idempotent: if posted_at is set, returns without doing anything.
     *
     * @param int $invoiceId
     * @throws \Exception on validation or save failure
     */
    public static function post($invoiceId)
    {
        $invoice = InventoryInvoices::findOne($invoiceId);
        if (!$invoice) {
            throw new \InvalidArgumentException('Invoice not found: ' . $invoiceId);
        }

        if ($invoice->posted_at !== null && $invoice->posted_at !== '') {
            return;
        }

        if ($invoice->status !== InventoryInvoices::STATUS_APPROVED_FINAL) {
            throw new \DomainException('Invoice must be in status approved_final to post. Current: ' . $invoice->status);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $lineItems = ItemsInventoryInvoices::find()
                ->where(['inventory_invoices_id' => $invoiceId])
                ->andWhere(['is_deleted' => 0])
                ->all();

            foreach ($lineItems as $lineItem) {
                if (!$lineItem->inventory_items_id || !$lineItem->number) {
                    continue;
                }

                self::updateItemQuantity($invoice, $lineItem, 'add');
                StockMovement::record($lineItem->inventory_items_id, StockMovement::TYPE_IN, $lineItem->number, [
                    'reference_type' => 'invoice',
                    'reference_id'   => $invoice->id,
                    'unit_cost'      => $lineItem->single_price,
                    'supplier_id'    => $invoice->suppliers_id,
                    'company_id'     => $invoice->company_id,
                ]);
            }

            $invoice->posted_at = date('Y-m-d H:i:s');
            if (!$invoice->save(false)) {
                throw new \Exception('Failed to save invoice posted_at');
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Update stock quantity for one line item (add).
     */
    private static function updateItemQuantity(InventoryInvoices $invoice, ItemsInventoryInvoices $lineItem, $operation)
    {
        if (!$lineItem->inventory_items_id || !$lineItem->number) {
            return;
        }

        $qtyRecord = InventoryItemQuantities::find()
            ->where(['item_id' => $lineItem->inventory_items_id, 'is_deleted' => 0])
            ->andFilterWhere(['company_id' => $invoice->company_id])
            ->one();

        if ($operation === 'add') {
            if ($qtyRecord) {
                $qtyRecord->quantity += $lineItem->number;
                $qtyRecord->save(false);
            } else {
                $qtyRecord = new InventoryItemQuantities();
                $qtyRecord->item_id       = $lineItem->inventory_items_id;
                $qtyRecord->quantity     = $lineItem->number;
                $qtyRecord->company_id   = $invoice->company_id ?? 0;
                $qtyRecord->suppliers_id = $invoice->suppliers_id ?: 0;
                $qtyRecord->locations_id  = 0;
                $qtyRecord->save(false);
            }
        }
    }
}
