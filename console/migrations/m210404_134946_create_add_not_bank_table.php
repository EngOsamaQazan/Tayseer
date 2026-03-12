<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%add_not_bank}}`.
 */
class m210404_134946_create_add_not_bank_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%financial_transaction}}', 'notes', $this->text());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%financial_transaction}}', 'notes');
    }
}
