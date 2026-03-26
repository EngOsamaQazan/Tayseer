-- ============================================================
-- توليد سجلات المواعيد الناقصة للقضايا والطلبات الموجودة
-- Date: 2026-03-26 (idempotent — safe to run multiple times)
-- ============================================================

SET NAMES utf8mb4;

-- 1) مواعيد قرار القاضي للطلبات الإجرائية بدون موعد
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

-- 2) مواعيد فحص التسجيل للقضايا بدون موعد
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

SELECT 'Deadline data population complete' AS result;
