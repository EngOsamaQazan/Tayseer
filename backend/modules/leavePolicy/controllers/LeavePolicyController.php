<?php

namespace backend\modules\leavePolicy\controllers;

use Yii;
use  backend\modules\leavePolicy\models\LeavePolicy;
use  backend\modules\leavePolicy\models\LeavePolicySearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use yii\filters\AccessControl;
use backend\helpers\ExportTrait;

/**
 * LeavePolicyController implements the CRUD actions for LeavePolicy model.
 */
class LeavePolicyController extends Controller
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
     * Lists all LeavePolicy models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new LeavePolicySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }


    public function actionExportExcel()
    {
        $searchModel = new LeavePolicySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title'    => 'سياسات الإجازات',
            'filename' => 'leave_policies',
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
        $searchModel = new LeavePolicySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title'    => 'سياسات الإجازات',
            'filename' => 'leave_policies',
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
     * Displays a single LeavePolicy model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "LeavePolicy #" . $id,
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
     * Creates a new LeavePolicy model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new LeavePolicy();

        if ($model->load($request->post()) && $model->save()) {
            return $this->redirect(['index']);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }

    }

    /**
     * Updates an existing LeavePolicy model.
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
     * Delete an existing LeavePolicy model.
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
     * Delete multiple existing LeavePolicy model.
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
     * Finds the LeavePolicy model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return LeavePolicy the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = LeavePolicy::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
