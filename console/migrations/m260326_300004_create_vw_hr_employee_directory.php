<?php

use yii\db\Migration;

/**
 * Phase 3.4 — vw_hr_employee_directory
 *
 * يجمع بيانات الموظف مع القسم والمدير المباشر
 * بدل تكرار JOINs في كل شاشة تعرض اسم القسم أو المدير
 */
class m260326_300004_create_vw_hr_employee_directory extends Migration
{
    public function safeUp()
    {
        $p = $this->db->tablePrefix;

        $this->execute("
            CREATE OR REPLACE VIEW {$p}vw_hr_employee_directory AS
            SELECT
                u.id,
                u.username,
                u.name,
                u.email,
                u.mobile,
                u.employee_type,
                u.employee_status,
                u.date_of_hire,
                u.gender,
                u.nationality,
                u.department AS department_id,
                u.job_title AS job_title_id,
                u.reporting_to,
                u.blocked_at,
                u.confirmed_at,
                u.created_at,

                d.title AS department_name,
                mgr.name AS reporting_to_name,

                CASE
                    WHEN u.blocked_at IS NOT NULL THEN 'blocked'
                    WHEN u.employee_type = 'Active' THEN 'active'
                    ELSE COALESCE(u.employee_type, 'unknown')
                END AS computed_status

            FROM {$p}user u
            LEFT JOIN {$p}department d ON d.id = u.department
            LEFT JOIN {$p}user mgr ON mgr.id = u.reporting_to
            WHERE u.confirmed_at IS NOT NULL
        ");
    }

    public function safeDown()
    {
        $p = $this->db->tablePrefix;
        $this->execute("DROP VIEW IF EXISTS {$p}vw_hr_employee_directory");
    }
}
