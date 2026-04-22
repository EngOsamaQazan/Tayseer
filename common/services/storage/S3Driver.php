<?php

namespace common\services\storage;

use yii\base\Component;
use common\contracts\StorageDriverInterface;

/**
 * Phase 1 / M1.1 — S3-compatible storage driver (STUB).
 *
 * Intentionally not implemented yet. The interface is shipped today so
 * the rest of the codebase can be written against
 * {@see StorageDriverInterface} without leaking any local-disk
 * assumptions; the actual S3/MinIO wiring will land in Phase 7 once
 * AWS SDK is added to composer.
 *
 * To activate later:
 *   1. composer require aws/aws-sdk-php
 *   2. Replace each `throw` below with the corresponding S3Client call.
 *   3. In `common/config/main.php`, swap:
 *        'storage' => ['class' => LocalDiskDriver::class],
 *      for:
 *        'storage' => [
 *           'class'   => S3Driver::class,
 *           'bucket'  => $params['s3.bucket'],
 *           'region'  => $params['s3.region'],
 *           'cdnBase' => $params['s3.cdnBase'],
 *        ],
 *   4. Run a one-shot `php yii media-backfill/sync-to-s3` (Phase 7).
 *
 * Until activation, instantiating this class throws on first use so a
 * fat-fingered config swap fails loudly instead of silently dropping
 * uploads.
 */
class S3Driver extends Component implements StorageDriverInterface
{
    public string  $bucket  = '';
    public string  $region  = '';
    public ?string $cdnBase = null;

    public function buildKey(int $mediaId, string $checksumSha256, string $originalFileName): string
    {
        $this->stub(__FUNCTION__);
    }

    public function put(string $key, string $sourcePath): bool
    {
        $this->stub(__FUNCTION__);
    }

    public function get(string $key)
    {
        $this->stub(__FUNCTION__);
    }

    public function exists(string $key): bool
    {
        $this->stub(__FUNCTION__);
    }

    public function delete(string $key): bool
    {
        $this->stub(__FUNCTION__);
    }

    public function url(string $key): string
    {
        $this->stub(__FUNCTION__);
    }

    public function localPath(string $key): ?string
    {
        return null;
    }

    private function stub(string $method): never
    {
        throw new \BadMethodCallException(
            "S3Driver::$method — driver is a stub. " .
            "Add aws/aws-sdk-php to composer and implement before swapping in common/config/main.php."
        );
    }
}
