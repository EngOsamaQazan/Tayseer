<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%add_financial_transaction_id_to_inselementy}}`.
 */
class m210201_174329_create_add_financial_transaction_id_to_inselementy_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
       $this->addColumn('{{%Income}}','financial_transaction_id',$this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

    }
}
