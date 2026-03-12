<?php
/**
 * RiskEngine — محرك تقييم مخاطر العملاء
 * Phase A: Rule-Based Scoring
 * Phase B: ML Integration (prepared)
 * Phase C: Ensemble (prepared)
 *
 * Score range: 0-100 (lower = better)
 * @author Smart Onboarding System
 */

namespace backend\modules\customers\components;

use Yii;
use yii\base\BaseObject;
use backend\modules\customers\models\Customers;

class RiskEngine extends BaseObject
{
    const VERSION = '1.0';

    /* Risk Tiers */
    const TIER_APPROVED    = 'approved';
    const TIER_CONDITIONAL = 'conditional';
    const TIER_HIGH_RISK   = 'high_risk';
    const TIER_REJECTED    = 'rejected';

    /* Engine Modes */
    const MODE_RULES    = 'rules_only';
    const MODE_ML       = 'ml_only';
    const MODE_ENSEMBLE = 'ensemble';

    /** @var array Factor weights (sum = 100) */
    private static $factorWeights = [
        'age'              => 10,
        'employment'       => 15,
        'income'           => 15,
        'dti'              => 15,
        'documents'        => 10,
        'property'         => 5,
        'social_security'  => 5,
        'references'       => 10,
        'history'          => 10,
        'contact_quality'  => 5,
    ];

    /** @var array Default tier thresholds */
    private static $defaultThresholds = [
        'approved'    => 25,
        'conditional' => 45,
        'high_risk'   => 65,
    ];

    /**
     * Calculate risk score for a customer
     * @param array $data Customer data snapshot
     * @return array Complete assessment result
     */
    public static function assess(array $data): array
    {
        $factors = [];
        $alerts = [];

        /* ── 1. Age Factor (0-10) ── */
        $age = self::calculateAge($data['birth_date'] ?? null);
        $factors['age'] = self::scoreAge($age);

        /* ── 2. Employment (0-15) ── */
        $factors['employment'] = self::scoreEmployment($data);

        /* ── 3. Income (0-15) ── */
        $factors['income'] = self::scoreIncome($data);

        /* ── 4. Debt-to-Income Ratio (0-15) ── */
        $dtiResult = self::scoreDTI($data);
        $factors['dti'] = $dtiResult['score'];
        if ($dtiResult['alert']) $alerts[] = $dtiResult['alert'];

        /* ── 5. Documents (0-10) ── */
        $factors['documents'] = self::scoreDocuments($data);

        /* ── 6. Property (0-5) ── */
        $factors['property'] = self::scoreProperty($data);

        /* ── 7. Social Security (0-5) ── */
        $factors['social_security'] = self::scoreSocialSecurity($data);

        /* ── 8. References (0-10) ── */
        $refResult = self::scoreReferences($data);
        $factors['references'] = $refResult['score'];
        if ($refResult['alert']) $alerts[] = $refResult['alert'];

        /* ── 9. History (0-10) ── */
        $factors['history'] = self::scoreHistory($data);

        /* ── 10. Contact Quality (0-5) ── */
        $factors['contact_quality'] = self::scoreContactQuality($data);

        /* ── Calculate totals ── */
        $ruleScore = array_sum($factors);
        $ruleScore = max(0, min(100, $ruleScore));

        /* ── Profile completeness ── */
        $profilePct = self::calculateCompleteness($data);

        /* ── Tier determination ── */
        $thresholds = self::getThresholds();
        $tier = self::determineTier($ruleScore, $thresholds);

        /* ── Top factors (sorted by impact) ── */
        arsort($factors);
        $topFactors = array_slice($factors, 0, 5, true);

        /* ── Human-readable factors ── */
        $factorLabels = self::getFactorLabels();
        $topFactorsLabeled = [];
        foreach ($topFactors as $key => $score) {
            $max = self::$factorWeights[$key] ?? 10;
            $pct = $max > 0 ? round(($score / $max) * 100) : 0;
            $topFactorsLabeled[] = [
                'key'       => $key,
                'label'     => $factorLabels[$key] ?? $key,
                'score'     => $score,
                'max'       => $max,
                'pct'       => $pct,
                'impact'    => $pct > 60 ? 'negative' : ($pct > 30 ? 'neutral' : 'positive'),
            ];
        }

        /* ── Financing recommendations ── */
        $financing = self::calculateFinancing($data, $tier);

        /* ── Smart alerts ── */
        $alerts = array_merge($alerts, self::generateAlerts($data, $ruleScore, $profilePct));

        /* ── Decision reasons ── */
        $reasons = self::generateReasons($topFactorsLabeled, $tier, $profilePct);

        return [
            'rule_score'    => round($ruleScore, 2),
            'ml_score'      => null,
            'final_score'   => round($ruleScore, 2),
            'risk_tier'     => $tier,
            'profile_pct'   => $profilePct,
            'top_factors'   => $topFactorsLabeled,
            'all_factors'   => $factors,
            'reasons'       => $reasons,
            'alerts'        => $alerts,
            'financing'     => $financing,
            'rules_version' => self::VERSION,
            'model_version' => null,
            'engine_mode'   => self::MODE_RULES,
            'score_weights' => ['rule' => 1.0, 'ml' => 0.0],
        ];
    }

    /* ================================================================
       INDIVIDUAL FACTOR SCORING
       ================================================================ */

    private static function scoreAge(?int $age): float
    {
        if ($age === null) return 8;
        if ($age < 21)  return 10;
        if ($age <= 25) return 6;
        if ($age <= 45) return 0;
        if ($age <= 55) return 3;
        return 7;
    }

    private static function scoreEmployment(array $d): float
    {
        $type = $d['employment_type'] ?? null;
        $years = (float)($d['years_at_job'] ?? 0);

        $map = [
            'government'    => 0,
            'military'      => 0,
            'private'       => 5,
            'retired'       => 4,
            'self_employed' => 9,
            'unemployed'    => 15,
        ];
        $base = isset($map[$type]) ? $map[$type] : 10;

        // Stability bonus
        if ($years >= 5) $base = max(0, $base - 3);
        elseif ($years >= 2) $base = max(0, $base - 1);
        elseif ($years < 0.5 && $type !== 'unemployed') $base = min(15, $base + 2);

        return min(15, $base);
    }

    private static function scoreIncome(array $d): float
    {
        $salary = (float)($d['total_salary'] ?? 0);
        $additional = (float)($d['additional_income'] ?? 0);
        $total = $salary + $additional;

        if ($total <= 0) return 15;
        if ($total < 250) return 14;
        if ($total < 350) return 11;
        if ($total < 500) return 7;
        if ($total < 800) return 3;
        return 0;
    }

    private static function scoreDTI(array $d): array
    {
        $income = (float)($d['total_salary'] ?? 0) + (float)($d['additional_income'] ?? 0);
        $obligations = (float)($d['monthly_obligations'] ?? 0);
        $alert = null;

        if ($income <= 0) return ['score' => 12, 'alert' => null];

        $dti = $obligations / $income;

        if ($dti > 0.70) {
            $alert = [
                'type' => 'danger',
                'icon' => 'exclamation-triangle',
                'message' => 'نسبة الالتزامات مرتفعة جدًا (' . round($dti * 100) . '% من الدخل)',
            ];
        } elseif ($dti > 0.50) {
            $alert = [
                'type' => 'warning',
                'icon' => 'exclamation-circle',
                'message' => 'نسبة الالتزامات عالية (' . round($dti * 100) . '% من الدخل)',
            ];
        }

        if ($dti < 0.20) return ['score' => 0, 'alert' => $alert];
        if ($dti < 0.35) return ['score' => 4, 'alert' => $alert];
        if ($dti < 0.50) return ['score' => 8, 'alert' => $alert];
        if ($dti < 0.70) return ['score' => 12, 'alert' => $alert];
        return ['score' => 15, 'alert' => $alert];
    }

    private static function scoreDocuments(array $d): float
    {
        $docs = (int)($d['documents_count'] ?? 0);
        $required = 3; // ID, salary slip, job letter

        if ($docs >= $required) return 0;
        if ($docs >= 2) return 3;
        if ($docs >= 1) return 6;
        return 10;
    }

    private static function scoreProperty(array $d): float
    {
        return !empty($d['has_property']) ? 0 : 5;
    }

    private static function scoreSocialSecurity(array $d): float
    {
        $ss = $d['is_social_security'] ?? 0;
        $ssIncome = $d['has_ss_salary'] ?? 'no';
        if ($ss && $ssIncome === 'yes') return 0;
        if ($ss) return 2;
        return 5;
    }

    private static function scoreReferences(array $d): array
    {
        $count = (int)($d['references_count'] ?? 0);
        $alert = null;

        if ($count >= 3) return ['score' => 0, 'alert' => null];
        if ($count >= 2) return ['score' => 2, 'alert' => null];

        if ($count <= 0) {
            $alert = [
                'type' => 'warning',
                'icon' => 'users',
                'message' => 'لا يوجد معرّفون — يُنصح بإضافة معرّفَين على الأقل',
            ];
            return ['score' => 10, 'alert' => $alert];
        }

        $alert = [
            'type' => 'info',
            'icon' => 'user-plus',
            'message' => 'معرّف واحد فقط — يُفضّل إضافة معرّف آخر لتحسين التقييم',
        ];
        return ['score' => 5, 'alert' => $alert];
    }

    private static function scoreHistory(array $d): float
    {
        $prevContracts = (int)($d['previous_contracts'] ?? 0);
        $defaulted = (bool)($d['has_defaults'] ?? false);

        if ($prevContracts === 0) return 5; // New customer
        if ($defaulted) return 10;
        return 0; // Good history = bonus
    }

    private static function scoreContactQuality(array $d): float
    {
        $score = 5;
        if (!empty($d['phone'])) $score -= 2;
        if (!empty($d['email'])) $score -= 1;
        if (!empty($d['address_count']) && $d['address_count'] > 0) $score -= 1;
        if (!empty($d['facebook'])) $score -= 1;
        return max(0, $score);
    }

    /* ================================================================
       HELPERS
       ================================================================ */

    private static function calculateAge(?string $birthDate): ?int
    {
        if (!$birthDate) return null;
        try {
            $birth = new \DateTime($birthDate);
            $now = new \DateTime();
            return $now->diff($birth)->y;
        } catch (\Exception $e) {
            return null;
        }
    }

    private static function determineTier(float $score, array $thresholds): string
    {
        if ($score <= $thresholds['approved'])    return self::TIER_APPROVED;
        if ($score <= $thresholds['conditional']) return self::TIER_CONDITIONAL;
        if ($score <= $thresholds['high_risk'])   return self::TIER_HIGH_RISK;
        return self::TIER_REJECTED;
    }

    private static function getThresholds(): array
    {
        try {
            $db = Yii::$app->db;
            $row = $db->createCommand("SELECT config_value FROM os_risk_engine_config WHERE config_key='tier_thresholds'")->queryScalar();
            if ($row) return json_decode($row, true);
        } catch (\Exception $e) {}
        return self::$defaultThresholds;
    }

    private static function calculateCompleteness(array $d): int
    {
        $checks = [
            !empty($d['name']),
            !empty($d['id_number']),
            !empty($d['birth_date']),
            !empty($d['phone']),
            !empty($d['city']),
            !empty($d['employment_type']),
            (float)($d['total_salary'] ?? 0) > 0,
            !empty($d['bank_name']),
            (int)($d['documents_count'] ?? 0) > 0,
            (int)($d['references_count'] ?? 0) > 0,
            !empty($d['address_count']) && $d['address_count'] > 0,
            !empty($d['email']),
            isset($d['is_social_security']),
            isset($d['has_property']),
            !empty($d['years_at_job']),
        ];

        $filled = count(array_filter($checks));
        return (int)round(($filled / count($checks)) * 100);
    }

    private static function calculateFinancing(array $d, string $tier): array
    {
        $annualIncome = ((float)($d['total_salary'] ?? 0) + (float)($d['additional_income'] ?? 0)) * 12;
        $obligations = (float)($d['monthly_obligations'] ?? 0);

        $multipliers = ['approved' => 0.60, 'conditional' => 0.40, 'high_risk' => 0.20, 'rejected' => 0];
        $maxMonths   = ['approved' => 36, 'conditional' => 24, 'high_risk' => 12, 'rejected' => 0];

        $mult = $multipliers[$tier] ?? 0;
        $months = $maxMonths[$tier] ?? 0;
        $maxFinancing = round($annualIncome * $mult, 2);
        $maxInstallment = $months > 0 ? round($maxFinancing / $months, 2) : 0;

        // Ensure installment doesn't exceed available income
        $availableMonthly = max(0, (($annualIncome / 12) * 0.50) - $obligations);
        $maxInstallment = min($maxInstallment, $availableMonthly);

        return [
            'max_financing'   => $maxFinancing,
            'max_installment' => round($maxInstallment, 2),
            'max_months'      => $months,
            'annual_income'   => $annualIncome,
            'available_monthly' => round($availableMonthly, 2),
        ];
    }

    private static function generateAlerts(array $d, float $score, int $profilePct): array
    {
        $alerts = [];

        // Duplicate check would be done via AJAX in controller
        if ($profilePct < 50) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'exclamation-circle',
                'message' => 'الملف غير مكتمل (' . $profilePct . '%) — أكمل البيانات لتقييم أدق',
            ];
        }

        if ((int)($d['documents_count'] ?? 0) < 2) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'file-o',
                'message' => 'مستندات ناقصة — يلزم إرفاق الهوية وكشف الراتب على الأقل',
            ];
        }

        if ($score > 45 && (int)($d['references_count'] ?? 0) < 2) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'user-plus',
                'message' => 'يُوصى بإضافة كفيل/معرّفين لتحسين فرص الموافقة',
            ];
        }

        return $alerts;
    }

    private static function generateReasons(array $topFactors, string $tier, int $profilePct): array
    {
        $reasons = [];

        foreach ($topFactors as $f) {
            if ($f['impact'] === 'negative') {
                $reasons[] = "⚠ {$f['label']}: مستوى مخاطر مرتفع ({$f['score']}/{$f['max']})";
            } elseif ($f['impact'] === 'positive') {
                $reasons[] = "✓ {$f['label']}: مستوى جيد ({$f['score']}/{$f['max']})";
            }
        }

        $tierLabels = [
            'approved' => 'التقييم: مقبول — العميل يستوفي معايير الموافقة',
            'conditional' => 'التقييم: مشروط — يحتاج ضمانات/مستندات إضافية',
            'high_risk' => 'التقييم: مخاطر عالية — يحتاج مراجعة إدارية',
            'rejected' => 'التقييم: مرفوض — لا يستوفي الحد الأدنى',
        ];
        array_unshift($reasons, $tierLabels[$tier] ?? '');

        return $reasons;
    }

    private static function getFactorLabels(): array
    {
        return [
            'age'              => 'العمر',
            'employment'       => 'الاستقرار الوظيفي',
            'income'           => 'مستوى الدخل',
            'dti'              => 'نسبة الدين للدخل',
            'documents'        => 'اكتمال المستندات',
            'property'         => 'ملكية عقارية',
            'social_security'  => 'الضمان الاجتماعي',
            'references'       => 'المعرّفون/الكفلاء',
            'history'          => 'السجل السابق',
            'contact_quality'  => 'جودة بيانات التواصل',
        ];
    }

    /**
     * Build input snapshot from customer model + related data
     */
    public static function buildSnapshot(Customers $customer, array $extra = []): array
    {
        $phoneCount = count($customer->phoneNumbers ?? []);
        $docCount = count($customer->customersDocuments ?? []);
        $addrCount = count($customer->addresses ?? []);

        // Previous contracts
        $prevContracts = 0;
        $hasDefaults = false;
        try {
            $prevContracts = (int)Yii::$app->db->createCommand(
                "SELECT COUNT(*) FROM os_contracts_customers cc JOIN os_contracts c ON c.id=cc.contract_id WHERE cc.customer_id=:cid",
                [':cid' => $customer->id]
            )->queryScalar();

            if ($prevContracts > 0) {
                $badStatuses = (int)Yii::$app->db->createCommand(
                    "SELECT COUNT(*) FROM os_contracts_customers cc JOIN os_contracts c ON c.id=cc.contract_id WHERE cc.customer_id=:cid AND c.status IN ('judiciary','canceled')",
                    [':cid' => $customer->id]
                )->queryScalar();
                $hasDefaults = $badStatuses > 0;
            }
        } catch (\Exception $e) {}

        return array_merge([
            'name'               => $customer->name,
            'id_number'          => $customer->id_number,
            'birth_date'         => $customer->birth_date,
            'phone'              => $customer->primary_phone_number,
            'email'              => $customer->email,
            'city'               => $customer->city,
            'total_salary'       => $customer->total_salary,
            'additional_income'  => 0,
            'monthly_obligations'=> 0,
            'employment_type'    => null,
            'years_at_job'       => 0,
            'bank_name'          => $customer->bank_name,
            'is_social_security' => $customer->is_social_security,
            'has_ss_salary'      => $customer->has_social_security_salary,
            'has_property'       => $customer->do_have_any_property,
            'facebook'           => $customer->facebook_account,
            'documents_count'    => $docCount,
            'references_count'   => $phoneCount,
            'address_count'      => $addrCount,
            'previous_contracts' => $prevContracts,
            'has_defaults'       => $hasDefaults,
        ], $extra);
    }
}
