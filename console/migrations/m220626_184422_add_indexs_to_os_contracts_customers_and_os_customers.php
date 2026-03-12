<?php

use yii\db\Migration;

/**
 * Class m220626_184422_add_indexs_to_os_contracts_customers_and_os_customers
 */
class m220626_184422_add_indexs_to_os_contracts_customers_and_os_customers extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createIndex('contracts_customers_contract_id_idx', '{{%contracts_customers}}', 'contract_id');
        $this->createIndex('contracts_customers_customer_id_idx', '{{%contracts_customers}}', 'customer_id');
        $this->createIndex('customers_primary_phone_number_idx', '{{%customers}}', 'primary_phone_number');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('contracts_customers_contract_id_idx', '{{%contracts_customers}}');
        $this->dropIndex('contracts_customers_customer_id_idx', '{{%contracts_customers}}');
        $this->dropIndex('customers_primary_phone_number_idx', '{{%customers}}');
    }

}
