<?php

use yii\db\Migration;

/**
 * Phase 1 / M1.5 — Append-only audit log for every MediaService write.
 *
 * One row per (store / adopt / replace / delete / restore) call, with
 * enough context that the human looking at it six months later can
 * answer:
 *   • Who uploaded this?
 *   • From which surface (wizard, smart_media, REST, …)?
 *   • Was it adopted later? When? By whom?
 *   • What was the original, and what replaced it?
 *
 * Indexed for the three lookup patterns we have today:
 *   1. "show me the history for media #123"               → media_id
 *   2. "show me everything user #42 did this week"        → actor_user_id, created_at
 *   3. "show me orphan-adoption events for customer #99"  → entity_type+entity_id+action
 *
 * NOT indexed for free-text payload search on purpose — that path is
 * handled by an OpenSearch sink in Phase 7 if we ever need it.
 */
class m260419_100002_create_media_audit_log extends Migration
{
    private const TABLE = 'media_audit_log';

    public function safeUp()
    {
        if ($this->db->getTableSchema(self::TABLE, true) !== null) {
            echo "    > " . self::TABLE . " already exists, skipped.\n";
            return;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable(self::TABLE, [
            'id'             => $this->bigPrimaryKey()->unsigned(),
            'media_id'       => $this->integer()->unsigned()->null()
                                    ->comment('NULL for events that pre-create the row (rare, dedup-hit).'),
            'action'         => $this->string(32)->notNull()
                                    ->comment('store|adopt|replace|delete|restore|dedup_hit'),
            'actor_user_id'  => $this->integer()->null()
                                    ->comment('NULL when triggered by console / cron / job.'),
            'entity_type'    => $this->string(32)->null(),
            'entity_id'      => $this->integer()->unsigned()->null(),
            'group_name'     => $this->string(50)->null(),
            'uploaded_via'   => $this->string(32)->null(),
            'payload_json'   => $this->json()->null()
                                    ->comment('Free-form context: previous values for replace, dedup target, error message, etc.'),
            'ip'             => $this->string(45)->null()
                                    ->comment('IPv4/IPv6.'),
            'user_agent'     => $this->string(255)->null(),
            'created_at'     => $this->dateTime()->notNull()
                                    ->defaultExpression('CURRENT_TIMESTAMP'),
        ], $tableOptions);

        $this->createIndex('idx_media_audit_media_id',   self::TABLE, ['media_id', 'created_at']);
        $this->createIndex('idx_media_audit_actor_time', self::TABLE, ['actor_user_id', 'created_at']);
        $this->createIndex('idx_media_audit_entity',     self::TABLE, ['entity_type', 'entity_id', 'action']);
    }

    public function safeDown()
    {
        if ($this->db->getTableSchema(self::TABLE, true) !== null) {
            $this->dropTable(self::TABLE);
        }
    }
}
