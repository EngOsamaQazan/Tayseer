<?php

use yii\db\Migration;

/**
 * Class m210705_124319_change_in_job_title_in_custamers_table
 */
class m210705_124319_change_in_job_title_in_custamers_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('{{%customers}}','job_title');
        $this->addColumn('{{%customers}}','job_title',$this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210705_124319_change_in_job_title_in_custamers_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210705_124319_change_in_job_title_in_custamers_table cannot be reverted.\n";

        return false;
    }
    */
}
