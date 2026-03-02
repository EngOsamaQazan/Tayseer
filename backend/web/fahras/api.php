<?php
/**
 * Fahras API — البحث عن العملاء وبيانات العقود
 * يُستدعى من نظام الفهرس المركزي
 *
 * يُرجع صف لكل عقد (وليس لكل عميل) لتمكين مقارنة العقود الفردية
 * عبر الشركات لكشف المخالفات بدقة
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
$db_pass = 'O$amaDaTaBase@123';

$dbMap = [
  'jadal' => 'namaa_jadal',
  'namaa' => 'namaa_erp',
  'erp'   => 'namaa_erp',
];

$accountMap = [
  'jadal' => 'جدل',
  'namaa' => 'نماء',
  'erp'   => 'نماء',
];

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

$statusMap = [
  'active'           => 'نشط',
  'finished'         => 'منتهي',
  'canceled'         => 'ملغي',
  'judiciary'        => 'قضائي',
  'settlement'       => 'تسوية',
  'legal_department' => 'قانوني',
  'pending'          => 'معلّق',
  'refused'          => 'مرفوض',
];

$action = $_REQUEST['action'] ?? 'search';

if ($action === 'search') {
  if (!isset($_REQUEST['search']) || trim($_REQUEST['search']) === '') {
    echo json_encode(['error' => 'no client name value']);
    exit();
  }
  $search = addslashes($_REQUEST['search']);

  $db->bind = [];
  $stmt = $db->run("
    SELECT
      cu.id AS customer_id,
      cu.name,
      cu.id_number,
      cu.primary_phone_number,
      cu.job_title,
      co.id AS contract_id,
      co.status,
      co.Date_of_sale,
      co.created_at AS contract_created_at,
      co.total_value,
      COALESCE((SELECT SUM(e.amount) FROM os_expenses e WHERE e.contract_id = co.id), 0) AS expenses_sum,
      COALESCE((SELECT SUM(j.lawyer_cost) FROM os_judiciary j WHERE j.contract_id = co.id AND j.is_deleted = 0), 0) AS lawyer_sum,
      COALESCE((SELECT SUM(i.amount) FROM os_income i WHERE i.contract_id = co.id), 0) AS paid_sum,
      COALESCE((SELECT SUM(a.amount) FROM os_contract_adjustments a WHERE a.contract_id = co.id AND a.is_deleted = 0), 0) AS adjustments_sum,
      (SELECT COUNT(*) FROM os_judiciary jc WHERE jc.contract_id = co.id AND jc.is_deleted = 0) AS court_cases
    FROM os_customers cu
    INNER JOIN os_contracts_customers cc ON cc.customer_id = cu.id AND cc.customer_type = 'client'
    INNER JOIN os_contracts co ON co.id = cc.contract_id
    WHERE co.status != 'canceled'
      AND (cu.name LIKE '%{$search}%'
       OR cu.id_number LIKE '%{$search}%'
       OR cu.primary_phone_number LIKE '%{$search}%')
    ORDER BY co.created_at ASC
    LIMIT 200
  ");
  $rows = ($stmt && is_object($stmt)) ? $stmt->fetchAll() : [];

} elseif ($action === 'bulk_export') {
  // ─── تصدير جماعي: كل العقود النشطة دفعة واحدة ───
  $db->bind = [];
  $stmt = $db->run("
    SELECT
      cu.id AS customer_id,
      cu.name,
      cu.id_number,
      cu.primary_phone_number,
      cu.job_title,
      co.id AS contract_id,
      co.status,
      co.Date_of_sale,
      co.created_at AS contract_created_at,
      co.total_value,
      COALESCE((SELECT SUM(e.amount) FROM os_expenses e WHERE e.contract_id = co.id), 0) AS expenses_sum,
      COALESCE((SELECT SUM(j.lawyer_cost) FROM os_judiciary j WHERE j.contract_id = co.id AND j.is_deleted = 0), 0) AS lawyer_sum,
      COALESCE((SELECT SUM(i.amount) FROM os_income i WHERE i.contract_id = co.id), 0) AS paid_sum,
      COALESCE((SELECT SUM(a.amount) FROM os_contract_adjustments a WHERE a.contract_id = co.id AND a.is_deleted = 0), 0) AS adjustments_sum,
      (SELECT COUNT(*) FROM os_judiciary jc WHERE jc.contract_id = co.id AND jc.is_deleted = 0) AS court_cases
    FROM os_customers cu
    INNER JOIN os_contracts_customers cc ON cc.customer_id = cu.id AND cc.customer_type = 'client'
    INNER JOIN os_contracts co ON co.id = cc.contract_id
    WHERE co.status != 'canceled'
    ORDER BY co.created_at ASC
  ");
  $rows = ($stmt && is_object($stmt)) ? $stmt->fetchAll() : [];

} else {
  echo json_encode(['error' => 'invalid action']);
  exit();
}

$array = [];

foreach ($rows as $row) {
  $custId     = (int)$row['customer_id'];
  $contractId = (int)$row['contract_id'];

  $totalDebt = (float)($row['total_value'] ?? 0)
             + (float)$row['expenses_sum']
             + (float)$row['lawyer_sum'];

  $totalPaid = (float)$row['paid_sum']
             + (float)$row['adjustments_sum'];

  $remaining = round(max(0, $totalDebt - $totalPaid), 2);

  // الوظيفة
  $jobName = '';
  $jobId = $row['job_title'] ?? 0;
  if (!empty($jobId)) {
    try {
      $jobName = $db->get_var('os_jobs', ['id' => $jobId], ['name']) ?: '';
    } catch (Exception $e) { $jobName = ''; }
  }

  // العناوين
  $homeAddr = '';
  $workAddr = '';
  try {
    $stmtHome = $db->run("SELECT GROUP_CONCAT(address SEPARATOR '##-##') FROM os_address WHERE address_type = 2 AND customers_id = " . $custId);
    $homeAddr = ($stmtHome && is_object($stmtHome)) ? ($stmtHome->fetchColumn() ?: '') : '';
  } catch (Exception $e) {}

  try {
    $stmtWork = $db->run("SELECT GROUP_CONCAT(address SEPARATOR '##-##') FROM os_address WHERE address_type = 1 AND customers_id = " . $custId);
    $workAddr = ($stmtWork && is_object($stmtWork)) ? ($stmtWork->fetchColumn() ?: '') : '';
  } catch (Exception $e) {}

  // المرفقات
  $attCount = 0;
  try {
    $attCount = (int)$db->get_count('os_ImageManager', ['customer_id' => $custId]);
  } catch (Exception $e) {
    try {
      $attCount = (int)$db->get_count('os_ImageManager', ['contractId' => $custId]);
    } catch (Exception $e2) { $attCount = 0; }
  }

  $array[] = [
    'account'          => $accountLabel,
    'cid'              => $custId,
    'id'               => (string)$contractId,
    'name'             => $row['name'] ?? '',
    'national_id'      => $row['id_number'] ?? '',
    'phone'            => $row['primary_phone_number'] ?? '',
    'work'             => $jobName,
    'home_address'     => $homeAddr,
    'work_address'     => $workAddr,
    'status'           => $statusMap[$row['status'] ?? ''] ?? ($row['status'] ?? ''),
    'sell_date'        => $row['Date_of_sale'] ?? '',
    'created_on'       => $row['contract_created_at'] ?? '',
    'remaining_amount' => $remaining,
    'court_status'     => ((int)$row['court_cases'] > 0) ? 'مشتكى عليه' : 'غير مشتكى عليه',
    'attachments'      => $attCount,
  ];
}

echo json_encode($array, JSON_UNESCAPED_UNICODE);
