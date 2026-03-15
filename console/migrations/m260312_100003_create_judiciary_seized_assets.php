<?php

use yii\db\Migration;

class m260312_100003_create_judiciary_seized_assets extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%judiciary_seized_assets}}', [
            'id' => $this->primaryKey(),
            'judiciary_id' => $this->integer()->notNull(),
            'customer_id' => $this->integer()->notNull(),
            'asset_type' => $this->string(30)->notNull(),
            'status' => $this->string(30)->notNull()->defaultValue('seizure_requested'),
            'authority_id' => $this->integer()->null(),
            'correspondence_id' => $this->integer()->null(),
            'description' => $this->string(500)->null(),
            'amount' => $this->decimal(12, 2)->null(),
            'notes' => $this->text()->null(),
            'is_deleted' => $this->tinyInteger()->defaultValue(0),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
            'created_by' => $this->integer()->null(),
        ]);

        $this->addForeignKey('fk-seized_assets-judiciary', '{{%judiciary_seized_assets}}', 'judiciary_id', '{{%judiciary}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-seized_assets-authority', '{{%judiciary_seized_assets}}', 'authority_id', '{{%judiciary_authorities}}', 'id', 'SET NULL');
        $this->addForeignKey('fk-seized_assets-correspondence', '{{%judiciary_seized_assets}}', 'correspondence_id', '{{%diwan_correspondence}}', 'id', 'SET NULL');

        $this->createIndex('idx-seized_assets-judiciary', '{{%judiciary_seized_assets}}', 'judiciary_id');
        $this->createIndex('idx-seized_assets-customer', '{{%judiciary_seized_assets}}', 'customer_id');
        $this->createIndex('idx-seized_assets-status', '{{%judiciary_seized_assets}}', 'status');
    }

    public function safeDown()
    {
        $this->dropTable('{{%judiciary_seized_assets}}');
    }
}
