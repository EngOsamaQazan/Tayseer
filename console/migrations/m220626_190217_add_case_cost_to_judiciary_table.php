<?php

use yii\db\Migration;

/**
 * Class m220626_190217_add_case_cost_to_judiciary_table
 */
class m220626_190217_add_case_cost_to_judiciary_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        //note : this action just to avoid the drop  coulmn effict
        $this->addColumn('{{%judiciary}}', 'case_cost', $this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%judiciary}}','case_cost');

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220626_190217_add_case_cost_to_judiciary_table cannot be reverted.\n";

        return false;
    }
    */
}
