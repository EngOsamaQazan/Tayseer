<?php

namespace backend\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;

class ExportButtons extends Widget
{
    /** @var string|array Route for Excel export action */
    public $excelRoute;

    /** @var string|array Route for PDF export action */
    public $pdfRoute;

    /** @var bool Whether to pass current query params to export URL */
    public $passQueryParams = true;

    /** @var string Excel button CSS class */
    public $excelBtnClass = 'btn btn-success btn-sm';

    /** @var string PDF button CSS class */
    public $pdfBtnClass = 'btn btn-danger btn-sm';

    public function run()
    {
        $params = $this->passQueryParams ? \Yii::$app->request->queryParams : [];

        $html = '';

        if ($this->excelRoute) {
            $excelUrl = $this->buildUrl($this->excelRoute, $params);
            $html .= Html::a(
                '<i class="fa fa-file-excel-o"></i> Excel',
                $excelUrl,
                ['class' => $this->excelBtnClass, 'style' => 'margin-left:4px', 'data-pjax' => '0', 'target' => '_blank']
            );
        }

        if ($this->pdfRoute) {
            $pdfUrl = $this->buildUrl($this->pdfRoute, $params);
            $html .= Html::a(
                '<i class="fa fa-file-pdf-o"></i> PDF',
                $pdfUrl,
                ['class' => $this->pdfBtnClass, 'style' => 'margin-left:4px', 'data-pjax' => '0', 'target' => '_blank']
            );
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
