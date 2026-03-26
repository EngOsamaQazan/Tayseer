-- ============================================================
-- إنشاء VIEW لحساب حالة المواعيد النهائية مباشرة
-- Migration: m260325_deadline_live_view
-- Date: 2026-03-25 (idempotent — safe to run multiple times)
-- ============================================================

SET NAMES utf8mb4;

-- إنشاء جدول المواعيد إذا لم يكن موجوداً
CREATE TABLE IF NOT EXISTS `os_judiciary_deadlines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judiciary_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `deadline_type` varchar(30) NOT NULL,
  `day_type` varchar(10) NOT NULL DEFAULT 'working',
  `label` varchar(255) NOT NULL DEFAULT '',
  `start_date` date NOT NULL,
  `deadline_date` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `related_communication_id` int(11) DEFAULT NULL,
  `related_customer_action_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_deadline_judiciary` (`judiciary_id`),
  KEY `idx_deadline_status` (`status`),
  KEY `idx_deadline_type` (`deadline_type`),
  KEY `idx_deadline_date` (`deadline_date`),
  KEY `idx_deadline_action` (`related_customer_action_id`),
  KEY `idx_deadline_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- توليد مواعيد ناقصة للطلبات الإجرائية
INSERT IGNORE INTO os_judiciary_deadlines
    (judiciary_id, customer_id, deadline_type, day_type, label,
     start_date, deadline_date, status,
     related_customer_action_id, is_deleted, created_at, updated_at)
SELECT
    jca.judiciary_id,
    jca.customers_id,
    'request_decision_3wd',
    'working',
    'قرار القاضي على الطلب',
    COALESCE(jca.action_date, FROM_UNIXTIME(jca.created_at, '%Y-%m-%d')),
    DATE_ADD(COALESCE(jca.action_date, FROM_UNIXTIME(jca.created_at, '%Y-%m-%d')), INTERVAL 5 DAY),
    'pending',
    jca.id,
    0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM os_judiciary_customers_actions jca
INNER JOIN os_judiciary_actions ja ON ja.id = jca.judiciary_actions_id
WHERE ja.action_nature = 'request'
  AND (jca.is_deleted = 0 OR jca.is_deleted IS NULL)
  AND NOT EXISTS (
      SELECT 1 FROM os_judiciary_deadlines dl
      WHERE dl.related_customer_action_id = jca.id
        AND dl.deadline_type IN ('request_decision_3wd', 'request_decision')
        AND dl.is_deleted = 0
  );

-- توليد مواعيد ناقصة لتسجيل القضايا
INSERT IGNORE INTO os_judiciary_deadlines
    (judiciary_id, customer_id, deadline_type, day_type, label,
     start_date, deadline_date, status,
     is_deleted, created_at, updated_at)
SELECT
    j.id,
    NULL,
    'registration_3wd',
    'working',
    'فحص حالة التبليغ بعد التسجيل',
    FROM_UNIXTIME(j.created_at, '%Y-%m-%d'),
    DATE_ADD(FROM_UNIXTIME(j.created_at, '%Y-%m-%d'), INTERVAL 5 DAY),
    'pending',
    0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM os_judiciary j
WHERE (j.is_deleted = 0 OR j.is_deleted IS NULL)
  AND NOT EXISTS (
      SELECT 1 FROM os_judiciary_deadlines dl
      WHERE dl.judiciary_id = j.id
        AND dl.deadline_type IN ('registration_3wd', 'registration')
        AND dl.is_deleted = 0
  );

-- إنشاء الـ VIEW
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
             AND cc.c_status = 'judiciary'
             AND cc.remaining <= 0
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
LEFT JOIN os_judiciary_customers_actions ra
       ON ra.id = d.related_customer_action_id
LEFT JOIN (
    SELECT
        co.id,
        co.status AS c_status,
        GREATEST(0,
            co.total_value
            + COALESCE(ex.t,0)
            + COALESCE(lw.t,0)
            - COALESCE(ic.t,0)
            - COALESCE(ad.t,0)
        ) AS remaining
    FROM os_contracts co
    LEFT JOIN (SELECT contract_id, SUM(amount) t FROM os_expenses
               WHERE is_deleted=0 OR is_deleted IS NULL
               GROUP BY contract_id) ex ON ex.contract_id = co.id
    LEFT JOIN (SELECT contract_id, SUM(lawyer_cost) t FROM os_judiciary
               WHERE is_deleted=0 OR is_deleted IS NULL
               GROUP BY contract_id) lw ON lw.contract_id = co.id
    LEFT JOIN (SELECT contract_id, SUM(amount) t FROM os_income
               GROUP BY contract_id) ic ON ic.contract_id = co.id
    LEFT JOIN (SELECT contract_id, SUM(amount) t FROM os_contract_adjustments
               WHERE is_deleted=0
               GROUP BY contract_id) ad ON ad.contract_id = co.id
) cc ON cc.id = j.contract_id

WHERE d.is_deleted = 0;
