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

require_once __DIR__ . '/_companies.php';

$requestDb = $_GET['db'] ?? '';
$db_name = $dbMap[$requestDb] ?? null;
$baseUrl = isset($baseUrlMap[$requestDb]) ? $baseUrlMap[$requestDb] . '/images/imagemanager/' : '';

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

// Phase 4 / M4.1 — single source of truth (no more 3-copy drift).
require_once __DIR__ . '/_doc_types.php';
// $docTypes is now provided by _doc_types.php (see GroupNameRegistry).

$images = [];
try {
  $db->bind = [];
  // Phase 4 / M4.1 — also include rows written via MediaService
  // (entity_type='customer'). Skip soft-deleted rows.
  $stmt = $db->run("
    SELECT id, fileName, fileHash, groupName
    FROM os_ImageManager
    WHERE deleted_at IS NULL
      AND (
            customer_id = {$custId}
         OR CAST(contractId AS UNSIGNED) = {$custId}
         OR (entity_type = 'customer' AND entity_id = {$custId})
      )
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
    $label  = $docTypes[$gn] ?? fahras_doc_label($gn);
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
