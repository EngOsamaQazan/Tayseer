<?php

namespace backend\modules\customers\models;

use Yii;
use \backend\modules\contracts\models\Contracts;
/**
 * This is the model class for table "{{%contracts_customers}}".
 *
 * @property int $id
 * @property int $contract_id
 * @property int $customer_id
 * @property string $customer_type
 *
 * @property Contracts $contract
 * @property Customers $customer
 */
class ContractsCustomers extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%contracts_customers}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contract_id', 'customer_id', 'customer_type'], 'required'],
            [['contract_id', 'customer_id'], 'integer'],
            [['customer_type'], 'string'],
            [['contract_id'], 'exist', 'skipOnError' => true, 'targetClass' => \backend\modules\contracts\models\Contracts::className(), 'targetAttribute' => ['contract_id' => 'id']],
            [['customer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Customers::className(), 'targetAttribute' => ['customer_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'contract_id' => Yii::t('app', 'Contract ID'),
            'customer_id' => Yii::t('app', 'Customer ID'),
            'customer_type' => Yii::t('app', 'Customer Type'),
        ];
    }

    /**
     * Gets query for [[Contract]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getContract()
    {
        return $this->hasOne(\backend\modules\contracts\models\Contracts::className(), ['id' => 'contract_id']);
    }

    /**
     * Gets query for [[Customer]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(Customers::className(), ['id' => 'customer_id']);
    }
    /**
     * Gets query for [[Customer]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCustomers()
    {
        return $this->hasMany(Customers::className(), ['id' => 'customer_id']);
    }
    public function getPhoneNumbers()
    {
        return $this->hasMany(\backend\modules\phoneNumbers\models\PhoneNumbers::className(), ['id' => 'customer_id']);
    }
}
