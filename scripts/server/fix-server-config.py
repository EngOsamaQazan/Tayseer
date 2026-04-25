# --- Credentials (loaded from scripts/credentials.py, git-ignored) ---
# Copy scripts/credentials.example.py to scripts/credentials.py and
# fill in the real values before running this script.
import os as _os, sys as _sys
_sys.path.insert(0, _os.path.join(_os.path.dirname(_os.path.abspath(__file__)), '..'))
_sys.path.insert(0, _os.path.dirname(_os.path.abspath(__file__)))
try:
    from credentials import *  # noqa: F401,F403
except ImportError as _e:
    raise SystemExit(
        'Missing scripts/credentials.py — copy credentials.example.py and fill in real values.\n'
        f'Original error: {_e}'
    )
# ---------------------------------------------------------------------

import paramiko
import os

HOST = OLD_SERVER_IP
USER = 'root'
PASS = OLD_SERVER_PASS

SITES = [
    '/var/www/jadal.aqssat.co',
    '/var/www/namaa.aqssat.co',
]

def run_cmd(ssh, cmd, timeout=60):
    print(f"  $ {cmd}")
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    exit_code = stdout.channel.recv_exit_status()
    if out.strip():
        for line in out.strip().split('\n')[:50]:
            try:
                print(f"    {line}")
            except UnicodeEncodeError:
                pass
    if err.strip():
        for line in err.strip().split('\n')[:20]:
            try:
                print(f"    [err] {line}")
            except UnicodeEncodeError:
                pass
    print(f"  -> exit: {exit_code}")
    return exit_code, out, err

def main():
    print(f"Connecting to {HOST}...")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASS, timeout=30)
    print("Connected!\n")

    for site in SITES:
        name = site.split('/')[-1]
        conf = f"{site}/backend/config/main-local.php"
        print(f"\n{'='*50}")
        print(f"  Fixing: {name}")
        print(f"{'='*50}")

        print("\n  Current config:")
        run_cmd(ssh, f"cat {conf}")

        sed_cmd = (
            f"sed -i "
            f"\"s|\\$config\\['bootstrap'\\]\\[\\] = 'debug';|if (class_exists('yii\\\\\\\\debug\\\\\\\\Module')) {{ \\$config['bootstrap'][] = 'debug';|; "
            f"s|'class' => 'yii\\\\\\\\debug\\\\\\\\Module',|'class' => 'yii\\\\\\\\debug\\\\\\\\Module',|; "
            f"s|\\];$|]; }}|\" "
            f"{conf}"
        )

        # Easier: just write the correct file
        new_config = r"""<?php

$config = [
    'components' => [
        'request' => [
            'cookieValidationKey' => 'LblLjIafItrV_nBh6qmceSv7czTOxXWT',
        ],
    ],
];

if (!YII_ENV_TEST) {
    if (class_exists('yii\debug\Module')) {
        $config['bootstrap'][] = 'debug';
        $config['modules']['debug'] = [
            'class' => 'yii\debug\Module',
            'allowedIPs' => ['127.0.0.1', '::1'],
        ];
    }

    if (class_exists('yii\gii\Module')) {
        $config['bootstrap'][] = 'gii';
        $config['modules']['gii'] = [
            'class' => 'yii\gii\Module',
        ];
    }
}

return $config;
"""

        # First read the actual cookie key from the server config
        code, out, _ = run_cmd(ssh, f"grep cookieValidationKey {conf}")
        cookie_key = 'LblLjIafItrV_nBh6qmceSv7czTOxXWT'
        if 'cookieValidationKey' in out:
            import re
            m = re.search(r"'cookieValidationKey'\s*=>\s*'([^']*)'", out)
            if m:
                cookie_key = m.group(1)
                print(f"    Found cookie key: {cookie_key}")

        final_config = new_config.replace('LblLjIafItrV_nBh6qmceSv7czTOxXWT', cookie_key)

        # Write via heredoc
        write_cmd = f"cat > {conf} << 'PHPEOF'\n{final_config}PHPEOF"
        run_cmd(ssh, write_cmd)

        print("\n  Updated config:")
        run_cmd(ssh, f"cat {conf}")

        print("\n  Testing PHP syntax:")
        run_cmd(ssh, f"php -l {conf}")

    print(f"\n{'='*50}")
    print("  Restarting Apache...")
    print(f"{'='*50}")
    run_cmd(ssh, "a2enmod php8.3 2>&1; true")
    run_cmd(ssh, "systemctl restart apache2 2>&1")

    print("\n  Testing sites:")
    run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://jadal.aqssat.co")
    run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://namaa.aqssat.co")

    print(f"\n{'='*50}")
    print("  DONE!")
    print(f"{'='*50}")

    ssh.close()

if __name__ == '__main__':
    main()
