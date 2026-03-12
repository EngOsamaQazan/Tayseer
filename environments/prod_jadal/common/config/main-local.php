<?php
return [
    'components' => [
        //  'request'= > [

        //              'enableCsrfValidation' = > false
        //],
        // 'cache'= > [
        //   'class' = > 'yii\caching\FileCache',
        //],
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=namaa_jadal',
            'username' => 'osama',
            'password' => 'OsamaDB123',
            'charset' => 'utf8',
            'tablePrefix' => 'os_',
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 86400,
            'schemaCache' => 'cache',
            'enableQueryCache' => true,
            'queryCacheDuration' => 3600,
            'attributes' => [
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ],
        ],
        'mailer' => [
            'class' => 'yii\symfonymailer\Mailer',
            'viewPath' => '@common/mail',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
    ],
];