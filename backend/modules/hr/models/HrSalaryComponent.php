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
 * This is the model class for table "{{%hr_salary_component}}".
 * مكونات الراتب
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $component_type
 * @property int|null $is_taxable
 * @property int|null $is_fixed
 * @property string|null $calculation_formula
 * @property int|null $sort_order
 * @property string|null $description
 * @property int $is_deleted
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 */
class HrSalaryComponent extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_salary_component}}';
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
            [['code', 'name', 'component_type'], 'required'],
            [['is_taxable', 'is_fixed', 'sort_order', 'is_deleted', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['code'], 'string', 'max' => 30],
            [['name'], 'string', 'max' => 100],
            [['component_type'], 'string', 'max' => 30],
            [['calculation_formula'], 'string', 'max' => 500],
            [['description'], 'string'],
            [['code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'المعرف'),
            'code' => Yii::t('app', 'الرمز'),
            'name' => Yii::t('app', 'الاسم'),
            'component_type' => Yii::t('app', 'نوع المكون'),
            'is_taxable' => Yii::t('app', 'خاضع للضريبة'),
            'is_fixed' => Yii::t('app', 'ثابت'),
            'calculation_formula' => Yii::t('app', 'صيغة الحساب'),
            'sort_order' => Yii::t('app', 'ترتيب العرض'),
            'description' => Yii::t('app', 'الوصف'),
            'is_deleted' => Yii::t('app', 'محذوف'),
            'created_at' => Yii::t('app', 'تاريخ الإنشاء'),
            'created_by' => Yii::t('app', 'أنشئ بواسطة'),
            'updated_at' => Yii::t('app', 'تاريخ التعديل'),
            'updated_by' => Yii::t('app', 'عُدّل بواسطة'),
        ];
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
