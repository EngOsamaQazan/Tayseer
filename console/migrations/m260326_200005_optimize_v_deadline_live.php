<?php

use yii\db\Migration;

/**
 * Phase 2.5 — تحسين v_deadline_live
 *
 * استبدال الحساب المكرر للرصيد المتبقي بـ JOIN مباشر على vw_contract_balance
 * الذي تم إنشاؤه في Phase 1.
 */
class m260326_200005_optimize_v_deadline_live extends Migration
{
    public function safeUp()
    {
        $p = $this->db->tablePrefix;

        $this->execute("
            CREATE OR REPLACE VIEW {$p}v_deadline_live AS
            SELECT
                d.id,
                d.judiciary_id,
                d.customer_id,
                d.deadline_type,
                d.day_type,
                d.label,
                d.start_date,
                d.deadline_date,
                d.related_communication_id,
                d.related_customer_action_id,
                d.notes,
                d.is_deleted,
                d.created_at,
                d.updated_at,
                d.created_by,

                CASE
                    WHEN j.case_status IN ('closed','archived')
                        THEN 'completed'

                    WHEN j.contract_id IS NOT NULL
                         AND cb.status = 'judiciary'
                         AND cb.remaining_balance <= 0
                        THEN 'completed'

                    WHEN d.related_customer_action_id IS NOT NULL
                         AND ra.is_deleted = 1
                        THEN 'completed'

                    WHEN d.deadline_type IN ('registration_3wd','registration')
                         AND EXISTS (
                             SELECT 1 FROM {$p}judiciary_customers_actions s
                             WHERE s.judiciary_id = d.judiciary_id
                               AND (s.is_deleted = 0 OR s.is_deleted IS NULL)
                               AND s.action_date > d.start_date
                         )
                        THEN 'completed'

                    WHEN d.deadline_type IN ('request_decision_3wd','request_decision')
                         AND d.related_customer_action_id IS NOT NULL
                         AND ra.request_status IN ('approved','rejected')
                        THEN 'completed'

                    WHEN d.deadline_type IN ('request_decision_3wd','request_decision')
                         AND d.related_customer_action_id IS NOT NULL
                         AND EXISTS (
                             SELECT 1 FROM {$p}judiciary_customers_actions ch
                             WHERE ch.parent_id = d.related_customer_action_id
                               AND (ch.is_deleted = 0 OR ch.is_deleted IS NULL)
                         )
                        THEN 'completed'

                    WHEN d.deadline_type IN ('correspondence_10wd','correspondence')
                         AND EXISTS (
                             SELECT 1 FROM {$p}judiciary_customers_actions s2
                             WHERE s2.judiciary_id = d.judiciary_id
                               AND (s2.is_deleted = 0 OR s2.is_deleted IS NULL)
                               AND s2.action_date > d.deadline_date
                         )
                        THEN 'completed'

                    WHEN d.deadline_date < CURDATE()
                        THEN 'expired'

                    WHEN d.deadline_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                        THEN 'approaching'

                    ELSE 'pending'
                END AS live_status

            FROM {$p}judiciary_deadlines d
            LEFT JOIN {$p}judiciary j ON j.id = d.judiciary_id
            LEFT JOIN {$p}judiciary_customers_actions ra
                   ON ra.id = d.related_customer_action_id
            LEFT JOIN {$p}vw_contract_balance cb ON cb.contract_id = j.contract_id

            WHERE d.is_deleted = 0
        ");
    }

    public function safeDown()
    {
        $p = $this->db->tablePrefix;
        $this->execute("DROP VIEW IF EXISTS {$p}v_deadline_live");
    }
}
