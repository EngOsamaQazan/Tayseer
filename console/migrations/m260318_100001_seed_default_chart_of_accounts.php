<?php

use yii\db\Migration;

class m260318_100001_seed_default_chart_of_accounts extends Migration
{
    public function safeUp()
    {
        $now = time();
        $accounts = [
            // Level 1 - Main categories
            ['code' => '1000', 'name_ar' => 'الأصول', 'name_en' => 'Assets', 'parent_id' => null, 'type' => 'assets', 'nature' => 'debit', 'level' => 1, 'is_parent' => 1],
            ['code' => '2000', 'name_ar' => 'الخصوم', 'name_en' => 'Liabilities', 'parent_id' => null, 'type' => 'liabilities', 'nature' => 'credit', 'level' => 1, 'is_parent' => 1],
            ['code' => '3000', 'name_ar' => 'حقوق الملكية', 'name_en' => 'Equity', 'parent_id' => null, 'type' => 'equity', 'nature' => 'credit', 'level' => 1, 'is_parent' => 1],
            ['code' => '4000', 'name_ar' => 'الإيرادات', 'name_en' => 'Revenue', 'parent_id' => null, 'type' => 'revenue', 'nature' => 'credit', 'level' => 1, 'is_parent' => 1],
            ['code' => '5000', 'name_ar' => 'المصروفات', 'name_en' => 'Expenses', 'parent_id' => null, 'type' => 'expenses', 'nature' => 'debit', 'level' => 1, 'is_parent' => 1],
        ];

        foreach ($accounts as $account) {
            $this->insert('{{%accounts}}', array_merge($account, [
                'is_active' => 1,
                'company_id' => null,
                'opening_balance' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // Get parent IDs
        $db = Yii::$app->db;
        $assetsId = $db->createCommand("SELECT id FROM {{%accounts}} WHERE code='1000'")->queryScalar();
        $liabilitiesId = $db->createCommand("SELECT id FROM {{%accounts}} WHERE code='2000'")->queryScalar();
        $equityId = $db->createCommand("SELECT id FROM {{%accounts}} WHERE code='3000'")->queryScalar();
        $revenueId = $db->createCommand("SELECT id FROM {{%accounts}} WHERE code='4000'")->queryScalar();
        $expensesId = $db->createCommand("SELECT id FROM {{%accounts}} WHERE code='5000'")->queryScalar();

        // Level 2 - Sub categories
        $level2 = [
            ['code' => '1100', 'name_ar' => 'الأصول المتداولة', 'name_en' => 'Current Assets', 'parent_id' => $assetsId, 'type' => 'assets', 'nature' => 'debit', 'level' => 2, 'is_parent' => 1],
            ['code' => '1200', 'name_ar' => 'الأصول الثابتة', 'name_en' => 'Fixed Assets', 'parent_id' => $assetsId, 'type' => 'assets', 'nature' => 'debit', 'level' => 2, 'is_parent' => 1],
            ['code' => '2100', 'name_ar' => 'الخصوم المتداولة', 'name_en' => 'Current Liabilities', 'parent_id' => $liabilitiesId, 'type' => 'liabilities', 'nature' => 'credit', 'level' => 2, 'is_parent' => 1],
            ['code' => '2200', 'name_ar' => 'الخصوم طويلة الأجل', 'name_en' => 'Long-term Liabilities', 'parent_id' => $liabilitiesId, 'type' => 'liabilities', 'nature' => 'credit', 'level' => 2, 'is_parent' => 1],
            ['code' => '3100', 'name_ar' => 'رأس المال', 'name_en' => 'Capital', 'parent_id' => $equityId, 'type' => 'equity', 'nature' => 'credit', 'level' => 2, 'is_parent' => 0],
            ['code' => '3200', 'name_ar' => 'أرباح مبقاة', 'name_en' => 'Retained Earnings', 'parent_id' => $equityId, 'type' => 'equity', 'nature' => 'credit', 'level' => 2, 'is_parent' => 0],
            ['code' => '3300', 'name_ar' => 'أرباح/خسائر العام', 'name_en' => 'Current Year P&L', 'parent_id' => $equityId, 'type' => 'equity', 'nature' => 'credit', 'level' => 2, 'is_parent' => 0],
            ['code' => '4100', 'name_ar' => 'إيرادات العقود', 'name_en' => 'Contract Revenue', 'parent_id' => $revenueId, 'type' => 'revenue', 'nature' => 'credit', 'level' => 2, 'is_parent' => 0],
            ['code' => '4200', 'name_ar' => 'إيرادات الغرامات', 'name_en' => 'Fine Revenue', 'parent_id' => $revenueId, 'type' => 'revenue', 'nature' => 'credit', 'level' => 2, 'is_parent' => 0],
            ['code' => '4300', 'name_ar' => 'إيرادات أخرى', 'name_en' => 'Other Revenue', 'parent_id' => $revenueId, 'type' => 'revenue', 'nature' => 'credit', 'level' => 2, 'is_parent' => 0],
            ['code' => '5100', 'name_ar' => 'رواتب وأجور', 'name_en' => 'Salaries & Wages', 'parent_id' => $expensesId, 'type' => 'expenses', 'nature' => 'debit', 'level' => 2, 'is_parent' => 0],
            ['code' => '5200', 'name_ar' => 'إيجارات', 'name_en' => 'Rent', 'parent_id' => $expensesId, 'type' => 'expenses', 'nature' => 'debit', 'level' => 2, 'is_parent' => 0],
            ['code' => '5300', 'name_ar' => 'مصاريف قضائية', 'name_en' => 'Legal Expenses', 'parent_id' => $expensesId, 'type' => 'expenses', 'nature' => 'debit', 'level' => 2, 'is_parent' => 0],
            ['code' => '5400', 'name_ar' => 'مصاريف إدارية وعمومية', 'name_en' => 'General & Admin Expenses', 'parent_id' => $expensesId, 'type' => 'expenses', 'nature' => 'debit', 'level' => 2, 'is_parent' => 0],
            ['code' => '5500', 'name_ar' => 'مصاريف تشغيلية', 'name_en' => 'Operating Expenses', 'parent_id' => $expensesId, 'type' => 'expenses', 'nature' => 'debit', 'level' => 2, 'is_parent' => 0],
        ];

        foreach ($level2 as $account) {
            $this->insert('{{%accounts}}', array_merge($account, [
                'is_active' => 1,
                'company_id' => null,
                'opening_balance' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // Get Level 2 IDs for Level 3
        $currentAssetsId = $db->createCommand("SELECT id FROM {{%accounts}} WHERE code='1100'")->queryScalar();
        $fixedAssetsId = $db->createCommand("SELECT id FROM {{%accounts}} WHERE code='1200'")->queryScalar();
        $currentLiabId = $db->createCommand("SELECT id FROM {{%accounts}} WHERE code='2100'")->queryScalar();

        // Level 3 - Detail accounts
        $level3 = [
            ['code' => '1101', 'name_ar' => 'النقدية والبنوك', 'name_en' => 'Cash & Banks', 'parent_id' => $currentAssetsId, 'type' => 'assets', 'nature' => 'debit', 'level' => 3, 'is_parent' => 0],
            ['code' => '1102', 'name_ar' => 'الذمم المدينة', 'name_en' => 'Accounts Receivable', 'parent_id' => $currentAssetsId, 'type' => 'assets', 'nature' => 'debit', 'level' => 3, 'is_parent' => 0],
            ['code' => '1103', 'name_ar' => 'أوراق القبض', 'name_en' => 'Notes Receivable', 'parent_id' => $currentAssetsId, 'type' => 'assets', 'nature' => 'debit', 'level' => 3, 'is_parent' => 0],
            ['code' => '1104', 'name_ar' => 'مدينون متنوعون', 'name_en' => 'Other Receivables', 'parent_id' => $currentAssetsId, 'type' => 'assets', 'nature' => 'debit', 'level' => 3, 'is_parent' => 0],
            ['code' => '1201', 'name_ar' => 'عقارات', 'name_en' => 'Real Estate', 'parent_id' => $fixedAssetsId, 'type' => 'assets', 'nature' => 'debit', 'level' => 3, 'is_parent' => 0],
            ['code' => '1202', 'name_ar' => 'أثاث ومعدات', 'name_en' => 'Furniture & Equipment', 'parent_id' => $fixedAssetsId, 'type' => 'assets', 'nature' => 'debit', 'level' => 3, 'is_parent' => 0],
            ['code' => '2101', 'name_ar' => 'الذمم الدائنة', 'name_en' => 'Accounts Payable', 'parent_id' => $currentLiabId, 'type' => 'liabilities', 'nature' => 'credit', 'level' => 3, 'is_parent' => 0],
            ['code' => '2102', 'name_ar' => 'مصاريف مستحقة', 'name_en' => 'Accrued Expenses', 'parent_id' => $currentLiabId, 'type' => 'liabilities', 'nature' => 'credit', 'level' => 3, 'is_parent' => 0],
            ['code' => '2103', 'name_ar' => 'دائنون متنوعون', 'name_en' => 'Other Payables', 'parent_id' => $currentLiabId, 'type' => 'liabilities', 'nature' => 'credit', 'level' => 3, 'is_parent' => 0],
        ];

        foreach ($level3 as $account) {
            $this->insert('{{%accounts}}', array_merge($account, [
                'is_active' => 1,
                'company_id' => null,
                'opening_balance' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function safeDown()
    {
        $this->delete('{{%accounts}}');
    }
}
