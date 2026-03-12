<?php

use yii\db\Migration;

/**
 * Class m210615_085752_add_contract_id_to_expenses_table
 */
class m210615_085752_add_contract_id_to_expenses_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%expenses}}', 'contract_id', $this->integer());
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
        echo "m210615_085752_add_contract_id_to_expenses_table cannot be reverted.\n";

        return false;
    }
    */
}
