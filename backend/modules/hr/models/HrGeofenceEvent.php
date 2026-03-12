<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * أحداث الـ Geofence (دخول / خروج / بقاء)
 *
 * @property int $id
 * @property int|null $company_id
 * @property int $user_id
 * @property int $zone_id
 * @property string $event_type  enter|exit|dwell
 * @property float $latitude
 * @property float $longitude
 * @property float|null $accuracy
 * @property string $triggered_at
 * @property int $processed
 * @property int|null $attendance_log_id
 */
class HrGeofenceEvent extends ActiveRecord
{
    const EVENT_ENTER = 'enter';
    const EVENT_EXIT  = 'exit';
    const EVENT_DWELL = 'dwell';

    public static function tableName()
    {
        return '{{%hr_geofence_event}}';
    }

    public function rules()
    {
        return [
            [['user_id', 'zone_id', 'event_type', 'latitude', 'longitude', 'triggered_at'], 'required'],
            [['company_id', 'user_id', 'zone_id', 'processed'], 'integer'],
            [['attendance_log_id'], 'integer'],
            [['latitude', 'longitude', 'accuracy'], 'number'],
            [['event_type'], 'in', 'range' => ['enter', 'exit', 'dwell']],
            [['triggered_at'], 'safe'],
            [['processed'], 'default', 'value' => 0],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'           => Yii::t('app', 'المعرف'),
            'user_id'      => Yii::t('app', 'الموظف'),
            'zone_id'      => Yii::t('app', 'المنطقة'),
            'event_type'   => Yii::t('app', 'نوع الحدث'),
            'latitude'     => Yii::t('app', 'خط العرض'),
            'longitude'    => Yii::t('app', 'خط الطول'),
            'accuracy'     => Yii::t('app', 'الدقة'),
            'triggered_at' => Yii::t('app', 'وقت الحدث'),
            'processed'    => Yii::t('app', 'تمت المعالجة'),
        ];
    }

    public function getUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'user_id']);
    }

    public function getZone()
    {
        return $this->hasOne(HrWorkZone::class, ['id' => 'zone_id']);
    }

    public function getAttendanceLog()
    {
        return $this->hasOne(HrAttendanceLog::class, ['id' => 'attendance_log_id']);
    }

    /**
     * معالجة حدث دخول — auto clock-in
     */
    public function processEnterEvent()
    {
        if ($this->processed) return;

        $emp = HrEmployeeExtended::findOne(['user_id' => $this->user_id]);
        if (!$emp || $emp->tracking_mode === 'disabled') return;

        $zone = $this->zone;
        if (!$zone || $zone->zone_type === 'restricted') return;

        if ($emp->work_zone_id && $emp->work_zone_id != $this->zone_id) return;

        $result = HrAttendanceLog::clockIn(
            $this->user_id,
            $this->latitude,
            $this->longitude,
            HrAttendanceLog::METHOD_GEOFENCE,
            $this->zone_id,
            $this->accuracy
        );

        $this->processed = 1;
        if ($result['success'] ?? false) {
            $this->attendance_log_id = $result['log_id'];
        }
        $this->save(false);
    }

    /**
     * معالجة حدث خروج — auto clock-out
     */
    public function processExitEvent()
    {
        if ($this->processed) return;

        $emp = HrEmployeeExtended::findOne(['user_id' => $this->user_id]);
        if (!$emp || $emp->tracking_mode === 'disabled') return;

        if ($emp->work_zone_id && $emp->work_zone_id != $this->zone_id) return;

        $result = HrAttendanceLog::clockOut(
            $this->user_id,
            $this->latitude,
            $this->longitude,
            HrAttendanceLog::METHOD_GEOFENCE,
            $this->zone_id
        );

        $this->processed = 1;
        if ($result['success'] ?? false) {
            $log = HrAttendanceLog::findOne(['user_id' => $this->user_id, 'attendance_date' => date('Y-m-d')]);
            $this->attendance_log_id = $log ? $log->id : null;
        }
        $this->save(false);
    }
}
