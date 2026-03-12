<?php

namespace backend\modules\court\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii\behaviors\BlameableBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;
use \common\models\User;
/**
 * This is the model class for table "os_court".
 *
 * @property int $id
 * @property string $name
 * @property int $city
 * @property string $adress
 * @property string $phone_number
 * @property int $created_by
 * @property int $last_updated_by
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $is_deleted
 * @property int|null $number_row
 *
 * @property User $createdBy
 * @property Judiciary[] $judiciaries
 */
class Court extends ActiveRecord
{  public $number_row;
    const CITY = ['إربد','البلقاء','جرش','الزرقاء','الطفيلة','عجلون','العقبة','عمان','الكرك','مادبا','معان','المفرق'];
    /**
     * {@inheritdoc}
     */
    
    public static function tableName()
    {
        return '{{%court}}';
    }
    /**
     * {@inheritdoc}
     */
    
    public function rules()
    {
        return [
            [['name', 'city'], 'required'],
            [['city', 'created_by', 'last_updated_by', 'created_at', 'updated_at', 'is_deleted','number_row'], 'integer'],
            [['name', 'adress', 'phone_number'], 'string', 'max' => 255],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['created_by' => 'id']],
        ];
    }
    
    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'last_updated_by',
            ],
            [
                'class' => TimestampBehavior::className(),
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'is_deleted' => true
                ],
                
              'replaceRegularDelete' => true // mutate native `delete()` method
            ],

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
            'city' => Yii::t('app', 'City'),
            'adress' => Yii::t('app', 'Adress'),
            'phone_number' => Yii::t('app', 'Phone Number'),
            'created_by' => Yii::t('app', 'Created By'),
            'last_updated_by' => Yii::t('app', 'Last Updated By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
        ];
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }

    /**
     * Gets query for [[Judiciaries]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getJudiciaries()
    {
        return $this->hasMany(Judiciary::className(), ['court_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery|SoftDeleteQueryBehavior
     */
    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::className());
         return $query->notDeleted();
    }
}
