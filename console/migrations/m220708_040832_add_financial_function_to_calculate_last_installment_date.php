<?php

use yii\db\Migration;

/**
 * Class m220708_040832_add_financial_function_to_calculate_last_installment_date
 */
class m220708_040832_add_financial_function_to_calculate_last_installment_date extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("
        CREATE OR REPLACE FUNCTION `get_should_payments_date`(first_installment_date DATE , monthly_installment_value INT, contracts_total_value INT ) 
        RETURNS date DETERMINISTIC
        BEGIN  
           DECLARE total_installment_count INT DEFAULT 0;
           DECLARE mounths_count_from_first_date_until_now INT DEFAULT 0;
           DECLARE calculation_date DATETIME;
           SET total_installment_count = (contracts_total_value/monthly_installment_value);
           SET mounths_count_from_first_date_until_now = PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'),DATE_FORMAT(first_installment_date,'%Y%m'));
           
           IF  mounths_count_from_first_date_until_now < total_installment_count THEN
              SET calculation_date = NOW();
           ELSE
              SET calculation_date = DATE_ADD(first_installment_date, INTERVAL total_installment_count MONTH);
           END IF;
        
           RETURN calculation_date;   		
        END");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute("DROP FUNCTION IF EXISTS get_should_payments_date;");
    }

/*
 // Use up()/down() to run migration code without a transaction.
 public function up()
 {
 }
 public function down()
 {
 echo "m220708_040832_add_financial_function_to_calculate_last_installment_date cannot be reverted.\n";
 return false;
 }
 */
}
