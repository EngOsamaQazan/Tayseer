<?php

use yii\db\Migration;
use yii\db\Schema;

/**
 * Phase 0 / M0.1 — Extends `os_ImageManager` to become the unified store
 * for every kind of media in Tayseer (customers, contracts, lawyers,
 * employees, companies, judiciary actions, movement receipts, …).
 *
 * Backwards-compatible by design:
 *   • All new columns are NULLABLE so legacy INSERTs (which only know
 *     about fileName/fileHash/customer_id/contractId/groupName/created)
 *     keep working unchanged for the entire 3-month deprecation window.
 *   • Existing columns (`customer_id`, `contractId`) are NOT touched —
 *     they remain readable and writable. The eventual drop happens in
 *     Phase 8 once every caller has switched to entity_type/entity_id.
 *
 * Idempotent: every column/index addition is guarded so the migration
 * can be re-run on partially-migrated environments without crashing.
 */
class m260419_100001_extend_os_imagemanager_unified extends Migration
{
    private const TABLE = 'os_ImageManager';

    public function safeUp()
    {
        $schema = $this->db->getTableSchema(self::TABLE, true);
        if ($schema === null) {
            throw new \RuntimeException(
                self::TABLE . ' does not exist; run the original create migration first.'
            );
        }

        // ── New columns ─────────────────────────────────────────────
        // entity_type drives polymorphism. Length 32 covers every
        // current and foreseeable owner ('customer', 'contract',
        // 'lawyer', 'employee', 'company', 'judiciary_action',
        // 'movement', 'contract_doc', …).
        $this->addColumnIfMissing($schema, 'entity_type', $this->string(32)->null());
        $this->addColumnIfMissing($schema, 'entity_id',   $this->integer()->unsigned()->null());

        // File-level metadata captured at upload time. Cheap to fill,
        // critical for de-dup and for serving correct headers without
        // re-stat'ing the disk.
        $this->addColumnIfMissing($schema, 'file_size',  $this->integer()->unsigned()->null());
        $this->addColumnIfMissing($schema, 'mime_type',  $this->string(100)->null());
        $this->addColumnIfMissing($schema, 'width',      $this->integer()->unsigned()->null());
        $this->addColumnIfMissing($schema, 'height',     $this->integer()->unsigned()->null());

        // SHA-256 in hex = 64 chars. Authoritative duplicate detector.
        // (`fileHash` predates this column and is only an MD-style 32-char
        // hash of the original name; not collision-safe enough for
        // de-dup decisions.)
        $this->addColumnIfMissing($schema, 'checksum_sha256', $this->char(64)->null());

        // Lifecycle of the async pipeline:
        //   pending    — row inserted, file on disk, jobs queued
        //   processing — at least one job has picked it up
        //   ready      — all jobs finished, thumbnails exist, safe to serve
        //   failed     — terminal, see media_audit_log for details
        // Stored as VARCHAR (not ENUM) so adding a new state later
        // doesn't require an ALTER TABLE.
        $this->addColumnIfMissing(
            $schema,
            'processing_status',
            $this->string(16)->notNull()->defaultValue('ready')
        );

        // Which surface produced the upload — 'wizard', 'smart_media',
        // 'lawyer_form', 'employee_form', 'company_form',
        // 'judiciary_form', 'movement_form', 'rest_api', 'backfill', …
        // Drives analytics + lets us roll back per-surface if a release
        // breaks one upload path.
        $this->addColumnIfMissing($schema, 'uploaded_via', $this->string(32)->null());

        // Soft-delete timestamp. Hard-delete is reserved for the
        // periodic GC job that runs N days after deleted_at is set.
        $this->addColumnIfMissing($schema, 'deleted_at', $this->dateTime()->null());

        // ── Indexes ─────────────────────────────────────────────────
        // The composite index is the workhorse for "all media that
        // belong to entity X" queries (Fahras, customer view, lawyer
        // signature lookup, …) and naturally excludes soft-deleted rows
        // at the leaf level.
        $this->createIndexIfMissing(
            'idx_imagemanager_entity_status',
            self::TABLE,
            ['entity_type', 'entity_id', 'deleted_at']
        );

        // Checksum lookup for de-dup. Not unique — same user can
        // legitimately re-upload the same file under a different
        // groupName (e.g. front side of ID also serving as a profile pic).
        $this->createIndexIfMissing(
            'idx_imagemanager_checksum',
            self::TABLE,
            ['checksum_sha256']
        );

        // Used by the async pipeline + the health endpoint to find
        // stuck rows fast without scanning the whole table.
        $this->createIndexIfMissing(
            'idx_imagemanager_processing_status',
            self::TABLE,
            ['processing_status']
        );
    }

    public function safeDown()
    {
        $this->dropIndexIfExists('idx_imagemanager_processing_status', self::TABLE);
        $this->dropIndexIfExists('idx_imagemanager_checksum',          self::TABLE);
        $this->dropIndexIfExists('idx_imagemanager_entity_status',     self::TABLE);

        foreach ([
            'deleted_at', 'uploaded_via', 'processing_status',
            'checksum_sha256', 'height', 'width', 'mime_type',
            'file_size', 'entity_id', 'entity_type',
        ] as $col) {
            $this->dropColumnIfExists(self::TABLE, $col);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // Idempotency helpers — Yii's Migration API is not idempotent by
    // default; these wrappers let the migration tolerate partial state
    // (e.g. a column added by a hot-fix outside the migration system).
    // ─────────────────────────────────────────────────────────────────

    private function addColumnIfMissing($schema, string $column, $type): void
    {
        if (isset($schema->columns[$column])) {
            echo "    > column $column already exists, skipped.\n";
            return;
        }
        $this->addColumn(self::TABLE, $column, $type);
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        $schema = $this->db->getTableSchema($table, true);
        if ($schema !== null && isset($schema->columns[$column])) {
            $this->dropColumn($table, $column);
        }
    }

    private function createIndexIfMissing(string $name, string $table, array $cols): void
    {
        $existing = $this->db->getSchema()->getTableIndexes($table, true);
        foreach ($existing as $idx) {
            if ($idx->name === $name) {
                echo "    > index $name already exists, skipped.\n";
                return;
            }
        }
        $this->createIndex($name, $table, $cols);
    }

    private function dropIndexIfExists(string $name, string $table): void
    {
        $existing = $this->db->getSchema()->getTableIndexes($table, true);
        foreach ($existing as $idx) {
            if ($idx->name === $name) {
                $this->dropIndex($name, $table);
                return;
            }
        }
    }
}
