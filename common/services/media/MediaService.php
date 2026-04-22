<?php

namespace common\services\media;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\web\UploadedFile;
use yii\db\Query;
use yii\db\Expression;
use backend\models\Media;
use backend\modules\customers\components\VisionService;
use common\contracts\StorageDriverInterface;
use common\jobs\media\GenerateThumbnailJob;
use common\jobs\media\ExtractMetadataJob;
use common\jobs\media\ScanForMalwareJob;
use common\jobs\media\OptimizeImageJob;

/**
 * Phase 1 / M1.4 — The unified entry point for every media operation.
 *
 * Replaces 17 ad-hoc upload paths with a single, well-tested service.
 * Registered as `Yii::$app->media` (see common/config/main.php).
 *
 * Three write paths exist; they all converge on {@see self::persist()}
 * which is the single function that knows how to insert a Media row,
 * persist the bytes via the StorageDriver, and dispatch async jobs:
 *
 *   • store(UploadedFile)       — the 99% case (HTTP form upload).
 *   • storeFromBase64(string)   — webcam captures.
 *   • storeFromPath(string)     — back-fill, REST imports, OCR scratch.
 *
 * Read paths ({@see url()}, {@see thumbUrl()}, {@see getById()}) are
 * trivial wrappers — they exist on the service so callers never need
 * to import MediaHelper or StorageDriverInterface directly.
 *
 * Lifecycle paths:
 *   • adopt()         — attach an orphan to its owner (wizard finalize).
 *   • adoptOrphans()  — bulk-adopt every orphan a user uploaded recently.
 *   • replace()       — swap the bytes of an existing row in-place.
 *   • delete()        — soft by default, hard when explicitly requested.
 *
 * Idempotency / dedup:
 *   • SHA-256 is computed BEFORE the row is inserted. If the same
 *     (entity_type, entity_id, group_name, sha256) tuple already
 *     exists and is not soft-deleted, we return that row instead of
 *     creating a duplicate, and log a `dedup_hit` audit entry. This
 *     turns double-clicks of the upload button into no-ops.
 *
 * Async jobs are dispatched only when the queue component is wired up;
 * if it is missing the row is marked `processing_status='ready'`
 * synchronously so legacy environments (and tests) keep working.
 */
class MediaService extends Component
{
    /**
     * The audit log table created by m260419_100002. Kept as a const
     * so refactors don't accidentally diverge between the migration
     * and the writers.
     */
    private const AUDIT_TABLE = 'media_audit_log';

    /**
     * Dedup window for "same user re-uploaded the same bytes".
     * 24h matches what users perceive as a single session — beyond
     * that we treat the upload as new (defensive: maybe the underlying
     * document genuinely changed and they re-scanned it).
     */
    private const DEDUP_WINDOW_SEC = 86400;

    /**
     * Optional override; when null we read `Yii::$app->storage`.
     * Settable from tests via the `setStorage()` helper.
     */
    private ?StorageDriverInterface $storage = null;

    public function init()
    {
        parent::init();
        // We resolve the storage component lazily so a test that
        // injects its own driver doesn't get clobbered by the
        // application config.
    }

    // ───────────────────────────────────────────────────────────────
    // Public write API
    // ───────────────────────────────────────────────────────────────

    /**
     * Store an HTTP-uploaded file. The 99% path.
     *
     * @throws \InvalidArgumentException on validation failure
     * @throws \RuntimeException         on IO failure
     */
    public function store(UploadedFile $file, MediaContext $ctx): MediaResult
    {
        if ($file === null || $file->error !== UPLOAD_ERR_OK || !is_file($file->tempName)) {
            throw new \InvalidArgumentException(
                'MediaService::store — upload error code ' . ($file->error ?? -1)
            );
        }

        $originalName = $ctx->originalName ?? $file->name;
        return $this->persist(
            $file->tempName,
            $originalName,
            $file->type ?: null,
            $ctx,
            keepSource: true
        );
    }

    /**
     * Decode a data URL / raw base64 payload to a temp file then
     * persist. Used by the webcam capture endpoint.
     *
     * Accepts both forms:
     *   "data:image/jpeg;base64,/9j/4AAQ..."
     *   "/9j/4AAQ..."
     */
    public function storeFromBase64(string $data, MediaContext $ctx): MediaResult
    {
        // Strip the optional data-URL header, capture the MIME hint.
        $hintedMime = null;
        if (preg_match('#^data:([\w./+-]+);base64,(.*)$#s', $data, $m)) {
            $hintedMime = $m[1];
            $data = $m[2];
        }

        $bin = base64_decode($data, true);
        if ($bin === false || strlen($bin) === 0) {
            throw new \InvalidArgumentException('MediaService::storeFromBase64 — invalid base64 payload');
        }

        $ext = $this->mimeToExtension($hintedMime) ?? 'png';
        $tmp = tempnam(sys_get_temp_dir(), 'media_b64_');
        if ($tmp === false) {
            throw new \RuntimeException('MediaService::storeFromBase64 — cannot create temp file');
        }
        $tmpExt = $tmp . '.' . $ext;
        if (!@rename($tmp, $tmpExt)) {
            // Some filesystems disallow the rename; fall back to writing under the original tempnam.
            $tmpExt = $tmp;
        }
        if (file_put_contents($tmpExt, $bin) === false) {
            @unlink($tmpExt);
            throw new \RuntimeException('MediaService::storeFromBase64 — cannot write temp file');
        }

        try {
            $name = $ctx->originalName ?? ('webcam_' . date('Ymd_His') . '.' . $ext);
            return $this->persist($tmpExt, $name, $hintedMime, $ctx, keepSource: false);
        } finally {
            @unlink($tmpExt);
        }
    }

    /**
     * Persist a file already on disk (back-fill, REST imports, OCR
     * scratch). The source file is NOT deleted — caller owns its
     * lifecycle.
     */
    public function storeFromPath(string $path, MediaContext $ctx): MediaResult
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException("MediaService::storeFromPath — missing file: $path");
        }
        $name = $ctx->originalName ?? basename($path);
        return $this->persist($path, $name, null, $ctx, keepSource: true);
    }

    /**
     * Adopt an orphan media row into an entity. Called by the wizard
     * finalize step to attach pre-uploaded scan images to the
     * customer that was just created.
     *
     * Returns true when the row was adopted (or already belonged to
     * the entity), false when the row does not exist or already
     * belongs to a *different* entity.
     */
    public function adopt(int $mediaId, string $entityType, int $entityId): bool
    {
        $media = Media::findOne($mediaId);
        if ($media === null) {
            return false;
        }

        $existingType = $media->getAttribute('entity_type');
        $existingId   = $media->getAttribute('entity_id');

        if ($existingType !== null && $existingId !== null) {
            // Already adopted by someone — only OK if it's the same
            // (entity_type, entity_id) pair (idempotent re-adopt).
            if ((string)$existingType === $entityType && (int)$existingId === $entityId) {
                return true;
            }
            Yii::warning(
                "MediaService::adopt — media #$mediaId already owned by "
                . "$existingType:$existingId, refused to re-assign to $entityType:$entityId",
                __METHOD__
            );
            return false;
        }

        $media->setAttribute('entity_type', $entityType);
        $media->setAttribute('entity_id',   $entityId);

        // Mirror to legacy columns for back-compat readers.
        if ($entityType === 'customer' && $media->hasAttribute('customer_id')) {
            $media->setAttribute('customer_id', $entityId);
        }
        if ($entityType === 'contract' && $media->hasAttribute('contractId')) {
            $media->setAttribute('contractId', (string)$entityId);
        }

        $media->setAttribute('modified', date('Y-m-d H:i:s'));
        if (!$media->save(false)) {
            return false;
        }

        $this->audit('adopt', $media, $this->actorUserId(), [
            'previous_entity_type' => $existingType,
            'previous_entity_id'   => $existingId,
        ]);
        return true;
    }

    /**
     * Bulk adoption: every orphan a user uploaded since $sinceTs
     * with a wizard-known group, attached to (entityType, entityId).
     *
     * Returns the count of adopted rows. Mirrors the heuristic that
     * RecoverOrphanMediaController uses but for the live wizard
     * finalize path (where we have certainty about the user/customer).
     */
    public function adoptOrphans(string $entityType, int $entityId, int $userId, int $sinceTs): int
    {
        $wizardGroups = GroupNameRegistry::wizardGroups();
        if (empty($wizardGroups)) {
            return 0;
        }

        $sinceStr = date('Y-m-d H:i:s', max(0, $sinceTs - 300)); // 5-min skew

        $ids = (new Query())
            ->select(['id'])
            ->from('os_ImageManager')
            ->where([
                'and',
                ['entity_id' => null],
                ['createdBy' => $userId],
                ['groupName' => $wizardGroups],
                ['>=', 'created', $sinceStr],
            ])
            ->column(Yii::$app->db);

        if (empty($ids)) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $update = [
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'modified'    => $now,
        ];
        if ($entityType === 'customer') {
            $update['customer_id'] = $entityId;
        } elseif ($entityType === 'contract') {
            $update['contractId'] = (string)$entityId;
        }

        $affected = Yii::$app->db->createCommand()->update(
            'os_ImageManager',
            $update,
            ['and', ['id' => $ids], ['entity_id' => null]]
        )->execute();

        $this->writeAudit([
            'action'        => 'adopt',
            'media_id'      => null, // bulk; payload carries the list
            'actor_user_id' => $userId,
            'entity_type'   => $entityType,
            'entity_id'     => $entityId,
            'payload_json'  => $this->encodeJson([
                'bulk'      => true,
                'ids'       => array_map('intval', $ids),
                'count'     => (int)$affected,
                'since_ts'  => $sinceTs,
            ]),
        ]);

        return (int)$affected;
    }

    /**
     * Replace the bytes of an existing media row.
     *
     * The OLD bytes are NOT deleted from storage — they are renamed
     * with a `.replaced-{timestamp}` suffix so a panicked rep can
     * still recover them via support. The cron-job sweeper hard-
     * deletes those suffixes after 30 days.
     */
    public function replace(int $mediaId, UploadedFile $newFile): MediaResult
    {
        $media = Media::findOne($mediaId);
        if ($media === null) {
            throw new \InvalidArgumentException("MediaService::replace — media #$mediaId not found");
        }
        if ($newFile === null || $newFile->error !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('MediaService::replace — bad upload');
        }

        $oldKey = $this->keyFor($media);

        // Compute the new metadata.
        $sha = hash_file('sha256', $newFile->tempName);
        if ($sha === false) {
            throw new \RuntimeException('MediaService::replace — hash_file failed');
        }

        $size = (int)@filesize($newFile->tempName);
        $mime = $this->detectMime($newFile->tempName) ?? ($newFile->type ?: 'application/octet-stream');

        // Best-effort backup of the previous bytes.
        $driver = $this->driver();
        try {
            $oldPath = $driver->localPath($oldKey);
            if ($oldPath !== null && is_file($oldPath)) {
                @rename($oldPath, $oldPath . '.replaced-' . date('Ymd_His'));
            }
        } catch (\Throwable $e) {
            Yii::warning('MediaService::replace — backup failed: ' . $e->getMessage(), __METHOD__);
        }

        // Update the row first so buildKey() uses the new hash for
        // the new bytes' key.
        $media->setAttribute('checksum_sha256', $sha);
        $media->setAttribute('file_size', $size);
        $media->setAttribute('mime_type', $mime);
        $media->setAttribute('processing_status', 'pending');
        $media->setAttribute('modified', date('Y-m-d H:i:s'));
        // fileHash/fileName are kept in sync for legacy readers.
        $media->fileHash = substr($sha, 0, 32);
        $media->fileName = $newFile->name;
        $media->save(false);

        $newKey = $driver->buildKey((int)$media->id, $sha, $newFile->name);
        $driver->put($newKey, $newFile->tempName);

        $this->audit('replace', $media, $this->actorUserId(), [
            'old_key' => $oldKey,
            'new_key' => $newKey,
            'old_sha' => null, // we did not preserve it; opt-in later
        ]);

        $this->dispatchPostStoreJobs($media);

        return MediaResult::fromMedia(
            $media,
            $driver->url($newKey),
            $this->thumbUrlFor($media)
        );
    }

    /**
     * Delete a media row.
     *
     * Soft delete by default — sets `deleted_at` so the row drops
     * out of every "active media" query but the bytes stay on disk
     * for restoration. Hard delete removes both the row and the
     * bytes; use only for GC of confirmed garbage.
     */
    public function delete(int $mediaId, bool $hardDelete = false): bool
    {
        $media = Media::findOne($mediaId);
        if ($media === null) {
            return false;
        }

        if ($hardDelete) {
            $key = $this->keyFor($media);
            try {
                $this->driver()->delete($key);
            } catch (\Throwable $e) {
                Yii::warning('MediaService::delete — driver delete failed: ' . $e->getMessage(), __METHOD__);
            }
            $this->audit('delete', $media, $this->actorUserId(), ['hard' => true]);
            return (bool)$media->delete();
        }

        $media->setAttribute('deleted_at', date('Y-m-d H:i:s'));
        $media->setAttribute('modified', date('Y-m-d H:i:s'));
        if (!$media->save(false)) {
            return false;
        }
        $this->audit('delete', $media, $this->actorUserId(), ['hard' => false]);
        return true;
    }

    // ───────────────────────────────────────────────────────────────
    // Public read API
    // ───────────────────────────────────────────────────────────────

    public function getById(int $mediaId): ?Media
    {
        return Media::findOne($mediaId);
    }

    public function url(int $mediaId): string
    {
        $m = $this->getById($mediaId);
        return $m === null ? '' : $this->driver()->url($this->keyFor($m));
    }

    public function thumbUrl(int $mediaId): string
    {
        $m = $this->getById($mediaId);
        return $m === null ? '' : $this->thumbUrlFor($m);
    }

    /**
     * Force-regenerate the thumbnail. Used by the admin "rebuild
     * thumbnails" button when a manual edit corrupted the file.
     */
    public function regenerateThumbnail(int $mediaId): void
    {
        $m = $this->getById($mediaId);
        if ($m === null) return;
        $this->dispatchJob(new GenerateThumbnailJob(['mediaId' => (int)$m->id, 'force' => true]));
    }

    // ───────────────────────────────────────────────────────────────
    // Configuration helpers
    // ───────────────────────────────────────────────────────────────

    public function setStorage(StorageDriverInterface $driver): void
    {
        $this->storage = $driver;
    }

    private function driver(): StorageDriverInterface
    {
        if ($this->storage !== null) {
            return $this->storage;
        }
        try {
            $component = Yii::$app->get('storage');
        } catch (InvalidConfigException $e) {
            throw new InvalidConfigException(
                'MediaService: `storage` component is not registered. '
                . 'Add it to common/config/main.php — see LocalDiskDriver.'
            );
        }
        if (!$component instanceof StorageDriverInterface) {
            throw new InvalidConfigException(
                'MediaService: `storage` component must implement StorageDriverInterface, got '
                . get_class($component)
            );
        }
        $this->storage = $component;
        return $component;
    }

    // ───────────────────────────────────────────────────────────────
    // The single insert path
    // ───────────────────────────────────────────────────────────────

    /**
     * The one and only INSERT into os_ImageManager. Every public
     * write-path funnels here.
     *
     * @param string  $sourcePath  filesystem path to bytes (will not be deleted)
     * @param string  $originalName  user-facing file name (used for extension + display)
     * @param ?string $hintedMime  MIME guess from upload metadata; verified against the bytes
     * @param bool    $keepSource  reserved for future move-vs-copy optimisations
     */
    private function persist(
        string $sourcePath,
        string $originalName,
        ?string $hintedMime,
        MediaContext $ctx,
        bool $keepSource = true
    ): MediaResult {
        // 1. Validate the (groupName, entityType) combo upfront so
        //    we don't waste a hash on a request that can never land.
        $canonicalGroup = GroupNameRegistry::canonicalize($ctx->groupName);
        if (!GroupNameRegistry::validate($canonicalGroup, $ctx->entityType)) {
            throw new \InvalidArgumentException(sprintf(
                'MediaService: groupName "%s" is not allowed for entity_type "%s"',
                $canonicalGroup, $ctx->entityType
            ));
        }

        // 2. MIME + size gates.
        $size = (int)@filesize($sourcePath);
        if ($size <= 0) {
            throw new \InvalidArgumentException('MediaService: source file is empty');
        }
        $maxBytes = GroupNameRegistry::maxBytes($canonicalGroup);
        if ($maxBytes > 0 && $size > $maxBytes) {
            throw new \InvalidArgumentException(sprintf(
                'MediaService: file size %d exceeds limit %d for groupName "%s"',
                $size, $maxBytes, $canonicalGroup
            ));
        }

        $detectedMime = $this->detectMime($sourcePath) ?? $hintedMime ?? 'application/octet-stream';
        if (!GroupNameRegistry::mimeAllowed($canonicalGroup, $detectedMime)) {
            throw new \InvalidArgumentException(sprintf(
                'MediaService: MIME "%s" is not allowed for groupName "%s"',
                $detectedMime, $canonicalGroup
            ));
        }

        // 3. Hash. After this point we can dedup.
        $sha = @hash_file('sha256', $sourcePath);
        if ($sha === false || strlen($sha) !== 64) {
            throw new \RuntimeException('MediaService: hash_file failed for ' . $sourcePath);
        }

        // 4. Dedup probe — same uploader, same checksum, same group,
        //    within 24h, not soft-deleted → return the existing row.
        $duplicate = $this->findRecentDuplicate($sha, $canonicalGroup, $ctx);
        if ($duplicate !== null) {
            $this->audit('dedup_hit', $duplicate, $ctx->userId, [
                'group' => $canonicalGroup, 'sha256' => $sha, 'size' => $size,
            ]);
            return MediaResult::fromMedia(
                $duplicate,
                $this->driver()->url($this->keyFor($duplicate)),
                $this->thumbUrlFor($duplicate)
            );
        }

        // 5. Inspect image dimensions when applicable. Cheap and
        //    nice to have available immediately for picture <img> tags.
        [$width, $height] = $this->probeDimensions($sourcePath, $detectedMime);

        // 6. Insert the row, then upload. We do INSERT first so we
        //    own a numeric id we can embed in the storage key. If
        //    the upload fails we delete the row to keep the table
        //    clean (no zombie rows pointing at missing bytes).
        $media = new Media();
        $media->fileName = mb_substr($originalName, 0, 128);
        $media->fileHash = substr($sha, 0, 32);    // legacy 32-char column
        $media->groupName = mb_substr($canonicalGroup, 0, 50);
        $media->created = date('Y-m-d H:i:s');
        $media->createdBy = $ctx->userId;
        // New columns — assigned dynamically because the AR model's
        // `rules()` predates them, but the columns exist after the
        // M0.1 migration runs.
        $media->setAttribute('entity_type',       $ctx->entityType);
        $media->setAttribute('entity_id',         $ctx->entityId);
        $media->setAttribute('file_size',         $size);
        $media->setAttribute('mime_type',         $detectedMime);
        $media->setAttribute('width',             $width);
        $media->setAttribute('height',            $height);
        $media->setAttribute('checksum_sha256',   $sha);
        $media->setAttribute('processing_status', 'pending');
        $media->setAttribute('uploaded_via',      $ctx->uploadedVia);

        // Mirror to legacy columns so the old read paths keep working.
        if ($ctx->customerId !== null && $media->hasAttribute('customer_id')) {
            $media->setAttribute('customer_id', $ctx->customerId);
        } elseif ($ctx->entityType === 'customer' && $ctx->entityId !== null
                  && $media->hasAttribute('customer_id')) {
            $media->setAttribute('customer_id', $ctx->entityId);
        }
        if ($ctx->contractId !== null && $media->hasAttribute('contractId')) {
            $media->setAttribute('contractId', $ctx->contractId);
        } elseif ($ctx->entityType === 'contract' && $ctx->entityId !== null
                  && $media->hasAttribute('contractId')) {
            $media->setAttribute('contractId', (string)$ctx->entityId);
        }

        if (!$media->save(false)) {
            throw new \RuntimeException(
                'MediaService: failed to insert os_ImageManager row: '
                . json_encode($media->getErrors(), JSON_UNESCAPED_UNICODE)
            );
        }

        // 7. Upload bytes via the storage driver.
        $driver = $this->driver();
        $key = $driver->buildKey((int)$media->id, $sha, $originalName);
        try {
            $driver->put($key, $sourcePath);
        } catch (\Throwable $e) {
            // Roll back the row so we don't accumulate dangling DB entries.
            try { $media->delete(); } catch (\Throwable) {}
            throw new \RuntimeException(
                'MediaService: storage put failed: ' . $e->getMessage(), 0, $e
            );
        }

        // 8. Optional Vision classification (synchronous — same call
        //    pattern that SmartMediaController used to make inline).
        $classification = null;
        if ($ctx->autoClassify) {
            try {
                $localPath = $driver->localPath($key);
                if ($localPath !== null) {
                    $classification = VisionService::classify(
                        $localPath,
                        $ctx->customerId
                    );
                }
            } catch (\Throwable $e) {
                Yii::warning('MediaService: VisionService::classify failed: ' . $e->getMessage(), __METHOD__);
            }
        }

        // 9. Audit.
        $this->audit('store', $media, $ctx->userId, [
            'sha256'       => $sha,
            'size'         => $size,
            'mime'         => $detectedMime,
            'group'        => $canonicalGroup,
            'uploaded_via' => $ctx->uploadedVia,
            'classified'   => $classification !== null,
        ]);

        // 10. Schedule the async pipeline. If no queue is configured
        //     the dispatcher silently flips status to 'ready'.
        $this->dispatchPostStoreJobs($media);

        return MediaResult::fromMedia(
            $media,
            $driver->url($key),
            $this->thumbUrlFor($media),
            $classification
        );
    }

    // ───────────────────────────────────────────────────────────────
    // Internals
    // ───────────────────────────────────────────────────────────────

    /**
     * Looks for a non-soft-deleted row uploaded by the same user with
     * the same SHA-256 + groupName within the dedup window.
     */
    private function findRecentDuplicate(string $sha, string $groupName, MediaContext $ctx): ?Media
    {
        if ($ctx->userId === null) {
            return null; // can't scope dedup safely without an actor
        }
        $sinceStr = date('Y-m-d H:i:s', time() - self::DEDUP_WINDOW_SEC);

        $query = Media::find()
            ->where([
                'and',
                ['checksum_sha256' => $sha],
                ['groupName'       => $groupName],
                ['createdBy'       => $ctx->userId],
                ['>=', 'created',   $sinceStr],
                ['deleted_at'      => null],
            ])
            ->orderBy(['id' => SORT_DESC])
            ->limit(1);

        return $query->one();
    }

    /**
     * Returns [width, height] or [null, null] when not an image.
     *
     * @return array{0:?int,1:?int}
     */
    private function probeDimensions(string $path, string $mime): array
    {
        if (!str_starts_with($mime, 'image/')) {
            return [null, null];
        }
        $info = @getimagesize($path);
        if (!is_array($info)) {
            return [null, null];
        }
        return [
            (int)($info[0] ?? 0) ?: null,
            (int)($info[1] ?? 0) ?: null,
        ];
    }

    private function detectMime(string $path): ?string
    {
        if (function_exists('finfo_open')) {
            $f = @finfo_open(FILEINFO_MIME_TYPE);
            if ($f) {
                $m = @finfo_file($f, $path);
                @finfo_close($f);
                if (is_string($m) && $m !== '') return $m;
            }
        }
        return null;
    }

    private function mimeToExtension(?string $mime): ?string
    {
        return [
            'image/jpeg' => 'jpg',
            'image/jpg'  => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            'application/pdf' => 'pdf',
        ][$mime ?? ''] ?? null;
    }

    /**
     * Storage key for an existing Media row, derived deterministically
     * from its id + checksum + filename. Falls back to the legacy
     * fileHash when checksum_sha256 is empty (back-fill not yet run
     * for this row).
     */
    private function keyFor(Media $m): string
    {
        $sha = (string)($m->getAttribute('checksum_sha256') ?? '');
        if ($sha === '') {
            // Pad the legacy 32-char fileHash to a 64-char hex so
            // LocalDiskDriver::buildKey's substring grab still yields
            // exactly the legacy `{id}_{fileHash}.{ext}` layout.
            $sha = str_pad((string)$m->fileHash, 64, '0');
        }
        return $this->driver()->buildKey((int)$m->id, $sha, (string)$m->fileName);
    }

    /**
     * Smart Media historically used a different thumb URL convention
     * (`/uploads/customers/documents/thumbs/thumb_…`). We keep the
     * legacy URL format until Phase 6 swaps everyone to the unified
     * thumbnail endpoint.
     */
    private function thumbUrlFor(Media $m): string
    {
        return \backend\helpers\MediaHelper::thumbUrl(
            (int)$m->id, (string)$m->fileHash, (string)$m->fileName
        );
    }

    /**
     * Fire the post-store async pipeline. Falls back to no-ops when
     * the queue component is missing.
     */
    private function dispatchPostStoreJobs(Media $m): void
    {
        $mediaId = (int)$m->id;
        $jobs = [
            new ScanForMalwareJob(['mediaId' => $mediaId]),
            new ExtractMetadataJob(['mediaId' => $mediaId]),
            new OptimizeImageJob(['mediaId' => $mediaId]),
            new GenerateThumbnailJob(['mediaId' => $mediaId]),
        ];

        $dispatched = 0;
        foreach ($jobs as $job) {
            if ($this->dispatchJob($job)) {
                $dispatched++;
            }
        }

        // No queue → run nothing async, mark as ready immediately so
        // the rest of the system doesn't see it stuck in 'pending'.
        if ($dispatched === 0) {
            $m->setAttribute('processing_status', 'ready');
            $m->save(false);
        }
    }

    /** Returns true when the job was successfully pushed. */
    private function dispatchJob(object $job): bool
    {
        try {
            $queue = Yii::$app->get('queue', false);
            if ($queue === null) return false;
            $queue->push($job);
            return true;
        } catch (\Throwable $e) {
            Yii::warning('MediaService: queue push failed: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    // ───────────────────────────────────────────────────────────────
    // Audit
    // ───────────────────────────────────────────────────────────────

    private function audit(string $action, Media $m, ?int $userId, array $payload = []): void
    {
        $this->writeAudit([
            'action'        => $action,
            'media_id'      => (int)$m->id,
            'actor_user_id' => $userId,
            'entity_type'   => $m->getAttribute('entity_type'),
            'entity_id'     => $m->getAttribute('entity_id'),
            'group_name'    => $m->groupName,
            'uploaded_via'  => $m->getAttribute('uploaded_via'),
            'payload_json'  => $this->encodeJson($payload),
        ]);
    }

    private function writeAudit(array $row): void
    {
        try {
            $row += [
                'ip'         => $this->clientIp(),
                'user_agent' => $this->userAgent(),
                'created_at' => date('Y-m-d H:i:s'),
            ];
            Yii::$app->db->createCommand()->insert(self::AUDIT_TABLE, $row)->execute();
        } catch (\Throwable $e) {
            // Audit MUST NEVER break the actual write path — log and swallow.
            Yii::warning('MediaService: audit insert failed: ' . $e->getMessage(), __METHOD__);
        }
    }

    private function actorUserId(): ?int
    {
        try {
            $u = Yii::$app->user ?? null;
            if ($u === null || $u->isGuest) return null;
            return (int)$u->id;
        } catch (\Throwable) {
            return null;
        }
    }

    private function clientIp(): ?string
    {
        try {
            return Yii::$app->request->userIP ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function userAgent(): ?string
    {
        try {
            $ua = Yii::$app->request->userAgent ?? null;
            return $ua !== null ? mb_substr($ua, 0, 255) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function encodeJson(array $payload): ?string
    {
        $j = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $j === false ? null : $j;
    }
}
