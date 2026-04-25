#!/bin/bash
export DEBIAN_FRONTEND=noninteractive
export PATH=$PATH:/sbin

LOG=/root/hestia_install.log
echo "=== HestiaCP Installation Started: $(date) ===" > $LOG

# Download the debian installer directly
echo "[1/4] Downloading Debian installer..." | tee -a $LOG
wget -q https://raw.githubusercontent.com/hestiacp/hestiacp/release/install/hst-install-debian.sh -O /root/hst-install-debian.sh 2>>$LOG
chmod +x /root/hst-install-debian.sh
echo "Downloaded." | tee -a $LOG

# Patch the installer to skip the conflicting packages check
echo "[2/4] Patching installer to allow existing packages..." | tee -a $LOG
sed -i 's/if \[ -n "\$conflicts" \] && \[ -z "\$force" \]; then/if false; then/' /root/hst-install-debian.sh
# Also set force=yes early in the script to bypass any other force checks
sed -i '/^force=.*/d' /root/hst-install-debian.sh
sed -i '2i force="yes"' /root/hst-install-debian.sh
echo "Patched." | tee -a $LOG

# Run the installer
echo "[3/4] Running installer (this takes 10-25 min)..." | tee -a $LOG
echo "Start time: $(date)" >> $LOG

bash /root/hst-install-debian.sh \
  --interactive no \
  --hostname cp.aqssat.co \
  --email osamaqazan89@gmail.com \
  --username admin \
  --password "${HESTIA_PASS:?HESTIA_PASS env var must be set}" \
  --apache yes \
  --phpfpm yes \
  --multiphp yes \
  --mysql yes \
  --postgresql no \
  --named no \
  --exim yes \
  --dovecot no \
  --clamav no \
  --spamassassin no \
  --iptables yes \
  --fail2ban yes \
  --quota no \
  --api yes \
  --port 8083 \
  --lang ar \
  --force \
  >> $LOG 2>&1 < /dev/null

EXIT_CODE=$?
echo "" >> $LOG
echo "[4/4] Installation finished with exit code: $EXIT_CODE at $(date)" | tee -a $LOG

if [ $EXIT_CODE -eq 0 ]; then
  SERVER_IP=$(hostname -I | awk '{print $1}')
  echo "SUCCESS!" | tee -a $LOG
  echo "Panel: https://${SERVER_IP}:8083" | tee -a $LOG
  echo "User: admin | Pass: <see scripts/credentials.py>" | tee -a $LOG
else
  echo "FAILED with exit code $EXIT_CODE" | tee -a $LOG
  echo "Check log: /root/hestia_install.log" | tee -a $LOG
fi
