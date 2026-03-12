<?php

use yii\db\Migration;

class m210203_045711_update_table_os_financial_transaction extends Migration
{
    public function up()
    {
        $this->dropTable('{{%financial_transaction}}');

        $this->addColumn('{{%financial_transaction}}', 'id', $this->primaryKey()->first());
        $this->addColumn('{{%financial_transaction}}', 'date', $this->dateTime()->after('id'));
        $this->addColumn('{{%financial_transaction}}', 'category_id', $this->integer()->after('date'));
        $this->addColumn('{{%financial_transaction}}', 'created_at', $this->integer()->after('category_id'));
        $this->addColumn('{{%financial_transaction}}', 'created_by', $this->integer()->notNull()->after('created_at'));
        $this->addColumn('{{%financial_transaction}}', 'updated_at', $this->integer()->after('created_by'));
        $this->addColumn('{{%financial_transaction}}', 'last_updated_by', $this->integer()->after('updated_at'));
        $this->addColumn('{{%financial_transaction}}', 'is_deleted', $this->integer()->notNull()->defaultValue('0')->after('last_updated_by'));
        $this->addColumn('{{%financial_transaction}}', 'bank_description', $this->string(400)->after('is_deleted'));
        $this->addColumn('{{%financial_transaction}}', 'description', $this->string(250)->after('bank_description'));
        $this->addColumn('{{%financial_transaction}}', 'receiver_number', $this->integer()->after('description'));
        $this->addColumn('{{%financial_transaction}}', 'document_number', $this->integer()->after('receiver_number'));
        $this->addColumn('{{%financial_transaction}}', 'type', $this->integer()->after('document_number'));
        $this->addColumn('{{%financial_transaction}}', 'income_type', $this->integer()->after('type'));
        $this->addColumn('{{%financial_transaction}}', 'contract_id', $this->integer()->after('income_type'));
        $this->addColumn('{{%financial_transaction}}', 'amount', $this->double()->notNull()->after('contract_id'));
        $this->addColumn('{{%financial_transaction}}', 'company_id', $this->integer()->after('amount'));
        $this->addForeignKey('fk_company_expenses', '{{%financial_transaction}}', 'company_id', '{{%companies}}', 'id', 'RESTRICT', 'RESTRICT');        $this->createIndex('index_contract', '{{%financial_transaction}}', 'contract_id');
        $this->createIndex('index_company_expenses', '{{%financial_transaction}}', 'company_id');
        $this->createIndex('index_category_id', '{{%financial_transaction}}', 'category_id');
        $this->addPrimaryKey('PRIMARYKEY', '{{%financial_transaction}}', ['id']);
    }

    public function down()
    {
        echo "m210203_045711_update_table_os_financial_transaction cannot be reverted.\n";
        return false;
    }
}
