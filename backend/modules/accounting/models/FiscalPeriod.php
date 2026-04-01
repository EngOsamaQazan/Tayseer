<?php

namespace backend\modules\accounting\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * @property int $id
 * @property int $fiscal_year_id
 * @property int $period_number
 * @property string $name
 * @property string $start_date
 * @property string $end_date
 * @property string $status
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property FiscalYear $fiscalYear
 */
class FiscalPeriod extends ActiveRecord
{
    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';

    public static function tableName()
    {
        return '{{%fiscal_periods}}';
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
            [['fiscal_year_id', 'period_number', 'name', 'start_date', 'end_date'], 'required'],
            [['fiscal_year_id', 'period_number'], 'integer'],
            [['start_date', 'end_date'], 'date', 'format' => 'php:Y-m-d'],
            [['name'], 'string', 'max' => 50],
            [['status'], 'in', 'range' => [self::STATUS_OPEN, self::STATUS_CLOSED]],
            [['status'], 'default', 'value' => self::STATUS_OPEN],
            [['fiscal_year_id'], 'exist', 'skipOnError' => true, 'targetClass' => FiscalYear::class, 'targetAttribute' => ['fiscal_year_id' => 'id']],
            [['fiscal_year_id', 'period_number'], 'unique', 'targetAttribute' => ['fiscal_year_id', 'period_number']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'م',
            'fiscal_year_id' => 'السنة المالية',
            'period_number' => 'رقم الفترة',
            'name' => 'اسم الفترة',
            'start_date' => 'تاريخ البداية',
            'end_date' => 'تاريخ النهاية',
            'status' => 'الحالة',
        ];
    }

    public function getFiscalYear()
    {
        return $this->hasOne(FiscalYear::class, ['id' => 'fiscal_year_id']);
    }

    public function getStatusBadge()
    {
        $colors = [self::STATUS_OPEN => 'success', self::STATUS_CLOSED => 'danger'];
        $labels = [self::STATUS_OPEN => 'مفتوحة', self::STATUS_CLOSED => 'مغلقة'];
        $color = $colors[$this->status] ?? 'default';
        $label = $labels[$this->status] ?? $this->status;
        $map = ['info' => 'badge bg-info', 'warning' => 'badge bg-warning text-dark', 'success' => 'badge bg-success', 'danger' => 'badge bg-danger', 'default' => 'badge bg-secondary', 'primary' => 'badge bg-primary'];
        $bc = $map[$color] ?? 'badge bg-secondary';
        return '<span class="' . $bc . '">' . $label . '</span>';
    }

    /**
     * Find the period that contains a given date.
     */
    public static function findByDate($date, $companyId = null)
    {
        $query = self::find()
            ->joinWith('fiscalYear')
            ->where(['<=', self::tableName() . '.start_date', $date])
            ->andWhere(['>=', self::tableName() . '.end_date', $date])
            ->andWhere([self::tableName() . '.status' => self::STATUS_OPEN]);
        if ($companyId) {
            $query->andWhere(['or', ['{{%fiscal_years}}.company_id' => $companyId], ['{{%fiscal_years}}.company_id' => null]]);
        }
        return $query->one();
    }
}
