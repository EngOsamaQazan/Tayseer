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
            'dsn' => 'mysql:host=localhost;dbname=namaa_erp',
            'username' => 'osama',
            'password' => 'O$amaDaTaBase@123',
            'charset' => 'utf8',
            'tablePrefix' => 'os_',
            'enableSchemaCache' => true,

            // Duration of schema cache.
            // 'schemaCacheDuration' = > 3600,

            // Name of the cache component used to store schema information
            // 'schemaCache' = > 'cache',
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