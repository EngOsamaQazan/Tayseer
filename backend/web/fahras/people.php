<?php
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
  header('Content-Type: application/json');
  echo json_encode(['error' => 'invalid db parameter']);
  exit();
}

try {
  $db = new smplPDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
} catch (Exception $e) {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'database connection failed']);
  exit();
}

if (!isset($_REQUEST['token'])) {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'no token value']);
  exit();
}

if ($_REQUEST['token'] != 'b83ba7a49b72') {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'not authorized']);
  exit();
}

if (!isset($_REQUEST['client'])) {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'no client name value']);
  exit();
}

$clientId = (int)$_GET['client'];
$db->bind = [];
$stmt = $db->run("SELECT * FROM `os_phone_numbers` WHERE `customers_id` = " . $clientId);
$result = $stmt ? $stmt->fetchAll() : [];

?>

<table class="table table-hover table-bordered table-striped">
  <thead>
    <tr>
      <th>الهاتف</th>
      <th>مالك الهاتف</th>
      <th>صلة القرابة</th>
      <th>فيسبوك</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($result as $key) {
    $cousin = $db->get_var('os_cousins', array('id' => $key['phone_number_owner']), array('name')) ?: '';
    echo '
    <tr>
      <td>'.htmlspecialchars($key['phone_number']).'</td>
      <td>'.htmlspecialchars($key['owner_name']).'</td>
      <td>'.htmlspecialchars($cousin).'</td>
      <td>'.htmlspecialchars($key['fb_account'] ?? '').'</td>
    </tr>
    ';
  } ?>
  </tbody>
</table>
