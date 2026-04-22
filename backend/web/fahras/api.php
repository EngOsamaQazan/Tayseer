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
$baseUrl = $baseUrlMap[$requestDb] ?? '';

// Phase 4 / M4.1 — single source of truth (no more 3-copy drift).
require_once __DIR__ . '/_doc_types.php';
// $docTypes is now provided by _doc_types.php (see GroupNameRegistry).

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

/**
 * تطبيع مقطع من الاسم — نفس منطق CustomersSearch::applyNormalizedNameFilter
 */
function fahras_normalize_name_fragment($w) {
  $wNorm = str_replace(['أ', 'إ', 'آ'], 'ا', $w);
  $wNorm = str_replace('ة', 'ه', $wNorm);
  $wNorm = str_replace('ى', 'ي', $wNorm);
  return $wNorm;
}

if ($action === 'search') {
  if (!isset($_REQUEST['search']) || trim($_REQUEST['search']) === '') {
    echo json_encode(['error' => 'no client name value']);
    exit();
  }

  $searchRaw = trim($_REQUEST['search']);
  $words = preg_split('/\s+/u', $searchRaw, -1, PREG_SPLIT_NO_EMPTY);

  if (empty($words)) {
    echo json_encode(['error' => 'no client name value']);
    exit();
  }

  /* اسم مُطبَّع للمقارنة — مطابق لـ os_customers في شاشة العملاء */
  $nameNorm = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(cu.name, 'ة', 'ه'), 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ى', 'ي')";

  $nameConds = [];
  $bind = [];
  foreach ($words as $w) {
    $wNorm = fahras_normalize_name_fragment($w);
    $nameConds[] = "($nameNorm LIKE ?)";
    $bind[] = '%' . $wNorm . '%';
  }
  $nameClause = implode(' AND ', $nameConds);

  /* البحث بالهوية أو الجوال: الجملة كاملة (مقطع واحد أو رقم كامل) */
  $fullLike = '%' . $searchRaw . '%';
  $bind[] = $fullLike;
  $bind[] = $fullLike;

  $whereSearch = "($nameClause OR cu.id_number LIKE ? OR cu.primary_phone_number LIKE ?)";

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
      COALESCE(vb.total_expenses, 0) AS expenses_sum,
      COALESCE(vb.total_lawyer_cost, 0) AS lawyer_sum,
      COALESCE(vb.total_paid, 0) AS paid_sum,
      COALESCE(vb.total_adjustments, 0) AS adjustments_sum,
      COALESCE(vb.judiciary_case_count, 0) AS court_cases
    FROM os_customers cu
    INNER JOIN os_contracts_customers cc ON cc.customer_id = cu.id
    INNER JOIN os_contracts co ON co.id = cc.contract_id
    LEFT JOIN os_vw_contract_balance vb ON vb.contract_id = co.id
    WHERE co.status != 'canceled'
      AND $whereSearch
    ORDER BY co.created_at ASC
    LIMIT 200
  ", $bind);
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
      COALESCE(vb.total_expenses, 0) AS expenses_sum,
      COALESCE(vb.total_lawyer_cost, 0) AS lawyer_sum,
      COALESCE(vb.total_paid, 0) AS paid_sum,
      COALESCE(vb.total_adjustments, 0) AS adjustments_sum,
      COALESCE(vb.judiciary_case_count, 0) AS court_cases
    FROM os_customers cu
    INNER JOIN os_contracts_customers cc ON cc.customer_id = cu.id
    INNER JOIN os_contracts co ON co.id = cc.contract_id
    LEFT JOIN os_vw_contract_balance vb ON vb.contract_id = co.id
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
    // Phase 4 / M4.1 — also include the new unified entity_type/entity_id
    // columns so rows written via MediaService surface in Fahras even if
    // the legacy customer_id/contractId columns are not populated (they
    // will be dropped in M8). Soft-deleted rows are excluded.
    $stmtImg = $db->run("
      SELECT id, fileName, fileHash, groupName, created
      FROM os_ImageManager
      WHERE deleted_at IS NULL
        AND (
              customer_id = {$custId}
           OR CAST(contractId AS UNSIGNED) = {$custId}
           OR (entity_type = 'customer' AND entity_id = {$custId})
        )
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
          'type' => $docTypes[$gn] ?? fahras_doc_label($gn),
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
