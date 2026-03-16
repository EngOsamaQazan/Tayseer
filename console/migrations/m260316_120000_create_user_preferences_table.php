<?php

use yii\db\Migration;

class m260316_120000_create_user_preferences_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%user_preferences}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'setting_key' => $this->string(50)->notNull(),
            'setting_value' => $this->string(255)->null(),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('uq-user_pref-user_key', '{{%user_preferences}}', ['user_id', 'setting_key'], true);
        $this->addForeignKey('fk-user_pref-user', '{{%user_preferences}}', 'user_id', '{{%user}}', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%user_preferences}}');
    }
}
