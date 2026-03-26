<?php

use yii\db\Migration;

/**
 * Phase 3.1 — vw_income_contract_summary
 *
 * يجمع المدفوعات مع بيانات العقد وحالة القضائي
 * بدل تكرار JOIN + Judiciary::find()->all() في IncomeSearch
 */
class m260326_300001_create_vw_income_contract_summary extends Migration
{
    public function safeUp()
    {
        $p = $this->db->tablePrefix;

        $this->execute("
            CREATE OR REPLACE VIEW {$p}vw_income_contract_summary AS
            SELECT
                i.id AS income_id,
                i.contract_id,
                i.amount,
                i.date,
                i.type,
                i.payment_type,
                i._by,
                i.created_by,

                c.status AS contract_status,
                c.company_id,
                c.followed_by,
                c.Date_of_sale,
                c.is_deleted AS contract_is_deleted,
                c.total_value AS contract_total_value,
                c.seller_id,

                CASE WHEN j.jud_count > 0 THEN 1 ELSE 0 END AS has_judiciary

            FROM {$p}income i
            LEFT JOIN {$p}contracts c ON c.id = i.contract_id
            LEFT JOIN (
                SELECT contract_id, COUNT(*) AS jud_count
                FROM {$p}judiciary
                WHERE is_deleted = 0 OR is_deleted IS NULL
                GROUP BY contract_id
            ) j ON j.contract_id = i.contract_id
        ");
    }

    public function safeDown()
    {
        $p = $this->db->tablePrefix;
        $this->execute("DROP VIEW IF EXISTS {$p}vw_income_contract_summary");
    }
}
