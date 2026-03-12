<?php

namespace backend\modules\leaveRequest\controllers;

use backend\modules\leavePolicy\models\LeavePolicy;
use backend\modules\notification\models\Notification;
use Yii;
use backend\modules\leaveRequest\models\LeaveRequest;
use backend\modules\leaveRequest\models\LeaveRequestSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use yii\filters\AccessControl;
use backend\helpers\ExportTrait;

/**
 * LeaveRequestController implements the CRUD actions for LeaveRequest model.
 */
class LeaveRequestController extends Controller
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
                        'actions' => ['logout', 'index', 'update', 'create', 'delete', 'view', 'number-date', 'suspended-vacations', 'aproved','reject', 'export-excel', 'export-pdf'],
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
     * Lists all LeaveRequest models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new LeaveRequestSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionExportExcel()
    {
        $searchModel = new LeaveRequestSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title'    => 'طلبات الإجازة',
            'filename' => 'leave_requests',
            'headers'  => ['#', 'السبب', 'تاريخ البداية', 'تاريخ النهاية', 'سياسة الإجازة', 'الحالة', 'أنشأ بواسطة'],
            'keys'     => [
                '#', 'Reason', 'start_at', 'end_at',
                function ($model) { return $model->leavePolicy ? $model->leavePolicy->title : ''; },
                'status',
                function ($model) { return $model->createdBy ? $model->createdBy->username : ''; },
            ],
            'widths'   => [8, 25, 18, 18, 20, 15, 20],
        ], 'excel');
    }

    public function actionExportPdf()
    {
        $searchModel = new LeaveRequestSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title'    => 'طلبات الإجازة',
            'filename' => 'leave_requests',
            'headers'  => ['#', 'السبب', 'تاريخ البداية', 'تاريخ النهاية', 'سياسة الإجازة', 'الحالة', 'أنشأ بواسطة'],
            'keys'     => [
                '#', 'Reason', 'start_at', 'end_at',
                function ($model) { return $model->leavePolicy ? $model->leavePolicy->title : ''; },
                'status',
                function ($model) { return $model->createdBy ? $model->createdBy->username : ''; },
            ],
        ], 'pdf');
    }

    /**
     * Displays a single LeaveRequest model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "LeaveRequest #" . $id,
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
     * Creates a new LeaveRequest model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new LeaveRequest();

        if ($request->isAjax) {
            /*
             *   Process for ajax request
             */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "Create new LeaveRequest",
                    'content' => $this->renderAjax('create', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            } else if ($model->load($request->post())) {
                if (!empty($model->leave_policy)) {
                    $date_of_hire = Yii::$app->user->identity['date_of_hire'];
                    $userID = Yii::$app->user->identity['id'];
                    $connection = Yii::$app->getDb();
                    $sql = "SELECT SUM(DATEDIFF(`end_at`, `start_at`)) AS 'Result' FROM {{%leave_request}} WHERE status = 'approved' AND proved_at > $date_of_hire 
AND created_by = $userID  AND leave_policy = $model->leave_policy
;";
                    $connection = Yii::$app->getDb();
                    $command = $connection->createCommand($sql);
                    $result = $command->queryAll();
                    $discountedDays = $result[0]['Result'];
                    $allDate = LeavePolicy::findOne(['id' => $model->leave_policy]);
                    $allDate = $allDate->total_days;
                    $remainingDays = $allDate - $discountedDays;
                    if ($remainingDays < 0) {
                        return [
                            'title' => "Create new LeaveRequest",
                            'content' => $this->renderAjax('create', [
                                'model' => $model,
                            ]),
                            'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                                Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                        ];
                    } elseif ($result[0]['Result'] == 0) {
                        $model->save();
                        Yii::$app->notifications->sendByRule(['Manager'], 'leave-request/update?id=' . $model->id, Notification::GENERAL, Yii::t('app', 'طلب اجازه من قبل ') . Yii::$app->user->identity['username'], Yii::t('app', 'طلب اجازه من قبل ') . Yii::$app->user->identity['username'], Yii::$app->user->id);

                        return [
                            'forceReload' => '#crud-datatable-pjax',
                            'title' => "Create new LeaveRequest",
                            'content' => '<span class="text-success">Create LeaveRequest success</span>',
                            'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                                Html::a('Create More', ['create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
                        ];
                    } else {
                        $model->save();
                        Yii::$app->notifications->sendByRule(['Manager'], 'leave-request/update?id=' . $model->id, Notification::GENERAL, Yii::t('app', 'طلب اجازه من قبل ') . Yii::$app->user->identity['username'], Yii::t('app', 'طلب اجازه من قبل ') . Yii::$app->user->identity['username'], Yii::$app->user->id);

                        return [
                            'forceReload' => '#crud-datatable-pjax',
                            'title' => "Create new LeaveRequest",
                            'content' => '<span class="text-success">Create LeaveRequest success</span>',
                            'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                                Html::a('Create More', ['create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
                        ];
                    }

                } else {
                    return [
                        'title' => "Create new LeaveRequest",
                        'content' => $this->renderAjax('create', [
                            'model' => $model,
                        ]),
                        'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                            Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                    ];
                }
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => "Create new LeaveRequest",
                    'content' => '<span class="text-success">Create LeaveRequest success</span>',
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::a('Create More', ['create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
                ];
            } else {
                return [
                    'title' => "Create new LeaveRequest",
                    'content' => $this->renderAjax('create', [
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
                if (!empty($model->leave_policy)) {
                    $date_of_hire = Yii::$app->user->identity['date_of_hire'];
                    $userID = Yii::$app->user->identity['id'];
                    $connection = Yii::$app->getDb();
                    $sql = "SELECT SUM(DATEDIFF(`end_at`, `start_at`)) AS 'Result' FROM {{%leave_request}} WHERE status = 'approved' AND proved_at > $date_of_hire 
AND created_by = $userID  AND leave_policy = $model->leave_policy
;";
                    $connection = Yii::$app->getDb();
                    $command = $connection->createCommand($sql);
                    $result = $command->queryAll();
                    $discountedDays = $result[0]['Result'];
                    $allDate = LeavePolicy::findOne(['id' => $model->leave_policy]);
                    $allDate = $allDate->total_days;
                    $remainingDays = $allDate - $discountedDays;
                    if ($remainingDays < 0) {
                        return $this->redirect(['create']);
                    }
                    $model->save();
                    return $this->redirect(['view', 'id' => $model->id]);
                } else {
                    return $this->redirect(['create']);
                }
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return $this->render('create', [
                    'model' => $model,
                ]);
            }
        }
    }

    /**
     * Updates an existing LeaveRequest model.
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

        if ($request->isAjax) {
            /*
             *   Process for ajax request
             */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "Update LeaveRequest #" . $id,
                    'content' => $this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            } else if ($model->load($request->post())) {
                if (!empty($model->leave_policy)) {
                    $date_of_hire = Yii::$app->user->identity['date_of_hire'];
                    $userID = Yii::$app->user->identity['id'];
                    $connection = Yii::$app->getDb();
                    $sql = "SELECT SUM(DATEDIFF(`end_at`, `start_at`)) AS 'Result' FROM {{%leave_request}} WHERE status = 'approved' AND proved_at > $date_of_hire 
AND created_by = $userID  AND leave_policy = $model->leave_policy
;";
                    $connection = Yii::$app->getDb();
                    $command = $connection->createCommand($sql);
                    $result = $command->queryAll();
                    $discountedDays = $result[0]['Result'];
                    $allDate = LeavePolicy::findOne(['id' => $model->leave_policy]);
                    $allDate = $allDate->total_days;
                    $remainingDays = $allDate - $discountedDays;

                    if ($remainingDays < 0) {
                        return [
                            'title' => "Update LeaveRequest #" . $id,
                            'content' => $this->renderAjax('update', [
                                'model' => $model,
                            ]),
                            'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                                Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                        ];
                    } elseif ($result[0]['Result'] == 0) {
                        $model->save();
                        return [
                            'forceReload' => '#crud-datatable-pjax',
                            'title' => "LeaveRequest #" . $id,
                            'content' => $this->redirectAjax('index'),
                            'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                                Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
                        ];
                    } else {
                        $model->save();
                        return [
                            'forceReload' => '#crud-datatable-pjax',
                            'title' => "LeaveRequest #" . $id,
                            'content' => $this->redirectAjax('index'),
                            'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                                Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
                        ];
                    }

                } else {
                    return [
                        'title' => "Update LeaveRequest #" . $id,
                        'content' => $this->renderAjax('update', [
                            'model' => $model,
                        ]),
                        'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                            Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                    ];
                }

            } else {
                return [
                    'title' => "Update LeaveRequest #" . $id,
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
                if (!empty($model->leave_policy)) {
                    $date_of_hire = Yii::$app->user->identity['date_of_hire'];
                    $userID = Yii::$app->user->identity['id'];
                    $connection = Yii::$app->getDb();
                    $sql = "SELECT SUM(DATEDIFF(`end_at`, `start_at`)) AS 'Result' FROM {{%leave_request}} WHERE status = 'approved' AND proved_at > $date_of_hire 
AND created_by = $userID  AND leave_policy = $model->leave_policy
;";
                    $connection = Yii::$app->getDb();
                    $command = $connection->createCommand($sql);
                    $result = $command->queryAll();
                    $discountedDays = $result[0]['Result'];
                    $allDate = LeavePolicy::findOne(['id' => $model->leave_policy]);
                    $allDate = $allDate->total_days;
                    $remainingDays = $allDate - $discountedDays;
                    if ($remainingDays < 0) {
                        return $this->redirect(['update', 'id' => $model->id]);
                    }
                    $model->save();
                    return $this->redirect(['view', 'id' => $model->id]);
                } else {
                    return $this->redirect(['update', 'id' => $model->id]);
                }

            } else {
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
        }
    }

    /**
     * Delete an existing LeaveRequest model.
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
     * Delete multiple existing LeaveRequest model.
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
     * Finds the LeaveRequest model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return LeaveRequest the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = LeaveRequest::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionApprove($id)
    {
        $model = $this->findModel($id);
        $model->status = 'approved';
        $model->action_by = Yii::$app->user->id;
        if (!$model->save()) {
            var_dump($model->errors);
            die;
        }

        return $this->redirect(['index']);
    }

    public function actionRejecte($id)
    {
        $model = $this->findModel($id);
        $model->status = 'rejected';
        $model->action_by = Yii::$app->user->id;
        if (!$model->save()) {
            var_dump($model->errors);
            die;
        }
        return $this->redirect(['index']);
    }

    public function actionNumberDate()
    {
        $leavePolicy = Yii::$app->request->post('leavePolicy');
        if (!empty($leavePolicy)) {
            $date_of_hire = Yii::$app->user->identity['date_of_hire'];
            $userID = Yii::$app->user->identity['id'];
            $connection = Yii::$app->getDb();
            $sql = "SELECT SUM(DATEDIFF(`end_at`, `start_at`)) AS 'Result' FROM {{%leave_request}} WHERE status = 'approved' AND proved_at > $date_of_hire 
AND created_by = $userID  AND leave_policy = $leavePolicy
;";
            $connection = Yii::$app->getDb();
            $command = $connection->createCommand($sql);
            $result = $command->queryAll();
            $discountedDays = $result[0]['Result'];
            $allDate = LeavePolicy::findOne(['id' => $leavePolicy]);
            $allDate = $allDate->total_days;
            $remainingDays = $allDate - $discountedDays;

            if ($result[0]['Result'] == 0) {
                return $allDate;
            }
            if ($remainingDays < 0) {
                return 0;
            }
            return $remainingDays;
        } else {
            return 0;
        }

    }

    public function actionSuspendedVacations()
    {
        $searchModel = new LeaveRequestSearch();
        $dataProvider = $searchModel->searchSuspendedVacations(Yii::$app->request->queryParams);

        return $this->render('suspended_vacations', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionAproved()
    {
        $id = Yii::$app->request->post('id');
        if (LeaveRequest::updateAll(['status' => 'approved', 'proved_at' => time()], ['id' => $id])) {
            return 1;
        }

    }public function actionReject()
    {
        $id = Yii::$app->request->post('id');
        if (LeaveRequest::updateAll(['status' => 'rejected'], ['id' => $id])) {
            return 1;
        }

    }
}
