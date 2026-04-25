<?php

namespace backend\services\judiciary;

use Yii;
use yii\web\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use backend\modules\contracts\models\Contracts;
use backend\modules\judiciary\models\Judiciary;

/**
 * Parses each of the three input methods on the wizard's first step into a
 * single canonical shape: a list of contract IDs that the second step then
 * displays in the preview table.
 *
 * Three callers feed it:
 *   - "Paste"   : free-form text from a textarea (mixed separators).
 *   - "Excel"   : .xlsx / .csv upload, with auto column detection.
 *   - "Selection": already a clean array of IDs, just validates them.
 *
 * Validation is shared across methods so the UI gives consistent feedback.
 */
class BatchInputParserService
{
    /** Supported header names for the contract-id column (case-insensitive). */
    private const ID_HEADER_CANDIDATES = [
        'id', 'contract_id', 'contractid',
        'رقم العقد', 'معرف العقد', 'رقم_العقد', 'رقم', 'عقد',
        'contract', 'الرقم'
    ];

    private const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;
    private const ALLOWED_EXTENSIONS = ['xlsx', 'csv'];

    /**
     * Split arbitrary user text into integer IDs.
     * Accepts commas, newlines, tabs, semicolons, spaces — any combination.
     */
    public function parsePaste(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') return [];

        // One regex to rule them all — split on any non-digit run.
        $tokens = preg_split('/[^0-9]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $ids = array_values(array_unique(array_map('intval', $tokens)));
        return array_values(array_filter($ids, fn($v) => $v > 0));
    }

    /**
     * Read .xlsx or .csv and extract the contract-id column.
     * Auto-detects the column from the header row; falls back to col A.
     *
     * @throws \InvalidArgumentException on bad upload / unsupported file / empty result
     */
    public function parseExcel(UploadedFile $file): array
    {
        if (!$file || $file->error !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('فشل رفع الملف');
        }
        if ($file->size > self::MAX_UPLOAD_BYTES) {
            throw new \InvalidArgumentException('حجم الملف يتجاوز 5 ميغا');
        }

        $ext = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException('الصيغة غير مدعومة. الصيغ المسموحة: ' . implode(', ', self::ALLOWED_EXTENSIONS));
        }

        try {
            $reader = $ext === 'csv'
                ? IOFactory::createReader('Csv')
                : IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->tempName);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('تعذّر قراءة الملف: ' . $e->getMessage());
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            throw new \InvalidArgumentException('الملف فارغ');
        }

        $columnIndex = $this->detectIdColumn($rows[0] ?? []);
        $startRow    = ($columnIndex === null || $columnIndex === -1) ? 0 : 1;
        if ($columnIndex === null || $columnIndex === -1) {
            $columnIndex = 0; // No header found — treat first column as IDs.
            $startRow = 0;
        }

        $ids = [];
        for ($r = $startRow; $r < count($rows); $r++) {
            $cell = $rows[$r][$columnIndex] ?? null;
            if ($cell === null || $cell === '') continue;
            $val = (int)preg_replace('/[^0-9]/u', '', (string)$cell);
            if ($val > 0) $ids[] = $val;
        }
        $ids = array_values(array_unique($ids));

        if (empty($ids)) {
            throw new \InvalidArgumentException('لم يتم استخراج أي رقم عقد صالح من الملف');
        }
        return $ids;
    }

    /**
     * Inspect a list of IDs and bucket each one. The wizard preview uses the
     * three buckets to render success/warning/error rows.
     *
     * @return array{
     *   valid: int[],
     *   invalid: int[],
     *   has_existing_case: array<int, array{contract_id:int, judiciary_id:int}>
     * }
     */
    public function validateContractIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_values(array_filter($ids, fn($v) => $v > 0));

        if (empty($ids)) {
            return ['valid' => [], 'invalid' => [], 'has_existing_case' => []];
        }

        $existing = (new \yii\db\Query())
            ->from(Contracts::tableName())
            ->select('id')
            ->where(['id' => $ids, 'is_deleted' => 0])
            ->column();
        $existing = array_map('intval', $existing);
        $invalid  = array_values(array_diff($ids, $existing));

        $withCase = (new \yii\db\Query())
            ->from(Judiciary::tableName())
            ->select(['contract_id', 'id'])
            ->where(['contract_id' => $existing, 'is_deleted' => 0])
            ->all();
        $byContract = [];
        foreach ($withCase as $row) {
            $byContract[(int)$row['contract_id']] = (int)$row['id'];
        }

        $valid = [];
        $hasCase = [];
        foreach ($existing as $cid) {
            if (isset($byContract[$cid])) {
                $hasCase[] = ['contract_id' => $cid, 'judiciary_id' => $byContract[$cid]];
            } else {
                $valid[] = $cid;
            }
        }

        return [
            'valid'             => $valid,
            'invalid'           => $invalid,
            'has_existing_case' => $hasCase,
        ];
    }

    /**
     * Build the preview-table payload from a list of valid contract IDs.
     * Pulls compact data the wizard needs: client names, remaining, dates.
     *
     * @return array<int, array>  keyed by contract_id
     */
    public function buildPreview(array $contractIds): array
    {
        if (empty($contractIds)) return [];

        $idList = implode(',', array_map('intval', $contractIds));
        $rows = Yii::$app->db->createCommand("
            SELECT id, total_value, Date_of_sale, client_names, total_paid, remaining_balance, status, company_id
            FROM {{%vw_contracts_overview}}
            WHERE id IN ($idList)
        ")->queryAll();

        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['id']] = [
                'contract_id'       => (int)$r['id'],
                'client_names'      => $r['client_names'],
                'total_value'       => (float)$r['total_value'],
                'total_paid'        => (float)$r['total_paid'],
                'remaining'         => (float)$r['remaining_balance'],
                'date_of_sale'      => $r['Date_of_sale'],
                'company_id'        => (int)$r['company_id'],
                'status'            => $r['status'],
            ];
        }
        return $out;
    }

    /* ─────────────────────── internals ─────────────────────── */

    /**
     * Find a header cell whose normalized text matches an ID-column candidate.
     * Returns 0-based column index or null when no header row was found at all
     * (meaning: caller should treat row 0 as data and use column 0).
     */
    private function detectIdColumn(array $headerRow): ?int
    {
        if (empty($headerRow)) return null;

        // If row contains only numbers, it's probably data, not a header.
        $allNumeric = true;
        foreach ($headerRow as $c) {
            if ($c === null || $c === '') continue;
            if (!is_numeric($c)) { $allNumeric = false; break; }
        }
        if ($allNumeric) return null;

        $normalize = function ($s) {
            $s = trim((string)$s);
            $s = mb_strtolower($s, 'UTF-8');
            return preg_replace('/\s+/u', ' ', $s);
        };
        $candidates = array_map($normalize, self::ID_HEADER_CANDIDATES);

        foreach ($headerRow as $i => $cell) {
            if ($cell === null) continue;
            $n = $normalize($cell);
            if ($n === '') continue;
            if (in_array($n, $candidates, true)) return (int)$i;
            // Soft match: header contains "id" word boundary or "عقد".
            if (preg_match('/\bid\b/u', $n) || strpos($n, 'عقد') !== false || strpos($n, 'رقم') !== false) {
                return (int)$i;
            }
        }
        // Header row exists but no matching column — fallback to column 0.
        return -1;
    }
}
