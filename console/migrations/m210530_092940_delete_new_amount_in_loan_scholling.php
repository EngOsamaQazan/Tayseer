<?php

use yii\db\Migration;

/**
 * Class m210530_092940_delete_new_amount_in_loan_scholling
 */
class m210530_092940_delete_new_amount_in_loan_scholling extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('{{%loan_scheduling}}', 'new_amount');
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
        echo "m210530_092940_delete_new_amount_in_loan_scholling cannot be reverted.\n";

        return false;
    }
    */
}
