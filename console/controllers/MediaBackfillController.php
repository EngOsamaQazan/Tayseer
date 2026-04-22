<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;
use yii\helpers\Console;
use backend\helpers\MediaHelper;
use common\services\media\MediaContext;

/**
 * Phase 0 / M0.2 — Initial back-fill for the unified media schema.
 *
 * Walks every existing row in `os_ImageManager` and:
 *   1. Sets `entity_type` from the legacy *_id columns:
 *        customer_id IS NOT NULL  → 'customer'   + entity_id = customer_id
 *        contractId  IS NOT NULL  → 'contract'   + entity_id = contractId  (unless customer wins)
 *        both        IS NULL      → leaves entity_type NULL — picked up by recover-orphan-media
 *   2. Computes `checksum_sha256`, `file_size`, `mime_type` (and width/height
 *      for images) from the on-disk file using the path that
 *      MediaHelper::filePath() yields. Rows whose file is missing are
 *      logged + flagged with processing_status='failed' so they appear
 *      in the health endpoint instead of silently rotting.
 *   3. Sets `uploaded_via='legacy'` and `processing_status='ready'` for
 *      successfully back-filled rows so the new code paths treat them as
 *      first-class citizens.
 *
 * Idempotent + resumable: only touches rows whose `checksum_sha256` is
 * currently NULL, so re-running picks up where the last run stopped.
 *
 * Usage:
 *   php yii media-backfill/initial               (DRY-RUN, no DB writes)
 *   php yii media-backfill/initial --apply
 *   php yii media-backfill/initial --apply --batch=200 --limit=10000
 *   php yii media-backfill/initial --apply --resume      (skip already-checksummed rows; default)
 *   php yii media-backfill/initial --apply --rehash      (force re-checksum even if non-NULL)
 */
class MediaBackfillController extends Controller
{
    /** @var bool Without it, the command runs as a dry-run. */
    public $apply = false;

    /** @var int Rows per batch. Larger = fewer round-trips, more memory. */
    public $batch = 500;

    /** @var int|null Stop after processing this many rows total (debug). */
    public $limit = null;

    /** @var bool Re-compute checksum for rows that already have one. */
    public $rehash = false;

    /**
     * @var string|null  Restrict `actionAll` to a single source. One of:
     *                   lawyers | employees | companies | judiciary
     *                   | movement | smart_media. Null = all.
     */
    public $source = null;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'apply', 'batch', 'limit', 'rehash', 'source',
        ]);
    }

    public function optionAliases()
    {
        return ['a' => 'apply', 'b' => 'batch', 'l' => 'limit', 's' => 'source'];
    }

    public function actionInitial(): int
    {
        $db = Yii::$app->db;

        $this->stdout("\n=== Media Initial Back-fill ===\n", Console::FG_CYAN, Console::BOLD);
        $this->stdout(sprintf(
            "Mode: %s | Batch: %d | Limit: %s | Mode: %s\n\n",
            $this->apply ? 'APPLY' : 'DRY-RUN',
            (int)$this->batch,
            $this->limit !== null ? (int)$this->limit : 'all',
            $this->rehash ? 'rehash' : 'resume'
        ));

        // Make sure the new columns exist before we touch anything.
        $schema = $db->getTableSchema('os_ImageManager', true);
        if ($schema === null || !isset($schema->columns['checksum_sha256'])) {
            $this->stdout(
                "os_ImageManager is missing the new columns. Run the\n"
                . "  m260419_100001_extend_os_imagemanager_unified migration first.\n",
                Console::FG_RED
            );
            return ExitCode::CONFIG;
        }

        $stats = [
            'scanned'     => 0,
            'updated'     => 0,
            'missing_file'=> 0,
            'orphan'      => 0,
            'errors'      => 0,
            'skipped'     => 0,
        ];

        $batchSize = max(50, (int)$this->batch);
        // Cursor pagination by id keeps both APPLY and DRY-RUN modes
        // making forward progress even when no DB writes happen.
        $lastId = 0;
        // In APPLY+resume mode the WHERE filter shrinks each pass, so a
        // cursor by id still advances correctly. In DRY-RUN+resume we
        // would otherwise loop forever — the cursor is what fixes that.

        while (true) {
            $q = (new Query())
                ->select(['id', 'fileName', 'fileHash', 'customer_id', 'contractId',
                          'checksum_sha256', 'file_size', 'mime_type'])
                ->from('os_ImageManager')
                ->andWhere(['>', 'id', $lastId])
                ->orderBy(['id' => SORT_ASC])
                ->limit($batchSize);

            if (!$this->rehash) {
                $q->andWhere(['checksum_sha256' => null]);
            }

            $rows = $q->all($db);
            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                $stats['scanned']++;
                $lastId = max($lastId, (int)$row['id']);

                if ($this->limit !== null && $stats['scanned'] > (int)$this->limit) {
                    break 2;
                }

                $update = $this->buildUpdate($row, $stats);
                if ($update === null) {
                    continue; // counted in stats already
                }

                if (!$this->apply) {
                    continue;
                }

                try {
                    $db->createCommand()->update(
                        'os_ImageManager',
                        $update,
                        ['id' => (int)$row['id']]
                    )->execute();
                    $stats['updated']++;
                } catch (\Throwable $e) {
                    $stats['errors']++;
                    $this->stdout(
                        "  ✗ row #{$row['id']} update failed: " . $e->getMessage() . "\n",
                        Console::FG_RED
                    );
                    Yii::error('MediaBackfill: row ' . $row['id']
                        . ' failed: ' . $e->getMessage(), __METHOD__);
                }
            }

            $this->stdout(sprintf(
                "  … scanned=%d updated=%d missing=%d orphan=%d errors=%d (lastId=%d)\r",
                $stats['scanned'], $stats['updated'],
                $stats['missing_file'], $stats['orphan'], $stats['errors'], $lastId
            ));

            if (count($rows) < $batchSize) {
                break;
            }
        }

        $this->stdout("\n\nDONE.\n", Console::FG_CYAN, Console::BOLD);
        $this->stdout(sprintf(
            "  scanned=%d  updated=%d  missing-on-disk=%d  orphan=%d  errors=%d  skipped=%d\n",
            $stats['scanned'], $stats['updated'], $stats['missing_file'],
            $stats['orphan'], $stats['errors'], $stats['skipped']
        ));

        if (!$this->apply) {
            $this->stdout("\nDRY-RUN — re-run with --apply to commit.\n\n", Console::FG_YELLOW);
        }

        return ExitCode::OK;
    }

    /**
     * Build the UPDATE payload for one row. Returns NULL when the row
     * should be skipped (counters already incremented inside).
     */
    private function buildUpdate(array $row, array &$stats): ?array
    {
        $id   = (int)$row['id'];
        $hash = (string)$row['fileHash'];
        $name = (string)$row['fileName'];

        // 1. Resolve owner -------------------------------------------------
        // Customer always wins over contract — wizard rows carry both
        // when a customer was attached to a contract during onboarding,
        // and the customer is the more granular owner.
        $entityType = null;
        $entityId   = null;
        if (!empty($row['customer_id'])) {
            $entityType = 'customer';
            $entityId   = (int)$row['customer_id'];
        } elseif (!empty($row['contractId'])) {
            $entityType = 'contract';
            $entityId   = (int)$row['contractId'];
        } else {
            $stats['orphan']++;
            // Still try to checksum the file — orphans need the hash
            // so the recover-orphan-media tool can match across users.
        }

        // 2. Inspect on-disk file -----------------------------------------
        $path = MediaHelper::filePath($id, $hash, $name);

        if (!is_file($path)) {
            $stats['missing_file']++;
            // Print only the first 20 missing-file lines so the console
            // does not drown when the dev box has none of the prod files.
            if ($stats['missing_file'] <= 20) {
                $this->stdout(
                    "  ! row #$id missing file: $path\n",
                    Console::FG_RED
                );
            } elseif ($stats['missing_file'] === 21) {
                $this->stdout(
                    "  ! …additional missing-file warnings suppressed (will be summarised at end).\n",
                    Console::FG_YELLOW
                );
            }
            // We still record entity_type so the row is at least
            // routable via the new schema, plus mark it failed so
            // the health endpoint surfaces it.
            $update = ['processing_status' => 'failed'];
            if ($entityType !== null) {
                $update['entity_type'] = $entityType;
                $update['entity_id']   = $entityId;
            }
            return $update;
        }

        // 3. Skip if already enriched and not in rehash mode --------------
        if (!$this->rehash && !empty($row['checksum_sha256'])) {
            $stats['skipped']++;
            return null;
        }

        // 4. Compute file metadata ----------------------------------------
        $size   = @filesize($path) ?: null;
        $mime   = $this->detectMime($path) ?? $this->guessMimeFromName($name);
        $sha256 = @hash_file('sha256', $path) ?: null;

        $width = null;
        $height = null;
        if ($mime !== null && str_starts_with($mime, 'image/')) {
            $info = @getimagesize($path);
            if (is_array($info)) {
                $width  = (int)($info[0] ?? 0) ?: null;
                $height = (int)($info[1] ?? 0) ?: null;
            }
        }

        $update = [
            'checksum_sha256'   => $sha256,
            'file_size'         => $size,
            'mime_type'         => $mime,
            'width'             => $width,
            'height'            => $height,
            'processing_status' => 'ready',
            'uploaded_via'      => 'legacy',
        ];
        if ($entityType !== null) {
            $update['entity_type'] = $entityType;
            $update['entity_id']   = $entityId;
        }
        return $update;
    }

    private function detectMime(string $path): ?string
    {
        if (function_exists('finfo_open')) {
            $f = @finfo_open(FILEINFO_MIME_TYPE);
            if ($f) {
                $m = @finfo_file($f, $path);
                @finfo_close($f);
                if (is_string($m) && $m !== '') {
                    return $m;
                }
            }
        }
        return null;
    }

    /** Last-ditch MIME guess from the extension only. */
    private function guessMimeFromName(string $name): ?string
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ][$ext] ?? null;
    }

    // ═══════════════════════════════════════════════════════════════
    //  Phase 5 / M5 — `media-backfill/all`
    //  Walks every LEGACY media source still living outside
    //  os_ImageManager and creates the matching unified row via
    //  MediaService::storeFromPath(). The legacy column is rewritten
    //  to the new unified URL so existing reads keep working without
    //  a code change. Original disk files are NOT deleted (M8 will
    //  clean them up after the deprecation window expires).
    // ═══════════════════════════════════════════════════════════════

    /**
     * Usage:
     *   php yii media-backfill/all                 (DRY-RUN, all sources)
     *   php yii media-backfill/all --apply
     *   php yii media-backfill/all --apply --source=lawyers
     *   php yii media-backfill/all --apply --source=companies --batch=200
     */
    public function actionAll(): int
    {
        $this->stdout("\n=== Media Full Back-fill (Phase 5 / M5) ===\n", Console::FG_CYAN, Console::BOLD);
        $this->stdout(sprintf(
            "Mode: %s | Batch: %d | Limit: %s | Source: %s\n\n",
            $this->apply ? 'APPLY' : 'DRY-RUN',
            (int)$this->batch,
            $this->limit !== null ? (int)$this->limit : 'all',
            $this->source ?? 'all'
        ));

        if (Yii::$app->get('media', false) === null) {
            $this->stdout(
                "media component is not registered. Aborting.\n",
                Console::FG_RED
            );
            return ExitCode::CONFIG;
        }

        $sources = [
            'lawyers'     => fn() => $this->backfillLawyers(),
            'employees'   => fn() => $this->backfillEmployees(),
            'companies'   => fn() => $this->backfillCompanies(),
            'judiciary'   => fn() => $this->backfillJudiciary(),
            'movement'    => fn() => $this->backfillMovement(),
            'smart_media' => fn() => $this->backfillSmartMedia(),
        ];

        if ($this->source !== null && !isset($sources[$this->source])) {
            $this->stdout(
                "Unknown --source '{$this->source}'. Valid: "
                . implode(', ', array_keys($sources)) . "\n",
                Console::FG_RED
            );
            return ExitCode::USAGE;
        }

        $totals = [
            'scanned' => 0, 'migrated' => 0, 'already' => 0,
            'missing' => 0, 'errors' => 0, 'bytes' => 0,
        ];

        foreach ($sources as $name => $fn) {
            if ($this->source !== null && $this->source !== $name) {
                continue;
            }
            $this->stdout("\n— $name —\n", Console::FG_BLUE, Console::BOLD);
            try {
                $stats = $fn();
                foreach ($stats as $k => $v) {
                    $totals[$k] = ($totals[$k] ?? 0) + (int)$v;
                }
                $this->stdout(sprintf(
                    "  scanned=%d migrated=%d already-unified=%d missing-on-disk=%d errors=%d size=%s\n",
                    $stats['scanned'] ?? 0,
                    $stats['migrated'] ?? 0,
                    $stats['already'] ?? 0,
                    $stats['missing'] ?? 0,
                    $stats['errors'] ?? 0,
                    $this->formatBytes((int)($stats['bytes'] ?? 0))
                ));
            } catch (\Throwable $e) {
                $this->stdout(
                    "  ✗ source $name failed: " . $e->getMessage() . "\n",
                    Console::FG_RED
                );
                Yii::error("media-backfill/all $name: " . $e->getMessage(), __METHOD__);
                $totals['errors']++;
            }
        }

        $this->stdout("\nDONE — TOTAL ", Console::FG_CYAN, Console::BOLD);
        $this->stdout(sprintf(
            "scanned=%d migrated=%d already=%d missing=%d errors=%d size=%s\n\n",
            $totals['scanned'], $totals['migrated'], $totals['already'],
            $totals['missing'], $totals['errors'],
            $this->formatBytes((int)$totals['bytes'])
        ));

        if (!$this->apply) {
            $this->stdout("DRY-RUN — re-run with --apply to commit.\n\n", Console::FG_YELLOW);
        }

        return ExitCode::OK;
    }

    // ─── Per-source loops ──────────────────────────────────────────

    /**
     * Migrate `os_lawyers_image.image` rows.
     * Mapping: entity_type='lawyer', entity_id=lawyer_id, group='lawyer_photo'.
     */
    private function backfillLawyers(): array
    {
        return $this->migrateRowSet(
            table:        'os_lawyers_image',
            pkColumn:     'id',
            pathColumn:   'image',
            extraColumns: ['lawyer_id'],
            buildContext: function (array $row): ?MediaContext {
                $lawyerId = (int)($row['lawyer_id'] ?? 0);
                if ($lawyerId <= 0) return null;
                return MediaContext::forLawyer($lawyerId);
            }
        );
    }

    /**
     * Migrate `os_employee_files.path` rows.
     * Mapping: entity_type='employee', group='employee_avatar'|'employee_attachment'.
     */
    private function backfillEmployees(): array
    {
        return $this->migrateRowSet(
            table:        'os_employee_files',
            pkColumn:     'id',
            pathColumn:   'path',
            extraColumns: ['user_id', 'type'],
            buildContext: function (array $row): ?MediaContext {
                $employeeId = (int)($row['user_id'] ?? 0);
                if ($employeeId <= 0) return null;
                $group = ((int)($row['type'] ?? 1) === 0)
                    ? 'employee_avatar'
                    : 'employee_attachment';
                return MediaContext::forEmployee($employeeId, $group);
            }
        );
    }

    /**
     * Migrate `os_judiciary_customers_actions` rows. Two file columns:
     *   image          → group='judiciary_action'
     *   decision_file  → group='judiciary_decision'
     */
    private function backfillJudiciary(): array
    {
        $stats = $this->migrateRowSet(
            table:        'os_judiciary_customers_actions',
            pkColumn:     'id',
            pathColumn:   'image',
            extraColumns: [],
            buildContext: function (array $row): ?MediaContext {
                $actionId = (int)($row['id'] ?? 0);
                if ($actionId <= 0) return null;
                return MediaContext::forJudiciaryAction($actionId);
            }
        );
        $stats2 = $this->migrateRowSet(
            table:        'os_judiciary_customers_actions',
            pkColumn:     'id',
            pathColumn:   'decision_file',
            extraColumns: [],
            buildContext: function (array $row): ?MediaContext {
                $actionId = (int)($row['id'] ?? 0);
                if ($actionId <= 0) return null;
                return MediaContext::forJudiciaryAction($actionId)
                    ->withGroupName('judiciary_decision');
            }
        );
        foreach ($stats2 as $k => $v) {
            $stats[$k] = ($stats[$k] ?? 0) + (int)$v;
        }
        return $stats;
    }

    /**
     * Migrate `os_movment.receipt_image` rows.
     * Mapping: entity_type='movement', group='receipt'.
     */
    private function backfillMovement(): array
    {
        return $this->migrateRowSet(
            table:        'os_movment',
            pkColumn:     'id',
            pathColumn:   'receipt_image',
            extraColumns: [],
            buildContext: function (array $row): ?MediaContext {
                $movementId = (int)($row['id'] ?? 0);
                if ($movementId <= 0) return null;
                return MediaContext::forMovement($movementId);
            }
        );
    }

    /**
     * Migrate `os_customer_documents.file_path` rows (Smart Media legacy).
     * Mapping: entity_type='customer', group='smart_media'.
     */
    private function backfillSmartMedia(): array
    {
        $db = Yii::$app->db;
        // Table is optional in some installs (Smart Media side-table).
        if ($db->getTableSchema('os_customer_documents', true) === null) {
            $this->stdout("  (table os_customer_documents not present — skipped)\n", Console::FG_YELLOW);
            return ['scanned' => 0, 'migrated' => 0, 'already' => 0, 'missing' => 0, 'errors' => 0];
        }
        return $this->migrateRowSet(
            table:        'os_customer_documents',
            pkColumn:     'id',
            pathColumn:   'file_path',
            extraColumns: ['customer_id'],
            buildContext: function (array $row): ?MediaContext {
                $customerId = (int)($row['customer_id'] ?? 0);
                if ($customerId <= 0) return null;
                return MediaContext::forCustomer($customerId, 'smart_media');
            }
        );
    }

    /**
     * Companies have JSON arrays in `commercial_register` and
     * `trade_license`; each element of the array becomes a separate
     * unified row, but the JSON itself is rewritten in place to
     * preserve back-compat with the existing UI.
     */
    private function backfillCompanies(): array
    {
        $db = Yii::$app->db;
        $stats = ['scanned' => 0, 'migrated' => 0, 'already' => 0, 'missing' => 0, 'errors' => 0, 'bytes' => 0];
        $batchSize = max(50, (int)$this->batch);
        $lastId = 0;

        while (true) {
            $rows = (new Query())
                ->select(['id', 'commercial_register', 'trade_license'])
                ->from('os_companies')
                ->andWhere(['>', 'id', $lastId])
                ->andWhere(
                    'commercial_register IS NOT NULL OR trade_license IS NOT NULL'
                )
                ->orderBy(['id' => SORT_ASC])
                ->limit($batchSize)
                ->all($db);

            if (empty($rows)) break;

            foreach ($rows as $row) {
                $stats['scanned']++;
                $companyId = (int)$row['id'];
                $lastId = max($lastId, $companyId);

                if ($this->limit !== null && $stats['scanned'] > (int)$this->limit) {
                    break 2;
                }

                foreach (['commercial_register', 'trade_license'] as $col) {
                    $raw = (string)($row[$col] ?? '');
                    if ($raw === '') continue;
                    $items = json_decode($raw, true);
                    if (!is_array($items) || empty($items)) continue;

                    $changed = false;
                    foreach ($items as &$item) {
                        if (!is_array($item) || empty($item['path'])) continue;
                        $oldPath = (string)$item['path'];
                        if ($this->isUnifiedPath($oldPath)) {
                            $stats['already']++;
                            continue;
                        }

                        $abs = $this->resolveLegacyAbsolutePath($oldPath);
                        if (!is_file($abs)) {
                            $stats['missing']++;
                            $this->maybeWarnMissing($oldPath, 'companies/' . $col);
                            continue;
                        }

                        $ctx = MediaContext::forCompany($companyId, $col)
                            ->withOriginalName(
                                (string)($item['name'] ?? basename($abs))
                            );

                        $stats['bytes'] += (int)(@filesize($abs) ?: 0);

                        if (!$this->apply) {
                            $stats['migrated']++;
                            continue;
                        }

                        try {
                            $result = Yii::$app->media->storeFromPath($abs, $ctx);
                            $item['path'] = ltrim($result->url, '/');
                            $changed = true;
                            $stats['migrated']++;
                        } catch (\Throwable $e) {
                            $stats['errors']++;
                            $this->stdout(
                                "  ✗ company #$companyId/$col path '$oldPath': "
                                . $e->getMessage() . "\n",
                                Console::FG_RED
                            );
                            Yii::error('backfill/all companies: ' . $e->getMessage(), __METHOD__);
                        }
                    }
                    unset($item);

                    if ($changed && $this->apply) {
                        try {
                            $db->createCommand()->update(
                                'os_companies',
                                [$col => json_encode($items, JSON_UNESCAPED_UNICODE)],
                                ['id' => $companyId]
                            )->execute();
                        } catch (\Throwable $e) {
                            $stats['errors']++;
                            $this->stdout(
                                "  ✗ company #$companyId rewrite $col failed: "
                                . $e->getMessage() . "\n",
                                Console::FG_RED
                            );
                        }
                    }
                }
            }

            if (count($rows) < $batchSize) break;
        }

        return $stats;
    }

    /**
     * Generic per-table migrator. Reads each row, ensures the file is
     * still under @backend/web at the legacy relative path, then calls
     * MediaService::storeFromPath() and rewrites the legacy column to
     * the new unified URL (sans leading slash, matching the pattern
     * already used by every Phase 3 controller).
     *
     * @param string                          $table
     * @param string                          $pkColumn
     * @param string                          $pathColumn
     * @param array<int,string>               $extraColumns
     * @param callable(array): ?MediaContext  $buildContext
     * @return array{scanned:int, migrated:int, already:int, missing:int, errors:int}
     */
    private function migrateRowSet(
        string   $table,
        string   $pkColumn,
        string   $pathColumn,
        array    $extraColumns,
        callable $buildContext
    ): array {
        $db = Yii::$app->db;
        $stats = ['scanned' => 0, 'migrated' => 0, 'already' => 0, 'missing' => 0, 'errors' => 0, 'bytes' => 0];
        $batchSize = max(50, (int)$this->batch);
        $lastId = 0;

        $select = array_unique(array_merge([$pkColumn, $pathColumn], $extraColumns));

        while (true) {
            $rows = (new Query())
                ->select($select)
                ->from($table)
                ->andWhere(['>', $pkColumn, $lastId])
                ->andWhere(['not', [$pathColumn => null]])
                ->andWhere(['<>', $pathColumn, ''])
                ->orderBy([$pkColumn => SORT_ASC])
                ->limit($batchSize)
                ->all($db);

            if (empty($rows)) break;

            foreach ($rows as $row) {
                $stats['scanned']++;
                $pk = (int)$row[$pkColumn];
                $lastId = max($lastId, $pk);

                if ($this->limit !== null && $stats['scanned'] > (int)$this->limit) {
                    break 2;
                }

                $oldPath = (string)$row[$pathColumn];
                if ($oldPath === '') continue;

                if ($this->isUnifiedPath($oldPath)) {
                    $stats['already']++;
                    continue;
                }

                $abs = $this->resolveLegacyAbsolutePath($oldPath);
                if (!is_file($abs)) {
                    $stats['missing']++;
                    $this->maybeWarnMissing($oldPath, $table);
                    continue;
                }

                $ctx = $buildContext($row);
                if ($ctx === null) {
                    $stats['errors']++;
                    $this->stdout(
                        "  ✗ $table#$pk: cannot build MediaContext (likely missing FK)\n",
                        Console::FG_RED
                    );
                    continue;
                }
                $ctx = $ctx->withOriginalName(basename($abs));

                $stats['bytes'] += (int)(@filesize($abs) ?: 0);

                if (!$this->apply) {
                    $stats['migrated']++;
                    continue;
                }

                try {
                    $result = Yii::$app->media->storeFromPath($abs, $ctx);
                    $newPath = ltrim($result->url, '/');
                    $db->createCommand()->update(
                        $table,
                        [$pathColumn => $newPath],
                        [$pkColumn => $pk]
                    )->execute();
                    $stats['migrated']++;
                } catch (\Throwable $e) {
                    $stats['errors']++;
                    $this->stdout(
                        "  ✗ $table#$pk path '$oldPath': " . $e->getMessage() . "\n",
                        Console::FG_RED
                    );
                    Yii::error("media-backfill/all $table#$pk: " . $e->getMessage(), __METHOD__);
                }
            }

            $this->stdout(sprintf(
                "  … %s scanned=%d migrated=%d already=%d missing=%d errors=%d (lastId=%d)\r",
                $table,
                $stats['scanned'], $stats['migrated'], $stats['already'],
                $stats['missing'], $stats['errors'], $lastId
            ));

            if (count($rows) < $batchSize) break;
        }
        $this->stdout("\n");

        return $stats;
    }

    /**
     * "Already unified" detection — path lives under the LocalDiskDriver
     * URL prefix, so re-running the backfill is a no-op for it.
     */
    private function isUnifiedPath(string $path): bool
    {
        $p = ltrim($path, '/');
        return str_starts_with($p, 'images/imagemanager/');
    }

    /**
     * Convert a legacy web-relative path (like 'uploads/lawyers/photos/x.jpg')
     * to an absolute path under @backend/web. We tolerate the optional
     * leading slash so call-sites that store both shapes are handled.
     */
    private function resolveLegacyAbsolutePath(string $path): string
    {
        return Yii::getAlias('@backend/web') . DIRECTORY_SEPARATOR
            . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }

    /** Console hygiene — capped at 20 missing-file warnings per source. */
    private array $missingCounters = [];

    /** Pretty-print a byte count for the dry-run report. */
    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int)floor(log($bytes, 1024));
        $i = max(0, min($i, count($units) - 1));
        return number_format($bytes / pow(1024, $i), $i === 0 ? 0 : 2) . ' ' . $units[$i];
    }

    private function maybeWarnMissing(string $path, string $source): void
    {
        // Initialise on first miss to avoid Yii's error-handler turning the
        // "Undefined array key" notice into an ErrorException, which would
        // otherwise abort the whole source mid-batch.
        if (!isset($this->missingCounters[$source])) {
            $this->missingCounters[$source] = 0;
        }
        $n = ++$this->missingCounters[$source];
        if ($n <= 20) {
            $this->stdout("  ! $source missing on disk: $path\n", Console::FG_RED);
        } elseif ($n === 21) {
            $this->stdout("  ! …additional $source missing-file warnings suppressed.\n", Console::FG_YELLOW);
        }
    }
}
