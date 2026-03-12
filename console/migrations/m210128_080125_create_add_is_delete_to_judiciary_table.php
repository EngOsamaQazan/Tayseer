<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%add_is_delete_to_judiciary}}`.
 */
class m210128_080125_create_add_is_delete_to_judiciary_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%judiciary}}','is_deleted',$this->integer()->notNull());

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        
   }
}
