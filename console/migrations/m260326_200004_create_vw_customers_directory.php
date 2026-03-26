<?php

use yii\db\Migration;

/**
 * Phase 2.4 — vw_customers_directory
 *
 * يجمع بيانات العملاء مع وظائفهم وعدد عقودهم وحالة القضائي
 * بدل تكرار JOINs المعقدة مع jobs و contracts.
 */
class m260326_200004_create_vw_customers_directory extends Migration
{
    public function safeUp()
    {
        $p = $this->db->tablePrefix;

        $this->execute("
            CREATE OR REPLACE VIEW {$p}vw_customers_directory AS
            SELECT
                c.id,
                c.name,
                c.id_number,
                c.primary_phone_number,
                c.city,
                c.status,
                c.job_title,
                c.is_deleted,
                c.created_at,
                c.updated_at,

                j.name AS job_name,
                jt.id AS job_type_id,
                jt.name AS job_type_name,

                COALESCE(cs.contract_count, 0) AS contract_count,
                COALESCE(cs.judiciary_count, 0) AS judiciary_count,
                cs.has_judiciary_balance

            FROM {$p}customers c
            LEFT JOIN {$p}jobs j ON j.id = c.job_title
            LEFT JOIN {$p}jobs_type jt ON jt.id = j.job_type
            LEFT JOIN (
                SELECT
                    cc.customer_id,
                    COUNT(DISTINCT cc.contract_id) AS contract_count,
                    COUNT(DISTINCT CASE WHEN co.status = 'judiciary' THEN cc.contract_id END) AS judiciary_count,
                    MAX(CASE
                        WHEN co.status = 'judiciary' AND cb.remaining_balance > 0
                        THEN 1 ELSE 0
                    END) AS has_judiciary_balance
                FROM {$p}contracts_customers cc
                INNER JOIN {$p}contracts co ON co.id = cc.contract_id
                LEFT JOIN {$p}vw_contract_balance cb ON cb.contract_id = co.id
                GROUP BY cc.customer_id
            ) cs ON cs.customer_id = c.id
        ");
    }

    public function safeDown()
    {
        $p = $this->db->tablePrefix;
        $this->execute("DROP VIEW IF EXISTS {$p}vw_customers_directory");
    }
}
