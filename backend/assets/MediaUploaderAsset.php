<?php

namespace backend\assets;

use yii\web\AssetBundle;
use yii\web\YiiAsset;

/**
 * Phase 6 / M6.1 — Asset bundle for the unified MediaUploader bundle.
 *
 * Register this from any view that wants the unified upload widgets:
 *
 *   use backend\assets\MediaUploaderAsset;
 *   MediaUploaderAsset::register($this);
 *
 * Then drop `data-media-uploader` / `data-media-webcam` /
 * `data-media-scanner` markers in the markup — auto-attach handles
 * the rest. See backend/web/js/media-uploader/core.js for usage.
 *
 * The bundle intentionally has NO Bootstrap dependency: the uploader
 * is plain DOM so it works in Smart Onboarding (Tailwind-flavoured)
 * and the legacy AdminLTE forms alike.
 */
class MediaUploaderAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl  = '@web';

    public $js = [
        'js/media-uploader/core.js',
        'js/media-uploader/progress.js',
        'js/media-uploader/webcam.js',
        'js/media-uploader/scanner.js',
    ];

    public $depends = [
        YiiAsset::class, // exposes csrf-param / csrf-token meta tags
    ];
}
