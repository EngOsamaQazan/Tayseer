<?php

namespace common\services\media;

use backend\models\Media;

/**
 * Phase 1 / M1.3 — Immutable return value of MediaService write paths.
 *
 * Why a DTO and not just `return $media` (the AR row)?
 *   • Callers (controllers, jobs, REST) need a *stable* shape that
 *     does not change when we add columns to `os_ImageManager`. The
 *     AR object exposes every column directly, which has historically
 *     caused JSON responses to silently grow new fields.
 *   • The result object carries derived fields (urls, thumbnail urls,
 *     AI classification) that the AR row does not know about.
 *   • A DTO is trivially serialisable for API responses and for
 *     enqueuing onto the job queue.
 */
final class MediaResult
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $url,
        public readonly string  $thumbUrl,
        public readonly string  $fileName,
        public readonly string  $groupName,
        public readonly string  $mimeType,
        public readonly int     $fileSize,
        public readonly string  $checksumSha256,
        public readonly string  $processingStatus,
        public readonly ?string $entityType = null,
        public readonly ?int    $entityId   = null,
        /** @var array<string,mixed>|null */
        public readonly ?array  $aiClassification = null,
    ) {}

    /**
     * Build the result from a freshly-persisted Media row plus the
     * driver-resolved URLs and (optional) Vision classification.
     *
     * @param array<string,mixed>|null $aiClassification
     */
    public static function fromMedia(
        Media $m,
        string $url,
        string $thumbUrl,
        ?array $aiClassification = null
    ): self {
        return new self(
            id:              (int)$m->id,
            url:             $url,
            thumbUrl:        $thumbUrl,
            fileName:        (string)$m->fileName,
            groupName:       (string)($m->groupName ?? ''),
            mimeType:        (string)($m->getAttribute('mime_type') ?? 'application/octet-stream'),
            fileSize:        (int)($m->getAttribute('file_size') ?? 0),
            checksumSha256:  (string)($m->getAttribute('checksum_sha256') ?? ''),
            processingStatus:(string)($m->getAttribute('processing_status') ?? 'ready'),
            entityType:      $m->getAttribute('entity_type') !== null
                                ? (string)$m->getAttribute('entity_type') : null,
            entityId:        $m->getAttribute('entity_id') !== null
                                ? (int)$m->getAttribute('entity_id') : null,
            aiClassification:$aiClassification,
        );
    }

    /** JSON-friendly serialisation for REST + queue payloads. */
    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'url'               => $this->url,
            'thumb_url'         => $this->thumbUrl,
            'file_name'         => $this->fileName,
            'group_name'        => $this->groupName,
            'mime_type'         => $this->mimeType,
            'file_size'         => $this->fileSize,
            'checksum_sha256'   => $this->checksumSha256,
            'processing_status' => $this->processingStatus,
            'entity_type'       => $this->entityType,
            'entity_id'         => $this->entityId,
            'ai_classification' => $this->aiClassification,
        ];
    }
}
