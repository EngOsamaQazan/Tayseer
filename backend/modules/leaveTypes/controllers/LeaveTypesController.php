<?php

namespace backend\modules\LeaveTypes\controllers;

use Yii;
use  backend\modules\LeaveTypes\models\LeaveTypes;
use  backend\modules\LeaveTypes\models\LeaveTypesSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use yii\filters\AccessControl;
use backend\helpers\ExportTrait;

/**
 * LeaveTypesController implements the CRUD actions for LeaveTypes model.
 */
class LeaveTypesController extends Controller
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
                        'actions' => ['logout', 'index', 'update', 'create', 'delete', 'export-excel', 'export-pdf'],
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
     * Lists all LeaveTypes models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new LeaveTypesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }


    public function actionExportExcel()
    {
        $searchModel = new LeaveTypesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title'    => 'أنواع الإجازات',
            'filename' => 'leave_types',
            'headers'  => ['#', 'السنة', 'نوع الإجازة', 'إجمالي الأيام', 'الوصف', 'القسم'],
            'keys'     => [
                '#', 'year',
                function ($model) {
                    $type = \common\models\LeaveTypes::findOne($model->leave_type);
                    return $type ? $type->title : '';
                },
                'total_days', 'description',
                function ($model) {
                    $dept = \backend\modules\department\models\Department::findOne($model->department);
                    return $dept ? $dept->title : '';
                },
            ],
            'widths'   => [8, 12, 20, 15, 25, 20],
        ], 'excel');
    }

    public function actionExportPdf()
    {
        $searchModel = new LeaveTypesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title'    => 'أنواع الإجازات',
            'filename' => 'leave_types',
            'headers'  => ['#', 'السنة', 'نوع الإجازة', 'إجمالي الأيام', 'الوصف', 'القسم'],
            'keys'     => [
                '#', 'year',
                function ($model) {
                    $type = \common\models\LeaveTypes::findOne($model->leave_type);
                    return $type ? $type->title : '';
                },
                'total_days', 'description',
                function ($model) {
                    $dept = \backend\modules\department\models\Department::findOne($model->department);
                    return $dept ? $dept->title : '';
                },
            ],
        ], 'pdf');
    }

    /**
     * Displays a single LeaveTypes model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "LeaveTypes #" . $id,
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
     * Creates a new LeaveTypes model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new LeaveTypes();

        if ($model->load($request->post()) && $model->save()) {
            return $this->redirect(['index']);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }

    }

    /**
     * Updates an existing LeaveTypes model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);


        if ($model->load($request->post()) && $model->save()) {
            return $this->redirect(['index']);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }

    }

    /**
     * Delete an existing LeaveTypes model.
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
     * Delete multiple existing LeaveTypes model.
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
     * Finds the LeaveTypes model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return LeaveTypes the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = LeaveTypes::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
