<?php

use yii\db\Migration;

/**
 * Class m220702_065418_add_new_indexing
 */
class m220702_065418_add_new_indexing extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createIndex('customers_primary_name_idx', '{{%customers}}', 'name');
        $this->createIndex('customers_primary_status_idx', '{{%customers}}', 'status');
        $this->createIndex('income_primary_contract_id_idx', '{{%income}}', 'contract_id');
        $this->createIndex('income_primary_created_by_idx', '{{%income}}', 'created_by');
        $this->createIndex('income_primary_payment_type_idx', '{{%income}}', 'payment_type');
        $this->createIndex('income_primary_by_idx', '{{%income}}', '_by');
        $this->createIndex('income_primary_type_idx', '{{%income}}', 'type');
    }


    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('customers_primary_name_idx', '{{%customers}}');
        $this->dropIndex('customers_primary_status_idx', '{{%customers}}');
        $this->dropIndex('income_primary_contract_id_idx', '{{%income}}');
        $this->dropIndex('income_primary_created_by_idx', '{{%income}}');
        $this->dropIndex('income_primary_payment_type_idx', '{{%income}}');
        $this->dropIndex('income_primary_by_idx', '{{%income}}');
        $this->dropIndex('income_primary_type_idx', '{{%income}}');

    }

/*
 // Use up()/down() to run migration code without a transaction.
 public function up()
 {
 }
 public function down()
 {
 echo "m220702_065418_add_new_indexing cannot be reverted.\n";
 return false;
 }
 */
}
