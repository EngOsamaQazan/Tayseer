<?php

use yii\db\Migration;

class m260312_100006_create_judiciary_request_templates extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%judiciary_request_templates}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'template_type' => $this->string(50)->notNull(),
            'template_content' => 'LONGTEXT NULL',
            'is_combinable' => $this->tinyInteger()->defaultValue(1),
            'sort_order' => $this->integer()->defaultValue(0),
            'is_deleted' => $this->tinyInteger()->defaultValue(0),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
            'created_by' => $this->integer()->null(),
        ]);

        $this->createIndex('idx-request_templates-type', '{{%judiciary_request_templates}}', 'template_type');
    }

    public function safeDown()
    {
        $this->dropTable('{{%judiciary_request_templates}}');
    }
}
