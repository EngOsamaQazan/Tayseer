<?php
/**
 * Fahras Company Registry — loads company maps from os_companies table.
 *
 * Returns $dbMap, $accountMap, $baseUrlMap arrays populated from
 * the master database's os_companies table.
 *
 * Requires: $db_host, $db_user, $db_pass to be set before including.
 */

$_masterDb = 'namaa_jadal';

$dbMap      = [];
$accountMap = [];
$baseUrlMap = [];

try {
    $_regPdo = new PDO(
        "mysql:host={$db_host};dbname={$_masterDb};charset=utf8",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $_stmt = $_regPdo->query(
        "SELECT slug, db_name, name_ar, domain, og_image
         FROM os_companies
         WHERE status = 'active'
         ORDER BY id ASC"
    );

    while ($_row = $_stmt->fetch(PDO::FETCH_ASSOC)) {
        $dbMap[$_row['slug']]      = $_row['db_name'];
        $accountMap[$_row['slug']] = $_row['name_ar'];
        $baseUrlMap[$_row['slug']] = 'https://' . $_row['domain'];
    }

    // namaa has an "erp" alias
    if (isset($dbMap['namaa'])) {
        $dbMap['erp']      = $dbMap['namaa'];
        $accountMap['erp'] = $accountMap['namaa'];
        $baseUrlMap['erp'] = $baseUrlMap['namaa'];
    }

    $_regPdo = null;
} catch (Exception $_e) {
    // Fallback to hardcoded values if DB is unreachable
    $dbMap = [
        'jadal' => 'namaa_jadal',
        'namaa' => 'namaa_erp',
        'erp'   => 'namaa_erp',
        'watar' => 'tayseer_watar',
        'majd'  => 'tayseer_majd',
    ];
    $accountMap = [
        'jadal' => 'جدل',
        'namaa' => 'نماء',
        'erp'   => 'نماء',
        'watar' => 'وتر',
        'majd'  => 'المجد',
    ];
    $baseUrlMap = [
        'jadal' => 'https://jadal.aqssat.co',
        'namaa' => 'https://namaa.aqssat.co',
        'erp'   => 'https://namaa.aqssat.co',
        'watar' => 'https://watar.aqssat.co',
        'majd'  => 'https://majd.aqssat.co',
    ];
}
