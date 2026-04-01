#!/bin/bash
# Fix SFTP to show full filesystem (/) for root - main config + sshd_config.d
set -e
echo "=== Backup ==="
cp /etc/ssh/sshd_config /etc/ssh/sshd_config.bak."$(date +%Y%m%d_%H%M%S)"
for f in /etc/ssh/sshd_config.d/*.conf; do
  [ -f "$f" ] || continue
  cp "$f" "${f}.bak.$(date +%Y%m%d_%H%M%S)"
done
echo "=== Replace ChrootDirectory /root with / in main config ==="
sed -i.bak2 's|ChrootDirectory[[:space:]]*/root[^[:space:]]*|ChrootDirectory /|g' /etc/ssh/sshd_config 2>/dev/null || true
echo "=== Replace in sshd_config.d ==="
for f in /etc/ssh/sshd_config.d/*.conf; do
  [ -f "$f" ] || continue
  sed -i.bak2 's|ChrootDirectory[[:space:]]*/root[^[:space:]]*|ChrootDirectory /|g' "$f" 2>/dev/null || true
done
echo "=== Force root to see / (last Match wins) ==="
cat > /etc/ssh/sshd_config.d/99-sftp-root-full.conf << 'EOF'
Match User root
    ChrootDirectory /
EOF
echo "=== Test sshd ==="
sshd -t || { echo "Config error. Remove 99-sftp-root-full.conf and restore backups."; rm -f /etc/ssh/sshd_config.d/99-sftp-root-full.conf; exit 1; }
echo "=== Restart sshd ==="
systemctl restart sshd 2>/dev/null || systemctl restart ssh 2>/dev/null || service ssh restart 2>/dev/null || service sshd restart 2>/dev/null || true
echo "Done. Restart rclone mount (normal PowerShell, not admin) and open Y:"
