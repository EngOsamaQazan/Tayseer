<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%financial_transaction}}`.
 */
class m210203_043037_create_financial_transaction_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropTable('{{%financial_transaction}}');
        $this->createTable('{{%financial_transaction}}', [
            'id' => $this->primaryKey(),
            'date' => $this->date(),
            'category_id' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'last_updated_by' => $this->integer()->notNull(),
            'is_deleted' => $this->integer()->notNull(),
            'bank_description' => $this->text(),
            'description' => $this->text(),
            'receiver_number' => $this->integer(),
            'document_number' => $this->integer(),
            'type' => $this->integer(),
            'income_type' => $this->integer(),
            'contract_id' => $this->integer(),
            'amount' => $this->double()->notNull(),
            'company_id' => $this->integer(),
            'is_transfer'=> $this->integer(),
        ]);
        $this->createIndex('index_category_id', '{{%financial_transaction}}', 'category_id');
        $this->createIndex('index_created_by', '{{%financial_transaction}}', 'created_by');
        $this->createIndex('index_company_expenses', '{{%financial_transaction}}', 'company_id');
        $this->createIndex('index_contract', '{{%financial_transaction}}', 'contract_id');
        $this->createIndex('index_last_updated_by', '{{%financial_transaction}}', 'last_updated_by');

        $this->addForeignKey('fk_company_expenses', '{{%financial_transaction}}', 'company_id', '{{%companies}}', 'id');
        $this->addForeignKey('fk_contract', '{{%financial_transaction}}', 'contract_id', '{{%contracts}}', 'id');
        $this->addForeignKey('fk_category', '{{%financial_transaction}}', 'category_id', '{{%expense_categories}}', 'id');
        $this->addForeignKey('fk_created_by', '{{%financial_transaction}}', 'created_by', '{{%user}}', 'id');
        $this->addForeignKey('fk_last_updated_by', '{{%financial_transaction}}', 'last_updated_by', '{{%user}}', 'id');

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%financial_transaction}}');
    }
}
