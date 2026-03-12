<?php

use yii\db\Migration;

/**
 * نظام إدارة القوى العاملة — Workforce Management System
 * ═══════════════════════════════════════════════════════
 * يُنشئ جداول: الورديات المحسّنة، مناطق العمل (Geofences)،
 * سجل الحضور الموحّد، أحداث Geofence، نقاط التتبع.
 * يُضيف أعمدة جديدة لجدول الموظفين الموسّع.
 */
class m260223_200000_create_workforce_tracking_tables extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        // ═══════════════════════════════════════════════════════
        //  1. تحسين جدول الورديات الموجود (os_work_shift)
        // ═══════════════════════════════════════════════════════
        $cols = $this->db->getTableSchema('{{%work_shift}}');
        if ($cols !== null) {
            if (!isset($cols->columns['grace_minutes'])) {
                $this->addColumn('{{%work_shift}}', 'grace_minutes', $this->integer()->defaultValue(15)->after('end_at'));
            }
            if (!isset($cols->columns['early_leave_minutes'])) {
                $this->addColumn('{{%work_shift}}', 'early_leave_minutes', $this->integer()->defaultValue(10)->after('grace_minutes'));
            }
            if (!isset($cols->columns['overtime_after_minutes'])) {
                $this->addColumn('{{%work_shift}}', 'overtime_after_minutes', $this->integer()->defaultValue(30)->after('early_leave_minutes'));
            }
            if (!isset($cols->columns['working_days'])) {
                $this->addColumn('{{%work_shift}}', 'working_days', 'JSON NULL DEFAULT NULL AFTER `overtime_after_minutes`');
            }
            if (!isset($cols->columns['is_flexible'])) {
                $this->addColumn('{{%work_shift}}', 'is_flexible', $this->tinyInteger(1)->defaultValue(0)->after('working_days'));
            }
            if (!isset($cols->columns['flex_window_minutes'])) {
                $this->addColumn('{{%work_shift}}', 'flex_window_minutes', $this->integer()->defaultValue(60)->after('is_flexible'));
            }
            if (!isset($cols->columns['break_duration_minutes'])) {
                $this->addColumn('{{%work_shift}}', 'break_duration_minutes', $this->integer()->defaultValue(60)->after('flex_window_minutes'));
            }
            if (!isset($cols->columns['is_active'])) {
                $this->addColumn('{{%work_shift}}', 'is_active', $this->tinyInteger(1)->defaultValue(1)->after('break_duration_minutes'));
            }
            if (!isset($cols->columns['company_id'])) {
                $this->addColumn('{{%work_shift}}', 'company_id', $this->integer()->after('id'));
            }
        }

        // ═══════════════════════════════════════════════════════
        //  2. مناطق العمل — Work Zones (Geofences)
        // ═══════════════════════════════════════════════════════
        if ($this->db->getTableSchema('{{%hr_work_zone}}') === null) {
            $this->createTable('{{%hr_work_zone}}', [
                'id'              => $this->primaryKey(),
                'company_id'      => $this->integer(),
                'name'            => $this->string(150)->notNull(),
                'zone_type'       => "ENUM('office','branch','client_site','field_area','restricted') NOT NULL DEFAULT 'office'",
                'latitude'        => $this->decimal(10, 7)->notNull(),
                'longitude'       => $this->decimal(10, 7)->notNull(),
                'radius_meters'   => $this->integer()->notNull()->defaultValue(100),
                'address'         => $this->string(500),
                'wifi_ssid'       => $this->string(100),
                'wifi_bssid'      => $this->string(50),
                'is_active'       => $this->tinyInteger(1)->defaultValue(1),
                'created_at'      => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
                'created_by'      => $this->integer(),
                'updated_at'      => $this->dateTime(),
            ], $tableOptions);

            $this->createIndex('idx_wz_company', '{{%hr_work_zone}}', 'company_id');
            $this->createIndex('idx_wz_location', '{{%hr_work_zone}}', ['latitude', 'longitude']);
        }

        // ═══════════════════════════════════════════════════════
        //  3. أعمدة جديدة على جدول الموظفين الموسّع
        // ═══════════════════════════════════════════════════════
        $empCols = $this->db->getTableSchema('{{%hr_employee_extended}}');
        if ($empCols !== null) {
            if (!isset($empCols->columns['employee_type'])) {
                $this->addColumn('{{%hr_employee_extended}}', 'employee_type',
                    "ENUM('office','field','sales','hybrid') NOT NULL DEFAULT 'office' AFTER `shift_id`");
            }
            if (!isset($empCols->columns['work_zone_id'])) {
                $this->addColumn('{{%hr_employee_extended}}', 'work_zone_id',
                    $this->integer()->after('employee_type'));
            }
            if (!isset($empCols->columns['tracking_mode'])) {
                $this->addColumn('{{%hr_employee_extended}}', 'tracking_mode',
                    "ENUM('geofence_only','continuous','on_task','disabled') NOT NULL DEFAULT 'geofence_only' AFTER `work_zone_id`");
            }
        }

        // ═══════════════════════════════════════════════════════
        //  4. سجل الحضور الموحّد — Unified Attendance Log
        // ═══════════════════════════════════════════════════════
        if ($this->db->getTableSchema('{{%hr_attendance_log}}') === null) {
            $this->createTable('{{%hr_attendance_log}}', [
                'id'                    => $this->bigPrimaryKey(),
                'company_id'            => $this->integer(),
                'user_id'               => $this->integer()->notNull(),
                'attendance_date'       => $this->date()->notNull(),
                'shift_id'              => $this->integer(),

                'clock_in_at'           => $this->dateTime(),
                'clock_out_at'          => $this->dateTime(),
                'clock_in_method'       => "ENUM('geofence_auto','manual','wifi','qr','biometric','admin') NOT NULL DEFAULT 'manual'",
                'clock_out_method'      => "ENUM('geofence_auto','manual','wifi','shift_end','admin','timeout') NULL",

                'clock_in_lat'          => $this->decimal(10, 7),
                'clock_in_lng'          => $this->decimal(10, 7),
                'clock_out_lat'         => $this->decimal(10, 7),
                'clock_out_lng'         => $this->decimal(10, 7),
                'clock_in_zone_id'      => $this->integer(),
                'clock_out_zone_id'     => $this->integer(),

                'clock_in_accuracy'     => $this->float(),
                'clock_in_wifi_verified' => $this->tinyInteger(1)->defaultValue(0),
                'is_mock_location'      => $this->tinyInteger(1)->defaultValue(0),

                'status'                => "ENUM('present','late','absent','half_day','on_leave','holiday','weekend','field_duty') NOT NULL DEFAULT 'present'",
                'total_minutes'         => $this->integer()->defaultValue(0),
                'overtime_minutes'      => $this->integer()->defaultValue(0),
                'late_minutes'          => $this->integer()->defaultValue(0),
                'early_leave_minutes'   => $this->integer()->defaultValue(0),
                'break_minutes'         => $this->integer()->defaultValue(0),

                'notes'                 => $this->text(),
                'admin_adjusted'        => $this->tinyInteger(1)->defaultValue(0),
                'adjustment_reason'     => $this->text(),
                'created_at'            => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            ], $tableOptions);

            $this->createIndex('idx_al_company_date', '{{%hr_attendance_log}}', ['company_id', 'attendance_date']);
            $this->createIndex('idx_al_user_date', '{{%hr_attendance_log}}', ['user_id', 'attendance_date']);
            $this->createIndex('uk_al_user_date', '{{%hr_attendance_log}}', ['user_id', 'attendance_date'], true);
        }

        // ═══════════════════════════════════════════════════════
        //  5. أحداث Geofence (دخول/خروج)
        // ═══════════════════════════════════════════════════════
        if ($this->db->getTableSchema('{{%hr_geofence_event}}') === null) {
            $this->createTable('{{%hr_geofence_event}}', [
                'id'                => $this->bigPrimaryKey(),
                'company_id'        => $this->integer(),
                'user_id'           => $this->integer()->notNull(),
                'zone_id'           => $this->integer()->notNull(),
                'event_type'        => "ENUM('enter','exit','dwell') NOT NULL",
                'latitude'          => $this->decimal(10, 7)->notNull(),
                'longitude'         => $this->decimal(10, 7)->notNull(),
                'accuracy'          => $this->float(),
                'triggered_at'      => $this->dateTime()->notNull(),
                'processed'         => $this->tinyInteger(1)->defaultValue(0),
                'attendance_log_id' => $this->bigInteger(),
            ], $tableOptions);

            $this->createIndex('idx_ge_user_time', '{{%hr_geofence_event}}', ['user_id', 'triggered_at']);
            $this->createIndex('idx_ge_zone', '{{%hr_geofence_event}}', ['zone_id', 'triggered_at']);
            $this->createIndex('idx_ge_unprocessed', '{{%hr_geofence_event}}', ['processed', 'triggered_at']);
        }

        // ═══════════════════════════════════════════════════════
        //  6. نقاط التتبع المحسّنة
        // ═══════════════════════════════════════════════════════
        if ($this->db->getTableSchema('{{%hr_tracking_point}}') === null) {
            $this->createTable('{{%hr_tracking_point}}', [
                'id'            => $this->bigPrimaryKey(),
                'company_id'    => $this->integer(),
                'user_id'       => $this->integer()->notNull(),
                'session_id'    => $this->bigInteger(),
                'latitude'      => $this->decimal(10, 7)->notNull(),
                'longitude'     => $this->decimal(10, 7)->notNull(),
                'accuracy'      => $this->float(),
                'speed'         => $this->float(),
                'heading'       => $this->float(),
                'altitude'      => $this->float(),
                'battery_level' => $this->integer(),
                'is_moving'     => $this->tinyInteger(1),
                'is_mock'       => $this->tinyInteger(1)->defaultValue(0),
                'activity_type' => "ENUM('still','walking','driving','unknown') DEFAULT 'unknown'",
                'captured_at'   => $this->dateTime()->notNull(),
            ], $tableOptions);

            $this->createIndex('idx_tp_user_time', '{{%hr_tracking_point}}', ['user_id', 'captured_at']);
            $this->createIndex('idx_tp_session', '{{%hr_tracking_point}}', ['session_id', 'captured_at']);
        }
    }

    public function safeDown()
    {
        $this->dropTable('{{%hr_tracking_point}}');
        $this->dropTable('{{%hr_geofence_event}}');
        $this->dropTable('{{%hr_attendance_log}}');
        $this->dropTable('{{%hr_work_zone}}');

        // Remove added columns from hr_employee_extended
        $empCols = $this->db->getTableSchema('{{%hr_employee_extended}}');
        if ($empCols !== null) {
            if (isset($empCols->columns['tracking_mode'])) $this->dropColumn('{{%hr_employee_extended}}', 'tracking_mode');
            if (isset($empCols->columns['work_zone_id'])) $this->dropColumn('{{%hr_employee_extended}}', 'work_zone_id');
            if (isset($empCols->columns['employee_type'])) $this->dropColumn('{{%hr_employee_extended}}', 'employee_type');
        }

        // Remove added columns from work_shift
        $shiftCols = $this->db->getTableSchema('{{%work_shift}}');
        if ($shiftCols !== null) {
            foreach (['company_id', 'grace_minutes', 'early_leave_minutes', 'overtime_after_minutes',
                       'working_days', 'is_flexible', 'flex_window_minutes', 'break_duration_minutes', 'is_active'] as $col) {
                if (isset($shiftCols->columns[$col])) $this->dropColumn('{{%work_shift}}', $col);
            }
        }
    }
}
