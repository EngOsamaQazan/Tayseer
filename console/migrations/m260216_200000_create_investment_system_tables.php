<?php

use yii\db\Migration;

class m260216_200000_create_investment_system_tables extends Migration
{
    public function safeUp()
    {
        $db = $this->db;
        $tablePrefix = $db->tablePrefix;
        $existingCols = $db->getTableSchema($tablePrefix . 'companies')->columnNames;

        // 1. Add portfolio/investment fields to os_companies (skip if already added)
        $newCols = [
            'total_shares' => $this->integer()->null(),
            'invested_capital' => $this->decimal(15, 2)->defaultValue(0),
            'profit_share_ratio' => $this->decimal(5, 2)->null(),
            'parent_share_ratio' => $this->decimal(5, 2)->null(),
            'capital_refundable' => $this->tinyInteger()->defaultValue(0),
            'portfolio_status' => "ENUM('نشط','مجمّد','مُغلق') DEFAULT 'نشط'",
            'agreement_date' => $this->date()->null(),
            'agreement_notes' => $this->text()->null(),
        ];
        foreach ($newCols as $col => $type) {
            if (!in_array($col, $existingCols)) {
                $this->addColumn('{{%companies}}', $col, $type);
            }
        }

        // 2. Drop legacy tables if they exist
        $this->execute("DROP TABLE IF EXISTS {$tablePrefix}shares");
        $this->execute("DROP TABLE IF EXISTS {$tablePrefix}shareholders");

        $this->createTable('{{%shareholders}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(250)->notNull(),
            'phone' => $this->string(50)->null(),
            'email' => $this->string(255)->null(),
            'national_id' => $this->string(50)->null(),
            'share_count' => $this->integer()->notNull()->defaultValue(0),
            'join_date' => $this->date()->null(),
            'documents' => $this->text()->null(),
            'notes' => $this->text()->null(),
            'is_active' => $this->tinyInteger()->defaultValue(1),
            'is_deleted' => $this->integer()->defaultValue(0),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
            'created_by' => $this->integer()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // 3. Capital transactions
        $this->createTable('{{%capital_transactions}}', [
            'id' => $this->primaryKey(),
            'company_id' => $this->integer()->notNull(),
            'transaction_type' => "ENUM('إيداع','سحب','إعادة_رأس_مال') NOT NULL",
            'amount' => $this->decimal(15, 2)->notNull(),
            'transaction_date' => $this->date()->notNull(),
            'balance_after' => $this->decimal(15, 2)->null(),
            'payment_method' => $this->string(100)->null(),
            'reference_number' => $this->string(100)->null(),
            'notes' => $this->text()->null(),
            'created_by' => $this->integer()->null(),
            'created_at' => $this->integer()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->addForeignKey('fk_capital_tx_company', '{{%capital_transactions}}', 'company_id', '{{%companies}}', 'id', 'RESTRICT', 'CASCADE');

        // 4. Shared expense allocations
        $this->createTable('{{%shared_expense_allocations}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'total_amount' => $this->decimal(15, 2)->notNull(),
            'allocation_method' => "ENUM('عدد_العقود','صافي_الدين','يدوي','بالتساوي') NOT NULL",
            'allocation_date' => $this->date()->notNull(),
            'period_from' => $this->date()->null(),
            'period_to' => $this->date()->null(),
            'notes' => $this->text()->null(),
            'status' => "ENUM('مسودة','معتمد') DEFAULT 'مسودة'",
            'created_by' => $this->integer()->null(),
            'created_at' => $this->integer()->null(),
            'approved_by' => $this->integer()->null(),
            'approved_at' => $this->integer()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // 5. Shared expense allocation lines
        $this->createTable('{{%shared_expense_lines}}', [
            'id' => $this->primaryKey(),
            'allocation_id' => $this->integer()->notNull(),
            'company_id' => $this->integer()->notNull(),
            'metric_value' => $this->decimal(15, 2)->null(),
            'percentage' => $this->decimal(5, 2)->null(),
            'allocated_amount' => $this->decimal(15, 2)->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->addForeignKey('fk_sel_allocation', '{{%shared_expense_lines}}', 'allocation_id', '{{%shared_expense_allocations}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_sel_company', '{{%shared_expense_lines}}', 'company_id', '{{%companies}}', 'id', 'RESTRICT', 'CASCADE');

        // 6. Profit distributions
        $this->createTable('{{%profit_distributions}}', [
            'id' => $this->primaryKey(),
            'company_id' => $this->integer()->null(),
            'distribution_type' => "ENUM('مساهمين','محفظة') NOT NULL",
            'period_from' => $this->date()->notNull(),
            'period_to' => $this->date()->notNull(),
            'total_revenue' => $this->decimal(15, 2)->defaultValue(0),
            'direct_expenses' => $this->decimal(15, 2)->defaultValue(0),
            'shared_expenses' => $this->decimal(15, 2)->defaultValue(0),
            'net_profit' => $this->decimal(15, 2)->defaultValue(0),
            'investor_share_pct' => $this->decimal(5, 2)->null(),
            'investor_amount' => $this->decimal(15, 2)->defaultValue(0),
            'parent_amount' => $this->decimal(15, 2)->defaultValue(0),
            'distribution_amount' => $this->decimal(15, 2)->defaultValue(0),
            'status' => "ENUM('مسودة','معتمد','موزّع') DEFAULT 'مسودة'",
            'notes' => $this->text()->null(),
            'created_by' => $this->integer()->null(),
            'created_at' => $this->integer()->null(),
            'approved_by' => $this->integer()->null(),
            'approved_at' => $this->integer()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->addForeignKey('fk_pd_company', '{{%profit_distributions}}', 'company_id', '{{%companies}}', 'id', 'SET NULL', 'CASCADE');

        // 7. Profit distribution lines (per shareholder)
        $this->createTable('{{%profit_distribution_lines}}', [
            'id' => $this->primaryKey(),
            'distribution_id' => $this->integer()->notNull(),
            'shareholder_id' => $this->integer()->notNull(),
            'share_count_snapshot' => $this->integer()->null(),
            'total_shares_snapshot' => $this->integer()->null(),
            'percentage' => $this->decimal(5, 4)->null(),
            'amount' => $this->decimal(15, 2)->null(),
            'payment_status' => "ENUM('معلّق','مدفوع') DEFAULT 'معلّق'",
            'payment_date' => $this->date()->null(),
            'payment_method' => $this->string(100)->null(),
            'payment_reference' => $this->string(100)->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->addForeignKey('fk_pdl_dist', '{{%profit_distribution_lines}}', 'distribution_id', '{{%profit_distributions}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_pdl_shareholder', '{{%profit_distribution_lines}}', 'shareholder_id', '{{%shareholders}}', 'id', 'RESTRICT', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%profit_distribution_lines}}');
        $this->dropTable('{{%profit_distributions}}');
        $this->dropTable('{{%shared_expense_lines}}');
        $this->dropTable('{{%shared_expense_allocations}}');
        $this->dropTable('{{%capital_transactions}}');
        $this->dropTable('{{%shareholders}}');

        $this->dropColumn('{{%companies}}', 'total_shares');
        $this->dropColumn('{{%companies}}', 'invested_capital');
        $this->dropColumn('{{%companies}}', 'profit_share_ratio');
        $this->dropColumn('{{%companies}}', 'parent_share_ratio');
        $this->dropColumn('{{%companies}}', 'capital_refundable');
        $this->dropColumn('{{%companies}}', 'portfolio_status');
        $this->dropColumn('{{%companies}}', 'agreement_date');
        $this->dropColumn('{{%companies}}', 'agreement_notes');

        // Recreate original tables
        $this->createTable('{{%shareholders}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(250)->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $this->createTable('{{%shares}}', [
            'id' => $this->primaryKey(),
            'shareholder_id' => $this->integer()->notNull(),
            'amount' => $this->integer()->notNull(),
            'notes' => $this->string(250)->null(),
            'receive_number' => $this->string(50)->null(),
            'date' => $this->date()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }
}
