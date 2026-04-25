<?php

use yii\db\Migration;

/**
 * Tracks each batch creation of judiciary cases.
 *
 * A "batch" is a group of cases created together from the unified wizard
 * (paste / Excel upload / system selection). Each batch records the shared
 * configuration that was applied across all its cases plus a status that
 * lets us audit and reverse the operation within the 72-hour window.
 *
 * Companion tables:
 *   - os_judiciary_batch_items: per-contract row, holds previous_contract_status
 *     for revert + per-row override JSON.
 *   - os_judiciary_batch_templates: shared common-data templates.
 */
class m260425_100000_create_judiciary_batches extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%judiciary_batches}}', [
            'id'             => $this->primaryKey(),
            'batch_uuid'     => $this->string(36)->notNull(),

            'created_by'     => $this->integer()->notNull(),
            'created_at'     => $this->integer()->notNull(),

            // paste | excel | selection
            'entry_method'   => $this->string(16)->notNull(),

            'contract_count' => $this->integer()->notNull()->defaultValue(0),
            'success_count'  => $this->integer()->notNull()->defaultValue(0),
            'failed_count'   => $this->integer()->notNull()->defaultValue(0),

            // Shared common-data snapshot at the moment of batch start:
            // court_id, lawyer_id, type_id, percentage, year, address_mode,
            // address_id, company_id, auto_print, auto_action_name.
            'shared_data'    => 'JSON NULL',

            // running | completed | partial | reverted
            'status'         => $this->string(16)->notNull()->defaultValue('running'),

            'reverted_at'    => $this->integer()->null(),
            'reverted_by'    => $this->integer()->null(),
            'revert_reason'  => $this->string(255)->null(),
        ], $tableOptions);

        $this->createIndex(
            'uq-judiciary_batches-batch_uuid',
            '{{%judiciary_batches}}',
            'batch_uuid',
            true
        );
        $this->createIndex(
            'idx-judiciary_batches-created_by_status',
            '{{%judiciary_batches}}',
            ['created_by', 'status']
        );
        $this->createIndex(
            'idx-judiciary_batches-created_at',
            '{{%judiciary_batches}}',
            'created_at'
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%judiciary_batches}}');
    }
}
