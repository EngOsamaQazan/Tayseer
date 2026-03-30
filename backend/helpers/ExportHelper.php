<?php

namespace backend\helpers;

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
     *   'headerBg'  => string (hex without #),
     *   'headerFg'  => string (hex without #),
     * ]
     * @return \yii\web\Response
     */
    public static function toExcel(array $config)
    {
        if (class_exists(\OpenSpout\Writer\XLSX\Writer::class)) {
            return self::toExcelOpenSpout($config);
        }
        return self::toExcelPhpSpreadsheet($config);
    }

    /* ================================================================
     *  OpenSpout — streaming, low memory (~3 MB for 10k+ rows)
     * ================================================================ */
    private static function toExcelOpenSpout(array $config)
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

        $bgColor = self::hexToSpoutColor($headerBg);
        $fgColor = self::hexToSpoutColor($headerFg);

        $options = new \OpenSpout\Writer\XLSX\Options();
        for ($c = 0; $c < $colCount; $c++) {
            $options->setColumnWidth((float)($widths[$c] ?? 18), $c + 1);
        }

        $writer = new \OpenSpout\Writer\XLSX\Writer($options);
        $writer->openToFile($tmpFile);

        $titleStyle = new \OpenSpout\Common\Entity\Style\Style();
        $titleStyle->setFontBold();
        $titleStyle->setFontSize(14);
        $titleStyle->setFontName('Arial');
        $titleStyle->setFontColor($fgColor);
        $titleStyle->setBackgroundColor($bgColor);

        $titleCells = [\OpenSpout\Common\Entity\Cell::fromValue($title . ' — تاريخ: ' . date('Y-m-d'), $titleStyle)];
        $emptyTitleStyle = new \OpenSpout\Common\Entity\Style\Style();
        $emptyTitleStyle->setBackgroundColor($bgColor);
        for ($c = 1; $c < $colCount; $c++) {
            $titleCells[] = \OpenSpout\Common\Entity\Cell::fromValue('', $emptyTitleStyle);
        }
        $writer->addRow(new \OpenSpout\Common\Entity\Row($titleCells));

        if ($subtitle) {
            $subStyle = new \OpenSpout\Common\Entity\Style\Style();
            $subStyle->setFontBold();
            $subStyle->setFontSize(11);
            $subStyle->setFontName('Arial');
            $subStyle->setBackgroundColor(self::hexToSpoutColor('F0F0FF'));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(array_pad([$subtitle], $colCount, ''), $subStyle));
        }

        $headerStyle = new \OpenSpout\Common\Entity\Style\Style();
        $headerStyle->setFontBold();
        $headerStyle->setFontSize(11);
        $headerStyle->setFontName('Arial');
        $headerStyle->setFontColor($fgColor);
        $headerStyle->setBackgroundColor($bgColor);
        $headerStyle->setShouldWrapText(true);
        $headerStyle->setBorder(new \OpenSpout\Common\Entity\Style\Border(
            new \OpenSpout\Common\Entity\Style\BorderPart(\OpenSpout\Common\Entity\Style\Border::BOTTOM, \OpenSpout\Common\Entity\Style\Color::BLACK, \OpenSpout\Common\Entity\Style\Border::WIDTH_THIN),
            new \OpenSpout\Common\Entity\Style\BorderPart(\OpenSpout\Common\Entity\Style\Border::TOP, \OpenSpout\Common\Entity\Style\Color::BLACK, \OpenSpout\Common\Entity\Style\Border::WIDTH_THIN),
            new \OpenSpout\Common\Entity\Style\BorderPart(\OpenSpout\Common\Entity\Style\Border::LEFT, \OpenSpout\Common\Entity\Style\Color::BLACK, \OpenSpout\Common\Entity\Style\Border::WIDTH_THIN),
            new \OpenSpout\Common\Entity\Style\BorderPart(\OpenSpout\Common\Entity\Style\Border::RIGHT, \OpenSpout\Common\Entity\Style\Color::BLACK, \OpenSpout\Common\Entity\Style\Border::WIDTH_THIN),
        ));
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($headers, $headerStyle));

        $dataStyle = new \OpenSpout\Common\Entity\Style\Style();
        $dataStyle->setFontSize(11);
        $dataStyle->setFontName('Arial');

        $altStyle = new \OpenSpout\Common\Entity\Style\Style();
        $altStyle->setFontSize(11);
        $altStyle->setFontName('Arial');
        $altStyle->setBackgroundColor(self::hexToSpoutColor('F5F5F5'));

        foreach ($rows as $idx => $row) {
            $values = self::extractRowValues($keys, $row, $idx);
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($values, $idx % 2 === 1 ? $altStyle : $dataStyle));
        }

        $writer->close();

        return Yii::$app->response->sendFile($tmpFile, $fullFilename, [
            'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->on(\yii\web\Response::EVENT_AFTER_SEND, function () use ($tmpFile) {
            @unlink($tmpFile);
        });
    }

    /* ================================================================
     *  PhpSpreadsheet fallback — in-memory, higher RAM usage
     * ================================================================ */
    private static function toExcelPhpSpreadsheet(array $config)
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

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);

        for ($c = 0; $c < $colCount; $c++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 1);
            $sheet->getColumnDimension($col)->setWidth($widths[$c] ?? 18);
        }

        $rowNum = 1;

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);
        $sheet->mergeCells("A{$rowNum}:{$lastCol}{$rowNum}");
        $sheet->setCellValue("A{$rowNum}", $title . ' — تاريخ: ' . date('Y-m-d'));
        $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'name' => 'Arial', 'color' => ['rgb' => $headerFg]],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $headerBg]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $rowNum++;

        if ($subtitle) {
            $sheet->mergeCells("A{$rowNum}:{$lastCol}{$rowNum}");
            $sheet->setCellValue("A{$rowNum}", $subtitle);
            $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 11, 'name' => 'Arial'],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0FF']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ]);
            $rowNum++;
        }

        for ($c = 0; $c < $colCount; $c++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 1);
            $sheet->setCellValue("{$col}{$rowNum}", $headers[$c]);
        }
        $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'name' => 'Arial', 'color' => ['rgb' => $headerFg]],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $headerBg]],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
            'alignment' => ['wrapText' => true, 'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $rowNum++;

        foreach ($rows as $idx => $row) {
            $values = self::extractRowValues($keys, $row, $idx);
            for ($c = 0; $c < $colCount; $c++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 1);
                $sheet->setCellValue("{$col}{$rowNum}", $values[$c] ?? '');
            }
            $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
                'font' => ['size' => 11, 'name' => 'Arial'],
            ]);
            if ($idx % 2 === 1) {
                $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F5F5F5');
            }
            $rowNum++;
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'phpxl_') . '.xlsx';
        $xlWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $xlWriter->save($tmpFile);
        $spreadsheet->disconnectWorksheets();

        return Yii::$app->response->sendFile($tmpFile, $fullFilename, [
            'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->on(\yii\web\Response::EVENT_AFTER_SEND, function () use ($tmpFile) {
            @unlink($tmpFile);
        });
    }

    /* ================================================================
     *  PDF export (mpdf)
     * ================================================================ */

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
            $values = self::extractRowValues($keys, $row, $idx);
            foreach ($values as $val) {
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

    /* ================================================================
     *  Shared helpers
     * ================================================================ */

    private static function extractRowValues(array $keys, $row, int $idx): array
    {
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
        return $values;
    }

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

    private static function hexToSpoutColor(string $hex): string
    {
        $hex = ltrim($hex, '#');
        return \OpenSpout\Common\Entity\Style\Color::rgb(
            (int)hexdec(substr($hex, 0, 2)),
            (int)hexdec(substr($hex, 2, 2)),
            (int)hexdec(substr($hex, 4, 2)),
        );
    }
}
