<?php

namespace backend\modules\lawyers\controllers;

use Yii;
use backend\modules\lawyers\models\Lawyers;
use backend\modules\lawyers\models\LawyersSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;
use backend\modules\LawyersImage\models\LawyersImage;
use backend\helpers\ExportTrait;

/**
 * LawyersController implements the CRUD actions for Lawyers model.
 */
class LawyersController extends Controller
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
                        'actions' => ['logout', 'index','update','create','delete','view','export-excel','export-pdf','delete-photo'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                    'delete-photo' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Lawyers models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new LawyersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $searchCounter = $searchModel->searchCounter(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'searchCounter'=>$searchCounter
        ]);
    }

    /**
     * Displays a single Lawyers model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "عرض #" . $id,
                'content' => $this->renderAjax('view', [
                    'model' => $this->findModel($id),
                ]),
                'footer' => Html::button('إغلاق', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                    Html::a('تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
            ];
        } else {
            return $this->render('view', [
                'model' => $this->findModel($id),
            ]);
        }
    }

    /**
     * Creates a new Lawyers model.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new Lawyers();

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "إضافة مفوض / وكيل",
                    'content' => $this->renderAjax('create', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            } else if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => "إضافة مفوض / وكيل",
                    'content' => '<span class="text-success">تمت الإضافة بنجاح</span>',
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                        Html::a('إضافة آخر', ['create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
                ];
            } else {
                return [
                    'title' => "إضافة مفوض / وكيل",
                    'content' => $this->renderAjax('create', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            }
        } else {
            if ($model->load($request->post()) && $model->save()) {
                $this->handleSignatureUpload($model);
                $model->uploadeMultipleImag($model);
                Yii::$app->cache->set(
                    Yii::$app->params['key_lawyer'],
                    Yii::$app->db->createCommand(Yii::$app->params['lawyer_query']),
                    Yii::$app->params['time_duration']
                );

                return $this->redirect(['index']);
            } else {
                return $this->render('create', [
                    'model' => $model,
                ]);
            }
        }
    }

    /**
     * Updates an existing Lawyers model.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "تعديل #" . $id,
                    'content' => $this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            } else if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => "#" . $id,
                    'content' => $this->renderAjax('view', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                        Html::a('تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
                ];
            } else {
                return [
                    'title' => "تعديل #" . $id,
                    'content' => $this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-secondary pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            }
        } else {
            if ($model->load($request->post()) && $model->save()) {
                $this->handleSignatureUpload($model);

                $connection = Yii::$app->getDb();
                $connection->createCommand("DELETE FROM `os_lawyers_image` WHERE `lawyer_id`= " . (int)$model->id)->execute();
                $model->uploadeMultipleImag($model);

                Yii::$app->cache->set(
                    Yii::$app->params['key_lawyer'],
                    Yii::$app->db->createCommand(Yii::$app->params['lawyer_query']),
                    Yii::$app->params['time_duration']
                );

                return $this->redirect(['index']);
            } else {
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
        }
    }

    /**
     * Delete an existing Lawyers model.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $request = Yii::$app->request;
        $this->findModel($id)->delete();

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        } else {
            return $this->redirect(['index']);
        }
    }

    /**
     * Delete multiple existing Lawyers model.
     * @return mixed
     */
    public function actionBulkDelete()
    {
        $request = Yii::$app->request;
        $pks = explode(',', $request->post('pks'));
        foreach ($pks as $pk) {
            $model = $this->findModel($pk);
            $model->delete();
        }

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        } else {
            return $this->redirect(['index']);
        }
    }

    /**
     * Delete a single LawyersImage photo via AJAX.
     */
    public function actionDeletePhoto()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = Yii::$app->request->post('id');
        $image = LawyersImage::findOne($id);
        if ($image) {
            $filePath = Yii::getAlias('@backend/web/') . $image->image;
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            $image->delete();
            return ['success' => true];
        }
        return ['success' => false];
    }

    public function actionExportExcel()
    {
        $searchModel = new LawyersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'المفوضين والوكلاء',
            'filename' => 'representatives',
            'headers' => ['#', 'الاسم', 'النوع', 'رقم الهاتف', 'الحالة', 'أنشئ بواسطة'],
            'keys' => [
                '#',
                'name',
                function ($model) {
                    return ($model->representative_type === Lawyers::REP_TYPE_LAWYER) ? 'وكيل محامي' : 'مفوض عادي';
                },
                'phone_number',
                function ($model) { return ($model->status == 0) ? 'نشط' : 'غير نشط'; },
                'createdBy.username',
            ],
            'widths' => [8, 25, 18, 20, 12, 20],
        ]);
    }

    public function actionExportPdf()
    {
        $searchModel = new LawyersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'المفوضين والوكلاء',
            'filename' => 'representatives',
            'headers' => ['#', 'الاسم', 'النوع', 'رقم الهاتف', 'الحالة', 'أنشئ بواسطة'],
            'keys' => [
                '#',
                'name',
                function ($model) {
                    return ($model->representative_type === Lawyers::REP_TYPE_LAWYER) ? 'وكيل محامي' : 'مفوض عادي';
                },
                'phone_number',
                function ($model) { return ($model->status == 0) ? 'نشط' : 'غير نشط'; },
                'createdBy.username',
            ],
        ], 'pdf');
    }

    /**
     * Handle signature PNG file upload for a lawyer/representative.
     */
    protected function handleSignatureUpload(Lawyers $model)
    {
        $signatureFile = UploadedFile::getInstanceByName('signature_file');

        if ($model->signature_image === '__removed__') {
            if ($model->getOldAttribute('signature_image')) {
                $oldPath = Yii::getAlias('@backend/web/') . $model->getOldAttribute('signature_image');
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $model->signature_image = null;
            $model->save(false, ['signature_image']);
            return;
        }

        if ($signatureFile) {
            $signatureDir = Yii::getAlias('@backend/web/uploads/lawyers/signatures');
            if (!is_dir($signatureDir)) {
                FileHelper::createDirectory($signatureDir, 0777);
            }

            if ($model->getOldAttribute('signature_image')) {
                $oldPath = Yii::getAlias('@backend/web/') . $model->getOldAttribute('signature_image');
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $signatureName = 'sig_' . $model->id . '_' . time() . '.png';
            if ($signatureFile->saveAs($signatureDir . DIRECTORY_SEPARATOR . $signatureName)) {
                $model->signature_image = 'uploads/lawyers/signatures/' . $signatureName;
                $model->save(false, ['signature_image']);
            }
        }
    }

    /**
     * Finds the Lawyers model based on its primary key value.
     * @param integer $id
     * @return Lawyers the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Lawyers::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}
