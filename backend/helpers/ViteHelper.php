<?php

namespace backend\helpers;

use Yii;
use yii\web\View;

/**
 * Reads Vite's build manifest and generates asset tags.
 * Usage in layout: <?= \backend\helpers\ViteHelper::tags() ?>
 */
class ViteHelper
{
    private static ?array $_manifest = null;

    public static function tags(string $entry = 'resources/js/app.js'): string
    {
        $manifest = self::getManifest();

        if ($manifest === null || !isset($manifest[$entry])) {
            return "<!-- Vite: manifest not found. Run: cd backend && npm run build -->\n";
        }

        $data = $manifest[$entry];
        $baseUrl = Yii::$app->request->baseUrl . '/dist';
        $html = '';

        if (isset($data['css'])) {
            foreach ($data['css'] as $css) {
                $html .= '<link rel="stylesheet" href="' . $baseUrl . '/' . $css . '">' . "\n";
            }
        }

        if (isset($data['file'])) {
            $html .= '<script type="module" src="' . $baseUrl . '/' . $data['file'] . '"></script>' . "\n";
        }

        return $html;
    }

    private static function getManifest(): ?array
    {
        if (self::$_manifest === null) {
            $path = Yii::getAlias('@webroot/dist/.vite/manifest.json');
            if (file_exists($path)) {
                self::$_manifest = json_decode(file_get_contents($path), true) ?: [];
            }
        }
        return self::$_manifest;
    }
}
