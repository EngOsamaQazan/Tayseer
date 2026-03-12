<?php

use yii\db\Migration;

/**
 * Class m220915_073424_Update_bancks_table
 */
class m220915_073424_Update_bancks_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
      $this->renameColumn("{{%company_banks}}", "bank_name", "bank_id");
      $this->alterColumn("{{%company_banks}}","bank_id",$this->integer() );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220915_073424_Update_bancks_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220915_073424_Update_bancks_table cannot be reverted.\n";

        return false;
    }
    */
}
