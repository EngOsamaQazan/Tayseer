<?php

use yii\db\Migration;

/**
 * Class m230308_082320_add_company_id
 */
class m230308_082320_add_company_id extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
$this->addColumn("{{%judiciary}}","company_id",$this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
       $this->dropColumn("{{%judiciary}}","company_id");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230308_082320_add_company_id cannot be reverted.\n";

        return false;
    }
    */
}
