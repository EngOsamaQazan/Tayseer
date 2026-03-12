<?php

namespace backend\modules\location\models;

use Yii;
use \common\models\User;
/**
 * This is the model class for table "{{%location}}".
 *
 * @property int $id
 * @property string $location
 * @property string|null $description
 * @property int $longitude
 * @property int $latitude
 * @property int $radius
 * @property string $status
 * @property int $created_by
 * @property int $created_at
 * @property int|null $updated_at
 *
 * @property User $createdBy
 * @property Profile[] $profiles
 * @property User[] $users
 */
class Location extends \common\models\Model
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%location}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['location','status'], 'required'],
            [['longitude', 'latitude', 'radius', 'created_by', 'created_at', 'updated_at'], 'integer'],
            [['status'], 'string'],
            [['location'], 'string', 'max' => 50],
            [['description'], 'string', 'max' => 250],
            [['location'], 'unique'],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'location' => Yii::t('app', 'Location'),
            'description' => Yii::t('app', 'Description'),
            'longitude' => Yii::t('app', 'Longitude'),
            'latitude' => Yii::t('app', 'Latitude'),
            'radius' => Yii::t('app', 'Radius'),
            'status' => Yii::t('app', 'Status'),
            'created_by' => Yii::t('app', 'Created By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery|UserQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }

    /**
     * Gets query for [[Profiles]].
     *
     * @return \yii\db\ActiveQuery|ProfileQuery
     */
    public function getProfiles()
    {
        return $this->hasMany(Profile::className(), ['location' => 'id']);
    }

    /**
     * Gets query for [[Users]].
     *
     * @return \yii\db\ActiveQuery|UserQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::className(), ['location' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return LocationQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new LocationQuery(get_called_class());
    }
}
