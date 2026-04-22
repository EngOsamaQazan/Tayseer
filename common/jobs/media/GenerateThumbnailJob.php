<?php

namespace common\jobs\media;

use Yii;
use backend\models\Media;
use common\contracts\StorageDriverInterface;

/**
 * Phase 2 — Build the small/medium thumbnails for an image media row.
 *
 * Produces TWO derivatives, both saved next to the legacy thumb path
 * that Smart Media has used since 2025 (so /uploads/.../thumb_…
 * URLs keep pointing at something):
 *   • thumb-200  (square cover, 200×200, WebP)  — list/grid views
 *   • thumb-800  (max-edge 800px,    WebP)      — modal previews
 *
 * For non-images (PDF, doc, …) the job is a no-op — we mark it done
 * so the row can flip to 'ready', but no derivatives are produced.
 * A future PdfThumbnailJob can specialise that path.
 *
 * GD is used when available (ships with PHP); Imagick is preferred
 * when present (better quality, handles CMYK JPEGs). The job degrades
 * gracefully when neither library is installed (logs a warning and
 * marks done with a `skipped` flag so the row still becomes ready).
 */
class GenerateThumbnailJob extends AbstractMediaJob
{
    protected function markerKey(): string
    {
        return 'thumbnail';
    }

    protected function processMedia(Media $media, string $localPath, StorageDriverInterface $driver): array
    {
        $mime = (string)($media->getAttribute('mime_type') ?? '');
        if (!str_starts_with($mime, 'image/')) {
            return ['skipped' => 'not_an_image', 'mime' => $mime];
        }

        $thumbDir = Yii::getAlias('@backend/web/uploads/customers/documents/thumbs');
        if (!is_dir($thumbDir) && !@mkdir($thumbDir, 0775, true) && !is_dir($thumbDir)) {
            return ['skipped' => 'thumb_dir_unwritable', 'dir' => $thumbDir];
        }

        $base   = 'thumb_' . (int)$media->id . '_' . (string)$media->fileHash;
        $small  = $thumbDir . DIRECTORY_SEPARATOR . $base . '.webp';
        $medium = $thumbDir . DIRECTORY_SEPARATOR . $base . '_md.webp';

        // We deliberately overwrite — the storage key is content-
        // addressed (the `fileHash` part comes from the SHA-256), so
        // overwriting only ever happens after an explicit replace().
        // Phase 7 / M7.2 — produce the two derivatives the plan asks
        // for: a 200×200 square-cover thumbnail (lists/grids) and a
        // long-edge-800px medium variant (modal previews / lightbox).
        // The original file remains untouched and is what `full` URLs
        // resolve to.
        $producedSmall  = $this->generate($localPath, $small,  200, 200, true);
        $producedMedium = $this->generate($localPath, $medium, 800, 800, false);

        if (!$producedSmall && !$producedMedium) {
            return ['skipped' => 'no_image_library_available'];
        }

        $out = [];
        if ($producedSmall) {
            $out['thumb_200'] = '/uploads/customers/documents/thumbs/' . basename($small);
            $out['bytes_200'] = @filesize($small) ?: 0;
        }
        if ($producedMedium) {
            $out['medium_800']  = '/uploads/customers/documents/thumbs/' . basename($medium);
            $out['bytes_800']   = @filesize($medium) ?: 0;
        }
        return $out;
    }

    /**
     * Render a thumbnail of $src into $dest using whichever image
     * library is available. Returns false when no library is usable.
     */
    private function generate(string $src, string $dest, int $w, int $h, bool $crop): bool
    {
        if (extension_loaded('imagick')) {
            try {
                $img = new \Imagick($src);
                $img->setImageFormat('webp');
                if ($crop) {
                    $img->cropThumbnailImage($w, $h);
                } else {
                    $img->thumbnailImage($w, $h, true, false);
                }
                $img->setImageCompressionQuality(82);
                $img->writeImage($dest);
                $img->clear();
                return true;
            } catch (\Throwable $e) {
                Yii::warning('GenerateThumbnailJob: Imagick failed, falling through: ' . $e->getMessage(), __METHOD__);
            }
        }

        if (extension_loaded('gd')) {
            $info = @getimagesize($src);
            if (!is_array($info)) return false;

            [$srcW, $srcH, $type] = $info;
            $srcImg = match ($type) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
                IMAGETYPE_PNG  => @imagecreatefrompng($src),
                IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : null,
                IMAGETYPE_GIF  => @imagecreatefromgif($src),
                default        => null,
            };
            if ($srcImg === null || $srcImg === false) return false;

            // Square-cover crop logic.
            if ($crop) {
                $side = min($srcW, $srcH);
                $sx = (int)(($srcW - $side) / 2);
                $sy = (int)(($srcH - $side) / 2);
                $dst = imagecreatetruecolor($w, $h);
                imagecopyresampled($dst, $srcImg, 0, 0, $sx, $sy, $w, $h, $side, $side);
            } else {
                $ratio = min($w / $srcW, $h / $srcH);
                $dw = max(1, (int)($srcW * $ratio));
                $dh = max(1, (int)($srcH * $ratio));
                $dst = imagecreatetruecolor($dw, $dh);
                imagecopyresampled($dst, $srcImg, 0, 0, 0, 0, $dw, $dh, $srcW, $srcH);
            }

            $ok = function_exists('imagewebp')
                ? @imagewebp($dst, $dest, 82)
                : @imagejpeg($dst, $dest, 82);

            imagedestroy($srcImg);
            imagedestroy($dst);
            return (bool)$ok;
        }

        return false;
    }
}
