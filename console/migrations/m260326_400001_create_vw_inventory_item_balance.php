<?php

use yii\db\Migration;

/**
 * Phase 4.1 — vw_inventory_item_balance
 *
 * يحسب رصيد كل صنف (الكمية الواردة - المباعة)
 * بدل correlated subqueries في itemQuery()
 */
class m260326_400001_create_vw_inventory_item_balance extends Migration
{
    public function safeUp()
    {
        $p = $this->db->tablePrefix;

        $this->execute("
            CREATE OR REPLACE VIEW {$p}vw_inventory_item_balance AS
            SELECT
                i.id AS item_id,
                i.item_name,
                i.item_barcode,
                i.serial_number,
                i.category,
                i.status,
                i.supplier_id,
                i.company_id,
                i.is_deleted,
                i.created_at,

                COALESCE(q.total_in, 0) AS total_quantity_in,
                COALESCE(s.total_out, 0) AS total_quantity_out,
                COALESCE(q.total_in, 0) - COALESCE(s.total_out, 0) AS remaining_amount

            FROM {$p}inventory_items i
            LEFT JOIN (
                SELECT item_id, COUNT(item_id) AS total_in
                FROM {$p}inventory_item_quantities
                GROUP BY item_id
            ) q ON q.item_id = i.id
            LEFT JOIN (
                SELECT item_id, COUNT(item_id) AS total_out
                FROM {$p}contract_inventory_item
                GROUP BY item_id
            ) s ON s.item_id = i.id
        ");
    }

    public function safeDown()
    {
        $p = $this->db->tablePrefix;
        $this->execute("DROP VIEW IF EXISTS {$p}vw_inventory_item_balance");
    }
}
