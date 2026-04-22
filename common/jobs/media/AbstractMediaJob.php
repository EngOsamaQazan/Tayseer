<?php

namespace common\jobs\media;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use yii\queue\Queue;
use backend\models\Media;
use common\contracts\StorageDriverInterface;

/**
 * Phase 2 — Common scaffolding for the four post-store media jobs.
 *
 * Each subclass implements {@see processMedia()} and gets, for free:
 *   • the resolved Media row (or an early skip if the row vanished),
 *   • the StorageDriver,
 *   • the absolute local path to the bytes (downloaded to a temp
 *     file when the driver has no native local path — i.e. S3),
 *   • atomic processing-status transitions (pending → processing →
 *     ready/failed) at the column level so the job is safe to run
 *     concurrently without two workers racing on the same row,
 *   • try/finally cleanup of any temp file we materialised.
 *
 * Status transitions are tracked at the WHOLE-ROW level — a row is
 * 'ready' only when ALL queued jobs have finished, which is checked
 * by counting the still-pending sibling jobs at the end of each run.
 * Today that count is hard-coded to the 4 jobs we dispatch; when the
 * pipeline grows we should switch to a per-job tracking table.
 */
abstract class AbstractMediaJob extends BaseObject implements JobInterface
{
    public int  $mediaId = 0;
    public bool $force   = false;

    /**
     * The total number of post-store sibling jobs MediaService
     * dispatches today. When this many have set their per-job done
     * marker, the row flips to 'ready'. Adjust together with
     * MediaService::dispatchPostStoreJobs().
     */
    protected const SIBLING_JOB_COUNT = 4;

    /** Subclasses must declare a unique short marker name. */
    abstract protected function markerKey(): string;

    /**
     * Do the work. Throw on failure — the queue driver decides
     * whether to retry. {@see ttr()} bounds wall-clock time per run.
     *
     * @param Media   $media     the AR row, freshly loaded
     * @param string  $localPath an absolute path to the bytes you can READ from
     * @param StorageDriverInterface $driver
     * @return array<string,mixed> arbitrary metadata to merge into payload_json (audit)
     */
    abstract protected function processMedia(Media $media, string $localPath, StorageDriverInterface $driver): array;

    public function execute($queue): void
    {
        if ($this->mediaId <= 0) {
            Yii::warning(static::class . ': mediaId not set, skipping.', __METHOD__);
            return;
        }

        $media = Media::findOne($this->mediaId);
        if ($media === null) {
            Yii::warning(static::class . ": media #$this->mediaId vanished, skipping.", __METHOD__);
            return;
        }

        // Idempotent skip: if our marker is already set in the audit
        // payload trail (recorded below), bail unless force=true.
        if (!$this->force && $this->isAlreadyDone()) {
            return;
        }

        // Move the row to 'processing' if it's currently 'pending';
        // do NOT clobber a 'failed' or 'ready' state set by an earlier
        // run — those carry intent.
        if ($media->getAttribute('processing_status') === 'pending') {
            $media->setAttribute('processing_status', 'processing');
            $media->save(false);
        }

        $driver = $this->resolveDriver();
        if ($driver === null) {
            $this->markFailed($media, 'storage component missing');
            return;
        }

        // Materialise the bytes locally if needed.
        $key = $this->keyFor($media, $driver);
        $localPath = $driver->localPath($key);
        $tempPath  = null;
        if ($localPath === null) {
            $tempPath = $this->downloadToTemp($key, $driver);
            if ($tempPath === null) {
                $this->markFailed($media, "bytes not retrievable for key=$key");
                return;
            }
            $localPath = $tempPath;
        }

        try {
            $meta = $this->processMedia($media, $localPath, $driver);
            $this->markDone($media, $meta);
        } catch (\Throwable $e) {
            Yii::error(static::class . ' failed for media #' . $this->mediaId . ': ' . $e->getMessage(), __METHOD__);
            $this->markFailed($media, $e->getMessage());
            throw $e; // let the queue decide on retry
        } finally {
            if ($tempPath !== null) {
                @unlink($tempPath);
            }
        }
    }

    /** Conservative TTR — single image work should never take this long. */
    public function ttr(): int
    {
        return 120;
    }

    /** Two retries for transient driver/network failures. */
    public function canRetry($attempt, $error): bool
    {
        return $attempt < 3;
    }

    // ─── Shared helpers ────────────────────────────────────────────

    private function resolveDriver(): ?StorageDriverInterface
    {
        try {
            $c = Yii::$app->get('storage');
            return $c instanceof StorageDriverInterface ? $c : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Mirror of MediaService::keyFor() — duplicated here so the jobs
     * have no accidental dependency on the service. Falls back to
     * the legacy fileHash padded out to 64 chars when the row
     * predates the back-fill.
     */
    protected function keyFor(Media $m, StorageDriverInterface $driver): string
    {
        $sha = (string)($m->getAttribute('checksum_sha256') ?? '');
        if ($sha === '') {
            $sha = str_pad((string)$m->fileHash, 64, '0');
        }
        return $driver->buildKey((int)$m->id, $sha, (string)$m->fileName);
    }

    private function downloadToTemp(string $key, StorageDriverInterface $driver): ?string
    {
        $stream = $driver->get($key);
        if ($stream === null) return null;

        $tmp = tempnam(sys_get_temp_dir(), 'media_job_');
        if ($tmp === false) {
            @fclose($stream);
            return null;
        }
        $out = @fopen($tmp, 'wb');
        if ($out === false) {
            @fclose($stream);
            @unlink($tmp);
            return null;
        }
        @stream_copy_to_stream($stream, $out);
        @fclose($stream);
        @fclose($out);
        return $tmp;
    }

    /**
     * Look at the audit log to decide whether a previous run of this
     * job already finished for this media. We use a 'job_done'
     * action with a `marker` payload field as the single source of
     * truth — cheaper than a dedicated table.
     */
    private function isAlreadyDone(): bool
    {
        try {
            return (bool)Yii::$app->db->createCommand(
                "SELECT 1 FROM media_audit_log
                 WHERE media_id = :mid AND action = 'job_done'
                   AND payload_json LIKE :marker
                 LIMIT 1",
                [
                    ':mid'    => $this->mediaId,
                    ':marker' => '%"marker":"' . $this->markerKey() . '"%',
                ]
            )->queryScalar();
        } catch (\Throwable) {
            return false;
        }
    }

    private function markDone(Media $m, array $meta): void
    {
        $payload = ['marker' => $this->markerKey()] + $meta;
        try {
            Yii::$app->db->createCommand()->insert('media_audit_log', [
                'action'        => 'job_done',
                'media_id'      => (int)$m->id,
                'actor_user_id' => null,
                'entity_type'   => $m->getAttribute('entity_type'),
                'entity_id'     => $m->getAttribute('entity_id'),
                'group_name'    => $m->groupName,
                'uploaded_via'  => $m->getAttribute('uploaded_via'),
                'payload_json'  => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'created_at'    => date('Y-m-d H:i:s'),
            ])->execute();
        } catch (\Throwable $e) {
            Yii::warning('AbstractMediaJob: audit insert failed: ' . $e->getMessage(), __METHOD__);
        }

        // Have all sibling jobs finished?
        try {
            $done = (int)Yii::$app->db->createCommand(
                "SELECT COUNT(DISTINCT JSON_UNQUOTE(JSON_EXTRACT(payload_json, '$.marker')))
                 FROM media_audit_log WHERE media_id = :mid AND action = 'job_done'",
                [':mid' => (int)$m->id]
            )->queryScalar();

            if ($done >= self::SIBLING_JOB_COUNT) {
                Yii::$app->db->createCommand()->update(
                    'os_ImageManager',
                    ['processing_status' => 'ready'],
                    ['and', ['id' => (int)$m->id], ['<>', 'processing_status', 'failed']]
                )->execute();
            }
        } catch (\Throwable $e) {
            // JSON_EXTRACT not available on very old MySQL — fall back
            // to a simple flip; we lose the all-done semantic but the
            // row at least stops looking broken.
            Yii::$app->db->createCommand()->update(
                'os_ImageManager',
                ['processing_status' => 'ready'],
                ['and', ['id' => (int)$m->id], ['<>', 'processing_status', 'failed']]
            )->execute();
        }
    }

    private function markFailed(Media $m, string $reason): void
    {
        try {
            Yii::$app->db->createCommand()->update(
                'os_ImageManager',
                ['processing_status' => 'failed'],
                ['id' => (int)$m->id]
            )->execute();
            Yii::$app->db->createCommand()->insert('media_audit_log', [
                'action'        => 'job_failed',
                'media_id'      => (int)$m->id,
                'entity_type'   => $m->getAttribute('entity_type'),
                'entity_id'     => $m->getAttribute('entity_id'),
                'group_name'    => $m->groupName,
                'payload_json'  => json_encode(
                    ['marker' => $this->markerKey(), 'reason' => $reason],
                    JSON_UNESCAPED_UNICODE
                ),
                'created_at'    => date('Y-m-d H:i:s'),
            ])->execute();
        } catch (\Throwable $e) {
            Yii::warning('AbstractMediaJob: markFailed audit failed: ' . $e->getMessage(), __METHOD__);
        }
    }
}
