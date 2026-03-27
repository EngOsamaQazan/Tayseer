<?php

use yii\db\Migration;

/**
 * Adds missing indexes on heavily-queried columns to speed up
 * JOINs, WHERE filters, and aggregate queries system-wide.
 */
class m260327_000001_add_performance_indexes extends Migration
{
    public function safeUp()
    {
        $indexes = [
            // ─── os_contracts — base table for most views ───
            ['{{%contracts}}', 'idx_contracts_status', 'status'],
            ['{{%contracts}}', 'idx_contracts_is_deleted', 'is_deleted'],
            ['{{%contracts}}', 'idx_contracts_followed_by', 'followed_by'],
            ['{{%contracts}}', 'idx_contracts_seller_id', 'seller_id'],
            ['{{%contracts}}', 'idx_contracts_company_id', 'company_id'],
            ['{{%contracts}}', 'idx_contracts_status_deleted', ['status', 'is_deleted']],

            // ─── os_follow_up — latest follow-up lookup ───
            ['{{%follow_up}}', 'idx_followup_contract_id', 'contract_id'],
            ['{{%follow_up}}', 'idx_followup_contract_latest', ['contract_id', 'id']],

            // ─── os_income — payment aggregates ───
            ['{{%income}}', 'idx_income_contract_id', 'contract_id'],

            // ─── os_loan_scheduling — installment lookup ───
            ['{{%loan_scheduling}}', 'idx_loansched_contract_deleted', ['contract_id', 'is_deleted']],

            // ─── os_judiciary — legal case lookups ───
            ['{{%judiciary}}', 'idx_judiciary_contract_id', 'contract_id'],
            ['{{%judiciary}}', 'idx_judiciary_is_deleted', 'is_deleted'],

            // ─── os_expenses — expense aggregates ───
            ['{{%expenses}}', 'idx_expenses_contract_id', 'contract_id'],
            ['{{%expenses}}', 'idx_expenses_is_deleted', 'is_deleted'],

            // ─── os_contract_adjustments ───
            ['{{%contract_adjustments}}', 'idx_adj_contract_id', 'contract_id'],
            ['{{%contract_adjustments}}', 'idx_adj_is_deleted', 'is_deleted'],

            // ─── os_contracts_customers — JOINs everywhere ───
            ['{{%contracts_customers}}', 'idx_cc_contract_id', 'contract_id'],
            ['{{%contracts_customers}}', 'idx_cc_customer_id', 'customer_id'],
            ['{{%contracts_customers}}', 'idx_cc_contract_type', ['contract_id', 'customer_type']],

            // ─── os_customers — search by name, phone, ID number ───
            ['{{%customers}}', 'idx_customers_is_deleted', 'is_deleted'],
            ['{{%customers}}', 'idx_customers_id_number', 'id_number'],

            // ─── os_judiciary_customers_actions ───
            ['{{%judiciary_customers_actions}}', 'idx_jca_judiciary_id', 'judiciary_id'],
            ['{{%judiciary_customers_actions}}', 'idx_jca_customers_id', 'customers_id'],
            ['{{%judiciary_customers_actions}}', 'idx_jca_is_deleted', 'is_deleted'],
            ['{{%judiciary_customers_actions}}', 'idx_jca_action_date', 'action_date'],

            // ─── os_contract_installment — payment tracking ───
            ['{{%contract_installment}}', 'idx_ci_contract_id', 'contract_id'],

            // ─── auth tables — RBAC lookups ───
            ['{{%auth_assignment}}', 'idx_auth_assign_user', 'user_id'],
        ];

        foreach ($indexes as [$table, $name, $columns]) {
            try {
                $tableSchema = $this->db->getTableSchema($table);
                if ($tableSchema === null) {
                    echo "  Skipping $name — table $table not found\n";
                    continue;
                }

                $cols = is_array($columns) ? $columns : [$columns];
                $skip = false;
                foreach ($cols as $col) {
                    if (!isset($tableSchema->columns[$col])) {
                        echo "  Skipping $name — column $col not found in $table\n";
                        $skip = true;
                        break;
                    }
                }
                if ($skip) continue;

                $existingIndexes = $this->db->createCommand(
                    "SHOW INDEX FROM $table WHERE Key_name = :name",
                    [':name' => $name]
                )->queryAll();

                if (!empty($existingIndexes)) {
                    echo "  Skipping $name — already exists\n";
                    continue;
                }

                $this->createIndex($name, $table, $columns);
                echo "  Created $name on $table\n";
            } catch (\Exception $e) {
                echo "  Warning: $name — " . $e->getMessage() . "\n";
            }
        }
    }

    public function safeDown()
    {
        $indexes = [
            ['{{%contracts}}', 'idx_contracts_status'],
            ['{{%contracts}}', 'idx_contracts_is_deleted'],
            ['{{%contracts}}', 'idx_contracts_followed_by'],
            ['{{%contracts}}', 'idx_contracts_seller_id'],
            ['{{%contracts}}', 'idx_contracts_company_id'],
            ['{{%contracts}}', 'idx_contracts_status_deleted'],
            ['{{%follow_up}}', 'idx_followup_contract_id'],
            ['{{%follow_up}}', 'idx_followup_contract_latest'],
            ['{{%income}}', 'idx_income_contract_id'],
            ['{{%loan_scheduling}}', 'idx_loansched_contract_deleted'],
            ['{{%judiciary}}', 'idx_judiciary_contract_id'],
            ['{{%judiciary}}', 'idx_judiciary_is_deleted'],
            ['{{%expenses}}', 'idx_expenses_contract_id'],
            ['{{%expenses}}', 'idx_expenses_is_deleted'],
            ['{{%contract_adjustments}}', 'idx_adj_contract_id'],
            ['{{%contract_adjustments}}', 'idx_adj_is_deleted'],
            ['{{%contracts_customers}}', 'idx_cc_contract_id'],
            ['{{%contracts_customers}}', 'idx_cc_customer_id'],
            ['{{%contracts_customers}}', 'idx_cc_contract_type'],
            ['{{%customers}}', 'idx_customers_is_deleted'],
            ['{{%customers}}', 'idx_customers_id_number'],
            ['{{%judiciary_customers_actions}}', 'idx_jca_judiciary_id'],
            ['{{%judiciary_customers_actions}}', 'idx_jca_customers_id'],
            ['{{%judiciary_customers_actions}}', 'idx_jca_is_deleted'],
            ['{{%judiciary_customers_actions}}', 'idx_jca_action_date'],
            ['{{%contract_installment}}', 'idx_ci_contract_id'],
            ['{{%auth_assignment}}', 'idx_auth_assign_user'],
        ];

        foreach ($indexes as [$table, $name]) {
            try {
                $this->dropIndex($name, $table);
            } catch (\Exception $e) {
                echo "  Warning: $name — " . $e->getMessage() . "\n";
            }
        }
    }
}
