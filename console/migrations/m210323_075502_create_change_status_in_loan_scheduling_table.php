<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%change_status_in_loan_scheduling}}`.
 */
class m210323_075502_create_change_status_in_loan_scheduling_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
     $this->alterColumn('{{%loan_scheduling}}','status',$this->string());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }
}
