<?php

use yii\db\Migration;

/**
 * Class m220319_205550_add_columns
 */
class m220319_205550_add_columns extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%income}}', 'bank_number', $this->integer());
        $this->addColumn('{{%financial_transaction}}', 'bank_number', $this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%income}}', 'bank_number');
        $this->dropColumn('{{%financial_transaction}}', 'bank_number');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220319_205550_add_columns cannot be reverted.\n";

        return false;
    }
    */
}
