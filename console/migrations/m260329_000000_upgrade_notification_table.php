<?php

use yii\db\Migration;

/**
 * Upgrades os_notification table:
 * - Adds read_at, entity_type, entity_id, priority, group_key, channel columns
 * - Fixes is_unread/is_hidden column types to TINYINT
 * - Adds performance indexes
 */
class m260329_000000_upgrade_notification_table extends Migration
{
    public function safeUp()
    {
        $table = '{{%notification}}';
        $schema = $this->db->getTableSchema($table, true);
        if (!$schema) {
            echo "Table {$table} does not exist, skipping.\n";
            return true;
        }

        if (!$schema->getColumn('read_at')) {
            $this->addColumn($table, 'read_at', $this->integer()->null()->comment('Unix timestamp when marked read'));
        }
        if (!$schema->getColumn('entity_type')) {
            $this->addColumn($table, 'entity_type', $this->string(50)->null()->comment('Source entity class: contract, customer, invoice...'));
        }
        if (!$schema->getColumn('entity_id')) {
            $this->addColumn($table, 'entity_id', $this->integer()->null()->comment('Source entity PK'));
        }
        if (!$schema->getColumn('priority')) {
            $this->addColumn($table, 'priority', $this->tinyInteger()->defaultValue(0)->comment('0=normal, 1=high, 2=urgent'));
        }
        if (!$schema->getColumn('group_key')) {
            $this->addColumn($table, 'group_key', $this->string(100)->null()->comment('Grouping key for similar notifications'));
        }
        if (!$schema->getColumn('channel')) {
            $this->addColumn($table, 'channel', $this->string(20)->defaultValue('in_app')->comment('in_app, email, push'));
        }

        $this->alterColumn($table, 'is_unread', $this->tinyInteger(1)->defaultValue(1));
        $this->alterColumn($table, 'is_hidden', $this->tinyInteger(1)->defaultValue(0));

        try { $this->createIndex('idx_notif_recipient_unread', $table, ['recipient_id', 'is_unread']); } catch (\Exception $e) {}
        try { $this->createIndex('idx_notif_recipient_created', $table, ['recipient_id', 'created_time']); } catch (\Exception $e) {}
        try { $this->createIndex('idx_notif_entity', $table, ['entity_type', 'entity_id']); } catch (\Exception $e) {}
        try { $this->createIndex('idx_notif_group', $table, ['group_key']); } catch (\Exception $e) {}
    }

    public function safeDown()
    {
        $table = '{{%notification}}';

        try { $this->dropIndex('idx_notif_group', $table); } catch (\Exception $e) {}
        try { $this->dropIndex('idx_notif_entity', $table); } catch (\Exception $e) {}
        try { $this->dropIndex('idx_notif_recipient_created', $table); } catch (\Exception $e) {}
        try { $this->dropIndex('idx_notif_recipient_unread', $table); } catch (\Exception $e) {}

        $this->dropColumn($table, 'channel');
        $this->dropColumn($table, 'group_key');
        $this->dropColumn($table, 'priority');
        $this->dropColumn($table, 'entity_id');
        $this->dropColumn($table, 'entity_type');
        $this->dropColumn($table, 'read_at');
    }
}
