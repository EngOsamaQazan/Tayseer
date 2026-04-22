<?php

namespace common\jobs\media;

use Yii;
use backend\models\Media;
use common\contracts\StorageDriverInterface;

/**
 * Phase 2 — Drop-in size reduction for image media.
 *
 * Today's strategy is conservative: produce a sibling WebP next to
 * the original (at the same storage key but with a `.webp` suffix
 * appended) so we keep the original bytes intact for forensic /
 * legal reasons (national IDs, contracts) but can serve a 30-60 %
 * smaller variant to bandwidth-constrained clients.
 *
 * The unified MediaUploader (Phase 6) will pick the WebP variant
 * automatically when the browser advertises Accept: image/webp.
 *
 * Non-image and non-JPEG/PNG inputs are skipped (no point WebP-ing
 * an already-WebP, no point re-encoding a PDF).
 */
class OptimizeImageJob extends AbstractMediaJob
{
    /** Lossy quality for the derivative. 75 is the visual sweet spot. */
    public int $webpQuality = 75;

    protected function markerKey(): string
    {
        return 'optimize';
    }

    protected function processMedia(Media $media, string $localPath, StorageDriverInterface $driver): array
    {
        $mime = (string)($media->getAttribute('mime_type') ?? '');
        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
            return ['skipped' => 'not_optimisable', 'mime' => $mime];
        }

        $sourceBytes = @filesize($localPath);
        if ($sourceBytes === false || $sourceBytes < 30 * 1024) {
            // Files under 30 KB rarely benefit — the WebP container
            // overhead can make the result LARGER. Skip.
            return ['skipped' => 'too_small', 'size' => $sourceBytes ?: 0];
        }

        // Derive a sibling path next to the canonical bytes when the
        // driver exposes a local path. For S3 we'd push back via
        // driver->put(); not implemented in the stub.
        if ($driver->localPath('') === null) {
            return ['skipped' => 'driver_has_no_local_path'];
        }
        $destPath = $localPath . '.webp';

        $produced = $this->encodeWebp($localPath, $destPath, $mime, $this->webpQuality);
        if (!$produced) {
            return ['skipped' => 'encoder_unavailable'];
        }

        $newBytes = @filesize($destPath) ?: 0;
        if ($newBytes >= $sourceBytes) {
            // The WebP came out bigger — drop it; the original wins.
            @unlink($destPath);
            return ['skipped' => 'no_savings', 'webp_size' => $newBytes, 'orig_size' => $sourceBytes];
        }

        $savedPct = $sourceBytes > 0 ? round((1 - $newBytes / $sourceBytes) * 100, 1) : 0;
        return [
            'orig_size' => $sourceBytes,
            'webp_size' => $newBytes,
            'saved_pct' => $savedPct,
        ];
    }

    private function encodeWebp(string $src, string $dest, string $mime, int $quality): bool
    {
        if (extension_loaded('imagick')) {
            try {
                $img = new \Imagick($src);
                $img->setImageFormat('webp');
                $img->setImageCompressionQuality($quality);
                $img->writeImage($dest);
                $img->clear();
                return true;
            } catch (\Throwable $e) {
                Yii::warning('OptimizeImageJob: Imagick failed: ' . $e->getMessage(), __METHOD__);
            }
        }

        if (extension_loaded('gd') && function_exists('imagewebp')) {
            $srcImg = match ($mime) {
                'image/jpeg' => @imagecreatefromjpeg($src),
                'image/png'  => @imagecreatefrompng($src),
                default      => null,
            };
            if ($srcImg === null || $srcImg === false) return false;

            $ok = @imagewebp($srcImg, $dest, $quality);
            imagedestroy($srcImg);
            return (bool)$ok;
        }

        return false;
    }
}
