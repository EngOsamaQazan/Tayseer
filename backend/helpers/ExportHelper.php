<?php

namespace backend\helpers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Yii;

class ExportHelper
{
    /**
     * @param array $config [
     *   'title'     => string,
     *   'subtitle'  => string|null,
     *   'headers'   => string[],
     *   'keys'      => (string|callable)[],
     *   'widths'    => int[],
     *   'rows'      => array,
     *   'filename'  => string,
     *   'sheetTitle'=> string|null,
     *   'headerBg'  => string (hex without #),
     *   'creator'   => string|null,
     * ]
     * @return \yii\web\Response
     */
    public static function toExcel(array $config)
    {
        $prevLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');
        $title      = $config['title'];
        $subtitle   = $config['subtitle'] ?? null;
        $headers    = $config['headers'];
        $keys       = $config['keys'];
        $widths     = $config['widths'] ?? [];
        $rows       = $config['rows'];
        $filename   = $config['filename'] ?? 'export';
        $sheetTitle = $config['sheetTitle'] ?? mb_substr($title, 0, 31);
        $headerBg   = $config['headerBg'] ?? '800020';
        $headerFg   = $config['headerFg'] ?? 'FFFFFF';
        $creator    = $config['creator'] ?? 'نظام تيسير';

        $excel = new Spreadsheet();
        $excel->getProperties()->setCreator($creator)->setTitle($title);

        $sheet = $excel->getActiveSheet();
        $sheet->setTitle($sheetTitle);
        $sheet->setRightToLeft(true);

        $colCount = count($headers);
        $lastCol  = Coordinate::stringFromColumnIndex($colCount);

        $currentRow = 1;

        /* ── صف العنوان الرئيسي ── */
        $sheet->mergeCells("A{$currentRow}:{$lastCol}{$currentRow}");
        $sheet->setCellValue('A1', $title . ' — تاريخ: ' . date('Y-m-d'));
        $sheet->getStyle("A1")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => $headerFg], 'name' => 'Arial'],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $headerBg]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(36);
        $currentRow++;

        /* ── صف العنوان الفرعي (اختياري) ── */
        if ($subtitle) {
            $sheet->mergeCells("A{$currentRow}:{$lastCol}{$currentRow}");
            $sheet->setCellValue("A{$currentRow}", $subtitle);
            $sheet->getStyle("A{$currentRow}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '334155'], 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0FF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension($currentRow)->setRowHeight(26);
            $currentRow++;
        }

        /* ── رؤوس الأعمدة ── */
        $hRow = $currentRow;
        for ($c = 0; $c < $colCount; $c++) {
            $col = Coordinate::stringFromColumnIndex($c + 1);
            $sheet->setCellValue("{$col}{$hRow}", $headers[$c]);
        }
        $sheet->getStyle("A{$hRow}:{$lastCol}{$hRow}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $headerFg], 'name' => 'Arial'],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $headerBg]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '666666']]],
        ]);
        $sheet->getRowDimension($hRow)->setRowHeight(28);
        $sheet->freezePane('A' . ($hRow + 1));

        /* ── كتابة البيانات ── */
        $oddRows = [];
        $rowNum  = $hRow + 1;

        foreach ($rows as $idx => $row) {
            for ($c = 0; $c < $colCount; $c++) {
                $col = Coordinate::stringFromColumnIndex($c + 1);
                $key = $keys[$c];
                if ($key === '#') {
                    $value = $idx + 1;
                } elseif ($key instanceof \Closure || (is_array($key) && is_callable($key))) {
                    $value = $key($row, $idx);
                } elseif (is_object($row)) {
                    $value = self::resolveAttribute($row, $key);
                } else {
                    $value = $row[$key] ?? '';
                }
                $sheet->setCellValue("{$col}{$rowNum}", $value);
            }
            if ($idx % 2 === 1) {
                $oddRows[] = $rowNum;
            }
            $rowNum++;
        }

        $lastDataRow = max($rowNum - 1, $hRow);

        /* ── تنسيق البيانات ── */
        if ($lastDataRow > $hRow) {
            $allData = "A" . ($hRow + 1) . ":{$lastCol}{$lastDataRow}";
            $sheet->getStyle($allData)->applyFromArray([
                'font'      => ['size' => 10.5, 'name' => 'Arial'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
            ]);

            foreach ($oddRows as $or) {
                $sheet->getStyle("A{$or}:{$lastCol}{$or}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                ]);
            }

            $sheet->setAutoFilter("A{$hRow}:{$lastCol}{$lastDataRow}");
        }

        /* ── عرض الأعمدة ── */
        for ($c = 0; $c < $colCount; $c++) {
            $w = $widths[$c] ?? 18;
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c + 1))->setWidth($w);
        }

        /* ── حفظ وإرسال ── */
        $fullFilename = $filename . '_' . date('Y-m-d') . '.xlsx';
        $tmpFile = tempnam(sys_get_temp_dir(), 'xl') . '.xlsx';

        $writer = new Xlsx($excel);
        $writer->save($tmpFile);

        $excel->disconnectWorksheets();
        unset($excel);

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
}
