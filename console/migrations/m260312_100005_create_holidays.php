<?php

use yii\db\Migration;

class m260312_100005_create_holidays extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%official_holidays}}', true) !== null) {
            return;
        }

        $this->createTable('{{%official_holidays}}', [
            'id' => $this->primaryKey(),
            'holiday_date' => $this->date()->notNull(),
            'name' => $this->string(255)->notNull(),
            'year' => $this->integer()->notNull(),
            'source' => $this->string(20)->notNull()->defaultValue('manual'),
            'created_at' => $this->integer()->null(),
        ]);

        $this->createIndex('idx-official_holidays-date', '{{%official_holidays}}', 'holiday_date', true);
        $this->createIndex('idx-official_holidays-year', '{{%official_holidays}}', 'year');
    }

    public function safeDown()
    {
        $this->dropTable('{{%official_holidays}}');
    }
}
