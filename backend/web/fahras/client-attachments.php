<?php
/**
 * Fahras Client Attachments — مرفقات العميل (صور)
 * يعرض صور العميل من os_ImageManager مباشرة من قاعدة البيانات
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
$db_pass = 'OsamaDB123';

// الفهرس يرسل db=jadal أو db=namaa للمرفقات
$dbMap = [
  'jadal' => 'namaa_jadal',
  'namaa' => 'namaa_erp',
  'erp'   => 'namaa_erp',
  'watar' => 'tayseer_watar',
];

// عنوان URL الأساسي لكل شركة
$baseUrlMap = [
  'jadal' => 'https://jadal.aqssat.co/images/imagemanager/',
  'namaa' => 'https://namaa.aqssat.co/images/imagemanager/',
  'erp'   => 'https://namaa.aqssat.co/images/imagemanager/',
  'watar' => 'https://watar.aqssat.co/images/imagemanager/',
];

$requestDb = $_GET['db'] ?? '';
$db_name = $dbMap[$requestDb] ?? null;
$baseUrl = $baseUrlMap[$requestDb] ?? '';

if (!$db_name) {
  echo '<div class="alert alert-danger">معرّف قاعدة البيانات غير صحيح</div>';
  exit();
}

$custId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($custId <= 0) {
  echo '<div class="alert alert-warning">لم يتم تحديد العميل</div>';
  exit();
}

try {
  $db = new smplPDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
} catch (Exception $e) {
  echo '<div class="alert alert-danger">فشل الاتصال بقاعدة البيانات</div>';
  exit();
}

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

$images = [];
try {
  $db->bind = [];
  $stmt = $db->run("
    SELECT id, fileName, fileHash, groupName
    FROM os_ImageManager
    WHERE customer_id = {$custId}
       OR CAST(contractId AS UNSIGNED) = {$custId}
    ORDER BY id DESC
    LIMIT 50
  ");
  if ($stmt && is_object($stmt)) {
    $images = $stmt->fetchAll();
  }
} catch (Exception $e) {
  $images = [];
}

$count = 0;

if (!empty($images)) {
  echo '<div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:center;">';
  foreach ($images as $img) {
    $fileHash = $img['fileHash'] ?? '';
    $fileName = $img['fileName'] ?? '';
    $imgId    = $img['id'] ?? 0;
    if (empty($fileHash) || empty($imgId)) continue;

    $ext    = pathinfo($fileName, PATHINFO_EXTENSION);
    $imgUrl = $baseUrl . $imgId . '_' . $fileHash . ($ext ? '.' . $ext : '');
    $gn     = $img['groupName'] ?? '9';
    $label  = $docTypes[$gn] ?? 'أخرى';
    $count++;

    echo '<div style="text-align:center;margin-bottom:10px;">';
    echo '<a href="' . htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank">';
    echo '<img style="max-width:300px;max-height:300px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.3);" '
       . 'src="' . htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') . '" onerror="this.parentNode.parentNode.style.display=\'none\'" />';
    echo '</a>';
    echo '<div style="margin-top:6px;font-size:12px;color:#93c5fd;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</div>';
    echo '</div>';
  }
  echo '</div>';
}

if ($count == 0) {
  echo '<div class="alert alert-info" style="text-align:center;">لم يتم العثور على أي مرفقات لهذا العميل</div>';
}
