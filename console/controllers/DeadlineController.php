<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class DeadlineController extends Controller
{
    /**
     * Generate missing deadline records for existing cases and request actions.
     * Safe to run multiple times (idempotent via LEFT JOIN ... IS NULL).
     */
    public function actionGenerate()
    {
        $db = Yii::$app->db;
        $p  = $db->tablePrefix;
        $now = time();
        $total = 0;

        $this->stdout("Generating missing deadline records...\n");

        $count = (int) $db->createCommand("
            INSERT IGNORE INTO {$p}judiciary_deadlines
                (judiciary_id, customer_id, deadline_type, day_type, label,
                 start_date, deadline_date, status,
                 related_customer_action_id, is_deleted, created_at, updated_at)
            SELECT
                jca.judiciary_id, jca.customers_id,
                'request_decision_3wd', 'working', 'قرار القاضي على الطلب',
                COALESCE(jca.action_date, FROM_UNIXTIME(jca.created_at, '%Y-%m-%d')),
                DATE_ADD(COALESCE(jca.action_date, FROM_UNIXTIME(jca.created_at, '%Y-%m-%d')), INTERVAL 5 DAY),
                'pending', jca.id, 0, {$now}, {$now}
            FROM {$p}judiciary_customers_actions jca
            INNER JOIN {$p}judiciary_actions ja ON ja.id = jca.judiciary_actions_id
            LEFT JOIN {$p}judiciary_deadlines dl
                ON dl.related_customer_action_id = jca.id
                AND dl.deadline_type IN ('request_decision_3wd', 'request_decision')
                AND dl.is_deleted = 0
            WHERE ja.action_nature = 'request'
              AND (jca.is_deleted = 0 OR jca.is_deleted IS NULL)
              AND dl.id IS NULL
        ")->execute();
        $total += $count;
        $this->stdout("  Request-decision: {$count}\n");

        $count = (int) $db->createCommand("
            INSERT IGNORE INTO {$p}judiciary_deadlines
                (judiciary_id, customer_id, deadline_type, day_type, label,
                 start_date, deadline_date, status,
                 is_deleted, created_at, updated_at)
            SELECT
                j.id, NULL,
                'registration_3wd', 'working', 'فحص حالة التبليغ بعد التسجيل',
                FROM_UNIXTIME(j.created_at, '%Y-%m-%d'),
                DATE_ADD(FROM_UNIXTIME(j.created_at, '%Y-%m-%d'), INTERVAL 5 DAY),
                'pending', 0, {$now}, {$now}
            FROM {$p}judiciary j
            LEFT JOIN {$p}judiciary_deadlines dl
                ON dl.judiciary_id = j.id
                AND dl.deadline_type IN ('registration_3wd', 'registration')
                AND dl.is_deleted = 0
            WHERE (j.is_deleted = 0 OR j.is_deleted IS NULL)
              AND dl.id IS NULL
        ")->execute();
        $total += $count;
        $this->stdout("  Registration-check: {$count}\n");
        $this->stdout("Generate done. Total inserted: {$total}\n");

        return ExitCode::OK;
    }

    /**
     * Normalize all deadline statuses using batch UPDATE queries.
     * Marks deadlines as 'completed' based on business rules, without VIEW.
     * Safe to run multiple times (idempotent).
     */
    public function actionNormalize()
    {
        $db = Yii::$app->db;
        $p  = $db->tablePrefix;
        $total = 0;

        $this->stdout("Normalizing deadline statuses...\n");

        // 1) Case closed/archived
        $n = (int) $db->createCommand("
            UPDATE {$p}judiciary_deadlines d
            INNER JOIN {$p}judiciary j ON j.id = d.judiciary_id
            SET d.status = 'completed'
            WHERE d.is_deleted = 0 AND d.status != 'completed'
              AND j.case_status IN ('closed','archived')
        ")->execute();
        $total += $n;
        $this->stdout("  Closed/archived cases: {$n}\n");

        // 2) Related action deleted
        $n = (int) $db->createCommand("
            UPDATE {$p}judiciary_deadlines d
            INNER JOIN {$p}judiciary_customers_actions ra ON ra.id = d.related_customer_action_id
            SET d.status = 'completed'
            WHERE d.is_deleted = 0 AND d.status != 'completed'
              AND ra.is_deleted = 1
        ")->execute();
        $total += $n;
        $this->stdout("  Deleted actions: {$n}\n");

        // 3) Registration deadlines with subsequent actions
        $n = (int) $db->createCommand("
            UPDATE {$p}judiciary_deadlines d
            SET d.status = 'completed'
            WHERE d.is_deleted = 0 AND d.status != 'completed'
              AND d.deadline_type IN ('registration_3wd','registration')
              AND EXISTS (
                  SELECT 1 FROM {$p}judiciary_customers_actions s
                  WHERE s.judiciary_id = d.judiciary_id
                    AND (s.is_deleted = 0 OR s.is_deleted IS NULL)
                    AND s.action_date > d.start_date
              )
        ")->execute();
        $total += $n;
        $this->stdout("  Registration w/ subsequent: {$n}\n");

        // 4) Request decision — explicitly approved/rejected
        $n = (int) $db->createCommand("
            UPDATE {$p}judiciary_deadlines d
            INNER JOIN {$p}judiciary_customers_actions ra ON ra.id = d.related_customer_action_id
            SET d.status = 'completed'
            WHERE d.is_deleted = 0 AND d.status != 'completed'
              AND d.deadline_type IN ('request_decision_3wd','request_decision')
              AND ra.request_status IN ('approved','rejected')
        ")->execute();
        $total += $n;
        $this->stdout("  Approved/rejected requests: {$n}\n");

        // 5) Request decision — implicit approval (has child actions)
        $n = (int) $db->createCommand("
            UPDATE {$p}judiciary_deadlines d
            SET d.status = 'completed'
            WHERE d.is_deleted = 0 AND d.status != 'completed'
              AND d.deadline_type IN ('request_decision_3wd','request_decision')
              AND d.related_customer_action_id IS NOT NULL
              AND EXISTS (
                  SELECT 1 FROM {$p}judiciary_customers_actions ch
                  WHERE ch.parent_id = d.related_customer_action_id
                    AND (ch.is_deleted = 0 OR ch.is_deleted IS NULL)
              )
        ")->execute();
        $total += $n;
        $this->stdout("  Implicit approval (child actions): {$n}\n");

        // 6) Correspondence with subsequent actions past deadline
        $n = (int) $db->createCommand("
            UPDATE {$p}judiciary_deadlines d
            SET d.status = 'completed'
            WHERE d.is_deleted = 0 AND d.status != 'completed'
              AND d.deadline_type IN ('correspondence_10wd','correspondence')
              AND EXISTS (
                  SELECT 1 FROM {$p}judiciary_customers_actions s2
                  WHERE s2.judiciary_id = d.judiciary_id
                    AND (s2.is_deleted = 0 OR s2.is_deleted IS NULL)
                    AND s2.action_date > d.deadline_date
              )
        ")->execute();
        $total += $n;
        $this->stdout("  Correspondence w/ subsequent: {$n}\n");

        // 7) Fully-paid judiciary contracts
        $n = (int) $db->createCommand("
            UPDATE {$p}judiciary_deadlines d
            INNER JOIN {$p}judiciary j ON j.id = d.judiciary_id
            INNER JOIN {$p}contracts co ON co.id = j.contract_id
            LEFT JOIN (SELECT contract_id, SUM(amount) t FROM {$p}expenses
                       WHERE is_deleted=0 OR is_deleted IS NULL GROUP BY contract_id) ex ON ex.contract_id = co.id
            LEFT JOIN (SELECT contract_id, SUM(lawyer_cost) t FROM {$p}judiciary
                       WHERE is_deleted=0 OR is_deleted IS NULL GROUP BY contract_id) lw ON lw.contract_id = co.id
            LEFT JOIN (SELECT contract_id, SUM(amount) t FROM {$p}income
                       GROUP BY contract_id) ic ON ic.contract_id = co.id
            LEFT JOIN (SELECT contract_id, SUM(amount) t FROM {$p}contract_adjustments
                       WHERE is_deleted=0 GROUP BY contract_id) ad ON ad.contract_id = co.id
            SET d.status = 'completed'
            WHERE d.is_deleted = 0 AND d.status != 'completed'
              AND co.status = 'judiciary'
              AND (co.total_value + COALESCE(ex.t,0) + COALESCE(lw.t,0) - COALESCE(ic.t,0) - COALESCE(ad.t,0)) <= 0
        ")->execute();
        $total += $n;
        $this->stdout("  Fully-paid contracts: {$n}\n");

        // 8) Revert old notes artifacts
        $n = (int) $db->createCommand("
            UPDATE {$p}judiciary_deadlines
            SET notes = NULL
            WHERE notes IN ('ترحيل تلقائي', 'تم إنجازه — منتهي أكثر من 6 أشهر بدون نشاط')
              AND is_deleted = 0
        ")->execute();
        $this->stdout("  Cleaned old notes: {$n}\n");

        $this->stdout("Normalize done. Total completed: {$total}\n");
        return ExitCode::OK;
    }
}
