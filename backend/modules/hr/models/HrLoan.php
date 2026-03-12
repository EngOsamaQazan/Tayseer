<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

/**
 * This is the model class for table "{{%hr_loan}}".
 * سلف الموظفين
 *
 * @property int $id
 * @property int $user_id
 * @property float $amount
 * @property float $monthly_deduction
 * @property int $installments
 * @property int|null $remaining_installments
 * @property float|null $repaid
 * @property string|null $start_date
 * @property string|null $loan_type
 * @property string|null $status
 * @property int|null $approved_by
 * @property string|null $approved_at
 * @property string|null $notes
 * @property int $is_deleted
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 */
class HrLoan extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_loan}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::class,
                'softDeleteAttributeValues' => [
                    'is_deleted' => true,
                ],
                'replaceRegularDelete' => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'amount', 'monthly_deduction', 'installments', 'remaining_installments', 'start_date'], 'required'],
            [['user_id', 'installments', 'remaining_installments', 'approved_by', 'approved_at', 'is_deleted', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['amount', 'monthly_deduction', 'repaid'], 'number'],
            [['start_date'], 'safe'],
            [['loan_type'], 'in', 'range' => ['advance', 'loan', 'penalty']],
            [['status'], 'in', 'range' => ['pending', 'active', 'completed', 'cancelled']],
            [['notes'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'المعرف'),
            'user_id' => Yii::t('app', 'الموظف'),
            'amount' => Yii::t('app', 'مبلغ السلفة'),
            'monthly_deduction' => Yii::t('app', 'الخصم الشهري'),
            'installments' => Yii::t('app', 'عدد الأقساط'),
            'remaining_installments' => Yii::t('app', 'الأقساط المتبقية'),
            'repaid' => Yii::t('app', 'المبلغ المسدد'),
            'start_date' => Yii::t('app', 'تاريخ البدء'),
            'loan_type' => Yii::t('app', 'نوع السلفة'),
            'status' => Yii::t('app', 'الحالة'),
            'approved_by' => Yii::t('app', 'اعتمد بواسطة'),
            'approved_at' => Yii::t('app', 'تاريخ الاعتماد'),
            'notes' => Yii::t('app', 'ملاحظات'),
            'is_deleted' => Yii::t('app', 'محذوف'),
            'created_at' => Yii::t('app', 'تاريخ الإنشاء'),
            'created_by' => Yii::t('app', 'أنشئ بواسطة'),
            'updated_at' => Yii::t('app', 'تاريخ التعديل'),
            'updated_by' => Yii::t('app', 'عُدّل بواسطة'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'user_id']);
    }

    /**
     * {@inheritdoc}
     */
    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::class);
        return $query->notDeleted();
    }
}
