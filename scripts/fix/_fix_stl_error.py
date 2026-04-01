import paramiko
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('31.220.82.115', username='root', password='HAmAS12852', timeout=15)

def run(label, cmd, timeout=60):
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err and not out:
        print(f'[stderr] {err}')
    if not out and not err:
        print('[OK]')
    print()

projects = [
    'jadal.aqssat.co',
    'namaa.aqssat.co',
    'watar.aqssat.co',
]

for proj in projects:
    modals_path = f'/var/www/{proj}/backend/modules/followUp/views/follow-up/modals.php'

    # Backup first
    run(f"Backup {proj} modals.php",
        f"cp {modals_path} {modals_path}.bak.20260328")

    # Add $_stlTotalDebt definition after line 395 (after $_vbStl = ...)
    # The fix: insert $_stlTotalDebt = $_vbStl ? $_vbStl['contractValue'] : (float)($contractModel->total_value ?? 0);
    # between the $_vbStl line and the $_stlAutoTotal line
    run(f"Fix {proj} - add _stlTotalDebt",
        f"""sed -i 's|^\\$_stlAutoTotal = \\$_vbStl|\\$_stlTotalDebt = \\$_vbStl ? \\$_vbStl['"'"'contractValue'"'"'] : (float)(\\$contractModel->total_value ?? 0);\\n\\$_stlAutoTotal = \\$_vbStl|' {modals_path}""")

    # Verify the fix
    run(f"Verify {proj} - lines 395-402",
        f"sed -n '395,403p' {modals_path}")

ssh.close()
