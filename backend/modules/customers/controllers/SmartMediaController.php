<?php
/**
 * SmartMediaController — Smart Document & Photo Management
 * Handles: file upload, webcam capture, AI classification, usage stats
 *
 * Phase 3.2 of unify-media-system: every write path now delegates to
 * `Yii::$app->media` (MediaService). The controller is now ~70 % smaller
 * because validation, hashing, dedup, audit and async pipeline live
 * inside MediaService where the other 16 upload surfaces also benefit.
 *
 * CSRF was previously disabled (`$enableCsrfValidation = false`) — it
 * is now ENABLED. The corresponding JS (`backend/web/js/smart-media.js`)
 * installs an `$.ajaxPrefilter` that attaches the X-CSRF-Token header
 * to every mutating request automatically.
 */

namespace backend\modules\customers\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\filters\AccessControl;
use backend\modules\customers\components\VisionService;
use backend\helpers\MediaHelper;
use common\services\media\MediaContext;

class SmartMediaController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Upload file(s) via AJAX — supports drag-and-drop and traditional upload
     * POST: file (multipart), customer_id (optional), auto_classify (0|1)
     * Returns: JSON with file info + AI classification
     */
    public function actionUpload()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $file = UploadedFile::getInstanceByName('file');
        if (!$file) {
            return ['success' => false, 'error' => 'لم يتم استلام الملف'];
        }

        $customerId   = Yii::$app->request->post('customer_id');
        $customerId   = $customerId !== null && $customerId !== '' ? (int)$customerId : null;
        $autoClassify = Yii::$app->request->post('auto_classify', '1') === '1';

        // Build the MediaContext once. The eventual groupName is set
        // by the AI classifier, so we start with the generic
        // 'smart_media' bucket and let MediaService::store() update it
        // via the autoClassify pipeline if auto-classify is on.
        //
        // When customer_id is missing, this is an orphan upload that
        // will be adopted later (same lifecycle as the wizard).
        $ctx = $customerId !== null
            ? MediaContext::forCustomer($customerId, 'smart_media', 'smart_media')
            : new MediaContext(
                entityType:  'customer',
                entityId:    null,
                groupName:   'smart_media',
                uploadedVia: 'smart_media',
                userId:      Yii::$app->user->id ?? null,
                autoClassify: false, // we run classification ourselves below to control the response shape
            );

        try {
            $result = Yii::$app->media->store($file, $ctx);
        } catch (\InvalidArgumentException $e) {
            // Translates the registry's MIME / size / group rejections
            // back to the friendly Arabic strings the previous
            // implementation used.
            return ['success' => false, 'error' => $this->humaniseUploadError($e->getMessage())];
        } catch (\Throwable $e) {
            Yii::error('SmartMediaController: store failed: ' . $e->getMessage(), __METHOD__);
            return ['success' => false, 'error' => 'فشل حفظ الملف'];
        }

        // ── Optional AI classification ────────────────────────────────
        // We run it after store() (instead of before, like the original
        // code did) because:
        //   1. The bytes already live at a stable on-disk path —
        //      no need to write a temp file just to call classify().
        //   2. Failures here NEVER prevent the upload from succeeding
        //      (classification is best-effort enrichment).
        $ai = null;
        $groupName = $result->groupName;
        if ($autoClassify && str_starts_with($result->mimeType, 'image/')) {
            try {
                Yii::$app->session->close(); // free the session lock for parallel uploads
                $localPath = MediaHelper::filePath($result->id, $this->fileHashFor($result->id), $result->fileName);
                if (is_file($localPath)) {
                    $ai = VisionService::classify($localPath, $customerId);
                    if (!empty($ai['classification']['type'])) {
                        $groupName = (string)$ai['classification']['type'];
                        // Persist the AI-derived groupName so the row
                        // in os_ImageManager reflects what Vision saw.
                        Yii::$app->db->createCommand()->update(
                            '{{%ImageManager}}',
                            ['groupName' => $groupName, 'modified' => date('Y-m-d H:i:s')],
                            ['id' => $result->id]
                        )->execute();
                    }
                }
            } catch (\Throwable $e) {
                Yii::warning('SmartMediaController: classify failed (non-fatal): ' . $e->getMessage(), __METHOD__);
            }
        }

        return [
            'success' => true,
            'file' => [
                'id'             => $result->id,
                'name'           => $result->fileName,
                'path'           => $result->url,
                'thumb'          => $result->thumbUrl ?: $result->url,
                'size'           => $result->fileSize,
                'mime'           => $result->mimeType,
                'capture_method' => 'upload',
                'group_name'     => $groupName,
            ],
            'ai' => $ai !== null ? [
                'classification' => $ai['classification'] ?? null,
                'text_preview'   => mb_substr($ai['text'] ?? '', 0, 200),
                'labels'         => array_slice($ai['labels'] ?? [], 0, 5),
                'response_time'  => $ai['response_time_ms'] ?? 0,
            ] : null,
        ];
    }

    /**
     * Capture webcam photo
     * POST: image_data (base64 data URL), customer_id (optional), photo_type
     */
    public function actionWebcamCapture()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $imageData = Yii::$app->request->post('image_data');
        if (!$imageData) {
            return ['success' => false, 'error' => 'لم يتم استلام بيانات الصورة'];
        }
        if (!preg_match('/^data:image\/(jpeg|png|webp);base64,/', $imageData)) {
            return ['success' => false, 'error' => 'صيغة البيانات غير صحيحة'];
        }

        $customerId = Yii::$app->request->post('customer_id');
        $customerId = $customerId !== null && $customerId !== '' ? (int)$customerId : null;
        $photoType  = Yii::$app->request->post('photo_type', 'webcam');

        // The legacy mapping: id_front/id_back → '0', everything else → '8'.
        $groupName = ($photoType === 'id_front' || $photoType === 'id_back') ? '0' : '8';

        $ctx = $customerId !== null
            ? MediaContext::forCustomer($customerId, $groupName, 'smart_media')
            : new MediaContext(
                entityType:  'customer',
                entityId:    null,
                groupName:   $groupName,
                uploadedVia: 'smart_media',
                userId:      Yii::$app->user->id ?? null,
            );

        try {
            $result = Yii::$app->media->storeFromBase64($imageData, $ctx);
        } catch (\InvalidArgumentException $e) {
            return ['success' => false, 'error' => $this->humaniseUploadError($e->getMessage())];
        } catch (\Throwable $e) {
            Yii::error('SmartMediaController: webcam store failed: ' . $e->getMessage(), __METHOD__);
            return ['success' => false, 'error' => 'فشل حفظ الصورة'];
        }

        return [
            'success' => true,
            'photo' => [
                'id'         => $result->id,
                'path'       => $result->url,
                'thumb'      => $result->thumbUrl ?: $result->url,
                'size'       => $result->fileSize,
                'type'       => $photoType,
                'group_name' => $groupName,
            ],
        ];
    }

    /**
     * Classify an already-uploaded document with AI
     * POST: file_path (web path to image), image_id (os_ImageManager ID)
     */
    public function actionClassify()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $imageId = Yii::$app->request->post('image_id');
        $webPath = Yii::$app->request->post('file_path');

        // Resolve the local path through MediaService when possible —
        // it knows the storage layout and works transparently for any
        // future driver. Fall back to the legacy webPath translation
        // only when the caller did not pass an image_id.
        $filePath = null;
        if ($imageId) {
            $media = Yii::$app->media->getById((int)$imageId);
            if ($media !== null) {
                $filePath = MediaHelper::filePath((int)$media->id, (string)$media->fileHash, (string)$media->fileName);
            }
        }
        if ($filePath === null && $webPath) {
            $filePath = Yii::getAlias('@backend/web') . $webPath;
        }
        if (!$filePath || !is_file($filePath)) {
            return ['success' => false, 'error' => 'الملف غير موجود'];
        }

        $customerId = Yii::$app->request->post('customer_id');
        $customerId = $customerId !== null && $customerId !== '' ? (int)$customerId : null;

        $result = VisionService::classify($filePath, $customerId);

        if (!empty($result['success']) && $imageId && !empty($result['classification']['type'])) {
            Yii::$app->db->createCommand()->update(
                '{{%ImageManager}}',
                [
                    'groupName' => (string)$result['classification']['type'],
                    'modified'  => date('Y-m-d H:i:s'),
                ],
                ['id' => (int)$imageId]
            )->execute();
        }

        return [
            'success'        => $result['success'] ?? false,
            'classification' => $result['classification'] ?? null,
            'text_preview'   => mb_substr($result['text'] ?? '', 0, 300),
            'labels'         => array_slice($result['labels'] ?? [], 0, 8),
            'error'          => $result['error'] ?? null,
            'response_time'  => $result['response_time_ms'] ?? 0,
        ];
    }

    public function actionUsageStats()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return VisionService::getUsageStats();
    }

    public function actionGoogleStats()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return VisionService::getCombinedStats();
    }

    /**
     * Update document type (groupName) for an image
     * POST: image_id, group_name
     */
    public function actionUpdateType()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $imageId   = Yii::$app->request->post('image_id');
        $groupName = Yii::$app->request->post('group_name');
        if (!$imageId) {
            return ['success' => false, 'error' => 'معرف الصورة مطلوب'];
        }

        try {
            Yii::$app->db->createCommand()->update(
                '{{%ImageManager}}',
                ['groupName' => (string)$groupName, 'modified' => date('Y-m-d H:i:s')],
                ['id' => (int)$imageId]
            )->execute();
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Extract structured fields from a document image using OCR + field parser.
     */
    public function actionExtractFields()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $file = UploadedFile::getInstanceByName('file');
        if (!$file) {
            return ['success' => false, 'error' => 'لم يتم استلام الملف'];
        }

        $customerId = Yii::$app->request->post('customer_id');
        $customerId = $customerId !== null && $customerId !== '' ? (int)$customerId : null;

        // We need the bytes BEFORE we hand them to MediaService so we
        // can run OCR and let the extracted document type drive the
        // groupName. Copy to a runtime temp first; MediaService later
        // reads the uploaded file directly from $file->tempName.
        $ext = strtolower($file->extension ?: 'bin');
        $tempPath = Yii::getAlias('@runtime') . '/scan_' . Yii::$app->security->generateRandomString(8) . '.' . $ext;
        if (!@copy($file->tempName, $tempPath)) {
            return ['success' => false, 'error' => 'فشل في حفظ النسخة المؤقتة'];
        }

        try {
            Yii::$app->session->close();
            $extraction = VisionService::extractFromDocument($tempPath);
            $groupName  = $extraction['document']['type'] ?? '9';

            $ctx = $customerId !== null
                ? MediaContext::forCustomer($customerId, $groupName, 'smart_media')
                : new MediaContext(
                    entityType:  'customer',
                    entityId:    null,
                    groupName:   $groupName,
                    uploadedVia: 'smart_media',
                    userId:      Yii::$app->user->id ?? null,
                );

            try {
                $result = Yii::$app->media->store($file, $ctx);
            } catch (\InvalidArgumentException $e) {
                return ['success' => false, 'error' => $this->humaniseUploadError($e->getMessage())];
            }

            return [
                'success'        => true,
                'extraction'     => $extraction,
                'file' => [
                    'id'         => $result->id,
                    'name'       => $result->fileName,
                    'path'       => $result->url,
                    'thumb'      => $result->thumbUrl ?: ($result->mimeType === 'application/pdf'
                                        ? '/css/images/pdf-icon.png' : $result->url),
                    'size'       => $result->fileSize,
                    'mime'       => $result->mimeType,
                    'group_name' => $groupName,
                ],
                'ocr_text'       => $extraction['meta']['ocr_text'] ?? '',
                'ocr_preview'    => mb_substr($extraction['meta']['ocr_text'] ?? '', 0, 300),
                'classification' => $extraction['meta']['classification'] ?? null,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        } finally {
            @unlink($tempPath);
        }
    }

    /**
     * Delete an uploaded file
     * POST: file_path, image_id (os_ImageManager ID)
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $imageId = Yii::$app->request->post('image_id');
        if ($imageId) {
            // Hard-delete via MediaService — the user explicitly clicked
            // the delete button, so we want both the row and the bytes
            // gone. Soft-delete is reserved for system-initiated removals.
            $ok = Yii::$app->media->delete((int)$imageId, hardDelete: true);
            return ['success' => $ok];
        }

        // Fallback to the legacy "delete by web path" flow for old
        // callers that don't carry the image_id. Will be removed in
        // the Phase 8 cleanup once we verify nobody still calls it.
        $webPath = Yii::$app->request->post('file_path');
        if (!$webPath) {
            return ['success' => false, 'error' => 'مسار الملف أو معرف الصورة مطلوب'];
        }
        $filePath = Yii::getAlias('@backend/web') . $webPath;
        if (is_file($filePath)) @unlink($filePath);
        Yii::warning('SmartMediaController::actionDelete called with file_path only (no image_id) — '
            . "DEPRECATED, switch to image_id. Removal: 2026-07-19", __METHOD__);
        return ['success' => true];
    }

    // ─── Private helpers ───────────────────────────────────────────

    private function fileHashFor(int $imageId): string
    {
        $row = Yii::$app->db->createCommand(
            "SELECT fileHash FROM {{%ImageManager}} WHERE id = :id",
            [':id' => $imageId]
        )->queryOne();
        return (string)($row['fileHash'] ?? '');
    }

    /**
     * Translate the structured exceptions MediaService throws back
     * into the user-friendly Arabic strings the previous controller
     * used, so the front-end toast text does not regress.
     */
    private function humaniseUploadError(string $msg): string
    {
        if (str_contains($msg, 'MIME')) {
            return 'نوع الملف غير مدعوم';
        }
        if (str_contains($msg, 'exceeds limit')) {
            return 'حجم الملف أكبر من المسموح';
        }
        if (str_contains($msg, 'is not allowed for entity_type')) {
            return 'نوع المستند غير مسموح لهذه الجهة';
        }
        if (str_contains($msg, 'empty')) {
            return 'الملف فارغ';
        }
        return $msg;
    }
}
