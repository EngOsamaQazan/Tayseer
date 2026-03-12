<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * نقاط التتبع المحسّنة
 *
 * @property int $id
 * @property int|null $company_id
 * @property int $user_id
 * @property int|null $session_id
 * @property float $latitude
 * @property float $longitude
 * @property float|null $accuracy
 * @property float|null $speed
 * @property float|null $heading
 * @property float|null $altitude
 * @property int|null $battery_level
 * @property int|null $is_moving
 * @property int $is_mock
 * @property string $activity_type
 * @property string $captured_at
 */
class HrTrackingPoint extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%hr_tracking_point}}';
    }

    public function rules()
    {
        return [
            [['user_id', 'latitude', 'longitude', 'captured_at'], 'required'],
            [['company_id', 'user_id', 'session_id', 'battery_level', 'is_moving', 'is_mock'], 'integer'],
            [['latitude', 'longitude', 'accuracy', 'speed', 'heading', 'altitude'], 'number'],
            [['activity_type'], 'in', 'range' => ['still', 'walking', 'driving', 'unknown']],
            [['captured_at'], 'safe'],
            [['is_mock'], 'default', 'value' => 0],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'            => Yii::t('app', 'المعرف'),
            'user_id'       => Yii::t('app', 'الموظف'),
            'latitude'      => Yii::t('app', 'خط العرض'),
            'longitude'     => Yii::t('app', 'خط الطول'),
            'accuracy'      => Yii::t('app', 'الدقة (متر)'),
            'speed'         => Yii::t('app', 'السرعة'),
            'battery_level' => Yii::t('app', 'مستوى البطارية'),
            'is_moving'     => Yii::t('app', 'متحرك'),
            'is_mock'       => Yii::t('app', 'موقع مُزيّف'),
            'activity_type' => Yii::t('app', 'نوع النشاط'),
            'captured_at'   => Yii::t('app', 'وقت الالتقاط'),
        ];
    }

    public function getUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'user_id']);
    }

    /**
     * حفظ نقطة جديدة + فحص Geofence
     */
    public static function recordPoint($data)
    {
        $point = new self();
        $point->attributes = $data;

        if (!$point->save()) {
            return ['success' => false, 'errors' => $point->getFirstErrors()];
        }

        $events = self::checkGeofences($point);

        return ['success' => true, 'point_id' => $point->id, 'events' => $events];
    }

    /**
     * فحص كل مناطق العمل النشطة ضد النقطة الحالية
     */
    private static function checkGeofences($point)
    {
        $zones = HrWorkZone::find()->where(['is_active' => 1])->all();
        $events = [];

        foreach ($zones as $zone) {
            $inside = $zone->isPointInside($point->latitude, $point->longitude);

            $lastEvent = HrGeofenceEvent::find()
                ->where(['user_id' => $point->user_id, 'zone_id' => $zone->id])
                ->orderBy(['triggered_at' => SORT_DESC])
                ->one();

            $wasInside = $lastEvent && $lastEvent->event_type === HrGeofenceEvent::EVENT_ENTER;

            if ($inside && !$wasInside) {
                $event = new HrGeofenceEvent([
                    'company_id'   => $point->company_id,
                    'user_id'      => $point->user_id,
                    'zone_id'      => $zone->id,
                    'event_type'   => HrGeofenceEvent::EVENT_ENTER,
                    'latitude'     => $point->latitude,
                    'longitude'    => $point->longitude,
                    'accuracy'     => $point->accuracy,
                    'triggered_at' => $point->captured_at,
                ]);
                if ($event->save()) {
                    $event->processEnterEvent();
                    $events[] = ['type' => 'enter', 'zone' => $zone->name];
                }
            } elseif (!$inside && $wasInside) {
                $event = new HrGeofenceEvent([
                    'company_id'   => $point->company_id,
                    'user_id'      => $point->user_id,
                    'zone_id'      => $zone->id,
                    'event_type'   => HrGeofenceEvent::EVENT_EXIT,
                    'latitude'     => $point->latitude,
                    'longitude'    => $point->longitude,
                    'accuracy'     => $point->accuracy,
                    'triggered_at' => $point->captured_at,
                ]);
                if ($event->save()) {
                    $event->processExitEvent();
                    $events[] = ['type' => 'exit', 'zone' => $zone->name];
                }
            }
        }

        return $events;
    }
}
