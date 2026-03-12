<?php

use yii\db\Migration;

/**
 * Class m220201_203942_add_required_to_columns_2
 */
class m220201_203942_add_required_to_columns_2 extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
$this->alterColumn('{{%phone_numbers}}','owner_name',$this->string()->notNull());
$this->alterColumn('{{%phone_numbers}}','phone_number_owner',$this->string()->notNull());
$this->alterColumn('{{%phone_numbers}}','fb_account',$this->string()->notNull());
$this->alterColumn('{{%phone_numbers}}','phone_number',$this->string()->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220201_203942_add_required_to_columns_2 cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220201_203942_add_required_to_columns_2 cannot be reverted.\n";

        return false;
    }
    */
}
