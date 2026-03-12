<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%modify_type_not_in_layower}}`.
 */
class m210130_065600_create_modify_type_not_in_layower_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
   $this->dropColumn('{{%lawyers}}','notes');
        $this->addColumn('{{%lawyers}}','notes',$this->text());
      
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
       
    }
}
