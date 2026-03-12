<?php

use yii\db\Migration;

/**
 * Class m220322_150525_add_coulmn_to_sms_table
 */
class m220322_150525_add_coulmn_to_sms_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%sms}}', 'customers_id', $this->text());
        $this->addColumn('{{%sms}}', 'contract_id', $this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%sms}}', 'customers_id');
        $this->dropColumn('{{%sms}}', 'contract_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
    }

    public function down()
    {
        echo "m220322_150525_add_coulmn_to_sms_table cannot be reverted.\n";

        return false;
    }
    */
}
