<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * نموذج الورديات — Work Shifts
 *
 * @property int $id
 * @property int|null $company_id
 * @property string $title
 * @property string $start_at
 * @property string $end_at
 * @property int $grace_minutes
 * @property int $early_leave_minutes
 * @property int $overtime_after_minutes
 * @property array|null $working_days
 * @property int $is_flexible
 * @property int $flex_window_minutes
 * @property int $break_duration_minutes
 * @property int $is_active
 * @property int $created_by
 * @property int $created_at
 * @property int|null $updated_at
 */
class HrWorkShift extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%work_shift}}';
    }

    public function rules()
    {
        return [
            [['title', 'start_at', 'end_at'], 'required'],
            [['company_id', 'grace_minutes', 'early_leave_minutes', 'overtime_after_minutes',
              'is_flexible', 'flex_window_minutes', 'break_duration_minutes', 'is_active',
              'created_by', 'created_at', 'updated_at'], 'integer'],
            [['title'], 'string', 'max' => 50],
            [['start_at', 'end_at'], 'safe'],
            [['working_days'], 'safe'],
            [['grace_minutes'], 'default', 'value' => 15],
            [['early_leave_minutes'], 'default', 'value' => 10],
            [['overtime_after_minutes'], 'default', 'value' => 30],
            [['break_duration_minutes'], 'default', 'value' => 60],
            [['is_flexible'], 'default', 'value' => 0],
            [['is_active'], 'default', 'value' => 1],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'                     => Yii::t('app', 'المعرف'),
            'title'                  => Yii::t('app', 'اسم الوردية'),
            'start_at'               => Yii::t('app', 'وقت البداية'),
            'end_at'                 => Yii::t('app', 'وقت النهاية'),
            'grace_minutes'          => Yii::t('app', 'فترة السماح (دقائق)'),
            'early_leave_minutes'    => Yii::t('app', 'خروج مبكر مسموح (دقائق)'),
            'overtime_after_minutes' => Yii::t('app', 'إضافي بعد (دقائق)'),
            'working_days'           => Yii::t('app', 'أيام العمل'),
            'is_flexible'            => Yii::t('app', 'وردية مرنة'),
            'flex_window_minutes'    => Yii::t('app', 'نافذة المرونة (دقائق)'),
            'break_duration_minutes' => Yii::t('app', 'مدة الاستراحة (دقائق)'),
            'is_active'              => Yii::t('app', 'فعّال'),
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_by = Yii::$app->user->id ?? 0;
                $this->created_at = time();
            }
            $this->updated_at = time();

            if (is_array($this->working_days)) {
                $this->working_days = json_encode($this->working_days);
            }
            return true;
        }
        return false;
    }

    public function afterFind()
    {
        parent::afterFind();
        if (is_string($this->working_days)) {
            $this->working_days = json_decode($this->working_days, true);
        }
    }

    /**
     * حساب مدة الوردية بالدقائق
     */
    public function getDurationMinutes()
    {
        $start = strtotime($this->start_at);
        $end = strtotime($this->end_at);
        if ($end <= $start) $end += 86400;
        return ($end - $start) / 60;
    }

    /**
     * هل الوقت المُعطى يقع ضمن فترة السماح؟
     */
    public function isWithinGrace($clockInTime)
    {
        $shiftStart = strtotime($this->start_at);
        $clockIn = strtotime(date('H:i:s', strtotime($clockInTime)));
        $diff = ($clockIn - $shiftStart) / 60;
        return $diff <= $this->grace_minutes;
    }

    public static function getActiveList()
    {
        return self::find()
            ->where(['is_active' => 1])
            ->select(['title', 'id'])
            ->indexBy('id')
            ->column();
    }
}
