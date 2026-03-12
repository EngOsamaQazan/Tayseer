<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%add_created_by_in_custamer}}`.
 */
class m210321_094301_create_add_created_by_in_custamer_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%customers}}','created_by',$this->integer()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%add_created_by_in_custamer}}');
    }
}
