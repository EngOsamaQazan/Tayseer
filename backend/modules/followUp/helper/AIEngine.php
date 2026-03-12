<?php

namespace backend\modules\followUp\helper;

use Yii;
use backend\modules\contracts\models\Contracts;
use backend\modules\followUp\models\FollowUp;
use backend\modules\followUp\models\FollowUpTask;
use backend\modules\followUp\helper\RiskEngine;
use backend\modules\followUp\helper\ContractCalculations;
use backend\modules\judiciary\models\Judiciary;
use backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions;

/**
 * AI Engine — Rule-based recommendation system (Stage 1)
 * 
 * Generates Next Best Action, alternatives, and confidence scores.
 * Reads real judiciary data from os_judiciary & os_judiciary_customers_actions.
 * Designed for future ML upgrade (Stage 3).
 */
class AIEngine
{
    private $contract;
    private $risk;
    private $calc;
    private $dpd;
    private $brokenPromises;
    private $lastContactDate;

    /** @var Judiciary|null */
    private $judiciary;
    /** @var array Judiciary customer actions for this contract */
    private $judiciaryActions;
    /** @var array|null Last judiciary action */
    private $lastJudiciaryAction;
    /** @var int Days since last judiciary action */
    private $daysSinceLastJudiciaryAction;
    /** @var string|null Current judiciary stage */
    private $judiciaryStage;

    /**
     * Judiciary action type classification map
     * Maps action IDs to workflow stages
     */
    private static $STAGE_MAP = [
        // المرحلة 1: تجهيز أوراق الدعوى
        'case_preparation' => [1, 3, 16, 24, 47, 64, 87],
        // المرحلة 2: تسجيل الدعوى
        'case_registration' => [4, 36],
        // المرحلة 3: التبليغ والإخطار
        'notification' => [5, 6, 17, 18, 20, 39, 51, 52, 67, 68, 77],
        // المرحلة 4: حسم الراتب
        'salary_deduction' => [8, 13, 22, 45, 54, 56, 75, 81, 88, 89],
        // المرحلة 5: القبض والحبس ومنع السفر
        'arrest_detention' => [2, 7, 9, 10, 12, 35, 34, 48, 49, 50, 53, 57, 79],
        // المرحلة 6: حجز الأموال والمركبات والعقارات
        'asset_seizure' => [23, 27, 28, 42, 43, 44, 46, 59, 60, 66, 69, 70, 71, 72, 73, 76, 82, 83, 85, 86],
        // الاستئناف والإلغاء
        'appeal_cancellation' => [14, 15, 26, 29, 30, 31, 33, 65],
        // التسوية والإغلاق
        'settlement_closure' => [11, 19, 21, 25, 32, 40, 41, 55, 58, 78, 80, 84, 90],
        // قرارات قضائية
        'court_decision' => [61, 62, 63],
    ];

    /**
     * Ordered workflow stages for determining current phase
     */
    private static $WORKFLOW_ORDER = [
        'case_preparation',
        'case_registration',
        'notification',
        'salary_deduction',
        'arrest_detention',
        'asset_seizure',
        'court_decision',
        'settlement_closure',
    ];

    public function __construct($contract)
    {
        $this->contract = $contract;
        $riskEngine = new RiskEngine($contract);
        $this->risk = $riskEngine->assess();
        $this->calc = new ContractCalculations($contract->id);
        $this->dpd = $riskEngine->getDPD();
        $this->brokenPromises = $riskEngine->getBrokenPromisesCount();
        $this->lastContactDate = $riskEngine->getLastContactDate();

        // Load judiciary data
        $this->loadJudiciaryData();
    }

    /**
     * Load judiciary data for this contract
     */
    private function loadJudiciaryData()
    {
        // Find the main judiciary case for this contract
        $this->judiciary = Judiciary::find()
            ->where(['contract_id' => $this->contract->id, 'is_deleted' => 0])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        $this->judiciaryActions = [];
        $this->lastJudiciaryAction = null;
        $this->daysSinceLastJudiciaryAction = 999;
        $this->judiciaryStage = null;

        if ($this->judiciary) {
            // Load all actions for this judiciary case with related data
            $actions = JudiciaryCustomersActions::find()
                ->where(['judiciary_id' => $this->judiciary->id])
                ->orderBy(['action_date' => SORT_ASC])
                ->all();

            $this->judiciaryActions = $actions;

            if (!empty($actions)) {
                $lastAction = end($actions);
                $this->lastJudiciaryAction = $lastAction;

                // Calculate days since last action
                if ($lastAction->action_date) {
                    $this->daysSinceLastJudiciaryAction = max(0,
                        (int)((strtotime('today') - strtotime($lastAction->action_date)) / 86400)
                    );
                }

                // Determine current judiciary stage
                $this->judiciaryStage = $this->detectJudiciaryStage();
            }
        }
    }

    /**
     * Detect the current judiciary workflow stage based on the latest action
     */
    private function detectJudiciaryStage()
    {
        if (!$this->lastJudiciaryAction) {
            return 'case_preparation';
        }

        $actionId = $this->lastJudiciaryAction->judiciary_actions_id;

        // Find which stage this action belongs to
        foreach (self::$STAGE_MAP as $stage => $ids) {
            if (in_array($actionId, $ids)) {
                return $stage;
            }
        }

        return 'general';
    }

    /**
     * Get the Arabic label for a judiciary stage
     */
    private function getStageLabel($stage)
    {
        $labels = [
            'case_preparation' => 'تجهيز أوراق الدعوى',
            'case_registration' => 'تسجيل الدعوى',
            'notification' => 'التبليغ والإخطار',
            'salary_deduction' => 'حسم الراتب',
            'arrest_detention' => 'القبض والحبس ومنع السفر',
            'asset_seizure' => 'حجز الأموال والمركبات',
            'appeal_cancellation' => 'استئناف / إلغاء',
            'settlement_closure' => 'تسوية / إغلاق',
            'court_decision' => 'قرار قضائي',
            'general' => 'إجراء عام',
        ];
        if ($stage === null) {
            return '';
        }
        return $labels[$stage] ?? $stage;
    }

    /**
     * Get the next expected stage in the judiciary workflow
     */
    private function getNextStage()
    {
        if (!$this->judiciaryStage) {
            return 'case_preparation';
        }

        $currentIndex = array_search($this->judiciaryStage, self::$WORKFLOW_ORDER);
        if ($currentIndex === false || $currentIndex >= count(self::$WORKFLOW_ORDER) - 1) {
            return null; // Last stage or not found
        }

        return self::$WORKFLOW_ORDER[$currentIndex + 1];
    }

    /**
     * Generate complete AI recommendation
     * @return array
     */
    public function recommend()
    {
        $status = $this->contract->status;
        $isLegal = in_array($status, ['judiciary', 'legal_department']);

        $nba = $this->getNextBestAction();
        $alternatives = $this->getAlternatives($nba['action_type'], $isLegal);
        $playbook = $this->getActivePlaybook();

        $result = [
            'next_best_action' => $nba,
            'alternatives' => $alternatives,
            'playbook' => $playbook,
            'risk' => $this->risk,
            'dpd' => $this->dpd,
        ];

        // Add judiciary summary for legal contracts
        if ($isLegal) {
            $result['judiciary_summary'] = $this->buildJudiciarySummary();
        }

        return $result;
    }

    /**
     * Build judiciary summary for AI panel
     */
    private function buildJudiciarySummary()
    {
        $summary = [
            'has_case' => $this->judiciary !== null,
            'case_number' => null,
            'court' => null,
            'lawyer' => null,
            'total_actions' => count($this->judiciaryActions),
            'last_action_name' => null,
            'last_action_date' => null,
            'days_since_last_action' => $this->daysSinceLastJudiciaryAction,
            'current_stage' => $this->judiciaryStage,
            'current_stage_label' => $this->getStageLabel($this->judiciaryStage),
            'next_stage' => null,
            'next_stage_label' => null,
        ];

        if ($this->judiciary) {
            $summary['case_number'] = ($this->judiciary->judiciary_number ?: '-') . '/' . ($this->judiciary->year ?: '-');
            $summary['court'] = $this->judiciary->court ? $this->judiciary->court->name : null;
            $summary['lawyer'] = $this->judiciary->lawyer ? $this->judiciary->lawyer->name : null;
        }

        if ($this->lastJudiciaryAction) {
            $summary['last_action_name'] = $this->lastJudiciaryAction->judiciaryActions
                ? $this->lastJudiciaryAction->judiciaryActions->name
                : null;
            $summary['last_action_date'] = $this->lastJudiciaryAction->action_date;
        }

        $nextStage = $this->getNextStage();
        if ($nextStage) {
            $summary['next_stage'] = $nextStage;
            $summary['next_stage_label'] = $this->getStageLabel($nextStage);
        }

        return $summary;
    }

    /**
     * Determine Next Best Action
     */
    private function getNextBestAction()
    {
        $status = $this->contract->status;

        // Legal mode — with real judiciary data
        if (in_array($status, ['judiciary', 'legal_department'])) {
            return $this->legalModeAction();
        }

        // Critical: many broken promises or very late
        if ($this->brokenPromises >= 3 || $this->dpd > 30) {
            return $this->criticalAction();
        }

        // Late 8-30 days
        if ($this->dpd > 7 && $this->dpd <= 30) {
            return $this->moderateLateAction();
        }

        // Late 1-7 days
        if ($this->dpd > 0 && $this->dpd <= 7) {
            return $this->mildLateAction();
        }

        // No contact history
        if ($this->lastContactDate === null) {
            return $this->noContactAction();
        }

        // Days since last contact
        $daysSinceContact = $this->lastContactDate 
            ? (int)((strtotime('today') - strtotime($this->lastContactDate)) / 86400)
            : 999;

        // Stale contact
        if ($daysSinceContact > 14) {
            return $this->staleContactAction($daysSinceContact);
        }

        // Default: routine follow-up
        return $this->routineAction();
    }

    /**
     * Legal mode action — reads REAL judiciary data
     */
    private function legalModeAction()
    {
        $reasons = [];
        $action = '';
        $confidence = 85;
        $icon = 'fa-gavel';

        // No case registered yet
        if (!$this->judiciary) {
            return [
                'action' => 'تسجيل دعوى قضائية — لا يوجد ملف قضائي مسجل',
                'action_type' => 'add_judiciary_action',
                'reasons' => [
                    'العقد محول للقضاء لكن لا يوجد ملف قضائي مسجل في النظام',
                    'يجب تجهيز أوراق الدعوى وتسجيل القضية',
                    'مبلغ متأخر: ' . number_format($this->calc->deservedAmount()) . ' د.أ',
                ],
                'confidence' => 95,
                'risk_impact' => 'high',
                'icon' => 'fa-gavel',
            ];
        }

        // Case exists but no actions recorded
        if (empty($this->judiciaryActions)) {
            return [
                'action' => 'بدء تجهيز أوراق الدعوى — لم يُسجل أي إجراء بعد',
                'action_type' => 'add_judiciary_action',
                'reasons' => [
                    'القضية مسجلة برقم ' . ($this->judiciary->judiciary_number ?: '-') . '/' . ($this->judiciary->year ?: '-'),
                    'المحكمة: ' . ($this->judiciary->court ? $this->judiciary->court->name : 'غير محدد'),
                    'لم يُسجل أي إجراء على هذه القضية حتى الآن',
                    'يجب البدء بتجهيز الأوراق وتسجيل أول إجراء',
                ],
                'confidence' => 92,
                'risk_impact' => 'high',
                'icon' => 'fa-gavel',
            ];
        }

        // Has actions — analyze based on current stage
        $lastActionName = $this->lastJudiciaryAction->judiciaryActions
            ? $this->lastJudiciaryAction->judiciaryActions->name
            : 'غير محدد';
        $lastActionDate = $this->lastJudiciaryAction->action_date ?: 'غير محدد';

        $reasons[] = 'آخر إجراء: ' . $lastActionName . ' (بتاريخ ' . $lastActionDate . ')';
        $reasons[] = 'مضى ' . $this->daysSinceLastJudiciaryAction . ' يوم منذ آخر إجراء';
        $reasons[] = 'المرحلة الحالية: ' . $this->getStageLabel($this->judiciaryStage);
        $reasons[] = 'إجمالي الإجراءات المسجلة: ' . count($this->judiciaryActions);

        // Determine action based on stage and time
        switch ($this->judiciaryStage) {
            case 'case_preparation':
                $action = 'تسجيل الدعوى بالمحكمة — مرحلة التجهيز مكتملة';
                $nextStageLabel = $this->getStageLabel('case_registration');
                $reasons[] = 'الخطوة القادمة: ' . $nextStageLabel;
                $confidence = 88;
                break;

            case 'case_registration':
                if ($this->daysSinceLastJudiciaryAction < 20) {
                    $remaining = 20 - $this->daysSinceLastJudiciaryAction;
                    $action = 'انتظار فترة التبليغ — متبقي ~' . $remaining . ' يوم';
                    $reasons[] = 'فترة التبليغ المعتادة ~20 يوم من تاريخ التسجيل';
                    $confidence = 82;
                } else {
                    $action = 'انتهت فترة التبليغ — طلب حجز ثلث الراتب والأموال';
                    $reasons[] = 'مضى أكثر من 20 يوم على التبليغ';
                    $confidence = 90;
                }
                break;

            case 'notification':
                $action = 'طلب حجز ثلث الراتب + حجز الأموال المنقولة وغير المنقولة';
                $reasons[] = 'التبليغ تم — الخطوة التالية: طلبات الحجز';
                $confidence = 87;
                break;

            case 'salary_deduction':
                if ($this->daysSinceLastJudiciaryAction > 30) {
                    $action = 'متابعة ورود الاقتطاعات — لم يرد اقتطاع منذ ' . $this->daysSinceLastJudiciaryAction . ' يوم';
                    $reasons[] = 'يجب إرسال كتاب استفسار عن عدم ورود اقتطاع';
                    $confidence = 85;
                } else {
                    $action = 'متابعة حسم الراتب + النظر في إجراءات إضافية';
                    $reasons[] = 'الحسم جاري — يُنصح بمتابعة وإضافة حجوزات على الأصول إن لم تُنفذ';
                    $confidence = 78;
                }
                break;

            case 'arrest_detention':
                $action = 'متابعة تنفيذ أوامر القبض/الحبس + متابعة الاتصال بالعميل';
                $reasons[] = 'صدر أمر قبض/حبس — يجب متابعة التنفيذ';
                $reasons[] = 'الاتصال بالعميل قد يؤدي لتسوية قبل الحبس الفعلي';
                $confidence = 83;
                break;

            case 'asset_seizure':
                $action = 'متابعة تنفيذ الحجوزات + انتظار نتائج المحكمة';
                $reasons[] = 'تم طلب حجز أصول — يجب متابعة نتائج التنفيذ';
                if ($this->daysSinceLastJudiciaryAction > 14) {
                    $reasons[] = 'مضى أكثر من أسبوعين — يُنصح بالمثابرة على التنفيذ';
                }
                $confidence = 80;
                break;

            case 'appeal_cancellation':
                $action = 'متابعة نتيجة الاستئناف + إعداد خطة بديلة';
                $reasons[] = 'يوجد استئناف أو إلغاء — يجب متابعة النتيجة';
                $confidence = 75;
                break;

            case 'settlement_closure':
                $action = 'مراجعة التسوية القضائية — التحقق من الالتزام بالدفعات';
                $reasons[] = 'تمت تسوية قضائية — يجب التأكد من الالتزام';
                $confidence = 80;
                break;

            case 'court_decision':
                $action = 'تنفيذ القرار القضائي — متابعة صدور الكتب';
                $reasons[] = 'صدر قرار — يجب متابعة إصدار كتب التنفيذ وإيصالها';
                $confidence = 88;
                break;

            default:
                // Stale case — no recent actions
                if ($this->daysSinceLastJudiciaryAction > 30) {
                    $action = 'تحريك القضية — لا إجراء منذ ' . $this->daysSinceLastJudiciaryAction . ' يوم';
                    $reasons[] = 'القضية ساكنة — يجب المثابرة على التنفيذ منعاً للترك';
                    $confidence = 90;
                } else {
                    $action = 'إضافة إجراء قضائي جديد على ملف القضية';
                    $confidence = 80;
                }
                break;
        }

        // Add collection reminder (companies that continue collections during judiciary)
        $reasons[] = 'لا تنسَ: التحصيل مستمر بالتوازي مع الإجراءات القضائية';

        return [
            'action' => $action,
            'action_type' => 'add_judiciary_action',
            'reasons' => $reasons,
            'confidence' => $confidence,
            'risk_impact' => 'high',
            'icon' => $icon,
        ];
    }

    private function criticalAction()
    {
        $reasons = [];
        if ($this->brokenPromises >= 3) {
            $reasons[] = $this->brokenPromises . ' وعود دفع غير منفذة (نمط عدم التزام)';
        }
        if ($this->dpd > 30) {
            $reasons[] = 'تأخير ' . $this->dpd . ' يوم (تجاوز الحد الأقصى)';
        }
        $reasons[] = 'مستوى المخاطر: ' . $this->riskLevelArabic($this->risk['level']);
        $reasons[] = 'يجب التصعيد الفوري لحماية حقوق الشركة';

        return [
            'action' => 'تصعيد فوري + إعداد ملف إنذار',
            'action_type' => 'escalate',
            'reasons' => $reasons,
            'confidence' => 85,
            'risk_impact' => 'high',
            'icon' => 'fa-exclamation-triangle',
        ];
    }

    private function moderateLateAction()
    {
        $reasons = [
            'تأخير ' . $this->dpd . ' يوم (تأخير متوسط)',
            'مبلغ متأخر: ' . number_format($this->calc->deservedAmount()) . ' د.أ',
        ];

        if ($this->brokenPromises > 0) {
            $reasons[] = 'يوجد ' . $this->brokenPromises . ' وعد/وعود دفع غير منفذة';
            return [
                'action' => 'اتصال + طلب وعد دفع مع جدولة زيارة ميدانية',
                'action_type' => 'call',
                'reasons' => $reasons,
                'confidence' => 80,
                'risk_impact' => 'medium',
                'icon' => 'fa-phone',
            ];
        }

        $reasons[] = 'المطلوب الاتصال بالعميل وأخذ وعد دفع ملزم';

        return [
            'action' => 'اتصال + وعد دفع إلزامي',
            'action_type' => 'call',
            'reasons' => $reasons,
            'confidence' => 82,
            'risk_impact' => 'medium',
            'icon' => 'fa-phone',
        ];
    }

    private function mildLateAction()
    {
        return [
            'action' => 'اتصال تذكيري + إرسال تنبيه',
            'action_type' => 'call',
            'reasons' => [
                'تأخير بسيط (' . $this->dpd . ' يوم)',
                'التذكير المبكر يزيد احتمال الدفع بنسبة عالية',
                'مبلغ متأخر: ' . number_format($this->calc->deservedAmount()) . ' د.أ',
            ],
            'confidence' => 88,
            'risk_impact' => 'low',
            'icon' => 'fa-phone',
        ];
    }

    private function noContactAction()
    {
        return [
            'action' => 'إجراء أول اتصال مع العميل',
            'action_type' => 'call',
            'reasons' => [
                'لا يوجد سجل تواصل سابق مع العميل',
                'التواصل الأول يؤسس للعلاقة ويحدد نمط السداد',
                'مطلوب تسجيل بيانات التواصل وتقييم الوضع',
            ],
            'confidence' => 92,
            'risk_impact' => 'low',
            'icon' => 'fa-phone',
        ];
    }

    private function staleContactAction($days)
    {
        return [
            'action' => 'اتصال متابعة (لا تواصل منذ ' . $days . ' يوم)',
            'action_type' => 'call',
            'reasons' => [
                'مضى ' . $days . ' يوم بدون أي تواصل',
                'الفترة الطويلة بدون تواصل تزيد مخاطر عدم السداد',
                'يجب استعادة التواصل وتحديث وضع العميل',
            ],
            'confidence' => 85,
            'risk_impact' => 'medium',
            'icon' => 'fa-phone',
        ];
    }

    private function routineAction()
    {
        $overdue = $this->calc->deservedAmount();
        if ($overdue > 0) {
            return [
                'action' => 'متابعة دورية — تحصيل مستحقات',
                'action_type' => 'call',
                'reasons' => [
                    'مبلغ مستحق: ' . number_format($overdue) . ' د.أ',
                    'الوضع العام مستقر ولكن يوجد مستحقات',
                    'المتابعة الدورية تحافظ على معدل التحصيل',
                ],
                'confidence' => 75,
                'risk_impact' => 'low',
                'icon' => 'fa-phone',
            ];
        }

        return [
            'action' => 'العقد بوضع جيد — لا إجراء مطلوب حالياً',
            'action_type' => 'none',
            'reasons' => [
                'جميع الأقساط المستحقة مدفوعة',
                'لا توجد وعود دفع معلقة',
                'العقد ملتزم بالسداد',
            ],
            'confidence' => 95,
            'risk_impact' => 'low',
            'icon' => 'fa-check-circle',
        ];
    }

    /**
     * Get alternative actions — FILTERED by contract status
     */
    private function getAlternatives($primaryType, $isLegal = false)
    {
        if ($isLegal) {
            // For legal contracts: judiciary-focused alternatives
            $all = [
                ['action' => 'إضافة إجراء قضائي', 'type' => 'add_judiciary_action', 'icon' => 'fa-gavel'],
                ['action' => 'تسجيل اتصال', 'type' => 'call', 'icon' => 'fa-phone'],
                ['action' => 'إرسال تذكير SMS', 'type' => 'sms', 'icon' => 'fa-comment'],
                ['action' => 'فتح ملف القضية', 'type' => 'open_case', 'icon' => 'fa-folder-open'],
                ['action' => 'تسجيل وعد دفع', 'type' => 'promise', 'icon' => 'fa-handshake-o'],
                ['action' => 'إضافة ملاحظة', 'type' => 'note', 'icon' => 'fa-sticky-note'],
            ];
        } else {
            // For normal contracts
            $all = [
                ['action' => 'تسجيل اتصال', 'type' => 'call', 'icon' => 'fa-phone'],
                ['action' => 'تسجيل وعد دفع', 'type' => 'promise', 'icon' => 'fa-handshake-o'],
                ['action' => 'جدولة زيارة ميدانية', 'type' => 'visit', 'icon' => 'fa-car'],
                ['action' => 'إرسال تذكير SMS', 'type' => 'sms', 'icon' => 'fa-comment'],
                ['action' => 'تحويل للقضائي', 'type' => 'legal', 'icon' => 'fa-gavel'],
                ['action' => 'طلب مراجعة مدير', 'type' => 'review', 'icon' => 'fa-user-circle'],
                ['action' => 'إضافة ملاحظة', 'type' => 'note', 'icon' => 'fa-sticky-note'],
            ];
        }

        // Return 2 alternatives that are not the primary type
        $filtered = array_filter($all, function ($item) use ($primaryType) {
            return $item['type'] !== $primaryType;
        });

        return array_slice(array_values($filtered), 0, 2);
    }

    /**
     * Determine which playbook applies
     */
    public function getActivePlaybook()
    {
        $status = $this->contract->status;

        if (in_array($status, ['judiciary', 'legal_department'])) {
            return $this->playbookD();
        }

        if ($this->brokenPromises >= 3 || $this->dpd > 30) {
            return $this->playbookC();
        }

        if ($this->dpd > 7 && $this->dpd <= 30) {
            return $this->playbookB();
        }

        if ($this->dpd > 0 && $this->dpd <= 7) {
            return $this->playbookA();
        }

        return null;
    }

    private function playbookA()
    {
        $steps = [
            ['when' => 'اليوم', 'what' => 'اتصال تذكيري + إرسال تنبيه SMS', 'done' => false],
            ['when' => 'بعد 48 ساعة', 'what' => 'إذا لم يدفع: أخذ وعد دفع إلزامي', 'done' => false],
            ['when' => 'بعد 5 أيام', 'what' => 'إذا لم يستجب: تصعيد داخلي (ليس قضائي)', 'done' => false],
        ];

        $recentFollowUps = $this->getRecentFollowUps(7);
        if (count($recentFollowUps) > 0) {
            $steps[0]['done'] = true;
        }

        return [
            'id' => 'A',
            'name' => 'تأخير بسيط (1-7 أيام)',
            'steps' => $steps,
            'current_step' => $this->getCurrentStep($steps),
        ];
    }

    private function playbookB()
    {
        $steps = [
            ['when' => 'اليوم', 'what' => 'اتصال + وعد دفع + تحديد موعد مراجعة', 'done' => false],
            ['when' => 'موعد الوعد', 'what' => 'إذا وعد غير منفذ: زيارة/تحصيل ميداني', 'done' => false],
            ['when' => 'بعد أسبوع', 'what' => 'إذا لا استجابة: تصعيد للإدارة', 'done' => false],
        ];

        $recentFollowUps = $this->getRecentFollowUps(14);
        if (count($recentFollowUps) > 0) {
            $steps[0]['done'] = true;
        }

        return [
            'id' => 'B',
            'name' => 'تأخير متوسط (8-30 يوم)',
            'steps' => $steps,
            'current_step' => $this->getCurrentStep($steps),
        ];
    }

    private function playbookC()
    {
        $steps = [
            ['when' => 'فوراً', 'what' => 'تصعيد مباشر للإدارة', 'done' => false],
            ['when' => 'خلال 48 ساعة', 'what' => 'إعداد ملف إنذار رسمي', 'done' => false],
            ['when' => 'خلال أسبوع', 'what' => 'طلب موافقة مدير على الإجراء القضائي', 'done' => false],
        ];

        return [
            'id' => 'C',
            'name' => 'تأخير حرج (+30 يوم أو 3+ وعود مخلفة)',
            'steps' => $steps,
            'current_step' => 1,
        ];
    }

    /**
     * Playbook D — Real judiciary workflow based on actual data
     * Stages: تجهيز → تسجيل → تبليغ (20 يوم) → طلب حجز → انتظار قرار → إصدار كتب → متابعة تنفيذ
     */
    private function playbookD()
    {
        $steps = [
            ['when' => 'المرحلة 1', 'what' => 'تجهيز أوراق الدعوى', 'done' => false, 'stage' => 'case_preparation'],
            ['when' => 'المرحلة 2', 'what' => 'تسجيل الدعوى بالمحكمة', 'done' => false, 'stage' => 'case_registration'],
            ['when' => 'المرحلة 3', 'what' => 'التبليغ والإخطار (~20 يوم)', 'done' => false, 'stage' => 'notification'],
            ['when' => 'المرحلة 4', 'what' => 'طلب حجز ثلث الراتب + حجز الأموال المنقولة وغير المنقولة', 'done' => false, 'stage' => 'salary_deduction'],
            ['when' => 'المرحلة 5', 'what' => 'القبض/الحبس/منع السفر', 'done' => false, 'stage' => 'arrest_detention'],
            ['when' => 'المرحلة 6', 'what' => 'حجز أصول (مركبات، عقارات، حسابات بنكية)', 'done' => false, 'stage' => 'asset_seizure'],
            ['when' => 'المرحلة 7', 'what' => 'متابعة التنفيذ + التحصيل المستمر', 'done' => false, 'stage' => 'court_decision'],
        ];

        // Auto-detect completed stages from actual judiciary actions
        if (!empty($this->judiciaryActions)) {
            $completedStages = [];
            foreach ($this->judiciaryActions as $action) {
                $actionId = $action->judiciary_actions_id;
                foreach (self::$STAGE_MAP as $stage => $ids) {
                    if (in_array($actionId, $ids)) {
                        $completedStages[$stage] = true;
                    }
                }
            }

            // Mark completed stages
            foreach ($steps as $i => &$step) {
                if (isset($completedStages[$step['stage']])) {
                    $step['done'] = true;
                }
            }
            unset($step);
        }

        // Find current step
        $currentStep = 1;
        foreach ($steps as $i => $step) {
            if (!$step['done']) {
                $currentStep = $i + 1;
                break;
            }
            $currentStep = count($steps); // All done
        }

        // Add real data context
        $name = 'المرحلة القضائية';
        if ($this->judiciary) {
            $name .= ' — قضية ' . ($this->judiciary->judiciary_number ?: '-') . '/' . ($this->judiciary->year ?: '-');
        }

        return [
            'id' => 'D',
            'name' => $name,
            'steps' => $steps,
            'current_step' => $currentStep,
        ];
    }

    /**
     * Get recent follow-ups within N days
     */
    private function getRecentFollowUps($days)
    {
        return FollowUp::find()
            ->where(['contract_id' => $this->contract->id])
            ->andWhere(['>=', 'date_time', date('Y-m-d H:i:s', strtotime("-{$days} days"))])
            ->all();
    }

    /**
     * Determine current step in playbook
     */
    private function getCurrentStep($steps)
    {
        foreach ($steps as $i => $step) {
            if (!$step['done']) {
                return $i + 1;
            }
        }
        return count($steps);
    }

    private function riskLevelArabic($level)
    {
        $map = [
            'low' => 'منخفض',
            'med' => 'متوسط',
            'high' => 'مرتفع',
            'critical' => 'حرج',
        ];
        return isset($map[$level]) ? $map[$level] : $level;
    }

    /**
     * Get judiciary data for external use (e.g., by the controller for alerts)
     */
    public function getJudiciaryData()
    {
        return [
            'judiciary' => $this->judiciary,
            'actions' => $this->judiciaryActions,
            'last_action' => $this->lastJudiciaryAction,
            'days_since_last' => $this->daysSinceLastJudiciaryAction,
            'stage' => $this->judiciaryStage,
            'stage_label' => $this->getStageLabel($this->judiciaryStage),
            'per_party' => $this->getPerPartyLastActions(),
            'action_tree' => $this->buildActionTree(),
        ];
    }

    /**
     * Build a tree structure: requests → documents → statuses
     * Groups actions by customer, then builds parent-child relationships
     * For old data (no parent_id), uses action definition relationships
     */
    private function buildActionTree()
    {
        if (!$this->judiciary || empty($this->judiciaryActions)) {
            return [];
        }

        // Load all action definitions for nature lookup
        $actionDefs = \backend\modules\judiciaryActions\models\JudiciaryActions::find()
            ->andWhere(['or', ['is_deleted' => 0], ['is_deleted' => null]])
            ->indexBy('id')
            ->all();

        // Group all actions by customer
        $byCustomer = [];
        foreach ($this->judiciaryActions as $action) {
            $cid = $action->customers_id ?: 0;
            $byCustomer[$cid][] = $action;
        }

        $tree = [];
        foreach ($byCustomer as $cid => $actions) {
            $customerName = '';
            if (!empty($actions) && $actions[0]->customers) {
                $customerName = $actions[0]->customers->name;
            }

            // Determine customer type
            $customerType = 'unknown';
            $contractCustomers = \backend\modules\customers\models\ContractsCustomers::find()
                ->where(['contract_id' => $this->contract->id, 'customer_id' => $cid])
                ->one();
            if ($contractCustomers) {
                $customerType = $contractCustomers->customer_type;
            }

            // Separate by nature
            $requests = [];
            $documents = [];
            $statuses = [];
            $processes = [];

            foreach ($actions as $act) {
                $def = $actionDefs[$act->judiciary_actions_id] ?? null;
                $nature = $def ? $def->action_nature : null;

                switch ($nature) {
                    case 'request':   $requests[]  = $act; break;
                    case 'document':  $documents[] = $act; break;
                    case 'doc_status':$statuses[]  = $act; break;
                    case 'process':   $processes[] = $act; break;
                    default:          $processes[] = $act; break; // treat unknown as process
                }
            }

            // Track which documents/statuses got linked
            $linkedDocIds = [];
            $linkedStatusIds = [];

            // Build request trees
            $requestTrees = [];
            foreach ($requests as $req) {
                $reqDef = $actionDefs[$req->judiciary_actions_id] ?? null;
                $allowedDocActionIds = $reqDef ? $reqDef->getAllowedDocumentIds() : [];

                // Find documents that belong to this request
                $reqDocs = [];
                foreach ($documents as $doc) {
                    if (in_array($doc->id, $linkedDocIds)) continue; // already linked

                    $docDef = $actionDefs[$doc->judiciary_actions_id] ?? null;
                    $parentReqActionIds = $docDef ? $docDef->getParentRequestIdList() : [];

                    // Match by explicit parent_id OR by action definition relationship
                    $matched = false;
                    if ($doc->parent_id && $doc->parent_id == $req->id) {
                        $matched = true;
                    } elseif (!$doc->parent_id && in_array($req->judiciary_actions_id, $parentReqActionIds)) {
                        $matched = true;
                    } elseif (!$doc->parent_id && in_array($doc->judiciary_actions_id, $allowedDocActionIds)) {
                        $matched = true;
                    }

                    if ($matched) {
                        $linkedDocIds[] = $doc->id;

                        // Find statuses for this document
                        $docStatuses = [];
                        $docDef2 = $actionDefs[$doc->judiciary_actions_id] ?? null;
                        $allowedStatusActionIds = $docDef2 ? $docDef2->getAllowedStatusIds() : [];

                        foreach ($statuses as $st) {
                            if (in_array($st->id, $linkedStatusIds)) continue;

                            $stDef = $actionDefs[$st->judiciary_actions_id] ?? null;
                            $stParentDocIds = $stDef ? $stDef->getParentRequestIdList() : [];

                            $stMatched = false;
                            if ($st->parent_id && $st->parent_id == $doc->id) {
                                $stMatched = true;
                            } elseif (!$st->parent_id && in_array($doc->judiciary_actions_id, $stParentDocIds)) {
                                $stMatched = true;
                            } elseif (!$st->parent_id && in_array($st->judiciary_actions_id, $allowedStatusActionIds)) {
                                $stMatched = true;
                            }

                            if ($stMatched) {
                                $linkedStatusIds[] = $st->id;
                                $docStatuses[] = [
                                    'action' => $st,
                                    'def' => $stDef,
                                    'name' => $stDef ? $stDef->name : 'غير محدد',
                                    'is_current' => $st->is_current,
                                ];
                            }
                        }

                        // Sort statuses by date descending
                        usort($docStatuses, function ($a, $b) {
                            return strtotime($b['action']->action_date ?: '1970-01-01') - strtotime($a['action']->action_date ?: '1970-01-01');
                        });

                        $reqDocs[] = [
                            'action' => $doc,
                            'def' => $docDef,
                            'name' => $docDef ? $docDef->name : 'غير محدد',
                            'statuses' => $docStatuses,
                        ];
                    }
                }

                // Sort documents by date descending
                usort($reqDocs, function ($a, $b) {
                    return strtotime($b['action']->action_date ?: '1970-01-01') - strtotime($a['action']->action_date ?: '1970-01-01');
                });

                $requestTrees[] = [
                    'action' => $req,
                    'def' => $reqDef,
                    'name' => $reqDef ? $reqDef->name : 'غير محدد',
                    'request_status' => $req->request_status,
                    'documents' => $reqDocs,
                ];
            }

            // Sort requests by date descending
            usort($requestTrees, function ($a, $b) {
                return strtotime($b['action']->action_date ?: '1970-01-01') - strtotime($a['action']->action_date ?: '1970-01-01');
            });

            // Orphan documents (not linked to any request)
            $orphanDocs = [];
            foreach ($documents as $doc) {
                if (!in_array($doc->id, $linkedDocIds)) {
                    $docDef = $actionDefs[$doc->judiciary_actions_id] ?? null;

                    // Try to find orphan statuses for this doc
                    $docStatuses = [];
                    $allowedStatusActionIds = $docDef ? $docDef->getAllowedStatusIds() : [];
                    foreach ($statuses as $st) {
                        if (in_array($st->id, $linkedStatusIds)) continue;
                        $stDef = $actionDefs[$st->judiciary_actions_id] ?? null;
                        $stParentDocIds = $stDef ? $stDef->getParentRequestIdList() : [];

                        if (($st->parent_id && $st->parent_id == $doc->id)
                            || (!$st->parent_id && in_array($doc->judiciary_actions_id, $stParentDocIds))
                            || (!$st->parent_id && in_array($st->judiciary_actions_id, $allowedStatusActionIds))) {
                            $linkedStatusIds[] = $st->id;
                            $docStatuses[] = [
                                'action' => $st,
                                'def' => $stDef,
                                'name' => $stDef ? $stDef->name : 'غير محدد',
                                'is_current' => $st->is_current,
                            ];
                        }
                    }

                    $orphanDocs[] = [
                        'action' => $doc,
                        'def' => $docDef,
                        'name' => $docDef ? $docDef->name : 'غير محدد',
                        'statuses' => $docStatuses,
                    ];
                }
            }

            // Orphan statuses (not linked to any document)
            $orphanStatuses = [];
            foreach ($statuses as $st) {
                if (!in_array($st->id, $linkedStatusIds)) {
                    $stDef = $actionDefs[$st->judiciary_actions_id] ?? null;
                    $orphanStatuses[] = [
                        'action' => $st,
                        'def' => $stDef,
                        'name' => $stDef ? $stDef->name : 'غير محدد',
                        'is_current' => $st->is_current,
                    ];
                }
            }

            $tree[] = [
                'customer_id' => $cid,
                'customer_name' => $customerName,
                'customer_type' => $customerType,
                'requests' => $requestTrees,
                'processes' => $processes,
                'orphan_documents' => $orphanDocs,
                'orphan_statuses' => $orphanStatuses,
            ];
        }

        return $tree;
    }

    /**
     * Get last action per party (customer) for the judiciary case
     */
    private function getPerPartyLastActions()
    {
        if (!$this->judiciary || empty($this->judiciaryActions)) {
            return [];
        }

        // Load action definitions for nature info
        $actionDefs = \backend\modules\judiciaryActions\models\JudiciaryActions::find()
            ->where(['is_deleted' => 0])
            ->indexBy('id')
            ->all();

        // Group actions by customer — find last REQUEST action per customer
        $lastRequestByCustomer = [];
        $lastAnyByCustomer = [];
        $actionCounts = [];

        foreach ($this->judiciaryActions as $action) {
            $cid = $action->customers_id;
            $actionCounts[$cid] = ($actionCounts[$cid] ?? 0) + 1;

            // Track last of any type
            $aDate = $action->action_date ? strtotime($action->action_date) : 0;
            if (!isset($lastAnyByCustomer[$cid]) || $aDate > ($lastAnyByCustomer[$cid]->action_date ? strtotime($lastAnyByCustomer[$cid]->action_date) : 0)) {
                $lastAnyByCustomer[$cid] = $action;
            }

            // Track last request specifically
            $def = $actionDefs[$action->judiciary_actions_id] ?? null;
            if ($def && $def->action_nature === 'request') {
                if (!isset($lastRequestByCustomer[$cid]) || $aDate > ($lastRequestByCustomer[$cid]->action_date ? strtotime($lastRequestByCustomer[$cid]->action_date) : 0)) {
                    $lastRequestByCustomer[$cid] = $action;
                }
            }
        }

        // Customer types
        $customerTypes = [];
        $contractCustomers = \backend\modules\customers\models\ContractsCustomers::find()
            ->where(['contract_id' => $this->contract->id])
            ->all();
        foreach ($contractCustomers as $cc) {
            $customerTypes[$cc->customer_id] = $cc->customer_type;
        }

        $result = [];
        foreach ($lastAnyByCustomer as $cid => $lastAction) {
            $customerName = $lastAction->customers ? $lastAction->customers->name : 'غير محدد';
            $actionDef = $actionDefs[$lastAction->judiciary_actions_id] ?? null;
            $actionName = $actionDef ? $actionDef->name : 'غير محدد';
            $actionNature = $actionDef ? $actionDef->action_nature : 'unknown';
            $actionDate = $lastAction->action_date;
            $daysSince = $actionDate ? max(0, (int)((strtotime('today') - strtotime($actionDate)) / 86400)) : 999;

            // Use action_type from definition for stage
            $stage = $actionDef ? $actionDef->action_type : 'general';

            // Also get last request info
            $lastReq = $lastRequestByCustomer[$cid] ?? null;
            $lastReqDef = $lastReq ? ($actionDefs[$lastReq->judiciary_actions_id] ?? null) : null;

            $result[] = [
                'customer_id' => $cid,
                'customer_name' => $customerName,
                'customer_type' => $customerTypes[$cid] ?? 'unknown',
                'customer_type_label' => ($customerTypes[$cid] ?? '') === 'client' ? 'مدين' : (($customerTypes[$cid] ?? '') === 'guarantor' ? 'كفيل' : 'طرف'),
                'last_action_name' => $actionName,
                'last_action_nature' => $actionNature,
                'last_action_date' => $actionDate,
                'days_since_last_action' => $daysSince,
                'action_stage' => $stage,
                'action_stage_label' => $this->getStageLabel($stage),
                'total_actions' => $actionCounts[$cid] ?? 0,
                // Last request info (most meaningful for display)
                'last_request_name' => $lastReqDef ? $lastReqDef->name : null,
                'last_request_status' => $lastReq ? $lastReq->request_status : null,
                'last_request_date' => $lastReq ? $lastReq->action_date : null,
            ];
        }

        usort($result, function ($a, $b) {
            return $b['days_since_last_action'] - $a['days_since_last_action'];
        });

        return $result;
    }
}
