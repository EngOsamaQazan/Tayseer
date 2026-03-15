<?php

namespace backend\modules\customers\models;

use backend\modules\jobs\models\JobsType;
use backend\models\Media;
use backend\helpers\MediaHelper;
use Yii;
use backend\modlues\address\models\Address;
use backend\modlues\customers\models\ContractsCustomers;
use backend\modlues\installment\models\Installment;
use backend\modules\phoneNumbers\models\PhoneNumbers;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;
use backend\modules\jobs\models\Jobs;

/**
 * This is the model class for table "{{%customers}}".
 *
 * @property int $id
 * @property string $name
 * @property int $status
 * @property string|null $city
 * @property int|null $job_title
 * @property string|null $id_number
 * @property string|null $birth_date
 * @property string|null $email
 * @property int|null $sex
 * @property int|null $number_row
 * @property string $primary_phone_number
 * @property string $citizen
 * @property string|null $hear_about_us
 * @property string|null $selected_image
 * @property string|null $bank_name
 * @property string|null $bank_branch
 * @property string|null $account_number
 * @property int $is_social_security
 * @property int $job_Type
 * @property string|null $social_security_number
 * @property int $do_have_any_property
 * @property string|null $property_name
 * @property string|null $property_number
 * @property string|null $notes
 
 * @property Address[] $addresses
 * @property ContractsCustomers[] $contractsCustomers
 * @property CustomersDocument[] $customersDocuments
 * @property PhoneNumbers[] $phoneNumbers
 * @property Media $selectedImg
 * @property string $selectedImagePath
 * 
 * @property string|null $has_social_security_salary
 * @property string|null $social_security_salary_source
 * @property string|null $retirement_status
 * @property float|null $total_retirement_income
 * @property string|null $last_income_query_date
 * @property string|null $last_job_query_date
 * @property float|null $total_salary
 */
class Customers extends \yii\db\ActiveRecord
{

    public $customer_images;
    public $image_manager_id;
    public $number_row;
    public $job_Type;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%customers}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'last_updated_by',
            ],
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::class,
                'softDeleteAttributeValues' => [
                    'is_deleted' => true
                ],

                'replaceRegularDelete' => true // mutate native `delete()` method
            ],

        ];
    }

    public function afterFind()
    {
        parent::afterFind();
        if (!empty($this->birth_date)) {
            $ts = strtotime($this->birth_date);
            if ($ts === false || $ts < strtotime('1900-01-01') || $ts > time()) {
                $this->birth_date = null;
            }
        }
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) return false;
        if (!empty($this->primary_phone_number)) {
            $this->primary_phone_number = \backend\helpers\PhoneHelper::toE164($this->primary_phone_number);
        }

        if (!empty($this->birth_date)) {
            $ts = strtotime($this->birth_date);
            if ($ts === false || $ts < strtotime('1900-01-01') || $ts > time()) {
                $this->birth_date = null;
            } else {
                $this->birth_date = date('Y-m-d', $ts);
            }
        } else {
            $this->birth_date = null;
        }

        $dateFields = ['last_income_query_date', 'last_job_query_date'];
        foreach ($dateFields as $df) {
            if (!empty($this->$df)) {
                $ts = strtotime($this->$df);
                if ($ts === false || $ts < strtotime('1900-01-01')) {
                    $this->$df = null;
                }
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [

            [['name', 'sex', 'birth_date', 'city', 'id_number', 'job_title', 'hear_about_us', 'citizen', 'is_social_security', 'primary_phone_number', 'do_have_any_property'], 'required', 'on' => 'create'],
            [['status', 'sex', 'is_social_security', 'do_have_any_property', 'property_number', 'is_social_security', 'job_title', 'do_have_any_property', 'bank_name'], 'integer'],
            [['number_row', 'job_Type'], 'integer'],
            [['hear_about_us', 'primary_phone_number', 'facebook_account', 'id_number', 'citizen'], 'string'],
            [['birth_date', 'customer_images', 'image_manager_id', 'selected_image'], 'safe'],
            [['name'], 'string', 'max' => 250],
            [['city', 'id_number', 'property_number', 'selected_image'], 'string', 'max' => 255],
            [['job_number'], 'string', 'max' => 20],
            [['property_number'], 'string'],
            [['number_row'], 'integer'],
            [['email', 'citizen', 'account_number', 'social_security_number', 'property_name'], 'string', 'max' => 50],
            [['bank_branch'], 'string', 'max' => 100],
            [['notes'], 'string', 'max' => 500],
            [['has_social_security_salary'], 'in', 'range' => ['yes', 'no', ''], 'skipOnEmpty' => true],
            [['social_security_salary_source'], 'string', 'skipOnEmpty' => true],
            [['retirement_status'], 'in', 'range' => ['effective', 'stopped', ''], 'skipOnEmpty' => true],
            [['total_retirement_income'], 'number', 'skipOnEmpty' => true],
            [['last_income_query_date'], 'safe'],
            [['last_job_query_date'], 'safe'],
            [['total_salary'], 'number', 'skipOnEmpty' => true],
            [
                'social_security_number',
                'required',
                'when' => function ($models) {
                    return ($models->is_social_security == 1) ? true : false;
                },
                'on' => 'create'
            ],
        ];

    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'status' => Yii::t('app', 'Status'),
            'city' => Yii::t('app', 'City'),
            'job_title' => Yii::t('app', 'Job Title'),
            'id_number' => Yii::t('app', 'Id Number'),
            'birth_date' => Yii::t('app', 'Birth Date'),
            'job_number' => Yii::t('app', 'Job Number'),
            'email' => Yii::t('app', 'Email'),
            'sex' => Yii::t('app', 'Sex'),
            'citizen' => Yii::t('app', 'Citizen'),
            'hear_about_us' => Yii::t('app', 'How did you hear about us?'),
            'selected_image' => Yii::t('app', 'Selected Image'),
            'bank_name' => Yii::t('app', 'Bank Name'),
            'bank_branch' => Yii::t('app', 'Bank Branch'),
            'account_number' => Yii::t('app', 'Account Number'),
            'is_social_security' => Yii::t('app', 'Is Social Security'),
            'social_security_number' => Yii::t('app', 'Social Security Number'),
            'do_have_any_property' => Yii::t('app', 'Do Have Any Property'),
            'property_name' => Yii::t('app', 'Property Name'),
            'property_number' => Yii::t('app', 'Property Number'),
            'notes' => Yii::t('app', 'Notes'),
            'primary_phone_number' => Yii::t('app', 'primary phone number'),
            'facebook_account' => Yii::t('app', 'facebook account'),
            'customer_images' => Yii::t('app', 'Customer Images'),
            'has_social_security_salary' => Yii::t('app', 'Has Social Security Salary'),
            'social_security_salary_source' => Yii::t('app', 'Social Security Salary Source'),
            'retirement_status' => Yii::t('app', 'Retirement Status'),
            'total_retirement_income' => Yii::t('app', 'Total Retirement Income'),
            'last_income_query_date' => Yii::t('app', 'Last Income Query Date'),
            'last_job_query_date' => Yii::t('app', 'Last Job Query Date'),
            'total_salary' => Yii::t('app', 'Total Salary'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInstallments()
    {
        return $this->hasMany(Installment::class, ['customer_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddress()
    {
        return $this->hasMany(Address::class, ['customers_id' => 'id']);
    }

    public function getFirstAddress()
    {
        return $this->hasMany(Address::class, ['customers_id' => 'id'])->one();
    }
    public function getJobs()
    {
        return $this->hasMany(jobs::class, ['id' => 'job_title'])->one();
    }
    public function getJobType()
    {
        return $this->hasMany(JobsType::class, ['id' => 'job_type'])->one();
    }
    public function jobsName($id)
    {
        $model = Jobs::findOne(['id' => $id]);
        return $model->name;
    }

    /**
     * Gets query for [[ContractsCustomers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getContractsCustomers()
    {
        return $this->hasMany(ContractsCustomers::class, ['customer_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhoneNumbers()
    {
        return $this->hasMany(PhoneNumbers::class, ['customers_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return CustomersQuery the active query used by this AR class.
     */
    public function getSelectedImg()
    {
        return $this->hasOne(Media::class, ['id' => 'selected_image']);
    }

    public function getSelectedImagePath()
    {
        $personalPhoto = Media::find()
            ->where(['customer_id' => (int)$this->id, 'groupName' => '8'])
            ->orderBy(new \yii\db\Expression('RAND()'))
            ->one();

        if ($personalPhoto && $personalPhoto->fileExists()) {
            return $personalPhoto->getUrl();
        }

        if ($this->selectedImg && $this->selectedImg->fileExists()) {
            return $this->selectedImg->getUrl();
        }

        return null;
    }

}