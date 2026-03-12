<?php

namespace backend\modules\followUp\models;

use Yii;

/**
 * This is the model class for table "{{%follow_up_connection_reports}}".
 *
 * @property int $os_follow_up_id
 * @property int $customer_name
 * @property int $connection_type
 * @property int $connection_response
 * @property int $note
 *
 * @property FollowUp $osFollowUp
 */
class FollowUpConnectionReports extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%follow_up_connection_reports}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['os_follow_up_id', 'customer_name', 'connection_type','connection_response', 'note'], 'required'],
            [['os_follow_up_id'], 'integer'],
            [['customer_name', 'connection_type', 'note', 'connection_response'], 'string'],
            [['os_follow_up_id'], 'exist', 'skipOnError' => true, 'targetClass' => FollowUp::className(), 'targetAttribute' => ['os_follow_up_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'os_follow_up_id' => Yii::t('app', 'Os Follow Up ID'),
            'customer_name' => Yii::t('app', 'Customer Name'),
            'connection_type' => Yii::t('app', 'Connection Type'),
            'connection_response' => Yii::t('app', 'Connection Response'),
            'note' => Yii::t('app', 'Note'),
        ];
    }

    /**
     * Gets query for [[OsFollowUp]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOsFollowUp()
    {
        return $this->hasOne(FollowUp::className(), ['id' => 'os_follow_up_id']);
    }
}
