<?php

namespace backend\modules\accounting\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $name
 * @property string $start_date
 * @property string $end_date
 * @property string $status
 * @property int|null $company_id
 * @property int $is_current
 * @property int|null $created_by
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property FiscalPeriod[] $periods
 */
class FiscalYear extends ActiveRecord
{
    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';
    const STATUS_LOCKED = 'locked';

    public static function tableName()
    {
        return '{{%fiscal_years}}';
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
            [['name', 'start_date', 'end_date'], 'required'],
            [['start_date', 'end_date'], 'date', 'format' => 'php:Y-m-d'],
            [['company_id', 'is_current', 'created_by'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['status'], 'in', 'range' => array_keys(self::getStatuses())],
            [['status'], 'default', 'value' => self::STATUS_OPEN],
            [['is_current'], 'default', 'value' => 0],
            ['end_date', 'compare', 'compareAttribute' => 'start_date', 'operator' => '>', 'message' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'م',
            'name' => 'اسم السنة المالية',
            'start_date' => 'تاريخ البداية',
            'end_date' => 'تاريخ النهاية',
            'status' => 'الحالة',
            'company_id' => 'الشركة',
            'is_current' => 'السنة الحالية',
            'created_by' => 'أنشئ بواسطة',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'تاريخ التحديث',
        ];
    }

    public function getPeriods()
    {
        return $this->hasMany(FiscalPeriod::class, ['fiscal_year_id' => 'id'])->orderBy(['period_number' => SORT_ASC]);
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_OPEN => 'مفتوحة',
            self::STATUS_CLOSED => 'مغلقة',
            self::STATUS_LOCKED => 'مقفلة',
        ];
    }

    public function getStatusBadge()
    {
        $colors = [
            self::STATUS_OPEN => 'success',
            self::STATUS_CLOSED => 'warning',
            self::STATUS_LOCKED => 'danger',
        ];
        $color = $colors[$this->status] ?? 'default';
        $label = self::getStatuses()[$this->status] ?? $this->status;
        return '<span class="label label-' . $color . '">' . $label . '</span>';
    }

    public static function getCurrentYear($companyId = null)
    {
        $query = self::find()->where(['is_current' => 1]);
        if ($companyId) {
            $query->andWhere(['or', ['company_id' => $companyId], ['company_id' => null]]);
        }
        return $query->one();
    }

    /**
     * Generate 12 monthly periods for this fiscal year.
     */
    public function generatePeriods()
    {
        $start = new \DateTime($this->start_date);
        $months = [
            'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
            'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
        ];

        for ($i = 1; $i <= 12; $i++) {
            $periodStart = clone $start;
            $periodStart->modify('+' . ($i - 1) . ' months');
            $periodEnd = clone $periodStart;
            $periodEnd->modify('+1 month -1 day');

            if ($periodEnd->format('Y-m-d') > $this->end_date) {
                $periodEnd = new \DateTime($this->end_date);
            }

            if ($periodStart->format('Y-m-d') > $this->end_date) {
                break;
            }

            $period = new FiscalPeriod();
            $period->fiscal_year_id = $this->id;
            $period->period_number = $i;
            $period->name = $months[$periodStart->format('n') - 1] . ' ' . $periodStart->format('Y');
            $period->start_date = $periodStart->format('Y-m-d');
            $period->end_date = $periodEnd->format('Y-m-d');
            $period->status = FiscalPeriod::STATUS_OPEN;
            $period->save();
        }
    }

    /**
     * Find the fiscal period that contains the given date.
     */
    public function findPeriodByDate($date)
    {
        return FiscalPeriod::find()
            ->where(['fiscal_year_id' => $this->id])
            ->andWhere(['<=', 'start_date', $date])
            ->andWhere(['>=', 'end_date', $date])
            ->one();
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($this->is_current) {
            self::updateAll(['is_current' => 0], ['and', ['!=', 'id', $this->id], ['is_current' => 1]]);
        }

        if ($insert) {
            $this->generatePeriods();
        }
    }
}
