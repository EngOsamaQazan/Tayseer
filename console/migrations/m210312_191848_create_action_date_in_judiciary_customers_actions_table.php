<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%action_date_in_judiciary_customers_actions}}`.
 */
class m210312_191848_create_action_date_in_judiciary_customers_actions_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%judiciary_customers_actions}}','action_date',$this->dateTime());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

    }
}
