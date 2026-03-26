<?php

use yii\db\Migration;

/**
 * Phase 2.2 — vw_judiciary_cases_overview
 *
 * يجمع بيانات القضايا مع المحكمة والمحامي والنوع وأسماء الأطراف
 * بدل تكرار JOINs المعقدة في report() و search().
 */
class m260326_200002_create_vw_judiciary_cases_overview extends Migration
{
    public function safeUp()
    {
        $p = $this->db->tablePrefix;

        $this->execute("
            CREATE OR REPLACE VIEW {$p}vw_judiciary_cases_overview AS
            SELECT
                j.id,
                j.contract_id,
                j.court_id,
                j.type_id,
                j.lawyer_id,
                j.judiciary_number,
                j.year,
                j.income_date,
                j.lawyer_cost,
                j.case_cost,
                j.case_status,
                j.furthest_stage,
                j.bottleneck_stage,
                j.is_deleted,
                j.created_at,
                j.updated_at,
                j.created_by,

                ct.name AS court_name,
                lw.name AS lawyer_name,
                jt.name AS type_name,

                co.status AS contract_status,
                co.total_value AS contract_total_value,

                cn.client_names,
                cn.guarantor_names,
                cn.all_party_names,
                cn.client_phone,

                cb.remaining_balance AS contract_remaining_balance,
                cb.total_paid AS contract_total_paid

            FROM {$p}judiciary j
            LEFT JOIN {$p}court ct ON ct.id = j.court_id
            LEFT JOIN {$p}lawyers lw ON lw.id = j.lawyer_id
            LEFT JOIN {$p}judiciary_type jt ON jt.id = j.type_id
            LEFT JOIN {$p}contracts co ON co.id = j.contract_id
            LEFT JOIN {$p}vw_contract_customers_names cn ON cn.contract_id = j.contract_id
            LEFT JOIN {$p}vw_contract_balance cb ON cb.contract_id = j.contract_id
        ");
    }

    public function safeDown()
    {
        $p = $this->db->tablePrefix;
        $this->execute("DROP VIEW IF EXISTS {$p}vw_judiciary_cases_overview");
    }
}
