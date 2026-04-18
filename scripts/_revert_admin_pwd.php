<?php
// Restore admin (id=1) password hash from .bak file written by
// _set_admin_test_pwd.php. Run immediately after the UI audit completes.
$dsn  = 'mysql:host=127.0.0.1;port=3306;dbname=namaa_jadal';
$pdo  = new PDO($dsn, 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$bakFile = __DIR__ . '/_admin_pwd.bak';
if (!file_exists($bakFile)) {
    fwrite(STDERR, "FATAL: backup file {$bakFile} not found — cannot revert.\n");
    exit(1);
}
$orig = file_get_contents($bakFile);
if (!$orig || strlen($orig) < 30) {
    fwrite(STDERR, "FATAL: backup file looks invalid (length=" . strlen($orig) . ").\n");
    exit(1);
}
$st = $pdo->prepare('UPDATE os_user SET password_hash = :h WHERE id = 1');
$st->execute([':h' => $orig]);
echo "Admin password reverted (rows affected: " . $st->rowCount() . ").\n";
unlink($bakFile);
echo "Backup file deleted.\n";
