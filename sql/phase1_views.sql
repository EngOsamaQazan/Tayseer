-- ========================================================
-- Phase 1: Performance Views (idempotent — safe to re-run)
-- ========================================================

-- 1. vw_contract_balance
CREATE OR REPLACE VIEW os_vw_contract_balance AS
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
FROM os_contracts c
LEFT JOIN (
    SELECT contract_id, SUM(amount) AS total_paid
    FROM os_income
    GROUP BY contract_id
) paid ON paid.contract_id = c.id
LEFT JOIN (
    SELECT contract_id, SUM(amount) AS total_expenses
    FROM os_expenses
    WHERE is_deleted = 0 OR is_deleted IS NULL
    GROUP BY contract_id
) exp ON exp.contract_id = c.id
LEFT JOIN (
    SELECT contract_id,
           SUM(lawyer_cost)  AS total_lawyer_cost,
           COUNT(*)          AS case_count
    FROM os_judiciary
    WHERE is_deleted = 0 OR is_deleted IS NULL
    GROUP BY contract_id
) jud ON jud.contract_id = c.id
LEFT JOIN (
    SELECT contract_id, SUM(amount) AS total_adjustments
    FROM os_contract_adjustments
    WHERE is_deleted = 0
    GROUP BY contract_id
) adj ON adj.contract_id = c.id
LEFT JOIN (
    SELECT ls1.contract_id,
           ls1.monthly_installment AS effective_installment,
           ls1.first_installment_date AS effective_first_date,
           ls1.id
    FROM os_loan_scheduling ls1
    WHERE ls1.is_deleted = 0
      AND ls1.id = (
          SELECT MAX(ls2.id)
          FROM os_loan_scheduling ls2
          WHERE ls2.contract_id = ls1.contract_id
            AND ls2.is_deleted = 0
      )
) ls ON ls.contract_id = c.id;

-- 2. vw_contract_customers_names
CREATE OR REPLACE VIEW os_vw_contract_customers_names AS
SELECT
    cc.contract_id,
    GROUP_CONCAT(
        CASE WHEN cc.customer_type = 'client' THEN c.name END
        ORDER BY c.name SEPARATOR '، '
    ) AS client_names,
    GROUP_CONCAT(
        CASE WHEN cc.customer_type = 'guarantor' THEN c.name END
        ORDER BY c.name SEPARATOR '، '
    ) AS guarantor_names,
    GROUP_CONCAT(
        c.name ORDER BY c.name SEPARATOR '، '
    ) AS all_party_names,
    MIN(
        CASE WHEN cc.customer_type = 'client' THEN c.primary_phone_number END
    ) AS client_phone
FROM os_contracts_customers cc
INNER JOIN os_customers c ON c.id = cc.customer_id
GROUP BY cc.contract_id;

-- 3. os_follow_up_report
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
    );

-- 4. os_follow_up_no_contact
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
    );
