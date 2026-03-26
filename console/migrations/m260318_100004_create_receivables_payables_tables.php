<?php

use yii\db\Migration;

class m260318_100004_create_receivables_payables_tables extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%receivables}}', true) === null) {
            $this->createTable('{{%receivables}}', [
                'id' => $this->primaryKey(),
                'customer_id' => $this->integer()->null(),
                'contract_id' => $this->integer()->null(),
                'invoice_id' => $this->integer()->null(),
                'account_id' => $this->integer()->notNull(),
                'amount' => $this->decimal(15, 2)->notNull(),
                'paid_amount' => $this->decimal(15, 2)->notNull()->defaultValue(0),
                'balance' => $this->decimal(15, 2)->notNull(),
                'due_date' => $this->date()->null(),
                'status' => "ENUM('open','partial','paid','overdue','written_off') NOT NULL DEFAULT 'open'",
                'journal_entry_id' => $this->integer()->null(),
                'company_id' => $this->integer()->null(),
                'description' => $this->string(500)->null(),
                'created_by' => $this->integer()->null(),
                'created_at' => $this->integer()->null(),
                'updated_at' => $this->integer()->null(),
            ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            $this->addForeignKey('fk-receivables-account', '{{%receivables}}', 'account_id', '{{%accounts}}', 'id');
            $this->addForeignKey('fk-receivables-journal', '{{%receivables}}', 'journal_entry_id', '{{%journal_entries}}', 'id', 'SET NULL');
            $this->createIndex('idx-receivables-customer', '{{%receivables}}', 'customer_id');
            $this->createIndex('idx-receivables-contract', '{{%receivables}}', 'contract_id');
            $this->createIndex('idx-receivables-status', '{{%receivables}}', 'status');
            $this->createIndex('idx-receivables-due_date', '{{%receivables}}', 'due_date');
            $this->createIndex('idx-receivables-company', '{{%receivables}}', 'company_id');
        }

        if ($this->db->getTableSchema('{{%payables}}', true) === null) {
            $this->createTable('{{%payables}}', [
                'id' => $this->primaryKey(),
                'vendor_name' => $this->string(255)->notNull(),
                'vendor_id' => $this->integer()->null(),
                'account_id' => $this->integer()->notNull(),
                'amount' => $this->decimal(15, 2)->notNull(),
                'paid_amount' => $this->decimal(15, 2)->notNull()->defaultValue(0),
                'balance' => $this->decimal(15, 2)->notNull(),
                'due_date' => $this->date()->null(),
                'status' => "ENUM('open','partial','paid','overdue') NOT NULL DEFAULT 'open'",
                'journal_entry_id' => $this->integer()->null(),
                'company_id' => $this->integer()->null(),
                'description' => $this->string(500)->null(),
                'category' => $this->string(100)->null(),
                'reference_number' => $this->string(100)->null(),
                'created_by' => $this->integer()->null(),
                'created_at' => $this->integer()->null(),
                'updated_at' => $this->integer()->null(),
            ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            $this->addForeignKey('fk-payables-account', '{{%payables}}', 'account_id', '{{%accounts}}', 'id');
            $this->addForeignKey('fk-payables-journal', '{{%payables}}', 'journal_entry_id', '{{%journal_entries}}', 'id', 'SET NULL');
            $this->createIndex('idx-payables-status', '{{%payables}}', 'status');
            $this->createIndex('idx-payables-due_date', '{{%payables}}', 'due_date');
            $this->createIndex('idx-payables-company', '{{%payables}}', 'company_id');
        }

        if ($this->db->getTableSchema('{{%invoices_accounting}}', true) === null) {
            $this->createTable('{{%invoices_accounting}}', [
                'id' => $this->primaryKey(),
                'invoice_number' => $this->string(30)->notNull(),
                'type' => "ENUM('receivable','payable') NOT NULL",
                'customer_id' => $this->integer()->null(),
                'vendor_name' => $this->string(255)->null(),
                'contract_id' => $this->integer()->null(),
                'issue_date' => $this->date()->notNull(),
                'due_date' => $this->date()->null(),
                'subtotal' => $this->decimal(15, 2)->notNull()->defaultValue(0),
                'tax_amount' => $this->decimal(15, 2)->notNull()->defaultValue(0),
                'total' => $this->decimal(15, 2)->notNull(),
                'paid_amount' => $this->decimal(15, 2)->notNull()->defaultValue(0),
                'status' => "ENUM('draft','issued','partial','paid','cancelled') NOT NULL DEFAULT 'draft'",
                'journal_entry_id' => $this->integer()->null(),
                'receivable_id' => $this->integer()->null(),
                'payable_id' => $this->integer()->null(),
                'company_id' => $this->integer()->null(),
                'notes' => $this->text()->null(),
                'created_by' => $this->integer()->null(),
                'created_at' => $this->integer()->null(),
                'updated_at' => $this->integer()->null(),
            ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            $this->addForeignKey('fk-inv_acc-journal', '{{%invoices_accounting}}', 'journal_entry_id', '{{%journal_entries}}', 'id', 'SET NULL');
            $this->addForeignKey('fk-inv_acc-receivable', '{{%invoices_accounting}}', 'receivable_id', '{{%receivables}}', 'id', 'SET NULL');
            $this->addForeignKey('fk-inv_acc-payable', '{{%invoices_accounting}}', 'payable_id', '{{%payables}}', 'id', 'SET NULL');
            $this->createIndex('idx-inv_acc-type', '{{%invoices_accounting}}', 'type');
            $this->createIndex('idx-inv_acc-status', '{{%invoices_accounting}}', 'status');
            $this->createIndex('idx-inv_acc-company', '{{%invoices_accounting}}', 'company_id');
            $this->createIndex('uq-inv_acc-number', '{{%invoices_accounting}}', 'invoice_number', true);
        }
    }

    public function safeDown()
    {
        $this->dropTable('{{%invoices_accounting}}');
        $this->dropTable('{{%payables}}');
        $this->dropTable('{{%receivables}}');
    }
}
