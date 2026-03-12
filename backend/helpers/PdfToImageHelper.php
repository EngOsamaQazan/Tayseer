<?php

namespace backend\helpers;

use Yii;

class PdfToImageHelper
{
    public static function getCacheDir(): string
    {
        $dir = Yii::getAlias('@backend/web/uploads/pdf-cache');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir;
    }

    /**
     * @param string $relativePath e.g. "uploads/investors/reg_xxx.pdf"
     * @return string[] Cached PNG paths (relative to backend/web), ordered by page
     */
    public static function convertAndCache(string $relativePath): array
    {
        $fullPath = Yii::getAlias('@backend/web/' . ltrim($relativePath, '/'));
        if (!file_exists($fullPath) || strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) !== 'pdf') {
            return [];
        }

        $cacheDir = self::getCacheDir();
        $hash = md5($relativePath . filemtime($fullPath));
        $pageCount = self::getPageCount($fullPath);

        if ($pageCount < 1) {
            return [];
        }

        $images = [];
        $allCached = true;
        for ($p = 1; $p <= $pageCount; $p++) {
            $outFile = $cacheDir . '/' . $hash . '-p' . $p . '.png';
            if (!file_exists($outFile)) {
                $allCached = false;
                break;
            }
            $images[] = 'uploads/pdf-cache/' . $hash . '-p' . $p . '.png';
        }

        if ($allCached) {
            return $images;
        }

        $pdftoppm = self::getPdftoppmPath();
        $images = [];
        for ($p = 1; $p <= $pageCount; $p++) {
            $outFile = $cacheDir . '/' . $hash . '-p' . $p . '.png';
            if (!file_exists($outFile)) {
                $tmpPrefix = $cacheDir . '/' . $hash . '-tmp';
                $cmd = sprintf(
                    '%s -png -r 200 -f %d -l %d -singlefile %s %s',
                    escapeshellarg($pdftoppm),
                    $p, $p,
                    escapeshellarg($fullPath),
                    escapeshellarg($tmpPrefix)
                );
                exec($cmd, $output, $ret);
                $tmpFile = $tmpPrefix . '.png';
                if (file_exists($tmpFile)) {
                    rename($tmpFile, $outFile);
                }
            }
            if (file_exists($outFile)) {
                $images[] = 'uploads/pdf-cache/' . $hash . '-p' . $p . '.png';
            }
        }

        return $images;
    }

    /**
     * @return string[] absolute PNG paths
     */
    public static function getCachedImages(string $relativePath): array
    {
        $fullPath = Yii::getAlias('@backend/web/' . ltrim($relativePath, '/'));
        if (!file_exists($fullPath)) {
            return [];
        }

        $cacheDir = self::getCacheDir();
        $hash = md5($relativePath . filemtime($fullPath));
        $pageCount = self::getPageCount($fullPath);
        $images = [];
        for ($p = 1; $p <= $pageCount; $p++) {
            $rel = 'uploads/pdf-cache/' . $hash . '-p' . $p . '.png';
            $abs = Yii::getAlias('@backend/web/' . $rel);
            if (file_exists($abs)) {
                $images[] = $rel;
            }
        }
        return $images;
    }

    public static function getPageCount(string $fullPath): int
    {
        $pdfinfo = self::getPdfinfoPath();
        exec(escapeshellarg($pdfinfo) . ' ' . escapeshellarg($fullPath), $output, $ret);
        foreach ($output as $line) {
            if (preg_match('/^Pages:\s+(\d+)/', $line, $m)) {
                return (int)$m[1];
            }
        }
        return 0;
    }

    private static function getPdftoppmPath(): string
    {
        $local = Yii::getAlias('@backend') . '/bin/poppler/pdftoppm.exe';
        if (file_exists($local)) {
            return $local;
        }
        return 'pdftoppm';
    }

    private static function getPdfinfoPath(): string
    {
        $local = Yii::getAlias('@backend') . '/bin/poppler/pdfinfo.exe';
        if (file_exists($local)) {
            return $local;
        }
        return 'pdfinfo';
    }
}
