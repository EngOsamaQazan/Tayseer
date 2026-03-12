<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%add_date_to_expenses}}`.
 */
class m210404_111010_create_add_date_to_expenses_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%expenses}}','expenses_date',$this->date());

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%expenses}}','expenses_date');
    }
}
