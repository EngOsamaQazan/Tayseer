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
 * @property string $name_ar
 * @property string|null $name_en
 * @property int|null $parent_id
 * @property string $type
 * @property string $nature
 * @property int $level
 * @property int $is_parent
 * @property int $is_active
 * @property int|null $company_id
 * @property float $opening_balance
 * @property string|null $description
 * @property int|null $created_by
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property Account $parent
 * @property Account[] $children
 */
class Account extends ActiveRecord
{
    const TYPE_ASSETS = 'assets';
    const TYPE_LIABILITIES = 'liabilities';
    const TYPE_EQUITY = 'equity';
    const TYPE_REVENUE = 'revenue';
    const TYPE_EXPENSES = 'expenses';

    const NATURE_DEBIT = 'debit';
    const NATURE_CREDIT = 'credit';

    public static function tableName()
    {
        return '{{%accounts}}';
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
            [['code', 'name_ar', 'type', 'nature'], 'required'],
            [['parent_id', 'level', 'is_parent', 'is_active', 'company_id', 'created_by'], 'integer'],
            [['opening_balance'], 'number'],
            [['description'], 'string'],
            [['code'], 'string', 'max' => 20],
            [['name_ar', 'name_en'], 'string', 'max' => 255],
            [['code'], 'unique'],
            [['type'], 'in', 'range' => array_keys(self::getTypes())],
            [['nature'], 'in', 'range' => [self::NATURE_DEBIT, self::NATURE_CREDIT]],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => self::class, 'targetAttribute' => ['parent_id' => 'id']],
            [['is_parent'], 'default', 'value' => 0],
            [['is_active'], 'default', 'value' => 1],
            [['level'], 'default', 'value' => 1],
            [['opening_balance'], 'default', 'value' => 0],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'م',
            'code' => 'رقم الحساب',
            'name_ar' => 'اسم الحساب',
            'name_en' => 'Account Name (EN)',
            'parent_id' => 'الحساب الرئيسي',
            'type' => 'نوع الحساب',
            'nature' => 'طبيعة الحساب',
            'level' => 'المستوى',
            'is_parent' => 'حساب رئيسي',
            'is_active' => 'فعال',
            'company_id' => 'الشركة',
            'opening_balance' => 'الرصيد الافتتاحي',
            'description' => 'الوصف',
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
        return $this->hasMany(self::class, ['parent_id' => 'id'])->orderBy(['code' => SORT_ASC]);
    }

    public static function getTypes()
    {
        return [
            self::TYPE_ASSETS => 'أصول',
            self::TYPE_LIABILITIES => 'خصوم',
            self::TYPE_EQUITY => 'حقوق ملكية',
            self::TYPE_REVENUE => 'إيرادات',
            self::TYPE_EXPENSES => 'مصروفات',
        ];
    }

    public static function getNatures()
    {
        return [
            self::NATURE_DEBIT => 'مدين',
            self::NATURE_CREDIT => 'دائن',
        ];
    }

    public function getTypeBadge()
    {
        $colors = [
            self::TYPE_ASSETS => 'primary',
            self::TYPE_LIABILITIES => 'danger',
            self::TYPE_EQUITY => 'info',
            self::TYPE_REVENUE => 'success',
            self::TYPE_EXPENSES => 'warning',
        ];
        $color = $colors[$this->type] ?? 'default';
        $label = self::getTypes()[$this->type] ?? $this->type;
        $map = ['info' => 'badge bg-info', 'warning' => 'badge bg-warning text-dark', 'success' => 'badge bg-success', 'danger' => 'badge bg-danger', 'default' => 'badge bg-secondary', 'primary' => 'badge bg-primary'];
        $bc = $map[$color] ?? 'badge bg-secondary';
        return '<span class="' . $bc . '">' . $label . '</span>';
    }

    /**
     * Returns a flat list for Select2 dropdown with indentation.
     */
    public static function getDropdownList($companyId = null, $excludeId = null)
    {
        $query = self::find()->where(['is_active' => 1])->orderBy(['code' => SORT_ASC]);
        if ($companyId) {
            $query->andWhere(['or', ['company_id' => $companyId], ['company_id' => null]]);
        }
        if ($excludeId) {
            $query->andWhere(['!=', 'id', $excludeId]);
        }
        $accounts = $query->all();
        $result = [];
        foreach ($accounts as $account) {
            $indent = str_repeat('— ', max(0, $account->level - 1));
            $result[$account->id] = $indent . $account->code . ' ' . $account->name_ar;
        }
        return $result;
    }

    /**
     * Returns parent accounts only (for parent dropdown).
     */
    public static function getParentDropdownList($excludeId = null)
    {
        $query = self::find()
            ->where(['is_active' => 1, 'is_parent' => 1])
            ->orWhere(['is_active' => 1])
            ->orderBy(['code' => SORT_ASC]);
        if ($excludeId) {
            $query->andWhere(['!=', 'id', $excludeId]);
        }
        $accounts = $query->all();
        $result = [];
        foreach ($accounts as $account) {
            $indent = str_repeat('— ', max(0, $account->level - 1));
            $result[$account->id] = $indent . $account->code . ' ' . $account->name_ar;
        }
        return $result;
    }

    /**
     * Returns leaf accounts only (for journal entry lines).
     */
    public static function getLeafAccounts($companyId = null)
    {
        $query = self::find()
            ->where(['is_active' => 1, 'is_parent' => 0])
            ->orderBy(['code' => SORT_ASC]);
        if ($companyId) {
            $query->andWhere(['or', ['company_id' => $companyId], ['company_id' => null]]);
        }
        return ArrayHelper::map($query->all(), 'id', function ($model) {
            return $model->code . ' - ' . $model->name_ar;
        });
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($this->parent_id) {
            $parent = self::findOne($this->parent_id);
            if ($parent) {
                $this->level = $parent->level + 1;
                $this->type = $parent->type;
                $this->nature = $parent->nature;
            }
        }
        return true;
    }
}
