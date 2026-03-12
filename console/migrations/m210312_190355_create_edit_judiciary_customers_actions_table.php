<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%edit_judiciary_customers_actions}}`.
 */
class m210312_190355_create_edit_judiciary_customers_actions_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%judiciary_customers_actions}}', 'customers_id', $this->integer());
        $this->addColumn('{{%judiciary_customers_actions}}', 'contract_id', $this->integer());
        $this->addColumn('{{%judiciary_customers_actions}}','action_date',$this->date()->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }
}
