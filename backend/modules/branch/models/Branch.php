<?php

namespace backend\modules\branch\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * الفروع الموحدة — Unified Branch
 *
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property string|null $code
 * @property string $branch_type  hq|branch|warehouse|client_site|field_area
 * @property string|null $description
 * @property string|null $address
 * @property float|null $latitude
 * @property float|null $longitude
 * @property int $radius_meters
 * @property string|null $wifi_ssid
 * @property string|null $wifi_bssid
 * @property int|null $manager_id
 * @property string|null $phone
 * @property int $is_active
 * @property int $sort_order
 * @property int|null $created_by
 * @property string $created_at
 * @property string|null $updated_at
 */
class Branch extends ActiveRecord
{
    const TYPE_HQ          = 'hq';
    const TYPE_BRANCH      = 'branch';
    const TYPE_WAREHOUSE   = 'warehouse';
    const TYPE_CLIENT_SITE = 'client_site';
    const TYPE_FIELD_AREA  = 'field_area';

    public static function tableName()
    {
        return '{{%branch}}';
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['company_id', 'radius_meters', 'manager_id', 'is_active', 'sort_order', 'created_by'], 'integer'],
            [['latitude', 'longitude'], 'number'],
            [['name'], 'string', 'max' => 150],
            [['code'], 'string', 'max' => 20],
            [['code'], 'unique'],
            [['branch_type'], 'in', 'range' => ['hq', 'branch', 'warehouse', 'client_site', 'field_area']],
            [['branch_type'], 'default', 'value' => 'branch'],
            [['description', 'address'], 'string', 'max' => 500],
            [['wifi_ssid'], 'string', 'max' => 100],
            [['wifi_bssid'], 'string', 'max' => 50],
            [['phone'], 'string', 'max' => 20],
            [['radius_meters'], 'default', 'value' => 100],
            [['radius_meters'], 'integer', 'min' => 20, 'max' => 5000],
            [['is_active'], 'default', 'value' => 1],
            [['sort_order'], 'default', 'value' => 0],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'            => 'المعرف',
            'company_id'    => 'الشركة',
            'name'          => 'اسم الفرع',
            'code'          => 'كود الفرع',
            'branch_type'   => 'نوع الفرع',
            'description'   => 'الوصف',
            'address'       => 'العنوان',
            'latitude'      => 'خط العرض',
            'longitude'     => 'خط الطول',
            'radius_meters' => 'نصف القطر (متر)',
            'wifi_ssid'     => 'شبكة Wi-Fi',
            'wifi_bssid'    => 'BSSID',
            'manager_id'    => 'مدير الفرع',
            'phone'         => 'الهاتف',
            'is_active'     => 'فعّال',
            'sort_order'    => 'الترتيب',
            'created_by'    => 'أنشئ بواسطة',
            'created_at'    => 'تاريخ الإنشاء',
            'updated_at'    => 'آخر تحديث',
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_by = Yii::$app->user->id ?? null;
                if (!$this->created_at) {
                    $this->created_at = date('Y-m-d H:i:s');
                }
                if (empty($this->code)) {
                    $this->code = self::generateCode();
                }
            }
            $this->updated_at = date('Y-m-d H:i:s');
            return true;
        }
        return false;
    }

    public static function generateCode()
    {
        $last = self::find()->select('code')->where(['LIKE', 'code', 'BR-'])->orderBy(['id' => SORT_DESC])->scalar();
        $num = 1;
        if ($last && preg_match('/BR-(\d+)/', $last, $m)) {
            $num = (int)$m[1] + 1;
        }
        return 'BR-' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }

    // ─── Type Labels ───

    public static function getTypeLabels()
    {
        return [
            self::TYPE_HQ          => 'المقر الرئيسي',
            self::TYPE_BRANCH      => 'فرع',
            self::TYPE_WAREHOUSE   => 'مخزن',
            self::TYPE_CLIENT_SITE => 'موقع عميل',
            self::TYPE_FIELD_AREA  => 'منطقة ميدانية',
        ];
    }

    public function getTypeLabel()
    {
        return self::getTypeLabels()[$this->branch_type] ?? $this->branch_type;
    }

    // ─── Relations ───

    public function getManager()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'manager_id']);
    }

    public function getCreatedByUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'created_by']);
    }

    public function getCompany()
    {
        return $this->hasOne(\backend\modules\companies\models\Companies::class, ['id' => 'company_id']);
    }

    public function getEmployees()
    {
        return $this->hasMany(\backend\modules\hr\models\HrEmployeeExtended::class, ['unified_branch_id' => 'id']);
    }

    public function getStockLocations()
    {
        return $this->hasMany(\backend\modules\inventoryStockLocations\models\InventoryStockLocations::class, ['branch_id' => 'id']);
    }

    // ─── Geofence (Haversine) ───

    public function isPointInside($lat, $lng)
    {
        if (!$this->latitude || !$this->longitude) return false;
        $earthRadius = 6371000;
        $dLat = deg2rad($lat - $this->latitude);
        $dLng = deg2rad($lng - $this->longitude);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) *
             sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return ($earthRadius * $c) <= $this->radius_meters;
    }

    public function distanceFrom($lat, $lng)
    {
        if (!$this->latitude || !$this->longitude) return null;
        $earthRadius = 6371000;
        $dLat = deg2rad($lat - $this->latitude);
        $dLng = deg2rad($lng - $this->longitude);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) *
             sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    // ─── Helpers ───

    public static function getActiveList()
    {
        return self::find()
            ->where(['is_active' => 1])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->select(['name', 'id'])
            ->indexBy('id')
            ->column();
    }

    public static function getActiveListByType($type)
    {
        return self::find()
            ->where(['is_active' => 1, 'branch_type' => $type])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->select(['name', 'id'])
            ->indexBy('id')
            ->column();
    }
}
