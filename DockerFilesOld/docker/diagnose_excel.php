<?php
/**
 * أداة تشخيص ملفات Excel البنكية
 * الاستخدام: php /var/www/html/docker/diagnose_excel.php /path/to/file.xlsx
 */

if ($argc < 2) {
    echo "Usage: php diagnose_excel.php <excel-file-path>\n";
    exit(1);
}

$filePath = $argv[1];
if (!file_exists($filePath)) {
    echo "File not found: $filePath\n";
    exit(1);
}

require_once '/var/www/html/vendor/autoload.php';

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
echo "=== Excel File Diagnostic ===\n";
echo "File: $filePath\n";
echo "Extension: $ext\n";
echo "Size: " . number_format(filesize($filePath)) . " bytes\n\n";

// Read the file
if ($ext === 'xlsx') {
    $reader = new PHPExcel_Reader_Excel2007();
} elseif ($ext === 'xls') {
    $reader = new PHPExcel_Reader_Excel5();
} else {
    echo "Unknown extension: $ext\n";
    exit(1);
}

$excel = $reader->load($filePath);
$sheet = $excel->getActiveSheet();
$data  = $sheet->toArray(null, true, true, true);

echo "Total rows: " . count($data) . "\n";
echo "Highest column: " . $sheet->getHighestColumn() . "\n";
echo "Highest row: " . $sheet->getHighestRow() . "\n\n";

// Show first 15 rows
echo "=== First 15 Rows (formatted) ===\n";
for ($r = 1; $r <= min(15, count($data)); $r++) {
    if (!isset($data[$r])) continue;
    echo "Row $r: ";
    $cells = [];
    foreach ($data[$r] as $col => $val) {
        $v = trim((string)$val);
        if (!empty($v)) {
            $cells[] = "$col=\"" . mb_substr($v, 0, 40) . "\"";
        }
    }
    echo implode(' | ', $cells) ?: '(empty)';
    echo "\n";
}

// Show raw cell types for first 15 rows
echo "\n=== Cell Types (first 15 rows) ===\n";
for ($r = 1; $r <= min(15, count($data)); $r++) {
    $typeCells = [];
    foreach ($data[$r] as $col => $val) {
        if (!empty(trim((string)$val))) {
            $cell = $sheet->getCell($col . $r);
            $type = $cell->getDataType();
            $rawVal = $cell->getValue();
            $typeNames = ['s' => 'str', 'n' => 'num', 'b' => 'bool', 'f' => 'formula', 'inlineStr' => 'inline'];
            $typeName = $typeNames[$type] ?? $type;
            $typeCells[] = "$col=$typeName(" . mb_substr((string)$rawVal, 0, 30) . ")";
        }
    }
    if (!empty($typeCells)) {
        echo "Row $r: " . implode(' | ', $typeCells) . "\n";
    }
}

// Run the analyzer
echo "\n=== BankStatementAnalyzer Result ===\n";
require_once '/var/www/html/backend/modules/financialTransaction/helpers/BankStatementAnalyzer.php';

$analyzer = new \backend\modules\financialTransaction\helpers\BankStatementAnalyzer();
$analysis = $analyzer->analyze($data);

echo "Header Row: " . $analysis['headerRow'] . "\n";
echo "Data Start Row: " . $analysis['dataStartRow'] . "\n";
echo "Confidence: " . $analysis['confidence'] . "%\n";
echo "Mapping:\n";
foreach ($analysis['mapping'] as $field => $col) {
    $hdr = $analysis['originalHeaders'][$col] ?? '';
    echo "  $field => $col ($hdr)\n";
}
echo "Original Headers:\n";
foreach ($analysis['originalHeaders'] as $col => $name) {
    if (!empty(trim($name))) {
        echo "  $col: $name\n";
    }
}

// Parse first 5 rows
$rows = $analyzer->parseRows($analysis['mapping'], $analysis['dataStartRow'], 5);
echo "\nParsed Rows (first 5):\n";
foreach ($rows as $i => $row) {
    echo "  Row {$row['row_number']}: date={$row['date']}, amount={$row['amount']}, type={$row['type']}, "
       . "desc=" . mb_substr($row['description'] ?? '', 0, 30)
       . (empty($row['errors']) ? '' : ', ERRORS=' . implode('; ', $row['errors']))
       . ($row['openingBalance'] ? ' [OPENING BALANCE]' : '')
       . "\n";
}

$summary = $analyzer->calculateSummary($rows);
echo "\nSummary: " . json_encode($summary, JSON_PRETTY_PRINT) . "\n";
echo "\n=== Done ===\n";
