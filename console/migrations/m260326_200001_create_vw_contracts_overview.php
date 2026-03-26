<?php

use yii\db\Migration;

/**
 * Phase 2.1 — vw_contracts_overview
 *
 * يجمع العقود مع الأرصدة المالية وأسماء العملاء واسم البائع
 * في view واحد بدل تكرار JOINs في كل شاشة.
 */
class m260326_200001_create_vw_contracts_overview extends Migration
{
    public function safeUp()
    {
        $p = $this->db->tablePrefix;

        $this->execute("
            CREATE OR REPLACE VIEW {$p}vw_contracts_overview AS
            SELECT
                co.id,
                co.total_value,
                co.status,
                co.is_deleted,
                co.seller_id,
                co.followed_by,
                co.Date_of_sale,
                co.monthly_installment_value,
                co.first_installment_date,
                co.first_installment_value,
                co.company_id,
                co.notes,
                co.type,
                co.is_can_not_contact,
                co.created_at,
                co.updated_at,

                cb.total_paid,
                cb.remaining_balance,
                cb.total_expenses,
                cb.total_lawyer_cost,
                cb.judiciary_case_count,
                cb.total_adjustments,
                cb.effective_installment,
                cb.effective_first_date,

                cn.client_names,
                cn.guarantor_names,
                cn.all_party_names,
                cn.client_phone,

                u.username AS seller_name

            FROM {$p}contracts co
            LEFT JOIN {$p}vw_contract_balance cb ON cb.contract_id = co.id
            LEFT JOIN {$p}vw_contract_customers_names cn ON cn.contract_id = co.id
            LEFT JOIN {$p}user u ON u.id = co.seller_id
        ");
    }

    public function safeDown()
    {
        $p = $this->db->tablePrefix;
        $this->execute("DROP VIEW IF EXISTS {$p}vw_contracts_overview");
    }
}
