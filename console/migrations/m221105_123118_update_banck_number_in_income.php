<?php

use yii\db\Migration;

/**
 * Class m221105_123118_update_banck_number_in_income
 */
class m221105_123118_update_banck_number_in_income extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%income}}','bank_number',$this->string());

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m221105_123118_update_banck_number_in_income cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m221105_123118_update_banck_number_in_income cannot be reverted.\n";

        return false;
    }
    */
}
