<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%add_new_column_to_expenses}}`.
 */
class m210118_075051_create_add_new_column_to_expenses_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
   $this->addColumn('os_expenses','document_number',$this->integer()->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

    }
}
