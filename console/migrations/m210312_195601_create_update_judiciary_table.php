<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%update_judiciary}}`.
 */
class m210312_195601_create_update_judiciary_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%judiciary}}','year',$this->string());
        $this->addColumn('{{%judiciary}}','income_date',$this->dateTime());
        $this->addColumn('{{%judiciary}}','judiciary_number',$this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%update_judiciary}}');
    }
}
