<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%update_name_bank_in_custamer}}`.
 */
class m210218_104652_create_update_name_bank_in_custamer_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('{{%customers}}','bank_name');
        $this->addColumn('{{%customers}}','bank_name',$this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }
}
