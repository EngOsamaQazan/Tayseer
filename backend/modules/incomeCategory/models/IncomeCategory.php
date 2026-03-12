<?php

namespace backend\modules\incomeCategory\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii\behaviors\BlameableBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;
use \common\models\User;
/**
 * This is the model class for table "{{%income_category}}".
 *
 * @property int $id
 * @property string|null $name
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $last_updated_by
 * @property int $is_deleted
 * @property string $description
 *
 * @property User $createdBy
 */
class IncomeCategory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%income_category}}';
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
    public function rules()
    {
        return [
            [['created_at', 'created_by', 'updated_at', 'last_updated_by', 'is_deleted', 'description'], 'required'],
            [['created_at', 'created_by', 'updated_at', 'last_updated_by', 'is_deleted'], 'integer'],
            [['description'], 'string'],
            [['name'], 'string', 'max' => 100],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => \common\models\User::className(), 'targetAttribute' => ['created_by' => 'id']],
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
            'created_at' => Yii::t('app', 'Created At'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'last_updated_by' => Yii::t('app', 'Last Updated By'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
            'description' => Yii::t('app', 'Description'),
        ];
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(\common\models\User::className(), ['id' => 'created_by']);
    }
    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::className());
        return $query->notDeleted();
    }
}
