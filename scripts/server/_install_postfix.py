import paramiko
import sys
import time
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(label, cmd, timeout=120):
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err:
        print(f'[stderr] {err}')
    if not out and not err:
        print('OK (no output)')
    print()
    return out

# Step 1: Pre-seed Postfix debconf to avoid interactive prompts
run("Pre-seed Postfix config",
    "echo 'postfix postfix/main_mailer_type select Local only' | debconf-set-selections && "
    "echo 'postfix postfix/mailname string cp.aqssat.co' | debconf-set-selections")

# Step 2: Stop Exim4
run("Stop Exim4", "systemctl stop exim4 2>/dev/null && echo STOPPED || echo ALREADY_STOPPED")

# Step 3: Install Postfix (will auto-remove exim4 due to conflict)
run("Install Postfix", "DEBIAN_FRONTEND=noninteractive apt-get install -y postfix 2>&1 | tail -20", timeout=180)

# Step 4: Remove Exim4 packages completely
run("Purge Exim4", "apt-get purge -y exim4 exim4-base exim4-config exim4-daemon-light 2>&1 | tail -10")

# Step 5: Configure Postfix for local only
postfix_config = """
# Postfix - Local delivery only
inet_interfaces = loopback-only
inet_protocols = ipv4
myhostname = cp.aqssat.co
mydomain = aqssat.co
myorigin = $myhostname
mydestination = $myhostname, localhost.$mydomain, localhost
relayhost =
mynetworks = 127.0.0.0/8
mailbox_size_limit = 51200000
message_size_limit = 10240000
smtpd_banner = $myhostname ESMTP
biff = no
append_dot_mydomain = no
readme_directory = no
compatibility_level = 3.6
smtpd_relay_restrictions = permit_mynetworks, reject_unauth_destination
alias_maps = hash:/etc/aliases
alias_database = hash:/etc/aliases
"""

run("Write Postfix config",
    f"cat > /etc/postfix/main.cf << 'POSTFIX_EOF'{postfix_config}POSTFIX_EOF")

# Step 6: Update aliases
run("Update aliases", "newaliases 2>&1")

# Step 7: Restart Postfix
run("Restart Postfix", "systemctl restart postfix 2>&1 && systemctl enable postfix 2>&1 && echo POSTFIX_STARTED")

# Step 8: Verify
run("Verify Postfix status", "systemctl is-active postfix && postfix status 2>&1")
run("Verify listening", "ss -tlnp | grep ':25'")
run("Verify sendmail exists", "ls -la /usr/sbin/sendmail 2>/dev/null")
run("Autoremove unused", "apt-get autoremove -y 2>&1 | tail -5")

ssh.close()
print("=== DONE ===")
