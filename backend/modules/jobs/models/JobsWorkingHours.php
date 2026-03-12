<?php

namespace backend\modules\jobs\models;

use Yii;

/**
 * This is the model class for table "os_jobs_working_hours".
 *
 * @property int $id
 * @property int $job_id
 * @property int $day_of_week
 * @property string|null $opening_time
 * @property string|null $closing_time
 * @property int $is_closed
 * @property string|null $notes
 *
 * @property Jobs $job
 */
class JobsWorkingHours extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_jobs_working_hours';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['job_id', 'day_of_week'], 'required'],
            [['job_id', 'day_of_week', 'is_closed'], 'integer'],
            [['opening_time', 'closing_time'], 'safe'],
            [['day_of_week'], 'in', 'range' => [0, 1, 2, 3, 4, 5, 6]],
            [['is_closed'], 'default', 'value' => 0],
            [['notes'], 'string', 'max' => 255],
            [['job_id'], 'exist', 'skipOnError' => true, 'targetClass' => Jobs::class, 'targetAttribute' => ['job_id' => 'id']],
            [['job_id', 'day_of_week'], 'unique', 'targetAttribute' => ['job_id', 'day_of_week'], 'message' => 'هذا اليوم مسجل مسبقاً لجهة العمل'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '#',
            'job_id' => Yii::t('app', 'جهة العمل'),
            'day_of_week' => Yii::t('app', 'اليوم'),
            'opening_time' => Yii::t('app', 'وقت البداية'),
            'closing_time' => Yii::t('app', 'وقت النهاية'),
            'is_closed' => Yii::t('app', 'مغلق'),
            'notes' => Yii::t('app', 'ملاحظات'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getJob()
    {
        return $this->hasOne(Jobs::class, ['id' => 'job_id']);
    }

    /**
     * Get day name in Arabic
     * @return string
     */
    public function getDayName()
    {
        $days = Jobs::getDayNames();
        return $days[$this->day_of_week] ?? '';
    }

    /**
     * Get formatted working hours
     * @return string
     */
    public function getFormattedHours()
    {
        if ($this->is_closed) {
            return '<span class="text-danger">مغلق</span>';
        }
        if ($this->opening_time && $this->closing_time) {
            return $this->opening_time . ' - ' . $this->closing_time;
        }
        return '-';
    }
}
