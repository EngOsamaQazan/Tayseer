<?php

use yii\db\Migration;

/**
 * Class m210429_181140_delete_totle_element_in_inventory
 */
class m210429_181140_delete_totle_element_in_inventory extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('{{%inventory_items}}','total_element');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210429_181140_delete_totle_element_in_inventory cannot be reverted.\n";

        return false;
    }
    */
}
