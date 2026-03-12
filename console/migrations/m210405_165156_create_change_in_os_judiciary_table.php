<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%change_in_os_judiciary}}`.
 */
class m210405_165156_create_change_in_os_judiciary_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
  $this->alterColumn('{{%judiciary}}','income_date',$this->date());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }
}
