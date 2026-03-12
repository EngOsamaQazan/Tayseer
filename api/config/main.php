<?php

use yii\helpers\ArrayHelper;

$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/params.php')
);

return [
    'id' => 'app-api',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'modules' => [
        'v1' => [
            'basePath' => '@app/modules/v1',
            'class' => 'api\modules\v1\Module'
        ]
    ],
    'components' => [
        'i18n' => [
            'translations' => [
                'api*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'forceTranslation' => true,
                    'basePath' => '@api/messages',
                    'sourceLanguage' => 'en',
                    'fileMap' => [
                        'api/response/ar-JO' => 'messages.php',
                        'api/response/en-US' => 'messages.php',
                        'api/translations/ar-JO' => 'translations.php',
                        'api/translations/en-US' => 'translations.php',
                    ],
                ],
            ],
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'cTusV2cQsc7Tqweq213sd12hhwJ860ucHm3H4s',
        ],
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
            'charset' => 'UTF-8',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => false,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['v1/user'],
                    'pluralize' => false,
                    'extraPatterns' => [
                        'GET index' => 'index',
                        'GET view' => 'view',
                        'POST create' => 'create',
                        'PATCH update' => 'update',
                        'DELETE delete' => 'delete',
                    ],
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['v1/payments'],
                    'pluralize' => false,
                    'extraPatterns' => [
                        'GET contract-enquiry' => 'contract-enquiry',
                        'GET flat-contract-enquiry' => 'flat-contract-enquiry',
                        'GET new-payment' => 'new-payment',
                        'GET flat-new-payment' => 'flat-new-payment',
                    ],
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['v1/customer-images'],
                    'pluralize' => false,
                    'extraPatterns' => [
                        'GET index' => 'index',
                    ],
                ],
            ]
        ]
    ],
    'params' => $params,
];
