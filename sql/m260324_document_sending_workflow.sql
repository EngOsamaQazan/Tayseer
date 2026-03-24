-- ============================================================
-- تحديث قاعدة البيانات: سير عمل إرسال الكتب والمذكرات
-- Migration: m260324_100000_document_sending_workflow
-- Date: 2026-03-24
-- ============================================================
-- يُنفَّذ على قواعد البيانات السحابية (jadal / namaa)
-- قبل التنفيذ تأكد من وجود جدول os_diwan_correspondence
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- 1) إضافة عمود طريقة الإرسال لجدول المراسلات
ALTER TABLE `os_diwan_correspondence`
    ADD COLUMN `delivery_method` VARCHAR(30) NULL DEFAULT NULL
    AFTER `notification_method`;

-- 2) إضافة عمود ربط المراسلة بالإجراء القضائي
ALTER TABLE `os_judiciary_customers_actions`
    ADD COLUMN `correspondence_id` INT(11) NULL DEFAULT NULL
    AFTER `request_target`;

-- 3) إنشاء المفتاح الأجنبي
ALTER TABLE `os_judiciary_customers_actions`
    ADD CONSTRAINT `fk_jca_correspondence`
    FOREIGN KEY (`correspondence_id`)
    REFERENCES `os_diwan_correspondence` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;

-- 4) تحديث حالة الكتب/المذكرات الحالية إلى "غير مُرسل"
UPDATE `os_judiciary_customers_actions` jca
INNER JOIN `os_judiciary_actions` ja ON ja.id = jca.judiciary_actions_id
SET jca.request_status = 'not_sent'
WHERE ja.action_nature = 'document'
  AND (jca.request_status IS NULL OR jca.request_status = '' OR jca.request_status = 'pending');

-- ============================================================
-- للتراجع (Rollback) في حال الحاجة:
-- ============================================================
-- UPDATE os_judiciary_customers_actions jca
-- INNER JOIN os_judiciary_actions ja ON ja.id = jca.judiciary_actions_id
-- SET jca.request_status = NULL
-- WHERE ja.action_nature = 'document' AND jca.request_status = 'not_sent';
--
-- ALTER TABLE os_judiciary_customers_actions DROP FOREIGN KEY fk_jca_correspondence;
-- ALTER TABLE os_judiciary_customers_actions DROP COLUMN correspondence_id;
-- ALTER TABLE os_diwan_correspondence DROP COLUMN delivery_method;
-- ============================================================
