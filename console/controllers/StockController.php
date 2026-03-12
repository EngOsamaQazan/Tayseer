<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class StockController extends Controller
{
    /**
     * ملء حركات المخزون التاريخية من البيانات الموجودة
     * يُنشئ حركات IN للأرقام التسلسلية و OUT للمبيعات
     */
    public function actionBackfill()
    {
        $db = Yii::$app->db;
        $prefix = $db->tablePrefix;

        $existing = (int) $db->createCommand("SELECT COUNT(*) FROM {$prefix}stock_movements")->queryScalar();
        if ($existing > 0) {
            $this->stdout("يوجد {$existing} حركة مخزون مسبقاً. هل تريد المتابعة وإضافة الحركات الناقصة؟ (y/n): ");
            $answer = trim(fgets(STDIN));
            if (strtolower($answer) !== 'y') {
                $this->stdout("تم الإلغاء.\n");
                return ExitCode::OK;
            }
        }

        $this->stdout("═══ ملء حركات المخزون التاريخية ═══\n\n");

        $inCount = 0;
        $outCount = 0;

        $serials = $db->createCommand(
            "SELECT id, item_id, serial_number, supplier_id, company_id, created_at, status, contract_id, sold_at
             FROM {$prefix}inventory_serial_numbers
             WHERE is_deleted = 0"
        )->queryAll();

        $this->stdout("وُجد " . count($serials) . " رقم تسلسلي\n");

        foreach ($serials as $s) {
            $alreadyIn = (int) $db->createCommand(
                "SELECT COUNT(*) FROM {$prefix}stock_movements
                 WHERE reference_type = 'serial_create' AND reference_id = :id",
                [':id' => $s['id']]
            )->queryScalar();

            if ($alreadyIn === 0) {
                $db->createCommand()->insert("{$prefix}stock_movements", [
                    'item_id'        => $s['item_id'],
                    'movement_type'  => 'IN',
                    'quantity'       => 1,
                    'reference_type' => 'serial_create',
                    'reference_id'   => $s['id'],
                    'supplier_id'    => $s['supplier_id'] ?: null,
                    'company_id'     => $s['company_id'] ?: null,
                    'unit_cost'      => null,
                    'notes'          => 'backfill: إضافة سيريال ' . $s['serial_number'],
                    'created_by'     => 1,
                    'created_at'     => $s['created_at'] ?: time(),
                ])->execute();
                $inCount++;
            }

            if ($s['status'] === 'sold' && $s['contract_id']) {
                $alreadyOut = (int) $db->createCommand(
                    "SELECT COUNT(*) FROM {$prefix}stock_movements
                     WHERE reference_type = 'contract_sale' AND reference_id = :cid
                     AND item_id = :iid",
                    [':cid' => $s['contract_id'], ':iid' => $s['item_id']]
                )->queryScalar();

                if ($alreadyOut === 0) {
                    $db->createCommand()->insert("{$prefix}stock_movements", [
                        'item_id'        => $s['item_id'],
                        'movement_type'  => 'OUT',
                        'quantity'       => 1,
                        'reference_type' => 'contract_sale',
                        'reference_id'   => $s['contract_id'],
                        'company_id'     => $s['company_id'] ?: null,
                        'notes'          => 'backfill: بيع سيريال ' . $s['serial_number'] . ' عبر عقد #' . $s['contract_id'],
                        'created_by'     => 1,
                        'created_at'     => $s['sold_at'] ?: time(),
                    ])->execute();
                    $outCount++;
                }
            }
        }

        $this->stdout("\n═══ النتائج ═══\n");
        $this->stdout("  حركات إدخال (IN): {$inCount}\n");
        $this->stdout("  حركات إخراج (OUT): {$outCount}\n");
        $this->stdout("  الإجمالي: " . ($inCount + $outCount) . "\n\n");

        return ExitCode::OK;
    }
}
