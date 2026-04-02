<?php

use yii\db\Migration;

class m260402_100000_create_sms_drafts_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%sms_drafts}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull(),
            'text' => $this->text()->notNull(),
            'created_by' => $this->integer()->null(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx-sms_drafts-created_at', '{{%sms_drafts}}', 'created_at');
    }

    public function safeDown()
    {
        $this->dropTable('{{%sms_drafts}}');
    }
}
