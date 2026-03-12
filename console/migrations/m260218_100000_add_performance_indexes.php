<?php

use yii\db\Migration;

class m260218_100000_add_performance_indexes extends Migration
{
    public function safeUp()
    {
        // os_contracts — filtered on every contracts listing
        $this->createIndexIfMissing('os_contracts', 'idx-contracts-company_del_status', ['company_id', 'is_deleted', 'status']);
        $this->createIndexIfMissing('os_contracts', 'idx-contracts-followed_by', ['followed_by']);
        $this->createIndexIfMissing('os_contracts', 'idx-contracts-date_of_sale', ['Date_of_sale']);

        // os_judiciary — filtered in contracts view, reports, dashboard
        $this->createIndexIfMissing('os_judiciary', 'idx-judiciary-contract_del', ['contract_id', 'is_deleted']);
        $this->createIndexIfMissing('os_judiciary', 'idx-judiciary-company', ['company_id']);

        // os_income — SUM queries on every contract row (ContractInstallment uses os_income table)
        $this->createIndexIfMissing('os_income', 'idx-income-contract', ['contract_id']);

        // os_expenses — SUM queries filtered by contract + category
        $this->createIndexIfMissing('os_expenses', 'idx-expenses-contract_cat', ['contract_id', 'category_id']);

        // os_customers — filtered on almost every customer query
        $this->createIndexIfMissing('os_customers', 'idx-customers-is_deleted', ['is_deleted']);
        $this->createIndexIfMissing('os_customers', 'idx-customers-id_number', ['id_number']);

        // os_inventory_serial_numbers — filtered in invoices and contracts
        $this->createIndexIfMissing('os_inventory_serial_numbers', 'idx-serial-item_del_company', ['item_id', 'is_deleted', 'company_id']);
        $this->createIndexIfMissing('os_inventory_serial_numbers', 'idx-serial-contract_status', ['contract_id', 'status']);
        $this->createIndexIfMissing('os_inventory_serial_numbers', 'idx-serial-status_del', ['status', 'is_deleted']);

        // os_items_inventory_invoices — filtered when viewing/editing invoices
        $this->createIndexIfMissing('os_items_inventory_invoices', 'idx-inv_items-invoice_del', ['inventory_invoices_id', 'is_deleted']);

        // os_inventory_items — filtered on dashboard and listings
        $this->createIndexIfMissing('os_inventory_items', 'idx-inv_items-status_del', ['status', 'is_deleted']);
        $this->createIndexIfMissing('os_inventory_items', 'idx-inv_items-supplier', ['supplier_id']);

        // os_stock_movements — ordered by date in item history
        $this->createIndexIfMissing('os_stock_movements', 'idx-stock-item_created', ['item_id', 'created_at']);

        // os_follow_up — filtered and ordered in follow-up screens
        $this->createIndexIfMissing('os_follow_up', 'idx-followup-contract_date', ['contract_id', 'date_time']);

        // os_judiciary_customers_actions — filtered in judiciary views
        $this->createIndexIfMissing('os_judiciary_customers_actions', 'idx-jud_cust-judiciary_del', ['judiciary_id', 'is_deleted']);
        $this->createIndexIfMissing('os_judiciary_customers_actions', 'idx-jud_cust-customer', ['customers_id']);

        // os_loan_scheduling — filtered in dashboard and reports
        $this->createIndexIfMissing('os_loan_scheduling', 'idx-loan-contract_del', ['contract_id', 'is_deleted']);

        // os_financial_transaction — filtered in collection views
        $this->createIndexIfMissing('os_financial_transaction', 'idx-fin_trans-contract_type', ['contract_id', 'income_type']);

        // os_follow_up_connection_reports — filtered by follow-up id
        $this->createIndexIfMissing('os_follow_up_connection_reports', 'idx-followup_conn-followup', ['os_follow_up_id']);
    }

    public function safeDown()
    {
        $indexes = [
            'os_contracts' => ['idx-contracts-company_del_status', 'idx-contracts-followed_by', 'idx-contracts-date_of_sale'],
            'os_judiciary' => ['idx-judiciary-contract_del', 'idx-judiciary-company'],
            'os_income' => ['idx-income-contract'],
            'os_expenses' => ['idx-expenses-contract_cat'],
            'os_customers' => ['idx-customers-is_deleted', 'idx-customers-id_number'],
            'os_inventory_serial_numbers' => ['idx-serial-item_del_company', 'idx-serial-contract_status', 'idx-serial-status_del'],
            'os_items_inventory_invoices' => ['idx-inv_items-invoice_del'],
            'os_inventory_items' => ['idx-inv_items-status_del', 'idx-inv_items-supplier'],
            'os_stock_movements' => ['idx-stock-item_created'],
            'os_follow_up' => ['idx-followup-contract_date'],
            'os_judiciary_customers_actions' => ['idx-jud_cust-judiciary_del', 'idx-jud_cust-customer'],
            'os_loan_scheduling' => ['idx-loan-contract_del'],
            'os_financial_transaction' => ['idx-fin_trans-contract_type'],
            'os_follow_up_connection_reports' => ['idx-followup_conn-followup'],
        ];

        foreach ($indexes as $table => $idxNames) {
            foreach ($idxNames as $idx) {
                try {
                    $this->dropIndex($idx, $table);
                } catch (\Exception $e) {
                }
            }
        }
    }

    private function createIndexIfMissing(string $table, string $name, array $columns): void
    {
        try {
            $this->createIndex($name, $table, $columns);
        } catch (\Exception $e) {
            echo "  > Index $name on $table skipped (may already exist): {$e->getMessage()}\n";
        }
    }
}
