<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%update_type_in_income}}`.
 */
class m210216_085244_create_update_type_in_income_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('{{%income}}','type');
        $this->addColumn('{{%income}}','type',$this->integer()->defaultValue(1));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%update_type_in_income}}');
    }
}
