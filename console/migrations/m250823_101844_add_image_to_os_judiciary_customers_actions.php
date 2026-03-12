<?php

use yii\db\Migration;

/**
 * Class m250823_101844_add_image_to_os_judiciary_customers_actions
 */
class m250823_101844_add_image_to_os_judiciary_customers_actions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('os_judiciary_customers_actions', 'image', $this->string()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

        $this->dropColumn('os_judiciary_customers_actions', 'image');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250823_101844_add_image_to_os_judiciary_customers_actions cannot be reverted.\n";

        return false;
    }
    */
}
