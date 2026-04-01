<?php

namespace backend\modules\expenseCategories\controllers;

use backend\modules\expenses\models\Expenses;
use Yii;
use backend\modules\expenseCategories\models\ExpenseCategories;
use backend\modules\expenseCategories\models\ExpenseCategoriesSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use yii\web\UploadedFile;
use backend\helpers\ExportTrait;

/**
 * ExpenseCategoriesController implements the CRUD actions for ExpenseCategories model.
 */
class ExpenseCategoriesController extends Controller
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
                        'actions' => ['logout', 'index','update','create','delete','view','import-file','export-excel','export-pdf'],
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
     * Lists all ExpenseCategories models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ExpenseCategoriesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }


    /**
     * Displays a single ExpenseCategories model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "ExpenseCategories #" . $id,
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
     * Creates a new ExpenseCategories model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new ExpenseCategories();

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "Create new ExpenseCategories",
                    'content' => $this->renderAjax('create', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            } else if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => "Create new ExpenseCategories",
                    'content' => '<span class="text-success">Create ExpenseCategories success</span>',
                    'footer' => Html::button('Close', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                        Html::a('Create More', ['create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])

                ];
            } else {
                return [
                    'title' => "Create new ExpenseCategories",
                    'content' => $this->renderAjax('create', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
        } else {
            /*
            *   Process for non-ajax request
            */
            if ($model->load($request->post())&&$model->save()) {

                $this->redirect(['index']);
            } else {
                return $this->render('create', [
                    'model' => $model,
                ]);
            }
        }

    }

    /**
     * Updates an existing ExpenseCategories model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
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
                    'title' => "Update ExpenseCategories #" . $id,
                    'content' => $this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            } else if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => "ExpenseCategories #" . $id,
                    'content' => $this->renderAjax('view', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                        Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
                ];
            } else {
                return [
                    'title' => "Update ExpenseCategories #" . $id,
                    'content' => $this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            }
        } else {
            /*
            *   Process for non-ajax request
            */
            if ($model->load($request->post())) {
                $this->redirect(['index']);
            } else {
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
        }
    }

    /**
     * Delete an existing ExpenseCategories model.
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
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        } else {
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }


    }

    /**
     * Delete multiple existing ExpenseCategories model.
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

    public function actionExportExcel()
    {
        $searchModel = new ExpenseCategoriesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'تصنيفات المصاريف',
            'filename' => 'expense_categories',
            'headers' => ['#', 'الاسم', 'أنشئ بواسطة'],
            'keys' => ['#', 'name', 'createdBy.username'],
            'widths' => [8, 30, 20],
        ]);
    }

    public function actionExportPdf()
    {
        $searchModel = new ExpenseCategoriesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'تصنيفات المصاريف',
            'filename' => 'expense_categories',
            'headers' => ['#', 'الاسم', 'أنشئ بواسطة'],
            'keys' => ['#', 'name', 'createdBy.username'],
        ], 'pdf');
    }

    /**
     * Finds the ExpenseCategories model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ExpenseCategories the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = ExpenseCategories::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionImportFile($id)
    {
        $expenses = new Expenses();
        $excelFileObj = UploadedFile::getInstance($expenses, 'excel_file');
        $notImportedRecords = [];

        if (!empty($excelFileObj)) {
            $expenses = new Expenses();
            $extension = $excelFileObj->extension;
            $filePath = $excelFileObj->tempName;;

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
                Yii::$app->session->addFlash('danger', 'Cannot this file');
            }

            for ($i = 16; $i < $sheetDataCount; $i++) {
                $cellM = $sheetData[$i]['M'] ?? '';
                if (!is_string($cellM) || trim($cellM) === '') {
                    continue;
                }

                $model = new Expenses();
                $model->description = trim($cellM);
                $model->amount = (float)($sheetData[$i]['I']);
                $model->receiver_number = 0;
                $model->document_number = 0;
                $model->category_id = $id;
                if (!$model->save()) {
                    array_push($notImportedRecords, $model->attributes);
                }
            }

            if (count($notImportedRecords) == 0) {
                Yii::$app->session->addFlash('success', 'All data has been imported');
            }
        }

        return $this->render('import', [
            'model' => $expenses,
            'notImportedRecords' => $notImportedRecords,
        ]);
    }
}
