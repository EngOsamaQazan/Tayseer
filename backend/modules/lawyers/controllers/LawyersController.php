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
use yii\web\UploadedFile;
use backend\modules\LawyersImage\models\LawyersImage;
use backend\helpers\ExportTrait;
use common\services\media\MediaContext;

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
                $this->adoptUploadedSignature($model);
                $this->adoptUploadedPhotos($model);
                $this->handleSignatureUpload($model);
                $this->handleLawyerPhotosUpload($model);
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
                $this->adoptUploadedSignature($model);
                $this->adoptUploadedPhotos($model);
                $this->handleSignatureUpload($model);

                $this->handleLawyerPhotosUpload($model);

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
     *
     * Routes through MediaService when the row points at a unified
     * media URL (new uploads since Phase 3.5). Falls back to direct
     * unlink for legacy rows that still carry a `uploads/lawyers/photos/...`
     * path written by the pre-MediaService code path. The LawyersImage
     * row itself is always removed so the gallery stops rendering it.
     */
    public function actionDeletePhoto()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = Yii::$app->request->post('id');
        $image = LawyersImage::findOne($id);
        if (!$image) {
            return ['success' => false];
        }

        $mediaId = $this->extractMediaIdFromUrl((string)$image->image);
        if ($mediaId !== null) {
            // New-style row — delegate to MediaService so audit log,
            // soft-delete window, and storage driver all run consistently.
            try {
                Yii::$app->media->delete($mediaId, hardDelete: true);
            } catch (\Throwable $e) {
                Yii::warning('Lawyers actionDeletePhoto MediaService delete failed: '
                    . $e->getMessage(), __METHOD__);
            }
        } else {
            // Legacy row — unlink the literal file path.
            $filePath = Yii::getAlias('@backend/web/') . $image->image;
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }

        $image->delete();
        return ['success' => true];
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
     *
     * Migrated to MediaService (Phase 3.5):
     *   • New uploads land in os_ImageManager via groupName='signature'.
     *   • lawyers.signature_image continues to hold a relative URL string
     *     so existing views (`Url::to(['/' . $model->signature_image])`)
     *     keep rendering without changes during the deprecation window.
     *   • '__removed__' clears the column AND soft-deletes the media row
     *     when it is a unified-system upload.
     */
    protected function handleSignatureUpload(Lawyers $model)
    {
        $signatureFile = UploadedFile::getInstanceByName('signature_file');

        if ($model->signature_image === '__removed__') {
            $this->dropPreviousSignature($model);
            $model->signature_image = null;
            $model->save(false, ['signature_image']);
            return;
        }

        if (!$signatureFile) {
            return;
        }

        try {
            $ctx = MediaContext::forLawyer((int)$model->id, 'signature');
            $result = Yii::$app->media->store($signatureFile, $ctx);
        } catch (\Throwable $e) {
            Yii::error('Lawyers signature upload failed: ' . $e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error',
                'تعذّر حفظ التوقيع: ' . $this->humaniseUploadError($e));
            return;
        }

        // Drop the previous signature AFTER the new one is safely stored
        // so a save failure never leaves the lawyer with no signature.
        $this->dropPreviousSignature($model);

        // Store URL without leading slash to match the historical
        // Url::to(['/' . $model->signature_image]) call sites.
        $model->signature_image = ltrim($result->url, '/');
        $model->save(false, ['signature_image']);
    }

    /**
     * Handle multi-file photo upload for a lawyer/representative.
     *
     * Migrated to MediaService (Phase 3.5):
     *   • Each accepted file goes through MediaService::store with
     *     groupName='lawyer_photo' so dedup, audit, and the storage
     *     driver are exercised consistently.
     *   • A LawyersImage row is still inserted for back-compat with
     *     the existing gallery view, but its `image` column now
     *     points at the unified URL (`/images/imagemanager/…`).
     *     Once Phase 5 finishes the side-table can be replaced with
     *     a database VIEW over os_ImageManager.
     *
     * Returns the count of successfully stored photos.
     */
    protected function handleLawyerPhotosUpload(Lawyers $model): int
    {
        $instances = UploadedFile::getInstances($model, 'image');
        if (empty($instances)) {
            return 0;
        }

        $saved = 0;
        foreach ($instances as $file) {
            try {
                $ctx = MediaContext::forLawyer((int)$model->id, 'lawyer_photo');
                $result = Yii::$app->media->store($file, $ctx);
            } catch (\Throwable $e) {
                Yii::error('Lawyers photo upload failed: ' . $e->getMessage(), __METHOD__);
                continue;
            }

            $row = new LawyersImage();
            $row->lawyer_id = (int)$model->id;
            $row->image     = ltrim($result->url, '/');
            if (!$row->save()) {
                Yii::error('LawyersImage save failed: '
                    . json_encode($row->getErrors(), JSON_UNESCAPED_UNICODE), __METHOD__);
                // Roll back the freshly-stored media row so we don't leave
                // an orphan that no UI can ever surface or delete.
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
     * Phase 6.2 — adopt a signature media row uploaded async via the
     * unified MediaUploader (data-media-uploader). The form posts a
     * single id under Lawyers[adopted_signature_id]. We claim it for
     * this lawyer, drop the previous signature, and pin the unified
     * URL onto lawyers.signature_image so existing readers keep
     * working without a code change.
     */
    protected function adoptUploadedSignature(Lawyers $model): void
    {
        $body = (array)Yii::$app->request->post('Lawyers', []);
        $mediaId = (int)($body['adopted_signature_id'] ?? 0);
        if ($mediaId <= 0) {
            return;
        }

        $ok = Yii::$app->media->adopt($mediaId, 'lawyer', (int)$model->id);
        if (!$ok) {
            Yii::warning("Lawyers adopt signature #$mediaId failed for lawyer #{$model->id}", __METHOD__);
            return;
        }

        try {
            $url = Yii::$app->media->url($mediaId);
        } catch (\Throwable $e) {
            Yii::error('Lawyers adopt signature url() failed: ' . $e->getMessage(), __METHOD__);
            return;
        }

        $this->dropPreviousSignature($model);
        $model->signature_image = ltrim($url, '/');
        $model->save(false, ['signature_image']);
    }

    /**
     * Phase 6.2 — adopt N photo media rows uploaded async via the
     * unified MediaUploader. Each id arrives under
     * Lawyers[adopted_photo_ids][]; we adopt them for this lawyer and
     * mirror them into LawyersImage so the existing gallery view
     * keeps rendering them.
     *
     * Returns the count of successfully adopted photos.
     */
    protected function adoptUploadedPhotos(Lawyers $model): int
    {
        $body = (array)Yii::$app->request->post('Lawyers', []);
        $ids = (array)($body['adopted_photo_ids'] ?? []);
        if (empty($ids)) {
            return 0;
        }

        $saved = 0;
        foreach ($ids as $rawId) {
            $mediaId = (int)$rawId;
            if ($mediaId <= 0) continue;

            if (!Yii::$app->media->adopt($mediaId, 'lawyer', (int)$model->id)) {
                Yii::warning("Lawyers adopt photo #$mediaId failed for lawyer #{$model->id}", __METHOD__);
                continue;
            }

            try {
                $url = Yii::$app->media->url($mediaId);
            } catch (\Throwable $e) {
                Yii::error('Lawyers adopt photo url() failed: ' . $e->getMessage(), __METHOD__);
                continue;
            }

            // Idempotent: do not double-insert if a previous form post
            // already mirrored the same media row.
            $exists = LawyersImage::find()
                ->where(['lawyer_id' => (int)$model->id, 'image' => ltrim($url, '/')])
                ->exists();
            if ($exists) {
                $saved++;
                continue;
            }

            $row = new LawyersImage();
            $row->lawyer_id = (int)$model->id;
            $row->image     = ltrim($url, '/');
            if ($row->save()) {
                $saved++;
            } else {
                Yii::error('LawyersImage adopt save failed: '
                    . json_encode($row->getErrors(), JSON_UNESCAPED_UNICODE), __METHOD__);
            }
        }
        return $saved;
    }

    /**
     * Remove the previously-saved signature (if any). Routes through
     * MediaService for unified-system rows, falls back to unlink
     * for legacy 'uploads/lawyers/signatures/…' paths.
     */
    private function dropPreviousSignature(Lawyers $model): void
    {
        $oldUrl = (string)$model->getOldAttribute('signature_image');
        if ($oldUrl === '' || $oldUrl === '__removed__') {
            return;
        }

        $mediaId = $this->extractMediaIdFromUrl($oldUrl);
        if ($mediaId !== null) {
            try {
                Yii::$app->media->delete($mediaId, hardDelete: true);
            } catch (\Throwable $e) {
                Yii::warning('Lawyers signature MediaService delete failed: '
                    . $e->getMessage(), __METHOD__);
            }
            return;
        }

        $oldPath = Yii::getAlias('@backend/web/') . $oldUrl;
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    /**
     * Pull the os_ImageManager.id out of a unified MediaService URL
     * (e.g. `/images/imagemanager/123_aBcDeF.png` → 123). Returns null
     * for legacy paths that did not flow through the unified system.
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
