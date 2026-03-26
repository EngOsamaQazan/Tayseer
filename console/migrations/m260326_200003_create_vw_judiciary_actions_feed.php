<?php

use yii\db\Migration;

/**
 * Phase 2.3 — vw_judiciary_actions_feed
 *
 * يجمع إجراءات أطراف القضايا مع بيانات القضية والمحكمة والمحامي والعميل
 * بدل 7 JOINs في JudiciaryCustomersActionsSearch.
 */
class m260326_200003_create_vw_judiciary_actions_feed extends Migration
{
    public function safeUp()
    {
        $p = $this->db->tablePrefix;

        $this->execute("
            CREATE OR REPLACE VIEW {$p}vw_judiciary_actions_feed AS
            SELECT
                jca.id,
                jca.judiciary_id,
                jca.customers_id,
                jca.judiciary_actions_id,
                jca.action_date,
                jca.note,
                jca.is_deleted AS action_is_deleted,
                jca.created_at,
                jca.updated_at,
                jca.created_by,
                jca.last_update_by,
                jca.request_status,
                jca.request_target,
                jca.correspondence_id,
                jca.parent_id,

                j.contract_id,
                j.court_id,
                j.lawyer_id,
                j.judiciary_number,
                j.year,

                ja.name AS action_name,

                cust.name AS customer_name,
                cust.primary_phone_number AS customer_phone,
                cust.id_number AS customer_id_number,

                ct.name AS court_name,
                lw.name AS lawyer_name,

                co.status AS contract_status,
                co.total_value AS contract_total_value

            FROM {$p}judiciary_customers_actions jca
            INNER JOIN {$p}judiciary j ON j.id = jca.judiciary_id
            INNER JOIN {$p}customers cust ON cust.id = jca.customers_id
            INNER JOIN {$p}judiciary_actions ja ON ja.id = jca.judiciary_actions_id
            LEFT JOIN {$p}court ct ON ct.id = j.court_id
            LEFT JOIN {$p}lawyers lw ON lw.id = j.lawyer_id
            LEFT JOIN {$p}contracts co ON co.id = j.contract_id
        ");
    }

    public function safeDown()
    {
        $p = $this->db->tablePrefix;
        $this->execute("DROP VIEW IF EXISTS {$p}vw_judiciary_actions_feed");
    }
}
