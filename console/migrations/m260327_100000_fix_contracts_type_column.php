<?php

use yii\db\Migration;

class m260327_100000_fix_contracts_type_column extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('{{%contracts}}', 'type', $this->string(30)->notNull()->defaultValue('normal'));
    }

    public function safeDown()
    {
        $this->alterColumn('{{%contracts}}', 'type', $this->string(30)->notNull());
    }
}
