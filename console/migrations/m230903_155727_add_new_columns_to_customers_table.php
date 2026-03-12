<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%customers}}`.
 */
class m230903_155727_add_new_columns_to_customers_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add has_social_security_salary column with 'yes' or 'no' options using native MySQL query
        $this->execute("ALTER TABLE {{%customers}} ADD COLUMN has_social_security_salary ENUM('yes', 'no') NOT NULL DEFAULT 'no'");

        // Add social_security_salary_source column with multiple options (assuming text for now, you can adjust as needed)
        $this->addColumn('{{%customers}}', 'social_security_salary_source', $this->text());

        // Add retirement_status column with 'effective' or 'stopped' options using native MySQL query
        $this->execute("ALTER TABLE {{%customers}} ADD COLUMN retirement_status ENUM('effective', 'stopped') NOT NULL DEFAULT 'stopped'");

        // Add total_retirement_income column (assuming decimal for now, you can adjust as needed)
        $this->addColumn('{{%customers}}', 'total_retirement_income', $this->decimal(10,2));

        // Add last_income_query_date column
        $this->addColumn('{{%customers}}', 'last_income_query_date', $this->date());

        $this->addColumn('{{%customers}}', 'last_job_query_date', $this->date());
        $this->addColumn('{{%customers}}', 'total_salary', $this->decimal(10,2));


    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%customers}}', 'has_social_security_salary');
        $this->dropColumn('{{%customers}}', 'social_security_salary_source');
        $this->dropColumn('{{%customers}}', 'retirement_status');
        $this->dropColumn('{{%customers}}', 'total_retirement_income');
        $this->dropColumn('{{%customers}}', 'last_income_query_date');
        $this->dropColumn('{{%customers}}', 'last_job_query_date');
        $this->dropColumn('{{%customers}}', 'total_salary');


    }
}
