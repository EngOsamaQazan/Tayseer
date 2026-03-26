<?php

use yii\db\Migration;

class m260318_100000_create_accounting_foundation_tables extends Migration
{
    public function safeUp()
    {
        // 1. Chart of Accounts
        if ($this->db->getTableSchema('{{%accounts}}', true) === null) {
            $this->createTable('{{%accounts}}', [
                'id' => $this->primaryKey(),
                'code' => $this->string(20)->notNull()->unique(),
                'name_ar' => $this->string(255)->notNull(),
                'name_en' => $this->string(255)->null(),
                'parent_id' => $this->integer()->null(),
                'type' => "ENUM('assets','liabilities','equity','revenue','expenses') NOT NULL",
                'nature' => "ENUM('debit','credit') NOT NULL",
                'level' => $this->tinyInteger()->notNull()->defaultValue(1),
                'is_parent' => $this->tinyInteger(1)->notNull()->defaultValue(0),
                'is_active' => $this->tinyInteger(1)->notNull()->defaultValue(1),
                'company_id' => $this->integer()->null(),
                'opening_balance' => $this->decimal(15, 2)->notNull()->defaultValue(0),
                'description' => $this->text()->null(),
                'created_by' => $this->integer()->null(),
                'created_at' => $this->integer()->null(),
                'updated_at' => $this->integer()->null(),
            ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            $this->addForeignKey('fk-accounts-parent', '{{%accounts}}', 'parent_id', '{{%accounts}}', 'id', 'SET NULL');
            $this->createIndex('idx-accounts-company', '{{%accounts}}', 'company_id');
            $this->createIndex('idx-accounts-type', '{{%accounts}}', 'type');
            $this->createIndex('idx-accounts-parent', '{{%accounts}}', 'parent_id');
        }

        // 2. Fiscal Years
        if ($this->db->getTableSchema('{{%fiscal_years}}', true) === null) {
            $this->createTable('{{%fiscal_years}}', [
                'id' => $this->primaryKey(),
                'name' => $this->string(100)->notNull(),
                'start_date' => $this->date()->notNull(),
                'end_date' => $this->date()->notNull(),
                'status' => "ENUM('open','closed','locked') NOT NULL DEFAULT 'open'",
                'company_id' => $this->integer()->null(),
                'is_current' => $this->tinyInteger(1)->notNull()->defaultValue(0),
                'created_by' => $this->integer()->null(),
                'created_at' => $this->integer()->null(),
                'updated_at' => $this->integer()->null(),
            ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            $this->createIndex('idx-fiscal_years-company', '{{%fiscal_years}}', 'company_id');
            $this->createIndex('idx-fiscal_years-status', '{{%fiscal_years}}', 'status');
        }

        // 3. Fiscal Periods
        if ($this->db->getTableSchema('{{%fiscal_periods}}', true) === null) {
            $this->createTable('{{%fiscal_periods}}', [
                'id' => $this->primaryKey(),
                'fiscal_year_id' => $this->integer()->notNull(),
                'period_number' => $this->tinyInteger()->notNull(),
                'name' => $this->string(50)->notNull(),
                'start_date' => $this->date()->notNull(),
                'end_date' => $this->date()->notNull(),
                'status' => "ENUM('open','closed') NOT NULL DEFAULT 'open'",
                'created_at' => $this->integer()->null(),
                'updated_at' => $this->integer()->null(),
            ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            $this->addForeignKey('fk-fiscal_periods-year', '{{%fiscal_periods}}', 'fiscal_year_id', '{{%fiscal_years}}', 'id', 'CASCADE');
            $this->createIndex('idx-fiscal_periods-year', '{{%fiscal_periods}}', 'fiscal_year_id');
            $this->createIndex('uq-fiscal_periods-year_number', '{{%fiscal_periods}}', ['fiscal_year_id', 'period_number'], true);
        }

        // 4. Cost Centers
        if ($this->db->getTableSchema('{{%cost_centers}}', true) === null) {
            $this->createTable('{{%cost_centers}}', [
                'id' => $this->primaryKey(),
                'code' => $this->string(20)->notNull(),
                'name' => $this->string(255)->notNull(),
                'parent_id' => $this->integer()->null(),
                'company_id' => $this->integer()->null(),
                'is_active' => $this->tinyInteger(1)->notNull()->defaultValue(1),
                'created_by' => $this->integer()->null(),
                'created_at' => $this->integer()->null(),
                'updated_at' => $this->integer()->null(),
            ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

            $this->addForeignKey('fk-cost_centers-parent', '{{%cost_centers}}', 'parent_id', '{{%cost_centers}}', 'id', 'SET NULL');
            $this->createIndex('idx-cost_centers-company', '{{%cost_centers}}', 'company_id');
            $this->createIndex('idx-cost_centers-parent', '{{%cost_centers}}', 'parent_id');
        }
    }

    public function safeDown()
    {
        $this->dropTable('{{%cost_centers}}');
        $this->dropTable('{{%fiscal_periods}}');
        $this->dropTable('{{%fiscal_years}}');
        $this->dropTable('{{%accounts}}');
    }
}
