-- ========================================================
-- Performance Views — Phase 1 & 2 (idempotent — safe to re-run)
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

-- ========================================================
-- Phase 2 Views
-- ========================================================

-- 5. vw_contracts_overview
CREATE OR REPLACE VIEW os_vw_contracts_overview AS
SELECT
    co.id,
    co.total_value,
    co.status,
    co.is_deleted,
    co.seller_id,
    co.followed_by,
    co.Date_of_sale,
    co.monthly_installment_value,
    co.first_installment_date,
    co.first_installment_value,
    co.company_id,
    co.notes,
    co.type,
    co.is_can_not_contact,
    co.created_at,
    co.updated_at,
    cb.total_paid,
    cb.remaining_balance,
    cb.total_expenses,
    cb.total_lawyer_cost,
    cb.judiciary_case_count,
    cb.total_adjustments,
    cb.effective_installment,
    cb.effective_first_date,
    cn.client_names,
    cn.guarantor_names,
    cn.all_party_names,
    cn.client_phone,
    u.username AS seller_name
FROM os_contracts co
LEFT JOIN os_vw_contract_balance cb ON cb.contract_id = co.id
LEFT JOIN os_vw_contract_customers_names cn ON cn.contract_id = co.id
LEFT JOIN os_user u ON u.id = co.seller_id;

-- 6. vw_judiciary_cases_overview
CREATE OR REPLACE VIEW os_vw_judiciary_cases_overview AS
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
FROM os_judiciary j
LEFT JOIN os_court ct ON ct.id = j.court_id
LEFT JOIN os_lawyers lw ON lw.id = j.lawyer_id
LEFT JOIN os_judiciary_type jt ON jt.id = j.type_id
LEFT JOIN os_contracts co ON co.id = j.contract_id
LEFT JOIN os_vw_contract_customers_names cn ON cn.contract_id = j.contract_id
LEFT JOIN os_vw_contract_balance cb ON cb.contract_id = j.contract_id;

-- 7. vw_judiciary_actions_feed
CREATE OR REPLACE VIEW os_vw_judiciary_actions_feed AS
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
FROM os_judiciary_customers_actions jca
INNER JOIN os_judiciary j ON j.id = jca.judiciary_id
INNER JOIN os_customers cust ON cust.id = jca.customers_id
INNER JOIN os_judiciary_actions ja ON ja.id = jca.judiciary_actions_id
LEFT JOIN os_court ct ON ct.id = j.court_id
LEFT JOIN os_lawyers lw ON lw.id = j.lawyer_id
LEFT JOIN os_contracts co ON co.id = j.contract_id;

-- 8. vw_customers_directory
CREATE OR REPLACE VIEW os_vw_customers_directory AS
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
FROM os_customers c
LEFT JOIN os_jobs j ON j.id = c.job_title
LEFT JOIN os_jobs_type jt ON jt.id = j.job_type
LEFT JOIN (
    SELECT
        cc.customer_id,
        COUNT(DISTINCT cc.contract_id) AS contract_count,
        COUNT(DISTINCT CASE WHEN co.status = 'judiciary' THEN cc.contract_id END) AS judiciary_count,
        MAX(CASE
            WHEN co.status = 'judiciary' AND cb.remaining_balance > 0
            THEN 1 ELSE 0
        END) AS has_judiciary_balance
    FROM os_contracts_customers cc
    INNER JOIN os_contracts co ON co.id = cc.contract_id
    LEFT JOIN os_vw_contract_balance cb ON cb.contract_id = co.id
    GROUP BY cc.customer_id
) cs ON cs.customer_id = c.id;

-- 9. v_deadline_live (optimized — uses vw_contract_balance)
CREATE OR REPLACE VIEW os_v_deadline_live AS
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
                 SELECT 1 FROM os_judiciary_customers_actions s
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
                 SELECT 1 FROM os_judiciary_customers_actions ch
                 WHERE ch.parent_id = d.related_customer_action_id
                   AND (ch.is_deleted = 0 OR ch.is_deleted IS NULL)
             )
            THEN 'completed'
        WHEN d.deadline_type IN ('correspondence_10wd','correspondence')
             AND EXISTS (
                 SELECT 1 FROM os_judiciary_customers_actions s2
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
FROM os_judiciary_deadlines d
LEFT JOIN os_judiciary j ON j.id = d.judiciary_id
LEFT JOIN os_judiciary_customers_actions ra ON ra.id = d.related_customer_action_id
LEFT JOIN os_vw_contract_balance cb ON cb.contract_id = j.contract_id
WHERE d.is_deleted = 0;

-- ========================================================
-- Phase 3 Views
-- ========================================================

-- 10. vw_income_contract_summary
CREATE OR REPLACE VIEW os_vw_income_contract_summary AS
SELECT
    i.id AS income_id, i.contract_id, i.amount, i.date, i.type,
    i.payment_type, i._by, i.created_by,
    c.status AS contract_status, c.company_id, c.followed_by,
    c.Date_of_sale, c.is_deleted AS contract_is_deleted,
    c.total_value AS contract_total_value, c.seller_id,
    CASE WHEN j.jud_count > 0 THEN 1 ELSE 0 END AS has_judiciary
FROM os_income i
LEFT JOIN os_contracts c ON c.id = i.contract_id
LEFT JOIN (
    SELECT contract_id, COUNT(*) AS jud_count
    FROM os_judiciary WHERE is_deleted = 0 OR is_deleted IS NULL
    GROUP BY contract_id
) j ON j.contract_id = i.contract_id;

-- 11. vw_hr_attendance_daily_summary
CREATE OR REPLACE VIEW os_vw_hr_attendance_daily_summary AS
SELECT
    attendance_date,
    COUNT(*) AS total_records,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present_count,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent_count,
    SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) AS leave_count,
    SUM(CASE WHEN status = 'holiday' THEN 1 ELSE 0 END) AS holiday_count,
    SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) AS half_day_count,
    SUM(CASE WHEN status = 'remote' THEN 1 ELSE 0 END) AS remote_count,
    SUM(CASE WHEN status IN ('present','late','field_duty') THEN 1 ELSE 0 END) AS working_count
FROM os_hr_attendance
WHERE is_deleted = 0
GROUP BY attendance_date;

-- 12. vw_hr_attendance_employee_monthly
CREATE OR REPLACE VIEW os_vw_hr_attendance_employee_monthly AS
SELECT
    u.id AS user_id, u.username, u.name AS employee_name,
    d.id AS department_id,
    d.title AS department_name,
    MONTH(a.attendance_date) AS att_month,
    YEAR(a.attendance_date) AS att_year,
    COUNT(a.id) AS total_records,
    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_days,
    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent_days,
    SUM(CASE WHEN a.status IN ('on_leave','leave') THEN 1 ELSE 0 END) AS leave_days,
    SUM(CASE WHEN a.status = 'half_day' THEN 1 ELSE 0 END) AS half_days,
    SUM(CASE WHEN a.status = 'remote' THEN 1 ELSE 0 END) AS remote_days,
    SUM(CASE WHEN a.status IN ('present','late','field_duty') THEN 1 ELSE 0 END) AS payroll_present_days,
    SUM(COALESCE(a.total_hours, 0)) AS total_hours,
    SUM(COALESCE(a.overtime_hours, 0)) AS total_overtime,
    SUM(COALESCE(a.late_minutes, 0)) AS total_late_minutes
FROM os_user u
INNER JOIN os_hr_attendance a ON a.user_id = u.id AND a.is_deleted = 0
LEFT JOIN os_department d ON d.id = u.department
WHERE u.blocked_at IS NULL AND u.confirmed_at IS NOT NULL
GROUP BY u.id, u.username, u.name, d.id, d.title,
         MONTH(a.attendance_date), YEAR(a.attendance_date);

-- 13. vw_hr_employee_directory
CREATE OR REPLACE VIEW os_vw_hr_employee_directory AS
SELECT
    u.id, u.username, u.name, u.email, u.mobile,
    u.employee_type, u.employee_status, u.date_of_hire,
    u.gender, u.nationality,
    u.department AS department_id, u.job_title AS job_title_id,
    u.reporting_to, u.blocked_at, u.confirmed_at, u.created_at,
    d.title AS department_name,
    mgr.name AS reporting_to_name,
    CASE
        WHEN u.blocked_at IS NOT NULL THEN 'blocked'
        WHEN u.employee_type = 'Active' THEN 'active'
        ELSE COALESCE(u.employee_type, 'unknown')
    END AS computed_status
FROM os_user u
LEFT JOIN os_department d ON d.id = u.department
LEFT JOIN os_user mgr ON mgr.id = u.reporting_to
WHERE u.confirmed_at IS NOT NULL;

-- 14. vw_payroll_employee_attendance
CREATE OR REPLACE VIEW os_vw_payroll_employee_attendance AS
SELECT
    a.user_id,
    YEAR(a.attendance_date) AS period_year,
    MONTH(a.attendance_date) AS period_month,
    COUNT(*) AS total_records,
    SUM(CASE WHEN a.status IN ('present','late','field_duty') THEN 1 ELSE 0 END) AS present_days,
    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent_days,
    SUM(CASE WHEN a.status IN ('on_leave','leave') THEN 1 ELSE 0 END) AS leave_days,
    SUM(COALESCE(a.overtime_hours, 0)) AS overtime_hours,
    SUM(COALESCE(a.late_minutes, 0)) AS total_late_minutes
FROM os_hr_attendance a
WHERE a.is_deleted = 0
GROUP BY a.user_id, YEAR(a.attendance_date), MONTH(a.attendance_date);

-- ═══════════════════════════════════════════════════════════════
-- Phase 4: المخزون والديوان
-- ═══════════════════════════════════════════════════════════════

-- 4.1 رصيد كل صنف مخزون
CREATE OR REPLACE VIEW os_vw_inventory_item_balance AS
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

FROM os_inventory_items i
LEFT JOIN (
    SELECT item_id, COUNT(item_id) AS total_in
    FROM os_inventory_item_quantities
    GROUP BY item_id
) q ON q.item_id = i.id
LEFT JOIN (
    SELECT item_id, COUNT(item_id) AS total_out
    FROM os_contract_inventory_item
    GROUP BY item_id
) s ON s.item_id = i.id;

-- 4.2 بحث معاملات الديوان مع أسماء الموظفين وعدد العقود
CREATE OR REPLACE VIEW os_vw_diwan_transaction_search AS
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

FROM os_diwan_transactions t
LEFT JOIN os_user fe ON fe.id = t.from_employee_id
LEFT JOIN os_user te ON te.id = t.to_employee_id
LEFT JOIN os_user cb ON cb.id = t.created_by
LEFT JOIN (
    SELECT
        transaction_id,
        COUNT(*) AS contract_count,
        GROUP_CONCAT(contract_number ORDER BY id SEPARATOR ', ') AS contract_numbers
    FROM os_diwan_transaction_details
    GROUP BY transaction_id
) dc ON dc.transaction_id = t.id;
