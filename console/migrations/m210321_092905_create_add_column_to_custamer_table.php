<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%add_column_to_custamer}}`.
 */
class m210321_092905_create_add_column_to_custamer_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%customers}}','updated_at',$this->integer());
        $this->addColumn('{{%customers}}','is_deleted',$this->integer()->defaultValue(0));
        $this->addColumn('{{%customers}}','created_at',$this->integer()->defaultValue(0));
        $this->addColumn('{{%customers}}','last_updated_by',$this->integer()->defaultValue(0));

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

    }
}
