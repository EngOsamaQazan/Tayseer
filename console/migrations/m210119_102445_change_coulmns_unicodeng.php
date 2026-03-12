<?php

use yii\db\Migration;

/**
 * Class m210119_102445_change_coulmns_unicodeng
 */
class m210119_102445_change_coulmns_unicodeng extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('ALTER TABLE `os_inventory_suppliers` CHANGE `name` `name` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, CHANGE `adress` `adress` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, CHANGE `phone_number` `phone_number` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210119_102445_change_coulmns_unicodeng cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210119_102445_change_coulmns_unicodeng cannot be reverted.\n";

        return false;
    }
    */
}
