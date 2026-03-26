<?php

use yii\db\Migration;

/**
 * Phase 1.2 — vw_contract_customers_names
 *
 * لبنة أساسية مشتركة: تجمع أسماء أطراف كل عقد (عملاء/كفلاء)
 * بدل تكرار GROUP_CONCAT + JOIN في 8+ شاشات.
 */
class m260326_100002_create_vw_contract_customers_names extends Migration
{
    public function safeUp()
    {
        $p = $this->db->tablePrefix;

        $this->execute("
            CREATE OR REPLACE VIEW {$p}vw_contract_customers_names AS
            SELECT
                cc.contract_id,

                GROUP_CONCAT(
                    CASE WHEN cc.customer_type = 'client' THEN c.name END
                    ORDER BY c.name SEPARATOR '، '
                ) AS client_names,

                GROUP_CONCAT(
                    CASE WHEN cc.customer_type = 'guarantor' THEN c.name END
                    ORDER BY c.name SEPARATOR '، '
                ) AS guarantor_names,

                GROUP_CONCAT(
                    c.name ORDER BY c.name SEPARATOR '، '
                ) AS all_party_names,

                MIN(
                    CASE WHEN cc.customer_type = 'client' THEN c.primary_phone_number END
                ) AS client_phone

            FROM {$p}contracts_customers cc
            INNER JOIN {$p}customers c ON c.id = cc.customer_id
            GROUP BY cc.contract_id
        ");
    }

    public function safeDown()
    {
        $p = $this->db->tablePrefix;
        $this->execute("DROP VIEW IF EXISTS {$p}vw_contract_customers_names");
    }
}
