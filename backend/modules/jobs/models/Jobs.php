<?php

namespace backend\modules\jobs\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "os_jobs".
 *
 * @property int $id
 * @property string $name
 * @property int $job_type
 * @property string|null $address_city
 * @property string|null $address_area
 * @property string|null $address_street
 * @property string|null $address_building
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $email
 * @property string|null $website
 * @property string|null $notes
 * @property int $status
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int $is_deleted
 *
 * @property JobsType $jobType
 * @property JobsPhone[] $phones
 * @property JobsWorkingHours[] $workingHours
 * @property JobsRating[] $ratings
 */
class Jobs extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_jobs';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['job_type', 'name'], 'required'],
            [['job_type', 'status', 'is_deleted'], 'integer'],
            [['latitude', 'longitude'], 'number'],
            [['notes'], 'string'],
            [['name', 'address_city', 'address_area', 'address_building', 'email', 'website'], 'string', 'max' => 255],
            [['postal_code', 'plus_code'], 'string', 'max' => 20],
            [['address_street'], 'string', 'max' => 500],
            [['email'], 'email'],
            [['website'], 'url', 'defaultScheme' => 'https'],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['is_deleted'], 'default', 'value' => 0],
            [['address_city', 'address_area', 'address_street', 'address_building', 'postal_code', 'plus_code', 'latitude', 'longitude', 'email', 'website', 'notes'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '#',
            'name' => Yii::t('app', 'اسم جهة العمل'),
            'job_type' => Yii::t('app', 'نوع جهة العمل'),
            'address_city' => Yii::t('app', 'المدينة'),
            'address_area' => Yii::t('app', 'المنطقة/الحي'),
            'address_street' => Yii::t('app', 'الشارع/العنوان التفصيلي'),
            'address_building' => Yii::t('app', 'المبنى/الطابق'),
            'postal_code' => Yii::t('app', 'الرمز البريدي'),
            'plus_code' => Yii::t('app', 'Plus Code'),
            'latitude' => Yii::t('app', 'خط العرض'),
            'longitude' => Yii::t('app', 'خط الطول'),
            'email' => Yii::t('app', 'البريد الإلكتروني'),
            'website' => Yii::t('app', 'الموقع الإلكتروني'),
            'notes' => Yii::t('app', 'ملاحظات'),
            'status' => Yii::t('app', 'الحالة'),
            'created_at' => Yii::t('app', 'تاريخ الإنشاء'),
            'updated_at' => Yii::t('app', 'تاريخ التحديث'),
            'created_by' => Yii::t('app', 'أنشئ بواسطة'),
            'updated_by' => Yii::t('app', 'حُدّث بواسطة'),
            'is_deleted' => Yii::t('app', 'محذوف'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getJobType()
    {
        return $this->hasOne(JobsType::class, ['id' => 'job_type']);
    }

    /**
     * Backward compatibility alias
     * @return \yii\db\ActiveQuery
     */
    public function getJobsType()
    {
        return $this->getJobType();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhones()
    {
        return $this->hasMany(JobsPhone::class, ['job_id' => 'id'])
            ->andWhere(['is_deleted' => 0]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkingHours()
    {
        return $this->hasMany(JobsWorkingHours::class, ['job_id' => 'id'])
            ->orderBy(['day_of_week' => SORT_ASC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRatings()
    {
        return $this->hasMany(JobsRating::class, ['job_id' => 'id'])
            ->andWhere(['is_deleted' => 0])
            ->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * Get average rating for this job entity
     * @param string|null $type rating type filter
     * @return float|null
     */
    public function getAverageRating($type = null)
    {
        $query = JobsRating::find()
            ->where(['job_id' => $this->id, 'is_deleted' => 0]);
        if ($type) {
            $query->andWhere(['rating_type' => $type]);
        }
        return $query->average('rating_value');
    }

    /**
     * Get status label
     * @return string
     */
    public function getStatusLabel()
    {
        return $this->status == self::STATUS_ACTIVE ? 'فعال' : 'غير فعال';
    }

    /**
     * Get status badge HTML
     * @return string
     */
    public function getStatusBadge()
    {
        if ($this->status == self::STATUS_ACTIVE) {
            return '<span class="label label-success">فعال</span>';
        }
        return '<span class="label label-danger">غير فعال</span>';
    }

    /**
     * @return array day names in Arabic
     */
    public static function getDayNames()
    {
        return [
            0 => 'الأحد',
            1 => 'الاثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت',
        ];
    }

    /**
     * Default scope: exclude soft-deleted
     */
    public static function find()
    {
        return parent::find()->andWhere(['os_jobs.is_deleted' => 0]);
    }

    /**
     * Soft delete
     */
    public function softDelete()
    {
        $this->is_deleted = 1;
        return $this->save(false);
    }

    /**
     * Get the full address as a single string
     * @return string
     */
    public function getFullAddress()
    {
        $parts = array_filter([
            $this->address_building,
            $this->address_street,
            $this->address_area,
            $this->address_city,
        ]);
        return implode('، ', $parts);
    }

    /**
     * Get Google Maps URL for this location
     * @return string|null
     */
    public function getMapUrl()
    {
        if ($this->latitude && $this->longitude) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }
        return null;
    }

    /**
     * Get number of linked customers
     * @return int
     */
    public function getCustomersCount()
    {
        return (int) \backend\modules\customers\models\Customers::find()
            ->where(['job_title' => $this->id])
            ->count();
    }
}
