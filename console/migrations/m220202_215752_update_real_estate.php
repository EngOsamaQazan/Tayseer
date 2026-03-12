<?php

use yii\db\Migration;

/**
 * Class m220202_215752_update_real_estate
 */
class m220202_215752_update_real_estate extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%real_estate}}','customer_id',$this->integer()->null());
        $this->alterColumn('{{%real_estate}}','property_type',$this->string()->null());
        $this->alterColumn('{{%real_estate}}','property_number',$this->string()->null());

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220202_215752_update_real_estate cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220202_215752_update_real_estate cannot be reverted.\n";

        return false;
    }
    */
}
