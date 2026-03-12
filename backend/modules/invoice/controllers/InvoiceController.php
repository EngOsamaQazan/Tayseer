<?php

namespace  backend\modules\invoice\controllers;

use Yii;
use backend\modules\invoice\models\Invoice;
use backend\modules\invoice\models\InvoiceSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use backend\modules\items\models\Items;
use backend\models\Model;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use yii\filters\AccessControl;
use backend\helpers\ExportTrait;

/**
 * InvoiceController implements the CRUD actions for Invoice model.
 */
class InvoiceController extends Controller {
    use ExportTrait;

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index','update','create','delete','export-excel','export-pdf'],
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
     * Lists all Invoice models.
     * @return mixed
     */
    public function actionIndex() {
        $searchModel = new InvoiceSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Invoice model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id) {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "Invoice #" . $id,
                'content' => $this->renderAjax('view', [
                    'model' => $this->findModel($id),
                ]),
                'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
            ];
        } else {
            return $this->render('view', [
                        'model' => $this->findModel($id),
            ]);
        }
    }

    /**
     * Creates a new customers model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate() {
        $request = Yii::$app->request;
        $model = new Invoice();
        $modelsAddress = [new Items];
        

        if ($request->isAjax) {
            /*
             *   Process for ajax request
             */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "Create new customers",
                    'content' => $this->renderAjax('create', [
                        'model' => $model,
                        'modelsAddress' => (empty($modelsAddress)) ? [new Items] : $modelsAddress
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            } else if ($model->load($request->post())) {
                $modelsAddress = Model::createMultiple(Items::classname());
                Model::loadMultiple($modelsAddress, Yii::$app->request->post());

// ajax validation
                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ArrayHelper::merge(
                                    ActiveForm::validateMultiple($modelsAddress), ActiveForm::validate($model)
                    );
                }

// validate all models
                $valid = $model->validate();
                $valid = Model::validateMultiple($modelsAddress) && $valid;

                if ($valid) {
                    $transaction = \Yii::$app->db->beginTransaction();
                    try {
                        if ($flag = $model->save(false)) {
                            foreach ($modelsAddress as $modelAddress) {
                                $modelAddress->invoice_number = $model->id;
                                if (!($flag = $modelAddress->save(false))) {
                                   
                                    $transaction->rollBack();
                                    break;
                                }
                            }
                        }
                        if ($flag) {
                            $transaction->commit();
                            return $this->redirect(['view', 'id' => $model->id]);
                        }
                    } catch (Exception $e) {
                        $transaction->rollBack();
                    }
                }
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => "Create new customers",
                    'content' => '<span class="text-success">Create customers success</span>',
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::a('Create More', ['create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
                ];
            } else {
                return [
                    'title' => "Create new customers",
                    'content' => $this->renderAjax('create', [
                        'model' => $model,
                        'modelsAddress' => (empty($modelsAddress)) ? [new Items] : $modelsAddress
                        
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            }
        } else {
            /*
             *   Process for non-ajax request
             */
            if ($model->load($request->post())) {
   
                
                $modelsAddress = Model::createMultiple(Items::classname());
                Model::loadMultiple($modelsAddress, Yii::$app->request->post());

// ajax validation
                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ArrayHelper::merge(
                                    ActiveForm::validateMultiple($modelsAddress), ActiveForm::validate($model)
                    );
                }

// validate all models
                $valid = $model->validate();
                //$valid = Model::validateMultiple($modelsAddress) && $valid;
                if ($valid) {
                    
                    $transaction = \Yii::$app->db->beginTransaction();
                    try {
                        if ($flag = $model->save(false)) {
                            
                            foreach ($modelsAddress as $modelAddress) {
                                $modelAddress->invoice_number = $model->id;
                                if (!$modelAddress->save(false)) {
                                    Yii::error('Invoice item save failed: ' . json_encode($modelAddress->getErrors()));
                                    $flag = false;
                                    $transaction->rollBack();
                                    break;
                                }
                            }
                        }
                        if ($flag) {
                            $transaction->commit();
                            return $this->redirect(['view', 'id' => $model->id]);
                        }
                    } catch (Exception $e) {
                        $transaction->rollBack();
                    }
                }
            } else {
                return $this->render('create', [
                            'model' => $model,
                            'modelsAddress' => (empty($modelsAddress)) ? [new Items] : $modelsAddress
                ]);
            }
        }
    }

    /**
     * Updates an existing customers model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id) {
        $request = Yii::$app->request;
        $model = $this->findModel($id);
        $modelsAddress = Items::find()->where(['invoice_number' => $id])->all();

        if ($request->isAjax) {
            /*
             *   Process for ajax request
             */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "Update customers #" . $id,
                    'content' => $this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            } else if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => "customers #" . $id,
                    'content' => $this->renderAjax('view', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
                ];
            } else {
                return [
                    'title' => "Update customers #" . $id,
                    'content' => $this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            }
        } else {
            /*
             *   Process for non-ajax request
             */
            if ($model->load($request->post())) {
                $oldIDs = yii\helpers\ArrayHelper::map($modelsAddress, 'id', 'id');
                $modelsAddress = Model::createMultiple(Items::classname(), $modelsAddress);
                Model::loadMultiple($modelsAddress, Yii::$app->request->post());
                $deletedIDs = array_diff($oldIDs, array_filter(yii\helpers\ArrayHelper::map($modelsAddress, 'id', 'id')));
// ajax validation
                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ArrayHelper::merge(
                                    ActiveForm::validateMultiple($modelsAddress), ActiveForm::validate($model)
                    );
                }
// validate all models
                $valid = $model->validate();
                $valid = Model::validateMultiple($modelsAddress) && $valid;
                if ($valid) {
                    $transaction = \Yii::$app->db->beginTransaction();
                    try {
                        if ($flag = $model->save(false)) {
                            if (!empty($deletedIDs)) {
                                Items::deleteAll(['id' => $deletedIDs]);
                            }
                            foreach ($modelsAddress as $modelAddress) {
                                $modelAddress->invoice_number = $model->id;
                                if (!($flag = $modelAddress->save(false))) {
                                    $transaction->rollBack();
                                    break;
                                }
                            }
                        }
                        if ($flag) {
                            $transaction->commit();
                            return $this->redirect(['view', 'id' => $model->id]);
                        }
                    } catch (Exception $e) {
                        $transaction->rollBack();
                    }
                }
                return $this->render('update', [
                            'model' => $model,
                            'modelsAddress' => (empty($modelsAddress)) ? [new Items] : $modelsAddress,
                ]);
            } else {
                return $this->render('update', [
                            'model' => $model,
                            'modelsAddress' => (empty($modelsAddress)) ? [new Items] : $modelsAddress,
                ]);
            }
        }
    }

    /**
     * Delete an existing Invoice model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id) {
        $request = Yii::$app->request;
        $this->findModel($id)->delete();

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
     * Delete multiple existing Invoice model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionBulkdelete() {
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

    public function actionExportExcel() {
        $searchModel = new InvoiceSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'الفواتير',
            'filename' => 'invoices',
            'headers' => ['#', 'العنوان', 'الرقم', 'الإجمالي', 'التاريخ'],
            'keys' => ['#', 'title', 'number', 'total', 'date'],
            'widths' => [8, 30, 14, 14, 14],
        ]);
    }

    public function actionExportPdf() {
        $searchModel = new InvoiceSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'الفواتير',
            'filename' => 'invoices',
            'headers' => ['#', 'العنوان', 'الرقم', 'الإجمالي', 'التاريخ'],
            'keys' => ['#', 'title', 'number', 'total', 'date'],
        ], 'pdf');
    }

    /**
     * Finds the Invoice model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Invoice the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id) {
        if (($model = Invoice::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}
