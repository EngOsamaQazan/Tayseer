<?php

use yii\db\Migration;

/**
 * Class m220419_202437_fix_financial_transaction_bank_number_data_type
 */
class m220419_202437_fix_financial_transaction_bank_number_data_type extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%financial_transaction}}','bank_number',$this->string()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220419_202437_fix_financial_transaction_bank_number_data_type cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220419_202437_fix_financial_transaction_bank_number_data_type cannot be reverted.\n";

        return false;
    }
    */
}
