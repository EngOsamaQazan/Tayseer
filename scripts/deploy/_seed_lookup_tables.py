"""Seed lookup/reference tables for existing companies (majd, watar)."""
import sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
import paramiko

SERVER = '31.220.82.115'
USER = 'root'
PASSWD = 'HAmAS12852'

SEED_TABLES = (
    "os_bancks os_city os_contact_type os_countries os_court os_department "
    "os_designation os_document_status os_document_type os_expense_categories "
    "os_feelings os_fiscal_years os_fiscal_periods os_hear_about_us os_hr_grade "
    "os_hr_salary_component os_income_category os_jobs_type os_judiciary_authorities "
    "os_judiciary_type os_official_holidays os_payment_type os_risk_engine_config "
    "os_status os_system_settings os_work_shift os_workdays os_accounts os_user_categories"
)

SRC_DB = 'namaa_jadal'
DB_USER = 'osama'
DB_PASS = 'OsamaDB123'

def run(ssh, cmd, timeout=120):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    code = stdout.channel.recv_exit_status()
    return out, err, code

def seed_db(ssh, target_db):
    print(f"\n{'='*50}")
    print(f"  Seeding {target_db}")
    print(f"{'='*50}")

    # Check current state
    out, _, _ = run(ssh, f"""mysql -u {DB_USER} -p{DB_PASS} {target_db} -N -e "SELECT COUNT(*) FROM os_bancks;" 2>/dev/null""")
    bank_count = out.strip()
    print(f"  Current os_bancks rows: {bank_count}")

    if bank_count and int(bank_count) > 0:
        print(f"  Already has data, skipping!")
        return

    # Run seed dump
    cmd = (
        f"mysqldump -u {DB_USER} -p{DB_PASS} --no-create-info --insert-ignore --skip-triggers "
        f"{SRC_DB} {SEED_TABLES} 2>/dev/null | mysql -u {DB_USER} -p{DB_PASS} {target_db} 2>&1"
    )
    out, err, code = run(ssh, cmd, timeout=120)
    if code == 0:
        print(f"  Seed data imported!")
    else:
        print(f"  Error: {out.strip()[:300]} {err.strip()[:300]}")

    # Verify
    for table in ['os_bancks', 'os_department', 'os_system_settings', 'os_city']:
        out, _, _ = run(ssh, f"""mysql -u {DB_USER} -p{DB_PASS} {target_db} -N -e "SELECT COUNT(*) FROM {table};" 2>/dev/null""")
        print(f"  {table}: {out.strip()} rows")

def main():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SERVER, username=USER, password=PASSWD, timeout=15)
    print("Connected!\n")

    # Check which databases need seeding
    for db in ['tayseer_majd', 'tayseer_watar']:
        out, _, _ = run(ssh, f"""mysql -u {DB_USER} -p{DB_PASS} -e "SHOW DATABASES LIKE '{db}';" 2>/dev/null""")
        if db in out:
            seed_db(ssh, db)
        else:
            print(f"\n  {db}: database not found, skipping")

    ssh.close()
    print("\n\nDone!")

if __name__ == '__main__':
    main()
