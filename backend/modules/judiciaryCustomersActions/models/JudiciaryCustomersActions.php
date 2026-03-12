<?php

namespace backend\modules\judiciaryCustomersActions\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use common\models\User;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

/**
 * This is the model class for table "os_judiciary_customers_actions".
 *
 * @property int $id
 * @property int $judiciary_id
 * @property int $customers_id
 * @property int $judiciary_actions_id
 * @property string|null $note
 * @property int $created_at
 * @property int $updated_at
 * @property int $created_by
 * @property int $last_update_by
 * @property int $is_deleted
 * @property string $action_date
 * @property int|null $parent_id
 * @property string|null $request_status  (pending|approved|rejected)
 * @property string|null $decision_text
 * @property string|null $decision_file
 * @property int $is_current
 * @property float|null $amount
 * @property string|null $request_target  (judge|accounting|other)
 */
class JudiciaryCustomersActions extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public $year;
    public $form_action_date;
    public $to_action_date;
    public $from_create_at;
    public $to_create_at;
    public $court_name;
    public $contract_id;
    public $lawyer_name;
    public $number_row;

    public static function tableName()
    {
        return 'os_judiciary_customers_actions';
    }

    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'last_update_by',
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
            [['judiciary_id', 'customers_id', 'judiciary_actions_id', 'action_date'], 'required'],
            [['judiciary_id', 'customers_id', 'created_at', 'updated_at', 'created_by', 'last_update_by', 'is_deleted', 'court_name', 'contract_id', 'lawyer_name'], 'integer'],
            [['judiciary_actions_id'], 'integer', 'on' => 'create'],
            [['judiciary_actions_id'], 'integer', 'on' => 'update'],
            [['number_row'], 'integer'],
            [['judiciary_actions_id'], 'integer', 'on' => 'update-followup-judicary-custamer-action'],
            [['judiciary_actions_id'], 'integer', 'on' => 'create-followup-judicary-custamer-action'],
            [['note', 'action_date', 'year', 'form_action_date', 'to_action_date'], 'string'],
            [['image'], 'string'],
            [['image'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, gif'],
            [['parent_id', 'is_current'], 'integer'],
            [['request_status'], 'in', 'range' => ['pending', 'approved', 'rejected'], 'skipOnEmpty' => true],
            [['request_target'], 'in', 'range' => ['judge', 'accounting', 'other'], 'skipOnEmpty' => true],
            [['decision_text'], 'string'],
            [['decision_file'], 'string', 'max' => 255],
            [['amount'], 'number'],
            [['parent_id', 'request_status', 'decision_text', 'decision_file', 'is_current', 'amount', 'request_target'], 'safe'],
        ];
    }

    /**
     * Convert empty strings to null for nullable fields before saving
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $nullableFields = ['request_status', 'request_target', 'decision_text', 'decision_file', 'parent_id', 'amount'];
        foreach ($nullableFields as $field) {
            if ($this->$field === '' || $this->$field === null) {
                $this->$field = null;
            }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'judiciary_id' => Yii::t('app', 'Judiciary ID'),
            'customers_id' => Yii::t('app', 'Customers ID'),
            'judiciary_actions_id' => Yii::t('app', 'Judiciary Actions ID'),
            'note' => Yii::t('app', 'Note'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'created_by' => Yii::t('app', 'Created By'),
            'last_update_by' => Yii::t('app', 'Last Update By'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
            'contract_id' => Yii::t('app', 'Contract Id'),
            'contract_not_in_status' => Yii::t('app', 'Contract Not In Status'),
            'number_row' => Yii::t('app', 'Number Row'),
            'lawyer_name' => Yii::t('app', 'Lawyer Name'),
            'court_name' => Yii::t('app', 'Court Name'),
            'year' => Yii::t('app', 'Year'),
            'image' => Yii::t('app', 'صورة مرفقة'),
        ];
    }

    public function getCustomers()
    {
        return $this->hasOne(\backend\modules\customers\models\Customers::className(), ['id' => 'customers_id']);
    }

    public function getJudiciaryActions()
    {
        return $this->hasOne(\backend\modules\judiciaryActions\models\JudiciaryActions::className(), ['id' => 'judiciary_actions_id']);
    }

    public function getJudiciary()
    {
        return $this->hasOne(\backend\modules\judiciary\models\Judiciary::className(), ['id' => 'judiciary_id']);
    }

    public function getCreatedBy()
    {
        return $this->hasOne(\common\models\User::className(), ['id' => 'created_by']);
    }

    public function getContract()
    {
        return $this->hasOne(\backend\modules\judiciaryContracts\models\JudiciaryContracts::className(), ['id' => 'contract_id']);
    }

    /**
     * Parent action (e.g., the request this document belongs to)
     */
    public function getParentAction()
    {
        return $this->hasOne(self::class, ['id' => 'parent_id']);
    }

    /**
     * Child actions (e.g., documents under this request, or statuses under this document)
     */
    public function getChildActions()
    {
        return $this->hasMany(self::class, ['parent_id' => 'id'])
            ->andWhere(['is_deleted' => 0])
            ->orderBy(['action_date' => SORT_ASC]);
    }

    /**
     * Get request status label in Arabic
     */
    public function getRequestStatusLabel()
    {
        $map = [
            'pending'  => 'معلق',
            'approved' => 'موافقة',
            'rejected' => 'مرفوض',
        ];
        return $map[$this->request_status] ?? '';
    }

    /**
     * Get request status color
     */
    public function getRequestStatusColor()
    {
        $map = [
            'pending'  => '#F59E0B',
            'approved' => '#10B981',
            'rejected' => '#EF4444',
        ];
        return $map[$this->request_status] ?? '#6B7280';
    }

    /**
     * Returns a full web-accessible URL for the image field.
     * Handles bare filenames (old records), relative paths, and full URLs.
     */
    public static function resolveImageUrl($imagePath)
    {
        if (empty($imagePath)) {
            return '';
        }
        if (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0) {
            return $imagePath;
        }
        $clean = ltrim($imagePath, '/\\');
        if (strpos($clean, 'uploads/') !== 0) {
            $onDisk = Yii::getAlias('@webroot/uploads/judiciary_customers_actions/' . $clean);
            if (file_exists($onDisk)) {
                $clean = 'uploads/judiciary_customers_actions/' . $clean;
            }
        }
        return Yii::getAlias('@web') . '/' . $clean;
    }

    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::className());
        return $query->notDeleted();
    }
}
