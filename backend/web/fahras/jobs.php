<?php
/**
 * Fahras Jobs API — البحث في جهات العمل
 * يُستدعى من نظام الفهرس المركزي
 */
date_default_timezone_set("Asia/Amman");

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require('db.php');
$db_host = 'localhost';
$db_user = 'osama';
$db_pass = 'OsamaDB123';

require_once __DIR__ . '/_companies.php';

$requestDb = $_REQUEST['db'] ?? '';
$db_name = $dbMap[$requestDb] ?? null;

if (!$db_name) {
  echo json_encode(['error' => 'invalid db parameter']);
  exit();
}

try {
  $db = new smplPDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
} catch (Exception $e) {
  echo json_encode(['error' => 'database connection failed']);
  exit();
}

if (!isset($_REQUEST['token']) || $_REQUEST['token'] != 'b83ba7a49b72') {
  echo json_encode(['error' => 'not authorized']);
  exit();
}

$accountLabel = $accountMap[$requestDb] ?? $requestDb;

if (!isset($_REQUEST['search']) || trim($_REQUEST['search']) === '') {
  echo json_encode(['error' => 'no search value']);
  exit();
}
$search = addslashes(trim($_REQUEST['search']));

$db->bind = [];
$stmt = $db->run("
  SELECT j.*, jt.name AS type_name
  FROM os_jobs j
  LEFT JOIN os_jobs_type jt ON jt.id = j.job_type
  WHERE (j.is_deleted = 0 OR j.is_deleted IS NULL)
    AND (
      j.name LIKE '%{$search}%'
      OR j.email LIKE '%{$search}%'
      OR j.address_city LIKE '%{$search}%'
      OR j.address_area LIKE '%{$search}%'
      OR jt.name LIKE '%{$search}%'
      OR j.id IN (
        SELECT jp.job_id FROM os_jobs_phones jp
        WHERE jp.phone_number LIKE '%{$search}%'
           OR jp.employee_name LIKE '%{$search}%'
      )
    )
  ORDER BY j.name ASC
  LIMIT 200
");
$rows = ($stmt && is_object($stmt)) ? $stmt->fetchAll() : [];

// جلب أرقام الهواتف لكل الوظائف دفعة واحدة
$jobIds = array_column($rows, 'id');
$phonesMap = [];
if (!empty($jobIds)) {
  $safeIds = implode(',', array_map('intval', $jobIds));
  try {
    $db->bind = [];
    $stmtPhones = $db->run("
      SELECT job_id, phone_number, phone_type, employee_name, employee_position
      FROM os_jobs_phones
      WHERE job_id IN ({$safeIds})
      ORDER BY id ASC
    ");
    if ($stmtPhones && is_object($stmtPhones)) {
      foreach ($stmtPhones->fetchAll() as $ph) {
        $phonesMap[(int)$ph['job_id']][] = [
          'phone'    => $ph['phone_number'] ?? '',
          'type'     => $ph['phone_type'] ?? '',
          'name'     => $ph['employee_name'] ?? '',
          'position' => $ph['employee_position'] ?? '',
        ];
      }
    }
  } catch (Exception $e) {}
}

// جلب أوقات العمل دفعة واحدة
$hoursMap = [];
if (!empty($jobIds)) {
  try {
    $db->bind = [];
    $stmtHours = $db->run("
      SELECT job_id, day_of_week, opening_time, closing_time, is_closed, notes
      FROM os_jobs_working_hours
      WHERE job_id IN ({$safeIds})
      ORDER BY day_of_week ASC
    ");
    if ($stmtHours && is_object($stmtHours)) {
      $dayNames = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
      foreach ($stmtHours->fetchAll() as $wh) {
        $dayIdx = (int)($wh['day_of_week'] ?? 0);
        $hoursMap[(int)$wh['job_id']][] = [
          'day'     => $dayNames[$dayIdx] ?? $dayIdx,
          'open'    => $wh['opening_time'] ?? '',
          'close'   => $wh['closing_time'] ?? '',
          'closed'  => (int)($wh['is_closed'] ?? 0),
          'notes'   => $wh['notes'] ?? '',
        ];
      }
    }
  } catch (Exception $e) {}
}

// عدد العملاء المرتبطين بكل وظيفة
$custCountMap = [];
if (!empty($jobIds)) {
  try {
    $db->bind = [];
    $stmtCust = $db->run("
      SELECT job_title, COUNT(*) AS cnt
      FROM os_customers
      WHERE (is_deleted = 0 OR is_deleted IS NULL)
        AND job_title IN ({$safeIds})
      GROUP BY job_title
    ");
    if ($stmtCust && is_object($stmtCust)) {
      foreach ($stmtCust->fetchAll() as $cc) {
        $custCountMap[(int)$cc['job_title']] = (int)$cc['cnt'];
      }
    }
  } catch (Exception $e) {}
}

$array = [];
foreach ($rows as $row) {
  $jid = (int)$row['id'];
  $lat = $row['latitude'] ?? null;
  $lng = $row['longitude'] ?? null;
  $mapUrl = (!empty($lat) && !empty($lng)) ? "https://www.google.com/maps?q={$lat},{$lng}" : '';

  $addressParts = array_filter([
    $row['address_city'] ?? '',
    $row['address_area'] ?? '',
    $row['address_street'] ?? '',
    $row['address_building'] ?? '',
  ]);
  $fullAddress = implode('، ', $addressParts);

  $array[] = [
    'account'        => $accountLabel,
    'id'             => $jid,
    'name'           => $row['name'] ?? '',
    'type'           => $row['type_name'] ?? '',
    'email'          => $row['email'] ?? '',
    'website'        => $row['website'] ?? '',
    'notes'          => $row['notes'] ?? '',
    'status'         => (int)($row['status'] ?? 0),
    'phones'         => $phonesMap[$jid] ?? [],
    'address'        => $fullAddress,
    'address_city'   => $row['address_city'] ?? '',
    'address_area'   => $row['address_area'] ?? '',
    'address_street' => $row['address_street'] ?? '',
    'address_building' => $row['address_building'] ?? '',
    'postal_code'    => $row['postal_code'] ?? '',
    'plus_code'      => $row['plus_code'] ?? '',
    'latitude'       => $lat,
    'longitude'      => $lng,
    'map_url'        => $mapUrl,
    'working_hours'  => $hoursMap[$jid] ?? [],
    'customers_count' => $custCountMap[$jid] ?? 0,
  ];
}

echo json_encode($array, JSON_UNESCAPED_UNICODE);
