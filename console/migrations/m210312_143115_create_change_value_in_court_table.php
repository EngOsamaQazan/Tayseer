<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%change_value_in_court}}`.
 */
class m210312_143115_create_change_value_in_court_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%court}}','adress',$this->string(255));
        $this->alterColumn('{{%court}}','phone_number',$this->string(255));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }
}
