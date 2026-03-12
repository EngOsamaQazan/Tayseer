<?php

namespace  backend\modules\leavePolicy\models;
use  common\models\Model;
use Yii;

/**
 * This is the model class for table "{{%leave_policy}}".
 *
 * @property int $id
 * @property string $year
 * @property int $leave_type
 * @property int $total_days
 * @property string|null $description
 * @property int $department
 * @property int $designation
 * @property int $location
 * @property string $gender
 * @property string $marital_status
 * @property string $status
 * @property int $created_by
 * @property int $created_at
 * @property int|null $updated_at
 *
 * @property Department $department0
 * @property Designation $designation0
 * @property LeaveTypes $leaveType
 */
class LeavePolicy extends Model
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%leave_policy}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['year','total_days', 'gender', 'marital_status','status','leave_type','title'], 'required'],
            [['department','designation', 'location'], 'default', 'value'=> 0],
            [['year'], 'safe'],
            [['leave_type', 'total_days', 'department', 'designation', 'location', 'created_by', 'created_at', 'updated_at'], 'integer'],
            [['gender', 'marital_status', 'status','title'], 'string'],
            [['description'], 'string', 'max' => 250],
            [['title'], 'string', 'max' => 50],
//            [['designation'], 'exist', 'skipOnError' => true, 'targetClass' => Designation::className(), 'targetAttribute' => ['designation' => 'id']],
//            [['department'], 'exist', 'skipOnError' => true, 'targetClass' => Department::className(), 'targetAttribute' => ['department' => 'id']],
//            [['leave_type'], 'exist', 'skipOnError' => true, 'targetClass' => LeaveTypes::className(), 'targetAttribute' => ['leave_type' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'year' => Yii::t('app', 'Year'),
            'leave_type' => Yii::t('app', 'Leave Type'),
            'total_days' => Yii::t('app', 'Total Days'),
            'description' => Yii::t('app', 'Description'),
            'department' => Yii::t('app', 'Department'),
            'designation' => Yii::t('app', 'Designation'),
            'location' => Yii::t('app', 'Location'),
            'gender' => Yii::t('app', 'Gender'),
            'marital_status' => Yii::t('app', 'Marital Status'),
            'status' => Yii::t('app', 'Status'),
            'created_by' => Yii::t('app', 'Created By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Gets query for [[Department0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDepartment0()
    {
        return $this->hasOne(Department::className(), ['id' => 'department']);
    }

    /**
     * Gets query for [[Designation0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDesignation0()
    {
        return $this->hasOne(Designation::className(), ['id' => 'designation']);
    }

    /**
     * Gets query for [[LeaveType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLeaveType()
    {
        return $this->hasOne(LeaveTypes::className(), ['id' => 'leave_type']);
    }
}
