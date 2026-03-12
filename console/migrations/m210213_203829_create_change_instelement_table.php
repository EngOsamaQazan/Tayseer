<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%change_instelement}}`.
 */
class m210213_203829_create_change_instelement_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->renameTable('{{%installment}}','{{%income}}');
        $this->addColumn('{{%income}}','type',$this->integer()->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }
}
