-- ============================================================
-- تحديث قاعدة البيانات: سير عمل إرسال الكتب والمذكرات
-- Migration: m260324_100000_document_sending_workflow
-- Date: 2026-03-24 (idempotent — safe to run multiple times)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- 1) إضافة عمود طريقة الإرسال (تخطي إذا موجود)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'os_diwan_correspondence' AND COLUMN_NAME = 'delivery_method');
SET @sql1 = IF(@col_exists = 0,
    'ALTER TABLE `os_diwan_correspondence` ADD COLUMN `delivery_method` VARCHAR(30) NULL DEFAULT NULL AFTER `notification_method`',
    'SELECT 1');
PREPARE stmt FROM @sql1; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) إضافة عمود ربط المراسلة بالإجراء القضائي (تخطي إذا موجود)
SET @col_exists2 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'os_judiciary_customers_actions' AND COLUMN_NAME = 'correspondence_id');
SET @sql2 = IF(@col_exists2 = 0,
    'ALTER TABLE `os_judiciary_customers_actions` ADD COLUMN `correspondence_id` INT(11) NULL DEFAULT NULL AFTER `request_target`',
    'SELECT 1');
PREPARE stmt FROM @sql2; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) إنشاء المفتاح الأجنبي (تخطي إذا موجود)
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'os_judiciary_customers_actions' AND CONSTRAINT_NAME = 'fk_jca_correspondence');
SET @sql3 = IF(@fk_exists = 0,
    'ALTER TABLE `os_judiciary_customers_actions` ADD CONSTRAINT `fk_jca_correspondence` FOREIGN KEY (`correspondence_id`) REFERENCES `os_diwan_correspondence` (`id`) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1');
PREPARE stmt FROM @sql3; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Done!
SELECT 'Document sending workflow SQL applied successfully' AS result;
