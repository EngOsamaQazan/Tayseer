<?php
namespace backend\modules\reports\controllers;

use Yii;
use backend\modules\income\models\IncomeSearch;
use backend\modules\income\models\Income;
use backend\modules\judiciary\models\JudiciarySearch;

use backend\modules\followUp\models\FollowUp;
use backend\modules\followUp\models\FollowUpSearch;
use yii\filters\AccessControl;
use common\helper\Permissions;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use yii\data\SqlDataProvider;
use backend\modules\judiciary\models\Judiciary;
use backend\modules\reports\models\CustomersJudiciaryActionsSearch;
use backend\helpers\ExportTrait;

/**
 * FollowUpReportsController implements the CRUD actions for FollowUpReports model.
 */
class ReportsController extends Controller
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
                    /* ═══ عرض التقارير ═══ */
                    [
                        'actions' => [
                            'customers-judiciary-actions', 'judiciary-index',
                            'index', 'index2',
                            'due-installment', 'month-installments', 'this-month-installments',
                            'monthly-installmentBeer-user', 'monthly-installment',
                            'total-customer-payments-index', 'total-judiciary-customer-payments-index',
                            'export-income-reports-excel', 'export-income-reports-pdf',
                            'export-total-payments-excel', 'export-total-payments-pdf',
                            'export-jud-payments-excel', 'export-jud-payments-pdf',
                            'export-follow-up-reports-excel', 'export-follow-up-reports-pdf',
                            'export-jud-actions-excel', 'export-jud-actions-pdf',
                            'export-jud-report-excel', 'export-jud-report-pdf',
                            'export-jud-index-excel', 'export-jud-index-pdf',
                        ],
                        'allow' => true,
                        'roles' => [Permissions::REP_VIEW],
                    ],
                    /* ═══ تسجيل خروج ═══ */
                    ['actions' => ['logout'], 'allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
            //             [
//            'class' => 'yii\filters\PageCache',
//            'only' => ['index'],
//            'duration' => 60,
//            'variations' => [
//                \Yii::$app->language,
//            ],
//            'dependency' => [
//                'class' => 'yii\caching\DbDependency',
//                'sql' => 'SELECT COUNT(*) FROM os_follow_up_report',
//            ],
//        ],
        ];
    }

    /**
     * Lists all FollowUpReports models.
     * @return mixed
     */
    public function actionIndex()
    {
        $db = Yii::$app->db;
        $request = Yii::$app->request;

        // فلتر الشهر والسنة — الافتراضي: الشهر الحالي
        $month = (int)$request->get('month', date('n'));
        $year  = (int)$request->get('year', date('Y'));

        $dateFilter = "YEAR(i.date) = :yr AND MONTH(i.date) = :mo";
        $params = [':yr' => $year, ':mo' => $month];

        try {
            $totalIncome = $db->createCommand(
                "SELECT COALESCE(SUM(i.amount), 0) FROM os_income i WHERE $dateFilter", $params
            )->queryScalar();
        } catch (\Exception $e) { $totalIncome = 0; }

        try {
            $incomeCount = $db->createCommand(
                "SELECT COUNT(*) FROM os_income i WHERE $dateFilter", $params
            )->queryScalar();
        } catch (\Exception $e) { $incomeCount = 0; }

        try {
            $followUpCount = $db->createCommand(
                "SELECT COUNT(*) FROM os_follow_up f WHERE YEAR(f.date_time) = :yr AND MONTH(f.date_time) = :mo", $params
            )->queryScalar();
        } catch (\Exception $e) { $followUpCount = 0; }

        try {
            $judiciaryCount = $db->createCommand(
                "SELECT COUNT(*) FROM os_judiciary"
            )->queryScalar();
        } catch (\Exception $e) { $judiciaryCount = 0; }

        return $this->render('index', [
            'totalIncome' => $totalIncome,
            'incomeCount' => $incomeCount,
            'followUpCount' => $followUpCount,
            'judiciaryCount' => $judiciaryCount,
            'selectedMonth' => $month,
            'selectedYear' => $year,
        ]);
    }

    public function actionMonthlyInstallment()
    {
        $sql = "SELECT COALESCE(SUM(i.amount), 0) AS SUM, YEAR(i.date) AS YEAR, MONTH(i.date) AS MONTH 
                FROM os_income i 
                GROUP BY YEAR(i.date), MONTH(i.date) 
                ORDER BY YEAR DESC, MONTH DESC";
        $count = count(Yii::$app->db->createCommand($sql)->queryAll());
        $dataProvider = new SqlDataProvider([
            'sql' => $sql,
            'totalCount' => $count,
        ]);
        return $this->render('monthly_installment', [
            'monthly_installment' => $dataProvider,
        ]);
    }

    public function actionMonthlyInstallmentBeerUser()
    {
        $sql = "SELECT COALESCE(SUM(i.amount), 0) AS SUM, YEAR(i.date) AS YEAR, MONTH(i.date) AS MONTH, i._by AS NAME 
                FROM os_income i 
                WHERE i._by IS NOT NULL AND i._by != ''
                GROUP BY YEAR(i.date), MONTH(i.date), i._by 
                ORDER BY YEAR DESC, MONTH DESC";
        $count = count(Yii::$app->db->createCommand($sql)->queryAll());
        $dataProvider = new SqlDataProvider([
            'sql' => $sql,
            'totalCount' => $count,
        ]);
        return $this->render('monthly_installment_monthly_beer_user', [
            'monthly_installment_monthly_beer_user' => $dataProvider,
        ]);
    }

    public function actionDueInstallment()
    {
        $sql = "SELECT i.*, COALESCE(SUM(i.amount), 0) as total_sum, COUNT(i.id) as total_installment 
                FROM os_income i 
                WHERE i.date < CURDATE()
                GROUP BY i._by 
                ORDER BY total_sum DESC";
        $count = count(Yii::$app->db->createCommand($sql)->queryAll());
        $dataProvider = new SqlDataProvider([
            'sql' => $sql,
            'totalCount' => $count,
        ]);
        return $this->render('due_installment', [
            'due_installment' => $dataProvider,
        ]);
    }

    public function actionThisMonthInstallments($date = null)
    {
        if ($date == null) {
            $date = date("Y-m-d");
        }
        $sql = "SELECT i.* FROM os_income i 
                WHERE i.date BETWEEN DATE_FORMAT(:dt,'%Y-%m-01') AND LAST_DAY(:dt2)
                ORDER BY i.date DESC";
        $allRows = Yii::$app->db->createCommand($sql, [':dt' => $date, ':dt2' => $date])->queryAll();
        $count = count($allRows);
        $dataProvider = new SqlDataProvider([
            'sql' => $sql,
            'params' => [':dt' => $date, ':dt2' => $date],
            'totalCount' => $count,
        ]);
        return $this->render('this_month_installments', [
            'this_month_installments' => $dataProvider,
        ]);
    }

    public function actionIndex2()
    {
        $searchModel = new FollowUpSearch();
        $dataProvider = $searchModel->searchReport(Yii::$app->request->queryParams);
        $searchCount = $searchModel->searchReportCount(Yii::$app->request->queryParams);
        return $this->render('/follow-up-reports/index', [
            'searchModel' => $searchModel,
            'count' => $searchCount,
            'dataProvider' => $dataProvider,
        ]);
    }


    /**
     * Displays a single FollowUpReports model.
     * @param integer $id
     * @return mixed
     */
    public function actionTotalCustomerPaymentsIndex()
    {
        $searchModel = new IncomeSearch();
        $dataProvider = $searchModel->totalCustomerPayments(Yii::$app->request->queryParams);
        $sumTotalCustomerPayments = $searchModel->sumTotalCustomerPayments(Yii::$app->request->queryParams);
        $sumTotalCustomerPayments = $sumTotalCustomerPayments;

        return $this->render('/income-reports/TotalCustomerPaymentsIndex.php', [
            'searchModel' => $searchModel,
            'sumTotalCustomerPayments' => $sumTotalCustomerPayments,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionTotalJudiciaryCustomerPaymentsIndex()
    {
        $searchModel = new IncomeSearch();
        $dataProvider = $searchModel->totalJudiciaryCustomerPayments(Yii::$app->request->queryParams);
        $sumTotalCustomerPayments = $searchModel->sumTotalJudiciaryCustomerPayments(Yii::$app->request->queryParams);

        return $this->render('/income-reports/TotalJudiciaryCustomerPaymentsIndex.php', [
            'searchModel' => $searchModel,
            'sumTotalCustomerPayments' => $sumTotalCustomerPayments,
            'dataProvider' => $dataProvider,
        ]);
    }
    public function actionJudiciaryIndex()
    {
        $request = Yii::$app->request->queryParams;
        $searchModel = new JudiciarySearch();

        // التحقق هل تم تطبيق فلتر فعلاً
        $hasFilter = false;
        $filterKeys = ['court_id', 'type_id', 'lawyer_id', 'contract_id', 'from_income_date', 'to_income_date', 'year', 'judiciary_number', 'lawyer_cost', 'case_cost'];
        $searchClass = (new \ReflectionClass($searchModel))->getShortName();
        foreach ($filterKeys as $key) {
            if (!empty($request[$searchClass][$key] ?? null)) {
                $hasFilter = true;
                break;
            }
        }

        if ($hasFilter) {
            $db = Yii::$app->db;
            $search = $db->cache(function ($db) use ($searchModel, $request) {
                return $searchModel->reportSearch($request);
            });
            $dataProvider = $search['dataProvider'];
            $counter = $search['count'];
        } else {
            $dataProvider = new \yii\data\ArrayDataProvider(['allModels' => []]);
            $counter = 0;
        }

        return $this->render('/judiciary/index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'counter' => $counter,
            'hasFilter' => $hasFilter,
        ]);
    }

    public function actionCustomersJudiciaryActions()
    {
        $request = Yii::$app->request->queryParams;
        $searchModel = new CustomersJudiciaryActionsSearch();

        // التحقق هل تم تطبيق فلتر فعلاً
        $hasFilter = false;
        $filterKeys = ['customer_id', 'customer_name', 'court_name'];
        $searchClass = (new \ReflectionClass($searchModel))->getShortName();
        foreach ($filterKeys as $key) {
            if (!empty($request[$searchClass][$key] ?? null)) {
                $hasFilter = true;
                break;
            }
        }

        if ($hasFilter) {
            $dataProvider = $searchModel->search($request);
        } else {
            $dataProvider = new \yii\data\ArrayDataProvider(['allModels' => []]);
        }

        return $this->render('/customers-judiciary-actions-report/index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'hasFilter' => $hasFilter,
        ]);
    }

    /* ═══════════════════════════════════════════════════
     *  Export Actions
     * ═══════════════════════════════════════════════════ */

    private function _incomeReportExportConfig()
    {
        return [
            'title'    => 'تقرير الإيرادات',
            'filename' => 'income_reports',
            'headers'  => ['#', 'رقم العقد', 'الحالة', 'التاريخ', 'المبلغ', 'المنشئ', 'طريقة الدفع', 'العميل', 'التصنيف'],
            'keys'     => ['#', 'contract_id', 'status.status', 'date', 'amount', 'createdBy.username', 'paymentType.name', '_by', 'incomeCategory.name'],
            'widths'   => [6, 14, 12, 14, 14, 16, 16, 20, 16],
        ];
    }

    public function actionExportIncomeReportsExcel()
    {
        $searchModel = new IncomeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->exportData($dataProvider, $this->_incomeReportExportConfig());
    }

    public function actionExportIncomeReportsPdf()
    {
        $searchModel = new IncomeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->exportData($dataProvider, $this->_incomeReportExportConfig(), 'pdf');
    }

    private function _totalPaymentsExportConfig()
    {
        return [
            'title'    => 'تقرير مدفوعات العملاء',
            'filename' => 'total_customer_payments',
            'headers'  => ['#', 'رقم العقد', 'الحالة', 'التاريخ', 'المبلغ', 'المنشئ', 'طريقة الدفع', 'العميل', 'التصنيف'],
            'keys'     => ['#', 'contract_id', 'status.status', 'date', 'amount', 'createdBy.username', 'paymentType.name', '_by', 'incomeCategory.name'],
            'widths'   => [6, 14, 12, 14, 14, 16, 16, 20, 16],
        ];
    }

    public function actionExportTotalPaymentsExcel()
    {
        $searchModel = new IncomeSearch();
        $dataProvider = $searchModel->totalCustomerPayments(Yii::$app->request->queryParams);
        return $this->exportData($dataProvider, $this->_totalPaymentsExportConfig());
    }

    public function actionExportTotalPaymentsPdf()
    {
        $searchModel = new IncomeSearch();
        $dataProvider = $searchModel->totalCustomerPayments(Yii::$app->request->queryParams);
        return $this->exportData($dataProvider, $this->_totalPaymentsExportConfig(), 'pdf');
    }

    private function _judPaymentsExportConfig()
    {
        return [
            'title'    => 'إيرادات عملاء القضايا',
            'filename' => 'judiciary_customer_payments',
            'headers'  => ['#', 'رقم العقد', 'الحالة', 'التاريخ', 'المبلغ', 'المنشئ', 'طريقة الدفع', 'العميل', 'التصنيف'],
            'keys'     => ['#', 'contract_id', 'status.status', 'date', 'amount', 'createdBy.username', 'paymentType.name', '_by', 'incomeCategory.name'],
            'widths'   => [6, 14, 12, 14, 14, 16, 16, 20, 16],
        ];
    }

    public function actionExportJudPaymentsExcel()
    {
        $searchModel = new IncomeSearch();
        $dataProvider = $searchModel->totalJudiciaryCustomerPayments(Yii::$app->request->queryParams);
        return $this->exportData($dataProvider, $this->_judPaymentsExportConfig());
    }

    public function actionExportJudPaymentsPdf()
    {
        $searchModel = new IncomeSearch();
        $dataProvider = $searchModel->totalJudiciaryCustomerPayments(Yii::$app->request->queryParams);
        return $this->exportData($dataProvider, $this->_judPaymentsExportConfig(), 'pdf');
    }

    private function _followUpReportsExportConfig()
    {
        return [
            'title'    => 'تقارير المتابعة',
            'filename' => 'follow_up_reports',
            'headers'  => ['#', 'رقم العقد', 'التاريخ', 'الملاحظات', 'الشعور', 'المنشئ', 'التذكير', 'وعد بالدفع'],
            'keys'     => ['#', 'contract_id', 'date_time', 'notes', 'feeling', 'createdBy.username', 'reminder', 'promise_to_pay_at'],
            'widths'   => [6, 14, 18, 30, 14, 16, 18, 18],
        ];
    }

    public function actionExportFollowUpReportsExcel()
    {
        $searchModel = new FollowUpSearch();
        $dataProvider = $searchModel->searchReport(Yii::$app->request->queryParams);
        return $this->exportData($dataProvider, $this->_followUpReportsExportConfig());
    }

    public function actionExportFollowUpReportsPdf()
    {
        $searchModel = new FollowUpSearch();
        $dataProvider = $searchModel->searchReport(Yii::$app->request->queryParams);
        return $this->exportData($dataProvider, $this->_followUpReportsExportConfig(), 'pdf');
    }

    private function _judActionsExportConfig()
    {
        return [
            'title'    => 'الحركات القضائية للعملاء',
            'filename' => 'customers_judiciary_actions',
            'headers'  => ['#', 'رقم العقد', 'رقم العميل', 'اسم العميل', 'المحكمة', 'رقم القضية', 'الإجراء القضائي'],
            'keys'     => ['#', 'contract_id', 'customer_id', 'customer_name', 'court_name', 'judiciary_id', 'judiciary_actions_name'],
            'widths'   => [6, 14, 14, 22, 18, 14, 22],
        ];
    }

    public function actionExportJudActionsExcel()
    {
        $searchModel = new CustomersJudiciaryActionsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->exportData($dataProvider, $this->_judActionsExportConfig());
    }

    public function actionExportJudActionsPdf()
    {
        $searchModel = new CustomersJudiciaryActionsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->exportData($dataProvider, $this->_judActionsExportConfig(), 'pdf');
    }

    private function _judReportExportConfig()
    {
        return [
            'title'    => 'التقرير القضائي',
            'filename' => 'judiciary_report',
            'headers'  => ['#', 'رقم العقد', 'المحكمة', 'رقم القضية', 'أتعاب المحامي', 'اسم العميل', 'الإجراء', 'تاريخ الإجراء'],
            'keys'     => ['#', 'contract_id', 'court_name', 'judiciary_number', 'lawyer_cost', 'customer_name', 'action_name', 'customer_date'],
            'widths'   => [6, 14, 18, 14, 16, 22, 20, 16],
        ];
    }

    public function actionExportJudReportExcel()
    {
        $searchModel = new JudiciarySearch();
        $result = $searchModel->report();
        if (is_array($result) && isset($result['dataProvider'])) {
            return $this->exportData($result['dataProvider'], $this->_judReportExportConfig());
        }
        return $this->exportData($result, $this->_judReportExportConfig());
    }

    public function actionExportJudReportPdf()
    {
        $searchModel = new JudiciarySearch();
        $result = $searchModel->report();
        if (is_array($result) && isset($result['dataProvider'])) {
            return $this->exportData($result['dataProvider'], $this->_judReportExportConfig(), 'pdf');
        }
        return $this->exportData($result, $this->_judReportExportConfig(), 'pdf');
    }

    private function _judIndexExportConfig()
    {
        return [
            'title'    => 'القضايا',
            'filename' => 'judiciary_index',
            'headers'  => ['#', 'رقم العقد', 'المحكمة', 'رقم القضية', 'أتعاب المحامي', 'المحامي', 'اسم العميل'],
            'keys'     => [
                '#',
                'contract_id',
                'court.name',
                function ($model) { return $model->judiciary_number . '/' . $model->year; },
                'lawyer_cost',
                'lawyer.name',
                function ($model) {
                    $names = [];
                    if (!empty($model->customersAndGuarantor)) {
                        foreach ($model->customersAndGuarantor as $c) {
                            $names[] = $c->name;
                        }
                    }
                    return implode(', ', $names);
                },
            ],
            'widths'   => [6, 14, 18, 14, 16, 18, 30],
        ];
    }

    public function actionExportJudIndexExcel()
    {
        $searchModel = new JudiciarySearch();
        $search = $searchModel->reportSearch(Yii::$app->request->queryParams);
        $dataProvider = is_array($search) ? $search['dataProvider'] : $search;
        return $this->exportData($dataProvider, $this->_judIndexExportConfig());
    }

    public function actionExportJudIndexPdf()
    {
        $searchModel = new JudiciarySearch();
        $search = $searchModel->reportSearch(Yii::$app->request->queryParams);
        $dataProvider = is_array($search) ? $search['dataProvider'] : $search;
        return $this->exportData($dataProvider, $this->_judIndexExportConfig(), 'pdf');
    }
}
