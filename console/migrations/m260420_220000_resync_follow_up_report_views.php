<?php

use yii\db\Migration;

/**
 * Re-syncs os_follow_up_report and os_follow_up_no_contact views.
 *
 * Guard migration: even though m260402_120000 is recorded as applied on all
 * servers, at least one production DB (namaa_erp) had its views silently
 * replaced by a stale version (missing never_followed, effective_installment,
 * last_follow_up columns) — likely via a DB restore or legacy script.
 *
 * This migration re-runs the CREATE OR REPLACE VIEW statements so deploying
 * `php yii migrate` on any environment brings the views back in sync.
 * Fully idempotent — safe to run as many times as needed.
 */
class m260420_220000_resync_follow_up_report_views extends Migration
{
    public function safeUp()
    {
        $this->execute("
CREATE OR REPLACE VIEW os_follow_up_report AS
SELECT
    c.*,
    f.date_time      AS last_follow_up,
    f.promise_to_pay_at,
    f.reminder,
    IFNULL(payments.total_paid, 0) AS total_paid,
    COALESCE(ls.monthly_installment, c.monthly_installment_value) AS effective_installment,
    LEAST(
        GREATEST(0,
            PERIOD_DIFF(DATE_FORMAT(CURDATE(),'%Y%m'),
                DATE_FORMAT(COALESCE(ls.first_installment_date, c.first_installment_date),'%Y%m'))
            + CASE WHEN DAY(CURDATE()) >= DAY(COALESCE(ls.first_installment_date, c.first_installment_date))
                   THEN 1 ELSE 0 END
        ),
        CEIL(
            GREATEST(0,
                c.total_value
                + IFNULL(exp_sum.total_expenses, 0)
                + IFNULL(jud.total_lawyer, 0)
                - IFNULL(adj.total_adjustments, 0)
                - IFNULL(payments.total_paid, 0)
            ) / GREATEST(COALESCE(ls.monthly_installment, c.monthly_installment_value), 1)
        )
    ) AS due_installments,
    LEAST(
        CASE
            WHEN jud.jud_id IS NOT NULL AND ls.id IS NULL THEN
                GREATEST(0,
                    c.total_value
                    + IFNULL(exp_sum.total_expenses, 0)
                    + IFNULL(jud.total_lawyer, 0)
                    - IFNULL(adj.total_adjustments, 0)
                    - IFNULL(payments.total_paid, 0)
                )
            ELSE
                GREATEST(0,
                    (GREATEST(0,
                        PERIOD_DIFF(DATE_FORMAT(CURDATE(),'%Y%m'),
                            DATE_FORMAT(COALESCE(ls.first_installment_date, c.first_installment_date),'%Y%m'))
                        + CASE WHEN DAY(CURDATE()) >= DAY(COALESCE(ls.first_installment_date, c.first_installment_date))
                               THEN 1 ELSE 0 END
                    ) * COALESCE(ls.monthly_installment, c.monthly_installment_value))
                    - IFNULL(payments.total_paid, 0)
                )
        END,
        GREATEST(0,
            c.total_value
            + IFNULL(exp_sum.total_expenses, 0)
            + IFNULL(jud.total_lawyer, 0)
            - IFNULL(adj.total_adjustments, 0)
            - IFNULL(payments.total_paid, 0)
        )
    ) AS due_amount,
    CASE WHEN f.id IS NULL THEN 1 ELSE 0 END AS never_followed
FROM os_contracts c
LEFT JOIN os_follow_up f ON f.contract_id = c.id
    AND f.id = (SELECT MAX(id) FROM os_follow_up WHERE contract_id = c.id)
LEFT JOIN os_loan_scheduling ls ON ls.contract_id = c.id
    AND ls.is_deleted = 0
    AND ls.id = (SELECT MAX(id) FROM os_loan_scheduling WHERE contract_id = c.id AND is_deleted = 0)
LEFT JOIN (
    SELECT contract_id, SUM(amount) AS total_paid
    FROM os_income GROUP BY contract_id
) payments ON c.id = payments.contract_id
LEFT JOIN (
    SELECT contract_id, MAX(id) AS jud_id, SUM(lawyer_cost) AS total_lawyer
    FROM os_judiciary WHERE is_deleted = 0
    GROUP BY contract_id
) jud ON jud.contract_id = c.id
LEFT JOIN (
    SELECT contract_id, SUM(amount) AS total_expenses
    FROM os_expenses
    WHERE (is_deleted = 0 OR is_deleted IS NULL)
    GROUP BY contract_id
) exp_sum ON exp_sum.contract_id = c.id
LEFT JOIN (
    SELECT contract_id, SUM(amount) AS total_adjustments
    FROM os_contract_adjustments WHERE is_deleted = 0
    GROUP BY contract_id
) adj ON adj.contract_id = c.id
WHERE
    c.status NOT IN ('finished','canceled')
    AND NOT (
        c.status = 'judiciary'
        AND (c.total_value + IFNULL(exp_sum.total_expenses, 0) + IFNULL(jud.total_lawyer, 0)
             - IFNULL(adj.total_adjustments, 0) - IFNULL(payments.total_paid, 0)) <= 0.01
    )
    AND (
        (c.is_can_not_contact = 0 AND (
            (jud.jud_id IS NOT NULL AND ls.id IS NULL AND
                (c.total_value + IFNULL(exp_sum.total_expenses, 0) + IFNULL(jud.total_lawyer, 0)
                 - IFNULL(adj.total_adjustments, 0) - IFNULL(payments.total_paid, 0)) > 5
            )
            OR
            ((jud.jud_id IS NULL OR ls.id IS NOT NULL) AND
                ((GREATEST(0,
                    PERIOD_DIFF(DATE_FORMAT(CURDATE(),'%Y%m'),
                        DATE_FORMAT(COALESCE(ls.first_installment_date, c.first_installment_date),'%Y%m'))
                    + CASE WHEN DAY(CURDATE()) >= DAY(COALESCE(ls.first_installment_date, c.first_installment_date))
                           THEN 1 ELSE 0 END
                ) * COALESCE(ls.monthly_installment, c.monthly_installment_value))
                - IFNULL(payments.total_paid, 0)) > 5
            )
        ))
        OR
        c.is_can_not_contact = 1
    )
        ");

        $this->execute("
CREATE OR REPLACE VIEW os_follow_up_no_contact AS
SELECT
    c.*,
    f.date_time,
    f.promise_to_pay_at,
    f.reminder,
    IFNULL(payments.total_paid, 0) AS total_paid,
    COALESCE(ls.monthly_installment, c.monthly_installment_value) AS effective_installment,
    LEAST(
        GREATEST(0,
            PERIOD_DIFF(DATE_FORMAT(CURDATE(),'%Y%m'),
                DATE_FORMAT(COALESCE(ls.first_installment_date, c.first_installment_date),'%Y%m'))
            + CASE WHEN DAY(CURDATE()) >= DAY(COALESCE(ls.first_installment_date, c.first_installment_date))
                   THEN 1 ELSE 0 END
        ),
        CEIL(
            GREATEST(0,
                c.total_value
                + IFNULL(exp_sum.total_expenses, 0)
                + IFNULL(jud.total_lawyer, 0)
                - IFNULL(adj.total_adjustments, 0)
                - IFNULL(payments.total_paid, 0)
            ) / GREATEST(COALESCE(ls.monthly_installment, c.monthly_installment_value), 1)
        )
    ) AS due_installments,
    LEAST(
        CASE
            WHEN jud.jud_id IS NOT NULL AND ls.id IS NULL THEN
                GREATEST(0,
                    c.total_value
                    + IFNULL(exp_sum.total_expenses, 0)
                    + IFNULL(jud.total_lawyer, 0)
                    - IFNULL(adj.total_adjustments, 0)
                    - IFNULL(payments.total_paid, 0)
                )
            ELSE
                GREATEST(0,
                    (GREATEST(0,
                        PERIOD_DIFF(DATE_FORMAT(CURDATE(),'%Y%m'),
                            DATE_FORMAT(COALESCE(ls.first_installment_date, c.first_installment_date),'%Y%m'))
                        + CASE WHEN DAY(CURDATE()) >= DAY(COALESCE(ls.first_installment_date, c.first_installment_date))
                               THEN 1 ELSE 0 END
                    ) * COALESCE(ls.monthly_installment, c.monthly_installment_value))
                    - IFNULL(payments.total_paid, 0)
                )
        END,
        GREATEST(0,
            c.total_value
            + IFNULL(exp_sum.total_expenses, 0)
            + IFNULL(jud.total_lawyer, 0)
            - IFNULL(adj.total_adjustments, 0)
            - IFNULL(payments.total_paid, 0)
        )
    ) AS due_amount
FROM os_contracts c
LEFT JOIN os_follow_up f ON f.contract_id = c.id
    AND f.id = (SELECT MAX(id) FROM os_follow_up WHERE contract_id = c.id)
LEFT JOIN os_loan_scheduling ls ON ls.contract_id = c.id
    AND ls.is_deleted = 0
    AND ls.id = (SELECT MAX(id) FROM os_loan_scheduling WHERE contract_id = c.id AND is_deleted = 0)
LEFT JOIN (
    SELECT contract_id, SUM(amount) AS total_paid
    FROM os_income GROUP BY contract_id
) payments ON c.id = payments.contract_id
LEFT JOIN (
    SELECT contract_id, MAX(id) AS jud_id, SUM(lawyer_cost) AS total_lawyer
    FROM os_judiciary WHERE is_deleted = 0
    GROUP BY contract_id
) jud ON jud.contract_id = c.id
LEFT JOIN (
    SELECT contract_id, SUM(amount) AS total_expenses
    FROM os_expenses
    WHERE (is_deleted = 0 OR is_deleted IS NULL)
    GROUP BY contract_id
) exp_sum ON exp_sum.contract_id = c.id
LEFT JOIN (
    SELECT contract_id, SUM(amount) AS total_adjustments
    FROM os_contract_adjustments WHERE is_deleted = 0
    GROUP BY contract_id
) adj ON adj.contract_id = c.id
WHERE c.is_can_not_contact = 1
    AND NOT (
        c.status = 'judiciary'
        AND (c.total_value + IFNULL(exp_sum.total_expenses, 0) + IFNULL(jud.total_lawyer, 0)
             - IFNULL(adj.total_adjustments, 0) - IFNULL(payments.total_paid, 0)) <= 0.01
    )
        ");
    }

    public function safeDown()
    {
        return true;
    }
}
