import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)
sftp = ssh.open_sftp()

def run(label, cmd, timeout=60):
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err:
        print(f'[stderr] {err}')
    if not out and not err:
        print('[OK]')
    print()

proj = '/var/www/jadal.aqssat.co'

# Create a debug PHP script on the server
debug_php = '''<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check yara's permissions directly from DB
$pdo = new PDO('mysql:host=localhost;dbname=namaa_jadal;charset=utf8', 'osama', 'OsamaDB123');

$user = $pdo->query("SELECT id, username FROM os_user WHERE username = 'yara'")->fetch(PDO::FETCH_ASSOC);
echo "User: " . json_encode($user, JSON_UNESCAPED_UNICODE) . "\\n";

if ($user) {
    $stmt = $pdo->prepare("SELECT item_name FROM os_auth_assignment WHERE user_id = ? AND item_name NOT LIKE '/%'");
    $stmt->execute([$user['id']]);
    $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Assigned permissions (" . count($perms) . "):\\n";
    foreach ($perms as $p) {
        echo "  - " . $p . "\\n";
    }

    // Check which settings permissions exist in auth_item
    $settingsPerms = [
        'الحالات','حالات الوثائق','الاقارب','الجنسيه','البنوك',
        'كيف سمعت عنا','المدن','طرق الدفع','الانفعالات',
        'طريقة الاتصال','رد العميل','انواع الوثائق','الرسائل',
        'الوظائف','الوظائف: مشاهدة','الوظائف: إضافة','الوظائف: تعديل','الوظائف: حذف'
    ];

    echo "\\n--- Settings permissions in auth_item ---\\n";
    foreach ($settingsPerms as $sp) {
        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM os_auth_item WHERE name = ? AND type = 2");
        $stmt2->execute([$sp]);
        $exists = $stmt2->fetchColumn();

        $stmt3 = $pdo->prepare("SELECT COUNT(*) FROM os_auth_assignment WHERE user_id = ? AND item_name = ?");
        $stmt3->execute([$user['id'], $sp]);
        $assigned = $stmt3->fetchColumn();

        $status = ($exists ? 'EXISTS' : 'MISSING') . ' | ' . ($assigned ? 'ASSIGNED' : 'NOT-ASSIGNED');
        echo "  {$sp}: {$status}\\n";
    }
}
'''

# Write debug script to server
with sftp.open(f'{proj}/backend/web/_debug_perms.php', 'w') as f:
    f.write(debug_php)

run('Check yara permissions',
    f'php {proj}/backend/web/_debug_perms.php')

# Clean up
run('Cleanup',
    f'rm -f {proj}/backend/web/_debug_perms.php')

# Also check what resolveRoute returns for system-settings
run('Check URL manager rules for system-settings',
    f'grep -n "system-settings\\|image-manager" {proj}/backend/config/main.php')

run('Check urlManager rules',
    f'grep -n "urlManager\\|rules\\|enablePrettyUrl" {proj}/backend/config/main.php | head -20')

sftp.close()
ssh.close()
print('Done!')
