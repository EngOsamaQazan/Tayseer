<?php
/**
 * Create os_sms_drafts table if it doesn't exist.
 * Run: php scripts/fix/_create_sms_drafts.php
 */
$config = require __DIR__ . '/../../common/config/main-local.php';
$db = $config['components']['db'];

$pdo = new PDO($db['dsn'], $db['username'], $db['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$pdo->exec("CREATE TABLE IF NOT EXISTS `os_sms_drafts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `text` TEXT NOT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_sms_drafts_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8");

echo "Done — os_sms_drafts table created (or already exists).\n";
