<?php

namespace backend\modules\designation\controllers;

use Yii;
use backend\modules\designation\models\Designation;
use backend\modules\designation\models\DesignationSearch;
use backend\modules\department\models\Department;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;
use yii\db\Query;

/**
 * DesignationController implements the CRUD actions for Designation model.
 */
class DesignationController extends Controller
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
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index','update','create','delete','seed-defaults','quick-add-department','quick-add-designation','ajax-delete','reset-all','auto-link'],
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
     * Lists all Designation models.
     * @return mixed
     */
    public function actionIndex()
    {
        Designation::ensureDepartmentColumn();

        $departments = Department::find()->where(['status' => 'active'])->orderBy(['title' => SORT_ASC])->all();
        $designations = (new Query())
            ->select(['d.*', 'dep.title as department_name'])
            ->from('{{%designation}} d')
            ->leftJoin('{{%department}} dep', 'dep.id = d.department_id')
            ->where(['d.status' => 'active'])
            ->orderBy(['dep.title' => SORT_ASC, 'd.title' => SORT_ASC])
            ->all();

        // عدد الموظفين لكل مسمى
        $desigCounts = (new Query())
            ->select(['job_title', 'COUNT(*) as cnt'])
            ->from('{{%user}}')
            ->where(['not', ['job_title' => null]])
            ->groupBy('job_title')
            ->indexBy('job_title')
            ->column();

        return $this->render('index', [
            'departments' => $departments,
            'designations' => $designations,
            'desigCounts' => $desigCounts,
        ]);
    }


    /**
     * Displays a single Designation model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {   
        $request = Yii::$app->request;
        if($request->isAjax){
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                    'title'=> "Designation #".$id,
                    'content'=>$this->renderAjax('view', [
                        'model' => $this->findModel($id),
                    ]),
                    'footer'=> Html::button('Close',['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                            Html::a('Edit',['update','id'=>$id],['class'=>'btn btn-primary','role'=>'modal-remote'])
                ];    
        }else{
            return $this->render('view', [
                'model' => $this->findModel($id),
            ]);
        }
    }

    /**
     * Creates a new Designation model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new Designation();  

        if($request->isAjax){
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if($request->isGet){
                return [
                    'title'=> "Create new Designation",
                    'content'=>$this->renderAjax('create', [
                        'model' => $model,
                    ]),
                    'footer'=> Html::button('Close',['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                                Html::button('Save',['class'=>'btn btn-primary','type'=>"submit"])
        
                ];         
            }else if($model->load($request->post()) && $model->save()){
                return [
                    'forceReload'=>'#crud-datatable-pjax',
                    'title'=> "Create new Designation",
                    'content'=>'<span class="text-success">Create Designation success</span>',
                    'footer'=> Html::button('Close',['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                            Html::a('Create More',['create'],['class'=>'btn btn-primary','role'=>'modal-remote'])
        
                ];         
            }else{           
                return [
                    'title'=> "Create new Designation",
                    'content'=>$this->renderAjax('create', [
                        'model' => $model,
                    ]),
                    'footer'=> Html::button('Close',['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                                Html::button('Save',['class'=>'btn btn-primary','type'=>"submit"])
        
                ];         
            }
        }else{
            /*
            *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return $this->render('create', [
                    'model' => $model,
                ]);
            }
        }
       
    }

    /**
     * Updates an existing Designation model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);       

        if($request->isAjax){
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if($request->isGet){
                return [
                    'title'=> "Update Designation #".$id,
                    'content'=>$this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer'=> Html::button('Close',['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                                Html::button('Save',['class'=>'btn btn-primary','type'=>"submit"])
                ];         
            }else if($model->load($request->post()) && $model->save()){
                return [
                    'forceReload'=>'#crud-datatable-pjax',
                    'title'=> "Designation #".$id,
                    'content'=>$this->renderAjax('view', [
                        'model' => $model,
                    ]),
                    'footer'=> Html::button('Close',['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                            Html::a('Edit',['update','id'=>$id],['class'=>'btn btn-primary','role'=>'modal-remote'])
                ];    
            }else{
                 return [
                    'title'=> "Update Designation #".$id,
                    'content'=>$this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer'=> Html::button('Close',['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                                Html::button('Save',['class'=>'btn btn-primary','type'=>"submit"])
                ];        
            }
        }else{
            /*
            *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
        }
    }

    /**
     * Delete an existing Designation model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $request = Yii::$app->request;
        $this->findModel($id)->delete();

        if($request->isAjax){
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose'=>true,'forceReload'=>'#crud-datatable-pjax'];
        }else{
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }


    }

     /**
     * Delete multiple existing Designation model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionBulkDelete()
    {        
        $request = Yii::$app->request;
        $pks = explode(',', $request->post( 'pks' )); // Array or selected records primary keys
        foreach ( $pks as $pk ) {
            $model = $this->findModel($pk);
            $model->delete();
        }

        if($request->isAjax){
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose'=>true,'forceReload'=>'#crud-datatable-pjax'];
        }else{
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }
       
    }

    /**
     * Finds the Designation model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Designation the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Designation::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionSeedDefaults()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Designation::ensureDepartmentColumn();

        // الأقسام الافتراضية
        $defaultDepts = [
            'الإدارة العامة', 'المبيعات', 'المالية', 'المتابعة والتحصيل',
            'القسم القانوني', 'المخزون', 'الموارد البشرية',
        ];

        $deptCreated = 0;
        $deptMap = []; // title => id
        foreach ($defaultDepts as $deptTitle) {
            $dept = Department::find()->where(['title' => $deptTitle])->one();
            if (!$dept) {
                $dept = new Department();
                $dept->title = $deptTitle;
                $dept->status = 'active';
                $dept->created_by = Yii::$app->user->id;
                $dept->created_at = time();
                if ($dept->save(false)) $deptCreated++;
            }
            $deptMap[$deptTitle] = $dept->id;
        }

        // توحيد أسماء قديمة → جديدة (قبل إدراج الافتراضيات)
        Yii::$app->db->createCommand()->update('{{%designation}}', ['title' => 'موظف مبيعات'], ['title' => 'مندوب مبيعات'])->execute();
        Yii::$app->db->createCommand()->update('{{%designation}}', ['title' => 'مورّد أجهزة'], ['title' => 'موزع أجهزة'])->execute();

        // المسميات الوظيفية مع ربطها بالأقسام
        $defaults = [
            'مدير عام'           => 'الإدارة العامة',
            'مدير مبيعات'        => 'المبيعات',
            'محاسب'              => 'المالية',
            'موظف متابعة'        => 'المتابعة والتحصيل',
            'محامي'              => 'القسم القانوني',
            'موظف مبيعات'        => 'المبيعات',
            'مندوب محكمة'        => 'القسم القانوني',
            'مورّد أجهزة'        => 'المخزون',
            'مدير فرع'           => 'الإدارة العامة',
            'أمين مخزن'          => 'المخزون',
            'مدير مالي'          => 'المالية',
            'موظف استقبال'       => 'الإدارة العامة',
            'مسؤول موارد بشرية'  => 'الموارد البشرية',
        ];

        $desigCreated = 0;
        $desigUpdated = 0;
        $desigSkipped = 0;
        $now = time();
        $userId = Yii::$app->user->id;

        foreach ($defaults as $title => $deptName) {
            $deptId = $deptMap[$deptName] ?? null;
            $existing = (new Query())->from('{{%designation}}')->where(['title' => $title])->one();
            if (!$existing) {
                $insertData = [
                    'title' => $title,
                    'status' => 'active',
                    'created_by' => $userId,
                    'created_at' => $now,
                ];
                if ($deptId) $insertData['department_id'] = $deptId;
                try {
                    Yii::$app->db->createCommand()->insert('{{%designation}}', $insertData)->execute();
                    $desigCreated++;
                } catch (\Exception $e) {
                    // محاولة بديلة: إدراج بدون department_id (مثلاً إن كان العمود غير موجود على السيرفر)
                    Yii::warning("إنشاء مسمى '{$title}' (مع القسم): " . $e->getMessage(), __METHOD__);
                    unset($insertData['department_id']);
                    try {
                        Yii::$app->db->createCommand()->insert('{{%designation}}', $insertData)->execute();
                        $desigCreated++;
                        if ($deptId) {
                            try {
                                $newId = (int) Yii::$app->db->getLastInsertID();
                                Yii::$app->db->createCommand()->update('{{%designation}}', ['department_id' => $deptId], ['id' => $newId])->execute();
                            } catch (\Exception $eUpdate) {
                                // العمود قد يكون غير موجود؛ المسمى أُنشئ بنجاح
                            }
                        }
                    } catch (\Exception $e2) {
                        Yii::warning("فشل إنشاء مسمى '{$title}': " . $e2->getMessage(), __METHOD__);
                    }
                }
            } else {
                $existingDeptId = $existing['department_id'] ?? null;
                if (!$existingDeptId && $deptId) {
                    try {
                        Yii::$app->db->createCommand()->update('{{%designation}}', ['department_id' => $deptId], ['id' => $existing['id']])->execute();
                        $desigUpdated++;
                    } catch (\Exception $e) {}
                } else {
                    $desigSkipped++;
                }
            }
        }

        // فئات المستخدمين
        \backend\models\UserCategory::ensureTablesExist();
        $catCreated = \backend\models\UserCategory::seedDefaults();

        return [
            'success' => true,
            'message' => "تم إنشاء {$deptCreated} قسم، {$desigCreated} مسمى وظيفي"
                . ($desigUpdated ? "، وتحديث ربط {$desigUpdated} مسمى بأقسامها" : '')
                . "، و{$catCreated} فئة مستخدم."
                . ($desigSkipped ? " تم تجاوز {$desigSkipped} موجود مسبقاً." : ''),
        ];
    }

    /**
     * إضافة قسم سريعة (AJAX)
     */
    public function actionQuickAddDepartment()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $title = trim(Yii::$app->request->post('title', ''));
        if ($title === '') return ['success' => false, 'message' => 'اسم القسم مطلوب'];

        if (Department::find()->where(['title' => $title])->exists()) {
            return ['success' => false, 'message' => 'القسم موجود مسبقاً'];
        }

        $dept = new Department();
        $dept->title = $title;
        $dept->status = 'active';
        $dept->created_by = Yii::$app->user->id;
        $dept->created_at = time();
        if ($dept->save(false)) {
            return ['success' => true, 'id' => $dept->id, 'name' => $dept->title, 'message' => 'تم إضافة القسم'];
        }
        return ['success' => false, 'message' => 'فشل الحفظ'];
    }

    /**
     * إضافة مسمى وظيفي سريعة (AJAX)
     */
    public function actionQuickAddDesignation()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $title = trim(Yii::$app->request->post('title', ''));
        $deptId = Yii::$app->request->post('department_id') ?: null;
        if ($title === '') return ['success' => false, 'message' => 'اسم المسمى مطلوب'];

        if (Designation::find()->where(['title' => $title])->exists()) {
            return ['success' => false, 'message' => 'المسمى موجود مسبقاً'];
        }

        Designation::ensureDepartmentColumn();
        $d = new Designation();
        $d->title = $title;
        $d->department_id = $deptId ? (int)$deptId : null;
        $d->status = 'active';
        $d->created_by = Yii::$app->user->id;
        if ($d->save(false)) {
            return ['success' => true, 'id' => $d->id, 'name' => $d->title, 'message' => 'تم إضافة المسمى الوظيفي'];
        }
        return ['success' => false, 'message' => 'فشل الحفظ'];
    }

    /**
     * حذف فردي/جماعي (AJAX)
     */
    public function actionAjaxDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $type = Yii::$app->request->post('type');
        $ids = Yii::$app->request->post('ids', []);
        if (empty($ids)) return ['success' => false, 'message' => 'لم يتم تحديد عناصر'];

        $ids = array_map('intval', $ids);
        $deleted = 0;

        try {
            if ($type === 'dept') {
                // إلغاء ربط المسميات بهذه الأقسام قبل الحذف
                Yii::$app->db->createCommand()->update('{{%designation}}', ['department_id' => null], ['department_id' => $ids])->execute();
                // إلغاء ربط المستخدمين بهذه الأقسام
                Yii::$app->db->createCommand()->update('{{%user}}', ['department' => null], ['department' => $ids])->execute();
                $deleted = Yii::$app->db->createCommand()->delete('{{%department}}', ['id' => $ids])->execute();
            } elseif ($type === 'desig') {
                // إلغاء ربط المستخدمين بهذه المسميات
                Yii::$app->db->createCommand()->update('{{%user}}', ['job_title' => null], ['job_title' => $ids])->execute();
                $deleted = Yii::$app->db->createCommand()->delete('{{%designation}}', ['id' => $ids])->execute();
            }
            return ['success' => true, 'message' => "تم حذف {$deleted} عنصر"];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'خطأ: ' . $e->getMessage()];
        }
    }

    /**
     * إعادة تعيين — حذف جميع الأقسام والمسميات
     */
    public function actionResetAll()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            // إلغاء الربط أولاً
            Yii::$app->db->createCommand()->update('{{%user}}', ['job_title' => null, 'department' => null], ['!=', 'id', 0])->execute();

            Designation::ensureDepartmentColumn();
            Yii::$app->db->createCommand()->update('{{%designation}}', ['department_id' => null], ['!=', 'id', 0])->execute();

            $delDesig = Yii::$app->db->createCommand()->delete('{{%designation}}')->execute();
            $delDept = Yii::$app->db->createCommand()->delete('{{%department}}')->execute();

            return [
                'success' => true,
                'message' => "تم حذف {$delDesig} مسمى وظيفي و{$delDept} قسم. تم إلغاء ربط جميع المستخدمين.",
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'خطأ: ' . $e->getMessage()];
        }
    }

    /**
     * ربط تلقائي — ربط المسميات بالأقسام حسب الخريطة الافتراضية
     */
    public function actionAutoLink()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Designation::ensureDepartmentColumn();

        $linkMap = [
            'مدير عام'           => 'الإدارة العامة',
            'مدير مبيعات'        => 'المبيعات',
            'محاسب'              => 'المالية',
            'موظف متابعة'        => 'المتابعة والتحصيل',
            'محامي'              => 'القسم القانوني',
            'موظف مبيعات'        => 'المبيعات',
            'مندوب محكمة'        => 'القسم القانوني',
            'مورّد أجهزة'        => 'المخزون',
            'مدير فرع'           => 'الإدارة العامة',
            'أمين مخزن'          => 'المخزون',
            'مدير مالي'          => 'المالية',
            'موظف استقبال'       => 'الإدارة العامة',
            'مسؤول موارد بشرية'  => 'الموارد البشرية',
        ];

        // جلب الأقسام الموجودة
        $deptMap = ArrayHelper::map(
            Department::find()->where(['status' => 'active'])->all(),
            'title', 'id'
        );

        $linked = 0;
        foreach ($linkMap as $desigTitle => $deptTitle) {
            $deptId = $deptMap[$deptTitle] ?? null;
            if (!$deptId) continue;

            $updated = Yii::$app->db->createCommand()->update(
                '{{%designation}}',
                ['department_id' => $deptId],
                ['title' => $desigTitle]
            )->execute();
            $linked += $updated;
        }

        return [
            'success' => true,
            'message' => "تم ربط {$linked} مسمى وظيفي بأقسامها تلقائياً.",
        ];
    }
}
