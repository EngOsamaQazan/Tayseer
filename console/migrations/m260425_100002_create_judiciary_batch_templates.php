<?php

use yii\db\Migration;

/**
 * Reusable common-data templates for the batch case-creation wizard.
 *
 * Templates are shared across the company (any user can load any template),
 * but only the creator or a manager can delete one. Each template stores a
 * snapshot of the wizard's "shared data" panel as JSON so loading restores
 * every field at once.
 */
class m260425_100002_create_judiciary_batch_templates extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%judiciary_batch_templates}}', [
            'id'          => $this->primaryKey(),
            'name'        => $this->string(100)->notNull(),

            'created_by'  => $this->integer()->notNull(),
            'created_at'  => $this->integer()->notNull(),
            'updated_at'  => $this->integer()->null(),

            // Snapshot: court_id, lawyer_id, type_id, percentage, year,
            // address_mode, address_id, company_id, auto_print, auto_action_name.
            'data'        => 'JSON NOT NULL',

            'usage_count' => $this->integer()->notNull()->defaultValue(0),
            'is_deleted'  => $this->tinyInteger(1)->notNull()->defaultValue(0),
        ], $tableOptions);

        $this->createIndex(
            'idx-judiciary_batch_templates-active',
            '{{%judiciary_batch_templates}}',
            ['is_deleted', 'name']
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%judiciary_batch_templates}}');
    }
}
