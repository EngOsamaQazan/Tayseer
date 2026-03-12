#!/bin/bash
echo "=== Checking MySQL connection ==="
mysql --defaults-file=/tmp/.my.cnf -e "SELECT 'CONNECTED' as status" 2>&1
echo "=== HR tables in namaa_jadal ==="
mysql --defaults-file=/tmp/.my.cnf namaa_jadal -e "SHOW TABLES LIKE 'os_hr%'" 2>&1
echo "=== Total table count ==="
mysql --defaults-file=/tmp/.my.cnf namaa_jadal -e "SELECT COUNT(*) as total_tables FROM information_schema.tables WHERE table_schema='namaa_jadal'" 2>&1
echo "=== Workdays table ==="
mysql --defaults-file=/tmp/.my.cnf namaa_jadal -e "SELECT COUNT(*) as wd FROM os_workdays" 2>&1
echo "=== DONE ==="
