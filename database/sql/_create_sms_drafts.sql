CREATE TABLE IF NOT EXISTS `os_sms_drafts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `text` TEXT NOT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_sms_drafts_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
