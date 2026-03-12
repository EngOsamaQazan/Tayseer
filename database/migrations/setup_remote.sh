#!/bin/bash
# Create MySQL config file on the remote server
cat > /tmp/.my.cnf << 'MYCNF'
[client]
user=osama
password=o$amaDaTaBase@O@123
MYCNF
chmod 600 /tmp/.my.cnf

# Test connection
mysql --defaults-file=/tmp/.my.cnf namaa_jadal -e "SELECT COUNT(*) as hr_tables FROM information_schema.tables WHERE table_schema='namaa_jadal' AND table_name LIKE 'os_hr%'"
