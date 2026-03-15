<?php

use yii\db\Migration;

class m260312_100008_create_defendant_stage extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%judiciary_defendant_stage}}', [
            'id' => $this->primaryKey(),
            'judiciary_id' => $this->integer()->notNull(),
            'customer_id' => $this->integer()->notNull(),
            'current_stage' => $this->string(30)->notNull()->defaultValue('case_preparation'),
            'stage_updated_at' => $this->dateTime()->null(),
            'notes' => $this->text()->null(),
        ]);

        $this->addForeignKey('fk-defendant_stage-judiciary', '{{%judiciary_defendant_stage}}', 'judiciary_id', '{{%judiciary}}', 'id', 'CASCADE');
        $this->createIndex('idx-defendant_stage-unique', '{{%judiciary_defendant_stage}}', ['judiciary_id', 'customer_id'], true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%judiciary_defendant_stage}}');
    }
}
