<?php

use yii\db\Migration;

/**
 * Phase 3.5 — vw_payroll_employee_attendance
 *
 * تجميع حضور الموظف لفترة الراتب (شهر/سنة)
 * يُستخدم في HrPayrollController::actionCalculate بدل استعلام
 * مستقل لكل موظف داخل loop
 */
class m260326_300005_create_vw_payroll_employee_attendance extends Migration
{
    public function safeUp()
    {
        $p = $this->db->tablePrefix;

        $this->execute("
            CREATE OR REPLACE VIEW {$p}vw_payroll_employee_attendance AS
            SELECT
                a.user_id,
                YEAR(a.attendance_date) AS period_year,
                MONTH(a.attendance_date) AS period_month,

                COUNT(*) AS total_records,
                SUM(CASE WHEN a.status IN ('present','late','field_duty') THEN 1 ELSE 0 END) AS present_days,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent_days,
                SUM(CASE WHEN a.status IN ('on_leave','leave') THEN 1 ELSE 0 END) AS leave_days,
                SUM(COALESCE(a.overtime_hours, 0)) AS overtime_hours,
                SUM(COALESCE(a.late_minutes, 0)) AS total_late_minutes

            FROM {$p}hr_attendance a
            WHERE a.is_deleted = 0
            GROUP BY a.user_id, YEAR(a.attendance_date), MONTH(a.attendance_date)
        ");
    }

    public function safeDown()
    {
        $p = $this->db->tablePrefix;
        $this->execute("DROP VIEW IF EXISTS {$p}vw_payroll_employee_attendance");
    }
}
