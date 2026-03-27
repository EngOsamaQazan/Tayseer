<?php

use yii\db\Migration;

class m260326_300001_create_wizard_drafts_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%wizard_drafts}}', [
            'id'         => $this->primaryKey(),
            'user_id'    => $this->integer()->notNull(),
            'draft_key'  => $this->string(100)->notNull(),
            'draft_data' => $this->text()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-wizard_drafts-user_key', '{{%wizard_drafts}}', ['user_id', 'draft_key'], true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%wizard_drafts}}');
    }
}
