#!/usr/bin/env bash
set -u
for t in jadal majd namaa watar; do
    echo "── tenant: $t ──"
    cd "/var/www/${t}.aqssat.co" || continue
    php -r '
        $cfg = require "common/config/main-local.php";
        $dsn = $cfg["components"]["db"]["dsn"];
        $u   = $cfg["components"]["db"]["username"];
        $p   = $cfg["components"]["db"]["password"];
        $tp  = $cfg["components"]["db"]["tablePrefix"] ?? "os_";
        $pdo = new PDO($dsn, $u, $p, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        echo "  dsn=$dsn  prefix=$tp\n";
        $tbl = $tp . "fahras_check_log";
        $rows = $pdo->query("SHOW TABLES LIKE \"$tbl\"")->fetchAll(PDO::FETCH_COLUMN);
        echo "  table $tbl exists: " . (count($rows) ? "YES" : "NO") . "\n";
        if (count($rows)) {
            $cnt = $pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();
            echo "  rows: $cnt\n";
        }
    '
done
