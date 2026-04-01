<?php

namespace backend\modules\companies\controllers;

use backend\modules\address\models\Address;
use backend\modules\contracts\models\Contracts;
use backend\modules\inventoryItemQuantities\models\InventoryItemQuantities;
use backend\modules\inventoryStockLocations\models\InventoryStockLocations;
use backend\modules\notification\models\Notification;
use common\models\Model;
use Yii;
use backend\modules\companies\models\Companies;
use backend\modules\companies\models\CompaniesSearch;
use \backend\modules\companyBanks\models\CompanyBanks;
use \backend\modules\companyBanks\models\CompanyBanksSearch;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use backend\helpers\PdfToImageHelper;
use yii\filters\AccessControl;
use common\helper\Permissions;

/**
 * CompaniesController implements the CRUD actions for Companies model.
 */
class CompaniesController extends Controller
{
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
                        'actions' => ['index', 'view', 'get-items', 'search-suggest'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Permissions::can(Permissions::COMP_VIEW);
                        },
                    ],
                    [
                        'actions' => ['create'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Permissions::can(Permissions::COMP_CREATE);
                        },
                    ],
                    [
                        'actions' => ['update'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Permissions::can(Permissions::COMP_UPDATE);
                        },
                    ],
                    [
                        'actions' => ['delete', 'bulk-delete'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Permissions::can(Permissions::COMP_DELETE);
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'bulk-delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * AJAX autocomplete for unified search.
     */
    public function actionSearchSuggest($q = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $q = trim($q);
        if (mb_strlen($q) < 2) return ['results' => []];

        $db = Yii::$app->db;
        $nameNorm = "REPLACE(REPLACE(REPLACE(REPLACE(name, 'ة', 'ه'), 'أ', 'ا'), 'إ', 'ا'), 'ى', 'ي')";
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

        $rows = $db->createCommand(
            "SELECT id, name, phone_number
             FROM {{%companies}}
             WHERE ($nameClause)
                OR phone_number LIKE :qLikePhone
             ORDER BY id DESC
             LIMIT 10",
            array_merge([':qLikePhone' => '%' . $q . '%'], $nameParams)
        )->queryAll();

        $results = [];
        foreach ($rows as $r) {
            $results[] = [
                'id'    => $r['id'],
                'title' => $r['name'],
                'sub'   => $r['phone_number'] ?: '',
                'icon'  => 'fa-building',
            ];
        }
        return ['results' => $results];
    }

    /**
     * Lists all Companies models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CompaniesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $searchCounter = $searchModel->searchCounter(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'searchCounter' => $searchCounter

        ]);
    }


    /**
     * Displays a single Companies model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "مُستثمر #" . $id,
                'content' => $this->renderAjax('view', [
                    'model' => $this->findModel($id),
                ]),
                'footer' => Html::button('Close', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                    Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
            ];
        } else {
            return $this->render('view', [
                'model' => $this->findModel($id),
            ]);
        }
    }

    /**
     * Creates a new Companies model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new Companies();
        $modelsCompanieBanks = [new CompanyBanks];

        /*
        *   Process for non-ajax request
        */
        if ($model->load($request->post())) {

            $model->created_by = Yii::$app->user->id;
            $model->logo = UploadedFile::getInstance($model, 'logo');
            if (!empty($model->logo)) {
                $logoPath = 'images/' . $model->logo->baseName . '.' . $model->logo->extension;
                if ($model->logo->saveAs($logoPath)) {
                    $model->logo = $logoPath;
                }
            }

            $this->handleDocumentUploads($model);

            $modelsCompanieBanks = Model::createMultiple(CompanyBanks::classname());
            Model::loadMultiple($modelsCompanieBanks, Yii::$app->request->post());
            $valid = $model->validate();
            $valid = Model::validateMultiple($modelsCompanieBanks);

            if ($valid) {
                $transaction = \Yii::$app->db->beginTransaction();
                try {

                    if ($flag = $model->save(false)) {
                        foreach ($modelsCompanieBanks as $modelsCompanieBank) {
                            $modelsCompanieBank->company_id = $model->id;

                            if (!($companieBankFlag = $modelsCompanieBank->save())) {
                                $transaction->rollBack();
                                break;
                            }
                        }

                        if ($flag && $companieBankFlag) {
                            $transaction->commit();
                        }
                    }

                } catch (Exception $e) {
                    $transaction->rollBack();
                }
            }

            Yii::$app->cache->set(Yii::$app->params['key_company'], Yii::$app->db->createCommand(Yii::$app->params['company_query'])->queryAll(), Yii::$app->params['time_duration']);
            Yii::$app->cache->set(Yii::$app->params['key_company_name'], Yii::$app->db->createCommand(Yii::$app->params['company_name_query'])->queryAll(), Yii::$app->params['time_duration']);

            $this->redirect(['index']);
        } else {
            return $this->render('create', [
                'model' => $model,
                'modelsCompanieBanks' => (empty($modelsCompanieBanks)) ? [new CompanyBanks] : $modelsCompanieBanks,
            ]);
        }


    }

    /**
     * Updates an existing Companies model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);
        $modelsCompanieBanks = CompanyBanks::find()->where(['company_id' => $id])->all();
        $logo = $model->logo;
        $existingRegister = $model->commercial_register;
        $existingLicense = $model->trade_license;
        $createdBy = $model->created_by;
        if ($model->load($request->post())) {
            $model->logo = UploadedFile::getInstance($model, 'logo');
            if (!empty($model->logo)) {
                $logoPath = 'images/' . $model->logo->baseName . '.' . $model->logo->extension;
                if ($model->logo->saveAs($logoPath)) {
                    $model->logo = $logoPath;
                }
            } else {
                $model->logo = $logo;
            }

            $model->commercial_register = $existingRegister;
            $model->trade_license = $existingLicense;
            $this->handleDocumentUploads($model);


            $oldIDs = yii\helpers\ArrayHelper::map($modelsCompanieBanks, 'id', 'id');

            $modelsCompanieBanks = Model::createMultiple(CompanyBanks::classname(), $modelsCompanieBanks);

            Model::loadMultiple($modelsCompanieBanks, Yii::$app->request->post());

            $deletedIDs = array_diff($oldIDs, array_filter(yii\helpers\ArrayHelper::map($modelsCompanieBanks, 'id', 'id')));

            $valid = $model->validate();
            $valid = Model::validateMultiple($modelsCompanieBanks) && $valid;

            if ($valid) {
                $transaction = \Yii::$app->db->beginTransaction();
                try {

                    if ($flag = $model->save(false)) {
                        if (!empty($deletedIDs)) {
                            CompanyBanks::deleteAll(['id' => $deletedIDs]);
                        }

                        foreach ($modelsCompanieBanks as $modelsCompanieBank) {

                            $modelsCompanieBank->company_id = $model->id;

                            if (!($companieBankFlag = $modelsCompanieBank->save())) {

                                $transaction->rollBack();
                                var_dump($modelsCompanieBank->getErrors());
                                break;
                            }
                        }
                        if ($flag) {
                            $transaction->commit();
                            return $this->redirect(['index']);
                        }

                    }

                } catch (Exception $e) {
                    $transaction->rollBack();
                }
            }

            Yii::$app->cache->set(Yii::$app->params['key_company'], Yii::$app->db->createCommand(Yii::$app->params['company_query'])->queryAll(), Yii::$app->params['time_duration']);
            Yii::$app->cache->set(Yii::$app->params['key_company_name'], Yii::$app->db->createCommand(Yii::$app->params['company_name_query'])->queryAll(), Yii::$app->params['time_duration']);

            $this->redirect(['index']);
        } else {
            return $this->render('update', [
                'model' => $model,
                'modelsCompanieBanks' => (empty($modelsCompanieBanks)) ? [new CompanyBanks] : $modelsCompanieBanks,

            ]);
        }

    }

    /**
     * Delete an existing Companies model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $request = Yii::$app->request;
        $this->findModel($id)->delete();
        CompanyBanks::updateAll(['is_deleted' => 1], ['company_id' => $id]);
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


    public function actionGetItems($company_id, $model_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $arr = [];
        $selected = [];
        $quantitiesItems = InventoryItemQuantities::find()
            ->joinWith(['locations'])
            ->andWhere(['company_id' => $company_id])
            ->all();
        if ($quantitiesItems) {
            foreach ($quantitiesItems as $quantitiesItem) {
                if (isset($quantitiesItem->item)) {
                    $arr[$quantitiesItem->item->id] = $quantitiesItem->item->item_name;
                }
            }
        }
        if ($model_id) {
            $contract = Contracts::find()->andWhere(['id' => $model_id])->one();
            if (isset($contract->inventoryItemValue) && !empty($contract->inventoryItemValue)) {
                $selected = $contract->inventoryItemValue;
            }
        }
        return [
            'items' => $arr,
            'selected' => $selected
        ];

    }

    /**
     * Delete multiple existing Companies model.
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
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        } else {
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }

    }

    /**
     * Finds the Companies model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Companies the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Companies::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    protected function handleDocumentUploads($model)
    {
        $uploadDir = Yii::getAlias('@backend/web/uploads/investors/');
        if (!is_dir($uploadDir)) {
            FileHelper::createDirectory($uploadDir, 0775, true);
        }

        $regFiles = UploadedFile::getInstances($model, 'commercial_register_files');
        if (!empty($regFiles)) {
            $existing = $model->getCommercialRegisterList();
            foreach ($regFiles as $file) {
                $fname = uniqid('reg_') . '.' . $file->extension;
                $fullPath = $uploadDir . $fname;
                if ($file->saveAs($fullPath)) {
                    $existing[] = [
                        'path' => 'uploads/investors/' . $fname,
                        'name' => $file->baseName . '.' . $file->extension,
                    ];
                } else {
                    Yii::error("Failed to save register file: {$file->name} to {$fullPath}", __METHOD__);
                }
            }
            $model->commercial_register = json_encode($existing, JSON_UNESCAPED_UNICODE);
        }

        $licFiles = UploadedFile::getInstances($model, 'trade_license_files');
        if (!empty($licFiles)) {
            $existing = $model->getTradeLicenseList();
            foreach ($licFiles as $file) {
                $fname = uniqid('lic_') . '.' . $file->extension;
                $fullPath = $uploadDir . $fname;
                if ($file->saveAs($fullPath)) {
                    $existing[] = [
                        'path' => 'uploads/investors/' . $fname,
                        'name' => $file->baseName . '.' . $file->extension,
                    ];
                } else {
                    Yii::error("Failed to save license file: {$file->name} to {$fullPath}", __METHOD__);
                }
            }
            $model->trade_license = json_encode($existing, JSON_UNESCAPED_UNICODE);
        }

        foreach ($model->getCommercialRegisterList() as $doc) {
            if (strtolower(pathinfo($doc['name'], PATHINFO_EXTENSION)) === 'pdf') {
                PdfToImageHelper::convertAndCache($doc['path']);
            }
        }
        foreach ($model->getTradeLicenseList() as $doc) {
            if (strtolower(pathinfo($doc['name'], PATHINFO_EXTENSION)) === 'pdf') {
                PdfToImageHelper::convertAndCache($doc['path']);
            }
        }
    }
}
