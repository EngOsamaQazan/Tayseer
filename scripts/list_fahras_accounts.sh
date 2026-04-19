#!/usr/bin/env bash
set -u
# Pull DB creds from the active Fahras vhost (root-only readable).
USER=$(grep -oP 'SetEnv\s+FAHRAS_DB_USER\s+\K\S+' /etc/apache2/sites-enabled/fahras*.conf 2>/dev/null | head -n1)
PASS=$(grep -oP 'SetEnv\s+FAHRAS_DB_PASS\s+\K\S+' /etc/apache2/sites-enabled/fahras*.conf 2>/dev/null | head -n1)

if [[ -z "${USER:-}" || -z "${PASS:-}" ]]; then
    echo "ERR: cannot find FAHRAS_DB_USER/PASS in vhost"
    grep -E 'SetEnv\s+FAHRAS_DB_' /etc/apache2/sites-enabled/fahras*.conf 2>/dev/null
    exit 1
fi

echo "── accounts table ──"
mysql -u "$USER" -p"$PASS" fahras_db -e "SELECT id, name, phone, mobile FROM accounts ORDER BY id;" 2>&1

echo
echo "── distinct entitled_account values in violations ──"
mysql -u "$USER" -p"$PASS" fahras_db -e "SELECT entitled_account, COUNT(*) cnt FROM violations GROUP BY entitled_account ORDER BY cnt DESC LIMIT 30;" 2>&1

echo
echo "── distinct violating_account values in violations ──"
mysql -u "$USER" -p"$PASS" fahras_db -e "SELECT violating_account, COUNT(*) cnt FROM violations GROUP BY violating_account ORDER BY cnt DESC LIMIT 30;" 2>&1
