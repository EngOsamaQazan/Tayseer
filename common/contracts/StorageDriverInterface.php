<?php

namespace common\contracts;

/**
 * Phase 1 / M1.1 — Pluggable storage backend for the unified MediaService.
 *
 * The interface is deliberately tiny and stream-oriented: all media
 * operations in Tayseer can be expressed as "put bytes at key", "fetch
 * bytes at key", "does this key exist", "delete this key", "build a
 * publicly servable URL for this key". Anything richer (signed URLs,
 * multipart uploads, range reads) is intentionally NOT in this contract
 * because the LocalDiskDriver — which we ship today — does not need it,
 * and forcing it through the contract would punish the local case for
 * the sake of a future cloud case.
 *
 * Keys are opaque to MediaService; they are produced by the driver
 * itself via {@see self::buildKey()} so that swapping LocalDiskDriver
 * for S3Driver later does not require any caller to know about the
 * physical layout.
 *
 * Drivers MUST be deterministic: calling buildKey() twice with the
 * same inputs MUST yield the same key. This is what lets MediaService
 * reason about idempotency / dedup without consulting the storage.
 */
interface StorageDriverInterface
{
    /**
     * Persist the contents at $sourcePath under the storage key $key.
     *
     * `$sourcePath` is the absolute filesystem path of the temporary
     * file (typically `UploadedFile::$tempName` or a tmp file written
     * from base64). Drivers SHOULD prefer `move_uploaded_file()` /
     * `rename()` over `copy()` when $sourcePath is on the same disk,
     * but MUST tolerate the cross-device case.
     *
     * Returns true on success, false on a recoverable failure (caller
     * may retry). For unrecoverable conditions (permission denied,
     * disk full, missing source) the driver MAY throw — MediaService
     * wraps the call and turns it into a structured upload error.
     *
     * Implementations MUST be safe against partially-written files:
     * write to a temp name in the destination, fsync, then rename.
     */
    public function put(string $key, string $sourcePath): bool;

    /**
     * Stream-read the bytes for $key. The returned resource MUST be
     * seekable on the local driver; cloud drivers MAY return a
     * non-seekable stream (callers that need seek MUST copy to a temp
     * file first, see MediaService::copyToTemp()).
     *
     * Returns NULL when the key does not exist; throws on transport
     * errors (network, S3 5xx, etc.) so the caller can distinguish
     * "missing" from "broken".
     *
     * @return resource|null
     */
    public function get(string $key);

    /**
     * Cheap existence probe. MUST NOT download bytes. On the cloud
     * drivers this is a HEAD request; on disk this is `is_file`.
     */
    public function exists(string $key): bool;

    /**
     * Hard-delete. Returns true if the key was present and removed,
     * false if it was already absent. Soft-delete is a MediaService
     * concern (it sets `deleted_at` on the row); drivers only know
     * about the binary.
     */
    public function delete(string $key): bool;

    /**
     * Web-servable URL for $key. For LocalDiskDriver this is a path
     * under the backend's web root; for S3Driver it is a CDN or
     * signed-URL string.
     *
     * MediaService NEVER stores this URL — it is rebuilt on every
     * read so a future S3 swap does not require a data migration.
     */
    public function url(string $key): string;

    /**
     * Build the canonical key for a media row. Called by MediaService
     * right after the row is inserted (so $mediaId is known) but
     * BEFORE put(). Concentrating the layout here is what lets us
     * change "/images/imagemanager/{id}_{hash}.{ext}" to
     * "/year/month/{hash}.{ext}" later by editing one method.
     */
    public function buildKey(int $mediaId, string $checksumSha256, string $originalFileName): string;

    /**
     * Absolute filesystem path corresponding to $key, when one
     * exists. Returns NULL for cloud drivers (S3 has no local path).
     *
     * Image-processing jobs (thumbnail, EXIF, OCR) ask for this
     * first; if it returns NULL they fall back to streaming the
     * bytes via {@see get()} into a temp file.
     */
    public function localPath(string $key): ?string;
}
