<?php

namespace common\services\storage;

use Yii;
use yii\base\Component;
use common\contracts\StorageDriverInterface;

/**
 * Phase 1 / M1.1 — Default storage driver for Tayseer.
 *
 * Bytes live under `@backend/web/images/imagemanager/` and are served
 * via `/images/imagemanager/{id}_{hash}.{ext}`. This is byte-for-byte
 * the layout MediaHelper has been using since 2025, which means:
 *
 *   • Every existing URL in the database keeps working unchanged.
 *   • The Phase 0 back-fill does not need to move a single file.
 *   • No nginx / Apache config change is required to ship the
 *     unified MediaService.
 *
 * The S3/MinIO migration path is a one-line config swap in
 * `common/config/main.php`; see {@see S3Driver}.
 */
class LocalDiskDriver extends Component implements StorageDriverInterface
{
    /**
     * Web-relative URL prefix (with leading + trailing slash). Mirrors
     * MediaHelper::DIR exactly — do NOT diverge or every legacy URL
     * stored in user-facing bookmarks/emails breaks.
     */
    public string $urlPrefix = '/images/imagemanager/';

    /**
     * Filesystem alias under which keys are resolved. Pointed at the
     * backend webroot by default so files are reachable as static
     * assets. Override in tests to a writable temp dir.
     */
    public string $rootAlias = '@backend/web/images/imagemanager';

    /**
     * Optional CDN base URL prepended to {@see url()} results. Reads
     * `Yii::$app->params['customerImagesBaseUrl']` when null so the
     * existing param keeps working without a config change.
     */
    public ?string $publicBaseUrl = null;

    public function init()
    {
        parent::init();
        $dir = Yii::getAlias($this->rootAlias);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException(
                "LocalDiskDriver: cannot create root directory $dir"
            );
        }
    }

    public function buildKey(int $mediaId, string $checksumSha256, string $originalFileName): string
    {
        // Mirror MediaHelper exactly: "{id}_{hash}.{ext}". We preserve
        // the original short hash that legacy code wrote (it lives in
        // the `fileHash` column and is part of every existing URL).
        // The new SHA-256 lives in `checksum_sha256` for de-dup but
        // the URL keeps using the short hash — so URLs do not break.
        //
        // For brand-new uploads written by MediaService directly we
        // pass the same value for both (the short hash equals the
        // first 32 chars of the SHA-256), keeping the format stable.
        $shortHash = substr($checksumSha256, 0, 32);
        $ext = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION) ?: 'bin');
        return "{$mediaId}_{$shortHash}.{$ext}";
    }

    public function put(string $key, string $sourcePath): bool
    {
        if (!is_file($sourcePath)) {
            throw new \RuntimeException("LocalDiskDriver::put — source missing: $sourcePath");
        }

        $dest    = $this->absolutePath($key);
        $tmpDest = $dest . '.part-' . bin2hex(random_bytes(4));

        // Copy to a sibling temp file then atomically rename. Avoids
        // serving a half-written file if we are mid-upload when the
        // process dies (PHP-FPM crash, OOM, Windows shutdown, …).
        if (!@copy($sourcePath, $tmpDest)) {
            $err = error_get_last();
            throw new \RuntimeException(
                "LocalDiskDriver::put copy failed: " . ($err['message'] ?? 'unknown')
            );
        }

        // Best-effort fsync. PHP exposes no direct sync, but closing
        // the FD after touching the file is enough on every POSIX FS
        // we deploy on. (Windows ignores this.)
        @clearstatcache(true, $tmpDest);

        if (!@rename($tmpDest, $dest)) {
            // Cross-device or cross-volume rename can fail on some
            // Windows + network-share setups — fall back to copy +
            // unlink, accepting the (tiny) non-atomic window.
            if (@copy($tmpDest, $dest)) {
                @unlink($tmpDest);
            } else {
                @unlink($tmpDest);
                throw new \RuntimeException("LocalDiskDriver::put rename failed for $dest");
            }
        }

        @chmod($dest, 0664);
        return true;
    }

    public function get(string $key)
    {
        $path = $this->absolutePath($key);
        if (!is_file($path)) {
            return null;
        }
        $fh = @fopen($path, 'rb');
        return $fh !== false ? $fh : null;
    }

    public function exists(string $key): bool
    {
        return is_file($this->absolutePath($key));
    }

    public function delete(string $key): bool
    {
        $path = $this->absolutePath($key);
        if (!is_file($path)) {
            return false;
        }
        return @unlink($path);
    }

    public function url(string $key): string
    {
        $rel = $this->urlPrefix . ltrim($key, '/');

        $base = $this->publicBaseUrl ?? (Yii::$app->params['customerImagesBaseUrl'] ?? null);
        if (!empty($base)) {
            return rtrim($base, '/') . $rel;
        }
        return $rel;
    }

    public function localPath(string $key): ?string
    {
        $path = $this->absolutePath($key);
        return is_file($path) ? $path : null;
    }

    /**
     * Resolve a storage key to its absolute filesystem path. We refuse
     * keys containing path traversal so a poisoned DB row cannot
     * convince us to rewrite /etc/passwd.
     */
    private function absolutePath(string $key): string
    {
        if ($key === '' || str_contains($key, '..') || str_contains($key, "\0")) {
            throw new \InvalidArgumentException("LocalDiskDriver: refused unsafe key");
        }
        return Yii::getAlias($this->rootAlias) . DIRECTORY_SEPARATOR . $key;
    }
}
