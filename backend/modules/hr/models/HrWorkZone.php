<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * مناطق العمل — Work Zones (Geofences)
 *
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property string $zone_type
 * @property float $latitude
 * @property float $longitude
 * @property int $radius_meters
 * @property string|null $address
 * @property string|null $wifi_ssid
 * @property string|null $wifi_bssid
 * @property int $is_active
 * @property string $created_at
 * @property int|null $created_by
 * @property string|null $updated_at
 */
class HrWorkZone extends ActiveRecord
{
    const TYPE_OFFICE      = 'office';
    const TYPE_BRANCH      = 'branch';
    const TYPE_CLIENT_SITE = 'client_site';
    const TYPE_FIELD_AREA  = 'field_area';
    const TYPE_RESTRICTED  = 'restricted';

    public static function tableName()
    {
        return '{{%hr_work_zone}}';
    }

    public function rules()
    {
        return [
            [['name', 'latitude', 'longitude'], 'required'],
            [['company_id', 'radius_meters', 'is_active', 'created_by'], 'integer'],
            [['latitude', 'longitude'], 'number'],
            [['name'], 'string', 'max' => 150],
            [['zone_type'], 'in', 'range' => ['office', 'branch', 'client_site', 'field_area', 'restricted']],
            [['zone_type'], 'default', 'value' => 'office'],
            [['address'], 'string', 'max' => 500],
            [['wifi_ssid'], 'string', 'max' => 100],
            [['wifi_bssid'], 'string', 'max' => 50],
            [['radius_meters'], 'default', 'value' => 100],
            [['radius_meters'], 'integer', 'min' => 20, 'max' => 5000],
            [['is_active'], 'default', 'value' => 1],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'            => Yii::t('app', 'المعرف'),
            'name'          => Yii::t('app', 'اسم المنطقة'),
            'zone_type'     => Yii::t('app', 'نوع المنطقة'),
            'latitude'      => Yii::t('app', 'خط العرض'),
            'longitude'     => Yii::t('app', 'خط الطول'),
            'radius_meters' => Yii::t('app', 'نصف القطر (متر)'),
            'address'       => Yii::t('app', 'العنوان'),
            'wifi_ssid'     => Yii::t('app', 'شبكة Wi-Fi'),
            'wifi_bssid'    => Yii::t('app', 'BSSID'),
            'is_active'     => Yii::t('app', 'فعّال'),
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_by = Yii::$app->user->id ?? null;
                $this->created_at = date('Y-m-d H:i:s');
            }
            $this->updated_at = date('Y-m-d H:i:s');
            return true;
        }
        return false;
    }

    /**
     * هل النقطة (lat, lng) داخل النطاق الجغرافي؟
     * Haversine formula
     */
    public function isPointInside($lat, $lng)
    {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat - $this->latitude);
        $dLng = deg2rad($lng - $this->longitude);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) *
             sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance <= $this->radius_meters;
    }

    /**
     * حساب المسافة بالأمتار من نقطة
     */
    public function distanceFrom($lat, $lng)
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat - $this->latitude);
        $dLng = deg2rad($lng - $this->longitude);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) *
             sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    public static function getZoneTypes()
    {
        return [
            'office'      => Yii::t('app', 'مكتب'),
            'branch'      => Yii::t('app', 'فرع'),
            'client_site' => Yii::t('app', 'موقع عميل'),
            'field_area'  => Yii::t('app', 'منطقة ميدانية'),
            'restricted'  => Yii::t('app', 'منطقة محظورة'),
        ];
    }

    public static function getActiveList()
    {
        return self::find()
            ->where(['is_active' => 1])
            ->select(['name', 'id'])
            ->indexBy('id')
            ->column();
    }

    public function getEmployees()
    {
        return $this->hasMany(HrEmployeeExtended::class, ['work_zone_id' => 'id']);
    }
}
