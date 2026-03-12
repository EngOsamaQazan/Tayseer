<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%add_loan_number_in_contract_custamer}}`.
 */
class m210311_105525_create_add_loan_number_in_contract_custamer_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%contracts_customers}}', 'loan_number', $this->integer(11));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }
}
