<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "os_profile".
 *
 * @property int $user_id
 * @property string|null $name
 * @property string|null $public_email
 * @property string|null $gravatar_email
 * @property string|null $gravatar_id
 * @property int|null $location
 * @property string|null $website
 * @property string|null $bio
 * @property string|null $timezone
 * @property string|null $middle_name
 * @property string|null $last_name
 * @property string $employee_type
 * @property string $employee_status
 * @property string|null $date_of_hire
 * @property int|null $department
 * @property int|null $job_title
 * @property int|null $reporting_to
 * @property string|null $mobile
 * @property int|null $nationality
 * @property string $gender
 * @property string $marital_status
 */
class Profile extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_profile';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'employee_type', 'employee_status', 'gender', 'marital_status'], 'required'],
            [['user_id', 'location', 'department', 'job_title', 'reporting_to', 'nationality'], 'integer'],
            [['bio', 'employee_type', 'employee_status', 'gender', 'marital_status'], 'string'],
            [['date_of_hire'], 'safe'],
            [['name', 'public_email', 'gravatar_email', 'website', 'middle_name', 'last_name', 'mobile'], 'string', 'max' => 255],
            [['gravatar_id'], 'string', 'max' => 32],
            [['timezone'], 'string', 'max' => 40],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'user_id' => Yii::t('app', 'User ID'),
            'name' => Yii::t('app', 'Name'),
            'public_email' => Yii::t('app', 'Public Email'),
            'gravatar_email' => Yii::t('app', 'Gravatar Email'),
            'gravatar_id' => Yii::t('app', 'Gravatar ID'),
            'location' => Yii::t('app', 'Location'),
            'website' => Yii::t('app', 'Website'),
            'bio' => Yii::t('app', 'Bio'),
            'timezone' => Yii::t('app', 'Timezone'),
            'middle_name' => Yii::t('app', 'Middle Name'),
            'last_name' => Yii::t('app', 'Last Name'),
            'employee_type' => Yii::t('app', 'Employee Type'),
            'employee_status' => Yii::t('app', 'Employee Status'),
            'date_of_hire' => Yii::t('app', 'Date Of Hire'),
            'department' => Yii::t('app', 'Department'),
            'job_title' => Yii::t('app', 'Job Title'),
            'reporting_to' => Yii::t('app', 'Reporting To'),
            'mobile' => Yii::t('app', 'Mobile'),
            'nationality' => Yii::t('app', 'Nationality'),
            'gender' => Yii::t('app', 'Gender'),
            'marital_status' => Yii::t('app', 'Marital Status'),
        ];
    }
}
