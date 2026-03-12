<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%change_time_to_int_in_loan_sch}}`.
 */
class m210322_122553_create_change_time_to_int_in_loan_sch_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%loan_scheduling}}','created_at',$this->integer()->notNull());
        $this->alterColumn('{{%loan_scheduling}}','updated_at',$this->integer()->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%change_time_to_int_in_loan_sch}}');
    }
}
