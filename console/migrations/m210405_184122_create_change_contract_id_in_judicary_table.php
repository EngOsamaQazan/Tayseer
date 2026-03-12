<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%change_contract_id_in_judicary}}`.
 */
class m210405_184122_create_change_contract_id_in_judicary_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->alterColumn('{{%judiciary}}','contract_id',$this->integer()->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }
}
