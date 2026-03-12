<?php

namespace backend\modules\rejesterFollowUpType\models;

use Yii;

/**
 * This is the model class for table "os_rejester_follow_up_type".
 *
 * @property int $id
 * @property string $name
 */
class RejesterFollowUpType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_rejester_follow_up_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
        ];
    }
}
