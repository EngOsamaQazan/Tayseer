#!/bin/bash
echo "=== /etc/ssh/sshd_config (ChrootDirectory and Match) ==="
grep -n -E "ChrootDirectory|Match " /etc/ssh/sshd_config 2>/dev/null || true
echo ""
echo "=== /etc/ssh/sshd_config.d/*.conf ==="
for f in /etc/ssh/sshd_config.d/*.conf; do
  [ -f "$f" ] || continue
  echo "--- $f ---"
  cat "$f"
  echo ""
done
echo "=== Subsystem sftp ==="
grep -n -i subsystem /etc/ssh/sshd_config /etc/ssh/sshd_config.d/*.conf 2>/dev/null || true
