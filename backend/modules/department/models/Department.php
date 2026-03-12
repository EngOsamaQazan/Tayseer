<?php

namespace backend\modules\department\models;

use Yii;
use common\models\Model;
/**
 * This is the model class for table "{{%department}}".
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property int|null $lead_by
 * @property string $status
 * @property int $created_by
 * @property int $created_at
 * @property int|null $updated_at
 *
 * @property User $createdBy
 * @property User $leadBy
 * @property Profile[] $profiles
 */
class Department extends Model
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%department}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['lead_by', 'created_by', 'created_at', 'updated_at'], 'integer'],
            [['status'], 'string'],
            [['title'], 'string', 'max' => 50],
            [['description'], 'string', 'max' => 250],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['created_by' => 'id']],
            [['lead_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['lead_by' => 'id']],
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
            'description' => Yii::t('app', 'Description'),
            'lead_by' => Yii::t('app', 'Lead By'),
            'status' => Yii::t('app', 'Status'),
            'created_by' => Yii::t('app', 'Created By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery|yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(\common\models\User::className(), ['id' => 'created_by']);
    }

    /**
     * Gets query for [[LeadBy]].
     *
     * @return \yii\db\ActiveQuery|yii\db\ActiveQuery
     */
    public function getLeadBy()
    {
        return $this->hasOne(\common\models\User::className(), ['id' => 'lead_by']);
    }

    /**
     * Gets query for [[Profiles]].
     *
     * @return \yii\db\ActiveQuery|ProfileQuery
     */
    public function getProfiles()
    {
        return $this->hasMany(Profile::className(), ['department' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return DepartmentQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new DepartmentQuery(get_called_class());
    }
}
