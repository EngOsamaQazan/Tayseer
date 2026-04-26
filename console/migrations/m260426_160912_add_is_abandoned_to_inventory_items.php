<?php

use yii\db\Migration;

/**
 * Adds an `is_abandoned` flag (and timestamp) to os_inventory_items.
 *
 * Marks items that are no longer offered/produced. Distinct from `status`
 * (draft/pending/approved/rejected) so an approved item can be flagged
 * abandoned without losing its approval history.
 */
class m260426_160912_add_is_abandoned_to_inventory_items extends Migration
{
    public function safeUp()
    {
        $table = '{{%inventory_items}}';

        if ($this->db->getTableSchema($table)->getColumn('is_abandoned') === null) {
            $this->addColumn($table, 'is_abandoned', $this->boolean()->notNull()->defaultValue(false));
            $this->createIndex('idx_inv_items_is_abandoned', $table, 'is_abandoned');
        }

        if ($this->db->getTableSchema($table)->getColumn('abandoned_at') === null) {
            $this->addColumn($table, 'abandoned_at', $this->integer()->null());
        }

        if ($this->db->getTableSchema($table)->getColumn('abandoned_by') === null) {
            $this->addColumn($table, 'abandoned_by', $this->integer()->null());
        }
    }

    public function safeDown()
    {
        $table = '{{%inventory_items}}';

        if ($this->db->getTableSchema($table)->getColumn('abandoned_by') !== null) {
            $this->dropColumn($table, 'abandoned_by');
        }
        if ($this->db->getTableSchema($table)->getColumn('abandoned_at') !== null) {
            $this->dropColumn($table, 'abandoned_at');
        }
        if ($this->db->getTableSchema($table)->getColumn('is_abandoned') !== null) {
            try { $this->dropIndex('idx_inv_items_is_abandoned', $table); } catch (\Throwable $e) {}
            $this->dropColumn($table, 'is_abandoned');
        }
    }
}
