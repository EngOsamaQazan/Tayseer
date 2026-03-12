<?php

use yii\db\Migration;

/**
 * Class m220220_130537_drop_ename
 */
class m220220_130537_drop_ename extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
$this->alterColumn('os_follow_up_connection_reports','connection_type',$this->string());
$this->alterColumn('os_follow_up_connection_reports','connection_response',$this->string());
$this->alterColumn('os_follow_up','feeling',$this->string());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220220_130537_drop_ename cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220220_130537_drop_ename cannot be reverted.\n";

        return false;
    }
    */
}
