<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%is_transfer_to_financial_transaction}}`.
 */
class m210201_173753_create_is_transfer_to_financial_transaction_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%financial_transaction}}','is_transfer',$this->integer()->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%is_transfer_to_financial_transaction}}');
    }
}
