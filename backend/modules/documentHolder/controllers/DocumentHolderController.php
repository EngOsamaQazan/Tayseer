<?php

namespace backend\modules\documentHolder\controllers;

use backend\modules\contractDocumentFile\models\ContractDocumentFile;
use backend\modules\notification\models\Notification;
use common\models\User;
use Yii;
use backend\modules\documentHolder\models\DocumentHolder;
use backend\modules\documentHolder\models\DocumentHolderSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use yii\web\UploadedFile;
use backend\helpers\ExportTrait;

/**
 * DocumentHolderController implements the CRUD actions for DocumentHolder model.
 */
class DocumentHolderController extends Controller
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
                        'actions' => ['logout', 'index', 'update', 'create', 'delete', 'archives', 'find-type', 'manager-approved', 'employee-approved', 'manager-document-holder','find-list-user', 'export-excel', 'export-pdf', 'export-manager-excel', 'export-manager-pdf'],
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
     * Lists all DocumentHolder models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new DocumentHolderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionArchives()
    {
        $searchModel = new DocumentHolderSearch();
        $dataProvider = $searchModel->archives(Yii::$app->request->queryParams);

        return $this->render('archives', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }


    /**
     * Displays a single DocumentHolder model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "DocumentHolder #" . $id,
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
     * Creates a new DocumentHolder model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new DocumentHolder();


        if ($model->load($request->post())) {
            $model->status = 1;
            if (!$model->save()) {
                return $this->render('create', [
                    'model' => $model,
                ]);
            }
            Yii::$app->notifications->sendByRule(['Manager'], 'document-holder/update?id=' . $model->id, Notification::GENERAL, Yii::t('app', '   تم انشاء طلب من الارشيف من قبل') . Yii::$app->user->identity['username'], Yii::t('app', '   تم انشاء طلب من الارشيف من قبل') . Yii::$app->user->identity['username'], Yii::$app->user->id);

            return $this->redirect(['index']);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }

    }

    /**
     * Updates an existing DocumentHolder model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        if ($model->load($request->post())) {
            if (!empty($model->manager_approved) && $model->manager_approved == 1) {
                $model->approved_by_manager = Yii::$app->user->id;
                $model->approved_at = date('Y-m-d');
                $model->status = 2;
                Yii::$app->notifications->sendByRule(['Archives'], 'document-holder/update?id=' . $model->id, Notification::GENERAL, Yii::t('app', 'طلب ') . $model->type . Yii::t('app', ' من الارشيف '), Yii::t('app', 'طلب ') . $model->type . Yii::t('app', ' من الارشيف '), Yii::$app->user->id);
            }

            return $this->redirect(['index']);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }

    }

    /**
     * Delete an existing DocumentHolder model.
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
     * Delete multiple existing DocumentHolder model.
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
        $searchModel = new DocumentHolderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'حامل الوثائق',
            'filename' => 'document_holders',
            'headers' => ['#', 'موافقة المدير', 'موافقة الموظف', 'السبب', 'رقم العقد', 'الحالة', 'النوع'],
            'keys' => [
                '#',
                'approvedByManager.username',
                function ($model) { return empty($model->approved_by_employee) ? 'لا' : 'نعم'; },
                'reason',
                'contract_id',
                'status',
                'type',
            ],
            'widths' => [8, 20, 20, 30, 15, 15, 20],
        ]);
    }

    public function actionExportPdf()
    {
        $searchModel = new DocumentHolderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'حامل الوثائق',
            'filename' => 'document_holders',
            'headers' => ['#', 'موافقة المدير', 'موافقة الموظف', 'السبب', 'رقم العقد', 'الحالة', 'النوع'],
            'keys' => [
                '#',
                'approvedByManager.username',
                function ($model) { return empty($model->approved_by_employee) ? 'لا' : 'نعم'; },
                'reason',
                'contract_id',
                'status',
                'type',
            ],
        ], 'pdf');
    }

    public function actionExportManagerExcel()
    {
        $searchModel = new DocumentHolderSearch();
        $dataProvider = $searchModel->managerSearch(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'حامل الوثائق - المدير',
            'filename' => 'document_holders_manager',
            'headers' => ['#', 'موافقة المدير', 'موافقة الموظف', 'السبب', 'رقم العقد', 'الحالة', 'النوع'],
            'keys' => [
                '#',
                'approvedByManager.username',
                function ($model) { return empty($model->approved_by_employee) ? 'لا' : 'نعم'; },
                'reason',
                'contract_id',
                'status',
                'type',
            ],
            'widths' => [8, 20, 20, 30, 15, 15, 20],
        ]);
    }

    public function actionExportManagerPdf()
    {
        $searchModel = new DocumentHolderSearch();
        $dataProvider = $searchModel->managerSearch(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'حامل الوثائق - المدير',
            'filename' => 'document_holders_manager',
            'headers' => ['#', 'موافقة المدير', 'موافقة الموظف', 'السبب', 'رقم العقد', 'الحالة', 'النوع'],
            'keys' => [
                '#',
                'approvedByManager.username',
                function ($model) { return empty($model->approved_by_employee) ? 'لا' : 'نعم'; },
                'reason',
                'contract_id',
                'status',
                'type',
            ],
        ], 'pdf');
    }

    /**
     * Finds the DocumentHolder model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return DocumentHolder the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = DocumentHolder::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionFindType()
    {
        $contract = Yii::$app->request->post('contract');
        $contractFile = ContractDocumentFile::find()->where(['contract_id' => $contract])->andwhere(['document_type' => 'contract file'])->one();
        $judiciaryFile = ContractDocumentFile::find()->where(['contract_id' => $contract])->andwhere(['document_type' => 'judiciary file'])->one();
        $type = [];
        if (!empty($judiciaryFile)) {
            $type[] = 'judiciary file';
        }
        return json_encode($type);
    }

    public function actionManagerApproved()
    {
        $id = Yii::$app->request->post('id');

        if (DocumentHolder::updateAll(['manager_approved' => 1], ['id' => $id]) && DocumentHolder::updateAll(['status' => 2], ['id' => $id]) &&DocumentHolder::updateAll(['approved_by_manager' => time()], ['id' => $id])) {
            return 1;
        } else {
            return 0;
        }

    }

    public function actionEmployeeApproved()
    {
        $id = Yii::$app->request->post('id');
        if (DocumentHolder::updateAll(['approved_by_employee' => 1], ['id' => $id]) && DocumentHolder::updateAll(['status' => 3], ['id' => $id]) && DocumentHolder::updateAll(['status' => date('Y-m-d')], ['id' => $id])) {
            return 1;
        } else {
            return 0;
        }

    }

    public function actionManagerDocumentHolder()
    {
        $searchModel = new DocumentHolderSearch();
        $dataProvider = $searchModel->managerSearch(Yii::$app->request->queryParams);

        return $this->render('manager_index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionFindListUser()
    {
        $contract = Yii::$app->request->post('contract');

        $user = DocumentHolder::find()->where(['contract_id' => $contract])->andWhere(['approved_by_employee' => 1])->andWhere(['manager_approved' => 1])->orderBy(['id' => SORT_DESC])->one();
       if(!empty($user)){
           $user = User::findOne(['id' => $user->created_by]);
           return '  اخر شخص استلم الملف ' . $user->username;
       }else{
           return 'لا يوجد شخض استلم هذا الملف';
       }

    }
}
