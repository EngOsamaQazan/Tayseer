<?php

namespace backend\modules\judiciaryActions\models;

use Yii;

/**
 * This is the model class for table "os_judiciary_actions".
 *
 * @property int $id
 * @property string $name
 * @property string $action_type
 * @property string $action_nature  (request|document|doc_status|process)
 * @property string $allowed_documents  comma-separated IDs of documents after this request
 * @property string $allowed_statuses   comma-separated IDs of statuses for this document
 * @property string $parent_request_ids comma-separated IDs of parent requests
 * @property int $is_deleted
 */
class JudiciaryActions extends \yii\db\ActiveRecord
{
    /* تصنيفات المراحل القضائية */
    const STAGE_CASE_PREPARATION     = 'case_preparation';     // تجهيز أوراق الدعوى
    const STAGE_CASE_REGISTRATION    = 'case_registration';    // تسجيل الدعوى
    const STAGE_NOTIFICATION         = 'notification';         // التبليغ والإخطار
    const STAGE_SALARY_DEDUCTION     = 'salary_deduction';     // حسم الراتب
    const STAGE_ARREST_DETENTION     = 'arrest_detention';     // القبض والحبس ومنع السفر
    const STAGE_ASSET_SEIZURE        = 'asset_seizure';        // حجز الأموال والمركبات
    const STAGE_APPEAL_CANCELLATION  = 'appeal_cancellation';  // استئناف / إلغاء
    const STAGE_SETTLEMENT_CLOSURE   = 'settlement_closure';   // تسوية / إغلاق
    const STAGE_COURT_DECISION       = 'court_decision';       // قرار قضائي
    const STAGE_GENERAL              = 'general';              // عام

    /**
     * {@inheritdoc}
     */
    public $number_row;
    public static function tableName()
    {
        return 'os_judiciary_actions';
    }

    /**
     * قائمة تصنيفات المراحل القضائية
     */
    public static function getActionTypeList()
    {
        return [
            self::STAGE_CASE_PREPARATION    => 'تجهيز أوراق الدعوى',
            self::STAGE_CASE_REGISTRATION   => 'تسجيل الدعوى',
            self::STAGE_NOTIFICATION        => 'التبليغ والإخطار',
            self::STAGE_SALARY_DEDUCTION    => 'حسم الراتب',
            self::STAGE_ARREST_DETENTION    => 'القبض والحبس ومنع السفر',
            self::STAGE_ASSET_SEIZURE       => 'حجز الأموال والمركبات',
            self::STAGE_APPEAL_CANCELLATION => 'استئناف / إلغاء',
            self::STAGE_SETTLEMENT_CLOSURE  => 'تسوية / إغلاق',
            self::STAGE_COURT_DECISION      => 'قرار قضائي',
            self::STAGE_GENERAL             => 'عام',
        ];
    }

    /**
     * اسم تصنيف الإجراء
     */
    public function getActionTypeLabel()
    {
        $list = self::getActionTypeList();
        return $list[$this->action_type] ?? ($this->action_type ?: 'عام');
    }

    /**
     * {@inheritdoc}
     */
    // Nature constants
    const NATURE_REQUEST    = 'request';
    const NATURE_DOCUMENT   = 'document';
    const NATURE_DOC_STATUS = 'doc_status';
    const NATURE_PROCESS    = 'process';

    public static function getNatureList()
    {
        return [
            self::NATURE_REQUEST    => 'طلب إجرائي',
            self::NATURE_DOCUMENT   => 'كتاب / مذكرة',
            self::NATURE_PROCESS    => 'إجراء إداري',
        ];
    }

    public function getNatureLabel()
    {
        $list = self::getNatureList();
        return $list[$this->action_nature] ?? 'غير مصنف';
    }

    /**
     * Get allowed document IDs as array
     */
    public function getAllowedDocumentIds()
    {
        if (empty($this->allowed_documents)) return [];
        return array_map('intval', explode(',', $this->allowed_documents));
    }

    /**
     * Get allowed status IDs as array
     */
    public function getAllowedStatusIds()
    {
        if (empty($this->allowed_statuses)) return [];
        return array_map('intval', explode(',', $this->allowed_statuses));
    }

    /**
     * Get parent request IDs as array
     */
    public function getParentRequestIdList()
    {
        if (empty($this->parent_request_ids)) return [];
        return array_map('intval', explode(',', $this->parent_request_ids));
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['action_type'], 'string', 'max' => 50],
            [['action_type'], 'default', 'value' => self::STAGE_GENERAL],
            [['action_nature'], 'in', 'range' => ['request', 'document', 'doc_status', 'process']],
            [['allowed_documents', 'allowed_statuses', 'parent_request_ids'], 'string', 'max' => 500],
            [['is_deleted'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => 'الاسم',
            'action_type' => 'نوع الإجراء',
            'action_nature' => 'طبيعة الإجراء',
        ];
    }
     public function getJudiciaryCustomersActions()
    {
        return $this->hasMany(\backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions::className(), ['judiciary_actions_id' => 'id']);
    }
}
