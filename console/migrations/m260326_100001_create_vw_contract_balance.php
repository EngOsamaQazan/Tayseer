<?php

use yii\db\Migration;

/**
 * Phase 1.1 — vw_contract_balance
 *
 * لبنة أساسية مشتركة: تحسب لكل عقد الأرصدة المالية
 * (المدفوع، المصاريف، أتعاب المحامي، التسويات، المتبقي).
 * يُستخدم في أكثر من 10 شاشات بدل تكرار SUM/subquery في كل واحدة.
 */
class m260326_100001_create_vw_contract_balance extends Migration
{
    public function safeUp()
    {
        $p = $this->db->tablePrefix;

        $this->execute("
            CREATE OR REPLACE VIEW {$p}vw_contract_balance AS
            SELECT
                c.id                        AS contract_id,
                c.total_value,
                c.status,
                c.is_deleted,
                c.seller_id,
                c.followed_by,
                c.Date_of_sale,
                c.monthly_installment_value,
                c.first_installment_date,
                c.first_installment_value,
                c.company_id,
                c.is_can_not_contact,

                COALESCE(paid.total_paid, 0)         AS total_paid,
                COALESCE(exp.total_expenses, 0)      AS total_expenses,
                COALESCE(jud.total_lawyer_cost, 0)   AS total_lawyer_cost,
                COALESCE(jud.case_count, 0)          AS judiciary_case_count,
                COALESCE(adj.total_adjustments, 0)   AS total_adjustments,

                GREATEST(0,
                    c.total_value
                    + COALESCE(exp.total_expenses, 0)
                    + COALESCE(jud.total_lawyer_cost, 0)
                    - COALESCE(adj.total_adjustments, 0)
                    - COALESCE(paid.total_paid, 0)
                ) AS remaining_balance,

                COALESCE(ls.effective_installment, c.monthly_installment_value) AS effective_installment,
                COALESCE(ls.effective_first_date, c.first_installment_date)     AS effective_first_date,
                ls.id AS active_loan_scheduling_id

            FROM {$p}contracts c

            LEFT JOIN (
                SELECT contract_id, SUM(amount) AS total_paid
                FROM {$p}income
                GROUP BY contract_id
            ) paid ON paid.contract_id = c.id

            LEFT JOIN (
                SELECT contract_id, SUM(amount) AS total_expenses
                FROM {$p}expenses
                WHERE is_deleted = 0 OR is_deleted IS NULL
                GROUP BY contract_id
            ) exp ON exp.contract_id = c.id

            LEFT JOIN (
                SELECT contract_id,
                       SUM(lawyer_cost)  AS total_lawyer_cost,
                       COUNT(*)          AS case_count
                FROM {$p}judiciary
                WHERE is_deleted = 0 OR is_deleted IS NULL
                GROUP BY contract_id
            ) jud ON jud.contract_id = c.id

            LEFT JOIN (
                SELECT contract_id, SUM(amount) AS total_adjustments
                FROM {$p}contract_adjustments
                WHERE is_deleted = 0
                GROUP BY contract_id
            ) adj ON adj.contract_id = c.id

            LEFT JOIN (
                SELECT ls1.contract_id,
                       ls1.monthly_installment AS effective_installment,
                       ls1.first_installment_date AS effective_first_date,
                       ls1.id
                FROM {$p}loan_scheduling ls1
                WHERE ls1.is_deleted = 0
                  AND ls1.id = (
                      SELECT MAX(ls2.id)
                      FROM {$p}loan_scheduling ls2
                      WHERE ls2.contract_id = ls1.contract_id
                        AND ls2.is_deleted = 0
                  )
            ) ls ON ls.contract_id = c.id
        ");
    }

    public function safeDown()
    {
        $p = $this->db->tablePrefix;
        $this->execute("DROP VIEW IF EXISTS {$p}vw_contract_balance");
    }
}
