<?php

namespace backend\modules\phoneNumbers\models;

use Yii;

/**
 * This is the model class for table "{{%phone_numbers}}".
 *
 * @property int $id
 * @property int $customers_id
 * @property string $phone_number
 * @property string $created_at
 * @property string $updated_at
 * @property int $is_deleted
 *
 * @property Customers $customers
 */
class PhoneNumbers extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%phone_numbers}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['owner_name','phone_number_owner','phone_number'],'required'],
            [['customers_id', 'is_deleted'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['phone_number','fb_account'], 'string', 'max' => 255],
            [['phone_number_owner','owner_name'], 'string', 'max' => 100],
            [['customers_id'], 'exist', 'skipOnError' => true, 'targetClass' =>  \backend\modules\customers\models\Customers::className(), 'targetAttribute' => ['customers_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'customers_id' => Yii::t('app', 'Customers ID'),
            'phone_number' => Yii::t('app', 'Phone Number'),
            'phone_number_owner' => Yii::t('app', 'phone number owner'),
            'fb_account' => Yii::t('app', 'facebook account'),
            'owner_name' => Yii::t('app', 'owner name'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) return false;
        if (!empty($this->phone_number)) {
            $this->phone_number = \backend\helpers\PhoneHelper::toE164($this->phone_number);
        }
        return true;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomers()
    {
        return $this->hasOne(\backend\modules\customers\models\Customers::className(), ['id' => 'customers_id']);
    }

    /**
     * {@inheritdoc}
     * @return phoneNumbersQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PhoneNumbersQuery(get_called_class());
    }
}
