<?php

namespace backend\modules\designation\models;
use common\models\Model;
use Yii;
use \common\models\User;

/**
 * This is the model class for table "{{%designation}}".
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property int|null $department_id
 * @property string $status
 * @property int $created_by
 * @property int $created_at
 * @property int|null $updated_at
 *
 * @property User $createdBy
 * @property Department $department
 * @property Profile[] $profiles
 */
class Designation extends Model
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%designation}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'status'], 'required'],
            [['status'], 'string'],
            [['created_by', 'created_at', 'updated_at', 'department_id'], 'integer'],
            [['department_id'], 'default', 'value' => null],
            [['title'], 'string', 'max' => 50],
            [['description'], 'string', 'max' => 250],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'المسمى الوظيفي',
            'description' => 'الوصف',
            'department_id' => 'القسم',
            'status' => 'الحالة',
            'created_by' => 'أنشأه',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'تاريخ التحديث',
        ];
    }

    public function getCreatedBy()
    {
        return $this->hasOne(\common\models\User::className(), ['id' => 'created_by']);
    }

    public function getDepartment()
    {
        return $this->hasOne(\backend\modules\department\models\Department::className(), ['id' => 'department_id']);
    }

    public function getProfiles()
    {
        return $this->hasMany(Profile::className(), ['job_title' => 'id']);
    }

    public static function find()
    {
        return new DesignationQuery(get_called_class());
    }

    /**
     * التأكد من وجود عمود department_id في الجدول — إن لم يكن موجوداً يُنشأ تلقائياً.
     * لا يرمي استثناءً حتى لا يوقف "إنشاء الافتراضية" على سيرفرات بصلاحيات محدودة.
     */
    public static function ensureDepartmentColumn()
    {
        $db = Yii::$app->db;
        $tableName = $db->getSchema()->getRawTableName('{{%designation}}');
        $quotedTable = $db->getSchema()->quoteTableName($tableName);
        try {
            $rows = $db->createCommand("SHOW COLUMNS FROM {$quotedTable} LIKE 'department_id'")->queryAll();
            if (empty($rows)) {
                $db->createCommand("ALTER TABLE {$quotedTable} ADD COLUMN `department_id` INT NULL AFTER `description`")->execute();
            }
            $db->getSchema()->refreshTableSchema('{{%designation}}');
        } catch (\Exception $e) {
            Yii::warning('ensureDepartmentColumn: ' . $e->getMessage(), __METHOD__);
            // عدم إعادة رمي الاستثناء لئلا تتوقف "إنشاء الافتراضية"؛ الإدراج سيجرب بدون department_id إن لزم
        }
    }
}
