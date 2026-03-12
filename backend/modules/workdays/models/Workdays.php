<?php

namespace  backend\modules\workdays\models;

use Yii;
use common\models\Model;
/**
 * This is the model class for table "{{%workdays}}".
 *
 * @property int $id
 * @property string $day_name
 * @property string $start_at
 * @property string $end_at
 * @property string $status
 * @property int $created_by
 * @property int $created_at
 * @property int|null $updated_at
 */
class Workdays extends Model {

    /**
     * {@inheritdoc}
     */
    public static function tableName() {
        return '{{%workdays}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules() {
        return [
            [['day_name', 'start_at', 'end_at','status'], 'required'],
            [['day_name', 'status'], 'string'],
            [['start_at', 'end_at'], 'safe'],
            [['created_by', 'created_at', 'updated_at'], 'integer'],
            [['day_name'], 'unique'],
            ['start_at', 'compare', 'compareAttribute' => 'end_at', 'operator' => '<', 'enableClientValidation' => true],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels() {
        return [
            'id' => Yii::t('app', 'ID'),
            'day_name' => Yii::t('app', 'Day Name'),
            'start_at' => Yii::t('app', 'Start At'),
            'end_at' => Yii::t('app', 'End At'),
            'status' => Yii::t('app', 'Status'),
            'created_by' => Yii::t('app', 'Created By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
    public function getCreatedBy() {
        return $this->hasOne(\common\models\User::className(), ['id' => 'created_by']);
    }
}
