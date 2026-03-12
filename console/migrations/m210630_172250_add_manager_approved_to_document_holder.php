<?php

use yii\db\Migration;

/**
 * Class m210630_172250_add_manager_approved_to_document_holder
 */
class m210630_172250_add_manager_approved_to_document_holder extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
$this->addColumn('{{%document_holder}}','manager_approved',$this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210630_172250_add_manager_approved_to_document_holder cannot be reverted.\n";

        return false;
    }
    */
}
