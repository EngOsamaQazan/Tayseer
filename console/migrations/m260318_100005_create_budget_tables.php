<?php

use yii\db\Migration;

class m260318_100005_create_budget_tables extends Migration
{
    public function safeUp()
    {
        // Budget header
        $this->createTable('{{%budgets}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'fiscal_year_id' => $this->integer()->notNull(),
            'status' => "ENUM('draft','approved','closed') NOT NULL DEFAULT 'draft'",
            'total_amount' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'notes' => $this->text()->null(),
            'company_id' => $this->integer()->null(),
            'approved_by' => $this->integer()->null(),
            'approved_at' => $this->integer()->null(),
            'created_by' => $this->integer()->null(),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->addForeignKey('fk-budgets-fiscal_year', '{{%budgets}}', 'fiscal_year_id', '{{%fiscal_years}}', 'id');
        $this->createIndex('idx-budgets-company', '{{%budgets}}', 'company_id');

        // Budget lines (per account, per period or annual)
        $this->createTable('{{%budget_lines}}', [
            'id' => $this->primaryKey(),
            'budget_id' => $this->integer()->notNull(),
            'account_id' => $this->integer()->notNull(),
            'cost_center_id' => $this->integer()->null(),
            'period_1' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'period_2' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'period_3' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'period_4' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'period_5' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'period_6' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'period_7' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'period_8' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'period_9' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'period_10' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'period_11' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'period_12' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'annual_total' => $this->decimal(15, 2)->notNull()->defaultValue(0),
            'notes' => $this->string(500)->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->addForeignKey('fk-budget_lines-budget', '{{%budget_lines}}', 'budget_id', '{{%budgets}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-budget_lines-account', '{{%budget_lines}}', 'account_id', '{{%accounts}}', 'id');
        $this->addForeignKey('fk-budget_lines-cost_center', '{{%budget_lines}}', 'cost_center_id', '{{%cost_centers}}', 'id', 'SET NULL');
        $this->createIndex('idx-budget_lines-budget_account', '{{%budget_lines}}', ['budget_id', 'account_id']);
    }

    public function safeDown()
    {
        $this->dropTable('{{%budget_lines}}');
        $this->dropTable('{{%budgets}}');
    }
}
