<?php

namespace backend\modules\accounting\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int|null $parent_id
 * @property int|null $company_id
 * @property int $is_active
 * @property int|null $created_by
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property CostCenter $parent
 * @property CostCenter[] $children
 */
class CostCenter extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%cost_centers}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['code', 'name'], 'required'],
            [['parent_id', 'company_id', 'is_active', 'created_by'], 'integer'],
            [['code'], 'string', 'max' => 20],
            [['name'], 'string', 'max' => 255],
            [['is_active'], 'default', 'value' => 1],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => self::class, 'targetAttribute' => ['parent_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'م',
            'code' => 'رمز المركز',
            'name' => 'اسم المركز',
            'parent_id' => 'المركز الرئيسي',
            'company_id' => 'الشركة',
            'is_active' => 'فعال',
            'created_by' => 'أنشئ بواسطة',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'تاريخ التحديث',
        ];
    }

    public function getParent()
    {
        return $this->hasOne(self::class, ['id' => 'parent_id']);
    }

    public function getChildren()
    {
        return $this->hasMany(self::class, ['parent_id' => 'id']);
    }

    public static function getDropdownList($companyId = null)
    {
        $query = self::find()->where(['is_active' => 1])->orderBy(['code' => SORT_ASC]);
        if ($companyId) {
            $query->andWhere(['or', ['company_id' => $companyId], ['company_id' => null]]);
        }
        return ArrayHelper::map($query->all(), 'id', function ($model) {
            return $model->code . ' - ' . $model->name;
        });
    }
}
