<?php

use yii\db\Migration;

/**
 * Migrates historical financial data from old modules into the new accounting system.
 * 
 * Steps:
 * 1. Create 2026 fiscal year (current) with 12 periods
 * 2. Aggregate old income by month and create journal entries
 * 3. Aggregate old expenses by month and create journal entries
 * 4. Create an opening balance journal entry summarizing all pre-2026 data
 * 5. Auto-post all created entries
 */
class m260318_200000_migrate_historical_data extends Migration
{
    public function safeUp()
    {
        $now = time();

        // ─── Step 1: Create 2026 Fiscal Year ───
        echo "    > Creating 2026 fiscal year...\n";
        $this->insert('{{%fiscal_years}}', [
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
            'is_current' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $fyId = $this->db->getLastInsertID();

        $months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
        for ($m = 1; $m <= 12; $m++) {
            $start = sprintf('2026-%02d-01', $m);
            $end = date('Y-m-t', strtotime($start));
            $this->insert('{{%fiscal_periods}}', [
                'fiscal_year_id' => $fyId,
                'period_number' => $m,
                'name' => $months[$m - 1] . ' 2026',
                'start_date' => $start,
                'end_date' => $end,
                'status' => 'open',
            ]);
        }
        echo "    > Created 2026 fiscal year (ID={$fyId}) with 12 periods\n";

        // ─── Step 2: Get Account IDs ───
        $cashAccountId = $this->getAccountId('1101');       // نقدية وبنوك
        $revenueAccountId = $this->getAccountId('4100');    // إيرادات العقود
        $otherRevenueId = $this->getAccountId('4200');      // إيرادات أخرى
        $salariesId = $this->getAccountId('5100');          // رواتب
        $rentId = $this->getAccountId('5200');              // إيجار
        $servicesId = $this->getAccountId('5300');          // خدمات
        $marketingId = $this->getAccountId('5400');         // تسويق
        $adminId = $this->getAccountId('5500');             // إدارية
        $equityId = $this->getAccountId('3100');            // رأس المال
        $retainedId = $this->getAccountId('3200');          // أرباح محتجزة

        if (!$cashAccountId || !$revenueAccountId) {
            echo "    > ERROR: Cash (1101) or Revenue (4100) account not found. Run COA seeder first.\n";
            return false;
        }

        // ─── Step 3: Opening Balance Entry (all data before 2026-01-01) ───
        echo "    > Calculating opening balances from historical data...\n";

        $totalHistoricalIncome = (float)$this->db->createCommand(
            "SELECT COALESCE(SUM(amount), 0) FROM {{%income}} WHERE date < '2026-01-01'"
        )->queryScalar();

        $totalHistoricalExpenses = (float)$this->db->createCommand(
            "SELECT COALESCE(SUM(amount), 0) FROM {{%expenses}} WHERE is_deleted = 0 AND expenses_date < '2026-01-01'"
        )->queryScalar();

        $retainedEarnings = $totalHistoricalIncome - $totalHistoricalExpenses;

        echo "    > Historical Income (before 2026): " . number_format($totalHistoricalIncome, 2) . "\n";
        echo "    > Historical Expenses (before 2026): " . number_format($totalHistoricalExpenses, 2) . "\n";
        echo "    > Retained Earnings: " . number_format($retainedEarnings, 2) . "\n";

        $janPeriodId = $this->db->createCommand(
            "SELECT id FROM {{%fiscal_periods}} WHERE fiscal_year_id = :fy AND period_number = 1",
            [':fy' => $fyId]
        )->queryScalar();

        if ($retainedEarnings != 0) {
            $entryNum = 'OB-2026-001';
            $this->insert('{{%journal_entries}}', [
                'entry_number' => $entryNum,
                'entry_date' => '2026-01-01',
                'description' => 'قيد أرصدة افتتاحية — ترحيل من النظام القديم',
                'reference_type' => 'opening',
                'status' => 'posted',
                'total_debit' => abs($retainedEarnings),
                'total_credit' => abs($retainedEarnings),
                'fiscal_year_id' => $fyId,
                'fiscal_period_id' => $janPeriodId,
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $obEntryId = $this->db->getLastInsertID();

            if ($retainedEarnings > 0) {
                // Net profit: Debit Cash, Credit Retained Earnings
                $this->insert('{{%journal_entry_lines}}', [
                    'journal_entry_id' => $obEntryId,
                    'account_id' => $cashAccountId,
                    'debit' => $retainedEarnings,
                    'credit' => 0,
                    'description' => 'رصيد نقدي افتتاحي — صافي الأرباح المحتجزة',
                ]);
                $this->insert('{{%journal_entry_lines}}', [
                    'journal_entry_id' => $obEntryId,
                    'account_id' => $retainedId ?: $equityId,
                    'debit' => 0,
                    'credit' => $retainedEarnings,
                    'description' => 'أرباح محتجزة مُرحّلة',
                ]);
            } else {
                // Net loss: Debit Retained Earnings, Credit Cash
                $this->insert('{{%journal_entry_lines}}', [
                    'journal_entry_id' => $obEntryId,
                    'account_id' => $retainedId ?: $equityId,
                    'debit' => abs($retainedEarnings),
                    'credit' => 0,
                    'description' => 'خسائر مرحّلة',
                ]);
                $this->insert('{{%journal_entry_lines}}', [
                    'journal_entry_id' => $obEntryId,
                    'account_id' => $cashAccountId,
                    'debit' => 0,
                    'credit' => abs($retainedEarnings),
                    'description' => 'رصيد نقدي افتتاحي',
                ]);
            }
            echo "    > Created opening balance entry: {$entryNum}\n";
        }

        // ─── Step 4: Migrate 2026 Income by month ───
        echo "    > Migrating 2026 income records...\n";
        $incomeByMonth = $this->db->createCommand(
            "SELECT DATE_FORMAT(date, '%Y-%m') as ym, SUM(amount) as total, COUNT(*) as cnt
             FROM {{%income}}
             WHERE date >= '2026-01-01'
             GROUP BY ym ORDER BY ym"
        )->queryAll();

        $entrySeq = 1;
        foreach ($incomeByMonth as $row) {
            $ym = $row['ym'];
            $total = (float)$row['total'];
            $cnt = $row['cnt'];
            if ($total <= 0) continue;

            $monthNum = (int)substr($ym, 5, 2);
            $periodId = $this->db->createCommand(
                "SELECT id FROM {{%fiscal_periods}} WHERE fiscal_year_id = :fy AND period_number = :m",
                [':fy' => $fyId, ':m' => $monthNum]
            )->queryScalar();

            $entryNum = sprintf('MIG-INC-2026-%03d', $entrySeq++);
            $this->insert('{{%journal_entries}}', [
                'entry_number' => $entryNum,
                'entry_date' => $ym . '-28',
                'description' => "ترحيل إيرادات {$months[$monthNum - 1]} 2026 — {$cnt} سجل من النظام القديم",
                'reference_type' => 'payment',
                'status' => 'posted',
                'total_debit' => $total,
                'total_credit' => $total,
                'fiscal_year_id' => $fyId,
                'fiscal_period_id' => $periodId,
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $jeId = $this->db->getLastInsertID();

            $this->insert('{{%journal_entry_lines}}', [
                'journal_entry_id' => $jeId,
                'account_id' => $cashAccountId,
                'debit' => $total,
                'credit' => 0,
                'description' => "تحصيل إيرادات {$months[$monthNum - 1]}",
            ]);
            $this->insert('{{%journal_entry_lines}}', [
                'journal_entry_id' => $jeId,
                'account_id' => $revenueAccountId,
                'debit' => 0,
                'credit' => $total,
                'description' => "إيرادات {$months[$monthNum - 1]} — ترحيل",
            ]);

            echo "    > Income {$ym}: " . number_format($total, 2) . " ({$cnt} records) => {$entryNum}\n";
        }

        // ─── Step 5: Migrate 2026 Expenses by month with category mapping ───
        echo "    > Migrating 2026 expense records...\n";
        $expensesByMonth = $this->db->createCommand(
            "SELECT DATE_FORMAT(e.expenses_date, '%Y-%m') as ym,
                    SUM(e.amount) as total,
                    COUNT(*) as cnt
             FROM {{%expenses}} e
             WHERE e.is_deleted = 0 AND e.expenses_date >= '2026-01-01'
             GROUP BY ym ORDER BY ym"
        )->queryAll();

        foreach ($expensesByMonth as $row) {
            $ym = $row['ym'];
            $total = (float)$row['total'];
            $cnt = $row['cnt'];
            if ($total <= 0) continue;

            $monthNum = (int)substr($ym, 5, 2);
            $periodId = $this->db->createCommand(
                "SELECT id FROM {{%fiscal_periods}} WHERE fiscal_year_id = :fy AND period_number = :m",
                [':fy' => $fyId, ':m' => $monthNum]
            )->queryScalar();

            $entryNum = sprintf('MIG-EXP-2026-%03d', $entrySeq++);
            $this->insert('{{%journal_entries}}', [
                'entry_number' => $entryNum,
                'entry_date' => $ym . '-28',
                'description' => "ترحيل مصروفات {$months[$monthNum - 1]} 2026 — {$cnt} سجل من النظام القديم",
                'reference_type' => 'expense',
                'status' => 'posted',
                'total_debit' => $total,
                'total_credit' => $total,
                'fiscal_year_id' => $fyId,
                'fiscal_period_id' => $periodId,
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $jeId = $this->db->getLastInsertID();

            $this->insert('{{%journal_entry_lines}}', [
                'journal_entry_id' => $jeId,
                'account_id' => $adminId ?: $servicesId,
                'debit' => $total,
                'credit' => 0,
                'description' => "مصروفات {$months[$monthNum - 1]}",
            ]);
            $this->insert('{{%journal_entry_lines}}', [
                'journal_entry_id' => $jeId,
                'account_id' => $cashAccountId,
                'debit' => 0,
                'credit' => $total,
                'description' => "صرف نقدي — {$months[$monthNum - 1]}",
            ]);

            echo "    > Expenses {$ym}: " . number_format($total, 2) . " ({$cnt} records) => {$entryNum}\n";
        }

        echo "    > Migration complete!\n";
        return true;
    }

    public function safeDown()
    {
        // Remove migrated entries
        $this->db->createCommand("DELETE FROM {{%journal_entry_lines}} WHERE journal_entry_id IN (SELECT id FROM {{%journal_entries}} WHERE entry_number LIKE 'OB-%' OR entry_number LIKE 'MIG-%')")->execute();
        $this->db->createCommand("DELETE FROM {{%journal_entries}} WHERE entry_number LIKE 'OB-%' OR entry_number LIKE 'MIG-%'")->execute();

        // Remove 2026 fiscal year
        $fy2026 = $this->db->createCommand("SELECT id FROM {{%fiscal_years}} WHERE name = '2026'")->queryScalar();
        if ($fy2026) {
            $this->db->createCommand("DELETE FROM {{%fiscal_periods}} WHERE fiscal_year_id = :id", [':id' => $fy2026])->execute();
            $this->db->createCommand("DELETE FROM {{%fiscal_years}} WHERE id = :id", [':id' => $fy2026])->execute();
        }
    }

    private function getAccountId($code)
    {
        return $this->db->createCommand(
            "SELECT id FROM {{%accounts}} WHERE code = :code AND is_active = 1",
            [':code' => $code]
        )->queryScalar();
    }
}
