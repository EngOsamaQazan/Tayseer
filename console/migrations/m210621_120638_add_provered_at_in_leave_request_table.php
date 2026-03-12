<?php

use yii\db\Migration;

/**
 * Class m210621_120638_add_provered_at_in_leave_request_table
 */
class m210621_120638_add_provered_at_in_leave_request_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%leave_request}}', 'proved_at', $this->integer());

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
        echo "m210621_120638_add_provered_at_in_leave_request_table cannot be reverted.\n";

        return false;
    }
    */
}
