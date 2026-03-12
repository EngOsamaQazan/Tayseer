<?php

use yii\db\Migration;

/**
 * Class m220206_201914_update_custamers_table
 */
class m220206_201914_update_custamers_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%customers}}','city',$this->string()->defaultValue(0));
        $this->alterColumn('{{%customers}}','sex',$this->integer()->defaultValue(0));
        $this->alterColumn('{{%customers}}','birth_date',$this->date());
        $this->alterColumn('{{%customers}}','id_number',$this->string());
        $this->alterColumn('{{%customers}}','job_title',$this->integer());
        $this->alterColumn('{{%customers}}','hear_about_us',$this->string()->defaultValue(0));
        $this->alterColumn('{{%customers}}','citizen',$this->string()->defaultValue(0));
        $this->alterColumn('{{%customers}}','is_social_security',$this->integer()->defaultValue(0));
        $this->alterColumn('{{%customers}}','primary_phone_number',$this->string()->defaultValue(0));
        $this->alterColumn('{{%customers}}','do_have_any_property',$this->integer()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220206_201914_update_custamers_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220206_201914_update_custamers_table cannot be reverted.\n";

        return false;
    }
    */
}
