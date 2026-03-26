<?php

use yii\db\Migration;

class m260312_100004_create_judiciary_deadlines extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%judiciary_deadlines}}', true) !== null) {
            return;
        }

        $this->createTable('{{%judiciary_deadlines}}', [
            'id' => $this->primaryKey(),
            'judiciary_id' => $this->integer()->notNull(),
            'customer_id' => $this->integer()->null(),
            'deadline_type' => $this->string(30)->notNull(),
            'day_type' => $this->string(10)->notNull(),
            'label' => $this->string(255)->notNull(),
            'start_date' => $this->date()->notNull(),
            'deadline_date' => $this->date()->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('pending'),
            'related_communication_id' => $this->integer()->null(),
            'related_customer_action_id' => $this->integer()->null(),
            'notes' => $this->text()->null(),
            'is_deleted' => $this->tinyInteger()->defaultValue(0),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
            'created_by' => $this->integer()->null(),
        ]);

        $this->addForeignKey('fk-deadlines-judiciary', '{{%judiciary_deadlines}}', 'judiciary_id', '{{%judiciary}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-deadlines-communication', '{{%judiciary_deadlines}}', 'related_communication_id', '{{%diwan_correspondence}}', 'id', 'SET NULL');
        $this->addForeignKey('fk-deadlines-action', '{{%judiciary_deadlines}}', 'related_customer_action_id', '{{%judiciary_customers_actions}}', 'id', 'SET NULL');

        $this->createIndex('idx-deadlines-judiciary', '{{%judiciary_deadlines}}', 'judiciary_id');
        $this->createIndex('idx-deadlines-customer', '{{%judiciary_deadlines}}', 'customer_id');
        $this->createIndex('idx-deadlines-status', '{{%judiciary_deadlines}}', 'status');
        $this->createIndex('idx-deadlines-date', '{{%judiciary_deadlines}}', 'deadline_date');
        $this->createIndex('idx-deadlines-type_status', '{{%judiciary_deadlines}}', ['deadline_type', 'status']);
    }

    public function safeDown()
    {
        $this->dropTable('{{%judiciary_deadlines}}');
    }
}
