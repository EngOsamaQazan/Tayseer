<?php

namespace backend\modules\judiciary\controllers;

use \backend\modules\contractDocumentFile\models\ContractDocumentFile;
use \backend\modules\contracts\models\Contracts;
use \backend\modules\contractCustomers\models\ContractsCustomers;
use backend\modules\customers\models\Customers;
use backend\modules\expenses\models\Expenses;
use \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions;
use Yii;
use \backend\modules\judiciary\models\Judiciary;
use \backend\modules\judiciary\models\JudiciarySearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use \backend\modules\followUpReport\models\FollowUpReport;
use yii\helpers\Html;
use backend\helpers\NameHelper;
use backend\modules\contractInstallment\models\ContractInstallment;
use common\helper\Permissions;
use backend\helpers\ExportTrait;
use backend\services\JudiciaryWorkflowService;
use backend\services\JudiciaryDeadlineService;
use backend\services\JudiciaryRequestGenerator;
use backend\services\EntityResolverService;
use backend\services\DiwanCorrespondenceService;
use backend\services\HolidayService;
use backend\models\JudiciaryDeadline;
use backend\models\JudiciaryRequestTemplate;
use backend\modules\diwan\models\DiwanCorrespondence;
use backend\modules\diwan\models\DiwanCorrespondenceSearch;

/**
 * JudiciaryController implements the CRUD actions for Judiciary model.
 */
class JudiciaryController extends Controller
{
    use ExportTrait;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => [
                            'index', 'view', 'report',
                            'cases-report', 'cases-report-data', 'export-cases-report',
                            'print-cases-report', 'print-case', 'add-print-case',
                            'pdf-page-image', 'pdf-page-count',
                            'print-overlay',
                            'refresh-persistence-cache',
                            'tab-cases', 'tab-actions', 'tab-persistence', 'tab-legal',
                            'tab-collection', 'tab-counts',
                            'export-cases-excel', 'export-cases-pdf',
                            'export-actions-excel', 'export-actions-pdf',
                            'export-report-excel', 'export-report-pdf',
                            'case-timeline',
                            'correspondence-list', 'deadline-dashboard',
                            'deadline-dashboard-view',
                            'entity-search', 'generate-request',
                            'save-generated-request',
                            'refresh-deadlines', 'sync-holidays',
                        ],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::JUD_VIEW)
                                || Permissions::can(Permissions::COLL_VIEW);
                        },
                    ],
                    [
                        'actions' => [
                            'create', 'batch-create', 'batch-print',
                            'customer-action',
                            'batch-actions', 'batch-parse', 'batch-execute',
                        ],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::JUD_CREATE);
                        },
                    ],
                    [
                        'actions' => ['update', 'update-request-status', 'send-document', 'cancel-document'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::JUD_UPDATE);
                        },
                    ],
                    [
                        'actions' => ['delete', 'delete-customer-action'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::JUD_DELETE);
                        },
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /* ═══════════════════════════════════════════════════════════
     *  دالة مساعدة: تحويل persistence_status إلى label/color/icon
     * ═══════════════════════════════════════════════════════════ */
    private function parsePersistence(&$row)
    {
        $s = $row['persistence_status'] ?? '';
        if ($s === 'red_renew') {
            $row['persistence_label'] = 'بحاجة تجديد دعوى';
            $row['persistence_color'] = 'red';
            $row['persistence_icon']  = '🔴';
        } elseif ($s === 'red_due') {
            $row['persistence_label'] = 'مستحق المثابرة';
            $row['persistence_color'] = 'red';
            $row['persistence_icon']  = '🔴';
        } elseif ($s === 'orange_due') {
            $row['persistence_label'] = 'مستحق المثابرة';
            $row['persistence_color'] = 'orange';
            $row['persistence_icon']  = '🟠';
        } elseif ($s === 'green_due') {
            $row['persistence_label'] = 'مستحق المثابرة';
            $row['persistence_color'] = 'green';
            $row['persistence_icon']  = '🟢';
        } elseif (strpos($s, 'remaining_') === 0) {
            $parts  = explode('_', $s);
            $months = isset($parts[1]) ? (int)$parts[1] : 0;
            $days   = isset($parts[2]) ? (int)$parts[2] : 0;
            $row['persistence_label'] = "باقي {$months} شهر و {$days} يوم لاستحقاق المثابرة";
            $row['persistence_color'] = 'green';
            $row['persistence_icon']  = '🟢';
        } else {
            $row['persistence_label'] = $s;
            $row['persistence_color'] = 'gray';
            $row['persistence_icon']  = '⚪';
        }
    }

    /* ═══════════════════════════════════════════════════════════
     *  دالة مساعدة: بناء WHERE حسب الفلاتر
     * ═══════════════════════════════════════════════════════════ */
    private function buildPersistenceWhere($filter, $search)
    {
        $where = [];
        $params = [];

        /* فلتر اللون */
        if ($filter === 'red') {
            $where[] = "persistence_status IN ('red_renew','red_due')";
        } elseif ($filter === 'orange') {
            $where[] = "persistence_status = 'orange_due'";
        } elseif ($filter === 'green') {
            $where[] = "(persistence_status = 'green_due' OR persistence_status LIKE 'remaining_%')";
        }

        /* بحث نصي */
        if ($search !== '') {
            $where[] = "(customer_name LIKE :q1 OR court_name LIKE :q2 OR judiciary_number LIKE :q3 OR CAST(contract_id AS CHAR) LIKE :q4 OR lawyer_name LIKE :q5)";
            $sv = "%{$search}%";
            $params[':q1'] = $sv;
            $params[':q2'] = $sv;
            $params[':q3'] = $sv;
            $params[':q4'] = $sv;
            $params[':q5'] = $sv;
        }

        $sql = count($where) ? ' WHERE ' . implode(' AND ', $where) : '';
        return [$sql, $params];
    }

    /* ═══════════════════════════════════════════════════════════
     *  كشف المثابره — الصفحة الرئيسية (خفيفة، بدون بيانات)
     * ═══════════════════════════════════════════════════════════ */
    public function actionCasesReport()
    {
        $db = Yii::$app->db;

        /* جلب الإحصائيات فقط من الجدول المادي — لحظي */
        $stats = $db->createCommand("
            SELECT
                COUNT(*) AS total,
                SUM(persistence_status IN ('red_renew','red_due')) AS cnt_red,
                SUM(persistence_status = 'orange_due') AS cnt_orange,
                SUM(persistence_status = 'green_due' OR persistence_status LIKE 'remaining_%') AS cnt_green
            FROM tbl_persistence_cache
        ")->queryOne();

        return $this->render('cases_report', [
            'stats' => $stats,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════
     *  AJAX endpoint — جلب صفحة من البيانات
     * ═══════════════════════════════════════════════════════════ */
    public function actionCasesReportData()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $request = Yii::$app->request;
        $page    = max(1, (int)$request->get('page', 1));
        $perPage = max(1, min(100, (int)$request->get('per_page', 20)));
        $filter  = $request->get('filter', 'all');     // all|red|orange|green
        $search  = trim($request->get('search', ''));
        $showAll = $request->get('show_all', '0') === '1';

        $db = Yii::$app->db;

        list($whereSql, $params) = $this->buildPersistenceWhere($filter, $search);

        /* عدد السجلات */
        $total = (int)$db->createCommand("SELECT COUNT(*) FROM tbl_persistence_cache{$whereSql}", $params)->queryScalar();

        /* البيانات */
        if ($showAll) {
            $sql = "SELECT * FROM tbl_persistence_cache{$whereSql} ORDER BY court_name, contract_id, judiciary_number";
            $rows = $db->createCommand($sql, $params)->queryAll();
        } else {
            $offset = ($page - 1) * $perPage;
            $sql = "SELECT * FROM tbl_persistence_cache{$whereSql} ORDER BY court_name, contract_id, judiciary_number LIMIT {$perPage} OFFSET {$offset}";
            $rows = $db->createCommand($sql, $params)->queryAll();
        }

        /* تحويل persistence_status + اختصار الأسماء مع الاحتفاظ بالكامل للـ tooltip */
        foreach ($rows as &$row) {
            $this->parsePersistence($row);
            if (!empty($row['customer_name'])) {
                $row['customer_name_full'] = $row['customer_name'];
                $row['customer_name'] = NameHelper::short($row['customer_name']);
            }
            if (!empty($row['lawyer_name'])) {
                $row['lawyer_name_full'] = $row['lawyer_name'];
                $row['lawyer_name'] = NameHelper::short($row['lawyer_name']);
            }
        }
        unset($row);

        /* إحصائيات مفلترة (للبحث) */
        $statsParams = [];
        $statsWhere = '';
        if ($search !== '') {
            $statsWhere = " WHERE (customer_name LIKE :q1 OR court_name LIKE :q2 OR judiciary_number LIKE :q3 OR CAST(contract_id AS CHAR) LIKE :q4 OR lawyer_name LIKE :q5)";
            $sv = "%{$search}%";
            $statsParams[':q1'] = $sv;
            $statsParams[':q2'] = $sv;
            $statsParams[':q3'] = $sv;
            $statsParams[':q4'] = $sv;
            $statsParams[':q5'] = $sv;
        }
        $stats = $db->createCommand("
            SELECT
                COUNT(*) AS total,
                SUM(persistence_status IN ('red_renew','red_due')) AS cnt_red,
                SUM(persistence_status = 'orange_due') AS cnt_orange,
                SUM(persistence_status = 'green_due' OR persistence_status LIKE 'remaining_%') AS cnt_green
            FROM tbl_persistence_cache{$statsWhere}
        ", $statsParams)->queryOne();

        return [
            'rows'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'total_pages'=> $showAll ? 1 : (int)ceil($total / $perPage),
            'stats'      => $stats,
        ];
    }

    /* ═══════════════════════════════════════════════════════════
     *  طباعة كشف المثابره — صفحة مخصصة للطباعة حسب الفلتر
     * ═══════════════════════════════════════════════════════════ */
    public function actionPrintCasesReport()
    {
        $this->layout = false; // بدون layout — صفحة مستقلة للطباعة

        $request = Yii::$app->request;
        $filter  = $request->get('filter', 'all');
        $search  = trim($request->get('search', ''));

        $db = Yii::$app->db;
        list($whereSql, $params) = $this->buildPersistenceWhere($filter, $search);

        $rows = $db->createCommand(
            "SELECT * FROM tbl_persistence_cache{$whereSql} ORDER BY court_name, contract_id, judiciary_number",
            $params
        )->queryAll();

        foreach ($rows as &$row) {
            $this->parsePersistence($row);
        }
        unset($row);

        /* وصف الفلتر المُطبّق */
        $filterLabels = [
            'all' => 'جميع القضايا',
            'red' => 'بحاجة اهتمام عاجل',
            'orange' => 'قريب من الاستحقاق',
            'green' => 'بحالة جيدة',
        ];
        $filterLabel = $filterLabels[$filter] ?? 'جميع القضايا';
        if ($search !== '') {
            $filterLabel .= " — بحث: \"{$search}\"";
        }

        return $this->renderPartial('cases_report_print', [
            'rows'        => $rows,
            'filterLabel' => $filterLabel,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════
     *  تحديث Cache يدوياً
     * ═══════════════════════════════════════════════════════════ */
    public function actionRefreshPersistenceCache()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->db->createCommand("CALL sp_refresh_persistence_cache()")->execute();
        return ['success' => true, 'message' => 'تم تحديث البيانات'];
    }

    /* ═══════════════════════════════════════════════════════════
     *  تصدير كشف المثابره — XLSX بتنسيقات وألوان كاملة
     *  محسّن: VIEW + تنسيق بالدُفعات لتقليل استهلاك الذاكرة
     * ═══════════════════════════════════════════════════════════ */
    public function actionExportCasesReport()
    {
        /* PhpSpreadsheet handles memory caching internally */

        /* ── جلب الفلاتر من الـ URL ── */
        $request = Yii::$app->request;
        $filter  = $request->get('filter', 'all');
        $search  = trim($request->get('search', ''));

        $db = Yii::$app->db;
        list($whereSql, $params) = $this->buildPersistenceWhere($filter, $search);

        /* ── جلب البيانات حسب الفلتر النشط ── */
        $rows = $db->createCommand(
            "SELECT * FROM tbl_persistence_cache{$whereSql} ORDER BY court_name, contract_id, judiciary_number",
            $params
        )->queryAll();

        /* ── تحويل حالة المثابرة ── */
        foreach ($rows as &$r) {
            $this->parsePersistence($r);
        }
        unset($r);

        /* ── وصف الفلتر المُطبّق للعنوان ── */
        $filterLabels = [
            'all' => 'جميع القضايا',
            'red' => 'بحاجة اهتمام عاجل',
            'orange' => 'قريب من الاستحقاق',
            'green' => 'بحالة جيدة',
        ];
        $filterLabel = $filterLabels[$filter] ?? 'جميع القضايا';
        if ($search !== '') {
            $filterLabel .= " — بحث: {$search}";
        }

        /* ══════════════════════════════════════════
         *  بناء ملف Excel — تنسيق محسّن بالدُفعات
         * ══════════════════════════════════════════ */
        $excel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $excel->getProperties()
            ->setCreator('نظام جدل')
            ->setTitle('كشف المثابره');

        $sheet = $excel->getActiveSheet();
        $sheet->setTitle('كشف المثابره');
        $sheet->setRightToLeft(true);

        /* ألوان */
        $HBG = '800020'; $HFG = 'FFFFFF';

        /* العناوين */
        $headers = ['#','رقم القضية','سنة القضية','اسم المحكمة','رقم العقد','اسم العميل','الإجراء الأخير','تاريخ آخر إجراء','مؤشّر المثابرة','آخر متابعة للعقد','آخر تشييك وظيفة','المحامي','الوظيفة','نوع الوظيفة'];
        $colCount = count($headers);
        $lastCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        /* ── صف العنوان الرئيسي (صف 1) ── */
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'كشف المثابره — ' . $filterLabel . ' — تاريخ: ' . date('Y-m-d'));
        $sheet->getStyle("A1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => $HFG], 'name' => 'Arial'],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $HBG]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(36);

        /* ── صف الإحصائيات (صف 2) ── */
        $cnt = ['red' => 0, 'orange' => 0, 'green' => 0];
        foreach ($rows as $r) {
            $c = $r['persistence_color'];
            if (isset($cnt[$c])) $cnt[$c]++;
            else $cnt['green']++;
        }
        $total = count($rows);
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', "إجمالي: {$total}  |  عاجل: {$cnt['red']}  |  قريب: {$cnt['orange']}  |  جيد: {$cnt['green']}");
        $sheet->getStyle("A2")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '334155'], 'name' => 'Arial'],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0FF']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(26);

        /* ── رؤوس الأعمدة (صف 3) ── */
        $hRow = 3;
        for ($c = 0; $c < $colCount; $c++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 1);
            $sheet->setCellValue("{$col}{$hRow}", $headers[$c]);
        }
        $sheet->getStyle("A{$hRow}:{$lastCol}{$hRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $HFG], 'name' => 'Arial'],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $HBG]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '666666']]],
        ]);
        $sheet->getRowDimension($hRow)->setRowHeight(28);
        $sheet->freezePane('A4');

        /* ── كتابة البيانات ── */
        $keys = ['judiciary_number','case_year','court_name','contract_id','customer_name','last_action_name','last_action_date','persistence_label','last_followup_date','last_job_check_date','lawyer_name','job_title','job_type'];

        /* تجميع الصفوف حسب اللون لتنسيق الدُفعات */
        $redRows = []; $orangeRows = []; $greenRows = []; $oddRows = [];

        $rowNum = $hRow + 1;
        foreach ($rows as $idx => $row) {
            /* كتابة القيم */
            $sheet->setCellValue("A{$rowNum}", $idx + 1);
            for ($c = 0; $c < count($keys); $c++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 2);
                $sheet->setCellValue("{$col}{$rowNum}", $row[$keys[$c]] ?? '');
            }
            /* تصنيف الصفوف */
            $pc = $row['persistence_color'];
            if ($pc === 'red')         $redRows[]    = $rowNum;
            elseif ($pc === 'orange')  $orangeRows[] = $rowNum;
            else                       $greenRows[]  = $rowNum;
            if ($idx % 2 === 1)        $oddRows[]    = $rowNum;
            $rowNum++;
        }
        $lastDataRow = $rowNum - 1;

        /* ── تنسيق بالدُفعات (Range-based) بدل صف-بصف ── */
        $dataRange = "A{$hRow}:{$lastCol}{$lastDataRow}"; // تشمل الهيدر+البيانات

        /* 1. تنسيق أساسي لكل البيانات مرة واحدة */
        $allData = "A" . ($hRow + 1) . ":{$lastCol}{$lastDataRow}";
        $sheet->getStyle($allData)->applyFromArray([
            'font'      => ['size' => 10.5, 'name' => 'Arial'],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
        ]);

        /* 2. الصفوف الزوجية (خلفية مخططة) — بالدُفعة */
        foreach ($oddRows as $or) {
            $sheet->getStyle("A{$or}:{$lastCol}{$or}")->applyFromArray([
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
            ]);
        }

        /* 3. عمود المثابرة — تلوين بالدُفعات */
        foreach ($redRows as $rr) {
            $sheet->getStyle("I{$rr}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '991B1B']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getStyle("A{$rr}")->applyFromArray([
                'borders' => ['left' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK, 'color' => ['rgb' => 'DC2626']]],
            ]);
        }
        foreach ($orangeRows as $or) {
            $sheet->getStyle("I{$or}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '92400E']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getStyle("A{$or}")->applyFromArray([
                'borders' => ['left' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK, 'color' => ['rgb' => 'D97706']]],
            ]);
        }
        foreach ($greenRows as $gr) {
            $sheet->getStyle("I{$gr}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '166534']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCFCE7']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ]);
        }

        /* ── عرض الأعمدة ── */
        $widths = [6,12,10,18,10,28,22,14,30,14,14,18,18,16];
        for ($c = 0; $c < $colCount; $c++) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 1))->setWidth($widths[$c]);
        }

        /* ── فلتر تلقائي ── */
        $sheet->setAutoFilter("A{$hRow}:{$lastCol}{$lastDataRow}");

        /* ══════════════════════════════════════════
         *  حفظ مؤقت ثم إرسال
         * ══════════════════════════════════════════ */
        $filterSuffix = ($filter !== 'all') ? '_' . $filter : '';
        $filename = 'كشف_المثابره' . $filterSuffix . '_' . date('Y-m-d') . '.xlsx';
        $tmpFile  = tempnam(sys_get_temp_dir(), 'xl') . '.xlsx';

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($excel);
        $writer->save($tmpFile);

        $excel->disconnectWorksheets();
        unset($excel, $rows, $redRows, $orangeRows, $greenRows, $oddRows);

        return Yii::$app->response->sendFile($tmpFile, $filename, [
            'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->on(\yii\web\Response::EVENT_AFTER_SEND, function () use ($tmpFile) {
            @unlink($tmpFile);
        });
    }

    /**
     * Lists all Judiciary models.
     * @return mixed
     */
    public function actionReport()
    {
        $searchModel = new JudiciarySearch();
        $search = $searchModel->report();
        $dataProvider = $search['dataProvider'];
        $counter = $search['count'];

        return $this->render('report', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'counter' => $counter,
        ]);
    }

    /**
     * Lists all Judiciary models.
     * @return mixed
     */
    public function actionIndex()
    {
        $request = Yii::$app->request->queryParams;
        //$db=Yii::$app->db;
        $searchModel = new JudiciarySearch();
        //  $search = $db->cache(function($db) use ($searchModel,$request){

        //       return $searchModel->search($request);
        //  });
        $search = $searchModel->search($request);


        $dataProvider = $search['dataProvider'];
        $counter = $search['count'];

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'counter' => $counter,
        ]);
    }


    /* ═══════════════════════════════════════════════════════════
     *  متابعة القضية — Case Timeline (AJAX)
     * ═══════════════════════════════════════════════════════════ */

    public function actionCaseTimeline($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $judiciary = Judiciary::findOne((int)$id);
            if (!$judiciary) {
                return ['success' => false, 'message' => 'القضية غير موجودة'];
            }

            $parties = Yii::$app->db->createCommand(
                "SELECT cc.customer_id, cc.customer_type, cu.name
                 FROM {{%contracts_customers}} cc
                 INNER JOIN {{%customers}} cu ON cu.id = cc.customer_id
                 WHERE cc.contract_id = :cid",
                [':cid' => $judiciary->contract_id]
            )->queryAll();

            $partyList = [];
            foreach ($parties as $p) {
                $partyList[] = [
                    'id' => (int)$p['customer_id'],
                    'name' => $p['name'] ?? '',
                    'type' => $p['customer_type'],
                ];
            }

            $db = Yii::$app->db;
            $tblJca = $db->tablePrefix . 'judiciary_customers_actions';
            $tblJa  = $db->tablePrefix . 'judiciary_actions';
            $tblCu  = $db->tablePrefix . 'customers';
            $tblU   = $db->tablePrefix . 'user';

            $jaColumns = $db->getTableSchema($tblJa)->columnNames;
            $hasNature = in_array('action_nature', $jaColumns);
            $hasType   = in_array('action_type', $jaColumns);

            $jcaColumns = $db->getTableSchema($tblJca)->columnNames;
            $hasReqStatus = in_array('request_status', $jcaColumns);
            $hasDecision  = in_array('decision_text', $jcaColumns);
            $hasImage     = in_array('image', $jcaColumns);

            $selectCols = "jca.id, jca.judiciary_id, jca.customers_id, jca.judiciary_actions_id,
                jca.action_date, jca.note, jca.created_at, jca.created_by,
                ja.name AS action_name, cu.name AS customer_name, u.username AS created_by_name";
            if ($hasNature) $selectCols .= ", ja.action_nature";
            if ($hasType)   $selectCols .= ", ja.action_type";
            if ($hasReqStatus) $selectCols .= ", jca.request_status";
            if ($hasDecision)  $selectCols .= ", jca.decision_text";
            if ($hasImage)     $selectCols .= ", jca.image";

            $rows = $db->createCommand(
                "SELECT $selectCols
                 FROM `$tblJca` jca
                 INNER JOIN `$tblJa` ja ON ja.id = jca.judiciary_actions_id
                 INNER JOIN `$tblCu` cu ON cu.id = jca.customers_id
                 LEFT JOIN `$tblU` u ON u.id = jca.created_by
                 WHERE jca.judiciary_id = :jid AND jca.is_deleted = 0
                 ORDER BY jca.action_date DESC, jca.id DESC",
                [':jid' => (int)$id]
            )->queryAll();

            $timeline = [];
            foreach ($rows as $a) {
                $timeline[] = [
                    'id' => (int)$a['id'],
                    'action_name' => $a['action_name'] ?? '',
                    'action_nature' => $a['action_nature'] ?? 'process',
                    'action_type' => $a['action_type'] ?? '',
                    'customer_id' => (int)$a['customers_id'],
                    'customer_name' => $a['customer_name'] ?? '',
                    'action_date' => $a['action_date'] ?? '',
                    'note' => $a['note'] ?? '',
                    'request_status' => $a['request_status'] ?? '',
                    'decision_text' => $a['decision_text'] ?? '',
                    'image' => \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions::resolveImageUrl($a['image'] ?? ''),
                    'created_by' => $a['created_by_name'] ?? '',
                    'created_at' => !empty($a['created_at']) ? date('Y-m-d H:i', $a['created_at']) : '',
                ];
            }

            // Correspondence entries
            $corrRows = DiwanCorrespondence::find()
                ->forCase((int)$id)
                ->chronological()
                ->asArray()
                ->all();

            $corrTypeIcons = [
                'notification' => 'fa-bell',
                'outgoing_letter' => 'fa-paper-plane',
                'incoming_response' => 'fa-reply',
            ];
            $corrTypeLabels = [
                'notification' => 'تبليغ',
                'outgoing_letter' => 'كتاب صادر',
                'incoming_response' => 'رد وارد',
            ];
            foreach ($corrRows as $cr) {
                $timeline[] = [
                    'id' => 'corr-' . $cr['id'],
                    'action_name' => $corrTypeLabels[$cr['communication_type']] ?? $cr['communication_type'],
                    'action_nature' => 'correspondence',
                    'action_type' => $cr['communication_type'],
                    'customer_id' => (int)($cr['customer_id'] ?? 0),
                    'customer_name' => '',
                    'action_date' => $cr['correspondence_date'] ?? '',
                    'note' => $cr['content_summary'] ?? '',
                    'request_status' => '',
                    'decision_text' => '',
                    'image' => '',
                    'created_by' => '',
                    'created_at' => !empty($cr['created_at']) ? date('Y-m-d H:i', $cr['created_at']) : '',
                    'icon' => $corrTypeIcons[$cr['communication_type']] ?? 'fa-envelope',
                    'source' => 'correspondence',
                    'status' => $cr['status'] ?? '',
                ];
            }

            // Deadline entries
            $dlRows = JudiciaryDeadline::find()
                ->where(['judiciary_id' => (int)$id, 'is_deleted' => 0])
                ->orderBy(['deadline_date' => SORT_DESC])
                ->asArray()
                ->all();

            $dlTypeLabels = JudiciaryDeadline::getTypeLabels();
            foreach ($dlRows as $dl) {
                $timeline[] = [
                    'id' => 'dl-' . $dl['id'],
                    'action_name' => $dlTypeLabels[$dl['deadline_type']] ?? $dl['deadline_type'],
                    'action_nature' => 'deadline',
                    'action_type' => $dl['deadline_type'],
                    'customer_id' => (int)($dl['customer_id'] ?? 0),
                    'customer_name' => '',
                    'action_date' => $dl['deadline_date'] ?? '',
                    'note' => $dl['notes'] ?? '',
                    'request_status' => '',
                    'decision_text' => '',
                    'image' => '',
                    'created_by' => '',
                    'created_at' => '',
                    'icon' => $dl['status'] === 'expired' ? 'fa-exclamation-circle' : 'fa-clock-o',
                    'source' => 'deadline',
                    'status' => $dl['status'] ?? '',
                ];
            }

            usort($timeline, function ($a, $b) {
                return strcmp($b['action_date'] ?? '', $a['action_date'] ?? '');
            });

            $caseInfo = [
                'id' => $judiciary->id,
                'contract_id' => $judiciary->contract_id,
                'judiciary_number' => $judiciary->judiciary_number,
                'year' => $judiciary->year,
                'court' => $judiciary->court->name ?? '',
                'lawyer' => $judiciary->lawyer->name ?? '',
                'type' => $judiciary->type->name ?? '',
            ];

            return [
                'success' => true,
                'case' => $caseInfo,
                'parties' => $partyList,
                'timeline' => $timeline,
                'addActionUrl' => \yii\helpers\Url::to([
                    '/judiciaryCustomersActions/judiciary-customers-actions/create-followup-judicary-custamer-action',
                    'contractID' => $judiciary->contract_id,
                ]),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * AJAX: تغيير حالة طلب قضائي (موافقة / رفض)
     */
    public function actionUpdateRequestStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $request = Yii::$app->request;
        if (!$request->isPost) {
            return ['success' => false, 'message' => 'طلب غير صالح'];
        }

        $id = (int)$request->post('id');
        $status = $request->post('status', '');
        $decisionText = trim($request->post('decision_text', ''));

        if (!$id || !in_array($status, ['approved', 'rejected'])) {
            return ['success' => false, 'message' => 'بيانات ناقصة'];
        }

        $record = JudiciaryCustomersActions::findOne($id);
        if (!$record || $record->is_deleted) {
            return ['success' => false, 'message' => 'الإجراء غير موجود'];
        }

        $record->request_status = $status;
        if ($decisionText) {
            $record->decision_text = $decisionText;
        }

        if ($record->save(false)) {
            $statusLabels = ['approved' => 'تمت الموافقة', 'rejected' => 'تم الرفض'];
            return [
                'success' => true,
                'message' => $statusLabels[$status] ?? $status,
                'new_status' => $status,
            ];
        }

        return ['success' => false, 'message' => 'فشل في حفظ التغيير'];
    }

    /* ═══════════════════════════════════════════════════════════
     *  إرسال / إلغاء كتاب أو مذكرة (document actions)
     * ═══════════════════════════════════════════════════════════ */

    public function actionSendDocument()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        if (!$request->isPost) {
            return ['success' => false, 'message' => 'طلب غير صالح'];
        }

        $id = (int)$request->post('id');
        $deliveryMethod = $request->post('delivery_method', '');
        $sendDate = $request->post('send_date', date('Y-m-d'));
        $recipientType = $request->post('recipient_type', '');
        $bankId = $request->post('bank_id') ?: null;
        $jobId = $request->post('job_id') ?: null;
        $authorityId = $request->post('authority_id') ?: null;
        $referenceNumber = trim($request->post('reference_number', ''));
        $purpose = $request->post('purpose', '');
        $notes = trim($request->post('notes', ''));

        if (!$id || !$deliveryMethod) {
            return ['success' => false, 'message' => 'يرجى اختيار طريقة الإرسال'];
        }

        $record = JudiciaryCustomersActions::findOne($id);
        if (!$record || $record->is_deleted) {
            return ['success' => false, 'message' => 'الإجراء غير موجود'];
        }

        $def = $record->judiciaryActions;
        if (!$def || $def->action_nature !== 'document') {
            return ['success' => false, 'message' => 'هذا الإجراء ليس كتاباً / مذكرة'];
        }

        if ($record->request_status === 'sent') {
            return ['success' => false, 'message' => 'هذا الكتاب مُرسل مسبقاً'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $judiciary = $record->judiciary;
            $customer = $record->customers;

            if (!$recipientType && $customer) {
                $actionName = mb_strtolower($def->name);
                if (mb_strpos($actionName, 'راتب') !== false || mb_strpos($actionName, 'حسم') !== false) {
                    $recipientType = DiwanCorrespondence::RECIPIENT_EMPLOYER;
                    if (!$jobId && $customer->job_title) {
                        $jobId = $customer->job_title;
                    }
                } elseif (mb_strpos($actionName, 'بنك') !== false || mb_strpos($actionName, 'حساب') !== false || mb_strpos($actionName, 'تجميد') !== false) {
                    $recipientType = DiwanCorrespondence::RECIPIENT_BANK;
                    if (!$bankId && $customer->bank_name) {
                        $bankId = $customer->bank_name;
                    }
                } else {
                    $recipientType = DiwanCorrespondence::RECIPIENT_ADMINISTRATIVE;
                }
            }

            $corr = new DiwanCorrespondence();
            $corr->communication_type = DiwanCorrespondence::TYPE_OUTGOING_LETTER;
            $corr->related_module = 'judiciary';
            $corr->related_record_id = $record->judiciary_id;
            $corr->customer_id = $record->customers_id;
            $corr->direction = 'outgoing';
            $corr->recipient_type = $recipientType;
            $corr->bank_id = $bankId ? (int)$bankId : null;
            $corr->job_id = $jobId ? (int)$jobId : null;
            $corr->authority_id = $authorityId ? (int)$authorityId : null;
            $corr->delivery_method = $deliveryMethod;
            $corr->delivery_date = $sendDate;
            $corr->correspondence_date = $sendDate;
            $corr->reference_number = $referenceNumber ?: null;
            $corr->purpose = $purpose ?: null;
            $corr->content_summary = $def->name;
            $corr->notes = $notes ?: null;
            $corr->status = DiwanCorrespondence::STATUS_SENT;
            $corr->company_id = $judiciary ? $judiciary->company_id : null;

            if (!$corr->save(false)) {
                $transaction->rollBack();
                return ['success' => false, 'message' => 'فشل في إنشاء سجل المراسلة'];
            }

            $record->request_status = 'sent';
            $record->correspondence_id = $corr->id;

            if (!$record->save(false)) {
                $transaction->rollBack();
                return ['success' => false, 'message' => 'فشل في تحديث حالة الإجراء'];
            }

            $transaction->commit();

            $deliveryLabels = DiwanCorrespondence::getDeliveryMethodLabels();
            return [
                'success' => true,
                'message' => 'تم إرسال الكتاب بنجاح',
                'new_status' => 'sent',
                'delivery_method' => $deliveryLabels[$deliveryMethod] ?? $deliveryMethod,
                'correspondence_id' => $corr->id,
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()];
        }
    }

    public function actionCancelDocument()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        if (!$request->isPost) {
            return ['success' => false, 'message' => 'طلب غير صالح'];
        }

        $id = (int)$request->post('id');
        if (!$id) {
            return ['success' => false, 'message' => 'بيانات ناقصة'];
        }

        $record = JudiciaryCustomersActions::findOne($id);
        if (!$record || $record->is_deleted) {
            return ['success' => false, 'message' => 'الإجراء غير موجود'];
        }

        if ($record->request_status === 'sent') {
            return ['success' => false, 'message' => 'لا يمكن إلغاء كتاب تم إرساله'];
        }

        $record->request_status = 'cancelled';
        if ($record->save(false)) {
            return ['success' => true, 'message' => 'تم إلغاء الكتاب', 'new_status' => 'cancelled'];
        }

        return ['success' => false, 'message' => 'فشل في حفظ التغيير'];
    }

    /* ═══════════════════════════════════════════════════════════
     *  تصدير القضايا (تبويب القضايا) — Excel / PDF
     * ═══════════════════════════════════════════════════════════ */

    public function actionExportCasesExcel()
    {
        return $this->exportCasesLightweight('excel');
    }

    public function actionExportCasesPdf()
    {
        return $this->exportCasesLightweight('pdf');
    }

    /**
     * Memory-efficient export: uses raw SQL JOINs + asArray() instead of loading full AR models.
     */
    private function exportCasesLightweight($format)
    {
        $searchModel = new JudiciarySearch();
        $search = $searchModel->search(Yii::$app->request->queryParams);

        $query = $search['dataProvider']->query;
        $query->with = [];

        $query->leftJoin('{{%court}} _ct', '_ct.id = j.court_id')
              ->leftJoin('{{%judiciary_type}} _jt', '_jt.id = j.type_id')
              ->leftJoin('{{%lawyers}} _lw', '_lw.id = j.lawyer_id');

        $query->select([
            'j.id', 'j.contract_id', 'j.judiciary_number', 'j.year',
            'j.lawyer_cost', 'j.case_cost',
            'court_name'  => '_ct.name',
            'type_name'   => '_jt.name',
            'lawyer_name' => '_lw.name',
        ]);

        $rows = $query->asArray()->all();

        $contractIds = array_unique(array_filter(array_column($rows, 'contract_id')));
        $nameByContract = [];
        if (!empty($contractIds)) {
            $custData = (new \yii\db\Query())
                ->select(['cc.contract_id', "GROUP_CONCAT(c.name SEPARATOR '، ') as names"])
                ->from('{{%contracts_customers}} cc')
                ->innerJoin('{{%customers}} c', 'c.id = cc.customer_id')
                ->where(['cc.contract_id' => $contractIds])
                ->groupBy('cc.contract_id')
                ->all();
            $nameByContract = \yii\helpers\ArrayHelper::map($custData, 'contract_id', 'names');
        }

        $exportRows = [];
        foreach ($rows as $r) {
            $num  = $r['judiciary_number'] ?: '—';
            $year = $r['year'] ?: '';
            $exportRows[] = [
                'contract_id' => $r['contract_id'] ?: '—',
                'customer'    => $nameByContract[$r['contract_id']] ?? '—',
                'court'       => $r['court_name'] ?: '—',
                'type'        => $r['type_name'] ?: '—',
                'lawyer'      => $r['lawyer_name'] ?: '—',
                'case_number' => $year ? "{$num}-{$year}" : $num,
                'lawyer_cost' => $r['lawyer_cost'] ?: 0,
                'case_cost'   => $r['case_cost'] ?: 0,
            ];
        }

        return $this->exportArrayData($exportRows, [
            'title'    => 'القضايا',
            'filename' => 'judiciary_cases',
            'headers'  => ['#', 'العقد', 'العميل', 'المحكمة', 'النوع', 'المحامي', 'رقم القضية', 'أتعاب المحامي', 'رسوم القضية'],
            'keys'     => ['#', 'contract_id', 'customer', 'court', 'type', 'lawyer', 'case_number', 'lawyer_cost', 'case_cost'],
            'widths'   => [6, 10, 28, 18, 14, 18, 16, 16, 14],
        ], $format);
    }

    /* ═══════════════════════════════════════════════════════════
     *  تصدير إجراءات العملاء القضائية (تبويب الإجراءات) — Excel / PDF
     * ═══════════════════════════════════════════════════════════ */

    public function actionExportActionsExcel()
    {
        return $this->exportActionsLightweight('excel');
    }

    public function actionExportActionsPdf()
    {
        return $this->exportActionsLightweight('pdf');
    }

    private function exportActionsLightweight($format)
    {
        $rows = (new \yii\db\Query())
            ->select([
                'jca.id', 'jca.judiciary_id', 'jca.note', 'jca.action_date',
                'j.judiciary_number', 'j.year', 'j.contract_id',
                'cust_name' => 'c.name',
                'action_name' => 'ja.name',
                'user_name' => 'u.username',
                'lawyer_name' => 'lw.name',
                'court_name' => 'ct.name',
            ])
            ->from('{{%judiciary_customers_actions}} jca')
            ->leftJoin('{{%judiciary}} j', 'j.id = jca.judiciary_id')
            ->leftJoin('{{%customers}} c', 'c.id = jca.customers_id')
            ->leftJoin('{{%judiciary_actions}} ja', 'ja.id = jca.judiciary_actions_id')
            ->leftJoin('{{%user}} u', 'u.id = jca.created_by')
            ->leftJoin('{{%lawyers}} lw', 'lw.id = j.lawyer_id')
            ->leftJoin('{{%court}} ct', 'ct.id = j.court_id')
            ->andWhere(['or', ['jca.is_deleted' => 0], ['jca.is_deleted' => null]])
            ->orderBy(['jca.id' => SORT_DESC])
            ->all();

        $exportRows = [];
        foreach ($rows as $r) {
            $num  = $r['judiciary_number'] ?: '';
            $year = $r['year'] ?: '';
            $exportRows[] = [
                'case'    => $num ? "{$num}/{$year}" : '#' . $r['judiciary_id'],
                'customer' => $r['cust_name'] ?: '—',
                'action'  => $r['action_name'] ?: '—',
                'note'    => $r['note'] ?: '—',
                'creator' => $r['user_name'] ?: '—',
                'lawyer'  => $r['lawyer_name'] ?: '—',
                'court'   => $r['court_name'] ?: '—',
                'contract' => $r['contract_id'] ?: '—',
                'date'    => $r['action_date'] ?: '—',
            ];
        }

        return $this->exportArrayData($exportRows, [
            'title'    => 'إجراءات العملاء القضائية',
            'filename' => 'judiciary_actions',
            'headers'  => ['القضية', 'المحكوم عليه', 'الإجراء', 'ملاحظات', 'المنشئ', 'المحامي', 'المحكمة', 'العقد', 'تاريخ الإجراء'],
            'keys'     => ['case', 'customer', 'action', 'note', 'creator', 'lawyer', 'court', 'contract', 'date'],
            'widths'   => [14, 22, 16, 24, 14, 18, 18, 10, 14],
        ], $format);
    }

    /* ═══════════════════════════════════════════════════════════
     *  تصدير تقرير القضايا (report) — Excel / PDF
     * ═══════════════════════════════════════════════════════════ */

    public function actionExportReportExcel()
    {
        $searchModel = new JudiciarySearch();
        $search = $searchModel->report();

        return $this->exportData($search['dataProvider'], [
            'title'    => 'تقرير القضايا',
            'filename' => 'judiciary_report',
            'headers'  => ['العقد', 'المحكمة', 'رقم القضية', 'أتعاب المحامي', 'العميل', 'الإجراء', 'تاريخ الإجراء'],
            'keys'     => [
                'contract_id',
                'court_name',
                'judiciary_number',
                'lawyer_cost',
                'customer_name',
                'action_name',
                'customer_date',
            ],
            'widths' => [10, 18, 16, 16, 24, 18, 14],
        ], 'excel');
    }

    public function actionExportReportPdf()
    {
        $searchModel = new JudiciarySearch();
        $search = $searchModel->report();

        return $this->exportData($search['dataProvider'], [
            'title'    => 'تقرير القضايا',
            'filename' => 'judiciary_report',
            'headers'  => ['العقد', 'المحكمة', 'رقم القضية', 'أتعاب المحامي', 'العميل', 'الإجراء', 'تاريخ الإجراء'],
            'keys'     => [
                'contract_id',
                'court_name',
                'judiciary_number',
                'lawyer_cost',
                'customer_name',
                'action_name',
                'customer_date',
            ],
        ], 'pdf');
    }

    /* ═══════════════════════════════════════════════════════════
     *  AJAX Tab Loaders — للتحميل الكسول في الشاشة الموحدة
     * ═══════════════════════════════════════════════════════════ */

    public function actionTabCases()
    {
        $params = Yii::$app->request->queryParams;
        foreach ($params as $k => $v) {
            if (strpos($k, '_tog') === 0) {
                unset($params[$k]);
            }
        }

        $searchModel = new JudiciarySearch();
        $search = $searchModel->search($params);
        return $this->renderAjax('_tab_cases', [
            'searchModel' => $searchModel,
            'dataProvider' => $search['dataProvider'],
            'counter' => $search['count'],
        ]);
    }

    public function actionTabActions()
    {
        $params = Yii::$app->request->queryParams;
        foreach ($params as $k => $v) {
            if (strpos($k, '_tog') === 0) {
                unset($params[$k]);
            }
        }

        $searchModel = new \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActionsSearch();
        $dataProvider = $searchModel->search($params);
        $dataProvider->pagination->pageSize = $dataProvider->pagination->pageSize ?: 10;
        $searchCounter = $searchModel->searchCounter($params);
        return $this->renderAjax('_tab_actions', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'searchCounter' => $searchCounter,
        ]);
    }

    public function actionTabPersistence()
    {
        $db = Yii::$app->db;
        $stats = $db->createCommand("
            SELECT
                COUNT(*) AS total,
                SUM(persistence_status IN ('red_renew','red_due')) AS cnt_red,
                SUM(persistence_status = 'orange_due') AS cnt_orange,
                SUM(persistence_status = 'green_due' OR persistence_status LIKE 'remaining_%') AS cnt_green
            FROM tbl_persistence_cache
        ")->queryOne();
        return $this->renderAjax('_tab_persistence', ['stats' => $stats]);
    }

    public function actionTabLegal()
    {
        $searchModel = new \backend\modules\contracts\models\ContractsSearch();
        $dataProvider = $searchModel->searchLegalDepartment(Yii::$app->request->queryParams);
        $dataCount = $searchModel->searchLegalDepartmentCount(Yii::$app->request->queryParams);
        return $this->renderAjax('_tab_legal', ['dataCount' => $dataCount]);
    }

    public function actionTabCounts()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $db = Yii::$app->db;
        $tp = $db->tablePrefix;

        $cases = (int)$db->createCommand("SELECT COUNT(*) FROM {$tp}judiciary WHERE (is_deleted = 0 OR is_deleted IS NULL)")->queryScalar();

        $actions = (int)$db->createCommand("SELECT COUNT(*) FROM {$tp}judiciary_customers_actions WHERE (is_deleted = 0 OR is_deleted IS NULL)")->queryScalar();

        $persistence = (int)$db->createCommand("SELECT COUNT(*) FROM tbl_persistence_cache")->queryScalar();

        $legal = (int)(new \backend\modules\contracts\models\ContractsSearch())
            ->searchLegalDepartmentCount([]);

        $collectionModel = new \backend\modules\collection\models\Collection();
        $collection = (int)$collectionModel->numberResolvingIssues();

        $pending = (int)$db->createCommand(
            "SELECT COUNT(*) FROM {$tp}judiciary_customers_actions WHERE request_status = 'pending' AND (is_deleted = 0 OR is_deleted IS NULL)"
        )->queryScalar();

        $persistenceStats = $db->createCommand("
            SELECT
                SUM(persistence_status IN ('red_renew','red_due')) AS cnt_red,
                SUM(persistence_status = 'orange_due') AS cnt_orange,
                SUM(persistence_status = 'green_due' OR persistence_status LIKE 'remaining_%') AS cnt_green
            FROM tbl_persistence_cache
        ")->queryOne();

        return [
            'cases' => $cases,
            'actions' => $actions,
            'persistence' => $persistence,
            'legal' => $legal,
            'collection' => $collection,
            'pending' => $pending,
            'stats' => [
                'red' => (int)($persistenceStats['cnt_red'] ?? 0),
                'orange' => (int)($persistenceStats['cnt_orange'] ?? 0),
                'green' => (int)($persistenceStats['cnt_green'] ?? 0),
                'collectionAmount' => $collectionModel->availableToCatch(),
            ],
        ];
    }

    public function actionTabCollection()
    {
        $searchModel = new \backend\modules\collection\models\CollectionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $model = new \backend\modules\collection\models\Collection();
        return $this->renderAjax('_tab_collection', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'amount' => $model->availableToCatch(),
            'count_contract' => $model->numberResolvingIssues(),
        ]);
    }

    /**
     * Displays a single Judiciary model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => '<i class="fa fa-gavel"></i> ملف القضية #' . $model->judiciary_number,
                'content' => $this->renderAjax('view', [
                    'model' => $model,
                ]),
                'footer' => Html::button('إغلاق', ['class' => 'btn btn-secondary float-start', 'data-bs-dismiss' => "modal"]) .
                    Html::a('<i class="fa fa-pencil"></i> تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary'])
            ];
        }

        $actionsDP = new \yii\data\ActiveDataProvider([
            'query' => JudiciaryCustomersActions::find()
                ->where(['judiciary_id' => $model->id]),
            'sort' => ['defaultOrder' => ['action_date' => SORT_DESC]],
            'pagination' => ['pageSize' => 20],
        ]);

        $lastRequestDate = (new \yii\db\Query())
            ->select(['jca.action_date'])
            ->from('os_judiciary_customers_actions jca')
            ->leftJoin('os_judiciary_actions ja', 'ja.id = jca.judiciary_actions_id')
            ->where(['jca.judiciary_id' => $model->id])
            ->andWhere(['ja.action_nature' => 'request'])
            ->andWhere(['or', ['jca.is_deleted' => 0], ['jca.is_deleted' => null]])
            ->orderBy(['jca.action_date' => SORT_DESC])
            ->limit(1)
            ->scalar();

        (new JudiciaryWorkflowService())->refreshStagesFromActions($model->id);
        $model->refresh();
        $defendantStages = $model->getDefendantStages()->with('customer')->all();
        JudiciaryDeadlineService::refreshAllStatuses();
        $activeDeadlines = $model->getActiveDeadlines()
            ->with(['customerAction.judiciaryActions', 'customerAction.customers'])
            ->orderBy(['deadline_date' => SORT_ASC])->all();
        $seizedAssets = $model->getSeizedAssets()->with('authority')->all();
        $correspondences = $model->getCorrespondences()->orderBy(['correspondence_date' => SORT_DESC])->limit(10)->all();

        return $this->render('view', [
            'model' => $model,
            'actionsDP' => $actionsDP,
            'lastRequestDate' => $lastRequestDate ?: null,
            'defendantStages' => $defendantStages,
            'activeDeadlines' => $activeDeadlines,
            'seizedAssets' => $seizedAssets,
            'correspondences' => $correspondences,
        ]);
    }


    public function actionPdfPageImage($path, $page = 1)
    {
        $page = max(1, (int)$page);
        $images = \backend\helpers\PdfToImageHelper::convertAndCache($path);

        if (empty($images) || !isset($images[$page - 1])) {
            throw new NotFoundHttpException('Could not render PDF page.');
        }

        $outFile = Yii::getAlias('@backend/web/' . $images[$page - 1]);
        return Yii::$app->response->sendFile($outFile, null, ['mimeType' => 'image/png', 'inline' => true]);
    }

    public function actionPrintCase($id)
    {
        $request = Yii::$app->request;
        $this->layout = '/print_cases';
        $model =  $this->findModel($id);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "contracts #" . $id,
                'content' => $this->renderAjax('print_case', [
                    'model' => $model,
                    'id' => $id
                ]),
                'footer' => Html::button('Close', ['class' => 'btn btn-secondary float-start', 'data-bs-dismiss' => "modal"]) .
                    Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
            ];
        } else {
            return $this->render('print_case', [
                'model' => $model,
            ]);
        }
    }

    public function actionPrintOverlay($id, $noteIndex = 0)
    {
        $model = $this->findModel($id);
        $contract = $model->contract;
        $courtName = $model->court ? $model->court->name : '';
        $address = $model->informAddress ? $model->informAddress->address : '';

        $kambAmount = ($contract->total_value ?: 0) * 1.15;
        $notes = \backend\modules\contracts\models\PromissoryNote::ensureNotesExist(
            $contract->id, $kambAmount, $contract->due_date
        );

        $noteIndex = max(0, min((int)$noteIndex, count($notes) - 1));
        $note = $notes[$noteIndex] ?? null;

        if (!$note) {
            throw new NotFoundHttpException('Promissory note not found.');
        }

        $cc = new \common\components\CompanyChecked();
        $cc->id = $contract->company_id;
        $companyInfo = $cc->findCompany();
        $companyName = $companyInfo ? $companyInfo->name : '';

        $viewFile = Yii::getAlias('@backend/modules/contracts/views/contracts/_print_overlay.php');
        return $this->renderFile($viewFile, [
            'model' => $contract,
            'note' => $note,
            'courtName' => $courtName,
            'address' => $address,
            'judiciaryId' => $model->id,
            'companyName' => $companyName,
        ]);
    }

    /**
     * Creates a new Judiciary model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($contract_id)
    {
        $request = Yii::$app->request;
        $model = new Judiciary();
        if ($model->load($request->post())) {
            $model->contract_id = $contract_id;
            if ($model->input_method == 1) {

                $total_amount = Contracts::findOne(['id' => $contract_id]);
                $total_amount = $total_amount->total_value;
                $paid_amount = ContractInstallment::find()
                    ->andWhere(['contract_id' => $contract_id])
                    ->sum('amount');

                $paid_amount = ($paid_amount > 0) ? $paid_amount : 0;
                $custamer_referance = (empty($custamer_referance)) ? 0 : $custamer_referance;

                $amount =  ($total_amount + $custamer_referance) - $paid_amount;

                $model->lawyer_cost = $amount * ($model->lawyer_cost / 100);
            }

            if ($model->save()) {
                \backend\modules\contracts\models\Contracts::updateAll(['company_id' => $model->company_id], ['id' => $contract_id]);

                $contractCustamersMosels = \backend\modules\customers\models\ContractsCustomers::find()->where(['contract_id' => $model->contract_id])->all();
                foreach ($contractCustamersMosels as $contractCustamersMosel) {
                    $judicaryCustamerAction = new \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions();
                    $judicaryCustamerAction->judiciary_id = $model->id;
                    $judicaryCustamerAction->customers_id = $contractCustamersMosel->customer_id;
                    $judicaryCustamerAction->judiciary_actions_id = 1;
                    $judicaryCustamerAction->note = null;
                    $judicaryCustamerAction->action_date = $model->income_date;
                    $judicaryCustamerAction->save();
                }
            }
            $modelContractDocumentFile = new \backend\modules\contractDocumentFile\models\ContractDocumentFile;
            $modelContractDocumentFile->document_type = 'judiciary file';
            $modelContractDocumentFile->contract_id = $model->id;
            $modelContractDocumentFile->save();
            Yii::$app->cache->set(Yii::$app->params['key_judiciary_contract'], Yii::$app->db->createCommand(Yii::$app->params['judiciary_contract_query'])->queryAll(), Yii::$app->params['time_duration']);
            Yii::$app->cache->set(Yii::$app->params['key_judiciary_year'], Yii::$app->db->createCommand(Yii::$app->params['judiciary_year_query'])->queryAll(), Yii::$app->params['time_duration']);
            if (Yii::$app->request->post('print') !== null) {
                return $this->redirect(['print-case', 'id' => $model->id]);
            } else {
                return $this->redirect(['index']);
            }
        } else {
            $queryParams = Yii::$app->request->queryParams;
            $contract_model = \backend\modules\contracts\models\Contracts::findOne($contract_id);
            if ($contract_model->is_locked()) {
                throw new \yii\web\HttpException(403, 'هذا العقد مقفل ومتابع من قبل موظف اخر.');
            } else {
                $contract_model->unlock();
                $contract_model->lock();
            }
            return $this->render('create', [
                'model' => $model,
                'contract_id' => $contract_id,
                'contract_model' => $contract_model,
                'modelCustomerAction' => new JudiciaryCustomersActions(),
                'modelsPhoneNumbersFollwUps' => [new \backend\modules\followUpReport\models\FollowUpReport],
            ]);
        }
    }


    /**
     * Updates an existing Judiciary model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);
        $modelCustomerAction = new JudiciaryCustomersActions();

        if ($model->load($request->post())) {
            if ($model->input_method == 1) {

                $total_amount = Contracts::findOne(['id' => $model->contract_id]);
                $total_amount = $total_amount->total_value;
                $paid_amount = ContractInstallment::find()
                    ->andWhere(['contract_id' => $model->contract_id])
                    ->sum('amount');

                $paid_amount = ($paid_amount > 0) ? $paid_amount : 0;
                $custamer_referance = (empty($custamer_referance)) ? 0 : $custamer_referance;

                $amount =  ($total_amount + $custamer_referance) - $paid_amount;

                $model->lawyer_cost = $amount * ($model->lawyer_cost / 100);
            }
            $model->save();

            \backend\modules\contracts\models\Contracts::updateAll(['company_id' => $model->company_id], ['id' => $model->contract_id]);
            Yii::$app->cache->set(Yii::$app->params['key_judiciary_contract'], Yii::$app->db->createCommand(Yii::$app->params['judiciary_contract_query'])->queryAll(), Yii::$app->params['time_duration']);
            Yii::$app->cache->set(Yii::$app->params['key_judiciary_year'], Yii::$app->db->createCommand(Yii::$app->params['judiciary_year_query'])->queryAll(), Yii::$app->params['time_duration']);

            return $this->redirect(['index']);
        }
        return $this->render('update', [
            'model' => $model,
            'modelCustomerAction' => $modelCustomerAction,
            'contract_id' => $model->contract_id
        ]);
    }

    /**
     * Delete an existing Judiciary model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id, $contract_id = null)
    {
        $request = Yii::$app->request;
        $this->findModel($id)->delete();
        $judicarysCustamer = JudiciaryCustomersActions::find()->where(['judiciary_id' => $id])->all();
        $conection = Yii::$app->getDb();
        $conection->createCommand('UPDATE `os_judiciary_customers_actions` SET `is_deleted`=1 WHERE `judiciary_id`=' . $id)->execute();


        if ($request->isAjax) {
            /*
             *   Process for ajax request
             */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        } else {
            /*
             *   Process for non-ajax request
             */
            return $this->redirect(['index']);
        }
    }

    /**
     * Delete multiple existing Judiciary model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionBulkDelete()
    {
        $request = Yii::$app->request;
        $rawPks = $request->post('pks');
        if ($rawPks === null || $rawPks === '') {
            return $this->redirect(['index']);
        }
        $pks = explode(',', $rawPks);
        foreach ($pks as $pk) {
            $model = $this->findModel($pk);
            $model->delete();
        }

        if ($request->isAjax) {
            /*
             *   Process for ajax request
             */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        } else {
            /*
             *   Process for non-ajax request
             */
            return $this->redirect(['index']);
        }
    }

    /* ═══════════════════════════════════════════════════════════════════
     *  التجهيز الجماعي للقضايا — معالج Batch Create
     * ═══════════════════════════════════════════════════════════════════ */

    /**
     * GET: عرض صفحة المعالج مع العقود المختارة
     * POST (contract_ids فقط): تحميل بيانات العقود وعرض المعالج
     * POST (submit): إنشاء القضايا جماعياً
     */
    public function actionBatchCreate()
    {
        $request = Yii::$app->request;

        // ─── جمع أرقام العقود من POST أو GET ───
        $rawIds = $request->post('contract_ids', $request->get('contract_ids', ''));
        if (is_array($rawIds)) {
            $contractIds = array_map('intval', $rawIds);
        } else {
            $contractIds = array_filter(array_map('intval', explode(',', (string)$rawIds)));
        }

        if (empty($contractIds)) {
            Yii::$app->session->setFlash('error', 'الرجاء تحديد عقود للتجهيز');
            return $this->redirect(['/contracts/contracts/legal-department']);
        }

        // ─── التحقق: هل هذا POST للإنشاء الفعلي؟ ───
        if ($request->isPost && $request->post('batch_submit')) {
            return $this->processBatchCreate($contractIds, $request);
        }

        // ─── تحميل بيانات العقود لعرض المعالج ───
        $contracts = Contracts::find()
            ->where(['id' => $contractIds])
            ->with(['customers'])
            ->all();

        // استبعاد العقود التي لها قضايا مسبقة
        $existingCases = Judiciary::find()
            ->select('contract_id')
            ->where(['contract_id' => $contractIds, 'is_deleted' => 0])
            ->column();

        $contractsData = [];
        foreach ($contracts as $c) {
            if (in_array($c->id, $existingCases)) continue;

            $paid = ContractInstallment::find()
                ->where(['contract_id' => $c->id])
                ->sum('amount') ?? 0;
            $remaining = $c->total_value - $paid;
            $customerNames = implode('، ', \yii\helpers\ArrayHelper::map($c->customers, 'id', 'name'));

            $contractsData[] = [
                'id'            => $c->id,
                'customer'      => $customerNames ?: '—',
                'total'         => (float)$c->total_value,
                'paid'          => (float)$paid,
                'remaining'     => round($remaining, 2),
                'sale_date'     => $c->Date_of_sale,
            ];
        }

        if (empty($contractsData)) {
            Yii::$app->session->setFlash('warning', 'جميع العقود المحددة لها قضايا مسبقة');
            return $this->redirect(['/contracts/contracts/legal-department']);
        }

        return $this->render('batch_create', [
            'contractsData' => $contractsData,
        ]);
    }

    /**
     * معالجة الإنشاء الجماعي الفعلي داخل Transaction
     */
    private function processBatchCreate($contractIds, $request)
    {
        $courtId     = (int)$request->post('court_id');
        $typeId      = (int)$request->post('type_id');
        $lawyerId    = (int)$request->post('lawyer_id');
        $companyId   = (int)$request->post('company_id');
        $addressId   = (int)$request->post('judiciary_inform_address_id');
        $year        = $request->post('year', date('Y'));
        $percentage  = (float)$request->post('lawyer_percentage', 0);

        // Validation
        if (!$courtId || !$lawyerId) {
            Yii::$app->session->setFlash('error', 'المحكمة والمحامي حقول مطلوبة');
            return $this->redirect(['batch-create', 'contract_ids' => implode(',', $contractIds)]);
        }

        // استبعاد العقود التي لها قضايا مسبقة
        $existingCases = Judiciary::find()
            ->select('contract_id')
            ->where(['contract_id' => $contractIds, 'is_deleted' => 0])
            ->column();
        $contractIds = array_diff($contractIds, $existingCases);

        if (empty($contractIds)) {
            Yii::$app->session->setFlash('warning', 'جميع العقود المحددة لها قضايا مسبقة');
            return $this->redirect(['/contracts/contracts/legal-department']);
        }

        $transaction = Yii::$app->db->beginTransaction();
        $createdIds = [];

        try {
            foreach ($contractIds as $contractId) {
                $contract = Contracts::findOne($contractId);
                if (!$contract) continue;

                // حساب أتعاب المحامي بالنسبة المئوية
                $paid = ContractInstallment::find()
                    ->where(['contract_id' => $contractId])
                    ->sum('amount') ?? 0;
                $remaining = $contract->total_value - $paid;
                $lawyerCost = ($percentage > 0) ? round($remaining * ($percentage / 100), 2) : 0;

                // إنشاء سجل القضية
                $model = new Judiciary();
                $model->contract_id = $contractId;
                $model->court_id = $courtId;
                $model->type_id = $typeId ?: 1;        // افتراضي لتلبية required
                $model->lawyer_id = $lawyerId;
                $model->company_id = $companyId ?: null;
                $model->judiciary_inform_address_id = $addressId ?: 1; // افتراضي لتلبية required
                $model->lawyer_cost = $lawyerCost;
                $model->case_cost = 0;
                $model->year = (string)$year;
                $model->income_date = date('Y-m-d');

                if (!$model->save(false)) {
                    throw new \Exception('فشل إنشاء القضية للعقد #' . $contractId);
                }

                $createdIds[] = $model->id;

                Contracts::updateAll(
                    ['company_id' => $companyId ?: $contract->company_id],
                    ['id' => $contractId]
                );

                // إنشاء إجراءات العملاء
                $contractCustomers = \backend\modules\customers\models\ContractsCustomers::find()
                    ->where(['contract_id' => $contractId])
                    ->all();
                foreach ($contractCustomers as $cc) {
                    $action = new JudiciaryCustomersActions();
                    $action->judiciary_id = $model->id;
                    $action->customers_id = $cc->customer_id;
                    $action->judiciary_actions_id = 1;
                    $action->note = null;
                    $action->action_date = date('Y-m-d');
                    $action->save();
                }

                // إنشاء ملف المستند
                $docFile = new ContractDocumentFile();
                $docFile->document_type = 'judiciary file';
                $docFile->contract_id = $model->id;
                $docFile->save();
            }

            $transaction->commit();

            // تحديث الكاش
            try {
                if (isset(Yii::$app->params['key_judiciary_contract'])) {
                    Yii::$app->cache->set(
                        Yii::$app->params['key_judiciary_contract'],
                        Yii::$app->db->createCommand(Yii::$app->params['judiciary_contract_query'])->queryAll(),
                        Yii::$app->params['time_duration']
                    );
                    Yii::$app->cache->set(
                        Yii::$app->params['key_judiciary_year'],
                        Yii::$app->db->createCommand(Yii::$app->params['judiciary_year_query'])->queryAll(),
                        Yii::$app->params['time_duration']
                    );
                }
            } catch (\Exception $e) { /* ignore cache errors */ }

            Yii::$app->session->setFlash('success', 'تم إنشاء ' . count($createdIds) . ' قضية بنجاح');

            // التحويل لصفحة الطباعة الجماعية
            return $this->redirect(['batch-print', 'ids' => implode(',', $createdIds)]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'حدث خطأ: ' . $e->getMessage());
            return $this->redirect(['batch-create', 'contract_ids' => implode(',', $contractIds)]);
        }
    }

    /**
     * طباعة جماعية لعدة قضايا — صفحات A4 متتالية
     */
    public function actionBatchPrint(string $ids = '')
    {
        $this->layout = '/print_cases';
        $judiciaryIds = array_filter(array_map('intval', explode(',', $ids)));

        if (empty($judiciaryIds)) {
            throw new NotFoundHttpException('لا توجد قضايا للطباعة');
        }

        $models = Judiciary::find()
            ->where(['id' => $judiciaryIds])
            ->with(['contract', 'lawyer', 'court', 'customersAndGuarantor', 'informAddress'])
            ->all();

        if (empty($models)) {
            throw new NotFoundHttpException('لا توجد قضايا للطباعة');
        }

        return $this->render('batch_print', [
            'models' => $models,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════
     *  المراسلات والتبليغات — AJAX list for case view
     * ═══════════════════════════════════════════════════════════ */

    public function actionCorrespondenceList($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $type = Yii::$app->request->get('type');

        $query = DiwanCorrespondence::find()->forCase((int)$id)->chronological();
        if ($type) {
            $query->andWhere(['communication_type' => $type]);
        }

        $rows = $query->with(['customer', 'authority'])->asArray()->all();

        $entityService = new EntityResolverService();
        $result = [];
        foreach ($rows as $row) {
            $recipientName = '';
            if ($row['recipient_type'] === 'defendant') {
                $recipientName = $row['customer']['name'] ?? '';
            } elseif ($row['recipient_type'] && $row['recipient_type'] !== 'defendant') {
                $fkId = $row['bank_id'] ?? $row['job_id'] ?? $row['authority_id'] ?? null;
                if ($fkId) {
                    $recipientName = $entityService->getDisplayName($row['recipient_type'], (int)$fkId);
                }
            }
            $row['recipient_name'] = $recipientName;
            $result[] = $row;
        }

        return ['success' => true, 'data' => $result];
    }

    /* ═══════════════════════════════════════════════════════════
     *  لوحة المواعيد — Deadline Dashboard data
     * ═══════════════════════════════════════════════════════════ */

    public function actionDeadlineDashboard()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        JudiciaryDeadlineService::refreshAllStatuses();

        $expired = JudiciaryDeadline::find()
            ->where(['status' => JudiciaryDeadline::STATUS_EXPIRED])
            ->orderBy(['deadline_date' => SORT_ASC])
            ->limit(20)
            ->with(['judiciary'])
            ->asArray()
            ->all();

        $approaching = JudiciaryDeadline::find()
            ->where(['status' => JudiciaryDeadline::STATUS_APPROACHING])
            ->orderBy(['deadline_date' => SORT_ASC])
            ->limit(20)
            ->with(['judiciary'])
            ->asArray()
            ->all();

        return [
            'success' => true,
            'expired' => $expired,
            'approaching' => $approaching,
            'counts' => [
                'expired' => JudiciaryDeadline::find()->where(['status' => 'expired'])->count(),
                'approaching' => JudiciaryDeadline::find()->where(['status' => 'approaching'])->count(),
                'pending' => JudiciaryDeadline::find()->where(['status' => 'pending'])->count(),
            ],
        ];
    }

    /* ═══════════════════════════════════════════════════════════
     *  لوحة المواعيد — HTML View
     * ═══════════════════════════════════════════════════════════ */

    public function actionDeadlineDashboardView()
    {
        JudiciaryDeadlineService::refreshAllStatuses();

        $expired = JudiciaryDeadline::find()
            ->where(['status' => JudiciaryDeadline::STATUS_EXPIRED])
            ->orderBy(['deadline_date' => SORT_ASC])
            ->with(['judiciary'])
            ->all();

        $approaching = JudiciaryDeadline::find()
            ->where(['status' => JudiciaryDeadline::STATUS_APPROACHING])
            ->orderBy(['deadline_date' => SORT_ASC])
            ->with(['judiciary'])
            ->all();

        $pending = JudiciaryDeadline::find()
            ->where(['status' => JudiciaryDeadline::STATUS_PENDING])
            ->orderBy(['deadline_date' => SORT_ASC])
            ->with(['judiciary'])
            ->all();

        return $this->render('deadline_dashboard', [
            'expired' => $expired,
            'approaching' => $approaching,
            'pending' => $pending,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════
     *  بحث موحد عن الجهات — Entity Search for Select2
     * ═══════════════════════════════════════════════════════════ */

    public function actionEntitySearch()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $q = Yii::$app->request->get('q', '');
        $type = Yii::$app->request->get('type');
        $companyId = Yii::$app->user->identity->company_id ?? null;

        $service = new EntityResolverService();
        $results = $service->search($q, $type, $companyId);

        return [
            'results' => array_map(function ($dto) {
                return $dto->toArray();
            }, $results),
        ];
    }

    /* ═══════════════════════════════════════════════════════════
     *  توليد الطلبات الإجرائية — Request Generation Wizard
     * ═══════════════════════════════════════════════════════════ */

    public function actionGenerateRequest($id)
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;
        $db = Yii::$app->db;

        if ($request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $templateIds = $request->post('template_ids', []);
            if (is_string($templateIds)) {
                $templateIds = json_decode($templateIds, true) ?: [];
            }
            $defendantId = $request->post('defendant_id');
            $representativeId = $request->post('representative_id', $model->lawyer_id);

            $context = [
                'defendant_id' => $defendantId,
                'representative_id' => $representativeId,
                'employer_name_override' => $request->post('employer_name_override', ''),
                'bank_name_override' => $request->post('bank_name_override', ''),
                'authority_name' => $request->post('authority_name', ''),
                'amount' => $request->post('amount', ''),
                'notification_date' => $request->post('notification_date', ''),
            ];

            try {
                $generator = new JudiciaryRequestGenerator();
                $html = $generator->generate($model->id, $templateIds, $context);
                return ['success' => true, 'html' => $html];
            } catch (\Exception $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }

        $templates = JudiciaryRequestTemplate::find()
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->all();

        $defendants = $model->customersAndGuarantor;
        $lawyers = \backend\modules\lawyers\models\Lawyers::find()->all();

        $defendantProfiles = [];
        foreach ($defendants as $d) {
            $employer = '';
            if ($d->job_title) {
                $employer = $db->createCommand("SELECT name FROM {{%jobs}} WHERE id=:id", [':id' => $d->job_title])->queryScalar() ?: '';
            }
            $bank = '';
            if ($d->bank_name) {
                $bank = $db->createCommand("SELECT name FROM {{%bancks}} WHERE id=:id", [':id' => $d->bank_name])->queryScalar() ?: '';
            }
            $defendantProfiles[$d->id] = [
                'name'        => $d->name,
                'id_number'   => $d->id_number ?: '',
                'employer'    => $employer,
                'bank'        => $bank,
                'bank_branch' => $d->bank_branch ?: '',
                'account'     => $d->account_number ?: '',
                'salary'      => (float) ($d->total_salary ?: 0),
                'phone'       => $d->primary_phone_number ?: '',
            ];
        }

        $templateMeta = [];
        foreach ($templates as $t) {
            preg_match_all('/\{\{(\w+)\}\}/', $t->template_content, $matches);
            $templateMeta[$t->id] = [
                'type' => $t->template_type,
                'placeholders' => array_unique($matches[1] ?? []),
            ];
        }

        $contractAmount = $model->contract ? (float) ($model->contract->total_value ?: 0) : 0;

        return $this->render('generate_request', [
            'model' => $model,
            'templates' => $templates,
            'defendants' => $defendants,
            'lawyers' => $lawyers,
            'defendantProfiles' => $defendantProfiles,
            'templateMeta' => $templateMeta,
            'contractAmount' => $contractAmount,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════
     *  حفظ الطلب كإجراء — Save Generated Request as Action
     * ═══════════════════════════════════════════════════════════ */

    public function actionSaveGeneratedRequest($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        $html = $request->post('html', '');
        $defendantId = $request->post('defendant_id');
        $templateIds = $request->post('template_ids', '');

        if (empty($html) || empty($defendantId)) {
            return ['success' => false, 'message' => 'بيانات ناقصة'];
        }

        $jca = new \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions();
        $jca->judiciary_id = $model->id;
        $jca->customers_id = (int) $defendantId;
        $jca->judiciary_actions_id = 1;
        $jca->action_date = date('Y-m-d');
        $jca->note = 'طلب إجرائي — قوالب: ' . $templateIds;
        $jca->request_status = 'printed';

        if ($jca->save()) {
            return ['success' => true, 'message' => 'تم حفظ الطلب كإجراء بحالة "مطبوع"', 'action_id' => $jca->id];
        }

        return ['success' => false, 'message' => 'خطأ في الحفظ: ' . implode(', ', $jca->getFirstErrors())];
    }

    /* ═══════════════════════════════════════════════════════════
     *  تحديث المواعيد — Refresh Deadline Statuses
     * ═══════════════════════════════════════════════════════════ */

    public function actionRefreshDeadlines()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $updated = JudiciaryDeadlineService::refreshAllStatuses();
        return ['success' => true, 'updated' => $updated];
    }

    /* ═══════════════════════════════════════════════════════════
     *  مزامنة العطل — Sync Holidays from API
     * ═══════════════════════════════════════════════════════════ */

    public function actionSyncHolidays()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $year = (int)Yii::$app->request->get('year', date('Y'));
        $service = new HolidayService();
        return $service->syncFromApi($year);
    }

    /**
     * Finds the Judiciary model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Judiciary the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Judiciary::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionCustomerAction($judiciary, $contract_id)
    {
        $modelCustomerAction = new JudiciaryCustomersActions();
        $request = Yii::$app->request;
        $modelCustomerAction->judiciary_id = $judiciary;

        if ($modelCustomerAction->load($request->post())) {
            // Handle file upload
            $uploadedFile = \yii\web\UploadedFile::getInstance($modelCustomerAction, 'image');
            if ($uploadedFile) {
                $filePath = 'uploads/judiciary_customers_actions/' . uniqid() . '.' . $uploadedFile->extension;
                if ($uploadedFile->saveAs($filePath)) {
                    $modelCustomerAction->image = $filePath; // Save the file path to the model
                }
            }

            if ($modelCustomerAction->save()) {
                return $this->redirect(['update', 'id' => $judiciary, 'contract_id' => $contract_id]);
            }
        }

        return $this->render('update', [
            'modelCustomerAction' => $modelCustomerAction,
            'judiciary' => $judiciary,
            'contract_id' => $contract_id,
        ]);
    }

    public function actionDeleteCustomerAction($id, $judiciary)
    {
        $request = Yii::$app->request;
        $model = JudiciaryCustomersActions::findOne($id);
        $model->delete();

        if ($request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [
                'forceClose' => true,
            ];
        }
        return $this->redirect(['update', 'id' => $judiciary]);
    }

    /* ═══════════════════════════════════════════════════════════
     *  الإدخال المجمّع الذكي للإجراءات القضائية
     * ═══════════════════════════════════════════════════════════ */

    public function actionBatchActions()
    {
        return $this->render('batch_actions');
    }

    public function actionBatchParse()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $raw = Yii::$app->request->post('numbers', '');
        $lines = preg_split('/[\r\n]+/', trim($raw));

        $results = [];
        $db = Yii::$app->db;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            $parsed = $this->parseCaseNumber($line);
            if (!$parsed) {
                $results[] = ['input' => $line, 'status' => 'error', 'message' => 'تعذر تحليل الرقم'];
                continue;
            }

            $number = $parsed['number'];
            $year = $parsed['year'];

            $query = $db->createCommand("
                SELECT j.id, j.judiciary_number, j.year, j.contract_id, j.court_id,
                       c.name as court_name
                FROM os_judiciary j
                LEFT JOIN os_court c ON c.id = j.court_id
                WHERE j.judiciary_number = :num AND j.year = :yr AND (j.is_deleted = 0 OR j.is_deleted IS NULL)
            ", [':num' => $number, ':yr' => $year])->queryAll();

            if (count($query) === 0) {
                $results[] = [
                    'input' => $line, 'status' => 'not_found',
                    'number' => $number, 'year' => $year,
                    'message' => 'لم يتم العثور على قضية'
                ];
            } elseif (count($query) === 1) {
                $row = $query[0];
                $parties = $this->getCaseParties($row['contract_id']);
                $results[] = [
                    'input' => $line, 'status' => 'matched',
                    'number' => $number, 'year' => $year,
                    'judiciary_id' => $row['id'],
                    'contract_id' => $row['contract_id'],
                    'court_name' => $row['court_name'],
                    'parties' => $parties,
                ];
            } else {
                $options = [];
                foreach ($query as $row) {
                    $parties = $this->getCaseParties($row['contract_id']);
                    $options[] = [
                        'judiciary_id' => $row['id'],
                        'contract_id' => $row['contract_id'],
                        'court_name' => $row['court_name'],
                        'parties' => $parties,
                    ];
                }
                $results[] = [
                    'input' => $line, 'status' => 'multiple',
                    'number' => $number, 'year' => $year,
                    'options' => $options,
                    'message' => 'أكثر من قضية بنفس الرقم'
                ];
            }
        }

        return ['success' => true, 'results' => $results, 'total' => count($results)];
    }

    public function actionBatchExecute()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;
        $casesRaw = $request->post('cases', '[]');
        $cases = is_string($casesRaw) ? json_decode($casesRaw, true) : $casesRaw;
        if (!is_array($cases)) $cases = [];
        $globalActionId = (int)$request->post('action_id', 0);
        $globalActionDate = $request->post('action_date', date('Y-m-d'));
        $note = $request->post('note', '');
        $autoApprove = $request->post('auto_approve', '0') === '1';

        if (empty($cases)) {
            return ['success' => false, 'message' => 'بيانات ناقصة'];
        }

        $actionDefCache = [];
        $savedTotal = 0;
        $errors = [];
        $details = [];

        foreach ($cases as $case) {
            $judiciaryId = (int)($case['judiciary_id'] ?? 0);
            $contractId = (int)($case['contract_id'] ?? 0);
            $caseActionId = (int)($case['action_id'] ?? $globalActionId);
            $caseDate = !empty($case['action_date']) ? $case['action_date'] : $globalActionDate;

            if (!$judiciaryId || !$caseActionId) {
                $errors[] = $case['input'] ?? '?';
                continue;
            }

            if (!isset($actionDefCache[$caseActionId])) {
                $actionDefCache[$caseActionId] = \backend\modules\judiciaryActions\models\JudiciaryActions::findOne($caseActionId);
            }
            $actionDef = $actionDefCache[$caseActionId];

            $partyIds = $case['party_ids'] ?? [];
            if (empty($partyIds)) {
                $allParties = $this->getCaseParties($contractId);
                $partyIds = array_column($allParties, 'customer_id');
            }

            $caseSaved = 0;
            foreach ($partyIds as $customerId) {
                $customerId = (int)$customerId;
                if ($customerId <= 0) continue;
                $record = new JudiciaryCustomersActions();
                $record->judiciary_id = $judiciaryId;
                $record->customers_id = $customerId;
                $record->judiciary_actions_id = $caseActionId;
                $record->action_date = $caseDate;
                $record->note = !empty($case['note']) ? $case['note'] : $note;
                if ($actionDef && $actionDef->action_nature === 'request') {
                    $record->request_status = $autoApprove ? 'approved' : 'pending';
                }
                if ($record->save()) {
                    $caseSaved++;
                    $savedTotal++;
                }
            }
            $details[] = [
                'input' => $case['input'] ?? '',
                'judiciary_id' => $judiciaryId,
                'action_id' => $caseActionId,
                'saved' => $caseSaved,
            ];
        }

        return [
            'success' => true,
            'total_saved' => $savedTotal,
            'total_cases' => count($cases),
            'errors' => $errors,
            'details' => $details,
        ];
    }

    private function parseCaseNumber($input)
    {
        $input = trim($input);
        $parts = preg_split('/[\/\\\\\-\s]+/', $input);
        if (count($parts) < 2) {
            if (ctype_digit($input)) return ['number' => (int)$input, 'year' => null];
            return null;
        }

        $a = trim($parts[0]);
        $b = trim($parts[1]);
        if (!ctype_digit($a) || !ctype_digit($b)) return null;

        $a = (int)$a;
        $b = (int)$b;

        if ($a >= 2005 && $a <= 2035 && !($b >= 2005 && $b <= 2035)) {
            return ['year' => (string)$a, 'number' => $b];
        }
        if ($b >= 2005 && $b <= 2035 && !($a >= 2005 && $a <= 2035)) {
            return ['year' => (string)$b, 'number' => $a];
        }
        if ($a >= 2005 && $a <= 2035) {
            return ['year' => (string)$a, 'number' => $b];
        }
        return ['year' => (string)$b, 'number' => $a];
    }

    private function getCaseParties($contractId)
    {
        if (!$contractId) return [];
        return (new \yii\db\Query())
            ->select(['cc.customer_id', 'c.name', 'cc.customer_type'])
            ->from('os_contracts_customers cc')
            ->innerJoin('os_customers c', 'c.id = cc.customer_id')
            ->where(['cc.contract_id' => $contractId])
            ->all();
    }
}
