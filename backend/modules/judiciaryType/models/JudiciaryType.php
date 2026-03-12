<?php

namespace backend\modules\judiciaryType\models;

use Yii;

/**
 * This is the model class for table "os_judiciary_type".
 *
 * @property int $id
 * @property string $name
 *
 * @property Judiciary[] $judiciaries
 */
class JudiciaryType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_judiciary_type';
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

    /**
     * Gets query for [[Judiciaries]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getJudiciaries()
    {
        return $this->hasMany(Judiciary::className(), ['type_id' => 'id']);
    }
}
