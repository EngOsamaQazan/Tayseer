<?php

namespace backend\controllers;

use common\models\Expenses;
use common\models\SystemSettings;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;
use common\models\LoginForm;
use backend\modules\contracts\models\Contracts;
use yii\data\ActiveDataProvider;
use backend\modules\customers\models\Customers;
use common\models\Income;
use yii\db\Query;
use common\helper\Permissions;

/**
 * Site controller — Dashboard + Auth
 */
class SiteController extends Controller
{

    public function beforeAction($action)
    {
        if (in_array($action->id, ['test-google-connection', 'test-maps-connection', 'test-sms-connection', 'test-whatsapp-connection'])) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error', 'import'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['system-settings', 'test-google-connection', 'test-maps-connection', 'test-sms-connection', 'test-whatsapp-connection', 'server-backup', 'image-manager', 'image-manager-data', 'image-reassign', 'image-manager-stats', 'image-search-customers', 'image-update-doc-type', 'image-delete'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::hasAnyPermission(Permissions::getSettingsPermissions());
                        },
                    ],
                    [
                        'actions' => ['logout', 'index', 'update', 'create', 'delete'],
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

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * لوحة التحكم الرئيسية
     */
    public function actionIndex()
    {
        $companyId = isset(Yii::$app->params['company_id']) ? Yii::$app->params['company_id'] : null;
        $cacheKey = 'dashboard_stats_' . ($companyId ?: 'all') . '_' . date('Y-m');

        $stats = Yii::$app->cache->getOrSet($cacheKey, function () use ($companyId) {
            return $this->buildDashboardStats($companyId);
        }, 300);

        return $this->render('index', $stats);
    }

    private function buildDashboardStats($companyId): array
    {
        $db = Yii::$app->db;

        $contractsByStatus = [];
        $totalContracts = 0;
        $totalContractValue = 0;
        $totalCustomers = 0;
        $monthlyIncome = 0;
        $yearlyIncome = 0;
        $monthlyExpenses = 0;
        $totalCases = 0;
        $totalSettlements = 0;
        $incomeChart = [];
        $recentPayments = [];
        $recentContracts = [];
        $topCollectors = [];

        try {
            $contractStats = $db->createCommand(
                "SELECT status, COUNT(*) as cnt, COALESCE(SUM(total_value),0) as total_value
                 FROM os_contracts WHERE is_deleted=0
                 " . ($companyId ? "AND company_id=:cid" : "") . "
                 GROUP BY status",
                $companyId ? [':cid' => $companyId] : []
            )->queryAll();

            foreach ($contractStats as $row) {
                $contractsByStatus[$row['status']] = (int)$row['cnt'];
                $totalContracts += (int)$row['cnt'];
                $totalContractValue += (float)$row['total_value'];
            }
        } catch (\Exception $e) { Yii::error($e->getMessage()); }

        try {
            $totalCustomers = (int)$db->createCommand("SELECT COUNT(*) FROM os_customers")->queryScalar();
        } catch (\Exception $e) {}

        try {
            $monthlyIncome = (float)$db->createCommand(
                "SELECT COALESCE(SUM(amount),0) FROM os_income WHERE YEAR(`date`)=:y AND MONTH(`date`)=:m",
                [':y' => date('Y'), ':m' => date('m')]
            )->queryScalar();
        } catch (\Exception $e) {}

        try {
            $yearlyIncome = (float)$db->createCommand(
                "SELECT COALESCE(SUM(amount),0) FROM os_income WHERE YEAR(`date`)=:y",
                [':y' => date('Y')]
            )->queryScalar();
        } catch (\Exception $e) {}

        try {
            $monthlyExpenses = (float)$db->createCommand(
                "SELECT COALESCE(SUM(amount),0) FROM os_expenses WHERE YEAR(expenses_date)=:y AND MONTH(expenses_date)=:m",
                [':y' => date('Y'), ':m' => date('m')]
            )->queryScalar();
        } catch (\Exception $e) {}

        try {
            $totalCases = (int)$db->createCommand(
                "SELECT COUNT(*) FROM os_judiciary" . ($companyId ? " WHERE company_id=:cid" : ""),
                $companyId ? [':cid' => $companyId] : []
            )->queryScalar();
        } catch (\Exception $e) {}

        try {
            $totalSettlements = (int)$db->createCommand("SELECT COUNT(*) FROM os_loan_scheduling WHERE is_deleted=0")->queryScalar();
        } catch (\Exception $e) {}

        try {
            $incomeChart = $db->createCommand(
                "SELECT DATE_FORMAT(`date`, '%Y-%m') as month_key, SUM(amount) as total
                 FROM os_income
                 WHERE `date` >= :startDate
                 GROUP BY DATE_FORMAT(`date`, '%Y-%m')
                 ORDER BY month_key ASC",
                [':startDate' => date('Y-m-d', strtotime('-11 months', strtotime(date('Y-m-01'))))]
            )->queryAll();
        } catch (\Exception $e) {}

        try {
            $recentPayments = $db->createCommand(
                "SELECT i.id, i.date, i.amount, i.contract_id, c.name as customer_name
                 FROM os_income i
                 LEFT JOIN os_contracts ct ON ct.id = i.contract_id
                 LEFT JOIN os_contracts_customers cc ON cc.contract_id = ct.id AND cc.customer_type = 'client'
                 LEFT JOIN os_customers c ON c.id = cc.customer_id
                 ORDER BY i.id DESC LIMIT 10"
            )->queryAll();
        } catch (\Exception $e) {}

        try {
            $recentContracts = $db->createCommand(
                "SELECT ct.id, ct.Date_of_sale, ct.total_value, ct.status, ct.monthly_installment_value, c.name as customer_name
                 FROM os_contracts ct
                 LEFT JOIN os_contracts_customers cc ON cc.contract_id = ct.id AND cc.customer_type = 'client'
                 LEFT JOIN os_customers c ON c.id = cc.customer_id
                 WHERE ct.is_deleted=0
                 " . ($companyId ? "AND ct.company_id=:cid" : "") . "
                 ORDER BY ct.id DESC LIMIT 10",
                $companyId ? [':cid' => $companyId] : []
            )->queryAll();
        } catch (\Exception $e) {}

        try {
            $topCollectors = $db->createCommand(
                "SELECT u.id, p.name as emp_name, SUM(i.amount) as collected
                 FROM os_income i
                 LEFT JOIN os_user u ON u.id = i._by
                 LEFT JOIN os_profile p ON p.user_id = u.id
                 WHERE YEAR(i.`date`)=:y AND MONTH(i.`date`)=:m
                 GROUP BY u.id, p.name
                 ORDER BY collected DESC LIMIT 10",
                [':y' => date('Y'), ':m' => date('m')]
            )->queryAll();
        } catch (\Exception $e) {}

        return [
            'totalContracts'    => $totalContracts,
            'contractsByStatus' => $contractsByStatus,
            'totalContractValue'=> $totalContractValue,
            'totalCustomers'    => $totalCustomers,
            'monthlyIncome'     => $monthlyIncome,
            'yearlyIncome'      => $yearlyIncome,
            'monthlyExpenses'   => $monthlyExpenses,
            'totalCases'        => $totalCases,
            'totalSettlements'  => $totalSettlements,
            'incomeChart'       => $incomeChart,
            'recentPayments'    => $recentPayments,
            'recentContracts'   => $recentContracts,
            'topCollectors'     => $topCollectors,
        ];
    }

    /**
     * Login action.
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->redirect(['/site/index']);
        } else {
            $model->password = '';
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logout action.
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    public function actionImport()
    {
        $extension = 'xlsx';
        $filePath = Yii::getAlias('@backend/web/rptAccountStatment.xlsx');

        if ($extension == 'xlsx') {
            $objReader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } elseif ($extension == 'xls') {
            $objReader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        } else {
            $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
        }

        $objPHPExcel = $objReader->load($filePath);
        $objPHPExcel->setActiveSheetIndex(0);
        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

        $sheetDataCount = count($sheetData);
        if ($sheetData < 16) {
        }
        $notImportedData = [];
        for ($i = 16; $i < $sheetDataCount; $i++) {
            $model = new Expenses();
            if (!$model->save()) {
                array_push($notImportedData, $model->attributes);
            }
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  إعدادات النظام — System Settings
    // ═══════════════════════════════════════════════════════════

    /**
     * عرض وتعديل إعدادات النظام
     */
    public function actionSystemSettings()
    {
        // Load all groups
        $googleCloud = SystemSettings::getGroup('google_cloud');
        $flash = null;

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $tab = $post['settings_tab'] ?? 'google_cloud';

            if ($tab === 'google_maps') {
                $newKey = trim($post['gm_api_key'] ?? '');
                $existingKey = SystemSettings::get('google_maps', 'api_key', '');
                $keyToSave = $newKey !== '' ? $newKey : $existingKey;
                SystemSettings::set('google_maps', 'api_key', $keyToSave, true, 'Google Maps API Key');
                Yii::$app->session->setFlash('success', $keyToSave !== '' ? 'تم حفظ مفتاح Google Maps بنجاح' : 'تم مسح مفتاح Google Maps');
                return $this->redirect(['system-settings', 'tab' => 'google_apis']);
            }

            if ($tab === 'google_cloud') {
                $data = [
                    'enabled'        => $post['gc_enabled'] ?? '0',
                    'project_id'     => trim($post['gc_project_id'] ?? ''),
                    'client_email'   => trim($post['gc_client_email'] ?? ''),
                    'private_key'    => trim($post['gc_private_key'] ?? ''),
                    'monthly_limit'  => trim($post['gc_monthly_limit'] ?? '1000'),
                    'cost_per_request'=> trim($post['gc_cost_per_request'] ?? '0.0015'),
                ];

                if ($data['private_key'] === '' || $data['private_key'] === '••••••••••') {
                    $data['private_key'] = SystemSettings::get('google_cloud', 'private_key', '');
                }

                if (!empty($data['project_id']) && !empty($data['client_email']) && !empty($data['private_key'])) {
                    $data['enabled'] = '1';
                }

                $encryptedKeys = ['private_key'];
                $saved = SystemSettings::setGroup('google_cloud', $data, $encryptedKeys);

                if ($saved) {
                    Yii::$app->session->setFlash('success', 'تم حفظ إعدادات Google Cloud بنجاح');
                } else {
                    Yii::$app->session->setFlash('error', 'حدث خطأ أثناء حفظ الإعدادات');
                }

                return $this->redirect(['system-settings', 'tab' => 'google_apis']);
            }

            // ── SMS API Settings ──
            if ($tab === 'sms_api') {
                $data = [
                    'enabled'      => $post['sms_enabled'] ?? '0',
                    'provider'     => trim($post['sms_provider'] ?? ''),
                    'api_url'      => trim($post['sms_api_url'] ?? ''),
                    'api_key'      => trim($post['sms_api_key'] ?? ''),
                    'api_secret'   => trim($post['sms_api_secret'] ?? ''),
                    'sender_id'    => trim($post['sms_sender_id'] ?? ''),
                    'username'     => trim($post['sms_username'] ?? ''),
                    'password'     => trim($post['sms_password'] ?? ''),
                ];

                if ($data['api_key'] === '' || $data['api_key'] === '••••••••••') {
                    $data['api_key'] = SystemSettings::get('sms_api', 'api_key', '');
                }
                if ($data['api_secret'] === '' || $data['api_secret'] === '••••••••••') {
                    $data['api_secret'] = SystemSettings::get('sms_api', 'api_secret', '');
                }
                if ($data['password'] === '' || $data['password'] === '••••••••••') {
                    $data['password'] = SystemSettings::get('sms_api', 'password', '');
                }

                $encryptedKeys = ['api_key', 'api_secret', 'password'];
                $saved = SystemSettings::setGroup('sms_api', $data, $encryptedKeys);

                Yii::$app->session->setFlash($saved ? 'success' : 'error',
                    $saved ? 'تم حفظ إعدادات SMS API بنجاح' : 'حدث خطأ أثناء حفظ الإعدادات');
                return $this->redirect(['system-settings', 'tab' => 'messaging']);
            }

            // ── WhatsApp Business API Settings ──
            if ($tab === 'whatsapp_api') {
                $data = [
                    'enabled'            => $post['wa_enabled'] ?? '0',
                    'phone_number_id'    => trim($post['wa_phone_number_id'] ?? ''),
                    'waba_id'            => trim($post['wa_waba_id'] ?? ''),
                    'access_token'       => trim($post['wa_access_token'] ?? ''),
                    'app_id'             => trim($post['wa_app_id'] ?? ''),
                    'app_secret'         => trim($post['wa_app_secret'] ?? ''),
                    'webhook_verify_token' => trim($post['wa_webhook_verify_token'] ?? ''),
                    'api_version'        => trim($post['wa_api_version'] ?? 'v21.0'),
                ];

                if ($data['access_token'] === '' || $data['access_token'] === '••••••••••') {
                    $data['access_token'] = SystemSettings::get('whatsapp_api', 'access_token', '');
                }
                if ($data['app_secret'] === '' || $data['app_secret'] === '••••••••••') {
                    $data['app_secret'] = SystemSettings::get('whatsapp_api', 'app_secret', '');
                }

                $encryptedKeys = ['access_token', 'app_secret'];
                $saved = SystemSettings::setGroup('whatsapp_api', $data, $encryptedKeys);

                Yii::$app->session->setFlash($saved ? 'success' : 'error',
                    $saved ? 'تم حفظ إعدادات WhatsApp Business API بنجاح' : 'حدث خطأ أثناء حفظ الإعدادات');
                return $this->redirect(['system-settings', 'tab' => 'messaging']);
            }
        }

        // Mask the private key for display
        $displaySettings = $googleCloud;
        if (!empty($displaySettings['private_key'])) {
            $displaySettings['private_key_masked'] = '••••••••••';
            $displaySettings['has_private_key'] = true;
        } else {
            $displaySettings['private_key_masked'] = '';
            $displaySettings['has_private_key'] = false;
        }

        // Get API usage stats
        $usageStats = $this->getApiUsageStats();

        // Google Maps
        $googleMapsKey = SystemSettings::get('google_maps', 'api_key', '');
        $googleMaps = [
            'configured' => $googleMapsKey !== '',
        ];

        // SMS API settings
        $smsSettings = SystemSettings::getGroup('sms_api');
        $smsDisplay = $smsSettings;
        foreach (['api_key', 'api_secret', 'password'] as $sensitiveKey) {
            if (!empty($smsDisplay[$sensitiveKey])) {
                $smsDisplay['has_' . $sensitiveKey] = true;
                $smsDisplay[$sensitiveKey . '_masked'] = '••••••••••';
            } else {
                $smsDisplay['has_' . $sensitiveKey] = false;
                $smsDisplay[$sensitiveKey . '_masked'] = '';
            }
        }

        // WhatsApp Business API settings
        $waSettings = SystemSettings::getGroup('whatsapp_api');
        $waDisplay = $waSettings;
        foreach (['access_token', 'app_secret'] as $sensitiveKey) {
            if (!empty($waDisplay[$sensitiveKey])) {
                $waDisplay['has_' . $sensitiveKey] = true;
                $waDisplay[$sensitiveKey . '_masked'] = '••••••••••';
            } else {
                $waDisplay['has_' . $sensitiveKey] = false;
                $waDisplay[$sensitiveKey . '_masked'] = '';
            }
        }

        return $this->render('system-settings', [
            'googleCloud'  => $displaySettings,
            'googleMaps'   => $googleMaps,
            'usageStats'   => $usageStats,
            'smsSettings'  => $smsDisplay,
            'waSettings'   => $waDisplay,
            'activeTab'    => Yii::$app->request->get('tab', 'general'),
        ]);
    }

    /**
     * تحميل نسخة احتياطية من قاعدة البيانات (SQL dump مضغوط)
     */
    public function actionServerBackup()
    {
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        $db = Yii::$app->db;
        $dsn = $db->dsn;

        preg_match('/host=([^;]+)/', $dsn, $hostMatch);
        preg_match('/dbname=([^;]+)/', $dsn, $dbMatch);
        $host = $hostMatch[1] ?? 'localhost';
        $dbName = $dbMatch[1] ?? 'tayseer';
        $username = $db->username;
        $password = $db->password;

        $timestamp = date('Y-m-d_H-i-s');
        $backupDir = Yii::getAlias('@runtime/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $sqlFile = $backupDir . "/db_{$dbName}_{$timestamp}.sql";
        $gzFile = $sqlFile . '.gz';

        $cmd = sprintf(
            'mysqldump --single-transaction --quick --routines --triggers -h%s -u%s -p%s %s 2>/dev/null | gzip > %s',
            escapeshellarg($host),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($dbName),
            escapeshellarg($gzFile)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($gzFile) || filesize($gzFile) < 100) {
            @unlink($gzFile);
            Yii::$app->session->setFlash('error', 'فشل إنشاء النسخة الاحتياطية — تحقق من صلاحيات mysqldump');
            return $this->redirect(['system-settings', 'tab' => 'backup']);
        }

        $downloadName = "backup_{$dbName}_{$timestamp}.sql.gz";

        return Yii::$app->response->sendFile($gzFile, $downloadName)
            ->on(\yii\web\Response::EVENT_AFTER_SEND, function () use ($gzFile) {
                @unlink($gzFile);
            });
    }

    /**
     * اختبار اتصال Google Cloud (AJAX)
     * يقبل بيانات الاعتماد من الفورم ويحفظها تلقائياً قبل الاختبار
     */
    public function actionTestGoogleConnection()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isAjax) {
            return ['success' => false, 'error' => 'طلب غير صالح'];
        }

        $post = Yii::$app->request->post();
        $projectId   = trim($post['gc_project_id'] ?? '');
        $clientEmail = trim($post['gc_client_email'] ?? '');
        $privateKey  = trim($post['gc_private_key'] ?? '');

        if ($privateKey === '' || $privateKey === '••••••••••') {
            $privateKey = SystemSettings::get('google_cloud', 'private_key', '');
        }
        if ($projectId === '') {
            $projectId = SystemSettings::get('google_cloud', 'project_id', '');
        }
        if ($clientEmail === '') {
            $clientEmail = SystemSettings::get('google_cloud', 'client_email', '');
        }

        $creds = [
            'project_id'   => $projectId,
            'client_email' => $clientEmail,
            'private_key'  => $privateKey,
        ];

        $result = SystemSettings::testGoogleConnection($creds);

        if ($result['success']) {
            $data = [
                'enabled'         => '1',
                'project_id'      => $projectId,
                'client_email'    => $clientEmail,
                'private_key'     => $privateKey,
                'monthly_limit'   => trim($post['gc_monthly_limit'] ?? '') ?: SystemSettings::get('google_cloud', 'monthly_limit', '1000'),
                'cost_per_request' => trim($post['gc_cost_per_request'] ?? '') ?: SystemSettings::get('google_cloud', 'cost_per_request', '0.0015'),
            ];
            SystemSettings::setGroup('google_cloud', $data, ['private_key']);
            $result['saved'] = true;
        }

        return $result;
    }

    /**
     * اختبار مفتاح Google Maps API (AJAX)
     */
    public function actionTestMapsConnection()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isAjax) {
            return ['success' => false, 'error' => 'طلب غير صالح'];
        }

        $apiKey = SystemSettings::get('google_maps', 'api_key', '');
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'لم يتم إعداد مفتاح Google Maps API بعد'];
        }

        $testUrl = 'https://maps.googleapis.com/maps/api/geocode/json?'
            . http_build_query(['address' => 'Amman, Jordan', 'key' => $apiKey]);

        $ch = curl_init($testUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => !YII_DEBUG,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($curlError) {
            return ['success' => false, 'error' => "خطأ في الاتصال: {$curlError}"];
        }

        $data = json_decode($response, true);
        if (!$data) {
            return ['success' => false, 'error' => 'لم يتم استلام رد صالح من Google'];
        }

        $status = $data['status'] ?? 'UNKNOWN';

        if ($status === 'OK') {
            $firstResult = $data['results'][0]['formatted_address'] ?? '';
            return [
                'success' => true,
                'message' => "المفتاح يعمل — تم اختبار Geocoding API بنجاح" . ($firstResult ? " ({$firstResult})" : ''),
            ];
        }

        $errorMessages = [
            'REQUEST_DENIED'     => 'المفتاح مرفوض — تأكد من تفعيل Geocoding API و Maps JavaScript API في مشروعك',
            'OVER_QUERY_LIMIT'   => 'تم تجاوز حد الاستخدام — تحقق من حساب الفوترة',
            'INVALID_REQUEST'    => 'طلب غير صالح — قد يكون المفتاح غير صحيح',
            'OVER_DAILY_LIMIT'   => 'تم تجاوز الحد اليومي — تحقق من إعدادات الفوترة',
        ];

        $errorMsg = $errorMessages[$status] ?? "رد غير متوقع من Google: {$status}";
        if (!empty($data['error_message'])) {
            $errorMsg .= " — " . $data['error_message'];
        }

        return ['success' => false, 'error' => $errorMsg];
    }

    /**
     * اختبار اتصال SMS API (AJAX)
     */
    public function actionTestSmsConnection()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isAjax) {
            return ['success' => false, 'error' => 'طلب غير صالح'];
        }

        $provider = SystemSettings::get('sms_api', 'provider', '');
        $apiUrl   = SystemSettings::get('sms_api', 'api_url', '');
        $apiKey   = SystemSettings::get('sms_api', 'api_key', '');
        $username = SystemSettings::get('sms_api', 'username', '');

        if (empty($apiUrl) && empty($apiKey)) {
            return ['success' => false, 'error' => 'لم يتم إعداد بيانات SMS API بعد — أدخل رابط API والمفتاح أولاً'];
        }

        try {
            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_SSL_VERIFYPEER => !YII_DEBUG,
                CURLOPT_NOBODY         => true,
            ]);
            curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return ['success' => false, 'error' => "خطأ في الاتصال بالسيرفر: {$curlError}"];
            }

            if ($httpCode >= 200 && $httpCode < 500) {
                return [
                    'success' => true,
                    'message' => "السيرفر يستجيب (HTTP {$httpCode}) — المزوّد: " . ($provider ?: 'غير محدد'),
                ];
            }

            return ['success' => false, 'error' => "السيرفر أرجع كود خطأ: HTTP {$httpCode}"];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * اختبار اتصال WhatsApp Business API (AJAX)
     */
    public function actionTestWhatsappConnection()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isAjax) {
            return ['success' => false, 'error' => 'طلب غير صالح'];
        }

        $phoneNumberId = SystemSettings::get('whatsapp_api', 'phone_number_id', '');
        $accessToken   = SystemSettings::get('whatsapp_api', 'access_token', '');
        $apiVersion    = SystemSettings::get('whatsapp_api', 'api_version', 'v21.0');

        if (empty($accessToken) || empty($phoneNumberId)) {
            return ['success' => false, 'error' => 'لم يتم إعداد Access Token أو Phone Number ID بعد'];
        }

        try {
            $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}";
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_SSL_VERIFYPEER => !YII_DEBUG,
                CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$accessToken}"],
            ]);
            $response  = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return ['success' => false, 'error' => "خطأ في الاتصال: {$curlError}"];
            }

            $data = json_decode($response, true);

            if ($httpCode === 200 && !empty($data['id'])) {
                $phoneName = $data['verified_name'] ?? $data['display_phone_number'] ?? $data['id'];
                return [
                    'success' => true,
                    'message' => "الاتصال ناجح! الرقم: {$phoneName}",
                ];
            }

            $errorMsg = $data['error']['message'] ?? "HTTP {$httpCode}";
            $errorCode = $data['error']['code'] ?? '';
            $hint = '';
            if ($errorCode == 190) {
                $hint = ' — Access Token منتهي الصلاحية أو غير صالح';
            } elseif ($errorCode == 100) {
                $hint = ' — Phone Number ID غير صحيح';
            }

            return ['success' => false, 'error' => "فشل: {$errorMsg}{$hint}"];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get API usage statistics for current month
     */
    private function getApiUsageStats(): array
    {
        $month = date('Y-m');
        $monthlyLimit = (int) SystemSettings::get('google_cloud', 'monthly_limit', 1000);
        $costPerReq = (float) SystemSettings::get('google_cloud', 'cost_per_request', 0.0015);

        // Check if table exists, handle gracefully
        try {
            $stats = (new Query())
                ->select([
                    'total_requests' => 'COUNT(*)',
                    'success_count'  => "SUM(CASE WHEN request_status = 'success' THEN 1 ELSE 0 END)",
                    'fail_count'     => "SUM(CASE WHEN request_status = 'error' THEN 1 ELSE 0 END)",
                    'total_cost'     => 'COALESCE(SUM(cost_estimate), 0)',
                ])
                ->from('{{%vision_api_usage}}')
                ->where(['>=', 'created_at', $month . '-01 00:00:00'])
                ->one();
        } catch (\Exception $e) {
            $stats = [
                'total_requests' => 0,
                'success_count'  => 0,
                'fail_count'     => 0,
                'total_cost'     => 0,
            ];
        }

        $stats['monthly_limit'] = $monthlyLimit;
        $stats['cost_per_request'] = $costPerReq;
        $stats['remaining'] = max(0, $monthlyLimit - (int)$stats['total_requests']);
        $stats['usage_percent'] = $monthlyLimit > 0
            ? round(((int)$stats['total_requests'] / $monthlyLimit) * 100, 1)
            : 0;

        return $stats;
    }

    // ═══════════════════════════════════════════════════════════
    //  إدارة الصور — مراجعة وتصحيح ربط الصور بالعملاء
    // ═══════════════════════════════════════════════════════════

    /**
     * صفحة إدارة الصور الرئيسية
     */
    public function actionImageManager()
    {
        return $this->render('image-manager');
    }

    /**
     * جلب بيانات الصور (AJAX مع pagination)
     */
    public function actionImageManagerData()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $page     = (int) Yii::$app->request->get('page', 1);
        $perPage  = (int) Yii::$app->request->get('per_page', 50);
        $filter   = Yii::$app->request->get('filter', 'all'); // all, orphans, customers, contracts, smart_media, unlinked, missing
        $search   = trim(Yii::$app->request->get('search', ''));
        $dateFrom = Yii::$app->request->get('date_from', '');
        $dateTo   = Yii::$app->request->get('date_to', '');
        $offset   = ($page - 1) * $perPage;

        $db = Yii::$app->db;

        // ── مجلدات التخزين ──
        $imagesDir       = Yii::getAlias('@backend/web/images/imagemanager');
        $uploadsDir      = Yii::getAlias('@backend/web/uploads/customers/documents');
        $uploadsPhotosDir = Yii::getAlias('@backend/web/uploads/customers/photos');

        // ──────────────────────────────────────────────────────────
        //  استعلام موحّد: UNION بين os_ImageManager و os_customer_documents
        //  يُجمع كل الصور من النظامين في استعلام واحد مرتب بالتاريخ
        // ──────────────────────────────────────────────────────────

        // ── فلتر os_ImageManager ──
        $imWhere = "WHERE 1=1";
        $imParams = [];

        if ($filter === 'orphans') {
            $imWhere .= " AND im.groupName IN ('coustmers','customers','0','1','2','3','4','5','6','7','8','9') AND c.id IS NULL";
        } elseif ($filter === 'customers') {
            $imWhere .= " AND im.groupName != 'contracts' AND c.id IS NOT NULL";
        } elseif ($filter === 'contracts') {
            $imWhere .= " AND im.groupName = 'contracts'";
        } elseif ($filter === 'smart_media') {
            $imWhere .= " AND 1=0"; // skip ImageManager
        } elseif ($filter === 'unlinked') {
            // صور بدون ربط بعميل
            $imWhere .= " AND c.id IS NULL AND (im.customer_id IS NULL OR im.customer_id = 0) AND (im.contractId IS NULL OR im.contractId = '' OR im.contractId = '0')";
        } elseif ($filter === 'no_customer') {
            // كل صورة غير مرتبطة بعميل فعلي (يتيمة + بدون ربط)
            $imWhere .= " AND im.groupName != 'contracts' AND c.id IS NULL";
        } elseif ($filter === 'missing') {
            // يتم الفلترة لاحقاً بعد التحقق من الملفات — جلب الكل
        }

        if ($search !== '') {
            $imWhere .= " AND (c.name LIKE :search OR im.contractId LIKE :searchId OR im.customer_id = :custId OR im.id = :exactId)";
            $imParams[':search'] = "%$search%";
            $imParams[':searchId'] = "%$search%";
            $imParams[':custId'] = is_numeric($search) ? (int)$search : 0;
            $imParams[':exactId'] = $search;
        }
        if ($dateFrom !== '') {
            $imWhere .= " AND DATE(im.created) >= :dateFrom";
            $imParams[':dateFrom'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $imWhere .= " AND DATE(im.created) <= :dateTo";
            $imParams[':dateTo'] = $dateTo;
        }

        // ── فلتر os_customer_documents ──
        $cdWhere = "WHERE 1=1";
        $cdParams = [];
        $includeSmartMedia = true;

        if ($filter === 'orphans' || $filter === 'contracts' || $filter === 'unlinked' || $filter === 'no_customer') {
            $includeSmartMedia = false; // Smart Media دائماً مرتبطة بعميل
        }

        if ($search !== '' && $includeSmartMedia) {
            $cdWhere .= " AND (c2.name LIKE :cd_search OR cd.customer_id = :cd_exactId)";
            $cdParams[':cd_search'] = "%$search%";
            $cdParams[':cd_exactId'] = $search;
        }
        if ($dateFrom !== '' && $includeSmartMedia) {
            $cdWhere .= " AND DATE(cd.created_at) >= :cd_dateFrom";
            $cdParams[':cd_dateFrom'] = $dateFrom;
        }
        if ($dateTo !== '' && $includeSmartMedia) {
            $cdWhere .= " AND DATE(cd.created_at) <= :cd_dateTo";
            $cdParams[':cd_dateTo'] = $dateTo;
        }

        // ── Count total ──
        $countSql = "SELECT COUNT(*) FROM os_ImageManager im
                     LEFT JOIN os_customers c ON im.customer_id = c.id
                     $imWhere";
        $total = (int) $db->createCommand($countSql, $imParams)->queryScalar();

        if ($includeSmartMedia) {
            try {
                $cdCountSql = "SELECT COUNT(*) FROM os_customer_documents cd
                               LEFT JOIN os_customers c2 ON cd.customer_id = c2.id
                               $cdWhere";
                $total += (int) $db->createCommand($cdCountSql, $cdParams)->queryScalar();
            } catch (\Exception $e) { /* table may not exist */ }
        }

        // ── Get ImageManager images ──
        $sql = "SELECT 
                    im.id, im.fileName, im.fileHash, im.contractId, im.customer_id, im.groupName,
                    im.created, im.modified,
                    c.name AS customer_name, c.id AS real_customer_id,
                    c.selected_image,
                    'imagemanager' AS _source
                FROM os_ImageManager im
                LEFT JOIN os_customers c ON im.customer_id = c.id
                $imWhere
                ORDER BY im.created DESC";

        $imRows = $db->createCommand($sql, $imParams)->queryAll();

        // ── Get Smart Media images ──
        $cdRows = [];
        if ($includeSmartMedia) {
            try {
                $cdSql = "SELECT 
                            cd.id, cd.file_path, cd.customer_id, cd.document_type,
                            cd.created_at AS created,
                            c2.name AS customer_name, c2.id AS real_customer_id,
                            c2.selected_image,
                            'smart_media' AS _source
                          FROM os_customer_documents cd
                          LEFT JOIN os_customers c2 ON cd.customer_id = c2.id
                          $cdWhere
                          ORDER BY cd.created_at DESC";
                $cdRows = $db->createCommand($cdSql, $cdParams)->queryAll();
            } catch (\Exception $e) { /* table may not exist */ }
        }

        // ── دمج النتائج وترتيبها بالتاريخ ──
        $merged = [];

        foreach ($imRows as $row) {
            $merged[] = [
                'created'  => $row['created'],
                'type'     => 'im',
                'data'     => $row,
            ];
        }
        foreach ($cdRows as $row) {
            $merged[] = [
                'created'  => $row['created'],
                'type'     => 'cd',
                'data'     => $row,
            ];
        }

        // Sort by created DESC
        usort($merged, function ($a, $b) {
            return strcmp($b['created'], $a['created']);
        });

        // Paginate (للفلتر missing نعالج الكل ثم نفلتر لاحقاً)
        $pagedItems = ($filter === 'missing') ? $merged : array_slice($merged, $offset, $perPage);
        $results = [];

        foreach ($pagedItems as $item) {
            if ($item['type'] === 'im') {
                $row = $item['data'];
                $ext = strtolower(pathinfo($row['fileName'] ?? '', PATHINFO_EXTENSION));
                if (empty($ext)) $ext = 'jpg';

                // ── بحث في عدة مواقع عن الصورة ──
                $physicalFile = $row['id'] . '_' . $row['fileHash'] . '.' . $ext;
                $fileExists = false;
                $imageUrl = '/images/imagemanager/' . $physicalFile;
                $source = 'imagemanager';

                // 1) المسار الأصلي: images/imagemanager/{id}_{hash}.{ext}
                if (file_exists($imagesDir . '/' . $physicalFile)) {
                    $fileExists = true;
                    $imageUrl = '/images/imagemanager/' . $physicalFile;
                    $source = 'imagemanager';
                }
                // 2) بالاسم الأصلي في uploads/customers/documents/{fileName}
                elseif (file_exists($uploadsDir . '/' . $row['fileName'])) {
                    $fileExists = true;
                    $imageUrl = '/uploads/customers/documents/' . $row['fileName'];
                    $source = 'smart_media';
                }
                // 3) بصيغة {id}_{hash} في uploads/customers/documents/
                elseif (file_exists($uploadsDir . '/' . $physicalFile)) {
                    $fileExists = true;
                    $imageUrl = '/uploads/customers/documents/' . $physicalFile;
                    $source = 'smart_media';
                }
                // 4) في uploads/customers/photos/
                elseif (file_exists($uploadsPhotosDir . '/' . $row['fileName'])) {
                    $fileExists = true;
                    $imageUrl = '/uploads/customers/photos/' . $row['fileName'];
                    $source = 'photos';
                }

                $gn = $row['groupName'];
                $isOrphan = ($gn !== 'contracts' && empty($row['real_customer_id']));
                $isSelected = ($row['selected_image'] == $row['id']);

                // نوع الصورة: القيم 0-9 هي أنواع مستندات، coustmers/customers/contracts قيم قديمة
                $knownLegacy = ['coustmers', 'customers', 'contracts'];
                $docType = in_array($gn, $knownLegacy) ? '' : $gn;

                $results[] = [
                    'id'            => (int) $row['id'],
                    'fileName'      => $row['fileName'],
                    'fileHash'      => $row['fileHash'],
                    'contractId'    => $row['contractId'],
                    'groupName'     => $row['groupName'],
                    'created'       => $row['created'],
                    'imageUrl'      => $imageUrl,
                    'fileExists'    => $fileExists,
                    'customerName'  => $row['customer_name'],
                    'customerId'    => $row['real_customer_id'],
                    'isOrphan'      => $isOrphan,
                    'isSelected'    => $isSelected,
                    'source'        => $source,
                    'docType'       => $docType,
                ];
            } else {
                // os_customer_documents row
                $row = $item['data'];
                $filePath = $row['file_path']; // e.g. /uploads/customers/documents/uuid.jpg
                $fileName = basename($filePath);
                $fileExists = false;
                $imageUrl = $filePath;

                // بحث في مسارات متعددة
                $webRoot = Yii::getAlias('@backend/web');
                if (file_exists($webRoot . $filePath)) {
                    $fileExists = true;
                    $imageUrl = $filePath;
                } elseif (file_exists($uploadsDir . '/' . $fileName)) {
                    $fileExists = true;
                    $imageUrl = '/uploads/customers/documents/' . $fileName;
                }

                $results[] = [
                    'id'            => 'cd_' . (int) $row['id'],
                    'fileName'      => $fileName,
                    'fileHash'      => '',
                    'contractId'    => $row['customer_id'],
                    'groupName'     => 'smart_media',
                    'created'       => $row['created'],
                    'imageUrl'      => $imageUrl,
                    'fileExists'    => $fileExists,
                    'customerName'  => $row['customer_name'],
                    'customerId'    => $row['real_customer_id'],
                    'isOrphan'      => empty($row['real_customer_id']),
                    'isSelected'    => false,
                    'source'        => 'smart_media',
                    'documentType'  => $row['document_type'] ?? 'unknown',
                ];
            }
        }

        // ── فلتر الملفات المفقودة (بعد التحقق من وجود الملفات) ──
        if ($filter === 'missing') {
            $results = array_values(array_filter($results, function ($r) { return !$r['fileExists']; }));
            $total = count($results);
            $results = array_slice($results, $offset, $perPage);
        } elseif ($filter !== 'all') {
            // استبعاد الملفات المفقودة من كل الفلاتر ما عدا "الكل" و "مفقودة"
            $results = array_values(array_filter($results, function ($r) { return $r['fileExists']; }));
            $total = count($results);
        }

        // ── تجميع الصور في دفعات (صور رُفعت معاً خلال 2 دقيقة بنفس الـ contractId) ──
        $batches = [];
        $batchIndex = 0;
        $usedIndices = [];

        for ($i = 0; $i < count($results); $i++) {
            if (in_array($i, $usedIndices)) continue;

            $batch = [$i];
            $usedIndices[] = $i;
            $baseTime = strtotime($results[$i]['created']);
            $baseContract = $results[$i]['contractId'];

            for ($j = $i + 1; $j < count($results); $j++) {
                if (in_array($j, $usedIndices)) continue;
                $diff = abs(strtotime($results[$j]['created']) - $baseTime);
                if ($diff <= 120 && $results[$j]['contractId'] === $baseContract) {
                    $batch[] = $j;
                    $usedIndices[] = $j;
                }
            }

            $batchId = 'batch_' . $batchIndex;
            foreach ($batch as $idx) {
                $results[$idx]['batchId'] = $batchId;
                $results[$idx]['batchSize'] = count($batch);
            }
            $batchIndex++;
        }

        return [
            'images'  => $results,
            'total'   => (int) $total,
            'page'    => $page,
            'perPage' => $perPage,
            'pages'   => (int) ceil($total / max(1, $perPage)),
        ];
    }

    /**
     * إعادة ربط صورة بعميل آخر (AJAX)
     */
    public function actionImageReassign()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'error' => 'طلب غير صالح'];
        }

        $imageId       = (int) Yii::$app->request->post('image_id');
        $newCustomerId = (int) Yii::$app->request->post('customer_id');
        $docType       = trim(Yii::$app->request->post('doc_type', ''));
        $setAsSelected = (bool) Yii::$app->request->post('set_selected', false);

        if ($imageId <= 0) {
            return ['success' => false, 'error' => 'رقم الصورة غير صالح'];
        }
        if ($docType === '') {
            return ['success' => false, 'error' => 'يرجى تحديد نوع الصورة'];
        }

        $db = Yii::$app->db;

        $image = $db->createCommand("SELECT * FROM os_ImageManager WHERE id = :id", [':id' => $imageId])->queryOne();
        if (!$image) {
            return ['success' => false, 'error' => 'الصورة غير موجودة'];
        }

        if ($newCustomerId > 0) {
            $customer = $db->createCommand("SELECT id, name AS customer_name FROM os_customers WHERE id = :id", [':id' => $newCustomerId])->queryOne();
            if (!$customer) {
                return ['success' => false, 'error' => 'العميل غير موجود'];
            }

            // تحديث customer_id + groupName + نوع الصورة
            $db->createCommand("UPDATE os_ImageManager SET customer_id = :cid, groupName = :docType, modified = NOW() WHERE id = :id", [
                ':cid'     => (int)$newCustomerId,
                ':docType' => $docType,
                ':id'      => $imageId,
            ])->execute();

            // الصورة الشخصية (8) → تلقائياً selected_image
            if ($docType === '8' || $setAsSelected) {
                $db->createCommand("UPDATE os_customers SET selected_image = :imgId WHERE id = :cid", [
                    ':imgId' => $imageId,
                    ':cid'   => $newCustomerId,
                ])->execute();
            }

            return [
                'success'      => true,
                'message'      => "تم ربط الصورة #{$imageId} بالعميل: {$customer['customer_name']} (#{$newCustomerId})",
                'customerName' => $customer['customer_name'],
            ];
        } else {
            return ['success' => false, 'error' => 'يرجى تحديد العميل'];
        }
    }

    /**
     * بحث عن العملاء بالاسم أو الرقم (AJAX)
     */
    public function actionImageSearchCustomers()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $q = trim((string)Yii::$app->request->get('q', ''));
        if ($q === '' || mb_strlen($q) < 2) return [];

        $db = Yii::$app->db;

        if (is_numeric($q)) {
            $rows = $db->createCommand(
                "SELECT id, name FROM os_customers WHERE id = :id OR CAST(id AS CHAR) LIKE :q ORDER BY name LIMIT 15",
                [':id' => (int)$q, ':q' => "%$q%"]
            )->queryAll();
        } else {
            $rows = $db->createCommand(
                "SELECT id, name FROM os_customers WHERE name LIKE :q ORDER BY name LIMIT 15",
                [':q' => "%$q%"]
            )->queryAll();
        }

        return $rows;
    }

    /**
     * تحديث نوع الصورة (AJAX)
     */
    public function actionImageUpdateDocType()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'error' => 'طلب غير صالح'];
        }

        $imageId = (int) Yii::$app->request->post('image_id');
        $docType = trim(Yii::$app->request->post('doc_type', ''));

        if ($imageId <= 0 || $docType === '') {
            return ['success' => false, 'error' => 'بيانات غير مكتملة'];
        }

        $db = Yii::$app->db;
        $image = $db->createCommand("SELECT id, contractId, groupName FROM os_ImageManager WHERE id = :id", [':id' => $imageId])->queryOne();
        if (!$image) {
            return ['success' => false, 'error' => 'الصورة غير موجودة'];
        }

        // حفظ النوع في groupName
        $db->createCommand("UPDATE os_ImageManager SET groupName = :docType, modified = NOW() WHERE id = :id", [
            ':docType' => $docType,
            ':id'      => $imageId,
        ])->execute();

        // إذا صورة شخصية (8) + مرتبطة بعميل → تعيين تلقائي كـ selected_image
        $autoSelected = false;
        if ($docType === '8') {
            $customerId = (int)($image['customer_id'] ?? 0) ?: (int)$image['contractId'];
            if ($customerId > 0) {
                $exists = $db->createCommand("SELECT id FROM os_customers WHERE id = :id", [':id' => $customerId])->queryScalar();
                if ($exists) {
                    $db->createCommand("UPDATE os_customers SET selected_image = :imgId WHERE id = :cid", [
                        ':imgId' => $imageId,
                        ':cid'   => $customerId,
                    ])->execute();
                    $autoSelected = true;
                }
            }
        }

        return ['success' => true, 'autoSelected' => $autoSelected];
    }

    /**
     * إحصائيات الصور (AJAX)
     */
    public function actionImageManagerStats()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $db = Yii::$app->db;

        $total = $db->createCommand("SELECT COUNT(*) FROM os_ImageManager")->queryScalar();

        $customerImages = $db->createCommand(
            "SELECT COUNT(*) FROM os_ImageManager WHERE groupName = 'coustmers'"
        )->queryScalar();

        $contractImages = $db->createCommand(
            "SELECT COUNT(*) FROM os_ImageManager WHERE groupName = 'contracts'"
        )->queryScalar();

        $orphans = $db->createCommand(
            "SELECT COUNT(*) FROM os_ImageManager im 
             LEFT JOIN os_customers c ON im.customer_id = c.id
             WHERE im.groupName IN ('coustmers','customers','0','1','2','3','4','5','6','7','8','9') AND c.id IS NULL"
        )->queryScalar();

        $linked = $db->createCommand(
            "SELECT COUNT(*) FROM os_ImageManager im 
             INNER JOIN os_customers c ON im.customer_id = c.id
             WHERE im.groupName IN ('coustmers','customers','0','1','2','3','4','5','6','7','8','9')"
        )->queryScalar();

        $unlinked = $db->createCommand(
            "SELECT COUNT(*) FROM os_ImageManager im 
             LEFT JOIN os_customers c ON im.customer_id = c.id
             WHERE c.id IS NULL AND (im.customer_id IS NULL OR im.customer_id = 0)"
        )->queryScalar();

        $noCustomer = $db->createCommand(
            "SELECT COUNT(*) FROM os_ImageManager im 
             LEFT JOIN os_customers c ON im.customer_id = c.id
             WHERE im.groupName != 'contracts' AND c.id IS NULL"
        )->queryScalar();

        // Check missing files across ALL storage locations (sample of 100)
        $sampleImages = $db->createCommand(
            "SELECT id, fileName, fileHash FROM os_ImageManager ORDER BY RAND() LIMIT 100"
        )->queryAll();

        $imagesDir = Yii::getAlias('@backend/web/images/imagemanager');
        $uploadsDir = Yii::getAlias('@backend/web/uploads/customers/documents');
        $uploadsPhotosDir = Yii::getAlias('@backend/web/uploads/customers/photos');
        $sampleMissing = 0;
        foreach ($sampleImages as $img) {
            $ext = strtolower(pathinfo($img['fileName'], PATHINFO_EXTENSION));
            if (empty($ext)) $ext = 'jpg';
            $physFile = $img['id'] . '_' . $img['fileHash'] . '.' . $ext;
            $found = file_exists($imagesDir . '/' . $physFile)
                  || file_exists($uploadsDir . '/' . $img['fileName'])
                  || file_exists($uploadsDir . '/' . $physFile)
                  || file_exists($uploadsPhotosDir . '/' . $img['fileName']);
            if (!$found) $sampleMissing++;
        }

        $estimatedMissing = $total > 0 ? round(($sampleMissing / max(1, count($sampleImages))) * $total) : 0;

        // Count Smart Media images
        $smartMediaCount = 0;
        try {
            $smartMediaCount = (int) $db->createCommand("SELECT COUNT(*) FROM os_customer_documents")->queryScalar();
        } catch (\Exception $e) {}

        return [
            'total'             => (int) $total,
            'customer_images'   => (int) $customerImages,
            'contract_images'   => (int) $contractImages,
            'orphans'           => (int) $orphans,
            'linked'            => (int) $linked,
            'unlinked'          => (int) $unlinked,
            'no_customer'       => (int) $noCustomer,
            'estimated_missing' => $estimatedMissing,
            'sample_size'       => count($sampleImages),
            'sample_missing'    => $sampleMissing,
            'smart_media_count' => $smartMediaCount,
        ];
    }

    /**
     * حذف صورة من قاعدة البيانات والملف الفعلي (AJAX)
     */
    public function actionImageDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'error' => 'طلب غير صالح'];
        }

        $rawId = trim(Yii::$app->request->post('image_id', ''));
        $db = Yii::$app->db;

        // ── Smart Media (os_customer_documents) ──
        if (strpos($rawId, 'cd_') === 0) {
            $cdId = (int) str_replace('cd_', '', $rawId);
            if ($cdId <= 0) return ['success' => false, 'error' => 'رقم غير صالح'];

            $row = $db->createCommand("SELECT * FROM os_customer_documents WHERE id = :id", [':id' => $cdId])->queryOne();
            if (!$row) return ['success' => false, 'error' => 'السجل غير موجود'];

            // حذف الملف الفعلي
            $webRoot = Yii::getAlias('@backend/web');
            $filePath = $row['file_path'] ?? '';
            if (!empty($filePath) && file_exists($webRoot . $filePath)) {
                @unlink($webRoot . $filePath);
            }
            // حذف الصورة المصغرة
            $thumbPath = str_replace('/documents/', '/documents/thumbs/thumb_', $filePath);
            if (!empty($thumbPath) && file_exists($webRoot . $thumbPath)) {
                @unlink($webRoot . $thumbPath);
            }

            $db->createCommand("DELETE FROM os_customer_documents WHERE id = :id", [':id' => $cdId])->execute();
            return ['success' => true, 'message' => "تم حذف صورة Smart Media #{$cdId}"];
        }

        // ── ImageManager (os_ImageManager) ──
        $imageId = (int) $rawId;
        if ($imageId <= 0) return ['success' => false, 'error' => 'رقم الصورة غير صالح'];

        $image = $db->createCommand("SELECT * FROM os_ImageManager WHERE id = :id", [':id' => $imageId])->queryOne();
        if (!$image) return ['success' => false, 'error' => 'الصورة غير موجودة'];

        // التحقق أنها ليست selected_image لأي عميل
        $usedByCustomer = $db->createCommand(
            "SELECT id, name FROM os_customers WHERE selected_image = :imgId LIMIT 1",
            [':imgId' => $imageId]
        )->queryOne();

        if ($usedByCustomer) {
            // إزالة الربط أولاً
            $db->createCommand("UPDATE os_customers SET selected_image = NULL WHERE selected_image = :imgId", [
                ':imgId' => $imageId,
            ])->execute();
        }

        // حذف الملف الفعلي — بحث في عدة مواقع
        $ext = strtolower(pathinfo($image['fileName'], PATHINFO_EXTENSION));
        if (empty($ext)) $ext = 'jpg';
        $physicalFile = $image['id'] . '_' . $image['fileHash'] . '.' . $ext;

        $imagesDir  = Yii::getAlias('@backend/web/images/imagemanager');
        $uploadsDir = Yii::getAlias('@backend/web/uploads/customers/documents');
        $photosDir  = Yii::getAlias('@backend/web/uploads/customers/photos');

        $paths = [
            $imagesDir . '/' . $physicalFile,
            $uploadsDir . '/' . $image['fileName'],
            $uploadsDir . '/' . $physicalFile,
            $photosDir . '/' . $image['fileName'],
        ];

        $deletedFile = false;
        foreach ($paths as $p) {
            if (file_exists($p)) {
                @unlink($p);
                $deletedFile = true;
            }
        }

        // حذف السجل من قاعدة البيانات
        $db->createCommand("DELETE FROM os_ImageManager WHERE id = :id", [':id' => $imageId])->execute();

        $msg = "تم حذف الصورة #{$imageId}";
        if ($usedByCustomer) $msg .= " (تم إلغاء ربطها بالعميل {$usedByCustomer['name']})";
        if (!$deletedFile) $msg .= " (الملف كان مفقوداً أصلاً)";

        return ['success' => true, 'message' => $msg];
    }
}
