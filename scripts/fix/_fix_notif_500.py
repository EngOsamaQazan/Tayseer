import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(cmd, timeout=120):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    return out, err

# Install the missing kartik-v/yii2-bootstrap5-dropdown package
print("=== Installing kartik-v/yii2-bootstrap5-dropdown ===")
out, err = run("cd /var/www/jadal.aqssat.co && composer require kartik-v/yii2-bootstrap5-dropdown --no-interaction 2>&1", timeout=120)
print(out[-5000:] if len(out) > 5000 else out)
if err:
    print("STDERR:", err[-2000:])

print("\n" + "="*80)

# Verify the package was installed
out, _ = run("ls -la /var/www/jadal.aqssat.co/vendor/kartik-v/yii2-bootstrap5-dropdown/ 2>&1")
print("=== VERIFY INSTALLATION ===")
print(out)

# Clear OPcache
out, _ = run("php -r \"opcache_reset();\" 2>&1; service apache2 graceful 2>&1")
print("=== OPCACHE CLEARED & APACHE RESTARTED ===")
print(out)

ssh.close()
print("\nDone!")
