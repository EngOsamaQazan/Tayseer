<?php

use yii\db\Migration;

/**
 * Class m220708_042746_add_financial_function_to_calculate_late_installment_count
 */
class m220708_042746_add_financial_function_to_calculate_late_installment_count extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->execute("
        CREATE OR REPLACE FUNCTION `get_late_installment_count`(total_paid_installments INT, should_payments INT, monthly_installments INT ) 
        RETURNS INT DETERMINISTIC
        BEGIN  
           DECLARE total INT DEFAULT 0;
           DECLARE total_installment_count INT DEFAULT 0;
           
           SET total = (should_payments - total_paid_installments);
           IF  total > 0 THEN
              SET total_installment_count = (total/monthly_installments);
           ELSE
              SET total_installment_count = 0;
           END IF;
        
           RETURN total_installment_count;   		
        END");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute("DROP FUNCTION IF EXISTS get_late_installment_count;");
    }

/*
 // Use up()/down() to run migration code without a transaction.
 public function up()
 {
 }
 public function down()
 {
 echo "m220708_042746_add_financial_function_to_calculate_late_installment_count cannot be reverted.\n";
 return false;
 }
 */
}
