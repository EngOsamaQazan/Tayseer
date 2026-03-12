<?php

use yii\db\Migration;

/**
 * Class m230122_200037_add_iban_to_compnay_bank_table
 */
class m230122_200037_add_iban_to_compnay_bank_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%company_banks}}', 'iban_number', $this->string(255)->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
       $this->dropColumn('{{%company_banks}}', 'iban_number');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230122_200037_add_iban_to_compnay_bank_table cannot be reverted.\n";

        return false;
    }
    */
}
