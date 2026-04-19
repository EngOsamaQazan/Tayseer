#!/usr/bin/env bash
set -u
for t in jadal majd namaa watar; do
    echo "── tenant: $t ──"
    cd "/var/www/${t}.aqssat.co" || { echo "missing dir"; continue; }
    echo "[ migration history :: fahras ]"
    php yii migrate/history --interactive=0 2>&1 | grep -i fahras || echo "  (no fahras migration in history)"
    echo "[ migration mark in DB ]"
    php -r '
        $cfg = require "common/config/main-local.php";
        $dsn = $cfg["components"]["db"]["dsn"];
        $u   = $cfg["components"]["db"]["username"];
        $p   = $cfg["components"]["db"]["password"];
        try {
            $pdo = new PDO($dsn, $u, $p, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $row = $pdo->query("SELECT version, apply_time FROM migration WHERE version LIKE \"%fahras%\"")->fetch(PDO::FETCH_ASSOC);
            print_r($row ?: ["row" => "NONE"]);
            echo "tables matching os_fahras*:\n";
            foreach ($pdo->query("SHOW TABLES LIKE \"os_fahras%\"") as $r) print_r($r);
        } catch (Throwable $e) {
            echo "ERR: " . $e->getMessage() . "\n";
        }
    '
    echo
done
