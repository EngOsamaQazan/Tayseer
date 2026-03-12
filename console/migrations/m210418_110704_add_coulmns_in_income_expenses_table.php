<?php

use yii\db\Migration;

/**
 * Class m210418_110704_add_coulmns_in_income_expenses_table
 */
class m210418_110704_add_coulmns_in_income_expenses_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%income}}','notes',$this->text());
        $this->addColumn('{{%expenses}}','notes',$this->text());
        $this->addColumn('{{%expenses}}','document_number',$this->integer());
        $this->addColumn('{{%income}}','document_number',$this->integer());

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210418_110704_add_coulmns_in_income_expenses_table cannot be reverted.\n";

        return false;
    }
    */
}
