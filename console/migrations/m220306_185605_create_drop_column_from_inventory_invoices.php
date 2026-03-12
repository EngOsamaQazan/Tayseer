<?php

use yii\db\Migration;

/**
 * Class m220306_185605_create_drop_column_from_inventory_invoices
 */
class m220306_185605_create_drop_column_from_inventory_invoices extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('{{%inventory_invoices}}','single_price');
        $this->dropColumn('{{%inventory_invoices}}','number');

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220306_185605_create_drop_column_from_inventory_invoices cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220306_185605_create_drop_column_from_inventory_invoices cannot be reverted.\n";

        return false;
    }
    */
}
