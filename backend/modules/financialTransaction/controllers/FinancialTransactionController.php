<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  وحدة التحكم بالحركات المالية — مبني من الصفر
 *  ─────────────────────────────────────────────────────────────────
 *  يشمل: عرض، إضافة، تعديل، حذف، استيراد ذكي، ترحيل، تحديثات مباشرة
 * ═══════════════════════════════════════════════════════════════════
 */

namespace backend\modules\financialTransaction\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use backend\modules\financialTransaction\models\FinancialTransaction;
use backend\modules\financialTransaction\models\FinancialTransactionSearch;
use backend\modules\financialTransaction\helpers\BankStatementAnalyzer;
use backend\modules\contracts\models\Contracts;
use backend\modules\companyBanks\models\CompanyBanks;
use common\helper\Permissions;
use backend\helpers\ExportTrait;

class FinancialTransactionController extends Controller
{
    use ExportTrait;
    /* ═══════════════════════════════════════════════
       صلاحيات الوصول والأفعال المسموحة
       ═══════════════════════════════════════════════ */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['actions' => ['login', 'error'], 'allow' => true],
                    /* ═══ عرض ═══ */
                    [
                        'actions' => ['index', 'view', 'find-notes', 'export-excel', 'export-pdf'],
                        'allow'   => true,
                        'roles'   => [Permissions::FIN_VIEW],
                    ],
                    /* ═══ إضافة ═══ */
                    [
                        'actions' => ['create'],
                        'allow'   => true,
                        'roles'   => [Permissions::FIN_CREATE],
                    ],
                    /* ═══ تعديل ═══ */
                    [
                        'actions' => [
                            'update',
                            'update-category', 'update-type', 'update-type-income',
                            'contract', 'update-document', 'update-company',
                            'save-notes',
                        ],
                        'allow' => true,
                        'roles' => [Permissions::FIN_EDIT],
                    ],
                    /* ═══ حذف ═══ */
                    [
                        'actions' => ['delete', 'bulk-delete', 'undo-last-import'],
                        'allow'   => true,
                        'roles'   => [Permissions::FIN_DELETE],
                    ],
                    /* ═══ استيراد ═══ */
                    [
                        'actions' => ['import-file'],
                        'allow'   => true,
                        'roles'   => [Permissions::FIN_IMPORT],
                    ],
                    /* ═══ ترحيل ═══ */
                    [
                        'actions' => ['transfer-data', 'transfer-data-to-expenses'],
                        'allow'   => true,
                        'roles'   => [Permissions::FIN_TRANSFER],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete'                   => ['post'],
                    'bulk-delete'              => ['post'],
                    'undo-last-import'         => ['post'],
                    'update-category'          => ['post'],
                    'update-type'              => ['post'],
                    'update-type-income'       => ['post'],
                    'contract'                 => ['post'],
                    'update-document'          => ['post'],
                    'update-company'           => ['post'],
                    'save-notes'               => ['post'],
                    'find-notes'               => ['post'],
                    'transfer-data'            => ['get', 'post'],
                    'transfer-data-to-expenses' => ['get', 'post'],
                ],
            ],
        ];
    }

    /* ╔═══════════════════════════════════════════════╗
       ║  عرض جميع الحركات المالية                    ║
       ╚═══════════════════════════════════════════════╝ */
    public function actionIndex()
    {
        $searchModel  = new FinancialTransactionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        /* عدد الحركات الجاهزة للترحيل */
        $dataTransfer         = $searchModel->CountDataTransfer();
        $dataTransferExpenses = $searchModel->CountDataTransferExpenses();

        /* حساب الإجماليات من الاستعلام المفلتر */
        $summary = (clone $dataProvider->query)
            ->select([
                'COALESCE(SUM(CASE WHEN type = 2 THEN amount ELSE 0 END), 0) AS total_debit',
                'COALESCE(SUM(CASE WHEN type = 1 THEN amount ELSE 0 END), 0) AS total_credit',
                'COUNT(*) AS total_count',
            ])
            ->createCommand()
            ->queryOne();

        return $this->render('index', [
            'searchModel'          => $searchModel,
            'dataProvider'         => $dataProvider,
            'dataTransfer'         => $dataTransfer,
            'dataTransferExpenses' => $dataTransferExpenses,
            'totalDebit'           => (float)$summary['total_debit'],
            'totalCredit'          => (float)$summary['total_credit'],
            'totalCount'           => (int)$summary['total_count'],
        ]);
    }

    /* ╔═══════════════════════════════════════════════╗
       ║  عرض حركة واحدة                              ║
       ╚═══════════════════════════════════════════════╝ */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title'   => 'حركة مالية #' . $id,
                'content' => $this->renderAjax('view', ['model' => $model]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => 'modal'])
                    . Html::a('تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
            ];
        }

        return $this->render('view', ['model' => $model]);
    }

    /* ╔═══════════════════════════════════════════════╗
       ║  إنشاء حركة مالية جديدة                      ║
       ╚═══════════════════════════════════════════════╝ */
    public function actionCreate()
    {
        $model = new FinancialTransaction();

        if (Yii::$app->request->isAjax) {
            return $this->handleAjaxCreateUpdate($model, 'create');
        }

        $model->setScenario('createAndUpadte');
        if ($model->load(Yii::$app->request->post())) {
            $model->document_number = time();
            $model->is_transfer     = 0;
            $model->date            = date('Y-m-d H:i:s');
            if ($model->save()) {
                $this->refreshCache();
                return $this->redirect(['index']);
            }
        }

        return $this->render('create', ['model' => $model]);
    }

    /* ╔═══════════════════════════════════════════════╗
       ║  تعديل حركة مالية                            ║
       ╚═══════════════════════════════════════════════╝ */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if (Yii::$app->request->isAjax) {
            return $this->handleAjaxCreateUpdate($model, 'update');
        }

        $model->setScenario('createAndUpadte');
        $isTransfer = $model->is_transfer;

        if ($model->load(Yii::$app->request->post())) {
            $model->is_transfer = $isTransfer;
            $model->date        = date('Y-m-d H:i:s');
            if ($model->save()) {
                $this->refreshCache();
                return $this->redirect(['index']);
            }
        }

        return $this->render('update', ['model' => $model]);
    }

    /* ╔═══════════════════════════════════════════════╗
       ║  حذف حركة مالية                              ║
       ╚═══════════════════════════════════════════════╝ */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }

        return $this->redirect(['index']);
    }

    /* ╔═══════════════════════════════════════════════╗
       ║  حذف مجموعة حركات دفعة واحدة               ║
       ╚═══════════════════════════════════════════════╝ */
    public function actionBulkDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $ids = Yii::$app->request->post('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return ['success' => false, 'message' => 'لم يتم تحديد أي حركات'];
        }

        /* تنظيف المعرّفات — أرقام صحيحة موجبة فقط */
        $safeIds = array_filter(array_map('intval', $ids), function ($id) {
            return $id > 0;
        });

        if (empty($safeIds)) {
            return ['success' => false, 'message' => 'معرّفات غير صالحة'];
        }

        $deleted = FinancialTransaction::deleteAll(['id' => $safeIds]);
        $this->refreshCache();

        return [
            'success' => true,
            'message' => "تم حذف {$deleted} حركة بنجاح",
            'count'   => $deleted,
        ];
    }

    /* ╔═══════════════════════════════════════════════════════════╗
       ║  التراجع عن آخر عملية استيراد                          ║
       ║  يحذف جميع الحركات التي تحمل نفس document_number     ║
       ║  الأحدث (أعلى timestamp) ولم يتم ترحيلها بعد           ║
       ╚═══════════════════════════════════════════════════════════╝ */
    public function actionUndoLastImport()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $db     = Yii::$app->db;
        $prefix = $db->tablePrefix;

        /* البحث عن آخر document_number — الاستيراد يستخدم time() كـ document_number */
        $lastDoc = $db->createCommand("
            SELECT document_number, COUNT(id) as cnt
            FROM {$prefix}financial_transaction
            WHERE is_deleted = 0 AND is_transfer = 0
              AND document_number > 1000000000
            GROUP BY document_number
            ORDER BY document_number DESC
            LIMIT 1
        ")->queryOne();

        if (!$lastDoc) {
            return ['success' => false, 'message' => 'لا توجد عمليات استيراد يمكن التراجع عنها'];
        }

        $docNum = $lastDoc['document_number'];
        $count  = (int)$lastDoc['cnt'];

        /* حذف الحركات */
        $deleted = FinancialTransaction::deleteAll([
            'document_number' => $docNum,
            'is_deleted'      => 0,
            'is_transfer'     => 0,
        ]);

        $this->refreshCache();

        return [
            'success' => true,
            'message' => "تم حذف {$deleted} حركة من آخر عملية استيراد (مستند #{$docNum})",
            'count'   => $deleted,
        ];
    }

    /* ╔═══════════════════════════════════════════════════════════╗
       ║  استيراد ذكي لكشوف حسابات بنكية — 3 مراحل             ║
       ║  1. GET  → عرض نموذج الرفع                             ║
       ║  2. POST → تحليل الملف + معاينة                         ║
       ║  3. POST+confirm → الاستيراد الفعلي                     ║
       ╚═══════════════════════════════════════════════════════════╝ */
    public function actionImportFile()
    {
        $model   = new FinancialTransaction();
        $preview = null;
        $mapping = null;
        $summary = null;
        $availableColumns = null;
        $analysis = null;
        $tempFile = null;

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());

            /* ═══ المرحلة 3: تأكيد الاستيراد ═══ */
            if (Yii::$app->request->post('confirm')) {
                return $this->processSmartImport();
            }

            /* ═══ المرحلة 2: تحليل الملف + معاينة ═══ */
            $excelFile = UploadedFile::getInstance($model, 'excel_file');

            if ($excelFile) {
                /* التحقق من صحة رفع الملف */
                if ($excelFile->error !== UPLOAD_ERR_OK) {
                    $uploadErrors = [
                        UPLOAD_ERR_INI_SIZE   => 'حجم الملف يتجاوز الحد المسموح به في إعدادات PHP.',
                        UPLOAD_ERR_FORM_SIZE  => 'حجم الملف يتجاوز الحد المسموح في النموذج.',
                        UPLOAD_ERR_PARTIAL    => 'تم رفع الملف جزئياً فقط. يرجى المحاولة مرة أخرى.',
                        UPLOAD_ERR_NO_FILE    => 'لم يتم رفع أي ملف.',
                        UPLOAD_ERR_NO_TMP_DIR => 'مجلد الملفات المؤقتة غير موجود على الخادم.',
                        UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف على القرص.',
                        UPLOAD_ERR_EXTENSION  => 'تم إيقاف رفع الملف بواسطة إضافة PHP.',
                    ];
                    $errMsg = $uploadErrors[$excelFile->error] ?? 'خطأ غير معروف في رفع الملف (رمز: ' . $excelFile->error . ')';
                    Yii::$app->session->setFlash('error', $errMsg);
                } else {
                    try {
                        /* قراءة الملف */
                        $sheetData = $this->readExcelFile($excelFile->tempName, $excelFile->extension);

                        if (empty($sheetData) || count($sheetData) < 2) {
                            Yii::$app->session->setFlash('error', 'الملف فارغ أو لا يحتوي على بيانات كافية.');
                        } else {
                            /* حفظ مؤقت للملف */
                            $tmpDir = sys_get_temp_dir();
                            $tempFile = $tmpDir . '/import_' . uniqid() . '.' . $excelFile->extension;
                            if (!copy($excelFile->tempName, $tempFile)) {
                                Yii::$app->session->setFlash('error', 'فشل في حفظ الملف المؤقت. تحقق من صلاحيات الكتابة.');
                            } else {
                                /* تسجيل أول 15 صف للتشخيص */
                                $debugRows = [];
                                for ($r = 1; $r <= min(15, count($sheetData)); $r++) {
                                    if (isset($sheetData[$r])) {
                                        $debugRows[$r] = $sheetData[$r];
                                    }
                                }
                                Yii::info('Import file: total rows=' . count($sheetData) . ', first 15 rows: ' . json_encode($debugRows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), __METHOD__);

                                /* تحليل تلقائي */
                                $analyzer = new BankStatementAnalyzer();
                                $analysis = $analyzer->analyze($sheetData);

                                Yii::info('Analysis result: headerRow=' . $analysis['headerRow']
                                    . ', dataStartRow=' . $analysis['dataStartRow']
                                    . ', confidence=' . $analysis['confidence']
                                    . ', mapping=' . json_encode($analysis['mapping'], JSON_UNESCAPED_UNICODE)
                                    . ', originalHeaders=' . json_encode($analysis['originalHeaders'], JSON_UNESCAPED_UNICODE),
                                    __METHOD__);

                                /* تحليل جميع الصفوف لحساب الملخص الكامل */
                                $allParsed = $analyzer->parseRows(
                                    $analysis['mapping'],
                                    $analysis['dataStartRow'],
                                    null /* بدون حد — كل الصفوف */
                                );

                                Yii::info('Parsed rows count: ' . count($allParsed), __METHOD__);

                                if (empty($allParsed)) {
                                    /* لم يتم تحليل أي صفوف — عرض تشخيص للمستخدم */
                                    $diagMsg = 'لم يتمكن النظام من تحليل بيانات الملف.';
                                    $diagMsg .= "\n" . 'صف العناوين المكتشف: ' . $analysis['headerRow'];
                                    $diagMsg .= ' | صف بداية البيانات: ' . $analysis['dataStartRow'];
                                    $diagMsg .= ' | مستوى الثقة: ' . $analysis['confidence'] . '%';
                                    $detected = [];
                                    $fieldLabels = ['date' => 'التاريخ', 'description' => 'البيان', 'debit' => 'المدين', 'credit' => 'الدائن', 'amount' => 'المبلغ', 'balance' => 'الرصيد'];
                                    foreach ($fieldLabels as $f => $lbl) {
                                        if (isset($analysis['mapping'][$f])) {
                                            $col = $analysis['mapping'][$f];
                                            $hdr = $analysis['originalHeaders'][$col] ?? '';
                                            $detected[] = $lbl . ' → ' . $col . ($hdr ? " ($hdr)" : '');
                                        }
                                    }
                                    if (!empty($detected)) {
                                        $diagMsg .= "\n" . 'الأعمدة المكتشفة: ' . implode(' | ', $detected);
                                    } else {
                                        $diagMsg .= "\n" . 'لم يتم اكتشاف أي أعمدة. تأكد من أن صف العناوين يحتوي على كلمات مثل: تاريخ، بيان، مدين، دائن، مبلغ.';
                                    }

                                    /* عرض عينة من البيانات حول dataStartRow */
                                    $sampleStart = max(1, $analysis['dataStartRow'] - 1);
                                    $sampleEnd   = min(count($sheetData), $analysis['dataStartRow'] + 3);
                                    $sampleInfo  = [];
                                    for ($sr = $sampleStart; $sr <= $sampleEnd; $sr++) {
                                        if (isset($sheetData[$sr])) {
                                            $cellVals = [];
                                            foreach ($sheetData[$sr] as $ck => $cv) {
                                                if (!empty(trim((string)$cv))) {
                                                    $cellVals[] = $ck . '=' . mb_substr(trim((string)$cv), 0, 25);
                                                }
                                            }
                                            if (!empty($cellVals)) {
                                                $sampleInfo[] = "صف $sr: " . implode(' | ', $cellVals);
                                            }
                                        }
                                    }
                                    if (!empty($sampleInfo)) {
                                        $diagMsg .= "\n\n" . 'عينة من البيانات:' . "\n" . implode("\n", $sampleInfo);
                                    }

                                    Yii::warning('Import: no rows parsed. Diagnostics: ' . $diagMsg, __METHOD__);
                                    Yii::$app->session->setFlash('error', $diagMsg);
                                } else {
                                    $summary = $analyzer->calculateSummary($allParsed);

                                    /* معاينة أول 10 صفوف فقط للعرض */
                                    $preview = array_slice($allParsed, 0, 10);

                                    /* استخراج الصفوف التي بها أخطاء لعرضها بالتفصيل */
                                    $errorRows = array_values(array_filter($allParsed, function ($r) {
                                        return !empty($r['errors']) && empty($r['openingBalance']);
                                    }));

                                    $mapping          = $analysis['mapping'];
                                    $availableColumns = $analyzer->getAvailableColumns();
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Yii::$app->session->setFlash('error', 'خطأ في قراءة الملف: ' . $e->getMessage());
                        Yii::error('Import file error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), __METHOD__);
                    }
                }
            } else {
                /* لم يتم رفع ملف — عرض رسالة توضيحية */
                Yii::$app->session->setFlash('error', 'الرجاء اختيار ملف Excel (.xlsx أو .xls) قبل الضغط على "تحليل الملف".');
            }
        }

        return $this->render('import', [
            'model'            => $model,
            'preview'          => $preview,
            'mapping'          => $mapping,
            'summary'          => $summary,
            'availableColumns' => $availableColumns,
            'analysis'         => $analysis,
            'tempFile'         => $tempFile,
            'errorRows'        => $errorRows ?? [],
        ]);
    }

    /**
     * تنفيذ الاستيراد الفعلي بعد تأكيد المستخدم
     */
    private function processSmartImport()
    {
        $post     = Yii::$app->request->post();
        $tempFile = $post['temp_file'] ?? '';
        $company  = (int)($post['company_id'] ?? 0);
        $bankId   = (int)($post['bank_id'] ?? 0);

        /* ربط الأعمدة المؤكد من المستخدم */
        $mapping = [];
        foreach (['date', 'description', 'debit', 'credit', 'amount', 'balance'] as $field) {
            if (!empty($post['mapping_' . $field])) {
                $mapping[$field] = $post['mapping_' . $field];
            }
        }
        $dataStartRow = (int)($post['data_start_row'] ?? 2);

        /* التحقق */
        if (empty($tempFile) || !file_exists($tempFile)) {
            Yii::$app->session->setFlash('error', 'الملف المؤقت غير موجود. يرجى رفع الملف مرة أخرى.');
            return $this->redirect(['import-file']);
        }
        if ($company <= 0 || $bankId <= 0) {
            Yii::$app->session->setFlash('error', 'يجب تحديد الشركة والبنك.');
            return $this->redirect(['import-file']);
        }

        /* رقم حساب البنك + ربط حساب الدفتر العام */
        $companyBank = CompanyBanks::findOne(['bank_id' => $bankId, 'company_id' => $company]);
        $bankNumber  = $companyBank ? $companyBank->bank_number : '';
        $bankGlAccountId = $companyBank ? $companyBank->account_id : null;

        try {
            /* قراءة الملف */
            $ext = pathinfo($tempFile, PATHINFO_EXTENSION);
            $sheetData = $this->readExcelFile($tempFile, $ext);

            /* تحليل جميع الصفوف */
            $analyzer  = new BankStatementAnalyzer();
            $analyzer->analyze($sheetData);
            $allRows   = $analyzer->parseRows($mapping, $dataStartRow);

            if (empty($allRows)) {
                Yii::$app->session->setFlash('error', 'لم يتم العثور على بيانات صالحة في الملف.');
                @unlink($tempFile);
                return $this->redirect(['import-file']);
            }

            /* الاستيراد داخل transaction */
            $documentNumber = time();
            $imported = 0;
            $errors   = 0;
            $transaction = Yii::$app->db->beginTransaction();

            try {
                foreach ($allRows as $row) {
                    if (!empty($row['openingBalance'])) continue; /* تخطي الرصيد الافتتاحي */
                    if ($row['amount'] <= 0) continue;
                    if (!empty($row['errors'])) { $errors++; continue; }

                    $ft = new FinancialTransaction();
                    $ft->setScenario('ImportFile');
                    $ft->bank_description = $row['description'] ?? '';
                    $ft->date             = $row['date'] ?? date('Y-m-d');
                    $ft->amount           = $row['amount'];
                    $ft->type             = $row['type']; /* 1=دائن, 2=مدين */
                    $ft->document_number  = $documentNumber;
                    $ft->company_id       = $company;
                    $ft->bank_id          = $bankId;
                    $ft->bank_number      = $bankNumber;
                    $ft->is_transfer      = 0;
                    $ft->receiver_number  = 0;
                    $ft->cash_account_id  = $bankGlAccountId;

                    if ($ft->validate() && $ft->save()) {
                        $imported++;
                    } else {
                        $errors++;
                    }
                }

                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollBack();
                @unlink($tempFile);
                Yii::$app->session->setFlash('error', 'فشل الاستيراد: ' . $e->getMessage());
                return $this->redirect(['import-file']);
            }

            /* حذف الملف المؤقت */
            @unlink($tempFile);

            /* تحديث الكاش */
            $this->refreshCache();

            /* رسالة النجاح */
            $msg = "تم استيراد {$imported} حركة بنجاح.";
            if ($errors > 0) $msg .= " ({$errors} صف تم تخطيه بسبب أخطاء)";
            Yii::$app->session->setFlash('success', $msg);

            return $this->redirect(['index']);

        } catch (\Exception $e) {
            @unlink($tempFile);
            Yii::$app->session->setFlash('error', 'خطأ: ' . $e->getMessage());
            return $this->redirect(['import-file']);
        }
    }

    /* ╔═══════════════════════════════════════════════╗
       ║  ترحيل الدفعات الدائنة إلى جدول الدخل       ║
       ╚═══════════════════════════════════════════════╝ */
    public function actionTransferData()
    {
        $userId = (int)Yii::$app->user->id;
        $db = Yii::$app->db;
        $prefix = $db->tablePrefix;

        $db->createCommand("
            INSERT INTO {$prefix}income
            (contract_id, date, amount, created_by, payment_type, _by,
             receipt_bank, payment_purpose, financial_transaction_id,
             type, notes, document_number, bank_number)
            SELECT
                contract_id, date, amount, :uid, '2', bank_description,
                NULL, 'monthly_payment', id,
                income_type, notes, document_number, bank_number
            FROM {$prefix}financial_transaction
            WHERE type = 1 AND income_type = 8 AND contract_id > 0
              AND is_transfer = 0 AND is_deleted = 0
        ", [':uid' => $userId])->execute();

        $db->createCommand("
            INSERT INTO {$prefix}income
            (contract_id, date, amount, created_by, payment_type, _by,
             receipt_bank, payment_purpose, financial_transaction_id,
             type, notes, document_number, bank_number)
            SELECT
                contract_id, date, amount, :uid, '2', bank_description,
                NULL, 'monthly_payment', id,
                income_type, notes, document_number, bank_number
            FROM {$prefix}financial_transaction
            WHERE type = 1 AND income_type != 8 AND is_transfer = 0
              AND is_deleted = 0
        ", [':uid' => $userId])->execute();

        $db->createCommand("
            UPDATE {$prefix}financial_transaction
            SET is_transfer = 1
            WHERE type = 1 AND income_type = 8 AND contract_id > 0
              AND is_transfer = 0 AND is_deleted = 0
        ")->execute();

        $db->createCommand("
            UPDATE {$prefix}financial_transaction
            SET is_transfer = 1
            WHERE type = 1 AND income_type != 8 AND is_transfer = 0
              AND is_deleted = 0
        ")->execute();

        $this->refreshCache();
        Yii::$app->session->setFlash('success', 'تم ترحيل الدفعات بنجاح.');
        return $this->redirect(['index']);
    }

    /* ╔═══════════════════════════════════════════════╗
       ║  ترحيل المصاريف المدينة إلى جدول المصاريف    ║
       ╚═══════════════════════════════════════════════╝ */
    public function actionTransferDataToExpenses()
    {
        $userId = (int)Yii::$app->user->id;
        $db = Yii::$app->db;
        $prefix = $db->tablePrefix;

        $db->createCommand("
            INSERT INTO {$prefix}expenses
            (category_id, created_at, created_by, updated_at, last_updated_by,
             is_deleted, description, amount, receiver_number,
             financial_transaction_id, expenses_date, notes, document_number, contract_id)
            SELECT
                category_id, created_at, :uid, updated_at, :uid2,
                is_deleted, bank_description, amount, receiver_number,
                id, date, notes, document_number, contract_id
            FROM {$prefix}financial_transaction
            WHERE type = 2 AND category_id > 0 AND is_transfer = 0
              AND is_deleted = 0
        ", [':uid' => $userId, ':uid2' => $userId])->execute();

        $db->createCommand("
            UPDATE {$prefix}financial_transaction
            SET is_transfer = 1
            WHERE type = 2 AND category_id > 0 AND is_transfer = 0
              AND is_deleted = 0
        ")->execute();

        $this->refreshCache();
        Yii::$app->session->setFlash('success', 'تم ترحيل المصاريف بنجاح.');
        return $this->redirect(['index']);
    }

    /* ╔═══════════════════════════════════════════════════════╗
       ║  تحديثات مباشرة من الجدول (AJAX inline updates)     ║
       ╚═══════════════════════════════════════════════════════╝ */

    /** تحديث التصنيف */
    public function actionUpdateCategory()
    {
        return $this->inlineUpdate('category_id', 'category_id');
    }

    /** تحديث النوع (مدين/دائن) مع تنظيف الحقول المرتبطة */
    public function actionUpdateType()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id   = (int)Yii::$app->request->post('id', 0);
        $type = (int)Yii::$app->request->post('type', 0);

        if ($id <= 0 || $type <= 0) {
            return ['success' => false, 'message' => 'بيانات غير صالحة'];
        }

        $updated = FinancialTransaction::updateAll(['type' => $type], ['id' => $id]);
        if ($updated > 0) {
            /* تنظيف الحقول حسب النوع */
            if ($type == 2) {
                FinancialTransaction::updateAll(['income_type' => null, 'contract_id' => null], ['id' => $id]);
            } else {
                FinancialTransaction::updateAll(['category_id' => null], ['id' => $id]);
            }
            $this->refreshCache();
            return ['success' => true, 'message' => 'تم تحديث النوع'];
        }

        return ['success' => false, 'message' => 'لم يتم التحديث'];
    }

    /** تحديث نوع الدخل */
    public function actionUpdateTypeIncome()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id         = (int)Yii::$app->request->post('id', 0);
        $typeIncome = (int)Yii::$app->request->post('type_income', 0);

        if ($id <= 0 || $typeIncome <= 0) {
            return ['success' => false, 'message' => 'بيانات غير صالحة'];
        }

        $updated = FinancialTransaction::updateAll(['income_type' => $typeIncome], ['id' => $id]);
        if ($updated > 0) {
            if ($typeIncome != 8) {
                FinancialTransaction::updateAll(['contract_id' => null], ['id' => $id]);
            }
            $this->refreshCache();
            return ['success' => true, 'message' => 'تم تحديث نوع الدخل'];
        }

        return ['success' => false, 'message' => 'لم يتم التحديث'];
    }

    /** تحديث رقم العقد مع التحقق من وجوده */
    public function actionContract()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id       = (int)Yii::$app->request->post('id', 0);
        $contract = (int)Yii::$app->request->post('contract', 0);

        if ($id <= 0) {
            return ['success' => false, 'message' => 'معرّف الحركة غير صالح'];
        }

        /* السماح بإزالة ربط العقد */
        if ($contract <= 0) {
            FinancialTransaction::updateAll(['contract_id' => null], ['id' => $id]);
            return ['success' => true, 'message' => 'تم إزالة ربط العقد'];
        }

        /* التحقق من وجود العقد */
        if (!Contracts::find()->where(['id' => $contract])->exists()) {
            return ['success' => false, 'message' => 'رقم العقد ' . $contract . ' غير موجود في النظام'];
        }

        if (FinancialTransaction::updateAll(['contract_id' => $contract], ['id' => $id]) > 0) {
            $this->refreshCache();
            return ['success' => true, 'message' => 'تم تحديث العقد بنجاح'];
        }

        return ['success' => false, 'message' => 'لم يتم التحديث'];
    }

    /** تحديث رقم المستند */
    public function actionUpdateDocument()
    {
        return $this->inlineUpdate('document_number', 'number', true);
    }

    /** تحديث الشركة */
    public function actionUpdateCompany()
    {
        return $this->inlineUpdate('company_id', 'company');
    }

    /** حفظ الملاحظات */
    public function actionSaveNotes()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id   = (int)Yii::$app->request->post('id', 0);
        $text = Yii::$app->request->post('text', '');

        if ($id <= 0) {
            return ['success' => false, 'message' => 'معرّف غير صالح'];
        }

        FinancialTransaction::updateAll(['notes' => $text], ['id' => $id]);
        return ['success' => true, 'message' => 'تم حفظ الملاحظات'];
    }

    /** جلب الملاحظات */
    public function actionFindNotes()
    {
        $id = (int)Yii::$app->request->post('id', 0);
        if ($id > 0) {
            $model = FinancialTransaction::findOne($id);
            return $model ? $model->notes : '';
        }
        return '';
    }

    /* ╔═══════════════════════════════════════════════╗
       ║  دوال مساعدة خاصة                            ║
       ╚═══════════════════════════════════════════════╝ */

    public function actionExportExcel()
    {
        $searchModel  = new FinancialTransactionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'الحركات المالية',
            'filename' => 'financial_transactions',
            'headers' => ['#', 'البيان', 'المبلغ', 'النوع', 'التصنيف', 'نوع الدخل', 'رقم العقد', 'الشركة', 'التاريخ', 'ملاحظات'],
            'keys' => [
                '#',
                function ($model) {
                    return !empty($model->description) ? $model->description : $model->bank_description;
                },
                'amount',
                function ($model) {
                    return $model->type == 1 ? 'Income' : 'Outcome';
                },
                'category.name',
                function ($model) {
                    if ($model->income_type) {
                        $cat = \backend\modules\incomeCategory\models\IncomeCategory::findOne($model->income_type);
                        return $cat ? $cat->name : $model->income_type;
                    }
                    return '';
                },
                'contract_id',
                'company.name',
                'date',
                'notes',
            ],
            'widths' => [8, 28, 14, 12, 16, 16, 14, 16, 14, 20],
        ]);
    }

    public function actionExportPdf()
    {
        $searchModel  = new FinancialTransactionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'الحركات المالية',
            'filename' => 'financial_transactions',
            'headers' => ['#', 'البيان', 'المبلغ', 'النوع', 'التصنيف', 'نوع الدخل', 'رقم العقد', 'الشركة', 'التاريخ', 'ملاحظات'],
            'keys' => [
                '#',
                function ($model) {
                    return !empty($model->description) ? $model->description : $model->bank_description;
                },
                'amount',
                function ($model) {
                    return $model->type == 1 ? 'Income' : 'Outcome';
                },
                'category.name',
                function ($model) {
                    if ($model->income_type) {
                        $cat = \backend\modules\incomeCategory\models\IncomeCategory::findOne($model->income_type);
                        return $cat ? $cat->name : $model->income_type;
                    }
                    return '';
                },
                'contract_id',
                'company.name',
                'date',
                'notes',
            ],
        ], 'pdf');
    }

    /**
     * البحث عن نموذج حركة مالية أو رمي 404
     */
    protected function findModel($id)
    {
        $model = FinancialTransaction::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة.');
        }
        return $model;
    }

    /**
     * تحديث الكاش — يُستدعى بعد أي تعديل على البيانات
     */
    private function refreshCache(): void
    {
        $p     = Yii::$app->params;
        $db    = Yii::$app->db;
        $cache = Yii::$app->cache;
        $d     = $p['time_duration'];

        $cache->set($p['key_document_number'],
            $db->createCommand($p['document_number_query'])->queryAll(), $d);
    }

    /**
     * تحديث حقل واحد مباشرة (inline) — دالة موحدة
     *
     * @param string $dbField اسم الحقل في قاعدة البيانات
     * @param string $postKey اسم المفتاح في الـ POST
     * @param bool $isInt هل القيمة عدد صحيح
     */
    private function inlineUpdate(string $dbField, string $postKey, bool $isInt = true)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id    = (int)Yii::$app->request->post('id', 0);
        $value = Yii::$app->request->post($postKey, '');
        if ($isInt) $value = (int)$value;

        if ($id <= 0) {
            return ['success' => false, 'message' => 'معرّف غير صالح'];
        }

        if (FinancialTransaction::updateAll([$dbField => $value ?: null], ['id' => $id]) > 0) {
            $this->refreshCache();
            return ['success' => true, 'message' => 'تم التحديث'];
        }

        return ['success' => false, 'message' => 'لم يتم التحديث'];
    }

    /**
     * معالجة AJAX للإنشاء والتعديل (المودال)
     */
    private function handleAjaxCreateUpdate(FinancialTransaction $model, string $action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;
        $isNew   = $model->isNewRecord;
        $id      = $model->id;

        if ($request->isGet) {
            return [
                'title'   => $isNew ? 'إضافة حركة مالية' : 'تعديل حركة #' . $id,
                'content' => $this->renderAjax($action, ['model' => $model]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => 'modal'])
                    . Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
            ];
        }

        $model->setScenario('others');
        if ($model->load($request->post()) && $model->save()) {
            $this->refreshCache();
            if ($isNew) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title'       => 'إضافة حركة مالية',
                    'content'     => '<span class="text-success">تم الإضافة بنجاح</span>',
                    'footer'      => Html::button('إغلاق', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => 'modal'])
                        . Html::a('إضافة أخرى', ['create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
                ];
            }
            return [
                'forceReload' => '#crud-datatable-pjax',
                'title'       => 'حركة مالية #' . $id,
                'content'     => $this->renderAjax('view', ['model' => $model]),
                'footer'      => Html::button('إغلاق', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => 'modal'])
                    . Html::a('تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
            ];
        }

        return [
            'title'   => $isNew ? 'إضافة حركة مالية' : 'تعديل حركة #' . $id,
            'content' => $this->renderAjax($action, ['model' => $model]),
            'footer'  => Html::button('إغلاق', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => 'modal'])
                . Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
        ];
    }

    /**
     * قراءة ملف Excel وإرجاع بياناته كمصفوفة
     * يحاول القراءة بتنسيق formatted ثم raw إذا لم يجد تواريخ مفهومة
     */
    private function readExcelFile(string $filePath, string $extension): array
    {
        if ($extension === 'xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } elseif ($extension === 'xls') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        } else {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
        }

        $excel = $reader->load($filePath);
        $excel->setActiveSheetIndex(0);

        $data = $excel->getActiveSheet()->toArray(null, true, true, true);

        $excel->disconnectWorksheets();
        unset($excel);

        return $data;
    }
}
