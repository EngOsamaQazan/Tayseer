<?php
/**
 * سكريبت تصدير الصور من ImageManager
 * ────────────────────────────────────
 * يُنشئ ملف ZIP يحتوي على جميع الصور مرتبة بفولدرات حسب تاريخ الرفع
 * مع تسمية وصفية تتضمن رقم العميل واسمه
 *
 * الاستخدام: https://domain.com/export-images.php?key=SECRET_KEY
 * اختياري:   &from=2022-01-01&to=2026-12-31  (فلترة بالتاريخ)
 *            &group=coustmers                  (فلترة بنوع المجموعة)
 */

// ── إعدادات الأمان ──
$SECRET_KEY = 'TayseerExport2026!@#';

if (($_GET['key'] ?? '') !== $SECRET_KEY) {
    http_response_code(403);
    die(json_encode(['error' => 'مفتاح الوصول غير صحيح', 'usage' => '?key=YOUR_KEY']));
}

// ── تحميل Yii2 للاتصال بقاعدة البيانات ──
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../../backend/config/main.php',
    require __DIR__ . '/../../backend/config/main-local.php'
);

// نحتاج فقط الـ DB component
new yii\web\Application($config);

set_time_limit(600); // 10 دقائق
ini_set('memory_limit', '1G');

// ── فلاتر اختيارية ──
$dateFrom  = $_GET['from']  ?? null;
$dateTo    = $_GET['to']    ?? null;
$groupName = $_GET['group'] ?? null;

$db = Yii::$app->db;

// ── استعلام الصور مع بيانات العميل ──
$sql = "
    SELECT 
        im.id,
        im.fileName,
        im.fileHash,
        im.contractId,
        im.groupName,
        DATE(im.created) AS upload_date,
        im.created,
        c.name AS customer_name,
        c.id AS real_customer_id
    FROM os_ImageManager im
    LEFT JOIN os_customers c ON CAST(im.contractId AS UNSIGNED) = c.id AND im.groupName = 'coustmers'
    WHERE 1=1
";

$params = [];

if ($dateFrom) {
    $sql .= " AND DATE(im.created) >= :dateFrom";
    $params[':dateFrom'] = $dateFrom;
}
if ($dateTo) {
    $sql .= " AND DATE(im.created) <= :dateTo";
    $params[':dateTo'] = $dateTo;
}
if ($groupName) {
    $sql .= " AND im.groupName = :groupName";
    $params[':groupName'] = $groupName;
}

$sql .= " ORDER BY im.created ASC";

$images = $db->createCommand($sql, $params)->queryAll();
$totalImages = count($images);

if ($totalImages === 0) {
    die(json_encode(['error' => 'لا توجد صور بالفلاتر المحددة', 'total' => 0]));
}

// ── مسار الصور الأصلية ──
$imagesDir = __DIR__ . '/images/imagemanager';

if (!is_dir($imagesDir)) {
    die(json_encode(['error' => "مجلد الصور غير موجود: $imagesDir"]));
}

// ── إعداد مجلد التصدير المؤقت ──
$exportId   = 'export_' . date('Y-m-d_His') . '_' . substr(md5(uniqid()), 0, 6);
$exportDir  = sys_get_temp_dir() . '/' . $exportId;
$zipFile    = __DIR__ . '/' . $exportId . '.zip';

// ── إنشاء ملف INDEX.txt يحتوي جدول بكل الصور ──
$indexContent = "═══════════════════════════════════════════════════════════\n";
$indexContent .= "  تقرير تصدير صور النظام\n";
$indexContent .= "  تاريخ التصدير: " . date('Y-m-d H:i:s') . "\n";
$indexContent .= "  إجمالي الصور: $totalImages\n";
if ($dateFrom) $indexContent .= "  من تاريخ: $dateFrom\n";
if ($dateTo)   $indexContent .= "  إلى تاريخ: $dateTo\n";
$indexContent .= "═══════════════════════════════════════════════════════════\n\n";

$found = 0;
$missing = 0;
$orphans = 0;

// ── نسخ الصور إلى فولدرات حسب التاريخ ──
foreach ($images as $img) {
    $ext = strtolower(pathinfo($img['fileName'], PATHINFO_EXTENSION));
    if (empty($ext)) $ext = 'jpg';
    
    $sourceFile = $imagesDir . '/' . $img['id'] . '_' . $img['fileHash'] . '.' . $ext;
    
    // تحديد التاريخ للفولدر
    $date = $img['upload_date'] ?: 'unknown_date';
    $dateDir = $exportDir . '/' . $date;
    
    if (!is_dir($dateDir)) {
        mkdir($dateDir, 0755, true);
    }
    
    // بناء اسم الملف الوصفي
    $isOrphan = false;
    if ($img['groupName'] === 'coustmers') {
        if ($img['customer_name']) {
            // مرتبط بعميل حقيقي
            $cleanName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $img['customer_name']);
            $cleanName = trim($cleanName);
            $label = "عميل_{$img['contractId']}_{$cleanName}";
        } else {
            // يتيم — contractId لا يطابق أي عميل
            $label = "يتيم_contractId_{$img['contractId']}";
            $isOrphan = true;
            $orphans++;
        }
    } elseif ($img['groupName'] === 'contracts') {
        $label = "عقد_{$img['contractId']}";
    } else {
        $label = "مجموعة_{$img['groupName']}_{$img['contractId']}";
    }
    
    $destFileName = "[ID{$img['id']}]_[{$label}]." . $ext;
    $destFile = $dateDir . '/' . $destFileName;
    
    if (file_exists($sourceFile)) {
        copy($sourceFile, $destFile);
        $found++;
        $status = "✓ موجود";
    } else {
        // إنشاء ملف نصي بدل الصورة المفقودة
        file_put_contents($destFile . '.MISSING.txt', 
            "الصورة مفقودة من السيرفر!\n" .
            "ID: {$img['id']}\n" .
            "اسم الملف الأصلي: {$img['fileName']}\n" .
            "المسار المتوقع: $sourceFile\n" .
            "تاريخ الرفع: {$img['created']}\n"
        );
        $missing++;
        $status = "✗ مفقود";
    }
    
    // إضافة للفهرس
    $indexContent .= sprintf(
        "[%s] ID:%-6s | %s | %s | %s | %s\n",
        $status,
        $img['id'],
        $date,
        str_pad($img['groupName'] ?? '-', 10),
        str_pad("contractId={$img['contractId']}", 25),
        $img['customer_name'] ?: ($isOrphan ? '⚠ يتيم (لا يوجد عميل بهذا الرقم)' : '-')
    );
}

// ── ملخص الفهرس ──
$indexContent .= "\n═══════════════════════════════════════════════════════════\n";
$indexContent .= "  ملخص:\n";
$indexContent .= "  صور موجودة: $found\n";
$indexContent .= "  صور مفقودة: $missing\n";
$indexContent .= "  صور يتيمة (بدون عميل): $orphans\n";
$indexContent .= "═══════════════════════════════════════════════════════════\n";

// حفظ الفهرس
if (!is_dir($exportDir)) {
    mkdir($exportDir, 0755, true);
}
file_put_contents($exportDir . '/INDEX.txt', $indexContent);

// ── إنشاء ملف ORPHANS.csv للصور اليتيمة ──
$orphansCsv = "ImageManager_ID,contractId,fileName,upload_date,fileExists\n";
foreach ($images as $img) {
    if ($img['groupName'] === 'coustmers' && !$img['customer_name']) {
        $ext = strtolower(pathinfo($img['fileName'], PATHINFO_EXTENSION));
        if (empty($ext)) $ext = 'jpg';
        $sourceFile = $imagesDir . '/' . $img['id'] . '_' . $img['fileHash'] . '.' . $ext;
        $exists = file_exists($sourceFile) ? 'YES' : 'NO';
        $orphansCsv .= "{$img['id']},{$img['contractId']},\"{$img['fileName']}\",{$img['upload_date']},$exists\n";
    }
}
file_put_contents($exportDir . '/ORPHANS.csv', "\xEF\xBB\xBF" . $orphansCsv); // BOM for Excel

// ── ضغط كل شيء في ZIP ──
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die(json_encode(['error' => 'فشل في إنشاء ملف ZIP']));
}

$directoryIterator = new RecursiveDirectoryIterator($exportDir, RecursiveDirectoryIterator::SKIP_DOTS);
$fileIterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::LEAVES_ONLY);

foreach ($fileIterator as $file) {
    if (!$file->isDir()) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($exportDir) + 1);
        $zip->addFile($filePath, $relativePath);
    }
}

$zip->close();

// ── تنظيف المجلد المؤقت ──
function deleteDir($dir) {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            deleteDir($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}
deleteDir($exportDir);

// ── إرجاع ملف ZIP للتحميل ──
$zipSize = filesize($zipFile);
$zipSizeMB = round($zipSize / 1024 / 1024, 2);

// إذا طلب المستخدم معاينة فقط
if (isset($_GET['preview'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'total_images'    => $totalImages,
        'found_on_disk'   => $found,
        'missing_files'   => $missing,
        'orphan_images'   => $orphans,
        'zip_size_mb'     => $zipSizeMB,
        'download_url'    => basename($zipFile),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// تحميل مباشر
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="images_export_' . date('Y-m-d') . '.zip"');
header('Content-Length: ' . $zipSize);
header('Cache-Control: no-cache, must-revalidate');
readfile($zipFile);

// حذف ملف ZIP بعد التحميل
unlink($zipFile);
exit;
