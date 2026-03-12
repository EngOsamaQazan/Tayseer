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
 * This is the model class for table "{{%hr_employee_document}}".
 * مستندات الموظف
 *
 * @property int $id
 * @property int $user_id
 * @property string $doc_type
 * @property string $doc_name
 * @property string $file_path
 * @property string|null $doc_number
 * @property string|null $issue_date
 * @property string|null $expiry_date
 * @property int|null $verified_by
 * @property string|null $verified_at
 * @property string|null $notes
 * @property int $is_deleted
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 */
class HrEmployeeDocument extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_employee_document}}';
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
            [['user_id', 'doc_type', 'doc_name', 'file_path'], 'required'],
            [['user_id', 'verified_by', 'is_deleted', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['doc_type', 'doc_name'], 'string', 'max' => 100],
            [['file_path'], 'string', 'max' => 500],
            [['doc_number'], 'string', 'max' => 50],
            [['issue_date', 'expiry_date', 'verified_at'], 'safe'],
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
            'doc_type' => Yii::t('app', 'نوع المستند'),
            'doc_name' => Yii::t('app', 'اسم المستند'),
            'file_path' => Yii::t('app', 'مسار الملف'),
            'doc_number' => Yii::t('app', 'رقم المستند'),
            'issue_date' => Yii::t('app', 'تاريخ الإصدار'),
            'expiry_date' => Yii::t('app', 'تاريخ الانتهاء'),
            'verified_by' => Yii::t('app', 'تم التحقق بواسطة'),
            'verified_at' => Yii::t('app', 'تاريخ التحقق'),
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
    public function getVerifier()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'verified_by']);
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
