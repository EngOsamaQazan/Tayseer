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

        // ─────────────────────────────────────────────────────────────
        //  Unified Media subsystem (Phase 1 of unify-media-system)
        // ─────────────────────────────────────────────────────────────
        // `storage` is the pluggable bytes back-end. LocalDiskDriver
        // mirrors the legacy MediaHelper layout exactly so no URL
        // breaks; swap to S3Driver here when ready (see S3Driver
        // header for the full procedure).
        'storage' => [
            'class' => \common\services\storage\LocalDiskDriver::class,
        ],
        // `media` is THE entry point for every upload/adopt/delete
        // path in the system. Replaces 17 ad-hoc upload paths.
        'media' => [
            'class' => \common\services\media\MediaService::class,
        ],

        // `queue` is OPTIONAL. When absent, MediaService dispatches
        // post-store work synchronously (rows go straight to 'ready'
        // without thumbnails / EXIF / malware-scan). To enable the
        // async pipeline:
        //
        //   1. Run the queue table migration (one-off):
        //        php yii migrate --migrationPath=@vendor/yiisoft/yii2-queue/src/drivers/db/migrations
        //   2. Uncomment the block below.
        //   3. Run a worker:  php yii queue/listen   (under supervisor / systemd)
        //
        // 'queue' => [
        //     'class'   => \yii\queue\db\Queue::class,
        //     'db'      => 'db',
        //     'tableName' => '{{%queue}}',
        //     'channel' => 'media',
        //     'mutex'   => \yii\mutex\MysqlMutex::class,
        //     'as log'  => \yii\queue\LogBehavior::class,
        // ],
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
            'enableRegistration' => false,
            'enableConfirmation' => false,
            'modelMap' => [
                'User' => 'common\models\User',
                'LoginForm' => 'backend\models\LoginForm',
            ],
        ],
    ],
];
