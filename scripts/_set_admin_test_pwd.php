<?php
// Temporarily set admin user (id=1) password for automated UI testing.
// PURPOSE: enable Playwright login. Original hash backed up to .bak file.
$dsn  = 'mysql:host=127.0.0.1;port=3306;dbname=namaa_jadal';
$pdo  = new PDO($dsn, 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$bakFile = __DIR__ . '/_admin_pwd.bak';
if (!file_exists($bakFile)) {
    $orig = $pdo->query('SELECT password_hash FROM os_user WHERE id = 1')->fetchColumn();
    file_put_contents($bakFile, $orig);
    echo "Original hash backed up to {$bakFile}\n";
} else {
    echo "Backup already exists at {$bakFile} — not overwriting.\n";
}

$newHash = password_hash('AuditPwd2026!', PASSWORD_BCRYPT);
$st = $pdo->prepare('UPDATE os_user SET password_hash = :h WHERE id = 1');
$st->execute([':h' => $newHash]);
echo "Admin password temporarily set to: AuditPwd2026!  (rows affected: " . $st->rowCount() . ")\n";
echo "Run scripts/_audit_wizard_viewports.js, then scripts/_revert_admin_pwd.php.\n";
