<?php

namespace backend\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Export Buttons (Excel + PDF) — Pro Style
 *
 * Renders modern green/red gradient buttons matching the
 * inventory-pro design system (.inv-pro-btn--excel / --pdf).
 * Falls back gracefully on legacy pages via inline classes.
 */
class ExportButtons extends Widget
{
    /** @var string|array Route for Excel export action */
    public $excelRoute;

    /** @var string|array Route for PDF export action */
    public $pdfRoute;

    /** @var bool Whether to pass current query params to export URL */
    public $passQueryParams = true;

    /** @var string Excel button CSS class */
    public $excelBtnClass = 'inv-pro-btn inv-pro-btn--excel inv-pro-btn--sm';

    /** @var string PDF button CSS class */
    public $pdfBtnClass = 'inv-pro-btn inv-pro-btn--pdf inv-pro-btn--sm';

    /** @var bool Wrap buttons in a flex group */
    public $group = true;

    public function run()
    {
        $params = $this->passQueryParams ? \Yii::$app->request->queryParams : [];

        $html = '';
        $buttons = '';

        if ($this->excelRoute) {
            $excelUrl = $this->buildUrl($this->excelRoute, $params);
            $buttons .= Html::a(
                '<i class="fa fa-file-excel-o"></i> Excel',
                $excelUrl,
                [
                    'class' => $this->excelBtnClass,
                    'data-pjax' => '0',
                    'target' => '_blank',
                    'title' => 'تصدير Excel',
                    'aria-label' => 'تصدير إلى ملف Excel',
                ]
            );
        }

        if ($this->pdfRoute) {
            $pdfUrl = $this->buildUrl($this->pdfRoute, $params);
            $buttons .= Html::a(
                '<i class="fa fa-file-pdf-o"></i> PDF',
                $pdfUrl,
                [
                    'class' => $this->pdfBtnClass,
                    'data-pjax' => '0',
                    'target' => '_blank',
                    'title' => 'تصدير PDF',
                    'aria-label' => 'تصدير إلى ملف PDF',
                ]
            );
        }

        if (!$buttons) {
            return '';
        }

        if ($this->group) {
            $html = '<div class="inv-export-group" role="group" aria-label="تصدير" style="display:inline-flex;gap:6px">' . $buttons . '</div>';
        } else {
            $html = $buttons;
        }

        return $html;
    }

    private function buildUrl($route, array $params): string
    {
        if (is_array($route)) {
            return Url::to(array_merge($route, $params));
        }
        return Url::to(array_merge([$route], $params));
    }
}
