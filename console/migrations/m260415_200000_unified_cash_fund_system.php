<?php

use yii\db\Migration;

/**
 * نظام الصناديق الموحّد:
 * 1. إزالة الصناديق المبذورة — المحاسب يعرّفها بنفسه
 * 2. إضافة account_id على os_company_banks (ربط البنك بشجرة الحسابات)
 * 3. إضافة cash_account_id على os_income
 * 4. إضافة cash_account_id على os_expenses
 */
class m260415_200000_unified_cash_fund_system extends Migration
{
    public function safeUp()
    {
        // 1. إزالة الصناديق المبذورة (110101-110104) — المحاسب يعرّفها
        $this->delete('{{%accounts}}', ['code' => ['110101', '110102', '110103', '110104']]);

        // 2. ربط بنوك الشركة بشجرة الحسابات
        $table = 'os_company_banks';
        if ($this->db->getTableSchema($table)->getColumn('account_id') === null) {
            $this->addColumn($table, 'account_id', $this->integer()->null()->after('iban_number'));
            $this->createIndex('idx-company_banks-account_id', $table, 'account_id');
        }

        // 3. صندوق على الدخل
        $table = 'os_income';
        if ($this->db->getTableSchema($table)->getColumn('cash_account_id') === null) {
            $this->addColumn($table, 'cash_account_id', $this->integer()->null()->after('receipt_bank'));
            $this->createIndex('idx-income-cash_account_id', $table, 'cash_account_id');
        }

        // 4. صندوق على المصاريف
        $table = 'os_expenses';
        if ($this->db->getTableSchema($table)->getColumn('cash_account_id') === null) {
            $this->addColumn($table, 'cash_account_id', $this->integer()->null()->after('document_number'));
            $this->createIndex('idx-expenses-cash_account_id', $table, 'cash_account_id');
        }
    }

    public function safeDown()
    {
        $this->dropIndex('idx-expenses-cash_account_id', 'os_expenses');
        $this->dropColumn('os_expenses', 'cash_account_id');

        $this->dropIndex('idx-income-cash_account_id', 'os_income');
        $this->dropColumn('os_income', 'cash_account_id');

        $this->dropIndex('idx-company_banks-account_id', 'os_company_banks');
        $this->dropColumn('os_company_banks', 'account_id');
    }
}
