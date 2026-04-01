<?php

namespace backend\modules\accounting\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $name
 * @property int $fiscal_year_id
 * @property string $status
 * @property float $total_amount
 * @property string|null $notes
 * @property int|null $company_id
 * @property int|null $approved_by
 * @property int|null $approved_at
 * @property int|null $created_by
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property FiscalYear $fiscalYear
 * @property BudgetLine[] $budgetLines
 */
class Budget extends ActiveRecord
{
    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_CLOSED = 'closed';

    public static function tableName()
    {
        return '{{%budgets}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['name', 'fiscal_year_id'], 'required'],
            [['fiscal_year_id', 'company_id', 'approved_by', 'created_by'], 'integer'],
            [['total_amount'], 'number', 'min' => 0],
            [['name'], 'string', 'max' => 255],
            [['notes'], 'string'],
            [['status'], 'in', 'range' => array_keys(self::getStatuses())],
            [['status'], 'default', 'value' => self::STATUS_DRAFT],
            [['total_amount'], 'default', 'value' => 0],
            [['fiscal_year_id'], 'exist', 'skipOnError' => true, 'targetClass' => FiscalYear::class, 'targetAttribute' => ['fiscal_year_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'م',
            'name' => 'اسم الموازنة',
            'fiscal_year_id' => 'السنة المالية',
            'status' => 'الحالة',
            'total_amount' => 'إجمالي الموازنة',
            'notes' => 'ملاحظات',
            'company_id' => 'الشركة',
            'approved_by' => 'اعتمدت بواسطة',
            'approved_at' => 'تاريخ الاعتماد',
            'created_by' => 'أنشئت بواسطة',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'تاريخ التحديث',
        ];
    }

    public function getFiscalYear()
    {
        return $this->hasOne(FiscalYear::class, ['id' => 'fiscal_year_id']);
    }

    public function getBudgetLines()
    {
        return $this->hasMany(BudgetLine::class, ['budget_id' => 'id']);
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_APPROVED => 'معتمدة',
            self::STATUS_CLOSED => 'مغلقة',
        ];
    }

    public function getStatusBadge()
    {
        $colors = [
            self::STATUS_DRAFT => 'default',
            self::STATUS_APPROVED => 'success',
            self::STATUS_CLOSED => 'info',
        ];
        $color = $colors[$this->status] ?? 'default';
        $label = self::getStatuses()[$this->status] ?? $this->status;
        $map = ['info' => 'badge bg-info', 'warning' => 'badge bg-warning text-dark', 'success' => 'badge bg-success', 'danger' => 'badge bg-danger', 'default' => 'badge bg-secondary', 'primary' => 'badge bg-primary'];
        $bc = $map[$color] ?? 'badge bg-secondary';
        return '<span class="' . $bc . '">' . $label . '</span>';
    }

    public function recalculateTotal()
    {
        $this->total_amount = BudgetLine::find()
            ->where(['budget_id' => $this->id])
            ->sum('annual_total') ?: 0;
        $this->save(false, ['total_amount']);
    }

    public function approve()
    {
        $this->status = self::STATUS_APPROVED;
        $this->approved_by = Yii::$app->user->id;
        $this->approved_at = time();
        return $this->save(false, ['status', 'approved_by', 'approved_at']);
    }
}
