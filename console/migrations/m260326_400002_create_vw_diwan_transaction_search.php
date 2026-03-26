<?php

use yii\db\Migration;

/**
 * Phase 4.2 — vw_diwan_transaction_search
 *
 * يجمع المعاملات مع أسماء الموظفين وعدد العقود
 * بدل تكرار JOINs + with() في كل عملية بحث أو تقرير
 */
class m260326_400002_create_vw_diwan_transaction_search extends Migration
{
    public function safeUp()
    {
        $p = $this->db->tablePrefix;

        $this->execute("
            CREATE OR REPLACE VIEW {$p}vw_diwan_transaction_search AS
            SELECT
                t.id,
                t.transaction_type,
                t.receipt_number,
                t.transaction_date,
                t.notes,
                t.from_employee_id,
                t.to_employee_id,
                t.created_by,
                t.created_at,

                fe.username AS from_employee_name,
                te.username AS to_employee_name,
                cb.username AS created_by_name,

                COALESCE(dc.contract_count, 0) AS contract_count,
                dc.contract_numbers

            FROM {$p}diwan_transactions t
            LEFT JOIN {$p}user fe ON fe.id = t.from_employee_id
            LEFT JOIN {$p}user te ON te.id = t.to_employee_id
            LEFT JOIN {$p}user cb ON cb.id = t.created_by
            LEFT JOIN (
                SELECT
                    transaction_id,
                    COUNT(*) AS contract_count,
                    GROUP_CONCAT(contract_number ORDER BY id SEPARATOR ', ') AS contract_numbers
                FROM {$p}diwan_transaction_details
                GROUP BY transaction_id
            ) dc ON dc.transaction_id = t.id
        ");
    }

    public function safeDown()
    {
        $p = $this->db->tablePrefix;
        $this->execute("DROP VIEW IF EXISTS {$p}vw_diwan_transaction_search");
    }
}
