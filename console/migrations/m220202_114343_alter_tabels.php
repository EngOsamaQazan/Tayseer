<?php

use yii\db\Migration;

/**
 * Class m220202_114343_alter_tabels
 */
class m220202_114343_alter_tabels extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->alterColumn('{{%phone_numbers}}','fb_account',$this->string()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220202_114343_alter_tabels cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220202_114343_alter_tabels cannot be reverted.\n";

        return false;
    }
    */
}
