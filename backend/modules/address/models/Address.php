<?php

namespace backend\modules\address\models;

use Yii;

/**
 * This is the model class for table "{{%address}}".
 *
 * @property int $id
 * @property int $customers_id
 * @property string $address
 * @property string|null $address_city
 * @property string|null $address_area
 * @property string|null $address_street
 * @property string|null $address_building
 * @property string|null $postal_code
 * @property string|null $plus_code
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string $created_at
 * @property string $updated_at
 * @property int $is_deleted
 * @property int $address_type
 *
 * @property Customers $customers
 */
class Address extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%address}}';
    }

    public function rules()
    {
        return [
            [['is_deleted', 'customers_id'], 'integer'],
            [['address_type'], 'required'],
            [['address_type'], 'integer', 'max' => 11],
            [['updated_at'], 'safe'],
            [['address', 'address_city', 'address_area', 'address_building'], 'string', 'max' => 255],
            [['address_street'], 'string', 'max' => 500],
            [['postal_code', 'plus_code'], 'string', 'max' => 20],
            [['latitude', 'longitude'], 'number'],
            [['address_city', 'address_area', 'address_street', 'address_building', 'postal_code', 'plus_code', 'latitude', 'longitude', 'address'], 'safe'],
            [['customers_id'], 'exist', 'skipOnError' => true, 'targetClass' => \backend\modules\customers\models\Customers::className(), 'targetAttribute' => ['customers_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'customers_id' => Yii::t('app', 'Customers ID'),
            'address_type' => Yii::t('app', 'النوع'),
            'address' => Yii::t('app', 'العنوان'),
            'address_city' => Yii::t('app', 'المدينة'),
            'address_area' => Yii::t('app', 'المنطقة/الحي'),
            'address_street' => Yii::t('app', 'الشارع/العنوان التفصيلي'),
            'address_building' => Yii::t('app', 'المبنى/الطابق'),
            'postal_code' => Yii::t('app', 'الرمز البريدي'),
            'plus_code' => Yii::t('app', 'Plus Code'),
            'latitude' => Yii::t('app', 'خط العرض'),
            'longitude' => Yii::t('app', 'خط الطول'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
        ];
    }

    public function getCustomers()
    {
        return $this->hasOne(Customers::className(), ['id' => 'customers_id']);
    }

    /**
     * Builds a human-readable full address string from structured fields.
     */
    public function getFullAddress()
    {
        $parts = array_filter([
            $this->address_building,
            $this->address_street,
            $this->address_area,
            $this->address_city,
        ]);
        return $parts ? implode('، ', $parts) : $this->address;
    }

    public function getMapUrl()
    {
        if ($this->latitude && $this->longitude) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }
        return null;
    }

    public static function find()
    {
        return new AddressQuery(get_called_class());
    }
}
