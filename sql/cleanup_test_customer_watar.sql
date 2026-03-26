-- ============================================================
-- تنظيف: حذف العميل التجريبي وتصفير العدادات
-- Target: tayseer_watar (safe to run on other DBs — no-op if data doesn't exist)
-- Date: 2026-03-26 (one-time cleanup)
-- ============================================================

SET NAMES utf8mb4;

-- 1) حذف ربط العميل التجريبي بالعقود
DELETE cc FROM os_contracts_customers cc
INNER JOIN os_customers c ON c.id = cc.customer_id
WHERE c.name LIKE '%طارق خالد تركي الزبن%';

-- 2) حذف العقود المرتبطة بالعميل التجريبي (التي لم يعد لها عملاء)
DELETE ct FROM os_contracts ct
WHERE ct.id NOT IN (SELECT DISTINCT contract_id FROM os_contracts_customers)
  AND ct.id = (SELECT MAX(id) FROM (SELECT id FROM os_contracts) tmp);

-- 3) حذف العميل التجريبي
DELETE FROM os_customers WHERE name LIKE '%طارق خالد تركي الزبن%';

-- 4) تصفير عداد جدول العملاء (MySQL يضبطه تلقائياً على MAX(id)+1)
ALTER TABLE os_customers AUTO_INCREMENT = 1;

-- 5) تصفير عداد جدول العقود
ALTER TABLE os_contracts AUTO_INCREMENT = 1;

-- 6) تصفير عداد جدول ربط العملاء بالعقود
ALTER TABLE os_contracts_customers AUTO_INCREMENT = 1;
