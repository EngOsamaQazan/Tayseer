<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * سجل الحضور الموحّد — Unified Attendance Log
 *
 * @property int $id
 * @property int|null $company_id
 * @property int $user_id
 * @property string $attendance_date
 * @property int|null $shift_id
 * @property string|null $clock_in_at
 * @property string|null $clock_out_at
 * @property string $clock_in_method
 * @property string|null $clock_out_method
 * @property float|null $clock_in_lat
 * @property float|null $clock_in_lng
 * @property float|null $clock_out_lat
 * @property float|null $clock_out_lng
 * @property int|null $clock_in_zone_id
 * @property int|null $clock_out_zone_id
 * @property float|null $clock_in_accuracy
 * @property int $clock_in_wifi_verified
 * @property int $is_mock_location
 * @property string $status
 * @property int $total_minutes
 * @property int $overtime_minutes
 * @property int $late_minutes
 * @property int $early_leave_minutes
 * @property int $break_minutes
 * @property string|null $notes
 * @property int $admin_adjusted
 * @property string|null $adjustment_reason
 * @property string $created_at
 */
class HrAttendanceLog extends ActiveRecord
{
    const STATUS_PRESENT    = 'present';
    const STATUS_LATE       = 'late';
    const STATUS_ABSENT     = 'absent';
    const STATUS_HALF_DAY   = 'half_day';
    const STATUS_ON_LEAVE   = 'on_leave';
    const STATUS_HOLIDAY    = 'holiday';
    const STATUS_WEEKEND    = 'weekend';
    const STATUS_FIELD_DUTY = 'field_duty';

    const METHOD_GEOFENCE = 'geofence_auto';
    const METHOD_MANUAL   = 'manual';
    const METHOD_WIFI     = 'wifi';
    const METHOD_QR       = 'qr';
    const METHOD_BIO      = 'biometric';
    const METHOD_ADMIN    = 'admin';

    public static function tableName()
    {
        return '{{%hr_attendance_log}}';
    }

    public function rules()
    {
        return [
            [['user_id', 'attendance_date'], 'required'],
            [['company_id', 'user_id', 'shift_id', 'clock_in_zone_id', 'clock_out_zone_id',
              'clock_in_wifi_verified', 'is_mock_location',
              'total_minutes', 'overtime_minutes', 'late_minutes', 'early_leave_minutes',
              'break_minutes', 'admin_adjusted'], 'integer'],
            [['clock_in_lat', 'clock_in_lng', 'clock_out_lat', 'clock_out_lng', 'clock_in_accuracy'], 'number'],
            [['attendance_date', 'clock_in_at', 'clock_out_at', 'created_at'], 'safe'],
            [['clock_in_method'], 'in', 'range' => ['geofence_auto', 'manual', 'wifi', 'qr', 'biometric', 'admin']],
            [['clock_out_method'], 'in', 'range' => ['geofence_auto', 'manual', 'wifi', 'shift_end', 'admin', 'timeout']],
            [['status'], 'in', 'range' => ['present', 'late', 'absent', 'half_day', 'on_leave', 'holiday', 'weekend', 'field_duty']],
            [['notes', 'adjustment_reason'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'                => Yii::t('app', 'المعرف'),
            'user_id'           => Yii::t('app', 'الموظف'),
            'attendance_date'   => Yii::t('app', 'التاريخ'),
            'shift_id'          => Yii::t('app', 'الوردية'),
            'clock_in_at'       => Yii::t('app', 'وقت الدخول'),
            'clock_out_at'      => Yii::t('app', 'وقت الخروج'),
            'clock_in_method'   => Yii::t('app', 'طريقة الدخول'),
            'clock_out_method'  => Yii::t('app', 'طريقة الخروج'),
            'status'            => Yii::t('app', 'الحالة'),
            'total_minutes'     => Yii::t('app', 'إجمالي الدقائق'),
            'overtime_minutes'  => Yii::t('app', 'دقائق الإضافي'),
            'late_minutes'      => Yii::t('app', 'دقائق التأخير'),
            'early_leave_minutes' => Yii::t('app', 'مغادرة مبكرة'),
            'break_minutes'     => Yii::t('app', 'دقائق الاستراحة'),
            'is_mock_location'  => Yii::t('app', 'موقع وهمي'),
            'admin_adjusted'    => Yii::t('app', 'معدّل يدوياً'),
            'adjustment_reason' => Yii::t('app', 'سبب التعديل'),
            'notes'             => Yii::t('app', 'ملاحظات'),
        ];
    }

    // ─── العلاقات ───

    public function getUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'user_id']);
    }

    public function getShift()
    {
        return $this->hasOne(HrWorkShift::class, ['id' => 'shift_id']);
    }

    public function getClockInZone()
    {
        return $this->hasOne(HrWorkZone::class, ['id' => 'clock_in_zone_id']);
    }

    public function getClockOutZone()
    {
        return $this->hasOne(HrWorkZone::class, ['id' => 'clock_out_zone_id']);
    }

    // ─── منطق الأعمال ───

    /**
     * تسجيل دخول موظف
     */
    public static function clockIn($userId, $lat, $lng, $method, $zoneId = null, $accuracy = null)
    {
        $today = date('Y-m-d');
        $log = self::findOne(['user_id' => $userId, 'attendance_date' => $today]);

        if ($log && $log->clock_in_at) {
            return ['success' => false, 'message' => Yii::t('app', 'تم تسجيل الدخول مسبقاً')];
        }

        if (!$log) {
            $log = new self();
            $log->user_id = $userId;
            $log->attendance_date = $today;
        }

        $now = date('Y-m-d H:i:s');
        $log->clock_in_at = $now;
        $log->clock_in_method = $method;
        $log->clock_in_lat = $lat;
        $log->clock_in_lng = $lng;
        $log->clock_in_zone_id = $zoneId;
        $log->clock_in_accuracy = $accuracy;

        $emp = HrEmployeeExtended::findOne(['user_id' => $userId]);
        if ($emp && $emp->shift_id) {
            $log->shift_id = $emp->shift_id;
            $shift = HrWorkShift::findOne($emp->shift_id);
            if ($shift) {
                $shiftStart = strtotime($today . ' ' . $shift->start_at);
                $clockIn = strtotime($now);
                $lateMin = max(0, ($clockIn - $shiftStart) / 60 - $shift->grace_minutes);
                $log->late_minutes = (int)$lateMin;
                $log->status = $lateMin > 0 ? self::STATUS_LATE : self::STATUS_PRESENT;
            }
        } else {
            $log->status = self::STATUS_PRESENT;
        }

        if ($log->save()) {
            return ['success' => true, 'log_id' => $log->id, 'status' => $log->status];
        }
        return ['success' => false, 'message' => implode(', ', $log->getFirstErrors())];
    }

    /**
     * تسجيل خروج موظف
     */
    public static function clockOut($userId, $lat, $lng, $method, $zoneId = null)
    {
        $today = date('Y-m-d');
        $log = self::findOne(['user_id' => $userId, 'attendance_date' => $today]);

        if (!$log || !$log->clock_in_at) {
            return ['success' => false, 'message' => Yii::t('app', 'لا يوجد تسجيل دخول لهذا اليوم')];
        }
        if ($log->clock_out_at) {
            return ['success' => false, 'message' => Yii::t('app', 'تم تسجيل الخروج مسبقاً')];
        }

        $now = date('Y-m-d H:i:s');
        $log->clock_out_at = $now;
        $log->clock_out_method = $method;
        $log->clock_out_lat = $lat;
        $log->clock_out_lng = $lng;
        $log->clock_out_zone_id = $zoneId;

        $totalMin = (strtotime($now) - strtotime($log->clock_in_at)) / 60;
        $log->total_minutes = (int)$totalMin;

        if ($log->shift_id) {
            $shift = HrWorkShift::findOne($log->shift_id);
            if ($shift) {
                $shiftDuration = $shift->getDurationMinutes();
                $overtime = max(0, $totalMin - $shiftDuration - $shift->overtime_after_minutes);
                $log->overtime_minutes = (int)$overtime;

                $shiftEnd = strtotime($today . ' ' . $shift->end_at);
                $clockOut = strtotime($now);
                $earlyMin = max(0, ($shiftEnd - $clockOut) / 60 - $shift->early_leave_minutes);
                $log->early_leave_minutes = (int)$earlyMin;

                if ($totalMin < ($shiftDuration / 2)) {
                    $log->status = self::STATUS_HALF_DAY;
                }
            }
        }

        if ($log->save()) {
            return ['success' => true, 'total_minutes' => $log->total_minutes, 'overtime_minutes' => $log->overtime_minutes];
        }
        return ['success' => false, 'message' => implode(', ', $log->getFirstErrors())];
    }

    public static function getStatusLabels()
    {
        return [
            self::STATUS_PRESENT    => Yii::t('app', 'حاضر'),
            self::STATUS_LATE       => Yii::t('app', 'متأخر'),
            self::STATUS_ABSENT     => Yii::t('app', 'غائب'),
            self::STATUS_HALF_DAY   => Yii::t('app', 'نصف يوم'),
            self::STATUS_ON_LEAVE   => Yii::t('app', 'في إجازة'),
            self::STATUS_HOLIDAY    => Yii::t('app', 'عطلة رسمية'),
            self::STATUS_WEEKEND    => Yii::t('app', 'عطلة أسبوعية'),
            self::STATUS_FIELD_DUTY => Yii::t('app', 'مهمة ميدانية'),
        ];
    }

    public static function getStatusBadgeClass($status)
    {
        $map = [
            'present'    => 'success',
            'late'       => 'warning',
            'absent'     => 'danger',
            'half_day'   => 'info',
            'on_leave'   => 'default',
            'holiday'    => 'primary',
            'weekend'    => 'default',
            'field_duty' => 'info',
        ];
        return $map[$status] ?? 'default';
    }
}
