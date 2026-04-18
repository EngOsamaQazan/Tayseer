<?php

use yii\db\Migration;

/**
 * Stores Social Security statements ("كشف البيانات التفصيلي") uploaded
 * during the customer wizard, and the structured rows extracted from each
 * statement (subscription periods + yearly salary history).
 *
 * Design decisions:
 *   • Linked to **customer_id** (not contract_id): the statement reflects
 *     the customer's overall financial standing; subsequent contracts for
 *     the same customer benefit from the same data.
 *   • Full history is preserved (audit-friendly). The latest statement
 *     per customer is flagged via `is_current = 1` — uniquely enforced.
 *   • Re-uploading a statement with the same `statement_date` is treated
 *     as an UPDATE (UNIQUE on `customer_id + statement_date`), avoiding
 *     duplicates while keeping older issuance dates intact.
 *   • Children (`subscriptions`, `salaries`) cascade on parent delete; the
 *     denormalised `customer_id` columns power fast per-customer reports
 *     without joining through the parent.
 */
class m260416_120000_create_customer_ss_tables extends Migration
{
    public function safeUp()
    {
        $tableOptions = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;

        // ────────────────────────── Parent ──────────────────────────
        if ($this->db->getTableSchema('{{%customer_ss_statements}}', true) === null) {
            $this->createTable('{{%customer_ss_statements}}', [
                'id'                        => $this->primaryKey(),
                'customer_id'               => $this->integer()->notNull(),
                // os_ImageManager.id is INT UNSIGNED — the FK column must
                // match exactly or MySQL rejects the constraint.
                'media_id'                  => $this->integer()->unsigned()->null()
                    ->comment('FK → os_ImageManager (uploaded PDF/image of the statement).'),
                'statement_date'            => $this->date()->null()
                    ->comment('Issuance date as printed on the statement.'),
                'social_security_number'    => $this->string(32)->null(),
                'national_id_number'        => $this->string(32)->null(),
                'join_date'                 => $this->date()->null(),
                'subjection_salary'         => $this->decimal(12, 2)->null(),
                'current_employer_id'       => $this->integer()->null()
                    ->comment('Resolved FK → os_jobs.id when employer is recognised.'),
                'current_employer_no'       => $this->string(64)->null(),
                'current_employer_name'     => $this->string(255)->null(),
                'subjection_employer_name'  => $this->string(255)->null(),
                'latest_salary_year'        => $this->smallInteger()->null(),
                'latest_monthly_salary'     => $this->decimal(12, 2)->null(),
                'total_subscription_months' => $this->smallInteger()->null(),
                'active_subscription'       => $this->boolean()->notNull()->defaultValue(false),
                'is_current'                => $this->boolean()->notNull()->defaultValue(false)
                    ->comment('1 = newest statement for this customer (computed).'),
                'extracted_payload'         => $this->text()->null()
                    ->comment('Raw normalised JSON returned by VisionService (audit/debug).'),
                'created_by'                => $this->integer()->null(),
                'created_at'                => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
                'updated_at'                => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')
                    ->append('ON UPDATE CURRENT_TIMESTAMP'),
            ], $tableOptions);

            $this->createIndex(
                'idx-cust_ss_stmt-customer',
                '{{%customer_ss_statements}}',
                ['customer_id', 'statement_date']
            );
            $this->createIndex(
                'uq-cust_ss_stmt-customer_date',
                '{{%customer_ss_statements}}',
                ['customer_id', 'statement_date'],
                true
            );

            $this->addForeignKey(
                'fk-cust_ss_stmt-customer',
                '{{%customer_ss_statements}}',
                'customer_id',
                '{{%customers}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                'fk-cust_ss_stmt-media',
                '{{%customer_ss_statements}}',
                'media_id',
                '{{%ImageManager}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        // ────────────────────── Subscription periods ──────────────────────
        if ($this->db->getTableSchema('{{%customer_ss_subscriptions}}', true) === null) {
            $this->createTable('{{%customer_ss_subscriptions}}', [
                'id'                  => $this->primaryKey(),
                'statement_id'        => $this->integer()->notNull(),
                'customer_id'         => $this->integer()->notNull()
                    ->comment('Denormalised — fast per-customer queries without join.'),
                'from_date'           => $this->date()->null(),
                'to_date'             => $this->date()->null()
                    ->comment('NULL = period still active.'),
                'salary'              => $this->decimal(12, 2)->null(),
                'reason'              => $this->string(64)->null()
                    ->comment('Reason for closing the period (e.g. استقالة).'),
                'establishment_no'    => $this->string(64)->null(),
                'establishment_name'  => $this->string(255)->null(),
                'months'              => $this->smallInteger()->null(),
                'sort_order'          => $this->smallInteger()->notNull()->defaultValue(0),
            ], $tableOptions);

            $this->createIndex(
                'idx-cust_ss_sub-statement',
                '{{%customer_ss_subscriptions}}',
                'statement_id'
            );
            $this->createIndex(
                'idx-cust_ss_sub-customer_from',
                '{{%customer_ss_subscriptions}}',
                ['customer_id', 'from_date']
            );

            $this->addForeignKey(
                'fk-cust_ss_sub-statement',
                '{{%customer_ss_subscriptions}}',
                'statement_id',
                '{{%customer_ss_statements}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                'fk-cust_ss_sub-customer',
                '{{%customer_ss_subscriptions}}',
                'customer_id',
                '{{%customers}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        // ────────────────────── Yearly salary history ──────────────────────
        if ($this->db->getTableSchema('{{%customer_ss_salaries}}', true) === null) {
            $this->createTable('{{%customer_ss_salaries}}', [
                'id'                  => $this->primaryKey(),
                'statement_id'        => $this->integer()->notNull(),
                'customer_id'         => $this->integer()->notNull(),
                'year'                => $this->smallInteger()->notNull(),
                'salary'              => $this->decimal(12, 2)->null(),
                'establishment_no'    => $this->string(64)->null(),
                'establishment_name'  => $this->string(255)->null(),
            ], $tableOptions);

            $this->createIndex(
                'idx-cust_ss_sal-statement',
                '{{%customer_ss_salaries}}',
                'statement_id'
            );
            $this->createIndex(
                'idx-cust_ss_sal-customer_year',
                '{{%customer_ss_salaries}}',
                ['customer_id', 'year']
            );

            $this->addForeignKey(
                'fk-cust_ss_sal-statement',
                '{{%customer_ss_salaries}}',
                'statement_id',
                '{{%customer_ss_statements}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                'fk-cust_ss_sal-customer',
                '{{%customer_ss_salaries}}',
                'customer_id',
                '{{%customers}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->getTableSchema('{{%customer_ss_salaries}}', true) !== null) {
            $this->dropTable('{{%customer_ss_salaries}}');
        }
        if ($this->db->getTableSchema('{{%customer_ss_subscriptions}}', true) !== null) {
            $this->dropTable('{{%customer_ss_subscriptions}}');
        }
        if ($this->db->getTableSchema('{{%customer_ss_statements}}', true) !== null) {
            $this->dropTable('{{%customer_ss_statements}}');
        }
    }
}
