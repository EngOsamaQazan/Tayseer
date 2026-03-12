<?php

namespace backend\modules\shares\models;

use Yii;

/**
 * This is the model class for table "{{%shares}}".
 *
 * @property int $id
 * @property int $shareholder_id
 * @property int $amount
 * @property string $notes
 * @property string $receive_number
 * @property string $date
 *
 * @property Shareholders $shareholder
 */
class Shares extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%shares}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shareholder_id', 'amount', 'date'], 'required'],
            [['shareholder_id', 'amount'], 'integer'],
            [['date'], 'safe'],
            [['notes'], 'string', 'max' => 250],
            [['receive_number'], 'string', 'max' => 50],
            [['shareholder_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shareholders::className(), 'targetAttribute' => ['shareholder_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'shareholder_id' => Yii::t('app', 'Shareholder ID'),
            'amount' => Yii::t('app', 'Amount'),
            'notes' => Yii::t('app', 'Notes'),
            'receive_number' => Yii::t('app', 'Receive Number'),
            'date' => Yii::t('app', 'Date'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShareholder()
    {
        return $this->hasOne(Shareholders::className(), ['id' => 'shareholder_id']);
    }

    /**
     * @inheritdoc
     * @return SharesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new SharesQuery(get_called_class());
    }
}
