<?php

namespace backend\modules\movment\controllers;

use Yii;
use backend\modules\movment\models\Movment;
use backend\modules\movment\models\MovmentSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use yii\web\UploadedFile;
use backend\helpers\ExportTrait;
use common\services\media\MediaContext;

/**
 * MovmentController implements the CRUD actions for Movment model.
 */
class MovmentController extends Controller
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
                        'actions' => ['logout', 'index','update','create','delete','view','export-excel','export-pdf'],
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
     * Lists all Movment models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new \backend\modules\movment\models\MovmentSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }


    /**
     * Displays a single Movment model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "Movment #" . $id,
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
     * Creates a new Movment model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new Movment();

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "Create new Movment",
                    'content' => $this->renderAjax('create', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            } else if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => "Create new Movment",
                    'content' => '<span class="text-success">Create Movment success</span>',
                    'footer' => Html::button('Close', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                        Html::a('Create More', ['create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])

                ];
            } else {
                return [
                    'title' => "Create new Movment",
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
            if ($model->load($request->post())) {
                $model->user_id = Yii::$app->user->id;
                // Save the row first so we have a numeric id to associate the
                // receipt media with via MediaService::store().
                if (!$model->save()) {
                    return $this->render('create', [
                        'model' => $model,
                    ]);
                }

                // ── Receipt upload via the unified MediaService ────────
                // Replaces the original `saveAs('images/' . …)` path,
                // which was CWD-relative (it broke whenever the worker
                // process happened to be running from a directory other
                // than `backend/web/`). MediaService writes to the same
                // /images/imagemanager/ tree the rest of the system reads
                // from, returns a stable URL, and dedupes against
                // double-clicks of the upload button.
                $upload = UploadedFile::getInstance($model, 'receipt_image');
                if ($upload !== null) {
                    try {
                        $result = Yii::$app->media->store(
                            $upload,
                            MediaContext::forMovement((int)$model->id)
                        );
                        // Store the public URL in the existing varchar
                        // column so legacy readers keep working. Phase 5
                        // backfill will fold this into a media_id link.
                        $model->receipt_image = $result->url;
                        $model->save(false);
                    } catch (\Throwable $e) {
                        Yii::error('MovmentController: receipt upload failed: ' . $e->getMessage(), __METHOD__);
                        Yii::$app->session->setFlash('error', 'فشل رفع إيصال الحركة: ' . $e->getMessage());
                    }
                }

                $searchModel = new MovmentSearch();
                $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
                return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
                ]);
            } else {
                return $this->render('create', [
                    'model' => $model,
                ]);
            }
        }

    }

    /**
     * Updates an existing Movment model.
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
                    'title' => "Update Movment #" . $id,
                    'content' => $this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            } else if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => "Movment #" . $id,
                    'content' => $this->renderAjax('view', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                        Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
                ];
            } else {
                return [
                    'title' => "Update Movment #" . $id,
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
                // Same migration as actionCreate(): the receipt now flows
                // through MediaService instead of the broken CWD-relative
                // saveAs(). On update we KEEP the existing receipt URL
                // when no new file is uploaded.
                $upload = UploadedFile::getInstance($model, 'receipt_image');
                if ($upload !== null) {
                    try {
                        $result = Yii::$app->media->store(
                            $upload,
                            MediaContext::forMovement((int)$model->id)
                        );
                        $model->receipt_image = $result->url;
                    } catch (\Throwable $e) {
                        Yii::error('MovmentController: receipt update upload failed: ' . $e->getMessage(), __METHOD__);
                        Yii::$app->session->setFlash('error', 'فشل رفع إيصال الحركة: ' . $e->getMessage());
                    }
                }
                $model->user_id = Yii::$app->user->id;

                if (!$model->save()) {
                    return $this->render('create', [
                        'model' => $model,
                    ]);
                } else {
                    $searchModel = new MovmentSearch();
                    $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

                    return $this->render('index', [
                        'searchModel' => $searchModel,
                        'dataProvider' => $dataProvider,
                    ]);
                }

            } else {
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
        }
    }

    /**
     * Delete an existing Movment model.
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
     * Delete multiple existing Movment model.
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
        $searchModel = new \backend\modules\movment\models\MovmentSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'الحركات',
            'filename' => 'movments',
            'headers' => ['#', 'المستخدم', 'رقم الحركة', 'رقم إيصال البنك', 'القيمة المالية', 'صورة الإيصال'],
            'keys' => ['#', 'user.username', 'movement_number', 'bank_receipt_number', 'financial_value', 'receipt_image'],
            'widths' => [8, 20, 18, 20, 18, 25],
        ]);
    }

    public function actionExportPdf()
    {
        $searchModel = new \backend\modules\movment\models\MovmentSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'الحركات',
            'filename' => 'movments',
            'headers' => ['#', 'المستخدم', 'رقم الحركة', 'رقم إيصال البنك', 'القيمة المالية', 'صورة الإيصال'],
            'keys' => ['#', 'user.username', 'movement_number', 'bank_receipt_number', 'financial_value', 'receipt_image'],
        ], 'pdf');
    }

    /**
     * Finds the Movment model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Movment the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Movment::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
