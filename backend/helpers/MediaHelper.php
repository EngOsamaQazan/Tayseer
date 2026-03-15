<?php

namespace backend\helpers;

use Yii;

/**
 * Centralized image URL/path builder for os_ImageManager files.
 * Single source of truth — replaces 15+ scattered path constructions.
 */
class MediaHelper
{
    private const DIR = '/images/imagemanager/';

    /**
     * Build web-relative URL: /images/imagemanager/{id}_{hash}.{ext}
     */
    public static function url(int $id, string $fileHash, string $fileName): string
    {
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        return self::DIR . $id . '_' . $fileHash . '.' . $ext;
    }

    /**
     * Build absolute URL using customerImagesBaseUrl param when available.
     * Falls back to relative URL if param is not set.
     */
    public static function absoluteUrl(int $id, string $fileHash, string $fileName): string
    {
        $base = Yii::$app->params['customerImagesBaseUrl'] ?? null;
        $rel  = self::url($id, $fileHash, $fileName);

        if (!empty($base)) {
            return rtrim($base, '/') . $rel;
        }

        return $rel;
    }

    /**
     * Smart Media thumbnail URL.
     */
    public static function thumbUrl(int $id, string $fileHash, string $fileName): string
    {
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        return '/uploads/customers/documents/thumbs/thumb_' . $id . '_' . $fileHash . '.' . $ext;
    }

    /**
     * Absolute filesystem path to the image file.
     */
    public static function filePath(int $id, string $fileHash, string $fileName): string
    {
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        return Yii::getAlias('@backend/web/images/imagemanager/')
            . $id . '_' . $fileHash . '.' . $ext;
    }

    /**
     * Check whether the physical file exists on disk.
     */
    public static function exists(int $id, string $fileHash, string $fileName): bool
    {
        return is_file(self::filePath($id, $fileHash, $fileName));
    }

    /**
     * Inline helper for standalone scripts (fahras) that lack Yii autoloading.
     * Returns a closure with the same signature as url().
     */
    public static function standaloneUrlBuilder(string $baseUrl): \Closure
    {
        return function (array $row) use ($baseUrl): string {
            $ext = pathinfo($row['fileName'] ?? '', PATHINFO_EXTENSION);
            return $baseUrl . $row['id'] . '_' . ($row['fileHash'] ?? '') . '.' . $ext;
        };
    }
}
