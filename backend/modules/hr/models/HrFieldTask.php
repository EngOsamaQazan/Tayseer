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
 * This is the model class for table "{{%hr_field_task}}".
 * المهام الميدانية
 *
 * @property int $id
 * @property string|null $title
 * @property string|null $description
 * @property int|null $assigned_to
 * @property int|null $customer_id
 * @property int|null $contract_id
 * @property string|null $task_date
 * @property string|null $priority
 * @property string|null $status
 * @property string|null $target_lat
 * @property string|null $target_lng
 * @property string|null $target_address
 * @property string|null $completed_at
 * @property string|null $notes
 * @property int $is_deleted
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $updated_by
 */
class HrFieldTask extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_field_task}}';
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
            [['assigned_to', 'customer_id', 'contract_id', 'is_deleted', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['title', 'target_address'], 'string', 'max' => 255],
            [['description', 'notes'], 'string'],
            [['task_date', 'completed_at'], 'safe'],
            [['priority'], 'string', 'max' => 20],
            [['status'], 'string', 'max' => 30],
            [['target_lat', 'target_lng'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'المعرف'),
            'title' => Yii::t('app', 'عنوان المهمة'),
            'description' => Yii::t('app', 'الوصف'),
            'assigned_to' => Yii::t('app', 'مُسندة إلى'),
            'customer_id' => Yii::t('app', 'العميل'),
            'contract_id' => Yii::t('app', 'العقد'),
            'task_date' => Yii::t('app', 'تاريخ المهمة'),
            'priority' => Yii::t('app', 'الأولوية'),
            'status' => Yii::t('app', 'الحالة'),
            'target_lat' => Yii::t('app', 'خط عرض الهدف'),
            'target_lng' => Yii::t('app', 'خط طول الهدف'),
            'target_address' => Yii::t('app', 'عنوان الهدف'),
            'completed_at' => Yii::t('app', 'تاريخ الإكمال'),
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
    public function getAssignedTo()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'assigned_to']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(\backend\modules\customers\models\Customers::class, ['id' => 'customer_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContract()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'contract_id'])
            ->from('os_contract');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEvents()
    {
        return $this->hasMany(HrFieldEvent::class, ['task_id' => 'id']);
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
