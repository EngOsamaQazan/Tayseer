<?php

use yii\db\Migration;

/**
 * Class m220220_134219_add_is_primary_company_to_company_table
 */
class m220220_134219_add_is_primary_company_to_company_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%companies}}', 'is_primary_company', $this->boolean()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220220_134219_add_is_primary_company_to_company_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220220_134219_add_is_primary_company_to_company_table cannot be reverted.\n";

        return false;
    }
    */
}
