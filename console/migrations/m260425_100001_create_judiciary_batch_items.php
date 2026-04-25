<?php

use yii\db\Migration;

/**
 * One row per contract that was queued in a batch.
 *
 * Holds the per-contract outcome (success/failed/skipped/reverted), the
 * judiciary_id created for it, the contract_status that was overwritten by
 * the batch (so revert can restore it), and any per-row override values
 * the operator set on the wizard (lawyer_id / type_id / company_id /
 * judiciary_inform_address_id).
 */
class m260425_100001_create_judiciary_batch_items extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%judiciary_batch_items}}', [
            'id'                       => $this->primaryKey(),
            'batch_id'                 => $this->integer()->notNull(),
            'contract_id'              => $this->integer()->notNull(),
            'judiciary_id'             => $this->integer()->null(),

            // Snapshot of os_contracts.status BEFORE batch overwrote it.
            // Used to restore the contract on revert.
            'previous_contract_status' => $this->string(50)->null(),

            // pending | success | failed | skipped | reverted
            'status'                   => $this->string(16)->notNull()->defaultValue('pending'),

            'error_message'            => $this->text()->null(),

            // Per-row override JSON (only diff from shared_data).
            'overrides'                => 'JSON NULL',

            'created_at'               => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex(
            'idx-judiciary_batch_items-batch_id',
            '{{%judiciary_batch_items}}',
            'batch_id'
        );
        $this->createIndex(
            'idx-judiciary_batch_items-contract_id',
            '{{%judiciary_batch_items}}',
            'contract_id'
        );
        $this->createIndex(
            'idx-judiciary_batch_items-judiciary_id',
            '{{%judiciary_batch_items}}',
            'judiciary_id'
        );
        $this->createIndex(
            'idx-judiciary_batch_items-batch_status',
            '{{%judiciary_batch_items}}',
            ['batch_id', 'status']
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%judiciary_batch_items}}');
    }
}
