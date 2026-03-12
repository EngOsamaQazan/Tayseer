<?php

namespace backend\modules\companyBanks\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;
use backend\modules\bancks\models\Bancks;
/**
 * This is the model class for table "os_company_banks".
 *
 * @property int $id
 * @property int|null $company_id
 * @property integer $bank_id
 * @property string $bank_number
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $created_by
 * @property int|null $last_updated_by
 * @property int|null $is_deleted
 * @property string|null $iban_number
 */
class CompanyBanks extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_company_banks';
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
            [['company_id', 'created_at', 'updated_at', 'created_by', 'last_updated_by', 'is_deleted','bank_id'], 'integer'],
            [['bank_id', 'bank_number'], 'required'],
            [['bank_number', 'iban_number'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'company_id' => 'Company ID',
            'bank_name' => 'Bank Name',
            'bank_number' => 'Bank Number',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'created_by' => 'Created By',
            'last_updated_by' => 'Last Updated By',
            'is_deleted' => 'Is Deleted',
        ];
    }
    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::className());
        return $query;
    }

    public function getBank(){
        return $this->hasOne(Bancks::className(), ['id' => 'bank_id']);
    }
}
