<?php

namespace backend\assets;

use noam148\imagemanager\assets\ImageManagerModuleAsset as BaseImageManagerModuleAsset;
use yii\web\AssetBundle;

/**
 * ImageManagerModuleAsset.
 */
class ImageManagerModuleAsset extends BaseImageManagerModuleAsset
{
    // public $sourcePath = '@backend/web';
    public $css = [
      'css/cropper.min.css', 
      'css/imagemanager.module.css',
      ];
      public $js = [
          'js/cropper.min.js',
      'js/script.imagemanager.module.js',
      ];
      public $depends = [
      'yii\web\JqueryAsset',
          'yii\bootstrap\BootstrapPluginAsset',
      ];
}