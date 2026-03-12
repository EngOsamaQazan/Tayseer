<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%edit_value_in_laywer}}`.
 */
class m210312_145237_create_edit_value_in_laywer_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%lawyers}}','address',$this->string(255));
        $this->alterColumn('{{%lawyers}}','phone_number',$this->string(255));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }
}
