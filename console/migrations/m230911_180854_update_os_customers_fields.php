<?php

use yii\db\Migration;

/**
 * Class m230911_180854_update_os_customers_fields
 */
class m230911_180854_update_os_customers_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Making columns nullable to match the 'skipOnEmpty' => true validation
        $this->alterColumn('os_customers', 'has_social_security_salary', $this->string()->null());
        $this->alterColumn('os_customers', 'social_security_salary_source', $this->string()->null());
        $this->alterColumn('os_customers', 'retirement_status', $this->string()->null());
        $this->alterColumn('os_customers', 'total_retirement_income', $this->decimal()->null());
        $this->alterColumn('os_customers', 'total_salary', $this->decimal()->null());

        // For dates, if you want them to be nullable, adjust accordingly.
        $this->alterColumn('os_customers', 'last_income_query_date', $this->date()->null());
        $this->alterColumn('os_customers', 'last_job_query_date', $this->date()->null());


    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Reverting columns to be NOT NULL 
        $this->alterColumn('os_customers', 'has_social_security_salary', $this->string()->notNull());
        $this->alterColumn('os_customers', 'social_security_salary_source', $this->string()->notNull());
        $this->alterColumn('os_customers', 'retirement_status', $this->string()->notNull());
        $this->alterColumn('os_customers', 'total_retirement_income', $this->decimal()->notNull());
        $this->alterColumn('os_customers', 'total_salary', $this->decimal()->notNull());

        // For dates, if you adjusted them in the up() method:
        $this->alterColumn('os_customers', 'last_income_query_date', $this->date()->notNull());
        $this->alterColumn('os_customers', 'last_job_query_date', $this->date()->notNull());

        // If everything reverts successfully, return true
        return true;
    }


    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230911_180854_update_os_customers_fields cannot be reverted.\n";

        return false;
    }
    */
}