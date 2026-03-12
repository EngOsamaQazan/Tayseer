<?php

use yii\db\Migration;

/**
 * Class m230122_190524_add_coulmn_to_companies_table
 */
class m230122_190524_add_coulmn_to_companies_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%companies}}', 'company_social_security_number', $this->string(255)->null());
        $this->addColumn('{{%companies}}', 'company_tax_number', $this->string(255)->null());
        $this->addColumn('{{%companies}}', 'company_email', $this->string(255)->null());
        $this->addColumn('{{%companies}}', 'company_address', $this->string(255)->null());

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
       
        $this->dropColumn('{{%companies}}', 'company_social_security_number');
        $this->dropColumn('{{%companies}}', 'company_tax_number');
        $this->dropColumn('{{%companies}}', 'company_email');
        $this->dropColumn('{{%companies}}', 'company_address');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230122_190524_add_coulmn_to_companies_table cannot be reverted.\n";

        return false;
    }
    */
}
