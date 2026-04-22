<?php

namespace backend\modules\employee\controllers;

use backend\modules\employee\models\EmployeeFiles;
use Yii;
use backend\models\Employee;
use backend\models\EmployeeSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use backend\modules\userLeavePolicy\models\UserLeavePolicy;
use backend\modules\leaveRequest\models\LeaveRequest;
use backend\modules\leavePolicy\models\LeavePolicy;
use yii\filters\AccessControl;
use yii\web\UploadedFile;
use common\helper\Permissions;
use backend\helpers\ExportTrait;
use common\services\media\MediaContext;


/**
 * EmployeeController implements the CRUD actions for Employee model.
 */
class EmployeeController extends Controller
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
                        'actions' => ['index', 'view', 'is_read', 'export-excel', 'export-pdf'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::EMP_VIEW);
                        },
                    ],
                    [
                        'actions' => ['create'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::EMP_CREATE);
                        },
                    ],
                    [
                        'actions' => ['update', 'employee-leave-policy', 'remove-file'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::EMP_UPDATE);
                        },
                    ],
                    [
                        'actions' => ['delete'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::EMP_DELETE);
                        },
                    ],
                    [
                        'actions' => ['logout'],
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
     * Lists all Employee models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new EmployeeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionExportExcel()
    {
        $searchModel = new EmployeeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title'    => 'الموظفين',
            'filename' => 'employees',
            'headers'  => ['#', 'اسم المستخدم', 'البريد الإلكتروني'],
            'keys'     => ['#', 'username', 'email'],
            'widths'   => [8, 30, 35],
        ], 'excel');
    }

    public function actionExportPdf()
    {
        $searchModel = new EmployeeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, [
            'title'    => 'الموظفين',
            'filename' => 'employees',
            'headers'  => ['#', 'اسم المستخدم', 'البريد الإلكتروني'],
            'keys'     => ['#', 'username', 'email'],
        ], 'pdf');
    }

    /**
     * Displays a single Employee model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "Employee #" . $id,
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
     * Creates a new Employee model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new Employee();
        $model->setScenario('create');

        if ($model->load($request->post())) {
            $model->profile_avatar_file = UploadedFile::getInstances($model, 'profile_avatar_file');
            $model->profile_attachment_files = UploadedFile::getInstances($model, 'profile_attachment_files');

            // ── تفعيل الحساب تلقائياً عند الإنشاء ──
            // password_hash يتم تشفيره في beforeSave فلا نشفّره هنا مرتين
            $model->confirmed_at = time();          // تفعيل فوري
            $model->created_at   = time();
            $model->updated_at   = time();
            $model->created_by   = Yii::$app->user->id ?? 1;

            if ($model->save()) {
                $this->adoptUploadedAvatar($model);
                if (!empty($model->profile_avatar_file)) {
                    $this->saveEmployeeAvatar($model);
                }
                if (!empty($model->profile_attachment_files)) {
                    $this->saveEmployeeAttachments($model);
                }
                Yii::$app->session->setFlash('success', 'تم إنشاء حساب الموظف وتفعيله بنجاح.');
                return $this->redirect(['index']);
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Employee model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id, $imageID = 0)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);
        $model->setScenario('update');


        $employeeAttachments = EmployeeFiles::find()->where(['user_id' => $id, 'type' => EmployeeFiles::TYPE_ATTACHMENT])->all();
        $oldPasswordHash = $model->password_hash;
        if ($model->load($request->post())) {
            if (!empty($model->password_hash) && $model->password_hash !== $oldPasswordHash) {
                $model->password_hash = Yii::$app->security->generatePasswordHash($model->password_hash);
            } else {
                $model->password_hash = $oldPasswordHash;
            }
            if ($model->save()) {
                $model->profile_avatar_file = UploadedFile::getInstances($model, 'profile_avatar_file');
                $model->profile_attachment_files = UploadedFile::getInstances($model, 'profile_attachment_files');
                $this->adoptUploadedAvatar($model);
                if (!empty($model->profile_avatar_file)) {
                    $this->saveEmployeeAvatar($model);
                }
                if (!empty($model->profile_attachment_files)) {
                    $this->saveEmployeeAttachments($model);
                }
                return $this->redirect(['index']);
            }
        }

        return $this->render('update', [
            'employeeAttachments' => $employeeAttachments,
            'model' => $model,
            'id' => $id
        ]);
    }

    /**
     * Updates an existing Employee model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionEmployeeLeavePolicy($id)
    {
        $request = Yii::$app->request->post('Employee');
        UserLeavePolicy::deleteAll(['user_id' => $id]);
        foreach ($request['leavePolicy'] as $key => $value) {
            $model = new UserLeavePolicy();
            $model->user_id = $id;
            $model->leave_policy_id = $value;
            $model->save();
        }
        return $this->redirect(['update', 'id' => $id]);
    }

    /**
     * Delete an existing Employee model.
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
     * Delete multiple existing Employee model.
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
     * Finds the Employee model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Employee the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Employee::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionLeavePolicyRemaining($policy_id)
    {
        $leaveRequestSum = LeaveRequest::find()
            ->where(['leave_policy' => $policy_id, 'created_by' => Yii::$app->user->id])
            ->andWhere(['or',
                ['status' => 'under review'],
                ['status' => 'approved']
            ])
            ->sum('DATEDIFF(`end_at`,`start_at`)');
        $leaveRequestDays = LeavePolicy::find()->select('total_days')->where(['id' => $policy_id])->one();
        return ($leaveRequestDays->total_days -
            $leaveRequestSum);
    }

    public function actionLeavePolicyRemainingWithoutTheUnderReview($policy_id)
    {
        $leaveRequestSum = LeaveRequest::find()
            ->where(['leave_policy' => $policy_id, 'created_by' => Yii::$app->user->id])
            ->andWhere(['=',
                'status', 'approved'
            ])
            ->sum('DATEDIFF(`end_at`,`start_at`)');
        $leaveRequestDays = LeavePolicy::find()->select('total_days')->where(['id' => $policy_id])->one();
        return ($leaveRequestDays->total_days - $leaveRequestSum);
    }

    /**
     * Remove a single EmployeeFiles row + its underlying file.
     *
     * Migrated to MediaService (Phase 3.6):
     *   • Delegates the bytes deletion to MediaService when the row
     *     points at a unified URL (`/images/imagemanager/…`).
     *   • Falls back to direct unlink for legacy rows still on the
     *     `/images/employeeImage/…` layout. The pre-migration code
     *     never deleted the file at all (DB-only delete leaving an
     *     orphan on disk) — this fixes that long-standing bug.
     */
    public function actionRemoveFile()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = (int)Yii::$app->request->post('id');
        $row = EmployeeFiles::findOne($id);
        if (!$row) {
            return ['success' => false];
        }

        $mediaId = $this->extractMediaIdFromUrl((string)$row->path);
        if ($mediaId !== null) {
            try {
                Yii::$app->media->delete($mediaId, hardDelete: true);
            } catch (\Throwable $e) {
                Yii::warning('Employee actionRemoveFile MediaService delete failed: '
                    . $e->getMessage(), __METHOD__);
            }
        } else {
            $abs = Yii::getAlias('@backend/web') . '/' . ltrim($row->path, '/');
            if (is_file($abs)) {
                @unlink($abs);
            }
        }

        $row->delete();
        return ['success' => true];
    }

    /**
     * Save the freshly-uploaded avatar via the unified MediaService.
     *
     * Behaviour mirrors the legacy `Employee::updateProfileAvatar`:
     *   1. The previous avatar row (and its bytes) is removed.
     *   2. A new os_ImageManager row is inserted via MediaService.
     *   3. An EmployeeFiles row is created for back-compat with the
     *      `getProfileAvatar()` relation used throughout the views.
     *      Once Phase 5 finishes the side-table can be replaced with
     *      a database VIEW over os_ImageManager.
     */
    protected function saveEmployeeAvatar(Employee $model): bool
    {
        $files = $model->profile_avatar_file;
        if (!is_array($files) || empty($files) || !$files[0] instanceof UploadedFile) {
            return false;
        }
        /** @var UploadedFile $file */
        $file = $files[0];

        try {
            $ctx = MediaContext::forEmployee((int)$model->id, 'employee_avatar');
            $result = Yii::$app->media->store($file, $ctx);
        } catch (\Throwable $e) {
            Yii::error('Employee avatar upload failed: ' . $e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error',
                'تعذّر حفظ الصورة الشخصية: ' . $this->humaniseUploadError($e));
            return false;
        }

        // Drop the previous avatar AFTER the new one is safely stored so
        // a failure half-way never leaves the employee without an avatar.
        $this->dropPreviousEmployeeFiles((int)$model->id, EmployeeFiles::TYPE_AVATAR);

        $row = new EmployeeFiles();
        $row->user_id   = (int)$model->id;
        $row->type      = EmployeeFiles::TYPE_AVATAR;
        $row->file_name = $file->baseName;
        $row->path      = $result->url; // already an absolute URL path with leading slash
        return (bool)$row->save();
    }

    /**
     * Phase 6.2 — adopt an employee avatar uploaded async via the
     * unified MediaUploader (data-media-uploader). The form posts a
     * single id under Employee[adopted_avatar_id]. We claim it for
     * this employee, drop the previous avatar, and create a matching
     * EmployeeFiles row so the existing avatar relation keeps
     * resolving without a code change.
     */
    protected function adoptUploadedAvatar(Employee $model): bool
    {
        $body = (array)Yii::$app->request->post('Employee', []);
        $mediaId = (int)($body['adopted_avatar_id'] ?? 0);
        if ($mediaId <= 0) {
            return false;
        }

        if (!Yii::$app->media->adopt($mediaId, 'employee', (int)$model->id)) {
            Yii::warning("Employee adopt avatar #$mediaId failed for emp #{$model->id}", __METHOD__);
            return false;
        }

        try {
            $url = Yii::$app->media->url($mediaId);
        } catch (\Throwable $e) {
            Yii::error('Employee adopt avatar url() failed: ' . $e->getMessage(), __METHOD__);
            return false;
        }

        $this->dropPreviousEmployeeFiles((int)$model->id, EmployeeFiles::TYPE_AVATAR);

        $row = new EmployeeFiles();
        $row->user_id   = (int)$model->id;
        $row->type      = EmployeeFiles::TYPE_AVATAR;
        $row->file_name = basename(parse_url($url, PHP_URL_PATH) ?: 'avatar');
        $row->path      = $url;
        return (bool)$row->save();
    }

    /**
     * Save the multi-file attachments via the unified MediaService.
     *
     * Note: the pre-migration `Employee::addProfileAttachment` had a
     * `return true` inside the loop body which silently dropped every
     * attachment after the first. This implementation actually iterates
     * the full list — that bug-fix is intentional and called out here
     * because it changes observable behaviour (more rows survive).
     */
    protected function saveEmployeeAttachments(Employee $model): int
    {
        $files = $model->profile_attachment_files;
        if (!is_array($files) || empty($files)) {
            return 0;
        }

        $saved = 0;
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }
            try {
                $ctx = MediaContext::forEmployee((int)$model->id, 'employee_attachment');
                $result = Yii::$app->media->store($file, $ctx);
            } catch (\Throwable $e) {
                Yii::error('Employee attachment upload failed: ' . $e->getMessage(), __METHOD__);
                continue;
            }

            $row = new EmployeeFiles();
            $row->user_id   = (int)$model->id;
            $row->type      = EmployeeFiles::TYPE_ATTACHMENT;
            $row->file_name = $file->baseName;
            $row->path      = $result->url;
            if (!$row->save()) {
                Yii::error('EmployeeFiles save failed: '
                    . json_encode($row->getErrors(), JSON_UNESCAPED_UNICODE), __METHOD__);
                try {
                    Yii::$app->media->delete($result->id, hardDelete: true);
                } catch (\Throwable) {}
                continue;
            }
            $saved++;
        }
        return $saved;
    }

    /**
     * Cascade-delete previous EmployeeFiles rows of a given type
     * (and their underlying bytes when stored in the unified system).
     */
    private function dropPreviousEmployeeFiles(int $userId, int $type): void
    {
        $rows = EmployeeFiles::find()
            ->where(['user_id' => $userId, 'type' => $type])
            ->all();
        foreach ($rows as $row) {
            $mediaId = $this->extractMediaIdFromUrl((string)$row->path);
            if ($mediaId !== null) {
                try {
                    Yii::$app->media->delete($mediaId, hardDelete: true);
                } catch (\Throwable $e) {
                    Yii::warning('Employee previous avatar MediaService delete failed: '
                        . $e->getMessage(), __METHOD__);
                }
            } else {
                $abs = Yii::getAlias('@backend/web') . '/' . ltrim($row->path, '/');
                if (is_file($abs)) {
                    @unlink($abs);
                }
            }
            $row->delete();
        }
    }

    /**
     * Pull the os_ImageManager.id out of a unified MediaService URL
     * (e.g. `/images/imagemanager/123_aBc.png` → 123). Returns null
     * for legacy paths so callers can fall back to direct unlink.
     */
    private function extractMediaIdFromUrl(string $url): ?int
    {
        if ($url === '') return null;
        $needle = '/images/imagemanager/';
        $pos = strpos($url, $needle);
        if ($pos === false) return null;
        $tail = substr($url, $pos + strlen($needle));
        if (preg_match('/^(\d+)_/', $tail, $m)) {
            return (int)$m[1];
        }
        return null;
    }

    /**
     * Translate a MediaService exception into an end-user message.
     */
    private function humaniseUploadError(\Throwable $e): string
    {
        $msg = $e->getMessage();
        if (str_contains($msg, 'exceeds limit')) {
            return 'حجم الملف يتجاوز الحد المسموح به.';
        }
        if (str_contains($msg, 'MIME')) {
            return 'نوع الملف غير مسموح به.';
        }
        return 'حدث خطأ غير متوقع. حاول مجدداً.';
    }
}
