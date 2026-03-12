<?php

use yii\db\Migration;

/**
 * Class m220306_144141_add_company_id_to_inventory_quantity_table
 */
class m220306_144141_add_company_id_to_inventory_quantity_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%inventory_item_quantities}}','company_id',$this->integer());

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    $this->dropColumn('{{inventory_item_quantities}}','company_id');

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220306_144141_add_company_id_to_inventory_quantity_table cannot be reverted.\n";

        return false;
    }
    */
}
