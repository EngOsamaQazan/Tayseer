<?php

namespace backend\models;

use backend\modules\designation\models\Designation;
use backend\modules\department\models\Department;
use backend\modules\location\models\Location;
use backend\modules\leavePolicy\models\LeavePolicy;
use backend\modules\employee\models\EmployeeFiles;
use Yii;
use  yii\helpers\FileHelper;
/**
 * This is the model class for table "{{%user}}".
 *
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $password_hash
 * @property string $auth_key
 * @property int|null $confirmed_at
 * @property string|null $unconfirmed_email
 * @property int|null $blocked_at
 * @property string|null $registration_ip
 * @property int $created_at
 * @property int $updated_at
 * @property int $flags
 * @property int|null $last_login_at
 * @property string|null $verification_token
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
 *
 * @property Department $department0
 * @property Department[] $departments
 * @property Department[] $departments0
 * @property Designation[] $designations
 * @property Employee[] $employees
 * @property Designation $jobTitle
 * @property Location $location0
 * @property Location[] $locations
 * @property Profile $profile
 * @property Profile[] $profiles
 * @property Employee $reportingTo
 * @property SocialAccount[] $socialAccounts
 * @property Token[] $tokens
 */
class Employee extends \common\models\Model
{


    public $credental_sms_send, $credental_email_send;
    public $documents;

    public $profile_avatar_file;
    public $profile_attachment_files;


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'password_hash', 'credental_sms_send', 'credental_email_send', 'date_of_hire', 'name', 'username', 'email', 'employee_type', 'employee_status', 'gender', 'marital_status', 'location', 'department', 'job_title', 'reporting_to', 'nationality', 'middle_name', 'last_name', 'mobile'], 'required', 'on' => 'create'],
            [['username', 'credental_sms_send', 'credental_email_send', 'date_of_hire', 'name', 'username', 'email', 'employee_type', 'employee_status', 'gender', 'marital_status', 'location', 'department', 'job_title', 'reporting_to', 'nationality', 'middle_name', 'last_name', 'mobile'], 'required', 'on' => 'update'],
            [['confirmed_at', 'blocked_at', 'created_at', 'updated_at', 'flags', 'last_login_at', 'location', 'department', 'job_title', 'reporting_to', 'nationality'], 'integer'],
            [['bio', 'employee_type', 'employee_status', 'gender', 'marital_status', 'avatar'], 'string'],
            [['date_of_hire', 'password_hash'], 'safe'],
            [['username', 'email', 'unconfirmed_email', 'verification_token', 'name', 'public_email', 'gravatar_email', 'website', 'middle_name', 'last_name', 'mobile'], 'string', 'max' => 255],
            [['password_hash'], 'string', 'max' => 60],
            [['avatar'], 'string', 'max' => 500],
            [['auth_key', 'gravatar_id'], 'string', 'max' => 32],
            [['registration_ip'], 'string', 'max' => 45],
            [['timezone'], 'string', 'max' => 40],
            [['username'], 'unique'],
            [['email'], 'unique'],
            [['job_title'], 'exist', 'skipOnError' => true, 'targetClass' => Designation::className(), 'targetAttribute' => ['job_title' => 'id']],
            [['reporting_to'], 'exist', 'skipOnError' => true, 'targetClass' => Employee::className(), 'targetAttribute' => ['reporting_to' => 'id']],
            [['department'], 'exist', 'skipOnError' => true, 'targetClass' => Department::className(), 'targetAttribute' => ['department' => 'id']],
            [['location'], 'exist', 'skipOnError' => true, 'targetClass' => Location::className(), 'targetAttribute' => ['location' => 'id']],
            [['profile_avatar_file'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg,jpeg'],
            [['profile_attachment_files'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg', 'maxFiles' => 50],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'username' => Yii::t('app', 'Username'),
            'email' => Yii::t('app', 'Email'),
            'password_hash' => Yii::t('app', 'Password Hash'),
            'auth_key' => Yii::t('app', 'Auth Key'),
            'confirmed_at' => Yii::t('app', 'Confirmed At'),
            'unconfirmed_email' => Yii::t('app', 'Unconfirmed Email'),
            'blocked_at' => Yii::t('app', 'Blocked At'),
            'registration_ip' => Yii::t('app', 'Registration Ip'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'flags' => Yii::t('app', 'Flags'),
            'last_login_at' => Yii::t('app', 'Last Login At'),
            'verification_token' => Yii::t('app', 'Verification Token'),
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
            'avatar' => Yii::t('app', 'Avatar'),
            'profile_attachment_file' => Yii::t('app', 'Profile Attachment File'),
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
     * Gets query for [[Departments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDepartments()
    {
        return $this->hasMany(Department::className(), ['created_by' => 'id']);
    }

    /**
     * Gets query for [[Departments0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDepartments0()
    {
        return $this->hasMany(Department::className(), ['lead_by' => 'id']);
    }

    /**
     * Gets query for [[Designations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDesignations()
    {
        return $this->hasMany(Designation::className(), ['created_by' => 'id']);
    }

    /**
     * Gets query for [[Employees]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEmployees()
    {
        return $this->hasMany(Employee::className(), ['reporting_to' => 'id']);
    }

    /**
     * Gets query for [[JobTitle]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getJobTitle()
    {
        return $this->hasOne(Designation::className(), ['id' => 'job_title']);
    }

    /**
     * Gets query for [[Location0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLocation0()
    {
        return $this->hasOne(Location::className(), ['id' => 'location']);
    }

    /**
     * Gets query for [[Locations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLocations()
    {
        return $this->hasMany(Location::className(), ['created_by' => 'id']);
    }

    /**
     * Gets query for [[Profile]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProfile()
    {
        return $this->hasOne(Profile::className(), ['user_id' => 'id']);
    }

    /**
     * Gets query for [[Profiles]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProfiles()
    {
        return $this->hasMany(Profile::className(), ['reporting_to' => 'id']);
    }

    /**
     * Gets query for [[ReportingTo]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getReportingTo()
    {
        return $this->hasOne(Employee::className(), ['id' => 'reporting_to']);
    }

    /**
     * Gets query for [[SocialAccounts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSocialAccounts()
    {
        return $this->hasMany(SocialAccount::className(), ['user_id' => 'id']);
    }

    /**
     * Gets query for [[SocialAccounts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLeavePolicy()
    {
        //return $this->hasMany(LeavePolicy::className(), ['user_id' => 'id'])->andWhere(['or', ['gender' => 'all'], ['gender' => 'gender']])->andWhere(['or', ['marital_status' => 'all'], ['marital_status' => 'marital_status']])->andFilterWhere(['status' => 'active']);
        $resutl = LeavePolicy::find()->select(['id', 'title']);
        $resutl->andWhere(['or', ['gender' => 'all'], ['gender' => $this->gender]]);
        $resutl->andWhere(['or', ['marital_status' => 'all'], ['marital_status' => $this->marital_status]]);
        $resutl->andFilterWhere(['status' => 'active']);
        return $resutl->all();
    }

    /**
     * Gets query for [[Tokens]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTokens()
    {
        return $this->hasMany(Token::className(), ['user_id' => 'id']);
    }

    /** @inheritdoc */
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->setAttribute('auth_key', \Yii::$app->security->generateRandomString());
            $this->setAttribute('password_hash', Yii::$app->security->generatePasswordHash($this->password_hash, Yii::$app->getModule('user')->cost));
        }


        return parent::beforeSave($insert);
    }

    public function getProfileAvatar()
    {
        return $this->hasOne(EmployeeFiles::className(), ['user_id' => 'id'])->andWhere(['type' => EmployeeFiles::TYPE_AVATAR]);
    }

    public function getAttachments()
    {
        return $this->hasOne(EmployeeFiles::className(), ['user_id' => 'id'])->andWhere(['type' => EmployeeFiles::TYPE_ATTACHMENT]);
    }

    public function updateProfileAvatar()
    {
        $saveToDirectory = Yii::getAlias("@backend/web/images/employeeImage");
        $fileName = time() . '_' . uniqid();
        if (!file_exists($saveToDirectory)) {
            FileHelper::createDirectory(Yii::getAlias($saveToDirectory), $mode = 0777, $recursive = true);
        }
        $this->profile_avatar_file = $this->profile_avatar_file[0];

        $saveFilePath = "{$saveToDirectory}/{$fileName}." . $this->profile_avatar_file->extension;
        if ($this->profile_avatar_file->saveAs($saveFilePath)) {
            EmployeeFiles::deleteAll(['type' => EmployeeFiles::TYPE_AVATAR, 'user_id' => $this->id]);
            $avatarFileModel = new EmployeeFiles();
            $avatarFileModel->user_id = $this->id;
            $avatarFileModel->type = EmployeeFiles::TYPE_AVATAR;
            $avatarFileModel->file_name = $this->profile_avatar_file->baseName;
            $avatarFileModel->path = '/images/employeeImage/' . ($fileName . '.' . $this->profile_avatar_file->extension);
            return $avatarFileModel->save();
        }
        return false;
    }

    public function addProfileAttachment()
    {
        $attachmentFiles = $this->profile_attachment_files;
        foreach ($attachmentFiles as $attachmentFile) {
            $saveToDirectory = Yii::getAlias("@backend/web/images/employeeImage");
            $fileName = $attachmentFile->baseName ;
            if (!file_exists($saveToDirectory)) {
                FileHelper::createDirectory(Yii::getAlias($saveToDirectory), $mode = 0777, $recursive = true);
            }

            $saveFilePath = "{$saveToDirectory}/{$fileName}." . $attachmentFile->extension;

            if ($attachmentFile->saveAs($saveFilePath)) {
                $avatarFileModel = new EmployeeFiles();
                $avatarFileModel->user_id = $this->id;
                $avatarFileModel->type = EmployeeFiles::TYPE_ATTACHMENT;
                $avatarFileModel->file_name = $attachmentFile->baseName;
                $avatarFileModel->path = '/images/employeeImage/' . ($fileName . '.' . $attachmentFile->extension);
                $avatarFileModel->save();
            }
            return true;
        }
        return false;
    }
}
