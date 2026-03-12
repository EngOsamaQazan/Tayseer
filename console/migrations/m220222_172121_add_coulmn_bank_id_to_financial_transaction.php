<?php

use yii\db\Migration;

/**
 * Class m220222_172121_add_coulmn_bank_id_to_financial_transaction
 */
class m220222_172121_add_coulmn_bank_id_to_financial_transaction extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
$this->addColumn('{{%financial_transaction}}','bank_id',$this->integer(11)->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
  $this->dropColumn('{{%financial_transaction}}','bank_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220222_172121_add_coulmn_bank_id_to_financial_transaction cannot be reverted.\n";

        return false;
    }
    */
}
