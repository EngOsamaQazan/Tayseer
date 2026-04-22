<?php

namespace common\jobs\media;

use Yii;
use backend\models\Media;
use common\contracts\StorageDriverInterface;

/**
 * Phase 2 — Pull EXIF / dimensions metadata from the bytes.
 *
 * For images we re-read width/height (the synchronous probe in
 * MediaService::probeDimensions() already wrote them, but cheap to
 * verify) and extract EXIF tags when present (camera, GPS, capture
 * timestamp). For PDFs we record the page count.
 *
 * Anything we extract gets folded into the audit payload — we
 * deliberately do NOT add columns to os_ImageManager for every EXIF
 * field; the audit log is the searchable record and analytics jobs
 * (Phase 7) project it back into a denormalised table.
 */
class ExtractMetadataJob extends AbstractMediaJob
{
    protected function markerKey(): string
    {
        return 'metadata';
    }

    protected function processMedia(Media $media, string $localPath, StorageDriverInterface $driver): array
    {
        $mime = (string)($media->getAttribute('mime_type') ?? '');
        $meta = ['mime' => $mime];

        if (str_starts_with($mime, 'image/')) {
            $info = @getimagesize($localPath);
            if (is_array($info)) {
                $meta['width']  = (int)($info[0] ?? 0);
                $meta['height'] = (int)($info[1] ?? 0);

                // Backfill any width/height left null by the synchronous
                // probe (e.g. when the synchronous probe ran on a
                // base64 webcam capture that GD couldn't open in mid-flight).
                if (!$media->getAttribute('width') && $meta['width']) {
                    $media->setAttribute('width', $meta['width']);
                }
                if (!$media->getAttribute('height') && $meta['height']) {
                    $media->setAttribute('height', $meta['height']);
                }
                if ($media->getDirtyAttributes()) {
                    $media->save(false);
                }
            }

            // EXIF — only meaningful for JPEG/TIFF.
            if (function_exists('exif_read_data') && in_array($mime, ['image/jpeg', 'image/tiff'], true)) {
                $exif = @exif_read_data($localPath, 'IFD0,EXIF,GPS', true);
                if (is_array($exif)) {
                    $meta['exif'] = [
                        'make'        => $exif['IFD0']['Make']        ?? null,
                        'model'       => $exif['IFD0']['Model']       ?? null,
                        'orientation' => $exif['IFD0']['Orientation'] ?? null,
                        'datetime'    => $exif['EXIF']['DateTimeOriginal']
                                        ?? $exif['IFD0']['DateTime']
                                        ?? null,
                        'has_gps'     => isset($exif['GPS']) && !empty($exif['GPS']),
                    ];
                }
            }
        } elseif ($mime === 'application/pdf') {
            $meta['page_count'] = $this->countPdfPages($localPath);
        }

        return $meta;
    }

    /**
     * Quick & dirty PDF page counter that does not require a full PDF
     * parser: counts /Page objects in the raw bytes. Wrong for some
     * exotic PDFs but good enough for the analytics use case.
     * Falls back to NULL when the file is too big to scan cheaply.
     */
    private function countPdfPages(string $path): ?int
    {
        $size = @filesize($path);
        if ($size === false || $size > 50 * 1024 * 1024) {
            return null;
        }
        $bytes = @file_get_contents($path);
        if ($bytes === false) return null;
        if (preg_match_all('#/Type\s*/Page[^s]#', $bytes, $m)) {
            return count($m[0]);
        }
        return null;
    }
}
