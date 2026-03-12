<?php

namespace backend\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * ActiveRecord for os_user_categories.
 *
 * @property int $id
 * @property string $slug
 * @property string $name_ar
 * @property string|null $name_en
 * @property string $icon
 * @property string $color
 * @property int $sort_order
 * @property int|null $company_id
 * @property int $is_system
 * @property int $is_active
 * @property string $created_at
 */
class UserCategory extends ActiveRecord
{
    public static function tableName()
    {
        return 'os_user_categories';
    }

    public function rules()
    {
        return [
            [['slug', 'name_ar'], 'required'],
            [['sort_order', 'company_id', 'is_system', 'is_active'], 'integer'],
            [['slug'], 'string', 'max' => 50],
            [['name_ar', 'name_en'], 'string', 'max' => 100],
            [['icon'], 'string', 'max' => 50],
            [['color'], 'string', 'max' => 20],
            [['created_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'slug' => 'المعرّف',
            'name_ar' => 'الاسم بالعربية',
            'name_en' => 'الاسم بالإنجليزية',
            'icon' => 'الأيقونة',
            'color' => 'اللون',
            'sort_order' => 'الترتيب',
            'company_id' => 'الشركة',
            'is_system' => 'نظامي',
            'is_active' => 'نشط',
        ];
    }

    public function getUserCategoryMaps()
    {
        return $this->hasMany(UserCategoryMap::class, ['category_id' => 'id']);
    }

    public function getUsers()
    {
        return $this->hasMany(\common\models\User::class, ['id' => 'user_id'])
            ->viaTable('os_user_category_map', ['category_id' => 'id']);
    }

    public static function findActive()
    {
        return static::find()->where(['is_active' => 1]);
    }

    public static function getDefaultCategories()
    {
        return [
            ['slug' => 'employee',       'name_ar' => 'موظف',                'name_en' => 'Employee',      'icon' => 'fa-id-badge',  'color' => '#3B82F6', 'sort_order' => 1, 'is_system' => 1],
            ['slug' => 'sales_employee', 'name_ar' => 'موظف مبيعات',         'name_en' => 'Sales Employee','icon' => 'fa-shopping-cart', 'color' => '#0ea5e9', 'sort_order' => 2, 'is_system' => 1],
            ['slug' => 'vendor',         'name_ar' => 'مورد بضائع',          'name_en' => 'Vendor',        'icon' => 'fa-truck',     'color' => '#F59E0B', 'sort_order' => 3, 'is_system' => 1],
            ['slug' => 'investor',       'name_ar' => 'مستثمر (شريك)',       'name_en' => 'Investor',      'icon' => 'fa-briefcase', 'color' => '#8B5CF6', 'sort_order' => 4, 'is_system' => 1],
            ['slug' => 'court_agent',    'name_ar' => 'مندوب محكمة',         'name_en' => 'Court Agent',   'icon' => 'fa-gavel',     'color' => '#800020', 'sort_order' => 5, 'is_system' => 1],
            ['slug' => 'branch_manager', 'name_ar' => 'مدير فرع',            'name_en' => 'Branch Manager','icon' => 'fa-building',  'color' => '#059669', 'sort_order' => 6, 'is_system' => 1],
            ['slug' => 'manager',        'name_ar' => 'مدير',                'name_en' => 'Manager',       'icon' => 'fa-star',      'color' => '#D97706', 'sort_order' => 0, 'is_system' => 1],
        ];
    }

    public static function ensureTablesExist()
    {
        $db = Yii::$app->db;
        $tablePrefix = $db->tablePrefix;

        $db->createCommand("
            CREATE TABLE IF NOT EXISTS os_user_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(50) NOT NULL,
                name_ar VARCHAR(100) NOT NULL,
                name_en VARCHAR(100) NULL,
                icon VARCHAR(50) DEFAULT 'fa-tag',
                color VARCHAR(20) DEFAULT '#64748B',
                sort_order INT DEFAULT 0,
                company_id INT NULL COMMENT 'Multi-tenant: NULL = global',
                is_system TINYINT(1) DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_slug_company (slug, company_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ")->execute();

        $db->createCommand("
            CREATE TABLE IF NOT EXISTS os_user_category_map (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                category_id INT NOT NULL,
                assigned_by INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_user_category (user_id, category_id),
                KEY idx_category (category_id),
                KEY idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ")->execute();
    }

    public static function seedDefaults()
    {
        $db = Yii::$app->db;
        $created = 0;
        foreach (static::getDefaultCategories() as $cat) {
            $exists = $db->createCommand("SELECT COUNT(*) FROM os_user_categories WHERE slug = :slug AND company_id IS NULL", [':slug' => $cat['slug']])->queryScalar();
            if (!$exists) {
                $db->createCommand()->insert('os_user_categories', array_merge($cat, ['company_id' => null, 'is_active' => 1]))->execute();
                $created++;
            } elseif ($cat['slug'] === 'vendor') {
                // توحيد الاسم للموجود مسبقاً
                $db->createCommand()->update('os_user_categories', ['name_ar' => $cat['name_ar'], 'name_en' => $cat['name_en']], ['slug' => 'vendor', 'company_id' => null])->execute();
            }
        }
        return $created;
    }
}
