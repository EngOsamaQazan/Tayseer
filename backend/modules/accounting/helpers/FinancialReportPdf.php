<?php

namespace backend\modules\accounting\helpers;

use Yii;
use common\components\CompanyChecked;

class FinancialReportPdf
{
    private $mpdf;
    private $company;
    private $fiscalYear;
    private $dateTo;

    private $navy = '#0B1D51';
    private $gold = '#B8860B';
    private $burgundy = '#6B1D3A';
    private $darkText = '#1A1A2E';
    private $midText = '#3D3D5C';
    private $stripeBg = '#F0F4FA';
    private $borderClr = '#C5CDE0';

    public function __construct($fiscalYear = null, $dateTo = null)
    {
        $checker = new CompanyChecked();
        $this->company = $checker->findPrimaryCompany();
        $this->fiscalYear = $fiscalYear;
        $this->dateTo = $dateTo ?: date('Y-m-d');
    }

    private function createMpdf($title, $orientation = 'P')
    {
        $this->mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-' . $orientation,
            'directionality' => 'rtl',
            'default_font' => 'xbriyaz',
            'margin_top' => 48,
            'margin_bottom' => 28,
            'margin_left' => 18,
            'margin_right' => 18,
            'margin_header' => 8,
            'margin_footer' => 10,
        ]);

        $this->mpdf->SetTitle($title);
        $this->mpdf->SetAuthor($this->getCompanyName());
        $this->mpdf->SetHTMLHeader($this->buildHeader());
        $this->mpdf->SetHTMLFooter($this->buildFooter());

        return $this->mpdf;
    }

    private function getCompanyName()
    {
        return $this->company ? ($this->company->name ?? 'الشركة') : 'الشركة';
    }

    private function getCompanyInfo()
    {
        if (!$this->company) return [];
        return [
            'name' => $this->company->name ?? '',
            'address' => $this->company->company_address ?? '',
            'phone' => $this->company->phone_number ?? '',
            'email' => $this->company->company_email ?? '',
            'cr' => $this->company->commercial_register ?? '',
            'tax' => $this->company->company_tax_number ?? '',
            'license' => $this->company->trade_license ?? '',
        ];
    }

    private function num($amount)
    {
        $formatted = number_format(abs($amount), 2);
        if ($amount < 0) $formatted = '(' . $formatted . ')';
        return '<span style="font-family:dejavusans;unicode-bidi:embed;direction:ltr">' . $formatted . '</span>';
    }

    private function fyEndDate()
    {
        if ($this->fiscalYear) {
            return $this->fiscalYear->end_date ?: $this->dateTo;
        }
        return $this->dateTo;
    }

    private function fyName()
    {
        return $this->fiscalYear ? $this->fiscalYear->name : date('Y');
    }

    private function companyHeader()
    {
        $info = $this->getCompanyInfo();
        $name = $info['name'] ?: 'اسم الشركة';
        $lines = [$name];
        if (!empty($info['address'])) $lines[] = htmlspecialchars($info['address']);
        return '<div style="text-align:center;font-family:xbriyaz;direction:rtl;margin-bottom:12px">'
            . '<div style="font-size:18px;font-weight:bold;color:' . $this->navy . '">' . htmlspecialchars($name) . '</div>'
            . (!empty($info['address']) ? '<div style="font-size:11px;color:' . $this->midText . '">' . htmlspecialchars($info['address']) . '</div>' : '')
            . '</div>';
    }

    private function buildHeader()
    {
        $info = $this->getCompanyInfo();
        $name = $info['name'] ?: 'اسم الشركة';
        $details = [];
        if (!empty($info['cr'])) $details[] = 'سجل تجاري: ' . $info['cr'];
        if (!empty($info['tax'])) $details[] = 'الرقم الضريبي: ' . $info['tax'];
        $detailLine = implode('   ◆   ', $details);

        return '
        <div style="direction:rtl">
            <table width="100%" dir="rtl" style="border:none;border-bottom:3px solid ' . $this->navy . '">
                <tr>
                    <td style="border:none;border-bottom:1px solid ' . $this->gold . ';width:65%;text-align:right;padding-bottom:8px">
                        <div style="font-size:18px;font-weight:bold;color:' . $this->navy . ';font-family:xbriyaz;margin-bottom:3px">' . htmlspecialchars($name) . '</div>
                        <div style="font-size:10px;color:' . $this->midText . ';font-family:xbriyaz">' . htmlspecialchars($detailLine) . '</div>
                    </td>
                    <td style="border:none;border-bottom:1px solid ' . $this->gold . ';width:35%;text-align:left;padding-bottom:8px">
                        <div style="font-size:9px;color:' . $this->midText . ';font-family:xbriyaz">
                            ' . (!empty($info['address']) ? htmlspecialchars($info['address']) . '<br>' : '') . '
                            ' . (!empty($info['phone']) ? 'هاتف: ' . htmlspecialchars($info['phone']) : '') . '
                        </div>
                    </td>
                </tr>
            </table>
        </div>';
    }

    private function buildFooter()
    {
        return '
        <div style="direction:rtl">
            <table width="100%" dir="rtl" style="border:none;border-top:2px solid ' . $this->navy . '">
                <tr>
                    <td style="border:none;border-top:1px solid ' . $this->gold . ';padding-top:5px;text-align:right;width:40%;font-size:9px;color:' . $this->midText . ';font-family:xbriyaz">' . htmlspecialchars($this->getCompanyName()) . '</td>
                    <td style="border:none;border-top:1px solid ' . $this->gold . ';padding-top:5px;text-align:center;width:20%;font-size:9px;color:' . $this->midText . ';font-family:dejavusans">{PAGENO} / {nbpg}</td>
                    <td style="border:none;border-top:1px solid ' . $this->gold . ';padding-top:5px;text-align:left;width:40%;font-size:9px;color:' . $this->midText . ';font-family:xbriyaz">تاريخ الإصدار: <span style="font-family:dejavusans">' . date('Y/m/d') . '</span></td>
                </tr>
            </table>
        </div>';
    }

    private function reportTitle($title, $subtitle = '')
    {
        return $this->companyHeader() . '
        <div style="text-align:center;margin:0 0 18px 0;direction:rtl">
            <div style="font-size:22px;font-weight:bold;color:' . $this->navy . ';font-family:xbriyaz;margin-bottom:4px">' . htmlspecialchars($title) . '</div>
            <div style="font-size:13px;color:' . $this->midText . ';font-family:xbriyaz">' . htmlspecialchars($subtitle ?: 'للسنة المنتهية في ' . $this->fyEndDate()) . '</div>
        </div>';
    }

    private function sectionHeader($title, $noteRef = '')
    {
        $note = $noteRef ? ' <span style="font-size:10px;font-weight:normal;color:' . $this->gold . '"> — إيضاح (' . $noteRef . ')</span>' : '';
        return '<tr>
            <td colspan="3" style="padding:8px 12px;font-weight:bold;color:#fff;font-size:14px;font-family:xbriyaz;background:' . $this->navy . ';border:1px solid ' . $this->navy . ';text-align:right">' . htmlspecialchars($title) . $note . '</td>
        </tr>';
    }

    private function subSectionHeader($title)
    {
        return '<tr style="background:#E8EDF5">
            <td colspan="3" style="padding:6px 20px;font-weight:bold;color:' . $this->navy . ';font-size:13px;font-family:xbriyaz;border:1px solid ' . $this->borderClr . ';text-align:right;text-decoration:underline">' . htmlspecialchars($title) . '</td>
        </tr>';
    }

    private function accountRow($name, $amount, $indent = 0, $bold = false, $stripe = false)
    {
        $padR = 12 + ($indent * 18);
        $fw = $bold ? 'bold' : 'normal';
        $bg = $stripe ? $this->stripeBg : '#fff';
        $fs = $bold ? '13px' : '12px';

        return '<tr style="background:' . $bg . '">
            <td style="padding:6px 12px 6px ' . $padR . 'px;font-weight:' . $fw . ';font-size:' . $fs . ';font-family:xbriyaz;border:1px solid ' . $this->borderClr . ';width:60%;text-align:right;color:' . $this->darkText . '">' . htmlspecialchars($name) . '</td>
            <td style="padding:6px 10px;text-align:center;font-weight:' . $fw . ';font-size:' . $fs . ';border:1px solid ' . $this->borderClr . ';width:20%;color:' . $this->darkText . '">' . $this->num($amount) . '</td>
            <td style="padding:6px 10px;text-align:center;font-weight:' . $fw . ';font-size:' . $fs . ';border:1px solid ' . $this->borderClr . ';width:20%">&nbsp;</td>
        </tr>';
    }

    private function totalRow($label, $amount, $level = 0)
    {
        $bg = $level === 0 ? $this->navy : $this->burgundy;

        return '<tr style="background:' . $bg . '">
            <td style="padding:8px 12px;font-weight:bold;color:#fff;font-size:13px;font-family:xbriyaz;border:1px solid ' . $bg . ';text-align:right">' . htmlspecialchars($label) . '</td>
            <td style="padding:8px 10px;text-align:center;font-weight:bold;color:#fff;font-size:13px;border:1px solid ' . $bg . '">&nbsp;</td>
            <td style="padding:8px 10px;text-align:center;font-weight:bold;color:#FDEBD0;font-size:14px;border:1px solid ' . $bg . '">' . $this->num($amount) . '</td>
        </tr>';
    }

    private function spacerRow()
    {
        return '<tr><td colspan="3" style="padding:4px;border:none">&nbsp;</td></tr>';
    }

    private function tableStart()
    {
        return '<table width="100%" dir="rtl" style="border-collapse:collapse;font-family:xbriyaz;direction:rtl">';
    }

    private function tableEnd()
    {
        return '</table>';
    }

    private function colHeaders($col2 = 'إيضاح', $col3 = 'دينار أردني')
    {
        return '<tr style="background:' . $this->stripeBg . '">
            <th style="padding:7px 12px;text-align:right;font-size:12px;color:' . $this->navy . ';font-family:xbriyaz;border:1px solid ' . $this->borderClr . ';border-bottom:2px solid ' . $this->gold . ';width:60%">البيان</th>
            <th style="padding:7px 10px;text-align:center;font-size:12px;color:' . $this->navy . ';font-family:xbriyaz;border:1px solid ' . $this->borderClr . ';border-bottom:2px solid ' . $this->gold . ';width:20%">' . $col2 . '</th>
            <th style="padding:7px 10px;text-align:center;font-size:12px;color:' . $this->navy . ';font-family:xbriyaz;border:1px solid ' . $this->borderClr . ';border-bottom:2px solid ' . $this->gold . ';width:20%">' . $col3 . '</th>
        </tr>';
    }

    private function approvalBlock()
    {
        return '
        <div style="margin-top:30px;direction:rtl;font-family:xbriyaz;font-size:11px;color:' . $this->midText . ';text-align:center;page-break-inside:avoid">
            <div style="border-top:1px solid ' . $this->borderClr . ';padding-top:8px;margin:0 20px">
                تمت الموافقة على إصدار هذه البيانات المالية من قبل الإدارة.
            </div>
        </div>';
    }

    private function signatureBlock()
    {
        return '
        <div style="margin-top:40px;page-break-inside:avoid;direction:rtl">
            <table width="100%" dir="rtl" style="border:none;font-family:xbriyaz;color:' . $this->darkText . '">
                <tr>
                    <td style="border:none;text-align:center;width:30%;padding-top:50px">
                        <div style="border-top:2px solid ' . $this->navy . ';padding-top:8px;margin:0 15px">
                            <div style="font-weight:bold;font-size:13px;margin-bottom:3px">المدير العام</div>
                            <div style="font-size:10px;color:' . $this->midText . '">التوقيع والختم</div>
                        </div>
                    </td>
                    <td style="border:none;width:40%">&nbsp;</td>
                    <td style="border:none;text-align:center;width:30%;padding-top:50px">
                        <div style="border-top:2px solid ' . $this->navy . ';padding-top:8px;margin:0 15px">
                            <div style="font-weight:bold;font-size:13px;margin-bottom:3px">المحاسب القانوني</div>
                            <div style="font-size:10px;color:' . $this->midText . '">التوقيع والختم</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>';
    }

    public function generateSingleReport($type, $data)
    {
        $titles = [
            'balance-sheet' => 'بيان المركز المالي',
            'income-statement' => 'بيان الربح أو الخسارة والدخل الشامل',
            'cash-flow' => 'بيان التدفقات النقدية',
            'equity-changes' => 'بيان التغيرات في حقوق الملكية',
        ];

        $mpdf = $this->createMpdf(($titles[$type] ?? 'تقرير مالي') . ' — ' . $this->getCompanyName());

        switch ($type) {
            case 'balance-sheet':   $this->addBalanceSheet($mpdf, $data); break;
            case 'income-statement': $this->addIncomeStatement($mpdf, $data); break;
            case 'cash-flow':       $this->addCashFlow($mpdf, $data); break;
            case 'equity-changes':  $this->addEquityChanges($mpdf, $data); break;
            default:                $this->addBalanceSheet($mpdf, $data);
        }

        $filename = ($titles[$type] ?? 'تقرير') . '_' . $this->getCompanyName() . '_' . $this->fyName() . '.pdf';
        $tmpFile = tempnam(sys_get_temp_dir(), 'fin') . '.pdf';
        $mpdf->Output($tmpFile, \Mpdf\Output\Destination::FILE);

        return Yii::$app->response->sendFile($tmpFile, $filename, [
            'mimeType' => 'application/pdf',
        ])->on(\yii\web\Response::EVENT_AFTER_SEND, function () use ($tmpFile) {
            @unlink($tmpFile);
        });
    }

    public function generateFullPackage($data)
    {
        $mpdf = $this->createMpdf('البيانات المالية — ' . $this->getCompanyName());

        $this->addCoverPage($mpdf, $data);
        $mpdf->AddPage();
        $this->addTableOfContents($mpdf);
        $mpdf->AddPage();
        $this->addBalanceSheet($mpdf, $data);
        $mpdf->AddPage();
        $this->addIncomeStatement($mpdf, $data);
        $mpdf->AddPage();
        $this->addEquityChanges($mpdf, $data);
        $mpdf->AddPage();
        $this->addCashFlow($mpdf, $data);
        $mpdf->AddPage();
        $this->addNotes($mpdf, $data);

        $filename = 'البيانات_المالية_' . $this->getCompanyName() . '_' . $this->fyName() . '.pdf';
        $tmpFile = tempnam(sys_get_temp_dir(), 'fin') . '.pdf';
        $mpdf->Output($tmpFile, \Mpdf\Output\Destination::FILE);

        return Yii::$app->response->sendFile($tmpFile, $filename, [
            'mimeType' => 'application/pdf',
        ])->on(\yii\web\Response::EVENT_AFTER_SEND, function () use ($tmpFile) {
            @unlink($tmpFile);
        });
    }

    private function addCoverPage($mpdf, $data)
    {
        $info = $this->getCompanyInfo();
        $mpdf->SetHTMLHeader('');
        $mpdf->SetHTMLFooter('');

        $html = '
        <div style="text-align:center;margin-top:160px;direction:rtl;font-family:xbriyaz">
            <div style="font-size:32px;font-weight:bold;color:' . $this->navy . ';margin-bottom:8px">' . htmlspecialchars($info['name'] ?: 'اسم الشركة') . '</div>
            ' . (!empty($info['address']) ? '<div style="font-size:14px;color:' . $this->midText . ';margin-bottom:4px">' . htmlspecialchars($info['address']) . '</div>' : '') . '

            <div style="width:120px;height:3px;background:' . $this->gold . ';margin:30px auto"></div>

            <div style="font-size:24px;font-weight:bold;color:' . $this->burgundy . ';margin-bottom:10px">البيانات المالية</div>
            <div style="font-size:16px;color:' . $this->darkText . ';margin-bottom:4px">للسنة المنتهية في <span style="font-family:dejavusans">31</span> كانون الأول <span style="font-family:dejavusans">' . $this->fyName() . '</span></div>

            <div style="width:60px;height:2px;background:' . $this->gold . ';margin:30px auto"></div>
            <div style="font-size:12px;color:' . $this->gold . '">(المبالغ بالدينار الأردني ما لم يُذكر خلاف ذلك)</div>
        </div>

        <div style="margin-top:80px;direction:rtl;font-family:xbriyaz">
            <table width="50%" dir="rtl" style="margin:0 auto;border:none;font-size:13px;color:' . $this->darkText . '">
                ' . (!empty($info['cr']) ? '<tr><td style="border:none;border-bottom:1px solid ' . $this->borderClr . ';text-align:right;padding:8px 10px;font-weight:bold;width:45%;color:' . $this->navy . '">رقم السجل التجاري</td><td style="border:none;border-bottom:1px solid ' . $this->borderClr . ';text-align:right;padding:8px 10px">' . htmlspecialchars($info['cr']) . '</td></tr>' : '') . '
                ' . (!empty($info['tax']) ? '<tr><td style="border:none;border-bottom:1px solid ' . $this->borderClr . ';text-align:right;padding:8px 10px;font-weight:bold;width:45%;color:' . $this->navy . '">الرقم الضريبي</td><td style="border:none;border-bottom:1px solid ' . $this->borderClr . ';text-align:right;padding:8px 10px">' . htmlspecialchars($info['tax']) . '</td></tr>' : '') . '
                ' . (!empty($info['license']) ? '<tr><td style="border:none;border-bottom:1px solid ' . $this->borderClr . ';text-align:right;padding:8px 10px;font-weight:bold;width:45%;color:' . $this->navy . '">رخصة المهن</td><td style="border:none;border-bottom:1px solid ' . $this->borderClr . ';text-align:right;padding:8px 10px">' . htmlspecialchars($info['license']) . '</td></tr>' : '') . '
            </table>
        </div>

        <div style="text-align:center;margin-top:100px;direction:rtl;font-family:xbriyaz">
            <div style="width:80px;height:2px;background:' . $this->gold . ';margin:0 auto 8px auto"></div>
            <div style="font-size:10px;color:' . $this->midText . '">تم إعداد هذه البيانات المالية بواسطة نظام تيسير المحاسبي</div>
        </div>';

        $mpdf->WriteHTML($html);

        $mpdf->SetHTMLHeader($this->buildHeader());
        $mpdf->SetHTMLFooter($this->buildFooter());
    }

    private function addTableOfContents($mpdf)
    {
        $html = $this->companyHeader();
        $html .= '
        <div style="text-align:center;margin:0 0 8px 0;direction:rtl;font-family:xbriyaz">
            <div style="font-size:20px;font-weight:bold;color:' . $this->navy . '">البيانات المالية</div>
            <div style="font-size:13px;color:' . $this->midText . '">للسنة المنتهية في ' . $this->fyEndDate() . '</div>
        </div>';

        $html .= '<div style="width:100%;height:2px;background:' . $this->gold . ';margin:15px 0 25px 0"></div>';

        $toc = [
            ['بيان المركز المالي', '1'],
            ['بيان الربح أو الخسارة والدخل الشامل', '2'],
            ['بيان التغيرات في حقوق الملكية', '3'],
            ['بيان التدفقات النقدية', '4'],
            ['إيضاحات حول البيانات المالية وتشكل جزءاً منها', '5'],
        ];

        $html .= '<table width="80%" dir="rtl" style="margin:0 auto;border:none;font-family:xbriyaz;font-size:15px;direction:rtl">';
        $html .= '<tr style="border-bottom:2px solid ' . $this->navy . '">
            <th style="border:none;text-align:right;padding:10px 8px;color:' . $this->navy . ';font-size:16px">المحتويات</th>
            <th style="border:none;text-align:center;padding:10px 8px;color:' . $this->navy . ';font-size:16px;width:15%">الصفحة</th>
        </tr>';

        foreach ($toc as $item) {
            $html .= '<tr>
                <td style="border:none;border-bottom:1px dotted ' . $this->borderClr . ';text-align:right;padding:12px 8px;color:' . $this->darkText . '">' . $item[0] . '</td>
                <td style="border:none;border-bottom:1px dotted ' . $this->borderClr . ';text-align:center;padding:12px 8px;color:' . $this->navy . ';font-weight:bold;font-family:dejavusans">' . $item[1] . '</td>
            </tr>';
        }
        $html .= '</table>';

        $mpdf->WriteHTML($html);
    }

    private function addBalanceSheet($mpdf, $data)
    {
        $html = $this->reportTitle('بيان المركز المالي', 'كما في ' . $this->fyEndDate());
        $html .= $this->tableStart();
        $html .= $this->colHeaders('إيضاح', 'دينار أردني');

        $html .= $this->sectionHeader('الموجودات');
        $html .= $this->subSectionHeader('موجودات متداولة');
        $i = 0;
        foreach ($data['assets'] as $item) {
            if (strpos($item['account']->code, '11') === 0) {
                $html .= $this->accountRow($item['account']->name_ar, $item['balance'], 2, false, $i % 2 === 1);
                $i++;
            }
        }

        $html .= $this->subSectionHeader('موجودات غير متداولة');
        foreach ($data['assets'] as $item) {
            if (strpos($item['account']->code, '11') !== 0) {
                $html .= $this->accountRow($item['account']->name_ar, $item['balance'], 2, false, $i % 2 === 1);
                $i++;
            }
        }
        $html .= $this->totalRow('مجموع الموجودات', $data['totalAssets']);

        $html .= $this->spacerRow();

        $html .= $this->sectionHeader('المطلوبات وحقوق الملكية');
        $html .= $this->subSectionHeader('المطلوبات');
        $i = 0;
        foreach ($data['liabilities'] as $item) {
            $html .= $this->accountRow($item['account']->name_ar, $item['balance'], 2, false, $i % 2 === 1);
            $i++;
        }
        $html .= $this->totalRow('مجموع المطلوبات', $data['totalLiabilities'], 1);

        $html .= $this->spacerRow();

        $html .= $this->subSectionHeader('حقوق الملكية');
        $i = 0;
        foreach ($data['equity'] as $item) {
            $html .= $this->accountRow($item['account']->name_ar, $item['balance'], 2, false, $i % 2 === 1);
            $i++;
        }
        if ($data['netIncome'] != 0) {
            $label = $data['netIncome'] >= 0 ? 'أرباح العام' : 'خسائر العام';
            $html .= $this->accountRow($label, $data['netIncome'], 2, true, true);
        }
        $totalEquityWithIncome = $data['totalEquity'] + $data['netIncome'];
        $html .= $this->totalRow('صافي حقوق الملكية', $totalEquityWithIncome, 1);

        $html .= $this->spacerRow();
        $html .= $this->totalRow('مجموع المطلوبات وحقوق الملكية', $data['totalLiabilities'] + $totalEquityWithIncome);

        $html .= $this->tableEnd();
        $html .= $this->approvalBlock();
        $html .= $this->signatureBlock();
        $mpdf->WriteHTML($html);
    }

    private function addIncomeStatement($mpdf, $data)
    {
        $html = $this->reportTitle('بيان الربح أو الخسارة والدخل الشامل', 'للسنة المنتهية في ' . $this->fyEndDate());
        $html .= $this->tableStart();
        $html .= $this->colHeaders('إيضاح', 'دينار أردني');

        $html .= $this->sectionHeader('الإيرادات');
        $i = 0;
        foreach ($data['revenue'] as $item) {
            $html .= $this->accountRow($item['account']->name_ar, $item['balance'], 1, false, $i % 2 === 1);
            $i++;
        }
        $html .= $this->totalRow('إجمالي الإيرادات', $data['totalRevenue'], 1);

        $html .= $this->spacerRow();

        $html .= $this->sectionHeader('المصروفات');
        $i = 0;
        foreach ($data['expenses'] as $item) {
            $html .= $this->accountRow($item['account']->name_ar, $item['balance'], 1, false, $i % 2 === 1);
            $i++;
        }
        $html .= $this->totalRow('إجمالي المصروفات', $data['totalExpenses'], 1);

        $html .= $this->spacerRow();

        $label = $data['netIncome'] >= 0 ? 'صافي الربح (مجموع الدخل الشامل)' : 'صافي الخسارة (مجموع الدخل الشامل)';
        $html .= $this->totalRow($label, $data['netIncome']);

        $html .= $this->tableEnd();
        $html .= $this->signatureBlock();
        $mpdf->WriteHTML($html);
    }

    private function addCashFlow($mpdf, $data)
    {
        $html = $this->reportTitle('بيان التدفقات النقدية', 'للسنة المنتهية في ' . $this->fyEndDate());
        $html .= $this->tableStart();
        $html .= $this->colHeaders('', 'دينار أردني');

        $operatingTotal = 0;
        $html .= $this->sectionHeader('الأنشطة التشغيلية');
        if ($data['netIncome'] != 0) {
            $lbl = $data['netIncome'] >= 0 ? 'ربح السنة' : '(خسارة) السنة';
            $html .= $this->accountRow($lbl, $data['netIncome'], 1, true);
        }
        $i = 0;
        foreach ($data['operating'] as $item) {
            $net = $item['total_debit'] - $item['total_credit'];
            if ($item['account']->type === 'revenue' || $item['account']->type === 'expenses') continue;
            $html .= $this->accountRow($item['account']->name_ar, $net, 1, false, $i % 2 === 1);
            $operatingTotal += $net;
            $i++;
        }
        $operatingTotal += ($data['netIncome'] ?? 0);
        foreach ($data['operating'] as $item) {
            if ($item['account']->type === 'revenue' || $item['account']->type === 'expenses') {
                $operatingTotal += ($item['total_debit'] - $item['total_credit']);
            }
        }
        $html .= $this->totalRow('التدفقات النقدية من الأنشطة التشغيلية', $operatingTotal, 1);

        $html .= $this->spacerRow();

        $investingTotal = 0;
        $html .= $this->sectionHeader('الأنشطة الاستثمارية');
        if (!empty($data['investing'])) {
            $i = 0;
            foreach ($data['investing'] as $item) {
                $net = $item['total_debit'] - $item['total_credit'];
                $html .= $this->accountRow($item['account']->name_ar, $net, 1, false, $i % 2 === 1);
                $investingTotal += $net;
                $i++;
            }
        } else {
            $html .= '<tr><td colspan="3" style="padding:10px;text-align:center;color:' . $this->midText . ';font-size:12px;font-family:xbriyaz;border:1px solid ' . $this->borderClr . '">لا توجد أنشطة استثمارية</td></tr>';
        }
        $html .= $this->totalRow('التدفقات النقدية من الأنشطة الاستثمارية', $investingTotal, 1);

        $html .= $this->spacerRow();

        $financingTotal = 0;
        $html .= $this->sectionHeader('الأنشطة التمويلية');
        if (!empty($data['financing'])) {
            $i = 0;
            foreach ($data['financing'] as $item) {
                $net = $item['total_debit'] - $item['total_credit'];
                $html .= $this->accountRow($item['account']->name_ar, $net, 1, false, $i % 2 === 1);
                $financingTotal += $net;
                $i++;
            }
        } else {
            $html .= '<tr><td colspan="3" style="padding:10px;text-align:center;color:' . $this->midText . ';font-size:12px;font-family:xbriyaz;border:1px solid ' . $this->borderClr . '">لا توجد أنشطة تمويلية</td></tr>';
        }
        $html .= $this->totalRow('التدفقات النقدية من الأنشطة التمويلية', $financingTotal, 1);

        $html .= $this->spacerRow();

        $netCash = $operatingTotal + $investingTotal + $financingTotal;
        $html .= $this->accountRow('الزيادة (النقص) في النقد وما في حكمه', $netCash, 0, true);
        $html .= $this->accountRow('النقد وما في حكمه بداية السنة', 0, 0);
        $html .= $this->totalRow('النقد وما في حكمه نهاية السنة', $netCash);

        $html .= $this->tableEnd();
        $html .= $this->signatureBlock();
        $mpdf->WriteHTML($html);
    }

    private function addEquityChanges($mpdf, $data)
    {
        $html = $this->reportTitle('بيان التغيرات في حقوق الملكية', 'للسنة المنتهية في ' . $this->fyEndDate());

        $equityAccounts = $data['equity'];

        $html .= '<table width="100%" dir="rtl" style="border-collapse:collapse;font-family:xbriyaz;font-size:12px;direction:rtl">';

        $html .= '<tr style="background:' . $this->navy . '">';
        $html .= '<th style="padding:8px 10px;color:#fff;border:1px solid ' . $this->navy . ';text-align:right;width:28%;font-size:12px">البيان</th>';
        foreach ($equityAccounts as $item) {
            $html .= '<th style="padding:8px 8px;color:#fff;border:1px solid ' . $this->navy . ';text-align:center;font-size:11px">' . htmlspecialchars($item['account']->name_ar) . '<br><span style="font-size:9px;color:' . $this->gold . '">دينار أردني</span></th>';
        }
        $html .= '<th style="padding:8px 8px;color:' . $this->gold . ';border:1px solid ' . $this->navy . ';text-align:center;font-size:12px;font-weight:bold;width:16%">الإجمالي<br><span style="font-size:9px">دينار أردني</span></th>';
        $html .= '</tr>';

        $html .= '<tr style="background:' . $this->stripeBg . '">';
        $html .= '<td style="padding:7px 10px;font-weight:bold;border:1px solid ' . $this->borderClr . ';text-align:right;color:' . $this->darkText . '">الرصيد في <span style="font-family:dejavusans">1</span> كانون الثاني <span style="font-family:dejavusans">' . $this->fyName() . '</span></td>';
        $openingTotal = 0;
        foreach ($equityAccounts as $item) {
            $opening = (float)$item['account']->opening_balance;
            $openingTotal += $opening;
            $html .= '<td style="padding:7px 8px;text-align:center;border:1px solid ' . $this->borderClr . '">' . $this->num($opening) . '</td>';
        }
        $html .= '<td style="padding:7px 8px;text-align:center;font-weight:bold;border:1px solid ' . $this->borderClr . '">' . $this->num($openingTotal) . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $label = 'الدخل الشامل';
        $html .= '<td style="padding:7px 10px;border:1px solid ' . $this->borderClr . ';text-align:right;color:' . $this->darkText . '">' . $label . '</td>';
        foreach ($equityAccounts as $i => $item) {
            $val = ($i === count($equityAccounts) - 1) ? $data['netIncome'] : 0;
            $html .= '<td style="padding:7px 8px;text-align:center;border:1px solid ' . $this->borderClr . '">' . $this->num($val) . '</td>';
        }
        $html .= '<td style="padding:7px 8px;text-align:center;font-weight:bold;border:1px solid ' . $this->borderClr . '">' . $this->num($data['netIncome']) . '</td>';
        $html .= '</tr>';

        $html .= '<tr style="background:' . $this->navy . '">';
        $html .= '<td style="padding:8px 10px;font-weight:bold;color:#fff;border:1px solid ' . $this->navy . ';text-align:right">الرصيد في <span style="font-family:dejavusans">31</span> كانون الأول <span style="font-family:dejavusans">' . $this->fyName() . '</span></td>';
        $endTotal = 0;
        foreach ($equityAccounts as $i => $item) {
            $ending = $item['balance'] + (($i === count($equityAccounts) - 1) ? $data['netIncome'] : 0);
            $endTotal += $ending;
            $html .= '<td style="padding:8px 8px;text-align:center;font-weight:bold;color:#FDEBD0;border:1px solid ' . $this->navy . '">' . $this->num($ending) . '</td>';
        }
        $html .= '<td style="padding:8px 8px;text-align:center;font-weight:bold;color:' . $this->gold . ';border:1px solid ' . $this->navy . '">' . $this->num($endTotal) . '</td>';
        $html .= '</tr>';

        $html .= '</table>';
        $html .= $this->signatureBlock();
        $mpdf->WriteHTML($html);
    }

    private function addNotes($mpdf, $data)
    {
        $info = $this->getCompanyInfo();

        $html = $this->companyHeader();
        $html .= '<div style="text-align:center;margin-bottom:15px;direction:rtl;font-family:xbriyaz">
            <div style="font-size:14px;color:' . $this->midText . '">يتبع إيضاحات حول البيانات المالية وتشكل جزءاً منها</div>
            <div style="font-size:13px;color:' . $this->midText . '">للسنة المنتهية في ' . $this->fyEndDate() . '</div>
        </div>';

        $html .= '<div style="text-align:center;margin-bottom:20px;direction:rtl">
            <div style="font-size:20px;font-weight:bold;color:' . $this->navy . ';font-family:xbriyaz">إيضاحات حول البيانات المالية</div>
            <div style="width:100%;height:2px;background:' . $this->gold . ';margin-top:8px"></div>
        </div>';

        $html .= '<div style="font-size:13px;color:' . $this->darkText . ';line-height:2;direction:rtl;font-family:xbriyaz">';

        $html .= '<div style="font-weight:bold;font-size:16px;color:' . $this->navy . ';margin:18px 0 10px 0;border-bottom:3px solid ' . $this->gold . ';padding-bottom:5px">1) عام</div>';
        $html .= '<p style="margin:0 15px 8px 0;font-size:13px">تأسست ' . htmlspecialchars($info['name'] ?: 'الشركة') . ' وتعمل في مجال البيع بالأقساط.';
        if (!empty($info['cr'])) $html .= ' وهي مسجلة تحت رقم (' . htmlspecialchars($info['cr']) . ').';
        $html .= '</p>';

        $html .= '<div style="font-weight:bold;font-size:16px;color:' . $this->navy . ';margin:18px 0 10px 0;border-bottom:3px solid ' . $this->gold . ';padding-bottom:5px">2) ملخص لأهم السياسات المحاسبية</div>';
        $html .= '<p style="margin:0 15px 5px 0;font-size:13px"><strong style="color:' . $this->burgundy . '">أساس الإعداد:</strong> أُعدت البيانات المالية وفقاً لمعايير التقارير المالية الدولية (IFRS). المبالغ مقربة إلى أقرب دينار أردني.</p>';
        $html .= '<p style="margin:0 15px 5px 0;font-size:13px"><strong style="color:' . $this->burgundy . '">الاعتراف بالإيرادات:</strong> يتم الاعتراف بالإيراد من مبيعات البضاعة عندما تقوم الشركة بتحويل المخاطر والمنافع إلى المشتري وفقاً لمعيار IFRS 15.</p>';
        $html .= '<p style="margin:0 15px 5px 0;font-size:13px"><strong style="color:' . $this->burgundy . '">الذمم المدينة:</strong> تُظهر بالقيمة الصافية بعد خصم مخصص تدني القيمة.</p>';
        $html .= '<p style="margin:0 15px 5px 0;font-size:13px"><strong style="color:' . $this->burgundy . '">العملة الوظيفية:</strong> الدينار الأردني هو العملة الوظيفية وعملة العرض.</p>';
        $html .= '<p style="margin:0 15px 5px 0;font-size:13px"><strong style="color:' . $this->burgundy . '">أساس القياس:</strong> تم إعداد البيانات المالية وفقاً لمبدأ التكلفة التاريخية.</p>';

        $sections = [
            ['3', 'الموجودات', $data['assets'], 'دينار أردني'],
            ['4', 'المطلوبات', $data['liabilities'], 'دينار أردني'],
            ['5', 'حقوق الملكية', $data['equity'], 'دينار أردني'],
            ['6', 'الإيرادات', $data['revenue'], 'دينار أردني'],
            ['7', 'المصروفات', $data['expenses'], 'دينار أردني'],
        ];

        foreach ($sections as [$num, $title, $items, $col2Label]) {
            $html .= '<div style="font-weight:bold;font-size:16px;color:' . $this->navy . ';margin:18px 0 10px 0;border-bottom:3px solid ' . $this->gold . ';padding-bottom:5px">' . $num . ') ' . $title . '</div>';
            $html .= '<table width="100%" dir="rtl" style="border-collapse:collapse;font-size:12px;margin-bottom:12px;direction:rtl;font-family:xbriyaz">';
            $html .= '<tr style="background:' . $this->stripeBg . '">
                <th style="padding:6px 10px;text-align:right;border:1px solid ' . $this->borderClr . ';border-bottom:2px solid ' . $this->gold . ';color:' . $this->navy . ';font-size:12px">الحساب</th>
                <th style="padding:6px 10px;text-align:center;border:1px solid ' . $this->borderClr . ';border-bottom:2px solid ' . $this->gold . ';width:28%;color:' . $this->navy . ';font-size:12px">' . $col2Label . '</th>
            </tr>';
            $j = 0;
            foreach ($items as $item) {
                $bg = $j % 2 === 1 ? $this->stripeBg : '#fff';
                $html .= '<tr style="background:' . $bg . '">
                    <td style="padding:5px 10px;border:1px solid ' . $this->borderClr . ';text-align:right;color:' . $this->darkText . '">' . htmlspecialchars($item['account']->name_ar) . '</td>
                    <td style="padding:5px 10px;text-align:center;border:1px solid ' . $this->borderClr . '">' . $this->num($item['balance']) . '</td>
                </tr>';
                $j++;
            }
            $html .= '</table>';
        }

        $html .= '</div>';
        $mpdf->WriteHTML($html);
    }
}
