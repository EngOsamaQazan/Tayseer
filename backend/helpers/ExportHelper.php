<?php

namespace backend\helpers;

use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use Yii;

class ExportHelper
{
    /**
     * تصدير Excel بتقنية OpenSpout — streaming بدون تحميل الكل بالذاكرة
     * يدعم 10,000+ صف بأقل من 3 MB ذاكرة
     *
     * @param array $config [
     *   'title'     => string,
     *   'subtitle'  => string|null,
     *   'headers'   => string[],
     *   'keys'      => (string|callable)[],
     *   'widths'    => int[],
     *   'rows'      => array,
     *   'filename'  => string,
     *   'headerBg'  => string (hex without #),
     *   'headerFg'  => string (hex without #),
     * ]
     * @return \yii\web\Response
     */
    public static function toExcel(array $config)
    {
        $title      = $config['title'];
        $subtitle   = $config['subtitle'] ?? null;
        $headers    = $config['headers'];
        $keys       = $config['keys'];
        $widths     = $config['widths'] ?? [];
        $rows       = $config['rows'];
        $filename   = $config['filename'] ?? 'export';
        $headerBg   = $config['headerBg'] ?? '800020';
        $headerFg   = $config['headerFg'] ?? 'FFFFFF';

        $colCount     = count($headers);
        $fullFilename = $filename . '_' . date('Y-m-d') . '.xlsx';
        $tmpFile      = tempnam(sys_get_temp_dir(), 'spout_') . '.xlsx';

        $bgColor = self::hexToColor($headerBg);
        $fgColor = self::hexToColor($headerFg);

        $options = new Options();
        for ($c = 0; $c < $colCount; $c++) {
            $options->setColumnWidth((float)($widths[$c] ?? 18), $c + 1);
        }

        $writer = new Writer($options);
        $writer->openToFile($tmpFile);

        /* ── صف العنوان الرئيسي ── */
        $titleStyle = new Style();
        $titleStyle->setFontBold();
        $titleStyle->setFontSize(14);
        $titleStyle->setFontName('Arial');
        $titleStyle->setFontColor($fgColor);
        $titleStyle->setBackgroundColor($bgColor);

        $titleCells = [Cell::fromValue($title . ' — تاريخ: ' . date('Y-m-d'), $titleStyle)];
        $emptyTitleStyle = new Style();
        $emptyTitleStyle->setBackgroundColor($bgColor);
        for ($c = 1; $c < $colCount; $c++) {
            $titleCells[] = Cell::fromValue('', $emptyTitleStyle);
        }
        $writer->addRow(new Row($titleCells));

        /* ── صف العنوان الفرعي (اختياري) ── */
        if ($subtitle) {
            $subStyle = new Style();
            $subStyle->setFontBold();
            $subStyle->setFontSize(11);
            $subStyle->setFontName('Arial');
            $subStyle->setBackgroundColor(self::hexToColor('F0F0FF'));
            $writer->addRow(Row::fromValues(array_pad([$subtitle], $colCount, ''), $subStyle));
        }

        /* ── رؤوس الأعمدة ── */
        $headerStyle = new Style();
        $headerStyle->setFontBold();
        $headerStyle->setFontSize(11);
        $headerStyle->setFontName('Arial');
        $headerStyle->setFontColor($fgColor);
        $headerStyle->setBackgroundColor($bgColor);
        $headerStyle->setShouldWrapText(true);
        $headerStyle->setBorder(new Border(
            new BorderPart(Border::BOTTOM, Color::BLACK, Border::WIDTH_THIN),
            new BorderPart(Border::TOP, Color::BLACK, Border::WIDTH_THIN),
            new BorderPart(Border::LEFT, Color::BLACK, Border::WIDTH_THIN),
            new BorderPart(Border::RIGHT, Color::BLACK, Border::WIDTH_THIN),
        ));

        $writer->addRow(Row::fromValues($headers, $headerStyle));

        /* ── كتابة البيانات — streaming صف بصف ── */
        $dataStyle = new Style();
        $dataStyle->setFontSize(11);
        $dataStyle->setFontName('Arial');

        $altStyle = new Style();
        $altStyle->setFontSize(11);
        $altStyle->setFontName('Arial');
        $altStyle->setBackgroundColor(self::hexToColor('F5F5F5'));

        foreach ($rows as $idx => $row) {
            $values = [];
            foreach ($keys as $key) {
                if ($key === '#') {
                    $values[] = $idx + 1;
                } elseif ($key instanceof \Closure || (is_array($key) && is_callable($key))) {
                    $values[] = $key($row, $idx);
                } elseif (is_object($row)) {
                    $values[] = self::resolveAttribute($row, $key);
                } else {
                    $values[] = $row[$key] ?? '';
                }
            }
            $writer->addRow(Row::fromValues($values, $idx % 2 === 1 ? $altStyle : $dataStyle));
        }

        $writer->close();

        return Yii::$app->response->sendFile($tmpFile, $fullFilename, [
            'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->on(\yii\web\Response::EVENT_AFTER_SEND, function () use ($tmpFile) {
            @unlink($tmpFile);
        });
    }

    /**
     * @param array $config Same as toExcel + optional 'orientation' => 'L'|'P'
     * @return \yii\web\Response
     */
    public static function toPdf(array $config)
    {
        $title    = $config['title'];
        $subtitle = $config['subtitle'] ?? '';
        $headers  = $config['headers'];
        $keys     = $config['keys'];
        $rows     = $config['rows'];
        $filename = ($config['filename'] ?? 'export') . '_' . date('Y-m-d') . '.pdf';
        $orient   = $config['orientation'] ?? (count($headers) > 6 ? 'L' : 'P');
        $headerBg = $config['headerBg'] ?? '800020';

        $html = '<style>
            body { font-family: arial, sans-serif; direction: rtl; }
            .title { text-align:center; font-size:18px; font-weight:bold; color:#fff; background:#' . $headerBg . '; padding:12px; margin-bottom:4px; }
            .subtitle { text-align:center; font-size:12px; color:#334155; background:#F0F0FF; padding:6px; margin-bottom:10px; }
            table { width:100%; border-collapse:collapse; font-size:10px; }
            th { background:#' . $headerBg . '; color:#fff; font-weight:bold; padding:6px 4px; border:1px solid #666; text-align:center; }
            td { padding:5px 4px; border:1px solid #D1D5DB; text-align:right; }
            tr:nth-child(even) td { background:#F9FAFB; }
            .date { text-align:center; font-size:10px; color:#666; margin-bottom:8px; }
        </style>';

        $html .= '<div class="title">' . htmlspecialchars($title) . '</div>';
        if ($subtitle) {
            $html .= '<div class="subtitle">' . htmlspecialchars($subtitle) . '</div>';
        }
        $html .= '<div class="date">تاريخ التصدير: ' . date('Y-m-d H:i') . '</div>';

        $html .= '<table><thead><tr>';
        foreach ($headers as $h) {
            $html .= '<th>' . htmlspecialchars($h) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $idx => $row) {
            $html .= '<tr>';
            foreach ($keys as $key) {
                if ($key === '#') {
                    $val = $idx + 1;
                } elseif ($key instanceof \Closure || (is_array($key) && is_callable($key))) {
                    $val = $key($row, $idx);
                } elseif (is_object($row)) {
                    $val = self::resolveAttribute($row, $key);
                } else {
                    $val = $row[$key] ?? '';
                }
                $html .= '<td>' . htmlspecialchars((string)$val) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-' . $orient,
            'directionality' => 'rtl',
            'default_font'  => 'arial',
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'margin_left'   => 10,
            'margin_right'  => 10,
        ]);

        $mpdf->SetTitle($title);
        $mpdf->SetFooter('{PAGENO} / {nbpg}');
        $mpdf->WriteHTML($html);

        $tmpFile = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';
        $mpdf->Output($tmpFile, \Mpdf\Output\Destination::FILE);

        return Yii::$app->response->sendFile($tmpFile, $filename, [
            'mimeType' => 'application/pdf',
        ])->on(\yii\web\Response::EVENT_AFTER_SEND, function () use ($tmpFile) {
            @unlink($tmpFile);
        });
    }

    /**
     * Resolve a dot-notation attribute on an AR model (e.g. 'customer.name')
     */
    private static function resolveAttribute($model, string $attribute)
    {
        if (strpos($attribute, '.') === false) {
            return $model->{$attribute} ?? '';
        }
        $parts = explode('.', $attribute, 2);
        $related = $model->{$parts[0]} ?? null;
        if ($related === null) {
            return '';
        }
        return $related->{$parts[1]} ?? '';
    }

    /**
     * hex string (e.g. '800020') → OpenSpout ARGB color string
     */
    private static function hexToColor(string $hex): string
    {
        $hex = ltrim($hex, '#');
        return Color::rgb(
            (int)hexdec(substr($hex, 0, 2)),
            (int)hexdec(substr($hex, 2, 2)),
            (int)hexdec(substr($hex, 4, 2)),
        );
    }
}
