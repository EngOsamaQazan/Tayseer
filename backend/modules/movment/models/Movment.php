<?php

namespace backend\modules\movment\models;

use backend\modules\contracts\models\Contracts;
use backend\modules\installment\models\Installment;
use \common\models\User;
use Yii;


/**
 * This is the model class for table "os_movment".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int $movement_number
 * @property int $bank_receipt_number
 * @property int $financial_value
 * @property string $receipt_image
 *
 * @property User $user
 */
class Movment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_movment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'movement_number', 'bank_receipt_number', 'financial_value'], 'integer'],
            [['movement_number', 'bank_receipt_number', 'receipt_image','financial_value'], 'required'],
            [['financial_value' ], function($attribute, $params)
            {
                $installmentAmmount = Installment::ammoutsDate();
                $contractsFirstInstallmentValue = Contracts::firstInstallmentValue();
                $totle = $installmentAmmount + $contractsFirstInstallmentValue;

                if ($this->financial_value != $totle) {
                    $this->addError($attribute,'The monetary value is not equal to the real money value');
                }
            }],
            [['receipt_image'], 'file', 'extensions' => 'png, jpg'],
        ];
    }



    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'movement_number' => Yii::t('app', 'Movement Number'),
            'bank_receipt_number' => Yii::t('app', 'Bank Receipt Number'),
            'financial_value' => Yii::t('app', 'Financial Value'),
            'receipt_image' => Yii::t('app', 'Receipt Image'),
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
