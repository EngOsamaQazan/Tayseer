<?php
/**
 * Seed test financial data for PDF export testing.
 * Run: php yii-runner or via: php _seed_test_financials.php
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

$basePath = __DIR__;
require $basePath . '/vendor/autoload.php';
require $basePath . '/vendor/yiisoft/yii2/Yii.php';
require $basePath . '/common/config/bootstrap.php';
require $basePath . '/backend/config/bootstrap.php';

$commonConfig = yii\helpers\ArrayHelper::merge(
    require $basePath . '/common/config/main.php',
    require $basePath . '/common/config/main-local.php'
);
$backendConfig = yii\helpers\ArrayHelper::merge(
    require $basePath . '/backend/config/main.php',
    require $basePath . '/backend/config/main-local.php'
);
$config = yii\helpers\ArrayHelper::merge($commonConfig, $backendConfig);
$config['id'] = 'seed-script';
$config['basePath'] = $basePath . '/backend';

$app = new yii\web\Application($config);

use backend\modules\accounting\models\Account;
use backend\modules\accounting\models\FiscalYear;
use backend\modules\accounting\models\JournalEntry;
use backend\modules\accounting\models\JournalEntryLine;

$db = Yii::$app->db;

echo "=== بدء إدخال البيانات التجريبية ===\n\n";

// 1. Check/create fiscal year 2026
$fy = FiscalYear::find()->where(['name' => '2026'])->one();
if (!$fy) {
    $fy = new FiscalYear();
    $fy->name = '2026';
    $fy->start_date = '2026-01-01';
    $fy->end_date = '2026-12-31';
    $fy->status = FiscalYear::STATUS_OPEN;
    $fy->is_current = 1;
    $fy->created_by = 1;
    if ($fy->save()) {
        echo "✓ تم إنشاء السنة المالية 2026 (id={$fy->id})\n";
    } else {
        echo "✗ خطأ في إنشاء السنة المالية: " . json_encode($fy->errors) . "\n";
        exit(1);
    }
} else {
    if (!$fy->is_current) {
        $fy->is_current = 1;
        $fy->save(false, ['is_current']);
    }
    echo "✓ السنة المالية 2026 موجودة (id={$fy->id})\n";
}

// Get first fiscal period
$period = $fy->periods[0] ?? null;
if (!$period) {
    echo "✗ لا توجد فترات مالية\n";
    exit(1);
}
echo "✓ الفترة المالية: {$period->name} (id={$period->id})\n\n";

// 2. Check accounts exist
$accountsByCode = [];
$accounts = Account::find()->where(['is_parent' => 0, 'is_active' => 1])->all();
foreach ($accounts as $acc) {
    $accountsByCode[$acc->code] = $acc;
}
echo "✓ عدد الحسابات الفرعية: " . count($accountsByCode) . "\n";

if (count($accountsByCode) === 0) {
    echo "✗ لا توجد حسابات! يرجى إنشاء دليل الحسابات أولاً\n";
    exit(1);
}

// List available accounts by type
$byType = [];
foreach ($accountsByCode as $code => $acc) {
    $byType[$acc->type][$code] = $acc->name_ar;
}
echo "\n--- الحسابات المتوفرة ---\n";
foreach ($byType as $type => $accs) {
    echo "[$type] " . count($accs) . " حسابات:\n";
    foreach ($accs as $code => $name) {
        echo "  $code - $name\n";
    }
}
echo "---\n\n";

// Helper: find account by code prefix
function findAccount($accountsByCode, $codePrefix, $type = null) {
    foreach ($accountsByCode as $code => $acc) {
        if (strpos($code, $codePrefix) === 0) {
            if ($type === null || $acc->type === $type) {
                return $acc;
            }
        }
    }
    return null;
}

// Helper: find first account of type
function findFirstOfType($accountsByCode, $type) {
    foreach ($accountsByCode as $acc) {
        if ($acc->type === $type) {
            return $acc;
        }
    }
    return null;
}

// Get accounts for each type
$cashAcc = findAccount($accountsByCode, '1101') ?: findAccount($accountsByCode, '110') ?: findFirstOfType($accountsByCode, 'assets');
$bankAcc = findAccount($accountsByCode, '1102') ?: findAccount($accountsByCode, '111') ?: $cashAcc;
$receivableAcc = findAccount($accountsByCode, '1201') ?: findAccount($accountsByCode, '120') ?: findFirstOfType($accountsByCode, 'assets');
$fixedAssetAcc = findAccount($accountsByCode, '1301') ?: findAccount($accountsByCode, '12') ?: findFirstOfType($accountsByCode, 'assets');
$accPayableAcc = findAccount($accountsByCode, '2101') ?: findAccount($accountsByCode, '210') ?: findFirstOfType($accountsByCode, 'liabilities');
$loanAcc = findAccount($accountsByCode, '2201') ?: findAccount($accountsByCode, '220') ?: $accPayableAcc;
$capitalAcc = findAccount($accountsByCode, '3101') ?: findAccount($accountsByCode, '310') ?: findFirstOfType($accountsByCode, 'equity');
$retainedAcc = findAccount($accountsByCode, '3201') ?: findAccount($accountsByCode, '320') ?: $capitalAcc;
$salesRevAcc = findAccount($accountsByCode, '4101') ?: findAccount($accountsByCode, '410') ?: findFirstOfType($accountsByCode, 'revenue');
$serviceRevAcc = findAccount($accountsByCode, '4201') ?: findAccount($accountsByCode, '420') ?: $salesRevAcc;
$salariesAcc = findAccount($accountsByCode, '5101') ?: findAccount($accountsByCode, '510') ?: findFirstOfType($accountsByCode, 'expenses');
$rentAcc = findAccount($accountsByCode, '5201') ?: findAccount($accountsByCode, '520') ?: $salariesAcc;
$utilitiesAcc = findAccount($accountsByCode, '5301') ?: findAccount($accountsByCode, '530') ?: $salariesAcc;
$marketingAcc = findAccount($accountsByCode, '5401') ?: findAccount($accountsByCode, '540') ?: $salariesAcc;
$depreciationAcc = findAccount($accountsByCode, '5501') ?: findAccount($accountsByCode, '550') ?: $salariesAcc;

echo "الحسابات المستخدمة:\n";
echo "  نقد: {$cashAcc->code} - {$cashAcc->name_ar}\n";
echo "  بنك: {$bankAcc->code} - {$bankAcc->name_ar}\n";
echo "  ذمم مدينة: {$receivableAcc->code} - {$receivableAcc->name_ar}\n";
echo "  أصول ثابتة: {$fixedAssetAcc->code} - {$fixedAssetAcc->name_ar}\n";
echo "  ذمم دائنة: {$accPayableAcc->code} - {$accPayableAcc->name_ar}\n";
echo "  قروض: {$loanAcc->code} - {$loanAcc->name_ar}\n";
echo "  رأس مال: {$capitalAcc->code} - {$capitalAcc->name_ar}\n";
echo "  أرباح مبقاة: {$retainedAcc->code} - {$retainedAcc->name_ar}\n";
echo "  إيرادات مبيعات: {$salesRevAcc->code} - {$salesRevAcc->name_ar}\n";
echo "  إيرادات خدمات: {$serviceRevAcc->code} - {$serviceRevAcc->name_ar}\n";
echo "  رواتب: {$salariesAcc->code} - {$salariesAcc->name_ar}\n";
echo "  إيجار: {$rentAcc->code} - {$rentAcc->name_ar}\n\n";

// 3. Define test journal entries
$testEntries = [
    [
        'date' => '2026-01-05',
        'desc' => 'إيداع رأس المال التأسيسي في البنك',
        'ref' => 'capital',
        'lines' => [
            [$bankAcc->id, 150000, 0],
            [$capitalAcc->id, 0, 150000],
        ],
    ],
    [
        'date' => '2026-01-10',
        'desc' => 'شراء أثاث ومعدات مكتبية نقداً',
        'ref' => 'manual',
        'lines' => [
            [$fixedAssetAcc->id, 25000, 0],
            [$bankAcc->id, 0, 25000],
        ],
    ],
    [
        'date' => '2026-01-15',
        'desc' => 'إيرادات مبيعات أقساط — عقود يناير',
        'ref' => 'income',
        'lines' => [
            [$receivableAcc->id, 85000, 0],
            [$salesRevAcc->id, 0, 85000],
        ],
    ],
    [
        'date' => '2026-01-20',
        'desc' => 'تحصيل دفعات من عملاء',
        'ref' => 'income',
        'lines' => [
            [$bankAcc->id, 35000, 0],
            [$receivableAcc->id, 0, 35000],
        ],
    ],
    [
        'date' => '2026-01-25',
        'desc' => 'صرف رواتب الموظفين — يناير',
        'ref' => 'payroll',
        'lines' => [
            [$salariesAcc->id, 18000, 0],
            [$bankAcc->id, 0, 18000],
        ],
    ],
    [
        'date' => '2026-01-28',
        'desc' => 'دفع إيجار المكتب — يناير',
        'ref' => 'expense',
        'lines' => [
            [$rentAcc->id, 3500, 0],
            [$bankAcc->id, 0, 3500],
        ],
    ],
    [
        'date' => '2026-01-30',
        'desc' => 'فواتير كهرباء وماء وإنترنت',
        'ref' => 'expense',
        'lines' => [
            [$utilitiesAcc->id, 1200, 0],
            [$bankAcc->id, 0, 1200],
        ],
    ],
    [
        'date' => '2026-02-05',
        'desc' => 'إيرادات خدمات استشارية',
        'ref' => 'income',
        'lines' => [
            [$bankAcc->id, 12000, 0],
            [$serviceRevAcc->id, 0, 12000],
        ],
    ],
    [
        'date' => '2026-02-10',
        'desc' => 'مبيعات أقساط — عقود فبراير',
        'ref' => 'income',
        'lines' => [
            [$receivableAcc->id, 72000, 0],
            [$salesRevAcc->id, 0, 72000],
        ],
    ],
    [
        'date' => '2026-02-15',
        'desc' => 'شراء بضاعة على الحساب من المورد',
        'ref' => 'manual',
        'lines' => [
            [$fixedAssetAcc->id, 15000, 0],
            [$accPayableAcc->id, 0, 15000],
        ],
    ],
    [
        'date' => '2026-02-20',
        'desc' => 'تحصيل دفعات أقساط شهر فبراير',
        'ref' => 'income',
        'lines' => [
            [$bankAcc->id, 42000, 0],
            [$receivableAcc->id, 0, 42000],
        ],
    ],
    [
        'date' => '2026-02-25',
        'desc' => 'صرف رواتب الموظفين — فبراير',
        'ref' => 'payroll',
        'lines' => [
            [$salariesAcc->id, 18000, 0],
            [$bankAcc->id, 0, 18000],
        ],
    ],
    [
        'date' => '2026-02-28',
        'desc' => 'دفع إيجار المكتب — فبراير',
        'ref' => 'expense',
        'lines' => [
            [$rentAcc->id, 3500, 0],
            [$bankAcc->id, 0, 3500],
        ],
    ],
    [
        'date' => '2026-03-01',
        'desc' => 'سداد جزء من الذمم الدائنة للموردين',
        'ref' => 'manual',
        'lines' => [
            [$accPayableAcc->id, 8000, 0],
            [$bankAcc->id, 0, 8000],
        ],
    ],
    [
        'date' => '2026-03-05',
        'desc' => 'إيرادات مبيعات — عقود مارس',
        'ref' => 'income',
        'lines' => [
            [$receivableAcc->id, 95000, 0],
            [$salesRevAcc->id, 0, 95000],
        ],
    ],
    [
        'date' => '2026-03-10',
        'desc' => 'مصاريف تسويق وإعلانات',
        'ref' => 'expense',
        'lines' => [
            [$marketingAcc->id, 5500, 0],
            [$bankAcc->id, 0, 5500],
        ],
    ],
    [
        'date' => '2026-03-15',
        'desc' => 'تحصيل دفعات عملاء — مارس',
        'ref' => 'income',
        'lines' => [
            [$bankAcc->id, 55000, 0],
            [$receivableAcc->id, 0, 55000],
        ],
    ],
    [
        'date' => '2026-03-18',
        'desc' => 'قيد إهلاك الأصول الثابتة — ربع أول',
        'ref' => 'manual',
        'lines' => [
            [$depreciationAcc->id, 2500, 0],
            [$fixedAssetAcc->id, 0, 2500],
        ],
    ],
    [
        'date' => '2026-03-20',
        'desc' => 'الحصول على قرض بنكي قصير الأجل',
        'ref' => 'manual',
        'lines' => [
            [$bankAcc->id, 50000, 0],
            [$loanAcc->id, 0, 50000],
        ],
    ],
    [
        'date' => '2026-03-25',
        'desc' => 'صرف رواتب الموظفين — مارس',
        'ref' => 'payroll',
        'lines' => [
            [$salariesAcc->id, 18000, 0],
            [$bankAcc->id, 0, 18000],
        ],
    ],
];

// 4. Insert entries
$inserted = 0;
$transaction = $db->beginTransaction();

try {
    foreach ($testEntries as $entryData) {
        // Find matching period
        $entryDate = $entryData['date'];
        $prd = $fy->findPeriodByDate($entryDate) ?: $period;

        $entry = new JournalEntry();
        $entry->entry_date = $entryDate;
        $entry->fiscal_year_id = $fy->id;
        $entry->fiscal_period_id = $prd->id;
        $entry->description = $entryData['desc'];
        $entry->reference_type = $entryData['ref'];
        $entry->status = JournalEntry::STATUS_POSTED;
        $entry->is_auto = 0;
        $entry->created_by = 1;
        $entry->approved_by = 1;
        $entry->approved_at = time();

        $totalD = 0;
        $totalC = 0;
        foreach ($entryData['lines'] as $l) {
            $totalD += $l[1];
            $totalC += $l[2];
        }
        $entry->total_debit = $totalD;
        $entry->total_credit = $totalC;

        if (!$entry->save()) {
            echo "✗ خطأ في حفظ القيد [{$entryData['desc']}]: " . json_encode($entry->errors, JSON_UNESCAPED_UNICODE) . "\n";
            continue;
        }

        foreach ($entryData['lines'] as $l) {
            $line = new JournalEntryLine();
            $line->journal_entry_id = $entry->id;
            $line->account_id = $l[0];
            $line->debit = $l[1];
            $line->credit = $l[2];
            $line->description = $entryData['desc'];
            if (!$line->save()) {
                echo "  ✗ خطأ في سطر القيد: " . json_encode($line->errors, JSON_UNESCAPED_UNICODE) . "\n";
            }
        }

        echo "✓ [{$entry->entry_number}] {$entry->entry_date} — {$entry->description} ({$totalD} / {$totalC})\n";
        $inserted++;
    }

    $transaction->commit();
    echo "\n=== تم إدخال {$inserted} قيد محاسبي بنجاح ===\n";
    echo "\n--- ملخص الأرصدة ---\n";

    // Show summary
    $totals = ['assets' => 0, 'liabilities' => 0, 'equity' => 0, 'revenue' => 0, 'expenses' => 0];
    $allAccounts = Account::find()->where(['is_parent' => 0, 'is_active' => 1])->all();
    foreach ($allAccounts as $acc) {
        $debitSum = (float)JournalEntryLine::find()
            ->joinWith('journalEntry')
            ->where(['{{%journal_entry_lines}}.account_id' => $acc->id])
            ->andWhere(['{{%journal_entries}}.status' => 'posted'])
            ->andWhere(['{{%journal_entries}}.fiscal_year_id' => $fy->id])
            ->sum('{{%journal_entry_lines}}.debit') ?: 0;
        $creditSum = (float)JournalEntryLine::find()
            ->joinWith('journalEntry')
            ->where(['{{%journal_entry_lines}}.account_id' => $acc->id])
            ->andWhere(['{{%journal_entries}}.status' => 'posted'])
            ->andWhere(['{{%journal_entries}}.fiscal_year_id' => $fy->id])
            ->sum('{{%journal_entry_lines}}.credit') ?: 0;

        $balance = (float)$acc->opening_balance;
        if ($acc->nature === 'debit') {
            $balance += $debitSum - $creditSum;
        } else {
            $balance += $creditSum - $debitSum;
        }

        if ($balance != 0) {
            $totals[$acc->type] += $balance;
            echo "  [{$acc->type}] {$acc->code} {$acc->name_ar}: " . number_format($balance, 2) . "\n";
        }
    }

    echo "\n--- إجماليات ---\n";
    echo "  الأصول:        " . number_format($totals['assets'], 2) . "\n";
    echo "  الخصوم:        " . number_format($totals['liabilities'], 2) . "\n";
    echo "  حقوق الملكية:  " . number_format($totals['equity'], 2) . "\n";
    echo "  الإيرادات:     " . number_format($totals['revenue'], 2) . "\n";
    echo "  المصروفات:     " . number_format($totals['expenses'], 2) . "\n";
    $netIncome = $totals['revenue'] - $totals['expenses'];
    echo "  صافي الربح:    " . number_format($netIncome, 2) . "\n";
    echo "  أصول = خصوم + حقوق ملكية + ربح ؟ " . number_format($totals['assets'], 2) . " = " . number_format($totals['liabilities'] + $totals['equity'] + $netIncome, 2) . "\n";

    $diff = abs($totals['assets'] - ($totals['liabilities'] + $totals['equity'] + $netIncome));
    if ($diff < 0.01) {
        echo "\n✓ المعادلة المحاسبية متوازنة!\n";
    } else {
        echo "\n⚠ فرق: " . number_format($diff, 2) . "\n";
    }

} catch (\Exception $e) {
    $transaction->rollBack();
    echo "✗ خطأ: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\nيمكنك الآن اختبار التصدير من:\n";
echo "http://tayseer.test/accounting/financial-statements/export-pdf\n";
echo "http://tayseer.test/accounting/financial-statements/balance-sheet\n";
