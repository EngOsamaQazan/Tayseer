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
 * This is the model class for table "{{%hr_employee_salary}}".
 * رواتب الموظفين
 *
 * @property int $id
 * @property int $user_id
 * @property int $component_id
 * @property float $amount
 * @property string $effective_from
 * @property string|null $effective_to
 * @property string|null $currency
 * @property string|null $notes
 * @property int $is_deleted
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 */
class HrEmployeeSalary extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_employee_salary}}';
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
            [['user_id', 'component_id', 'amount', 'effective_from'], 'required'],
            [['user_id', 'component_id', 'is_deleted', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['amount'], 'number'],
            [['effective_from', 'effective_to'], 'safe'],
            // currency column removed from DB schema
            // [['currency'], 'string', 'max' => 10],
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
            'component_id' => Yii::t('app', 'مكون الراتب'),
            'amount' => Yii::t('app', 'المبلغ'),
            'effective_from' => Yii::t('app', 'ساري من'),
            'effective_to' => Yii::t('app', 'ساري إلى'),
            'currency' => Yii::t('app', 'العملة'),
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
     * @return \yii\db\ActiveQuery
     */
    public function getComponent()
    {
        return $this->hasOne(HrSalaryComponent::class, ['id' => 'component_id']);
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
