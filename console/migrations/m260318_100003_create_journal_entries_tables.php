<?php

use yii\db\Migration;

class m260318_100003_create_journal_entries_tables extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%journal_entries}}', true) === null) {
            $this->createTable('{{%journal_entries}}', [
                'id' => $this->primaryKey(),
                'entry_number' => $this->string(30)->notNull(),
                'entry_date' => $this->date()->notNull(),
                'fiscal_year_id' => $this->integer()->notNull(),
                'fiscal_period_id' => $this->integer()->notNull(),
                'reference_type' => $this->string(50)->null(),
                'reference_id' => $this->integer()->null(),
                'description' => $this->text()->notNull(),
                'total_debit' => $this->decimal(15, 2)->notNull()->defaultValue(0),
                'total_credit' => $this->decimal(15, 2)->notNull()->defaultValue(0),
                'status' => "ENUM('draft','posted','reversed') NOT NULL DEFAULT 'draft'",
                'is_auto' => $this->tinyInteger(1)->notNull()->defaultValue(0),
                'company_id' => $this->integer()->null(),
                'reversed_by' => $this->integer()->null(),
                'created_by' => $this->integer()->null(),
                'created_at' => $this->integer()->null(),
                'updated_at' => $this->integer()->null(),
                'approved_by' => $this->integer()->null(),
                'approved_at' => $this->integer()->null(),
            ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            $this->addForeignKey('fk-journal_entries-fiscal_year', '{{%journal_entries}}', 'fiscal_year_id', '{{%fiscal_years}}', 'id');
            $this->addForeignKey('fk-journal_entries-fiscal_period', '{{%journal_entries}}', 'fiscal_period_id', '{{%fiscal_periods}}', 'id');
            $this->addForeignKey('fk-journal_entries-reversed_by', '{{%journal_entries}}', 'reversed_by', '{{%journal_entries}}', 'id', 'SET NULL');
            $this->createIndex('idx-journal_entries-company', '{{%journal_entries}}', 'company_id');
            $this->createIndex('idx-journal_entries-status', '{{%journal_entries}}', 'status');
            $this->createIndex('idx-journal_entries-date', '{{%journal_entries}}', 'entry_date');
            $this->createIndex('idx-journal_entries-ref', '{{%journal_entries}}', ['reference_type', 'reference_id']);
            $this->createIndex('uq-journal_entries-number_year', '{{%journal_entries}}', ['entry_number', 'fiscal_year_id'], true);
        }

        if ($this->db->getTableSchema('{{%journal_entry_lines}}', true) === null) {
            $this->createTable('{{%journal_entry_lines}}', [
                'id' => $this->primaryKey(),
                'journal_entry_id' => $this->integer()->notNull(),
                'account_id' => $this->integer()->notNull(),
                'cost_center_id' => $this->integer()->null(),
                'debit' => $this->decimal(15, 2)->notNull()->defaultValue(0),
                'credit' => $this->decimal(15, 2)->notNull()->defaultValue(0),
                'description' => $this->string(255)->null(),
                'contract_id' => $this->integer()->null(),
                'customer_id' => $this->integer()->null(),
            ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            $this->addForeignKey('fk-jel-journal_entry', '{{%journal_entry_lines}}', 'journal_entry_id', '{{%journal_entries}}', 'id', 'CASCADE');
            $this->addForeignKey('fk-jel-account', '{{%journal_entry_lines}}', 'account_id', '{{%accounts}}', 'id');
            $this->addForeignKey('fk-jel-cost_center', '{{%journal_entry_lines}}', 'cost_center_id', '{{%cost_centers}}', 'id', 'SET NULL');
            $this->createIndex('idx-jel-journal_entry', '{{%journal_entry_lines}}', 'journal_entry_id');
            $this->createIndex('idx-jel-account', '{{%journal_entry_lines}}', 'account_id');
        }
    }

    public function safeDown()
    {
        $this->dropTable('{{%journal_entry_lines}}');
        $this->dropTable('{{%journal_entries}}');
    }
}
