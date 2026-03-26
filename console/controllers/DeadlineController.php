<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use backend\models\JudiciaryDeadline;

class DeadlineController extends Controller
{
    /**
     * Generate missing deadline records for existing cases and request actions.
     * Safe to run multiple times (idempotent via NOT EXISTS).
     */
    public function actionGenerate()
    {
        $db = Yii::$app->db;
        $p  = $db->tablePrefix;
        $now = time();
        $total = 0;

        $this->stdout("Generating missing deadline records...\n");

        $reqType = 'request_decision_3wd';
        $count = (int) $db->createCommand("
            INSERT IGNORE INTO {$p}judiciary_deadlines
                (judiciary_id, customer_id, deadline_type, day_type, label,
                 start_date, deadline_date, status,
                 related_customer_action_id, is_deleted, created_at, updated_at)
            SELECT
                jca.judiciary_id,
                jca.customers_id,
                '{$reqType}',
                'working',
                'قرار القاضي على الطلب',
                COALESCE(jca.action_date, FROM_UNIXTIME(jca.created_at, '%Y-%m-%d')),
                DATE_ADD(COALESCE(jca.action_date, FROM_UNIXTIME(jca.created_at, '%Y-%m-%d')), INTERVAL 5 DAY),
                'pending',
                jca.id,
                0, {$now}, {$now}
            FROM {$p}judiciary_customers_actions jca
            INNER JOIN {$p}judiciary_actions ja ON ja.id = jca.judiciary_actions_id
            LEFT JOIN {$p}judiciary_deadlines dl
                ON dl.related_customer_action_id = jca.id
                AND dl.deadline_type IN ('{$reqType}', 'request_decision')
                AND dl.is_deleted = 0
            WHERE ja.action_nature = 'request'
              AND (jca.is_deleted = 0 OR jca.is_deleted IS NULL)
              AND dl.id IS NULL
        ")->execute();
        $total += $count;
        $this->stdout("  Request-decision deadlines: {$count} inserted\n");

        $regType = 'registration_3wd';
        $count = (int) $db->createCommand("
            INSERT IGNORE INTO {$p}judiciary_deadlines
                (judiciary_id, customer_id, deadline_type, day_type, label,
                 start_date, deadline_date, status,
                 is_deleted, created_at, updated_at)
            SELECT
                j.id,
                NULL,
                '{$regType}',
                'working',
                'فحص حالة التبليغ بعد التسجيل',
                FROM_UNIXTIME(j.created_at, '%Y-%m-%d'),
                DATE_ADD(FROM_UNIXTIME(j.created_at, '%Y-%m-%d'), INTERVAL 5 DAY),
                'pending',
                0, {$now}, {$now}
            FROM {$p}judiciary j
            LEFT JOIN {$p}judiciary_deadlines dl
                ON dl.judiciary_id = j.id
                AND dl.deadline_type IN ('{$regType}', 'registration')
                AND dl.is_deleted = 0
            WHERE (j.is_deleted = 0 OR j.is_deleted IS NULL)
              AND dl.id IS NULL
        ")->execute();
        $total += $count;
        $this->stdout("  Registration-check deadlines: {$count} inserted\n");

        $this->stdout("Done. Total inserted: {$total}\n");
        return ExitCode::OK;
    }
}
