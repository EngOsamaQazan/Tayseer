<?php

namespace backend\modules\sms\models;

use backend\modules\customers\models\Customers;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;
/**
 * This is the model class for table "os_sms".
 *
 * @property int $id
 * @property string|null $date
 * @property string|null $massage
 * @property int|null $is_send
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $created_by
 * @property int|null $last_updated_by
 * @property int|null $is_deleted
 * @property int|null $contract_id
 * @property int|null $customers_id
 * @property int|null $type
 */
class Sms extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_sms';
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
            [['massage','customers_id'],'string'],
            [['massage'],'required'],
            [['date'], 'safe'],
            [['is_send','created_at','updated_at','created_by','last_updated_by','is_deleted','type','contract_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date' => 'Date',
            'is_send' => 'Is Send',
        ];
    }
    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::className());
        return $query->notDeleted();
    }
    public function getCustomer()
    {
        return $this->hasMany(Customers::className(), ['customers_id' => 'id']);
    }
}
