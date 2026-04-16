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
