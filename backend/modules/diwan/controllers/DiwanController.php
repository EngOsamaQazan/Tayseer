<?php

namespace backend\modules\diwan\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use common\helper\Permissions;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use yii\data\SqlDataProvider;
use backend\modules\diwan\models\DiwanTransaction;
use backend\modules\diwan\models\DiwanTransactionDetail;
use backend\modules\diwan\models\DiwanDocumentTracker;
use backend\modules\diwan\models\DiwanTransactionSearch;
use common\models\User;

/**
 * DiwanController — قسم الديوان
 */
class DiwanController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    /* ═══ عرض ═══ */
                    [
                        'actions' => ['index', 'view', 'transactions', 'receipt', 'search', 'document-history', 'quick-search', 'reports'],
                        'allow' => true,
                        'roles' => [Permissions::DIWAN_VIEW],
                    ],
                    /* ═══ إضافة ═══ */
                    [
                        'actions' => ['create'],
                        'allow' => true,
                        'roles' => [Permissions::DIWAN_CREATE],
                    ],
                    /* ═══ حذف ═══ */
                    [
                        'actions' => ['delete'],
                        'allow' => true,
                        'roles' => [Permissions::DIWAN_DELETE],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /* ═══════════════════════════════════════════════════════════════
     *  لوحة المعلومات
     * ═══════════════════════════════════════════════════════════════ */
    public function actionIndex()
    {
        $db = Yii::$app->db;

        /* إحصائيات عامة */
        $totalDocuments = (int)$db->createCommand('SELECT COUNT(*) FROM os_diwan_document_tracker')->queryScalar();
        $totalTransactions = (int)$db->createCommand('SELECT COUNT(*) FROM os_diwan_transactions')->queryScalar();

        $todayStart = date('Y-m-d 00:00:00');
        $todayTransactions = (int)$db->createCommand(
            'SELECT COUNT(*) FROM os_diwan_transactions WHERE transaction_date >= :today',
            [':today' => $todayStart]
        )->queryScalar();

        $todayReceive = (int)$db->createCommand(
            "SELECT COUNT(*) FROM os_diwan_transactions WHERE transaction_date >= :today AND transaction_type = 'استلام'",
            [':today' => $todayStart]
        )->queryScalar();

        $todayDeliver = (int)$db->createCommand(
            "SELECT COUNT(*) FROM os_diwan_transactions WHERE transaction_date >= :today AND transaction_type = 'تسليم'",
            [':today' => $todayStart]
        )->queryScalar();

        /* آخر 10 معاملات */
        $recentTransactions = DiwanTransaction::find()
            ->with(['fromEmployee', 'toEmployee', 'details'])
            ->orderBy(['id' => SORT_DESC])
            ->limit(10)
            ->all();

        return $this->render('index', [
            'totalDocuments' => $totalDocuments,
            'totalTransactions' => $totalTransactions,
            'todayTransactions' => $todayTransactions,
            'todayReceive' => $todayReceive,
            'todayDeliver' => $todayDeliver,
            'recentTransactions' => $recentTransactions,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════════
     *  إنشاء معاملة جديدة
     * ═══════════════════════════════════════════════════════════════ */
    public function actionCreate()
    {
        $model = new DiwanTransaction();
        $model->transaction_date = date('Y-m-d H:i:s');

        if ($model->load(Yii::$app->request->post())) {
            /* تصحيح صيغة التاريخ من datetime-local (T separator) */
            if (!empty($model->transaction_date) && is_string($model->transaction_date) && strpos($model->transaction_date, 'T') !== false) {
                $model->transaction_date = str_replace('T', ' ', $model->transaction_date) . ':00';
            }

            $contractNumbers = Yii::$app->request->post('contract_numbers', '');

            /* تنظيف أرقام العقود */
            $numbers = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $contractNumbers)));

            if (empty($numbers)) {
                Yii::$app->session->setFlash('error', 'يجب إدخال رقم عقد واحد على الأقل.');
                return $this->render('create', [
                    'model' => $model,
                    'employees' => $this->getEmployeeList(),
                ]);
            }

            /* توليد رقم الإيصال */
            $model->generateReceiptNumber();

            $transaction = Yii::$app->db->beginTransaction();
            try {
                /* تعطيل STRICT_MODE مؤقتاً لتجنب مشاكل ترميز العربية */
                Yii::$app->db->createCommand("SET SESSION sql_mode=''")->execute();

                if (!$model->save(false)) {
                    throw new \Exception('فشل حفظ المعاملة: ' . implode(', ', $model->getFirstErrors()));
                }

                /* حفظ تفاصيل العقود */
                foreach ($numbers as $num) {
                    $detail = new DiwanTransactionDetail();
                    $detail->transaction_id = $model->id;
                    $detail->contract_number = $num;
                    $detail->created_at = time();

                    /* محاولة ربط مع جدول العقود */
                    $contractId = Yii::$app->db->createCommand(
                        'SELECT id FROM os_contracts WHERE id = :num LIMIT 1',
                        [':num' => $num]
                    )->queryScalar();
                    if ($contractId) {
                        $detail->contract_id = (int)$contractId;
                    }

                    if (!$detail->save(false)) {
                        throw new \Exception('فشل حفظ تفصيل العقد: ' . $num);
                    }

                    /* تحديث حامل الوثيقة */
                    DiwanDocumentTracker::updateHolder($num, $model->to_employee_id, $model->id);
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم إنشاء المعاملة بنجاح — إيصال رقم: ' . $model->receipt_number);
                return $this->redirect(['receipt', 'id' => $model->id]);

            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('create', [
            'model' => $model,
            'employees' => $this->getEmployeeList(),
        ]);
    }

    /* ═══════════════════════════════════════════════════════════════
     *  قائمة المعاملات
     * ═══════════════════════════════════════════════════════════════ */
    public function actionTransactions()
    {
        $searchModel = new DiwanTransactionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('transactions', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'employees' => $this->getEmployeeList(),
        ]);
    }

    /* ═══════════════════════════════════════════════════════════════
     *  عرض تفاصيل معاملة
     * ═══════════════════════════════════════════════════════════════ */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════════
     *  إيصال المعاملة (للطباعة)
     * ═══════════════════════════════════════════════════════════════ */
    public function actionReceipt($id)
    {
        $model = $this->findModel($id);
        return $this->render('receipt', [
            'model' => $model,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════════
     *  بحث الوثائق
     * ═══════════════════════════════════════════════════════════════ */
    public function actionSearch()
    {
        $query = Yii::$app->request->get('q', '');
        $results = [];

        if (!empty($query)) {
            $results = DiwanDocumentTracker::find()
                ->with(['currentHolder', 'lastTransaction'])
                ->andWhere(['like', 'contract_number', $query])
                ->orderBy(['updated_at' => SORT_DESC])
                ->limit(50)
                ->all();
        }

        /* جميع الوثائق المتتبعة */
        $allDocuments = new ActiveDataProvider([
            'query' => DiwanDocumentTracker::find()
                ->with(['currentHolder', 'lastTransaction'])
                ->orderBy(['updated_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('search', [
            'query' => $query,
            'results' => $results,
            'allDocuments' => $allDocuments,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════════
     *  سجل وثيقة معينة
     * ═══════════════════════════════════════════════════════════════ */
    public function actionDocumentHistory($contract_number)
    {
        $tracker = DiwanDocumentTracker::findOne(['contract_number' => $contract_number]);

        $history = DiwanTransactionDetail::find()
            ->alias('d')
            ->innerJoinWith('transaction t')
            ->where(['d.contract_number' => $contract_number])
            ->orderBy(['t.transaction_date' => SORT_DESC])
            ->all();

        return $this->render('document_history', [
            'contractNumber' => $contract_number,
            'tracker' => $tracker,
            'history' => $history,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════════
     *  التقارير
     * ═══════════════════════════════════════════════════════════════ */
    public function actionReports()
    {
        $period = Yii::$app->request->get('period', 'today');
        $db = Yii::$app->db;

        switch ($period) {
            case 'week':
                $dateFrom = date('Y-m-d 00:00:00', strtotime('-7 days'));
                break;
            case 'month':
                $dateFrom = date('Y-m-01 00:00:00');
                break;
            case 'all':
                $dateFrom = '2000-01-01 00:00:00';
                break;
            default: // today
                $dateFrom = date('Y-m-d 00:00:00');
                break;
        }

        $stats = $db->createCommand("
            SELECT
                COUNT(*) as total_transactions,
                SUM(CASE WHEN transaction_type = 'استلام' THEN 1 ELSE 0 END) as total_receive,
                SUM(CASE WHEN transaction_type = 'تسليم' THEN 1 ELSE 0 END) as total_deliver
            FROM os_diwan_transactions
            WHERE transaction_date >= :dateFrom
        ", [':dateFrom' => $dateFrom])->queryOne();

        $totalContracts = (int)$db->createCommand("
            SELECT COUNT(DISTINCT d.contract_number)
            FROM os_diwan_transaction_details d
            INNER JOIN os_diwan_transactions t ON t.id = d.transaction_id
            WHERE t.transaction_date >= :dateFrom
        ", [':dateFrom' => $dateFrom])->queryScalar();

        /* إحصائيات الموظفين */
        $employeeStats = $db->createCommand("
            SELECT
                u.username as employee_name,
                COUNT(CASE WHEN t.transaction_type = 'استلام' AND t.to_employee_id = u.id THEN 1 END) as received,
                COUNT(CASE WHEN t.transaction_type = 'تسليم' AND t.from_employee_id = u.id THEN 1 END) as delivered
            FROM os_user u
            INNER JOIN (
                SELECT from_employee_id as uid FROM os_diwan_transactions WHERE transaction_date >= :dateFrom
                UNION
                SELECT to_employee_id as uid FROM os_diwan_transactions WHERE transaction_date >= :dateFrom
            ) active ON active.uid = u.id
            LEFT JOIN os_diwan_transactions t ON (t.from_employee_id = u.id OR t.to_employee_id = u.id)
                AND t.transaction_date >= :dateFrom
            GROUP BY u.id, u.username
            ORDER BY (COUNT(CASE WHEN t.transaction_type = 'استلام' AND t.to_employee_id = u.id THEN 1 END) + COUNT(CASE WHEN t.transaction_type = 'تسليم' AND t.from_employee_id = u.id THEN 1 END)) DESC
        ", [':dateFrom' => $dateFrom])->queryAll();

        /* آخر المعاملات في الفترة */
        $transactions = DiwanTransaction::find()
            ->with(['fromEmployee', 'toEmployee', 'details'])
            ->where(['>=', 'transaction_date', $dateFrom])
            ->orderBy(['transaction_date' => SORT_DESC])
            ->limit(50)
            ->all();

        return $this->render('reports', [
            'period' => $period,
            'stats' => $stats,
            'totalContracts' => $totalContracts,
            'employeeStats' => $employeeStats,
            'transactions' => $transactions,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════════
     *  حذف معاملة
     * ═══════════════════════════════════════════════════════════════ */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->delete();
        Yii::$app->session->setFlash('success', 'تم حذف المعاملة بنجاح.');
        return $this->redirect(['transactions']);
    }

    /* ═══════════════════════════════════════════════════════════════
     *  AJAX — البحث السريع عن وثيقة
     * ═══════════════════════════════════════════════════════════════ */
    public function actionQuickSearch()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $q = Yii::$app->request->get('q');
        if ($q === null || $q === '') {
            return ['results' => []];
        }

        $trackers = DiwanDocumentTracker::find()
            ->with(['currentHolder'])
            ->andWhere(['like', 'contract_number', $q])
            ->limit(10)
            ->all();

        $results = [];
        foreach ($trackers as $t) {
            $results[] = [
                'contract_number' => $t->contract_number,
                'holder' => $t->currentHolder ? ($t->currentHolder->name ?: $t->currentHolder->username) : '—',
                'status' => $t->status,
            ];
        }

        return ['results' => $results];
    }

    /* ═══ مساعدة ═══ */

    protected function findModel($id)
    {
        $model = DiwanTransaction::find()
            ->with(['fromEmployee', 'toEmployee', 'createdByUser', 'details'])
            ->where(['id' => $id])
            ->one();

        if ($model !== null) {
            return $model;
        }
        throw new NotFoundHttpException('المعاملة المطلوبة غير موجودة.');
    }

    protected function getEmployeeList()
    {
        return ArrayHelper::map(
            User::find()
                ->where(['IS', 'blocked_at', null])
                ->orderBy(['username' => SORT_ASC])
                ->asArray()
                ->all(),
            'id',
            'username'
        );
    }
}
