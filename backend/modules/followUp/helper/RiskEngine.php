<?php

namespace backend\modules\followUp\helper;

use Yii;
use backend\modules\contracts\models\Contracts;
use backend\modules\followUp\models\FollowUp;
use backend\modules\followUp\helper\ContractCalculations;

/**
 * Risk Engine — Rule-based risk assessment for contracts
 * 
 * Stage 1: Pure rule-based scoring (0–100)
 * Stage 2: Weighted scoring with configurable weights
 * Stage 3: ML integration (future)
 */
class RiskEngine
{
    private $contract;
    private $calc;
    private $signals = [];
    private $score = 0;

    /**
     * @param Contracts $contract
     */
    public function __construct($contract)
    {
        $this->contract = $contract;
        $this->calc = new ContractCalculations($contract->id);
    }

    /**
     * Calculate complete risk assessment
     * @return array ['level', 'score', 'signals', 'primary_reason']
     */
    public function assess()
    {
        $this->signals = [];
        $this->score = 0;

        $this->assessDPD();
        $this->assessPaymentHistory();
        $this->assessPromiseHistory();
        $this->assessContactHistory();
        $this->assessLegalStatus();
        $this->assessFinancialPosition();

        // Clamp score 0–100
        $this->score = max(0, min(100, $this->score));

        $level = $this->scoreToLevel($this->score);

        // Get primary reason (highest weight signal)
        $primaryReason = '';
        if (!empty($this->signals)) {
            usort($this->signals, function ($a, $b) {
                return $b['weight'] - $a['weight'];
            });
            $primaryReason = $this->signals[0]['reason'];
        }

        return [
            'level' => $level,
            'score' => $this->score,
            'signals' => $this->signals,
            'primary_reason' => $primaryReason,
        ];
    }

    /**
     * Days Past Due assessment
     */
    private function assessDPD()
    {
        $dpd = $this->getDPD();
        
        if ($dpd <= 0) {
            // No delay
            return;
        } elseif ($dpd <= 7) {
            $this->addSignal('dpd_low', 10, 'تأخير بسيط (' . $dpd . ' يوم)');
        } elseif ($dpd <= 30) {
            $this->addSignal('dpd_medium', 25, 'تأخير متوسط (' . $dpd . ' يوم)');
        } elseif ($dpd <= 60) {
            $this->addSignal('dpd_high', 35, 'تأخير كبير (' . $dpd . ' يوم)');
        } else {
            $this->addSignal('dpd_critical', 45, 'تأخير حرج (' . $dpd . ' يوم+)');
        }
    }

    /**
     * Payment history assessment
     */
    private function assessPaymentHistory()
    {
        $total = $this->calc->getContractTotal();
        $paid = $this->calc->paidAmount();
        $shouldPaid = $this->calc->amountShouldBePaid();

        if ($total <= 0) return;

        $paidRatio = $paid / $total;
        $complianceRatio = ($shouldPaid > 0) ? ($paid / $shouldPaid) : 1;

        if ($complianceRatio < 0.3) {
            $this->addSignal('low_compliance', 30, 'نسبة الالتزام أقل من 30%');
        } elseif ($complianceRatio < 0.5) {
            $this->addSignal('med_compliance', 20, 'نسبة الالتزام أقل من 50%');
        } elseif ($complianceRatio < 0.8) {
            $this->addSignal('fair_compliance', 10, 'نسبة الالتزام متوسطة');
        }

        // Overdue amount check
        $overdue = $this->calc->deservedAmount();
        if ($overdue > 0 && $total > 0) {
            $overdueRatio = $overdue / $total;
            if ($overdueRatio > 0.5) {
                $this->addSignal('high_overdue', 15, 'المبلغ المتأخر يتجاوز 50% من إجمالي العقد');
            }
        }
    }

    /**
     * Promise-to-pay history assessment
     */
    private function assessPromiseHistory()
    {
        $brokenPromises = $this->getBrokenPromisesCount();

        if ($brokenPromises >= 3) {
            $this->addSignal('many_broken_promises', 30, $brokenPromises . ' وعود دفع غير منفذة');
        } elseif ($brokenPromises >= 1) {
            $this->addSignal('some_broken_promises', 15, 'وعد دفع غير منفذ');
        }
    }

    /**
     * Contact history assessment
     */
    private function assessContactHistory()
    {
        $lastContact = $this->getLastContactDate();

        if ($lastContact === null) {
            $this->addSignal('no_contact', 20, 'لا يوجد تواصل سابق');
            return;
        }

        $daysSinceContact = (strtotime('today') - strtotime($lastContact)) / 86400;

        if ($daysSinceContact > 30) {
            $this->addSignal('no_contact_30d', 20, 'لا تواصل منذ أكثر من 30 يوم');
        } elseif ($daysSinceContact > 14) {
            $this->addSignal('no_contact_14d', 10, 'لا تواصل منذ أكثر من 14 يوم');
        }
    }

    /**
     * Legal status assessment
     */
    private function assessLegalStatus()
    {
        if (in_array($this->contract->status, ['judiciary', 'legal_department'])) {
            $this->addSignal('legal_active', 20, 'ملف قضائي مفتوح');
        }
    }

    /**
     * Financial position assessment
     */
    private function assessFinancialPosition()
    {
        $remaining = $this->calc->remainingAmount();
        $total = $this->calc->getContractTotal();

        if ($total > 0 && ($remaining / $total) > 0.9) {
            $this->addSignal('high_remaining', 10, 'المتبقي أكثر من 90% من إجمالي العقد');
        }
    }

    /**
     * Get Days Past Due
     */
    public function getDPD()
    {
        $shouldPaid = $this->calc->amountShouldBePaid();
        $paid = $this->calc->paidAmount();

        if ($paid >= $shouldPaid) {
            return 0;
        }

        // Find the date of the oldest unpaid installment
        $firstInstDate = $this->contract->first_installment_date;
        if (empty($firstInstDate)) return 0;

        $monthlyAmount = $this->contract->monthly_installment_value;
        if ($monthlyAmount <= 0) return 0;

        // How many installments are fully covered
        $coveredInstallments = floor($paid / $monthlyAmount);
        
        // The next unpaid installment date
        $unpaidDate = date('Y-m-d', strtotime($firstInstDate . ' +' . $coveredInstallments . ' months'));

        $today = date('Y-m-d');
        if ($unpaidDate >= $today) return 0;

        return (int)((strtotime($today) - strtotime($unpaidDate)) / 86400);
    }

    /**
     * Get count of broken (expired unfulfilled) promises
     */
    public function getBrokenPromisesCount()
    {
        return (int)FollowUp::find()
            ->where(['contract_id' => $this->contract->id])
            ->andWhere(['IS NOT', 'promise_to_pay_at', null])
            ->andWhere(['<', 'promise_to_pay_at', date('Y-m-d')])
            ->count();
    }

    /**
     * Get last contact date
     */
    public function getLastContactDate()
    {
        $lastFollowUp = FollowUp::find()
            ->where(['contract_id' => $this->contract->id])
            ->orderBy(['date_time' => SORT_DESC])
            ->one();

        return $lastFollowUp ? $lastFollowUp->date_time : null;
    }

    /**
     * Get last payment date and amount
     */
    public function getLastPayment()
    {
        $payment = Yii::$app->db->createCommand(
            "SELECT date, amount FROM {{%income}} WHERE contract_id = :cid ORDER BY date DESC LIMIT 1",
            [':cid' => $this->contract->id]
        )->queryOne();

        return $payment ?: ['date' => null, 'amount' => 0];
    }

    /**
     * Add a risk signal
     */
    private function addSignal($code, $weight, $reason)
    {
        $this->signals[] = [
            'code' => $code,
            'weight' => $weight,
            'reason' => $reason,
        ];
        $this->score += $weight;
    }

    /**
     * Convert numeric score to risk level
     */
    private function scoreToLevel($score)
    {
        if ($score <= 20) return 'low';
        if ($score <= 45) return 'med';
        if ($score <= 70) return 'high';
        return 'critical';
    }

    /**
     * Get contract status label in Arabic
     */
    public static function statusLabel($status)
    {
        $labels = [
            'pending' => 'معلق',
            'active' => 'نشط',
            'reconciliation' => 'مصالحة',
            'judiciary' => 'قضائي',
            'canceled' => 'ملغي',
            'refused' => 'مرفوض',
            'legal_department' => 'الشؤون القانونية',
            'finished' => 'منتهي',
            'settlement' => 'تسوية',
        ];
        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    /**
     * Map contract status to OCP badge class
     */
    public static function statusBadgeClass($status)
    {
        $map = [
            'active' => 'active',
            'pending' => 'active',
            'reconciliation' => 'settlement',
            'settlement' => 'settlement',
            'judiciary' => 'legal',
            'legal_department' => 'legal',
            'canceled' => 'closed',
            'refused' => 'closed',
            'finished' => 'closed',
        ];
        return isset($map[$status]) ? $map[$status] : 'active';
    }
}
