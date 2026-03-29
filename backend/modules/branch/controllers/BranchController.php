<?php

namespace backend\modules\branch\controllers;

use Yii;
use backend\modules\branch\models\Branch;
use backend\modules\branch\models\BranchSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\helpers\Html;
use yii\filters\AccessControl;
use backend\helpers\ExportTrait;

class BranchController extends Controller
{
    use ExportTrait;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'bulk-delete' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new BranchSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'فرع: ' . $model->name,
                'content' => $this->renderAjax('view', ['model' => $model]),
                'footer' => Html::button('إغلاق', ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal']) .
                    Html::a('تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
            ];
        }

        return $this->render('view', ['model' => $model]);
    }

    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new Branch();

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => 'إضافة فرع جديد',
                    'content' => $this->renderAjax('create', ['model' => $model]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal']) .
                        Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            } elseif ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => 'إضافة فرع جديد',
                    'content' => '<span class="text-success">تم إنشاء الفرع بنجاح</span>',
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal']) .
                        Html::a('إضافة آخر', ['create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
                ];
            } else {
                return [
                    'title' => 'إضافة فرع جديد',
                    'content' => $this->renderAjax('create', ['model' => $model]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal']) .
                        Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            }
        }

        if ($model->load($request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => 'تعديل: ' . $model->name,
                    'content' => $this->renderAjax('update', ['model' => $model]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal']) .
                        Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            } elseif ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => 'فرع: ' . $model->name,
                    'content' => $this->renderAjax('view', ['model' => $model]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal']) .
                        Html::a('تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
                ];
            } else {
                return [
                    'title' => 'تعديل: ' . $model->name,
                    'content' => $this->renderAjax('update', ['model' => $model]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-secondary', 'data-bs-dismiss' => 'modal']) .
                        Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            }
        }

        if ($model->load($request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        $request = Yii::$app->request;
        $this->findModel($id)->delete();

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }

        return $this->redirect(['index']);
    }

    public function actionBulkDelete()
    {
        $request = Yii::$app->request;
        $pks = explode(',', $request->post('pks'));
        foreach ($pks as $pk) {
            $this->findModel($pk)->delete();
        }

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }

        return $this->redirect(['index']);
    }

    public function actionExportExcel()
    {
        $searchModel = new BranchSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'الفروع',
            'filename' => 'branches',
            'headers' => ['#', 'الكود', 'اسم الفرع', 'النوع', 'العنوان', 'الهاتف', 'الحالة'],
            'keys' => ['#', 'code', 'name', function ($model) {
                return $model->getTypeLabel();
            }, 'address', 'phone', function ($model) {
                return $model->is_active ? 'فعّال' : 'معطّل';
            }],
            'widths' => [8, 12, 25, 15, 30, 15, 10],
        ]);
    }

    public function actionExportPdf()
    {
        $searchModel = new BranchSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title' => 'الفروع',
            'filename' => 'branches',
            'headers' => ['#', 'الكود', 'اسم الفرع', 'النوع', 'العنوان', 'الهاتف', 'الحالة'],
            'keys' => ['#', 'code', 'name', function ($model) {
                return $model->getTypeLabel();
            }, 'address', 'phone', function ($model) {
                return $model->is_active ? 'فعّال' : 'معطّل';
            }],
        ], 'pdf');
    }

    protected function findModel($id)
    {
        if (($model = Branch::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة.');
    }
}
