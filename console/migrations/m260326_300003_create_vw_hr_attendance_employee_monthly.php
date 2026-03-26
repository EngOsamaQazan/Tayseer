<?php

use yii\db\Migration;

/**
 * Phase 3.3 — vw_hr_attendance_employee_monthly
 *
 * تجميع شهري لكل موظف: أيام الحضور/الغياب/الإجازات/الأوفرتايم
 * يُستخدم في:
 *   - التقرير الشهري (HrAttendanceController::actionSummary)
 *   - حساب الراتب (HrPayrollController::actionCalculate)
 *   - تقارير الالتزام والمخالفات
 */
class m260326_300003_create_vw_hr_attendance_employee_monthly extends Migration
{
    public function safeUp()
    {
        $p = $this->db->tablePrefix;

        $this->execute("
            CREATE OR REPLACE VIEW {$p}vw_hr_attendance_employee_monthly AS
            SELECT
                u.id AS user_id,
                u.username,
                u.name AS employee_name,
                d.id AS department_id,
                d.title AS department_name,
                MONTH(a.attendance_date) AS att_month,
                YEAR(a.attendance_date) AS att_year,

                COUNT(a.id) AS total_records,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_days,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent_days,
                SUM(CASE WHEN a.status IN ('on_leave','leave') THEN 1 ELSE 0 END) AS leave_days,
                SUM(CASE WHEN a.status = 'half_day' THEN 1 ELSE 0 END) AS half_days,
                SUM(CASE WHEN a.status = 'remote' THEN 1 ELSE 0 END) AS remote_days,
                SUM(CASE WHEN a.status IN ('present','late','field_duty') THEN 1 ELSE 0 END) AS payroll_present_days,
                SUM(COALESCE(a.total_hours, 0)) AS total_hours,
                SUM(COALESCE(a.overtime_hours, 0)) AS total_overtime,
                SUM(COALESCE(a.late_minutes, 0)) AS total_late_minutes

            FROM {$p}user u
            INNER JOIN {$p}hr_attendance a ON a.user_id = u.id AND a.is_deleted = 0
            LEFT JOIN {$p}department d ON d.id = u.department
            WHERE u.blocked_at IS NULL
              AND u.confirmed_at IS NOT NULL
            GROUP BY u.id, u.username, u.name, d.id, d.title,
                     MONTH(a.attendance_date), YEAR(a.attendance_date)
        ");
    }

    public function safeDown()
    {
        $p = $this->db->tablePrefix;
        $this->execute("DROP VIEW IF EXISTS {$p}vw_hr_attendance_employee_monthly");
    }
}
