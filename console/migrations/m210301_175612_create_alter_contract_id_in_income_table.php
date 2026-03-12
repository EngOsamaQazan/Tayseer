<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%alter_contract_id_in_income}}`.
 */
class m210301_175612_create_alter_contract_id_in_income_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%income}}','contract_id',$this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }
}
