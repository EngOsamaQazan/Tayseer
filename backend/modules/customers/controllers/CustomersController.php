<?php

namespace backend\modules\customers\controllers;

use backend\modules\realEstate\models\RealEstate;
use yii\web\Controller;
use backend\modules\notification\models\Notification;
use Yii;
use backend\modules\customers\models\Customers;
use  backend\modules\customers\models\CustomersSearch;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use backend\modules\address\models\Address;
use yii\helpers\ArrayHelper;
use common\models\Model;
use backend\modules\phoneNumbers\models\PhoneNumbers;
use backend\modules\customers\models\CustomersDocument;
use yii\filters\AccessControl;
use backend\modules\customers\components\RiskEngine;
use common\helper\Permissions;
use backend\helpers\ExportTrait;

/**
 * Default controller for the `reports` module
 */
class CustomersController extends Controller
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
                        'actions' => ['index', 'view', 'create-summary', 'customer-data', 'search-customers', 'search-suggest', 'field-suggest'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Permissions::can(Permissions::CUST_VIEW);
                        },
                    ],
                    [
                        'actions' => ['export-excel', 'export-pdf'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Permissions::can(Permissions::CUST_EXPORT);
                        },
                    ],
                    [
                        'actions' => ['create', 'calculate-risk', 'check-duplicate'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Permissions::can(Permissions::CUST_CREATE);
                        },
                    ],
                    [
                        'actions' => ['update', 'update-contact'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Permissions::can(Permissions::CUST_UPDATE);
                        },
                    ],
                    [
                        'actions' => ['delete', 'bulkdelete'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Permissions::can(Permissions::CUST_DELETE);
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

    /**
     * Lists all customers models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CustomersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single customers model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'عرض العميل #' . $id,
                'content' => $this->renderAjax('view', [
                    'model' => $this->findModel($id),
                ]),
                'footer' => Html::button('إغلاق', ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal']) .
                    Html::a('<i class="fa fa-pencil"></i> تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary']),
                'size' => 'large',
            ];
        } else {
            return $this->render('view', [
                'model' => $this->findModel($id),
            ]);
        }
    }

    /**
     * Drop sub-form rows whose user-fillable attributes are all empty.
     * Yii's DynamicFormWidget always renders at least one placeholder row;
     * if the user never touched it, we don't want required-field validators
     * to block the save. A row is "empty" when every attribute in
     * $checkAttributes is null/empty/whitespace-only.
     *
     * @param array $models
     * @param string[] $checkAttributes
     * @return array
     */
    private function filterEmptyRows(array $models, array $checkAttributes)
    {
        $kept = [];
        foreach ($models as $m) {
            // Existing records (have an id) are always kept so the user can
            // intentionally clear fields without losing the row.
            if (!empty($m->id)) {
                $kept[] = $m;
                continue;
            }
            $hasData = false;
            foreach ($checkAttributes as $attr) {
                $val = $m->{$attr} ?? null;
                if ($val !== null && trim((string)$val) !== '') {
                    $hasData = true;
                    break;
                }
            }
            if ($hasData) {
                $kept[] = $m;
            }
        }
        return $kept;
    }

    /**
     * Creates a new customers model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new Customers();
        $modelsAddress = [new Address];
        $modelsPhoneNumbers = [new PhoneNumbers];
        $modelCustomerDocuments = [new CustomersDocument];
        $modelRealEstate = [new RealEstate];
     $model->scenario = 'create';
        /*
         *   Process for non-ajax request
         */
        if ($model->load($request->post())) {
            $modelsAddress = Model::createMultiple(Address::classname());
            $modelRealEstate = Model::createMultiple(RealEstate::classname());
            $modelsPhoneNumbers = Model::createMultiple(PhoneNumbers::classname(), $modelsPhoneNumbers);
            $modelCustomerDocuments = Model::createMultiple(CustomersDocument::classname(), $modelCustomerDocuments);
            Model::loadMultiple($modelsAddress, Yii::$app->request->post());
            Model::loadMultiple($modelsPhoneNumbers, Yii::$app->request->post());
            Model::loadMultiple($modelCustomerDocuments, Yii::$app->request->post());
            Model::loadMultiple($modelRealEstate, Yii::$app->request->post());

            // Drop completely empty rows (auto-created placeholders) so they don't
            // trigger required-field validation errors on optional sub-forms.
            $modelsAddress = $this->filterEmptyRows($modelsAddress, ['address_city', 'address_area', 'address_street', 'address_building', 'postal_code', 'latitude', 'longitude', 'address']);
            $modelsPhoneNumbers = $this->filterEmptyRows($modelsPhoneNumbers, ['phone_number', 'owner_name', 'fb_account', 'phone_number_owner']);
            $modelCustomerDocuments = $this->filterEmptyRows($modelCustomerDocuments, ['document_number', 'document_image', 'images']);
            $modelRealEstate = $this->filterEmptyRows($modelRealEstate, ['property_type', 'property_number']);

            $valid = $model->validate();
            $valid = Model::validateMultiple($modelsAddress) && Model::validateMultiple($modelRealEstate) && Model::validateMultiple($modelsPhoneNumbers) && Model::validateMultiple($modelCustomerDocuments) && $valid;

            if ($valid) {
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    if ($flag = $model->save(false)) {
                        $addressFlag = true;
                        foreach ($modelsAddress as $modelAddress) {
                            $modelAddress->customers_id = $model->id;
                            if (!($addressFlag = $modelAddress->save())) {
                                $transaction->rollBack();
                                break;
                            }
                        }
                        $phoneNumberflag = true;
                        foreach ($modelsPhoneNumbers as $modelsPhoneNumber) {
                            $modelsPhoneNumber->customers_id = $model->id;
                            if (!($phoneNumberflag = $modelsPhoneNumber->save())) {
                                $transaction->rollBack();
                                break;
                            }
                        }
                        $modelRealEstatesflage = true;
                        foreach ($modelRealEstate as $modelRealEstates) {
                            $modelRealEstates->customer_id = $model->id;
                            if (!($modelRealEstatesflage = $modelRealEstates->save())) {
                                $transaction->rollBack();
                                break;
                            }
                        }

                        foreach ($modelCustomerDocuments as $modelCustomerDocument) {
                            $modelCustomerDocument->customer_id = $model->id;
                            if (!($customersDocumentflag = $modelCustomerDocument->save())) {
                                $transaction->rollBack();
                                break;
                            }

                            if (!empty($model->id)) {
                                if (!Yii::$app->db->createCommand("UPDATE {{%ImageManager}} set contractId={$model->id} WHERE contractId={$model->image_manager_id}")) {
                                    $transaction->rollBack();
                                }
                            }
                        }
                    }

                    if ($flag && $addressFlag && $phoneNumberflag && $modelRealEstatesflage) {
                        $transaction->commit();

                        $smartMedia = Yii::$app->request->post('SmartMedia', []);
                        if (!empty($smartMedia) && !empty($model->id)) {
                            foreach ($smartMedia as $item) {
                                if (!empty($item['image_id'])) {
                                    $updateData = ['customer_id' => (int)$model->id];
                                    if (isset($item['group_name'])) {
                                        $updateData['groupName'] = $item['group_name'];
                                    }
                                    Yii::$app->db->createCommand()->update(
                                        '{{%ImageManager}}',
                                        $updateData,
                                        ['id' => (int)$item['image_id']]
                                    )->execute();
                                }
                            }
                        }

                        $scanFileIds = Yii::$app->request->post('scan_file_ids', '');
                        if (!empty($scanFileIds) && !empty($model->id)) {
                            $ids = array_filter(array_map('intval', explode(',', $scanFileIds)));
                            if (!empty($ids)) {
                                Yii::$app->db->createCommand()->update(
                                    '{{%ImageManager}}',
                                    ['customer_id' => (int)$model->id],
                                    ['id' => $ids]
                                )->execute();
                            }
                        }

                        Yii::$app->notifications->sendByRule(['Manager'], 'customers/update/' . $model->id, Notification::GENERAL, Yii::t('app', ' اضافة عميل  ') . $model->name . Yii::t('app', '       الى العملاء من قبل') . Yii::$app->user->identity['username'], Yii::t('app', 'اضافة  ') . $model->name . Yii::t('app', '  الى العملاء من قبل') . Yii::$app->user->identity['username'], Yii::$app->user->id);
                        Yii::$app->cache->set(Yii::$app->params['key_customers'],Yii::$app->db->createCommand(Yii::$app->params['customers_query'])->queryAll(), Yii::$app->params['time_duration']);
                        Yii::$app->cache->set(Yii::$app->params['key_customers_name'],Yii::$app->db->createCommand(Yii::$app->params['customers_name_query'])->queryAll(), Yii::$app->params['time_duration']);
                        return $this->redirect(['create-summary', 'id' => $model->id]);
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
            'modelsAddress' => (empty($modelsAddress)) ? [new Address] : $modelsAddress,
            'modelsPhoneNumbers' => (empty($modelsPhoneNumbers)) ? [new PhoneNumbers] : $modelsPhoneNumbers,
            'customerDocumentsModel' => (empty($modelCustomerDocuments)) ? [new CustomersDocument] : $modelCustomerDocuments,
            'modelRealEstate' => (empty($modelRealEstate)) ? [new RealEstate] : $modelRealEstate
        ]);
    }

    /**
     * ملخص إضافة العميل — يعرض بعد إنشاء عميل جديد
     * يحتوي على زر إنشاء عقد
     * @param integer $id
     * @return mixed
     */
    public function actionCreateSummary($id)
    {
        $model = $this->findModel($id);
        $modelsAddress = Address::find()->where(['customers_id' => $id])->all();
        $modelsPhoneNumbers = PhoneNumbers::find()->where(['customers_id' => $id])->all();
        $modelRealEstate = RealEstate::find()->where(['customer_id' => $id])->all();

        return $this->render('create-summary', [
            'model' => $model,
            'modelsAddress' => $modelsAddress,
            'modelsPhoneNumbers' => $modelsPhoneNumbers,
            'modelRealEstate' => $modelRealEstate,
        ]);
    }

    /**
     * Updates an existing customers model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id, $notificationID = 0)
    {

        if ($notificationID != 0) {
            Yii::$app->notifications->setReaded($notificationID);
        }
        $request = Yii::$app->request;
        $model = $this->findModel($id);
        $modelsAddress = Address::find()->where(['customers_id' => $id])->all();
        $modelsPhoneNumbers = PhoneNumbers::find()->where(['customers_id' => $id])->all();
        $customerDocumentsModel = CustomersDocument::find()->where(['customer_id' => $id])->all();
        $modelRealEstate = RealEstate::find()->where(['customer_id' => $id])->all();

        if ($model->load($request->post())) {
            $oldIDs = yii\helpers\ArrayHelper::map($modelsAddress, 'id', 'id');
            $oldPhoneIDs = yii\helpers\ArrayHelper::map($modelsPhoneNumbers, 'id', 'id');
            $oldDocumentsIDs = yii\helpers\ArrayHelper::map($customerDocumentsModel, 'id', 'id');
            $oldmodelRealEstateIDs = yii\helpers\ArrayHelper::map($modelRealEstate, 'id', 'id');

            $modelsAddress = Model::createMultiple(Address::classname(), $modelsAddress);
            $modelsPhoneNumbers = Model::createMultiple(PhoneNumbers::classname(), $modelsPhoneNumbers);
            $customerDocumentsModel = Model::createMultiple(CustomersDocument::classname(), $customerDocumentsModel);
            $modelRealEstate = Model::createMultiple(RealEstate::classname(), $modelRealEstate);

            Model::loadMultiple($modelsAddress, Yii::$app->request->post());
            Model::loadMultiple($modelsPhoneNumbers, Yii::$app->request->post());
            Model::loadMultiple($customerDocumentsModel, Yii::$app->request->post());
            Model::loadMultiple($modelRealEstate, Yii::$app->request->post());

            $deletedIDs = array_diff($oldIDs, array_filter(yii\helpers\ArrayHelper::map($modelsAddress, 'id', 'id')));
            $deletedPhoneIDs = array_diff($oldPhoneIDs, array_filter(yii\helpers\ArrayHelper::map($modelsPhoneNumbers, 'id', 'id')));
            $deletedDocumentsIDs = array_diff($oldDocumentsIDs, array_filter(yii\helpers\ArrayHelper::map($customerDocumentsModel, 'id', 'id')));
            $deleteRealEstateIDs = array_diff($oldmodelRealEstateIDs, array_filter(yii\helpers\ArrayHelper::map($modelRealEstate, 'id', 'id')));

            $modelsAddress = $this->filterEmptyRows($modelsAddress, ['address_city', 'address_area', 'address_street', 'address_building', 'postal_code', 'latitude', 'longitude', 'address']);
            $modelsPhoneNumbers = $this->filterEmptyRows($modelsPhoneNumbers, ['phone_number', 'owner_name', 'fb_account', 'phone_number_owner']);
            $customerDocumentsModel = $this->filterEmptyRows($customerDocumentsModel, ['document_number', 'document_image', 'images']);
            $modelRealEstate = $this->filterEmptyRows($modelRealEstate, ['property_type', 'property_number']);

            // validate all models
            $valid = $model->validate();
            $valid = Model::validateMultiple($modelsAddress) && Model::validateMultiple($modelRealEstate)&& Model::validateMultiple($modelsPhoneNumbers) && Model::validateMultiple($customerDocumentsModel) && $valid;

            if ($valid) {
                $transaction = \Yii::$app->db->beginTransaction();
                try {

                    if ($flag = $model->save(false)) {
                        if (!empty($deletedIDs)) {
                            Address::deleteAll(['id' => $deletedIDs]);
                        }
                        foreach ($modelsAddress as $modelAddress) {
                            $modelAddress->customers_id = $model->id;
                            if (!($addressFlag = $modelAddress->save(false))) {

                                $transaction->rollBack();
                                break;
                            }
                        }
                        if (!empty($deleteRealEstateIDs)) {

                            RealEstate::deleteAll(['id' => $deleteRealEstateIDs]);
                        }
                        foreach ($modelRealEstate as $modelRealEstates) {

                            $modelRealEstates->customer_id = $model->id;
                            if (!($modelRealEstateFlag = $modelRealEstates->save(false))) {

                                $transaction->rollBack();
                                break;
                            }
                        }

                        if (!empty($deletedPhoneIDs)) {
                            PhoneNumbers::deleteAll(['id' => $deletedPhoneIDs]);
                        }
                        foreach ($modelsPhoneNumbers as $modelsPhoneNumber) {

                            $modelsPhoneNumber->customers_id = $model->id;
                            if (!($phoneNumberflag = $modelsPhoneNumber->save(false))) {

                                $transaction->rollBack();
                                break;
                            }
                        }
                        if (!empty($deletedDocumentsIDs)) {
                            CustomersDocument::deleteAll(['id' => $deletedDocumentsIDs]);
                        }
                        foreach ($customerDocumentsModel as $modelCustomerDocument) {

                            $modelCustomerDocument->customer_id = $model->id;
                            if (!($customersDocumentflag = ($modelCustomerDocument->save(false)))) {
                                $transaction->rollBack();
                                var_dump($modelCustomerDocument->getErrors());
                                break;
                            }
                        }
                    }
                    if ($flag && $addressFlag && $phoneNumberflag && $modelRealEstateFlag ) {
                        $transaction->commit();

                        // ═══ Link Smart Media uploads to the customer ═══
                        $smartMedia = Yii::$app->request->post('SmartMedia', []);
                        if (!empty($smartMedia) && !empty($model->id)) {
                            foreach ($smartMedia as $item) {
                                if (!empty($item['image_id'])) {
                                    $updateData = ['customer_id' => (int)$model->id];
                                    if (isset($item['group_name'])) {
                                        $updateData['groupName'] = $item['group_name'];
                                    }
                                    Yii::$app->db->createCommand()->update(
                                        '{{%ImageManager}}',
                                        $updateData,
                                        ['id' => (int)$item['image_id']]
                                    )->execute();
                                }
                            }
                        }

                        Yii::$app->cache->set(Yii::$app->params['key_customers'],Yii::$app->db->createCommand(Yii::$app->params['customers_query'])->queryAll(), Yii::$app->params['time_duration']);
                        Yii::$app->cache->set(Yii::$app->params['key_customers_name'],Yii::$app->db->createCommand(Yii::$app->params['customers_name_query'])->queryAll(), Yii::$app->params['time_duration']);

                        return $this->redirect(['update', 'id' => $model->id]);
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                }
            }
            return $this->render('update', [
                'modelCustomer' => $model,
                'model' => $model,
                'modelsAddress' => (empty($modelsAddress)) ? [new Address] : $modelsAddress,
                'modelsPhoneNumbers' => (empty($modelsPhoneNumbers)) ? [new PhoneNumbers] : $modelsPhoneNumbers,
                'customerDocumentsModel' => (empty($customerDocumentsModel)) ? [new CustomersDocument] : $customerDocumentsModel,
                'modelRealEstate' => (empty($modelRealEstate)) ? [new RealEstate] : $modelRealEstate
            ]);
        } else {
            return $this->render('update', [
                'model' => $model,
                'modelsAddress' => (empty($modelsAddress)) ? [new Address] : $modelsAddress,
                'modelsPhoneNumbers' => (empty($modelsPhoneNumbers)) ? [new PhoneNumbers] : $modelsPhoneNumbers,
                'customerDocumentsModel' => (empty($customerDocumentsModel)) ? [new CustomersDocument] : $customerDocumentsModel,
               'modelRealEstate' => (empty($modelRealEstate)) ? [new RealEstate] : $modelRealEstate

            ]);
        }
    }

    /**
     * Delete an existing customers model.
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
     * Delete multiple existing customers model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionBulkdelete()
    {
        $request = Yii::$app->request;
        $pks = explode(',', $request->post('pks'));
        foreach ($pks as $pk) {
            $model = $this->findModel($pk);
            $model->delete();
        }

        if ($request->isAjax) {
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
        return $this->exportCustomersLightweight('excel');
    }

    public function actionExportPdf()
    {
        return $this->exportCustomersLightweight('pdf');
    }

    private function exportCustomersLightweight($format)
    {
        if (!Permissions::can(Permissions::CUST_EXPORT)) {
            throw new \yii\web\ForbiddenHttpException('غير مصرح');
        }

        $searchModel = new CustomersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $query = $dataProvider->query;
        $query->with = [];

        $query->leftJoin('{{%jobs}} _jb', '_jb.id = os_customers.job_title');
        $query->select(['os_customers.id', 'os_customers.name', 'os_customers.primary_phone_number', 'os_customers.id_number', 'job_name' => '_jb.name']);

        $rows = $query->asArray()->all();

        $custIds = array_column($rows, 'id');
        $judCustIds = [];
        $contractsByCust = [];
        if (!empty($custIds)) {
            $judCustIds = array_flip((new \yii\db\Query())
                ->select('cc.customer_id')
                ->distinct()
                ->from('{{%contracts_customers}} cc')
                ->innerJoin('{{%judiciary}} j', 'j.contract_id = cc.contract_id AND (j.is_deleted = 0 OR j.is_deleted IS NULL)')
                ->where(['cc.customer_id' => $custIds])
                ->column());

            $ccRows = (new \yii\db\Query())
                ->select(['customer_id', "GROUP_CONCAT(contract_id SEPARATOR ', ') as contracts"])
                ->from('{{%contracts_customers}}')
                ->where(['customer_id' => $custIds])
                ->groupBy('customer_id')
                ->all();
            $contractsByCust = \yii\helpers\ArrayHelper::map($ccRows, 'customer_id', 'contracts');
        }

        $exportRows = [];
        foreach ($rows as $r) {
            $exportRows[] = [
                'name'     => $r['name'] ?: '—',
                'phone'    => $r['primary_phone_number'] ?: '—',
                'id_num'   => $r['id_number'] ?: '—',
                'sued'     => isset($judCustIds[$r['id']]) ? 'نعم' : 'لا',
                'contracts' => $contractsByCust[$r['id']] ?? '—',
                'job'      => $r['job_name'] ?: '—',
            ];
        }

        return $this->exportArrayData($exportRows, [
            'title'    => 'العملاء',
            'filename' => 'العملاء',
            'headers'  => ['#', 'الاسم', 'الهاتف', 'الرقم الوطني', 'مشتكى عليه', 'العقود', 'الوظيفة'],
            'keys'     => ['#', 'name', 'phone', 'id_num', 'sued', 'contracts', 'job'],
            'widths'   => [6, 25, 18, 18, 12, 20, 20],
        ], $format);
    }

    /**
     * Finds the customers model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return customers the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Customers::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionCustomerData($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = $this->findModel($id);
        $contracts_info = $model->getContractsCustomers();

        $jobName = null;
        $jobTypeId = null;
        $jobTypeName = null;
        if ($model->job_title) {
            $job = \backend\modules\jobs\models\Jobs::findOne($model->job_title);
            if ($job) {
                $jobName = $job->name;
                $jobTypeId = $job->job_type;
                $jt = $job->jobType;
                if ($jt) {
                    $jobTypeName = $jt->name;
                }
            }
        }

        return [
            'model' => $model,
            'job_name' => $jobName,
            'job_type_id' => $jobTypeId,
            'job_type_name' => $jobTypeName,
            'contracts_info' => [
                'count' => $contracts_info->count(),
                'info' => $contracts_info->all(),
            ]
        ];
    }

    /**
     * AJAX autocomplete for unified search widget on customers/index.
     */
    public function actionSearchSuggest($q = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return ['results' => []];
        }

        $db = Yii::$app->db;
        $nameNorm = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(c.name, 'ة', 'ه'), 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ى', 'ي')";
        $nameNormNoSpace = "REPLACE($nameNorm, ' ', '')";

        $words = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY);
        $nameParams = [];
        $nameClauses = [];
        foreach ($words as $i => $w) {
            $wNorm = str_replace(['أ', 'إ', 'آ'], 'ا', $w);
            $wNorm = str_replace('ة', 'ه', $wNorm);
            $wNorm = str_replace('ى', 'ي', $wNorm);
            $p1 = ':nw' . $i . 'a';
            $p2 = ':nw' . $i . 'b';
            $likeVal = '%' . $wNorm . '%';
            $nameClauses[] = "($nameNorm LIKE $p1 OR $nameNormNoSpace LIKE $p2)";
            $nameParams[$p1] = $likeVal;
            $nameParams[$p2] = $likeVal;
        }
        $nameClause = implode(' AND ', $nameClauses);

        $qLike = '%' . $q . '%';
        $qNormLike = '%' . str_replace('ى', 'ي', str_replace('ة', 'ه', str_replace(['أ', 'إ', 'آ'], 'ا', $q))) . '%';

        $rows = $db->createCommand(
            "SELECT c.id, c.name, c.id_number, c.primary_phone_number, c.city,
                    j.name AS job_name
             FROM {{%customers}} c
             LEFT JOIN {{%jobs}} j ON j.id = c.job_title
             WHERE (c.is_deleted = 0 OR c.is_deleted IS NULL)
               AND (
                   c.id = :qInt
                   OR ($nameClause)
                   OR c.id_number LIKE :qLikeId
                   OR c.primary_phone_number LIKE :qLikePhone
                   OR REPLACE(REPLACE(REPLACE(REPLACE(j.name, 'ة', 'ه'), 'أ', 'ا'), 'إ', 'ا'), 'ى', 'ي') LIKE :qNormLike
               )
             ORDER BY c.id DESC
             LIMIT 10",
            array_merge([
                ':qInt'      => (int)$q,
                ':qLikeId'   => $qLike,
                ':qLikePhone'=> $qLike,
                ':qNormLike' => $qNormLike,
            ], $nameParams)
        )->queryAll();

        $results = [];
        foreach ($rows as $r) {
            $sub = '#' . $r['id'];
            if (!empty($r['id_number'])) $sub .= ' · ' . $r['id_number'];
            if (!empty($r['primary_phone_number'])) $sub .= ' · ☎ ' . $r['primary_phone_number'];
            if (!empty($r['job_name'])) $sub .= ' · ' . $r['job_name'];
            $results[] = [
                'id'    => $r['id'],
                'title' => $r['name'],
                'sub'   => $sub,
                'icon'  => 'fa-user',
            ];
        }
        return ['results' => $results];
    }

    /**
     * AJAX autocomplete per field — customers filter bar
     */
    public function actionFieldSuggest($field = '', $q = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $q = trim($q);

        $allowed = ['customer_name', 'id', 'id_number', 'phone_number'];
        if (!in_array($field, $allowed) || mb_strlen($q) < 1) {
            return ['results' => []];
        }

        $db = Yii::$app->db;
        $results = [];
        $base = "FROM {{%customers}} c WHERE (c.is_deleted = 0 OR c.is_deleted IS NULL)";

        switch ($field) {
            case 'customer_name':
                if (mb_strlen($q) < 2) return ['results' => []];
                $qNorm = str_replace(['أ', 'إ', 'آ'], 'ا', str_replace('ة', 'ه', str_replace('ى', 'ي', $q)));
                $words = preg_split('/\s+/u', $qNorm, -1, PREG_SPLIT_NO_EMPTY);
                $norm = "REPLACE(REPLACE(REPLACE(REPLACE(c.name,'ة','ه'),'أ','ا'),'إ','ا'),'ى','ي')";
                $where = [];
                $params = [];
                foreach ($words as $i => $w) {
                    $p = ":w{$i}";
                    $where[] = "$norm LIKE $p";
                    $params[$p] = '%' . $w . '%';
                }
                $clause = implode(' AND ', $where);
                $rows = $db->createCommand(
                    "SELECT c.id, c.name, c.primary_phone_number AS phone
                     $base AND ($clause)
                     ORDER BY c.id DESC LIMIT 10", $params
                )->queryAll();
                foreach ($rows as $r) {
                    $results[] = [
                        'title' => $r['name'],
                        'sub'   => '#' . $r['id'] . ($r['phone'] ? ' — ' . $r['phone'] : ''),
                        'icon'  => 'fa-user',
                    ];
                }
                break;

            case 'id':
                $rows = $db->createCommand(
                    "SELECT c.id, c.name
                     $base AND CAST(c.id AS CHAR) LIKE :q
                     ORDER BY c.id DESC LIMIT 10",
                    [':q' => $q . '%']
                )->queryAll();
                foreach ($rows as $r) {
                    $results[] = [
                        'id'    => $r['id'],
                        'title' => '#' . $r['id'],
                        'sub'   => $r['name'] ?: '—',
                        'icon'  => 'fa-hashtag',
                    ];
                }
                break;

            case 'id_number':
                if (mb_strlen($q) < 2) return ['results' => []];
                $rows = $db->createCommand(
                    "SELECT c.id, c.name, c.id_number
                     $base AND c.id_number LIKE :q
                     ORDER BY c.id DESC LIMIT 10",
                    [':q' => $q . '%']
                )->queryAll();
                foreach ($rows as $r) {
                    $results[] = [
                        'title' => $r['id_number'],
                        'sub'   => $r['name'] . ' — #' . $r['id'],
                        'icon'  => 'fa-id-card',
                    ];
                }
                break;

            case 'phone_number':
                if (mb_strlen($q) < 2) return ['results' => []];
                $rows = $db->createCommand(
                    "SELECT c.id, c.name, c.primary_phone_number AS phone
                     $base AND c.primary_phone_number LIKE :q
                     ORDER BY c.id DESC LIMIT 10",
                    [':q' => '%' . $q . '%']
                )->queryAll();
                foreach ($rows as $r) {
                    $results[] = [
                        'title' => $r['phone'],
                        'sub'   => $r['name'] . ' — #' . $r['id'],
                        'icon'  => 'fa-phone',
                    ];
                }
                break;
        }

        return ['results' => $results];
    }

    /**
     * بحث احترافي عن العملاء — AJAX
     * يدعم: الاسم (كلمات متعددة) · الرقم الوطني · رقم الهاتف · رقم العميل
     * mode=id   → يُرجع id كمُعرّف   (لحقول customer_id)
     * mode=name → يُرجع name كمُعرّف  (لحقول customer_name)
     */
    public function actionSearchCustomers(string $q = '', string $mode = 'id')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $q = trim($q);

        if (mb_strlen($q) < 1) {
            return ['results' => []];
        }

        $query = Customers::find()
            ->select(['id', 'name', 'id_number', 'primary_phone_number'])
            ->andWhere(['is_deleted' => 0]);

        $words = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($words as $word) {
            $query->andWhere(['or',
                ['like', 'name', $word],
                ['like', 'id_number', $word],
                ['like', 'primary_phone_number', $word],
                is_numeric($word) ? ['=', 'id', (int)$word] : '0=1',
            ]);
        }

        $customers = $query->orderBy(['name' => SORT_ASC])->limit(20)->asArray()->all();

        $results = [];
        foreach ($customers as $c) {
            $results[] = [
                'id'   => $mode === 'name' ? $c['name'] : $c['id'],
                'text' => $c['name'],
                'id_number' => $c['id_number'] ?? '',
                'phone' => $c['primary_phone_number'] ?? '',
            ];
        }

        return ['results' => $results];
    }

    /**
     * Updates an existing PhoneNumbers model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdateContact($id)
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
                    'title' => 'تحديث اتصال — ' . $model->name,
                    'content' => $this->renderAjax('contact_update', [
                        'model' => $model,
                        'id' => $id
                    ]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal']) .
                        Html::button('<i class="fa fa-save"></i> حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            } else if ($model->load($request->post()) && $model->save()) {
                return ['forceClose' => true];
            } else {
                return [
                    'title' => 'تحديث اتصال — ' . $model->name,
                    'content' => $this->renderAjax('contact_update', [
                        'model' => $model,
                        'id' => $id
                    ]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal']) .
                        Html::button('<i class="fa fa-save"></i> حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            }
        } else {
            /*
             *   Process for non-ajax request
             */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return $this->render('contact_update', [
                    'model' => $model,
                    'id' => $id
                ]);
            }
        }
    }

    /**
     * AJAX: Calculate risk score live
     * @return array JSON risk assessment
     */
    public function actionCalculateRisk()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'error' => 'POST only'];
        }

        $data = Yii::$app->request->post('data', []);
        if (empty($data)) {
            return ['success' => false, 'error' => 'No data'];
        }

        try {
            $assessment = RiskEngine::assess($data);
            return ['success' => true, 'assessment' => $assessment];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * AJAX: Check for duplicate customer by ID number or phone
     * @return array JSON result
     */
    public function actionCheckDuplicate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['found' => false];
        }

        $field = Yii::$app->request->post('field');
        $value = Yii::$app->request->post('value');

        if (!$field || !$value) return ['found' => false];

        $query = Customers::find()->where(['is_deleted' => 0]);

        if ($field === 'id_number') {
            $query->andWhere(['id_number' => $value]);
        } elseif ($field === 'phone') {
            $query->andWhere(['primary_phone_number' => $value]);
        } else {
            return ['found' => false];
        }

        $customer = $query->one();

        if ($customer) {
            return [
                'found' => true,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
            ];
        }

        return ['found' => false];
    }
}
