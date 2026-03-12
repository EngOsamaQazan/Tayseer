<?php

namespace backend\modules\jobs\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "os_jobs_ratings".
 *
 * @property int $id
 * @property int $job_id
 * @property string $rating_type
 * @property int $rating_value
 * @property int|null $contract_id
 * @property int|null $judiciary_id
 * @property string|null $review_text
 * @property int $rated_by
 * @property string $rated_at
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int $is_deleted
 *
 * @property Jobs $job
 */
class JobsRating extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_jobs_ratings';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['job_id', 'rating_type', 'rating_value', 'rated_by'], 'required'],
            [['job_id', 'rating_value', 'contract_id', 'judiciary_id', 'rated_by', 'is_deleted'], 'integer'],
            [['review_text'], 'string'],
            [['rated_at'], 'safe'],
            [['rating_type'], 'in', 'range' => ['judicial_response', 'cooperation', 'speed', 'overall']],
            [['rating_value'], 'in', 'range' => [1, 2, 3, 4, 5]],
            [['is_deleted'], 'default', 'value' => 0],
            [['job_id'], 'exist', 'skipOnError' => true, 'targetClass' => Jobs::class, 'targetAttribute' => ['job_id' => 'id']],
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
            'rating_type' => Yii::t('app', 'نوع التقييم'),
            'rating_value' => Yii::t('app', 'التقييم'),
            'contract_id' => Yii::t('app', 'العقد'),
            'judiciary_id' => Yii::t('app', 'القضية'),
            'review_text' => Yii::t('app', 'تفاصيل التقييم'),
            'rated_by' => Yii::t('app', 'المُقيّم'),
            'rated_at' => Yii::t('app', 'تاريخ التقييم'),
            'created_at' => Yii::t('app', 'تاريخ الإنشاء'),
            'updated_at' => Yii::t('app', 'تاريخ التحديث'),
            'is_deleted' => Yii::t('app', 'محذوف'),
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
     * @return \yii\db\ActiveQuery
     */
    public function getRater()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'rated_by']);
    }

    /**
     * Get rating type label in Arabic
     * @return string
     */
    public function getRatingTypeLabel()
    {
        $types = self::getRatingTypes();
        return $types[$this->rating_type] ?? $this->rating_type;
    }

    /**
     * @return array rating type options
     */
    public static function getRatingTypes()
    {
        return [
            'judicial_response' => 'الاستجابة للقرارات القضائية',
            'cooperation' => 'التعاون',
            'speed' => 'سرعة الاستجابة',
            'overall' => 'تقييم عام',
        ];
    }

    /**
     * Get star rating HTML
     * @return string
     */
    public function getStarsHtml()
    {
        $html = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $this->rating_value) {
                $html .= '<i class="fa fa-star text-warning"></i>';
            } else {
                $html .= '<i class="fa fa-star-o text-muted"></i>';
            }
        }
        return $html;
    }

    /**
     * Default scope: exclude soft-deleted
     */
    public static function find()
    {
        return parent::find()->andWhere(['os_jobs_ratings.is_deleted' => 0]);
    }

    /**
     * Soft delete
     */
    public function softDelete()
    {
        $this->is_deleted = 1;
        return $this->save(false);
    }
}
