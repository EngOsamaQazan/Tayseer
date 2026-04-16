<?php

use yii\db\Migration;

/**
 * إضافة حقل cash_account_id لربط الحركة المالية بحساب الصندوق من شجرة الحسابات.
 * يبقى bank_id كما هو للتوافق مع الاستيراد البنكي القديم.
 */
class m260415_100001_add_cash_account_id_to_financial_transaction extends Migration
{
    public function safeUp()
    {
        $table = 'os_financial_transaction';

        if ($this->db->getTableSchema($table)->getColumn('cash_account_id') !== null) {
            echo "Column cash_account_id already exists — skipping.\n";
            return;
        }

        $this->addColumn($table, 'cash_account_id', $this->integer()->null()->after('bank_id'));
        $this->createIndex('idx-fin_tx-cash_account_id', $table, 'cash_account_id');
    }

    public function safeDown()
    {
        $table = 'os_financial_transaction';
        $this->dropIndex('idx-fin_tx-cash_account_id', $table);
        $this->dropColumn($table, 'cash_account_id');
    }
}
