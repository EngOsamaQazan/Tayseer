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
$db_pass = 'OsamaDB123';

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

$baseUrlMap = [
  'jadal' => 'https://jadal.aqssat.co',
  'namaa' => 'https://namaa.aqssat.co',
  'erp'   => 'https://namaa.aqssat.co',
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
$baseUrl = $baseUrlMap[$requestDb] ?? '';

$docTypes = [
  '0' => 'هوية وطنية',       '1' => 'جواز سفر',       '2' => 'رخصة قيادة',
  '3' => 'شهادة ميلاد',      '4' => 'شهادة تعيين',     '5' => 'كتاب ضمان اجتماعي',
  '6' => 'كشف راتب',         '7' => 'شهادة تعيين عسكري','8' => 'صورة شخصية',
  '9' => 'غير محدد',
  'coustmers'  => 'وثيقة عميل',
  'customers'  => 'وثيقة عميل',
  'contracts'  => 'وثيقة عقد',
  'smart_media'=> 'وسائط ذكية',
];

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
      cc.customer_type,
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
    INNER JOIN os_contracts_customers cc ON cc.customer_id = cu.id
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
  // ─── تصدير جماعي: كل العقود وكل أطرافها (عملاء + كفلاء) ───
  $db->bind = [];
  $stmt = $db->run("
    SELECT
      cu.id AS customer_id,
      cu.name,
      cu.id_number,
      cu.primary_phone_number,
      cu.job_title,
      cc.customer_type,
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
    INNER JOIN os_contracts_customers cc ON cc.customer_id = cu.id
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

$partyTypeMap = [
  'client'    => 'عميل',
  'guarantor' => 'كفيل',
  'partner'   => 'شريك',
  'witness'   => 'شاهد',
];

foreach ($rows as $row) {
  $custId     = (int)$row['customer_id'];
  $contractId = (int)$row['contract_id'];

  // المعادلة المُعتمدة — متطابقة مع ContractCalculations::remainingAmount()
  // المتبقي = max(0, round(الإجمالي − المدفوع − الخصومات, 2))
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

  // المرفقات — جلب الروابط الكاملة من نظامي الرفع
  $images = [];
  try {
    $db->bind = [];
    $stmtImg = $db->run("
      SELECT id, fileName, fileHash, groupName, created
      FROM os_ImageManager
      WHERE customer_id = {$custId}
         OR CAST(contractId AS UNSIGNED) = {$custId}
      ORDER BY created DESC
    ");
    if ($stmtImg && is_object($stmtImg)) {
      foreach ($stmtImg->fetchAll() as $img) {
        $ext = pathinfo($img['fileName'] ?? '', PATHINFO_EXTENSION);
        $imgUrl = $baseUrl . '/images/imagemanager/' . $img['id'] . '_' . $img['fileHash'] . ($ext ? '.' . $ext : '');
        $gn = $img['groupName'] ?? '9';
        $images[] = [
          'id'   => (int)$img['id'],
          'url'  => $imgUrl,
          'type' => $docTypes[$gn] ?? 'أخرى',
          'type_code' => $gn,
          'file_name' => $img['fileName'] ?? '',
          'date' => $img['created'] ?? '',
        ];
      }
    }
  } catch (Exception $e) {}

  $rawType = $row['customer_type'] ?? 'client';
  $partyLabel = $partyTypeMap[$rawType] ?? $rawType;

  $array[] = [
    'account'          => $accountLabel,
    'cid'              => $custId,
    'id'               => (string)$contractId,
    'name'             => $row['name'] ?? '',
    'national_id'      => $row['id_number'] ?? '',
    'phone'            => $row['primary_phone_number'] ?? '',
    'party_type'       => $partyLabel,
    'work'             => $jobName,
    'home_address'     => $homeAddr,
    'work_address'     => $workAddr,
    'status'           => $statusMap[$row['status'] ?? ''] ?? ($row['status'] ?? ''),
    'sell_date'        => $row['Date_of_sale'] ?? '',
    'created_on'       => $row['contract_created_at'] ?? '',
    'remaining_amount' => $remaining,
    'court_status'     => ((int)$row['court_cases'] > 0) ? 'مشتكى عليه' : 'غير مشتكى عليه',
    'attachments'      => count($images),
    'images'           => $images,
  ];
}

echo json_encode($array, JSON_UNESCAPED_UNICODE);
