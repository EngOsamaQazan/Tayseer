<?php

namespace backend\modules\judiciaryCustomersActions\controllers;

use Yii;
use backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions;
use backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActionsSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use backend\helpers\ExportHelper;
use backend\helpers\ExportTrait;

/**
 * JudiciaryCustomersActionsController implements the CRUD actions for JudiciaryCustomersActions model.
 */
class JudiciaryCustomersActionsController extends Controller
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
                        'actions' => ['logout', 'index', 'update', 'create', 'delete', 'view', 'create-followup-judicary-custamer-action', 'update-followup-judicary-custamer-action', 'export-excel', 'export-pdf', 'quick-add-request'],
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

    /**
     * Lists all JudiciaryCustomersActions models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new JudiciaryCustomersActionsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $searchCounter = $searchModel->searchCounter(Yii::$app->request->queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'searchCounter' => $searchCounter
        ]);
    }


    /**
     * Displays a single JudiciaryCustomersActions model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "JudiciaryCustomersActions #" . $id,
                'content' => $this->renderAjax('view', [
                    'model' => $this->findModel($id),
                ]),
                'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal', 'data-bs-dismiss' => 'modal']) .
                    Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
            ];
        } else {
            return $this->render('view', [
                'model' => $this->findModel($id),
            ]);
        }
    }

    /**
     * Creates a new JudiciaryCustomersActions model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $judiciaries = (new \yii\db\Query())
                ->select(['j.id', 'j.judiciary_number', 'j.year', 'j.contract_id', 'c.name as court_name'])
                ->from('os_judiciary j')
                ->leftJoin('os_court c', 'c.id = j.court_id')
                ->where(['or', ['j.is_deleted' => 0], ['j.is_deleted' => null]])
                ->orderBy(['j.id' => SORT_DESC])
                ->all();

            return [
                'title' => '<i class="fa fa-gavel"></i> اختر القضية لإضافة إجراء',
                'content' => $this->renderAjax('_select_judiciary', [
                    'judiciaries' => $judiciaries,
                ]),
                'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal', 'data-bs-dismiss' => 'modal']),
            ];
        }

        return $this->redirect(['index']);
    }

    public function actionCreateFollowupJudicaryCustamerAction($contractID)
    {
        $request = Yii::$app->request;
        $model = new JudiciaryCustomersActions();

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            if ($request->isGet) {
                return [
                    'title' => '<i class="fa fa-gavel"></i> إضافة إجراء قضائي',
                    'content' => $this->renderAjax('create-in-contract', [
                        'model' => $model,
                        'contractID' => $contractID
                    ]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal', 'data-bs-dismiss' => 'modal']) .
                        Html::button('<i class="fa fa-plus"></i> إضافة', ['class' => 'btn btn-primary', 'type' => "submit"]),
                    'size' => 'large',
                ];
            }

            if ($model->load($request->post())) {
                // Multi-party support: get selected customer IDs
                $multiCustomersRaw = $request->post('multi_customers_ids', '');
                $multiCustomerIds = array_filter(array_map('intval', explode(',', $multiCustomersRaw)));

                // Validate: at least one party must be selected
                if (empty($multiCustomerIds)) {
                    $model->addError('customers_id', 'يجب اختيار طرف واحد على الأقل');
                    return [
                        'title' => '<i class="fa fa-gavel"></i> إضافة إجراء قضائي',
                        'content' => $this->renderAjax('create-in-contract', [
                            'model' => $model,
                            'contractID' => $contractID
                        ]),
                        'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal', 'data-bs-dismiss' => 'modal']) .
                            Html::button('<i class="fa fa-plus"></i> إضافة', ['class' => 'btn btn-primary', 'type' => "submit"]),
                    ];
                }

                // Handle file upload (shared across all parties)
                $imagePath = null;
                if ($request->post('remove_image')) {
                    $model->image = null;
                }
                $uploadedFile = \yii\web\UploadedFile::getInstance($model, 'image');
                if ($uploadedFile) {
                    $uploadDir = Yii::getAlias('@webroot/uploads/judiciary_customers_actions');
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $filePath = $uploadDir . '/' . uniqid() . '.' . $uploadedFile->extension;
                    if ($uploadedFile->saveAs($filePath)) {
                        $imagePath = str_replace(Yii::getAlias('@webroot'), '', $filePath);
                    }
                }

                // Handle decision_file upload
                $decisionPath = null;
                $decisionFile = \yii\web\UploadedFile::getInstance($model, 'decision_file');
                if ($decisionFile) {
                    $uploadDir = Yii::getAlias('@webroot/uploads/judiciary_decisions');
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $decPath = $uploadDir . '/' . uniqid() . '.' . $decisionFile->extension;
                    if ($decisionFile->saveAs($decPath)) {
                        $decisionPath = str_replace(Yii::getAlias('@webroot'), '', $decPath);
                    }
                }

                // Auto-approve parent request when linking a document to it
                $actionDef = \backend\modules\judiciaryActions\models\JudiciaryActions::findOne($model->judiciary_actions_id);
                if ($actionDef && $actionDef->action_nature === 'document' && $model->parent_id) {
                    $parentAction = JudiciaryCustomersActions::findOne($model->parent_id);
                    if ($parentAction && $parentAction->request_status !== 'approved') {
                        $parentAction->request_status = 'approved';
                        $parentAction->save(false);
                    }
                }

                // When adding doc_status, mark previous current statuses as non-current
                if ($actionDef && $actionDef->action_nature === 'doc_status' && $model->is_current && $model->parent_id) {
                    JudiciaryCustomersActions::updateAll(
                        ['is_current' => 0],
                        ['parent_id' => $model->parent_id, 'is_current' => 1, 'is_deleted' => 0]
                    );
                }

                // Create one record per selected party
                $savedCount = 0;
                $errors = [];
                foreach ($multiCustomerIds as $customerId) {
                    $record = new JudiciaryCustomersActions();
                    $record->judiciary_id = $model->judiciary_id;
                    $record->customers_id = $customerId;
                    $record->judiciary_actions_id = $model->judiciary_actions_id;
                    $record->note = $model->note;
                    $record->action_date = $model->action_date;
                    $record->parent_id = $model->parent_id;
                    $record->is_current = $model->is_current;
                    $record->amount = $model->amount;
                    $record->request_target = $model->request_target;
                    $record->image = $imagePath;
                    $record->decision_text = $model->decision_text;
                    $record->decision_file = $decisionPath;

                    // Auto-set request_status for new requests
                    if ($actionDef && $actionDef->action_nature === 'request') {
                        $record->request_status = $model->request_status ?: 'pending';
                    } else {
                        $record->request_status = $model->request_status;
                    }

                    if ($record->save()) {
                        $savedCount++;
                    } else {
                        $errors[] = $customerId;
                    }
                }

                if ($savedCount > 0) {
                    $msg = $savedCount === 1
                        ? 'تم إضافة الإجراء القضائي بنجاح'
                        : 'تم إضافة الإجراء القضائي لـ ' . $savedCount . ' أطراف بنجاح';
                    return [
                        'forceClose' => true,
                        'title' => 'إضافة إجراء قضائي',
                        'content' => '<span class="text-success"><i class="fa fa-check-circle"></i> ' . $msg . '</span>',
                        'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal', 'data-bs-dismiss' => 'modal']),
                    ];
                }

                return [
                    'title' => 'إضافة إجراء قضائي',
                    'content' => $this->renderAjax('create-in-contract', [
                        'model' => $model,
                        'contractID' => $contractID
                    ]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal', 'data-bs-dismiss' => 'modal']) .
                        Html::button('<i class="fa fa-plus"></i> إضافة', ['class' => 'btn btn-primary', 'type' => "submit"]),
                ];
            }
        } else {
            if ($model->load($request->post())) {
                $uploadedFile = \yii\web\UploadedFile::getInstance($model, 'image');
                if ($uploadedFile) {
                    $uploadDir = Yii::getAlias('@webroot/uploads/judiciary_customers_actions');
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $filePath = $uploadDir . '/' . uniqid() . '.' . $uploadedFile->extension;
                    if ($uploadedFile->saveAs($filePath)) {
                        $model->image = str_replace(Yii::getAlias('@webroot'), '', $filePath);
                    }
                }

                if ($model->save()) {
                    return $this->redirect(['index']);
                }
            }

            return $this->render('create-in-contract', [
                'model' => $model,
                'contractID' => $contractID
            ]);
        }
    }

    public function actionUpdateFollowupJudicaryCustamerAction($id, $contractID)
    {


        $request = Yii::$app->request;
        $model = $this->findModel($id);

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => '<i class="fa fa-pencil"></i> تعديل إجراء قضائي',
                    'content' => $this->renderAjax('create-in-contract', [
                        'model' => $model,
                        'contractID' => $contractID
                    ]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal', 'data-bs-dismiss' => 'modal']) .
                        Html::button('<i class="fa fa-save"></i> حفظ التعديلات', ['class' => 'btn btn-primary', 'type' => "submit"]),
                    'size' => 'large',
                ];
            } else if ($model->load($request->post())) {
                // Handle remove_image
                if ($request->post('remove_image')) {
                    $model->image = null;
                }

                // Handle file uploads
                $uploadedFile = \yii\web\UploadedFile::getInstance($model, 'image');
                if ($uploadedFile) {
                    $uploadDir = Yii::getAlias('@webroot/uploads/judiciary_customers_actions');
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $filePath = $uploadDir . '/' . uniqid() . '.' . $uploadedFile->extension;
                    if ($uploadedFile->saveAs($filePath)) {
                        $model->image = str_replace(Yii::getAlias('@webroot'), '', $filePath);
                    }
                }
                $decisionFile = \yii\web\UploadedFile::getInstance($model, 'decision_file');
                if ($decisionFile) {
                    $uploadDir = Yii::getAlias('@webroot/uploads/judiciary_decisions');
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $decPath = $uploadDir . '/' . uniqid() . '.' . $decisionFile->extension;
                    if ($decisionFile->saveAs($decPath)) {
                        $model->decision_file = str_replace(Yii::getAlias('@webroot'), '', $decPath);
                    }
                }

                // When marking doc_status as current, deactivate old ones
                $actionDef = \backend\modules\judiciaryActions\models\JudiciaryActions::findOne($model->judiciary_actions_id);
                if ($actionDef && $actionDef->action_nature === 'doc_status' && $model->is_current && $model->parent_id) {
                    JudiciaryCustomersActions::updateAll(
                        ['is_current' => 0],
                        ['and', ['parent_id' => $model->parent_id, 'is_current' => 1, 'is_deleted' => 0], ['!=', 'id', $model->id]]
                    );
                }

                if ($model->save()) {
                    return [
                        'forceClose' => true,
                        'title' => "تعديل إجراء قضائي",
                        'content' => '<span class="text-success">تم تعديل الإجراء القضائي بنجاح</span>',
                        'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal', 'data-bs-dismiss' => 'modal'])
                    ];
                } else {
                    return [
                        'title' => "تعديل إجراء قضائي",
                        'content' => $this->renderAjax('create-in-contract', [
                            'model' => $model,
                            'contractID' => $contractID
                        ]),
                        'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal', 'data-bs-dismiss' => 'modal']) .
                            Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                    ];
                }
            }
        } else {
            /*
            *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                $this->redirect(['index']);
            } else {
                return $this->render('create-in-contract', [
                    'model' => $model,
                    'contractID' => $contractID
                ]);
            }
        }
    }

    /**
     * Updates an existing JudiciaryCustomersActions model.
     * Supports AJAX modal and uses the enhanced create-in-contract form.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        // Derive contractID from judiciary
        $contractID = 0;
        if ($model->judiciary_id) {
            $jud = \backend\modules\judiciary\models\Judiciary::findOne($model->judiciary_id);
            if ($jud) $contractID = $jud->contract_id;
        }

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            if ($request->isGet) {
                return [
                    'title' => '<i class="fa fa-pencil"></i> تعديل إجراء قضائي',
                    'content' => $this->renderAjax('create-in-contract', [
                        'model' => $model,
                        'contractID' => $contractID,
                    ]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                        Html::button('<i class="fa fa-save"></i> حفظ التعديلات', ['class' => 'btn btn-primary', 'type' => 'submit']),
                    'size' => 'large',
                ];
            }

            if ($model->load($request->post())) {
                // Handle remove_image flag
                if ($request->post('remove_image')) {
                    $model->image = null;
                }
                // Handle file upload
                $uploadedFile = \yii\web\UploadedFile::getInstance($model, 'image');
                if ($uploadedFile) {
                    $uploadDir = Yii::getAlias('@webroot/uploads/judiciary_customers_actions');
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $filePath = $uploadDir . '/' . uniqid() . '.' . $uploadedFile->extension;
                    if ($uploadedFile->saveAs($filePath)) {
                        $model->image = str_replace(Yii::getAlias('@webroot'), '', $filePath);
                    }
                }
                // Handle decision_file upload
                $decisionFile = \yii\web\UploadedFile::getInstance($model, 'decision_file');
                if ($decisionFile) {
                    $uploadDir = Yii::getAlias('@webroot/uploads/judiciary_decisions');
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $decPath = $uploadDir . '/' . uniqid() . '.' . $decisionFile->extension;
                    if ($decisionFile->saveAs($decPath)) {
                        $model->decision_file = str_replace(Yii::getAlias('@webroot'), '', $decPath);
                    }
                }

                // When marking doc_status as current, deactivate old ones
                $actionDef = \backend\modules\judiciaryActions\models\JudiciaryActions::findOne($model->judiciary_actions_id);
                if ($actionDef && $actionDef->action_nature === 'doc_status' && $model->is_current && $model->parent_id) {
                    JudiciaryCustomersActions::updateAll(
                        ['is_current' => 0],
                        ['and', ['parent_id' => $model->parent_id, 'is_current' => 1, 'is_deleted' => 0], ['!=', 'id', $model->id]]
                    );
                }

                if ($model->save()) {
                    return [
                        'forceClose' => true,
                        'title' => 'تعديل إجراء قضائي',
                        'content' => '<span class="text-success">تم تعديل الإجراء القضائي بنجاح</span>',
                        'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal', 'data-bs-dismiss' => 'modal']),
                    ];
                }

                return [
                    'title' => '<i class="fa fa-pencil"></i> تعديل إجراء قضائي',
                    'content' => $this->renderAjax('create-in-contract', [
                        'model' => $model,
                        'contractID' => $contractID,
                    ]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                        Html::button('<i class="fa fa-save"></i> حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            }
        }

        // Non-AJAX
        if ($model->load($request->post()) && $model->save()) {
            return $this->redirect(['index']);
        }
        return $this->render('create-in-contract', [
            'model' => $model,
            'contractID' => $contractID,
        ]);
    }

    /**
     * Delete an existing JudiciaryCustomersActions model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $request = Yii::$app->request;
        $this->findModel($id)->delete();

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true];
        } else {
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }
    }

    /**
     * Delete multiple existing JudiciaryCustomersActions model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionBulkDelete()
    {
        $request = Yii::$app->request;
        $pks = explode(',', $request->post('pks')); // Array or selected records primary keys
        foreach ($pks as $pk) {
            $model = $this->findModel($pk);
            $model->delete();
        }

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true];
        } else {
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }
    }

    public function actionExportExcel()
    {
        return $this->exportLightweight('excel');
    }

    public function actionExportPdf()
    {
        return $this->exportLightweight('pdf');
    }

    private function exportLightweight($format)
    {
        $searchModel = new JudiciaryCustomersActionsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $query = $dataProvider->query;
        $query->with = [];

        $query->leftJoin('os_court _ct', '_ct.id = os_judiciary.court_id')
              ->leftJoin('os_lawyers _lw', '_lw.id = os_judiciary.lawyer_id')
              ->leftJoin('os_user _usr', '_usr.id = os_judiciary_customers_actions.created_by');

        $query->select([
            'os_judiciary_customers_actions.id',
            'os_judiciary_customers_actions.judiciary_id',
            'os_judiciary_customers_actions.note',
            'os_judiciary_customers_actions.action_date',
            'os_judiciary.judiciary_number', 'os_judiciary.year', 'os_judiciary.contract_id',
            'cust_name'   => 'os_customers.name',
            'action_name' => 'os_judiciary_actions.name',
            'lawyer_name' => '_lw.name',
            'court_name'  => '_ct.name',
            'created_by_name' => '_usr.username',
        ]);

        $rows = $query->asArray()->all();

        $exportRows = [];
        foreach ($rows as $r) {
            $num  = $r['judiciary_number'] ?: '—';
            $year = $r['year'] ?: '';
            $exportRows[] = [
                'case'        => $year ? "{$num}/{$year}" : $num,
                'customer'    => $r['cust_name'] ?: '—',
                'action'      => $r['action_name'] ?: '—',
                'note'        => $r['note'] ?: '',
                'created_by'  => $r['created_by_name'] ?: '—',
                'lawyer'      => $r['lawyer_name'] ?: '—',
                'court'       => $r['court_name'] ?: '—',
                'contract_id' => $r['contract_id'] ?: '—',
                'action_date' => $r['action_date'] ?: '—',
            ];
        }

        return $this->exportArrayData($exportRows, [
            'title'       => 'إجراءات العملاء القضائية',
            'filename'    => 'judiciary-customers-actions',
            'orientation' => 'L',
            'headers'     => ['#', 'القضية', 'المحكوم عليه', 'الإجراء', 'ملاحظات', 'المنشئ', 'المحامي', 'المحكمة', 'العقد', 'تاريخ الإجراء'],
            'keys'        => ['#', 'case', 'customer', 'action', 'note', 'created_by', 'lawyer', 'court', 'contract_id', 'action_date'],
            'widths'      => [6, 14, 20, 20, 28, 14, 18, 18, 10, 14],
        ], $format);
    }

    public function actionQuickAddRequest()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        $judiciaryId = (int)$request->post('judiciary_id');
        $contractId  = (int)$request->post('contract_id');
        $actionId    = (int)$request->post('action_id');
        $actionDate  = $request->post('action_date');
        $customerIds = $request->post('customer_ids', []);

        if (!$judiciaryId || !$actionId || !$actionDate || empty($customerIds)) {
            return ['success' => false, 'message' => 'بيانات ناقصة'];
        }

        $actionDef = \backend\modules\judiciaryActions\models\JudiciaryActions::findOne($actionId);
        if (!$actionDef || $actionDef->action_nature !== 'request') {
            return ['success' => false, 'message' => 'الإجراء المحدد ليس من نوع طلب'];
        }

        $created = [];
        foreach ($customerIds as $custId) {
            $record = new JudiciaryCustomersActions();
            $record->judiciary_id = $judiciaryId;
            $record->customers_id = (int)$custId;
            $record->judiciary_actions_id = $actionId;
            $record->action_date = $actionDate;
            $record->contract_id = $contractId;
            $record->request_status = 'approved';
            $record->is_deleted = 0;

            try {
                if ($record->save(false)) {
                    $custName = (new \yii\db\Query())
                        ->select('name')->from('os_customers')
                        ->where(['id' => (int)$custId])->scalar();
                    $created[] = [
                        'id' => $record->id,
                        'label' => $actionDef->name . ' · ' . substr($actionDate, 0, 10) . ($custName ? ' — ' . $custName : ''),
                    ];
                } else {
                    return ['success' => false, 'message' => 'فشل في الحفظ: ' . implode(', ', $record->getFirstErrors())];
                }
            } catch (\Exception $e) {
                return ['success' => false, 'message' => 'خطأ: ' . $e->getMessage()];
            }
        }

        if (empty($created)) {
            return ['success' => false, 'message' => 'فشل في إنشاء الطلب'];
        }

        return [
            'success' => true,
            'message' => 'تم إنشاء واعتماد ' . count($created) . ' طلب بنجاح',
            'created' => $created,
        ];
    }

    /**
     * Finds the JudiciaryCustomersActions model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return JudiciaryCustomersActions the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = JudiciaryCustomersActions::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
