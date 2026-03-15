# -*- coding: utf-8 -*-
"""Find all Tayseer/ERP project paths on the server."""
import sys, os, subprocess

try:
    import paramiko
except ImportError:
    print("pip install paramiko"); sys.exit(1)

HOST = "31.220.82.115"
USER = "root"
PORT = 22

def get_password():
    conf_path = os.path.expandvars(r"%APPDATA%\rclone\rclone.conf")
    obscured = None
    with open(conf_path, "r", encoding="utf-8") as f:
        for line in f:
            if line.strip().startswith("pass = "):
                obscured = line.split("pass = ", 1)[1].strip()
                break
    rclone_exe = r"C:\Users\PC\AppData\Local\Microsoft\WinGet\Links\rclone.exe"
    try:
        result = subprocess.run([rclone_exe, "reveal", obscured], capture_output=True, text=True, timeout=15)
        if result.returncode == 0 and result.stdout.strip():
            return result.stdout.strip()
    except Exception:
        pass
    return "HAmAS12852"

def ssh_exec(client, cmd, timeout=30):
    stdin, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    code = stdout.channel.recv_exit_status()
    return out, err, code

def main():
    password = get_password()
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(HOST, port=PORT, username=USER, password=password, timeout=15)
    print("Connected!\n")

    # List all sites in /var/www
    print("=== All sites in /var/www ===")
    out, _, _ = ssh_exec(client, "ls -la /var/www/ 2>&1")
    print(out)

    # Find all Apache virtual hosts
    print("=== All Apache virtual hosts ===")
    out, _, _ = ssh_exec(client, "grep -r 'ServerName\\|DocumentRoot' /etc/apache2/sites-enabled/ 2>/dev/null | sort")
    print(out)

    # Find all yii projects
    print("=== All Yii projects (yii files) ===")
    out, _, _ = ssh_exec(client, "find /var/www -maxdepth 2 -name 'yii' -type f 2>/dev/null")
    print(out)

    # Check namaa and jadal domains specifically
    print("=== namaa.aqssat.co config ===")
    out, _, _ = ssh_exec(client, "cat /etc/apache2/sites-enabled/namaa.aqssat.co*.conf 2>/dev/null | grep -E 'ServerName|DocumentRoot'")
    print(out if out.strip() else "Not found in sites-enabled")
    out, _, _ = ssh_exec(client, "cat /etc/apache2/sites-available/namaa.aqssat.co*.conf 2>/dev/null | grep -E 'ServerName|DocumentRoot'")
    print(out if out.strip() else "Not found in sites-available")

    print("\n=== jadal.aqssat.co config ===")
    out, _, _ = ssh_exec(client, "cat /etc/apache2/sites-enabled/jadal.aqssat.co*.conf 2>/dev/null | grep -E 'ServerName|DocumentRoot'")
    print(out if out.strip() else "Not found in sites-enabled")
    out, _, _ = ssh_exec(client, "cat /etc/apache2/sites-available/jadal.aqssat.co*.conf 2>/dev/null | grep -E 'ServerName|DocumentRoot'")
    print(out if out.strip() else "Not found in sites-available")

    # Check DB configs for each project
    print("\n=== DB configs per project ===")
    out, _, _ = ssh_exec(client, "find /var/www -maxdepth 3 -path '*/common/config/main-local.php' 2>/dev/null")
    for line in out.strip().split('\n'):
        if line.strip():
            print(f"\n--- {line} ---")
            dbout, _, _ = ssh_exec(client, f"grep -A2 'dbname' {line}")
            print(dbout.strip())

    client.close()

if __name__ == "__main__":
    main()
