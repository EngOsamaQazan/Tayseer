# -*- coding: utf-8 -*-
"""تعديل إعدادات SSH/SFTP على السيرفر لعرض جذر النظام (/) عبر SFTP."""
import sys
import os

try:
    import paramiko
except ImportError:
    print("تثبيت paramiko: pip install paramiko")
    sys.exit(1)

def get_password():
    conf_path = os.path.expandvars(r"%APPDATA%\rclone\rclone.conf")
    if not os.path.exists(conf_path):
        conf_path = r"C:\Users\PC\AppData\Roaming\rclone\rclone.conf"
    try:
        with open(conf_path, "r", encoding="utf-8") as f:
            for line in f:
                if line.strip().startswith("pass = "):
                    return line.split("pass = ", 1)[1].strip()
    except Exception as e:
        print("فشل قراءة rclone.conf:", e)
        return None

HOST = "31.220.82.115"
USER = "root"
PORT = 22

def main():
    password = get_password()
    if not password:
        sys.exit(1)
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        client.connect(HOST, port=PORT, username=USER, password=password, timeout=15)
    except Exception as e:
        print("فشل الاتصال بـ SSH:", e)
        sys.exit(1)

    # أوامر تنفذ على السيرفر بالترتيب
    commands = [
        "cp /etc/ssh/sshd_config /etc/ssh/sshd_config.bak.$(date +%Y%m%d_%H%M%S)",
        r"sed -i.bak2 's|ChrootDirectory[[:space:]]*/root[^[:space:]]*|ChrootDirectory /|' /etc/ssh/sshd_config",
        "grep -q 'Match User root' /etc/ssh/sshd_config || (echo '' >> /etc/ssh/sshd_config; echo 'Match User root' >> /etc/ssh/sshd_config; echo '    ChrootDirectory /' >> /etc/ssh/sshd_config)",
        "sshd -t",
        "systemctl restart sshd 2>/dev/null || systemctl restart ssh 2>/dev/null || service ssh restart 2>/dev/null || service sshd restart 2>/dev/null",
    ]
    for i, cmd in enumerate(commands):
        print("تنفيذ:", cmd[:60] + "..." if len(cmd) > 60 else cmd)
        stdin, stdout, stderr = client.exec_command(cmd)
        out, err = stdout.read().decode(), stderr.read().decode()
        if err and "Warning" not in err:
            print("  stderr:", err.strip())
        if i == 3 and (out.strip() or err.strip()) and "error" in (out + err).lower():
            print("فشل اختبار sshd، تم الإلغاء. النسخة الاحتياطية محفوظة.")
            client.close()
            sys.exit(1)
    client.close()
    print("تم التعديل وإعادة تشغيل sshd. أعد تشغيل rclone mount ثم افتح Y:\\")

if __name__ == "__main__":
    main()
