<?php

use yii\db\Migration;

/**
 * Class m210201_095036_create_rname_expenses_to_financial_
 */
class m210201_095036_create_rname_expenses_to_financial_ extends Migration {

    /**
     * {@inheritdoc}
     */
    public function safeUp() {
         $this->renameTable("{{%expenses}}", "{{%financial_transaction}}") ;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown() {
        
    }

    /*
      // Use up()/down() to run migration code without a transaction.
      public function up()
      {

      }

      public function down()
      {
      echo "m210201_095036_create_rname_expenses_to_financial_ cannot be reverted.\n";

      return false;
      }
     */
}
