<?php

use yii\db\Migration;

class m260327_100000_fix_contracts_type_column extends Migration
{
    public function safeUp()
    {
        $this->db->createCommand("SET SESSION sql_mode=''")->execute();
        $this->db->createCommand("ALTER TABLE {{%contracts}} MODIFY `type` VARCHAR(30) NOT NULL DEFAULT 'normal'")->execute();
    }

    public function safeDown()
    {
        $this->db->createCommand("SET SESSION sql_mode=''")->execute();
        $this->db->createCommand("ALTER TABLE {{%contracts}} MODIFY `type` VARCHAR(30) NOT NULL")->execute();
    }
}
