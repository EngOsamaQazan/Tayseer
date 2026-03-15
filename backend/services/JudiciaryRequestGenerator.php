<?php

namespace backend\services;

use Yii;
use backend\models\JudiciaryRequestTemplate;
use backend\modules\judiciary\models\Judiciary;
use backend\modules\lawyers\models\Lawyers;

/**
 * Generates HTML request documents from templates with placeholder substitution.
 * Documents are rendered in-browser via CKEditor for editing before printing.
 */
class JudiciaryRequestGenerator
{
    /**
     * Standard placeholders available in all templates.
     */
    private static $placeholderLabels = [
        '{{case_number}}'              => 'رقم الدعوى',
        '{{case_year}}'                => 'سنة الدعوى',
        '{{court_name}}'               => 'اسم المحكمة',
        '{{execution_type}}'           => 'نوع التنفيذ',
        '{{defendant_name}}'           => 'اسم المحكوم عليه',
        '{{plaintiff_name}}'           => 'اسم المحكوم له',
        '{{representative_name}}'      => 'اسم المفوض/الوكيل',
        '{{representative_title}}'     => 'صفة المفوض/الوكيل',
        '{{representative_signature}}' => 'التوقيع',
        '{{employer_name}}'            => 'جهة التوظيف',
        '{{bank_name}}'                => 'البنك',
        '{{authority_name}}'           => 'الجهة الإدارية',
        '{{amount}}'                   => 'المبلغ',
        '{{notification_date}}'        => 'تاريخ التبليغ',
        '{{current_date}}'             => 'التاريخ الحالي',
        '{{request_items}}'            => 'بنود الطلب',
    ];

    /**
     * Generate HTML content from a template with filled placeholders.
     */
    public function generate(int $judiciaryId, array $templateIds, array $contextData = []): string
    {
        $judiciary = Judiciary::findOne($judiciaryId);
        if (!$judiciary) {
            throw new \yii\base\UserException('القضية غير موجودة');
        }

        $placeholders = $this->buildPlaceholders($judiciary, $contextData);

        $header = $this->buildMaBaadHeader($judiciary, $placeholders);

        $bodyParts = [];
        foreach ($templateIds as $templateId) {
            $template = JudiciaryRequestTemplate::findOne($templateId);
            if ($template && $template->template_content) {
                $bodyParts[] = $this->replacePlaceholders($template->template_content, $placeholders);
            }
        }

        $body = implode("\n<hr style=\"page-break-after:auto;\">\n", $bodyParts);

        return $header . "\n" . $body;
    }

    /**
     * Build the standard "ما بعد" header.
     */
    private function buildMaBaadHeader(Judiciary $judiciary, array $placeholders): string
    {
        $html = '<div class="request-header" style="text-align:center; margin-bottom:20px;">';
        $html .= '<h3>ما بعد</h3>';
        $html .= '<p>الدعوى التنفيذية رقم ' . ($placeholders['{{case_number}}'] ?? '') . '/' . ($placeholders['{{case_year}}'] ?? '') . '</p>';
        $html .= '<p>' . ($placeholders['{{court_name}}'] ?? '') . '</p>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Build the full placeholders map from judiciary data and context.
     */
    private function buildPlaceholders(Judiciary $judiciary, array $context): array
    {
        $court = $judiciary->court;
        $contract = $judiciary->contract;
        $lawyer = $judiciary->lawyer;

        $representativeTitle = '';
        $representativeSignature = '';
        if ($lawyer) {
            $isLawyer = ($lawyer->representative_type ?? 'delegate') === 'lawyer';
            $representativeTitle = $isLawyer ? 'وكيل المحكوم لها' : 'مفوض المحكوم لها';
            if ($isLawyer && !empty($lawyer->signature_image)) {
                $signaturePath = Yii::getAlias('@web') . '/' . ltrim($lawyer->signature_image, '/');
                $representativeSignature = '<img src="' . $signaturePath . '" alt="التوقيع" style="max-height:60px;">';
            }
        }

        $plaintiffNames = [];
        if ($judiciary->customers) {
            foreach ($judiciary->customers as $c) {
                $plaintiffNames[] = $c->name ?? '';
            }
        }

        $placeholders = [
            '{{case_number}}'              => $judiciary->judiciary_number ?? '',
            '{{case_year}}'                => $judiciary->year ?? '',
            '{{court_name}}'               => $court ? ($court->name ?? '') : '',
            '{{execution_type}}'           => $judiciary->type ? ($judiciary->type->name ?? '') : '',
            '{{plaintiff_name}}'           => implode(' و ', $plaintiffNames),
            '{{representative_name}}'      => $lawyer ? ($lawyer->name ?? '') : '',
            '{{representative_title}}'     => $representativeTitle,
            '{{representative_signature}}' => $representativeSignature,
            '{{current_date}}'             => date('Y-m-d'),
            '{{notification_date}}'        => $context['notification_date'] ?? '',
            '{{defendant_name}}'           => $context['defendant_name'] ?? '',
            '{{employer_name}}'            => $context['employer_name'] ?? '',
            '{{bank_name}}'                => $context['bank_name'] ?? '',
            '{{authority_name}}'           => $context['authority_name'] ?? '',
            '{{amount}}'                   => $context['amount'] ?? '',
            '{{request_items}}'            => $context['request_items'] ?? '',
        ];

        if (!empty($context['representative_id'])) {
            $overrideLawyer = Lawyers::findOne($context['representative_id']);
            if ($overrideLawyer) {
                $isLawyer = ($overrideLawyer->representative_type ?? 'delegate') === 'lawyer';
                $placeholders['{{representative_name}}'] = $overrideLawyer->name ?? '';
                $placeholders['{{representative_title}}'] = $isLawyer ? 'وكيل المحكوم لها' : 'مفوض المحكوم لها';
                if ($isLawyer && !empty($overrideLawyer->signature_image)) {
                    $signaturePath = Yii::getAlias('@web') . '/' . ltrim($overrideLawyer->signature_image, '/');
                    $placeholders['{{representative_signature}}'] = '<img src="' . $signaturePath . '" alt="التوقيع" style="max-height:60px;">';
                } else {
                    $placeholders['{{representative_signature}}'] = '';
                }
            }
        }

        return $placeholders;
    }

    private function replacePlaceholders(string $content, array $placeholders): string
    {
        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }

    /**
     * Get available placeholder labels for template editor reference.
     */
    public static function getPlaceholderLabels(): array
    {
        return self::$placeholderLabels;
    }
}
