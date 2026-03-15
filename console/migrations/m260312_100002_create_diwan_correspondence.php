<?php

use yii\db\Migration;

class m260312_100002_create_diwan_correspondence extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%diwan_correspondence}}', [
            'id' => $this->primaryKey(),
            'communication_type' => $this->string(20)->notNull(),
            'related_module' => $this->string(50)->notNull()->defaultValue('judiciary'),
            'related_record_id' => $this->integer()->null(),
            'customer_id' => $this->integer()->null(),
            'direction' => $this->string(10)->notNull(),

            'recipient_type' => $this->string(20)->null(),
            'authority_id' => $this->integer()->null(),
            'bank_id' => $this->integer()->null(),
            'job_id' => $this->integer()->null(),

            'notification_method' => $this->string(30)->null(),
            'delivery_date' => $this->date()->null(),
            'notification_result' => $this->string(20)->null(),

            'reference_number' => $this->string(100)->null(),
            'purpose' => $this->string(100)->null(),

            'parent_id' => $this->integer()->null(),
            'response_result' => $this->string(50)->null(),
            'response_amount' => $this->decimal(12, 2)->null(),

            'correspondence_date' => $this->date()->notNull(),
            'content_summary' => $this->text()->null(),
            'image' => $this->string(500)->null(),
            'follow_up_date' => $this->date()->null(),
            'status' => $this->string(20)->notNull()->defaultValue('draft'),
            'notes' => $this->text()->null(),
            'company_id' => $this->integer()->null(),
            'is_deleted' => $this->tinyInteger()->defaultValue(0),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
            'created_by' => $this->integer()->null(),
            'updated_by' => $this->integer()->null(),
        ]);

        $this->addForeignKey('fk-diwan_corr-authority', '{{%diwan_correspondence}}', 'authority_id', '{{%judiciary_authorities}}', 'id', 'SET NULL');
        $this->addForeignKey('fk-diwan_corr-bank', '{{%diwan_correspondence}}', 'bank_id', '{{%bancks}}', 'id', 'SET NULL');
        $this->addForeignKey('fk-diwan_corr-parent', '{{%diwan_correspondence}}', 'parent_id', '{{%diwan_correspondence}}', 'id', 'SET NULL');

        $this->createIndex('idx-diwan_corr-module_record', '{{%diwan_correspondence}}', ['related_module', 'related_record_id']);
        $this->createIndex('idx-diwan_corr-type_status', '{{%diwan_correspondence}}', ['communication_type', 'status']);
        $this->createIndex('idx-diwan_corr-customer', '{{%diwan_correspondence}}', 'customer_id');
        $this->createIndex('idx-diwan_corr-follow_up', '{{%diwan_correspondence}}', ['follow_up_date', 'status']);
        $this->createIndex('idx-diwan_corr-parent', '{{%diwan_correspondence}}', 'parent_id');
        $this->createIndex('idx-diwan_corr-company_deleted', '{{%diwan_correspondence}}', ['company_id', 'is_deleted']);
    }

    public function safeDown()
    {
        $this->dropTable('{{%diwan_correspondence}}');
    }
}
