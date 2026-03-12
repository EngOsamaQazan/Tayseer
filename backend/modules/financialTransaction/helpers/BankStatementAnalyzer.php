<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  محلل كشوف الحسابات البنكية — كشف تلقائي لأعمدة أي بنك
 *  ─────────────────────────────────────────────────────────────────
 *  يقرأ ملف Excel ويكتشف تلقائياً:
 *  - عمود التاريخ
 *  - عمود الوصف/البيان
 *  - عمود المدين (مسحوبات)
 *  - عمود الدائن (إيداعات)
 *  - عمود المبلغ (إذا كان عمود واحد)
 *  - عمود الرصيد
 *  - صف بداية البيانات
 * ═══════════════════════════════════════════════════════════════════
 */

namespace backend\modules\financialTransaction\helpers;

use Yii;

class BankStatementAnalyzer
{
    /* ═══ كلمات الرصيد الافتتاحي — يُستبعد من الاستيراد لأنه رصيد سابق وليس حركة ═══ */
    const OPENING_BALANCE_KEYWORDS = [
        /* عربي */
        'رصيد افتتاحي', 'الرصيد الافتتاحي', 'رصيد سابق', 'رصيد أول المدة',
        'رصيد مرحل', 'رصيد أول الفترة', 'رصيد مدور', 'الرصيد المدور',
        'رصيد اول المدة', 'رصيد اول الفترة',
        /* انجليزي */
        'opening balance', 'brought forward', 'b/f', 'balance b/f',
        'beginning balance', 'previous balance', 'carry forward',
        'carried forward', 'balance brought forward', 'opening bal',
    ];

    /* ═══ قاموس الكلمات المفتاحية لكل حقل (عربي + انجليزي) ═══ */
    const KEYWORDS = [
        'date' => [
            'تاريخ', 'التاريخ', 'تاريخ القيد', 'تاريخ العملية', 'تاريخ المعاملة',
            'date', 'value date', 'posting date', 'transaction date', 'txn date',
        ],
        'description' => [
            'وصف', 'الوصف', 'بيان', 'البيان', 'تفاصيل', 'التفاصيل', 'ملاحظات',
            'description', 'details', 'narrative', 'particulars', 'reference', 'memo',
        ],
        'debit' => [
            'مدين', 'المدين', 'منه', 'مسحوبات', 'سحب', 'خصم', 'مدفوعات',
            'debit', 'withdrawal', 'withdrawals', 'dr', 'paid out', 'charges',
        ],
        'credit' => [
            'دائن', 'الدائن', 'له', 'إيداع', 'ايداع', 'إضافة', 'اضافة', 'إيداعات',
            'credit', 'deposit', 'deposits', 'cr', 'paid in', 'received',
        ],
        'amount' => [
            'مبلغ', 'المبلغ', 'القيمة', 'قيمة',
            'amount', 'value', 'sum', 'total',
        ],
        'balance' => [
            'رصيد', 'الرصيد', 'رصيد الحساب',
            'balance', 'running balance', 'closing balance', 'available balance',
        ],
    ];

    /** @var array بيانات الشيت */
    private $sheetData;

    /** @var int صف العناوين المكتشف */
    private $headerRow = 1;

    /** @var int صف بداية البيانات */
    private $dataStartRow = 2;

    /** @var array ربط الأعمدة المكتشف */
    private $mapping = [];

    /** @var array أسماء الأعمدة الأصلية */
    private $originalHeaders = [];

    /**
     * تحليل بيانات الشيت واكتشاف الأعمدة تلقائياً
     *
     * @param array $sheetData بيانات الشيت من PHPExcel (مصفوفة مفهرسة بالأحرف A,B,C...)
     * @return array نتيجة التحليل
     */
    public function analyze(array $sheetData): array
    {
        $this->sheetData = $sheetData;

        /* الخطوة 1: اكتشاف صف العناوين */
        $this->detectHeaderRow();

        /* الخطوة 2: اكتشاف ربط الأعمدة */
        $this->detectColumnMapping();

        /* الخطوة 3: اكتشاف صف بداية البيانات */
        $this->detectDataStartRow();

        return [
            'mapping'         => $this->mapping,
            'headerRow'       => $this->headerRow,
            'dataStartRow'    => $this->dataStartRow,
            'originalHeaders' => $this->originalHeaders,
            'confidence'      => $this->calculateConfidence(),
        ];
    }

    /**
     * اكتشاف صف العناوين — يبحث عن الصف الذي يحتوي أكبر عدد من الكلمات المفتاحية
     * يدعم العناوين المركبة (صفين متتاليين)
     */
    private function detectHeaderRow(): void
    {
        $maxScore = 0;
        $bestRow  = 1;

        /* نفحص أول 20 صفاً — بعض البنوك تضع عناوين كثيرة */
        $limit = min(20, count($this->sheetData));
        for ($row = 1; $row <= $limit; $row++) {
            if (!isset($this->sheetData[$row])) continue;

            $score = 0;
            $nonEmptyCells = 0;
            foreach ($this->sheetData[$row] as $cell) {
                $normalized = $this->normalize($cell);
                if (empty($normalized)) continue;
                $nonEmptyCells++;

                foreach (self::KEYWORDS as $field => $keywords) {
                    foreach ($keywords as $keyword) {
                        if (mb_strpos($normalized, $this->normalize($keyword)) !== false) {
                            $score++;
                            break 2; /* خلية واحدة = نقطة واحدة فقط */
                        }
                    }
                }
            }

            /* يجب أن يكون الصف يحتوي على 3 خلايا غير فارغة على الأقل لاعتباره صف عناوين */
            if ($score > $maxScore && $nonEmptyCells >= 3) {
                $maxScore = $score;
                $bestRow  = $row;
            }
        }

        $this->headerRow = $bestRow;

        /* حفظ أسماء العناوين الأصلية — مع دمج الصف التالي إذا كان يحتوي عناوين فرعية */
        if (isset($this->sheetData[$bestRow])) {
            foreach ($this->sheetData[$bestRow] as $col => $val) {
                $this->originalHeaders[$col] = trim((string)$val);
            }
        }

        /* فحص الصف التالي — قد يحتوي أسماء أعمدة فرعية (عناوين مدمجة) */
        $nextRow = $bestRow + 1;
        if (isset($this->sheetData[$nextRow])) {
            $nextScore = 0;
            foreach ($this->sheetData[$nextRow] as $cell) {
                $normalized = $this->normalize($cell);
                if (empty($normalized)) continue;
                foreach (self::KEYWORDS as $keywords) {
                    foreach ($keywords as $keyword) {
                        if (mb_strpos($normalized, $this->normalize($keyword)) !== false) {
                            $nextScore++;
                            break 2;
                        }
                    }
                }
            }

            /* إذا كان الصف التالي يحتوي أيضاً كلمات مفتاحية، ادمج العناوين */
            if ($nextScore >= 2) {
                foreach ($this->sheetData[$nextRow] as $col => $val) {
                    $trimmed = trim((string)$val);
                    if (!empty($trimmed)) {
                        if (empty($this->originalHeaders[$col])) {
                            $this->originalHeaders[$col] = $trimmed;
                        } else {
                            $this->originalHeaders[$col] .= ' ' . $trimmed;
                        }
                    }
                }
                /* تحديث headerRow إلى الصف الثاني لأن dataStartRow سيكون بعده */
                $this->headerRow = $nextRow;
            }
        }
    }

    /**
     * اكتشاف ربط الأعمدة بناءً على أسماء العناوين + تحليل البيانات كبديل
     */
    private function detectColumnMapping(): void
    {
        $this->mapping = [];
        $usedColumns = [];

        /* ترتيب الأولوية: التاريخ أولاً، ثم الوصف، ثم المدين/الدائن، ثم المبلغ، ثم الرصيد */
        $priority = ['date', 'description', 'debit', 'credit', 'amount', 'balance'];

        foreach ($priority as $field) {
            $bestCol   = null;
            $bestScore = 0;

            foreach ($this->originalHeaders as $col => $headerText) {
                if (in_array($col, $usedColumns)) continue;
                $normalized = $this->normalize($headerText);
                if (empty($normalized)) continue;

                foreach (self::KEYWORDS[$field] as $keyword) {
                    $kwNorm = $this->normalize($keyword);

                    /* تطابق تام = أعلى نقاط */
                    if ($normalized === $kwNorm) {
                        $score = 100;
                    }
                    /* يحتوي الكلمة المفتاحية */
                    elseif (mb_strpos($normalized, $kwNorm) !== false) {
                        $score = 70;
                    }
                    /* الكلمة المفتاحية تحتوي العنوان */
                    elseif (mb_strpos($kwNorm, $normalized) !== false && mb_strlen($normalized) >= 3) {
                        $score = 40;
                    }
                    else {
                        $score = 0;
                    }

                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestCol   = $col;
                    }
                }
            }

            if ($bestCol !== null && $bestScore >= 40) {
                $this->mapping[$field] = $bestCol;
                $usedColumns[]         = $bestCol;
            }
        }

        /* ═══ فحص بديل بناءً على أنماط البيانات ═══ */
        /* إذا لم نجد عمود التاريخ بالكلمات المفتاحية، نبحث عن أعمدة تحتوي تواريخ */
        if (empty($this->mapping['date'])) {
            $this->detectColumnsByDataPatterns($usedColumns);
        }
    }

    /**
     * اكتشاف الأعمدة بناءً على أنماط البيانات — يُستخدم كبديل عند فشل الكلمات المفتاحية
     * يفحص أول صفوف البيانات ويحلل نوع المحتوى لكل عمود
     */
    private function detectColumnsByDataPatterns(array &$usedColumns): void
    {
        /* جمع 5 صفوف بيانات للتحليل */
        $sampleRows = [];
        $start = $this->headerRow + 1;
        for ($i = $start; $i <= min($start + 15, count($this->sheetData)); $i++) {
            if (!isset($this->sheetData[$i])) continue;
            $hasData = false;
            foreach ($this->sheetData[$i] as $cell) {
                if (!empty(trim((string)$cell))) { $hasData = true; break; }
            }
            if ($hasData) {
                $sampleRows[] = $this->sheetData[$i];
                if (count($sampleRows) >= 5) break;
            }
        }

        if (empty($sampleRows)) return;

        /* تحليل كل عمود */
        $columnStats = [];
        foreach ($sampleRows as $row) {
            foreach ($row as $col => $cell) {
                if (in_array($col, $usedColumns)) continue;
                $val = trim((string)$cell);
                if (empty($val)) continue;

                if (!isset($columnStats[$col])) {
                    $columnStats[$col] = ['dates' => 0, 'numbers' => 0, 'texts' => 0, 'total' => 0];
                }
                $columnStats[$col]['total']++;

                if ($this->isValidDate($val)) {
                    $columnStats[$col]['dates']++;
                } elseif (is_numeric(str_replace([',', ' ', '.'], '', $val)) && $this->parseNumber($val) > 0) {
                    $columnStats[$col]['numbers']++;
                } elseif (mb_strlen($val) > 3) {
                    $columnStats[$col]['texts']++;
                }
            }
        }

        /* عمود التاريخ: أكثر عمود يحتوي تواريخ */
        if (empty($this->mapping['date'])) {
            $bestDateCol = null;
            $bestDateCount = 0;
            foreach ($columnStats as $col => $stats) {
                if ($stats['dates'] > $bestDateCount) {
                    $bestDateCount = $stats['dates'];
                    $bestDateCol = $col;
                }
            }
            if ($bestDateCol && $bestDateCount >= 2) {
                $this->mapping['date'] = $bestDateCol;
                $usedColumns[] = $bestDateCol;
            }
        }

        /* عمود الوصف: أكثر عمود يحتوي نصوص طويلة */
        if (empty($this->mapping['description'])) {
            $bestTextCol = null;
            $bestTextCount = 0;
            foreach ($columnStats as $col => $stats) {
                if (in_array($col, $usedColumns)) continue;
                if ($stats['texts'] > $bestTextCount) {
                    $bestTextCount = $stats['texts'];
                    $bestTextCol = $col;
                }
            }
            if ($bestTextCol && $bestTextCount >= 2) {
                $this->mapping['description'] = $bestTextCol;
                $usedColumns[] = $bestTextCol;
            }
        }
    }

    /**
     * اكتشاف صف بداية البيانات — أول صف بعد العناوين يحتوي تاريخ صالح
     */
    private function detectDataStartRow(): void
    {
        $dateCol = $this->mapping['date'] ?? null;
        $start   = $this->headerRow + 1;
        $maxScan = min($start + 20, count($this->sheetData));

        for ($row = $start; $row <= $maxScan; $row++) {
            if (!isset($this->sheetData[$row])) continue;

            /* تخطي الصفوف الفارغة تماماً */
            $allEmpty = true;
            foreach ($this->sheetData[$row] as $cell) {
                if (!empty(trim((string)$cell))) { $allEmpty = false; break; }
            }
            if ($allEmpty) continue;

            /* إذا وجدنا عمود تاريخ — نبحث عن أول تاريخ صالح */
            if ($dateCol && isset($this->sheetData[$row][$dateCol])) {
                $val = $this->sheetData[$row][$dateCol];
                if ($this->isValidDate($val)) {
                    $this->dataStartRow = $row;
                    return;
                }
            }

            /* فحص بديل: البحث عن تاريخ صالح في أي خلية (لو عمود التاريخ المكتشف خاطئ) */
            if (!$dateCol) {
                foreach ($this->sheetData[$row] as $cell) {
                    $cellVal = trim((string)$cell);
                    if (!empty($cellVal) && $this->isValidDate($cellVal)) {
                        $this->dataStartRow = $row;
                        return;
                    }
                }
                /* فحص أخير: صف يحتوي أرقام متعددة (يشبه بيانات مالية) */
                $numericCount = 0;
                foreach ($this->sheetData[$row] as $cell) {
                    if (is_numeric(str_replace([',', ' '], '', (string)$cell))) {
                        $numericCount++;
                    }
                }
                if ($numericCount >= 3) {
                    $this->dataStartRow = $row;
                    return;
                }
            }
        }

        $this->dataStartRow = $this->headerRow + 1;
    }

    /**
     * تحويل صفوف البيانات حسب الربط المحدد
     *
     * @param array $mapping ربط الأعمدة (يمكن أن يكون معدّل يدوياً من المستخدم)
     * @param int $startRow صف بداية البيانات
     * @param int|null $limit عدد الصفوف (null = الكل)
     * @return array مصفوفة من الحركات المحللة
     */
    public function parseRows(array $mapping, int $startRow, ?int $limit = null): array
    {
        $rows    = [];
        $count   = 0;
        $hasDebitCredit = isset($mapping['debit']) && isset($mapping['credit']);
        $hasAmount      = isset($mapping['amount']);

        for ($i = $startRow; $i <= count($this->sheetData); $i++) {
            if (!isset($this->sheetData[$i])) continue;
            $row = $this->sheetData[$i];

            /* تخطي الصفوف الفارغة */
            $allEmpty = true;
            foreach ($row as $cell) {
                if (!empty(trim((string)$cell))) { $allEmpty = false; break; }
            }
            if ($allEmpty) continue;

            /* ═══ كشف واستبعاد الرصيد الافتتاحي ═══ */
            $isOpening = $this->isOpeningBalance($row, $mapping);

            $parsed = [
                'date'            => null,
                'description'     => null,
                'debit'           => 0,
                'credit'          => 0,
                'amount'          => 0,
                'type'            => null, /* 1=دائن, 2=مدين */
                'balance'         => null,
                'raw'             => $row,
                'row_number'      => $i,
                'errors'          => [],
                'openingBalance'  => $isOpening, /* علامة: هل هذا رصيد افتتاحي؟ */
            ];

            /* التاريخ */
            if (isset($mapping['date'], $row[$mapping['date']])) {
                $dateVal = trim((string)$row[$mapping['date']]);
                $parsed['date'] = $this->parseDate($dateVal);
                if (!$parsed['date']) {
                    $parsed['errors'][] = 'تاريخ غير صالح: ' . $dateVal;
                }
            }

            /* الوصف */
            if (isset($mapping['description'], $row[$mapping['description']])) {
                $parsed['description'] = trim((string)$row[$mapping['description']]);
            }

            /* المدين والدائن منفصلين */
            if ($hasDebitCredit) {
                $debitVal  = $this->parseNumber($row[$mapping['debit']] ?? '');
                $creditVal = $this->parseNumber($row[$mapping['credit']] ?? '');

                if ($debitVal > 0) {
                    $parsed['amount'] = $debitVal;
                    $parsed['debit']  = $debitVal;
                    $parsed['type']   = 2; /* مدين */
                } elseif ($creditVal > 0) {
                    $parsed['amount'] = $creditVal;
                    $parsed['credit'] = $creditVal;
                    $parsed['type']   = 1; /* دائن */
                }
            }
            /* مبلغ واحد — موجب=دائن، سالب=مدين */
            elseif ($hasAmount && isset($row[$mapping['amount']])) {
                $amountVal = $this->parseNumber($row[$mapping['amount']]);
                if ($amountVal > 0) {
                    $parsed['amount'] = $amountVal;
                    $parsed['credit'] = $amountVal;
                    $parsed['type']   = 1;
                } elseif ($amountVal < 0) {
                    $parsed['amount'] = abs($amountVal);
                    $parsed['debit']  = abs($amountVal);
                    $parsed['type']   = 2;
                }
            }

            /* الرصيد */
            if (isset($mapping['balance'], $row[$mapping['balance']])) {
                $parsed['balance'] = $this->parseNumber($row[$mapping['balance']]);
            }

            /* تخطي الصفوف بدون مبلغ (ما لم تكن رصيد افتتاحي للعرض في المعاينة) */
            if ($parsed['amount'] == 0 && !$isOpening && empty($parsed['errors'])) continue;

            $rows[] = $parsed;
            $count++;
            if ($limit && $count >= $limit) break;
        }

        return $rows;
    }

    /**
     * حساب ملخص البيانات — مع استبعاد صفوف الرصيد الافتتاحي من الإجماليات
     */
    public function calculateSummary(array $parsedRows): array
    {
        $totalDebit   = 0;
        $totalCredit  = 0;
        $errors       = 0;
        $openingCount = 0;
        $importable   = 0;

        foreach ($parsedRows as $row) {
            /* الرصيد الافتتاحي: لا يُحتسب ضمن المجاميع */
            if (!empty($row['openingBalance'])) {
                $openingCount++;
                continue;
            }
            $totalDebit  += $row['debit'];
            $totalCredit += $row['credit'];
            if (!empty($row['errors'])) {
                $errors++;
            } else {
                $importable++;
            }
        }

        return [
            'totalRows'             => count($parsedRows),
            'importableRows'        => $importable,
            'totalDebit'            => $totalDebit,
            'totalCredit'           => $totalCredit,
            'balance'               => $totalCredit - $totalDebit,
            'errorRows'             => $errors,
            'skippedOpeningBalance'  => $openingCount,
        ];
    }

    /**
     * حساب مستوى الثقة بالكشف التلقائي (0-100)
     */
    private function calculateConfidence(): int
    {
        $score = 0;
        $max   = 0;

        /* الحقول الإجبارية */
        $required = ['date' => 30, 'description' => 20];
        foreach ($required as $field => $weight) {
            $max += $weight;
            if (isset($this->mapping[$field])) $score += $weight;
        }

        /* يجب أن يكون هناك إما (مدين+دائن) أو (مبلغ) */
        $max += 40;
        if (isset($this->mapping['debit']) && isset($this->mapping['credit'])) {
            $score += 40;
        } elseif (isset($this->mapping['amount'])) {
            $score += 35;
        }

        /* الرصيد اختياري لكن يزيد الثقة */
        $max += 10;
        if (isset($this->mapping['balance'])) $score += 10;

        return $max > 0 ? (int)round(($score / $max) * 100) : 0;
    }

    /**
     * الحصول على كل أعمدة الملف لعرضها في dropdown التعديل اليدوي
     */
    public function getAvailableColumns(): array
    {
        $cols = [];
        foreach ($this->originalHeaders as $col => $name) {
            $cols[$col] = !empty($name) ? "$col — $name" : "$col — (فارغ)";
        }
        return $cols;
    }

    /* ═══ كشف الرصيد الافتتاحي ═══ */

    /**
     * فحص ما إذا كان الصف يمثل رصيداً افتتاحياً (رصيد سابق)
     * يبحث في عمود البيان أولاً ثم في جميع خلايا الصف
     *
     * @param array $rowData بيانات الصف
     * @param array $mapping ربط الأعمدة
     * @return bool
     */
    public function isOpeningBalance(array $rowData, array $mapping): bool
    {
        /* أولاً: فحص عمود البيان/الوصف */
        if (isset($mapping['description'], $rowData[$mapping['description']])) {
            $desc = $this->normalize($rowData[$mapping['description']]);
            if (!empty($desc) && $this->matchesOpeningBalance($desc)) {
                return true;
            }
        }

        /* ثانياً: فحص جميع خلايا الصف (بعض البنوك تضعه في عمود آخر) */
        foreach ($rowData as $cell) {
            $normalized = $this->normalize($cell);
            if (empty($normalized) || mb_strlen($normalized) > 60) continue; /* تجاهل الخلايا الطويلة جداً */
            if ($this->matchesOpeningBalance($normalized)) {
                return true;
            }
        }

        return false;
    }

    /**
     * مطابقة نص مع كلمات الرصيد الافتتاحي
     */
    private function matchesOpeningBalance(string $normalizedText): bool
    {
        foreach (self::OPENING_BALANCE_KEYWORDS as $keyword) {
            $kwNorm = $this->normalize($keyword);
            if ($normalizedText === $kwNorm || mb_strpos($normalizedText, $kwNorm) !== false) {
                return true;
            }
        }
        return false;
    }

    /* ═══ دوال مساعدة ═══ */

    /**
     * تنظيف النص للمقارنة
     */
    private function normalize(?string $text): string
    {
        if ($text === null) return '';
        $text = mb_strtolower(trim($text));
        /* إزالة التشكيل */
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text);
        /* توحيد المسافات */
        $text = preg_replace('/\s+/', ' ', $text);
        return $text;
    }

    /**
     * تحقق من صلاحية التاريخ — يدعم:
     * - صيغ النصوص: 2024-01-15, 15/01/2024, 15.01.2024
     * - أرقام Excel التسلسلية (Excel serial dates): 45302, 45302.0
     * - صيغ تحتوي وقت: 2024-01-15 14:30:00
     */
    private function isValidDate($val): bool
    {
        if ($val === null || $val === '') return false;
        $val = trim((string)$val);
        if (empty($val)) return false;

        /* فحص رقم Excel التسلسلي — نطاق معقول: 1900-01-01 (=1) إلى ~2100 (=73050) */
        if (is_numeric($val)) {
            $num = (float)$val;
            /* أرقام Excel: 30000=~1982, 40000=~2009, 50000=~2036 */
            if ($num > 25000 && $num < 60000) return true;
        }

        /* أنماط نصية شائعة */
        $patterns = [
            '/^\d{4}[-\/]\d{1,2}[-\/]\d{1,2}/',       /* 2024-01-15 (قد يتبعه وقت) */
            '/^\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}/',      /* 15/01/2024 أو 15-1-24 */
            '/^\d{1,2}\.\d{1,2}\.\d{2,4}$/',            /* 15.01.2024 */
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $val)) return true;
        }

        /* محاولة PHP */
        $ts = @strtotime($val);
        return $ts !== false && $ts > strtotime('1990-01-01');
    }

    /**
     * تحويل نص تاريخ إلى صيغة Y-m-d — يدعم:
     * - أرقام Excel التسلسلية
     * - صيغ DD/MM/YYYY (الأكثر شيوعاً في البنوك العربية)
     * - صيغ YYYY-MM-DD
     */
    public function parseDate($val): ?string
    {
        if ($val === null || $val === '') return null;
        $val = trim((string)$val);
        if (empty($val)) return null;

        /* فحص رقم Excel التسلسلي */
        if (is_numeric($val)) {
            $num = (float)$val;
            if ($num > 25000 && $num < 60000) {
                /* تحويل Excel serial → Unix timestamp */
                /* Excel base date: 1900-01-01 = serial 1 (مع تصحيح خطأ Excel المعروف للسنة الكبيسة 1900) */
                $unixDays = $num - 25569; /* 25569 = عدد الأيام بين 1900-01-01 و 1970-01-01 */
                $ts = (int)($unixDays * 86400);
                if ($ts > strtotime('1990-01-01')) {
                    return date('Y-m-d', $ts);
                }
            }
            return null;
        }

        /* إزالة جزء الوقت إذا كان موجوداً */
        if (preg_match('/^(\d{1,4}[-\/\.]\d{1,2}[-\/\.]\d{1,4})/', $val, $m)) {
            $val = $m[1];
        }

        /* تحويل فواصل التاريخ */
        $val = str_replace(['.'], '-', $val);

        /* معالجة DD/MM/YYYY (شائع في البنوك العربية والأردنية) */
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $val, $m)) {
            $day   = (int)$m[1];
            $month = (int)$m[2];
            $year  = (int)$m[3];
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
            /* ربما MM/DD/YYYY */
            if (checkdate($day, $month, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $day, $month);
            }
            return null;
        }

        /* معالجة DD/MM/YY */
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{2})$#', $val, $m)) {
            $day   = (int)$m[1];
            $month = (int)$m[2];
            $year  = (int)$m[3] + ($m[3] > 50 ? 1900 : 2000);
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
            return null;
        }

        /* محاولة عامة */
        $val = str_replace('/', '-', $val);
        $ts = @strtotime($val);
        if ($ts && $ts > strtotime('1990-01-01')) {
            return date('Y-m-d', $ts);
        }

        return null;
    }

    /**
     * تحويل نص رقم إلى float (يتعامل مع فواصل الآلاف والعملات)
     */
    private function parseNumber($val): float
    {
        if (is_numeric($val)) return (float)$val;
        $val = trim((string)$val);
        /* إزالة رموز العملات والمسافات */
        $val = preg_replace('/[^\d.,\-]/', '', $val);
        /* التعامل مع فاصلة الآلاف */
        if (preg_match('/^\d{1,3}(,\d{3})*(\.\d+)?$/', $val)) {
            $val = str_replace(',', '', $val); /* 1,234,567.89 → 1234567.89 */
        } elseif (preg_match('/^\d{1,3}(\.\d{3})*(,\d+)?$/', $val)) {
            $val = str_replace('.', '', $val);  /* 1.234.567,89 → 1234567,89 */
            $val = str_replace(',', '.', $val); /* 1234567,89 → 1234567.89 */
        }
        return is_numeric($val) ? (float)$val : 0;
    }
}
