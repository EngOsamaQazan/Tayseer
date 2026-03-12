<?php

use backend\models\Employee;

return [
    'language' => 'ar-JO',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
        '@sibilino/yii2/openlayers' => '@vendor/sibilino/yii2-openlayers/widget',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'formatter' => [
            'locale' => 'ar-u-nu-latn',
            'decimalSeparator' => '.',
            'thousandSeparator' => ',',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'imagemanager' => [
            'class' => 'noam148\imagemanager\components\ImageManagerGetPath',
            //set media path (outside the web folder is possible)
            'mediaPath' => '../../backend/web/images/imagemanager',
            //path relative web folder. In case of multiple environments (frontend, backend) add more paths 
            'cachePath' => ['assets/images', '../../frontend/web/assets/images'],
            //use filename (seo friendly) for resized images else use a hash
            'useFilename' => true,
            //show full url (for example in case of a API)
            'absoluteUrl' => false,
            'databaseComponent' => 'db' // The used database component by the image manager, this defaults to the Yii::$app->db component
        ],
        'avatar' => function () {
            $userId = \Yii::$app->user->isGuest ? null : \Yii::$app->user->id;
            if ($userId === null) {
                return null;
            }
            $cacheKey = "user_avatar_{$userId}";
            return \Yii::$app->cache->getOrSet($cacheKey, function () use ($userId) {
                $data = Employee::findOne($userId);
                return (!empty($data->avatar)) ? $data->avatar : null;
            }, 3600);
        },
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@common/messages',
                ]
            ],
        ],
    ],
    'controllerMap' => [
        'migration' => [
            'class' => 'bizley\migration\controllers\MigrationController',
        ],
    ],
    'modules' => [
        'user' => [
            'class' => 'dektrium\user\Module',
            'admins' => ['zaxx44a7@gmail.com'],
            'modelMap' => [
                'User' => 'common\models\User',
                'LoginForm' => 'backend\models\LoginForm',
            ],
        ],
    ],
];
