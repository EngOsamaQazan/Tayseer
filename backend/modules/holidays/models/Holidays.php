<?php

namespace backend\modules\holidays\models;

use Yii;
use \common\models\Model;
/**
 * This is the model class for table "{{%holidays}}".
 *
 * @property int $id
 * @property string $title
 * @property string $start_at
 * @property string $end_at
 * @property int $created_by
 * @property int $created_at
 * @property int|null $updated_at
 */
class Holidays extends Model
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%holidays}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'start_at', 'end_at'], 'required'],
            [['start_at', 'end_at'], 'safe'],
            [['created_by', 'created_at', 'updated_at'], 'integer'],
            [['title'], 'string', 'max' => 50],
            ['start_at', 'compare', 'compareAttribute' => 'end_at', 'operator' => '<=', 'enableClientValidation' => false],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'title' => Yii::t('app', 'Title'),
            'start_at' => Yii::t('app', 'Start At'),
            'end_at' => Yii::t('app', 'End At'),
            'created_by' => Yii::t('app', 'Created By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
    public function getCreatedBy() {
        return $this->hasOne(\common\models\User::className(), ['id' => 'created_by']);
    }
}
