<?php

namespace backend\modules\companies\models;

use backend\modules\companyBanks\models\CompanyBanks;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;
use \common\models\User;
/**
* This is the model class for table "{{%companies}}" 
*
 * @property int $id
 * @property string $name
 * @property string $phone_number
 * @property string $bank_info
 * @property string $logo
 * @property int $created_by
 * @property int $created_at
 * @property int|null $updated_at
 * @property int $is_deleted
 * @property int $number_row
 * @property int $last_updated_by
 * @property boolean $is_primary_company
 * @property int|null $is_primary_company
 * @property int|null $last_updated_by
 * @property string|null $company_social_security_number
 * @property string|null $company_tax_number
 * @property string|null $company_email
 *
 * @property InventoryStockLocations[] $inventoryStockLocations
 * @property InventorySuppliers[] $inventorySuppliers
 */
class Companies extends \yii\db\ActiveRecord
{
    public $number_row;
    public $commercial_register_files;
    public $trade_license_files;
    public static function tableName()
    {
        return '{{%companies}}';
    }
    public function behaviors()
    {
        return [

            [
                'class' => TimestampBehavior::className(),
                'value' => time(),
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
            [['name', 'phone_number'], 'required'],
            [['is_primary_company', 'capital_refundable'], 'boolean'],
            [['created_by', 'created_at', 'updated_at', 'is_deleted', 'number_row', 'total_shares'], 'integer'],
            [['name', 'phone_number'], 'string', 'max' => 50],
            [['logo'], 'file', 'extensions' => 'png, jpg, jpeg, gif, bmp, webp, svg, pdf'],
            [['commercial_register_files'], 'file', 'extensions' => 'png, jpg, jpeg, gif, bmp, webp, pdf', 'maxFiles' => 10],
            [['trade_license_files'], 'file', 'extensions' => 'png, jpg, jpeg, gif, bmp, webp, pdf', 'maxFiles' => 10],
            [['commercial_register', 'trade_license', 'agreement_notes'], 'safe'],
            [['company_email'], 'email'],
            [['company_social_security_number', 'company_tax_number', 'company_address'], 'string', 'max' => 255],
            [['invested_capital', 'profit_share_ratio', 'parent_share_ratio'], 'number'],
            [['portfolio_status'], 'string'],
            [['agreement_date'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'م',
            'name' => 'اسم المُستثمر',
            'phone_number' => 'رقم الهاتف',
            'logo' => 'الشعار',
            'created_by' => 'أنشئ بواسطة',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'تاريخ التحديث',
            'is_deleted' => 'محذوف',
            'is_primary_company' => 'شركة رئيسية',
            'company_social_security_number' => 'رقم الضمان الاجتماعي',
            'company_tax_number' => 'الرقم الضريبي',
            'company_email' => 'البريد الإلكتروني',
            'company_address' => 'العنوان',
            'commercial_register' => 'السجل التجاري',
            'trade_license' => 'رخصة المهن',
            'total_shares' => 'إجمالي الأسهم',
            'invested_capital' => 'رأس المال المستثمر',
            'profit_share_ratio' => 'نسبة المُستثمر من الأرباح %',
            'parent_share_ratio' => 'نسبة الشركة الأم من الأرباح %',
            'capital_refundable' => 'رأس المال قابل للإعادة',
            'portfolio_status' => 'حالة المحفظة',
            'agreement_date' => 'تاريخ الاتفاق',
            'agreement_notes' => 'شروط الاتفاق',
        ];
    }

    /**
     * Gets query for [[InventoryStockLocations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInventoryStockLocations()
    {
        return $this->hasMany(InventoryStockLocations::className(), ['company_id' => 'id']);
    }

    /**
     * Gets query for [[InventorySuppliers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInventorySuppliers()
    {
        return $this->hasMany(InventorySuppliers::className(), ['company_id' => 'id']);
    }
    public function getCreatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }

    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::className());
        return $query->notDeleted();
    }

    public function getPrimeryBankAccount()
    {
        return $this->hasOne(CompanyBanks::className(), ['company_id' => 'id']);
    }

    public function getCapitalTransactions()
    {
        return $this->hasMany(\backend\modules\capitalTransactions\models\CapitalTransactions::class, ['company_id' => 'id'])
            ->orderBy(['transaction_date' => SORT_DESC, 'id' => SORT_DESC]);
    }

    public function getCapitalBalance()
    {
        $deposits = (float) \backend\modules\capitalTransactions\models\CapitalTransactions::find()
            ->where(['company_id' => $this->id, 'transaction_type' => 'إيداع'])
            ->sum('amount') ?: 0;
        $withdrawals = (float) \backend\modules\capitalTransactions\models\CapitalTransactions::find()
            ->where(['company_id' => $this->id])
            ->andWhere(['in', 'transaction_type', ['سحب', 'إعادة_رأس_مال']])
            ->sum('amount') ?: 0;
        return $deposits - $withdrawals;
    }

    public function getCommercialRegisterList()
    {
        if (empty($this->commercial_register)) return [];
        $decoded = json_decode($this->commercial_register, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function getTradeLicenseList()
    {
        if (empty($this->trade_license)) return [];
        $decoded = json_decode($this->trade_license, true);
        return is_array($decoded) ? $decoded : [];
    }
}
