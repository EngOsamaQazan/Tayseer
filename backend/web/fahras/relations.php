<?php
/**
 * Fahras Relations API — أطراف العقد
 * يعرض جميع الأطراف المرتبطة بعقد معين (المشتري، الكفلاء، إلخ)
 */
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Amman");

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

require('db.php');
$db_host = 'localhost';
$db_user = 'osama';
$db_pass = 'O$amaDaTaBase@123';

// تحويل اسم الشركة لاسم قاعدة البيانات الصحيح
$dbMap = [
  'jadal' => 'namaa_jadal',
  'namaa' => 'namaa_erp',
  'erp'   => 'namaa_erp',
];

$requestDb = $_REQUEST['db'] ?? '';
$db_name = $dbMap[$requestDb] ?? null;

if (!$db_name) {
  echo '<div class="alert alert-danger">معرّف قاعدة البيانات غير صحيح</div>';
  exit();
}

try {
  $db = new smplPDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
} catch (Exception $e) {
  echo '<div class="alert alert-danger">فشل الاتصال بقاعدة البيانات</div>';
  exit();
}

if (!isset($_REQUEST['token']) || $_REQUEST['token'] != 'b83ba7a49b72') {
  echo '<div class="alert alert-danger">غير مصرح</div>';
  exit();
}

$contractId = isset($_REQUEST['contract']) ? (int)$_REQUEST['contract'] : 0;
$clientId = isset($_REQUEST['client']) ? (int)$_REQUEST['client'] : 0;

if ($contractId <= 0 && $clientId <= 0) {
  echo '<div class="alert alert-warning">لم يتم تحديد العقد أو العميل</div>';
  exit();
}

// جلب أطراف العقد
$parties = [];

if ($contractId > 0) {
  // جلب جميع العملاء المرتبطين بهذا العقد
  try {
    $db->bind = [];
    $stmt = $db->run("
      SELECT cc.customer_id, cc.contract_id, c.name, c.id_number, c.primary_phone_number,
             j.name AS job_name
      FROM os_contracts_customers cc
      LEFT JOIN os_customers c ON cc.customer_id = c.id
      LEFT JOIN os_jobs j ON c.job_title = j.id
      WHERE cc.contract_id = " . $contractId . "
      ORDER BY cc.id ASC
    ");
    if ($stmt && is_object($stmt)) {
      $parties = $stmt->fetchAll();
    }
  } catch (Exception $e) {
    $parties = [];
  }
} elseif ($clientId > 0) {
  // جلب جميع العقود المرتبطة بالعميل ثم جميع الأطراف
  try {
    $db->bind = [];
    $stmtContracts = $db->run("SELECT GROUP_CONCAT(DISTINCT contract_id) AS cids FROM os_contracts_customers WHERE customer_id = " . $clientId);
    $cids = ($stmtContracts && is_object($stmtContracts)) ? $stmtContracts->fetchColumn() : '';
    
    if (!empty($cids)) {
      $safeIds = implode(',', array_map('intval', explode(',', $cids)));
      $db->bind = [];
      $stmt = $db->run("
        SELECT cc.customer_id, cc.contract_id, c.name, c.id_number, c.primary_phone_number,
               j.name AS job_name
        FROM os_contracts_customers cc
        LEFT JOIN os_customers c ON cc.customer_id = c.id
        LEFT JOIN os_jobs j ON c.job_title = j.id
        WHERE cc.contract_id IN ({$safeIds})
        ORDER BY cc.contract_id ASC, cc.id ASC
      ");
      if ($stmt && is_object($stmt)) {
        $parties = $stmt->fetchAll();
      }
    }
  } catch (Exception $e) {
    $parties = [];
  }
}

if (isset($_REQUEST['format']) && $_REQUEST['format'] === 'json') {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['data' => $parties ?: []], JSON_UNESCAPED_UNICODE);
  exit();
}

if (empty($parties)) {
  echo '<div class="alert alert-info">لا يوجد أطراف مرتبطة بهذا العقد</div>';
  exit();
}
?>

<table class="table table-hover table-bordered table-striped">
  <thead>
    <tr>
      <th>#</th>
      <th>رقم العقد</th>
      <th>اسم الطرف</th>
      <th>رقم الهوية</th>
      <th>الهاتف</th>
      <th>الوظيفة</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $idx = 0;
  $seen = []; // لمنع التكرار
  foreach ($parties as $p) {
    // تجنب تكرار نفس العميل لنفس العقد
    $key = ($p['contract_id'] ?? '') . '-' . ($p['customer_id'] ?? '');
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    $idx++;
    
    // تمييز العميل الحالي
    $isCurrent = ((int)($p['customer_id'] ?? 0) === $clientId);
    $rowStyle = $isCurrent ? ' style="background:#fff3cd;"' : '';
    
    echo '<tr' . $rowStyle . '>';
    echo '<td>' . $idx . '</td>';
    echo '<td>' . htmlspecialchars($p['contract_id'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8');
    if ($isCurrent) echo ' <small>(العميل المحدد)</small>';
    echo '</td>';
    echo '<td>' . htmlspecialchars($p['id_number'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td dir="ltr">' . htmlspecialchars($p['primary_phone_number'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($p['job_name'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
    echo '</tr>';
  }
  ?>
  </tbody>
</table>
