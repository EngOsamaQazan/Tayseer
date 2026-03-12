<?php

use yii\db\Migration;

/**
 * Class m230120_153259_add_judiciary_inform_address_id_to_judiciary_table
 */
class m230120_153259_add_judiciary_inform_address_id_to_judiciary_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('os_judiciary', 'judiciary_inform_address_id', $this->integer()->after('year'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('os_judiciary', 'judiciary_inform_address_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230120_153259_add_judiciary_inform_address_id_to_judiciary_table cannot be reverted.\n";

        return false;
    }
    */
}
