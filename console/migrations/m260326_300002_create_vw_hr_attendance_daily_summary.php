<?php

use yii\db\Migration;

/**
 * Phase 3.2 — vw_hr_attendance_daily_summary
 *
 * تجميع يومي للحضور: عدد الحاضرين/الغائبين/الإجازات لكل يوم
 * يُستخدم في لوحة الحضور + Dashboard بدل COUNT متكرر
 */
class m260326_300002_create_vw_hr_attendance_daily_summary extends Migration
{
    public function safeUp()
    {
        $p = $this->db->tablePrefix;

        $this->execute("
            CREATE OR REPLACE VIEW {$p}vw_hr_attendance_daily_summary AS
            SELECT
                attendance_date,
                COUNT(*) AS total_records,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent_count,
                SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) AS leave_count,
                SUM(CASE WHEN status = 'holiday' THEN 1 ELSE 0 END) AS holiday_count,
                SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) AS half_day_count,
                SUM(CASE WHEN status = 'remote' THEN 1 ELSE 0 END) AS remote_count,
                SUM(CASE WHEN status IN ('present','late','field_duty') THEN 1 ELSE 0 END) AS working_count
            FROM {$p}hr_attendance
            WHERE is_deleted = 0
            GROUP BY attendance_date
        ");
    }

    public function safeDown()
    {
        $p = $this->db->tablePrefix;
        $this->execute("DROP VIEW IF EXISTS {$p}vw_hr_attendance_daily_summary");
    }
}
