<?php

use yii\db\Migration;

/**
 * Class m230125_193543_add_default_value_to_is_deleted_in_judiciary_inform_address_table
 */
class m230125_193543_add_default_value_to_is_deleted_in_judiciary_inform_address_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%judiciary_inform_address}}', 'is_deleted', $this->integer()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('{{%judiciary_inform_address}}', 'is_deleted', $this->integer());
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230125_193543_add_default_value_to_is_deleted_in_judiciary_inform_address_table cannot be reverted.\n";

        return false;
    }
    */
}
