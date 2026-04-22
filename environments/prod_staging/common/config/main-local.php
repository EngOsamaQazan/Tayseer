<?php
/**
 * STAGING database configuration.
 *
 * IMPORTANT: This DSN MUST point at a SEPARATE physical database
 * (`tayseer_staging`), never at a production tenant DB. The previous
 * version of this file pointed at `namaa_erp` which would have made
 * staging writes silently corrupt production data — fixed 2026-04-22
 * as part of the unify-media safety-net work.
 *
 * The `tayseer_staging` schema is refreshed nightly from the most
 * recent `namaa` daily snapshot; see scripts/staging/refresh.sh.
 */
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=tayseer_staging',
            'username' => 'osama',
            'password' => 'O$amaDaTaBase@123',
            'charset' => 'utf8',
            'tablePrefix' => 'os_',
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 86400,
            'schemaCache' => 'cache',
            'enableQueryCache' => true,
            'queryCacheDuration' => 3600,
            'attributes' => [
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode='', SESSION group_concat_max_len=65536",
            ],
        ],
        'mailer' => [
            'class' => 'yii\symfonymailer\Mailer',
            'viewPath' => '@common/mail',
            // Staging never sends real email — file transport prevents
            // an accidental "send 4000 reminder SMS to test customers"
            // incident on cloned production data.
            'useFileTransport' => true,
        ],
    ],
];
