<?php

use yii\db\Migration;

/**
 * Class m220128_175721_add_cane_not_contact_to_contracts_table
 */
class m220128_175721_add_cane_not_contact_to_contracts_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%contracts}}', 'is_can_not_contact', $this->boolean()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
  $this->dropColumn('{{%contracts}}','cane_not_contact');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220128_175721_add_cane_not_contact_to_contracts_table cannot be reverted.\n";

        return false;
    }
    */
}
