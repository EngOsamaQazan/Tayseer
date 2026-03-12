<?php
/**
 * SmartMediaController — Smart Document & Photo Management
 * Handles: file upload, webcam capture, AI classification, usage stats
 * 
 * IMPORTANT: All uploads are saved into os_ImageManager + images/imagemanager/
 * to ensure compatibility with the rest of the system (print preview, image manager, etc.)
 */

namespace backend\modules\customers\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use backend\modules\customers\components\VisionService;

class SmartMediaController extends Controller
{
    /**
     * Disable CSRF for AJAX file upload & webcam
     */
    public $enableCsrfValidation = false;

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
     * 
     * Files are saved to os_ImageManager table AND images/imagemanager/ directory
     * so they are visible in: image manager, print preview, customer photo display
     */
    public function actionUpload()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $file = UploadedFile::getInstanceByName('file');
        if (!$file) {
            return ['success' => false, 'error' => 'لم يتم استلام الملف'];
        }

        // Validate
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
        if (!in_array($file->type, $allowed)) {
            return ['success' => false, 'error' => 'نوع الملف غير مدعوم. المسموح: JPG, PNG, WebP, PDF'];
        }

        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file->size > $maxSize) {
            return ['success' => false, 'error' => 'حجم الملف أكبر من 10MB'];
        }

        $customerId = Yii::$app->request->post('customer_id');

        try {
            // AI Classification first (to determine groupName before saving)
            $aiResult = null;
            $autoClassify = Yii::$app->request->post('auto_classify', '1');
            $ext = strtolower($file->extension);

            if ($autoClassify === '1' && strpos($file->type, 'image/') === 0) {
                // Save to temp for AI analysis
                $tempPath = Yii::getAlias('@runtime') . '/temp_' . Yii::$app->security->generateRandomString(8) . '.' . $ext;
                $file->saveAs($tempPath, false); // false = don't delete the temp file yet
                $aiResult = VisionService::classify($tempPath, $customerId ? (int)$customerId : null);
                @unlink($tempPath); // clean up temp
            }

            // Determine groupName from AI classification
            $groupName = '9'; // default: "أخرى" (other)
            if ($aiResult && !empty($aiResult['classification']['type'])) {
                $groupName = (string)$aiResult['classification']['type'];
            }

            // Generate hash for ImageManager
            $fileHash = Yii::$app->security->generateRandomString(32);

            // Insert into os_ImageManager FIRST to get the ID
            $db = Yii::$app->db;
            $db->createCommand()->insert('{{%ImageManager}}', [
                'fileName'    => $file->name,
                'fileHash'    => $fileHash,
                'customer_id' => $customerId ? (int)$customerId : null,
                'contractId'  => null,  // contractId reserved for contract images only
                'groupName'   => $groupName,
                'created'     => date('Y-m-d H:i:s'),
                'modified'    => date('Y-m-d H:i:s'),
                'createdBy'   => Yii::$app->user->id ?? null,
                'modifiedBy'  => Yii::$app->user->id ?? null,
            ])->execute();

            $imageId = $db->getLastInsertID();

            // Save file to images/imagemanager/ with the correct naming: {id}_{hash}.{ext}
            $imageManagerDir = Yii::getAlias('@backend/web/images/imagemanager');
            if (!is_dir($imageManagerDir)) mkdir($imageManagerDir, 0755, true);

            $destFilename = $imageId . '_' . $fileHash . '.' . $ext;
            $destPath = $imageManagerDir . '/' . $destFilename;
            $webPath = '/images/imagemanager/' . $destFilename;

            if (!$file->saveAs($destPath)) {
                // Rollback DB insert if file save fails
                $db->createCommand()->delete('{{%ImageManager}}', ['id' => $imageId])->execute();
                throw new \Exception('فشل في حفظ الملف');
            }

            // Create thumbnail in uploads/customers/documents/thumbs/ for the UI card
            $thumbWebPath = null;
            if (strpos($file->type, 'image/') === 0) {
                $thumbDir = Yii::getAlias('@backend/web/uploads/customers/documents/thumbs');
                if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);
                $thumbFile = 'thumb_' . $destFilename;
                $thumbFullPath = $thumbDir . '/' . $thumbFile;
                if (VisionService::createThumbnail($destPath, $thumbFullPath)) {
                    $thumbWebPath = '/uploads/customers/documents/thumbs/' . $thumbFile;
                }
            }

            return [
                'success' => true,
                'file' => [
                    'id'             => (int)$imageId,
                    'name'           => $file->name,
                    'path'           => $webPath,
                    'full_path'      => $destPath,
                    'thumb'          => $thumbWebPath ?: $webPath,
                    'size'           => $file->size,
                    'mime'           => $file->type,
                    'capture_method' => 'upload',
                    'group_name'     => $groupName,
                ],
                'ai' => $aiResult ? [
                    'classification' => $aiResult['classification'],
                    'text_preview'   => mb_substr($aiResult['text'] ?? '', 0, 200),
                    'labels'         => array_slice($aiResult['labels'] ?? [], 0, 5),
                    'response_time'  => $aiResult['response_time_ms'] ?? 0,
                ] : null,
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Capture webcam photo
     * POST: image_data (base64 data URL), customer_id (optional), photo_type
     * 
     * Saved to os_ImageManager + images/imagemanager/ for system-wide compatibility
     */
    public function actionWebcamCapture()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $imageData = Yii::$app->request->post('image_data');
        if (!$imageData) {
            return ['success' => false, 'error' => 'لم يتم استلام بيانات الصورة'];
        }

        // Validate base64 data URL
        if (!preg_match('/^data:image\/(jpeg|png|webp);base64,/', $imageData, $matches)) {
            return ['success' => false, 'error' => 'صيغة البيانات غير صحيحة'];
        }

        $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $mimeType = 'image/' . $matches[1];

        try {
            // Decode base64
            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
            $binaryData = base64_decode($base64Data);

            if (!$binaryData) {
                throw new \Exception('فشل في فك تشفير الصورة');
            }

            $customerId = Yii::$app->request->post('customer_id');
            $photoType = Yii::$app->request->post('photo_type', 'webcam');

            // Map photo_type to groupName
            $groupName = '8'; // default: صورة شخصية (personal photo)
            if ($photoType === 'id_front' || $photoType === 'id_back') {
                $groupName = '0'; // هوية وطنية
            }

            // Generate hash & filename for ImageManager
            $fileHash = Yii::$app->security->generateRandomString(32);
            $originalName = 'cam_' . date('Ymd_His') . '.' . $ext;

            // Insert into os_ImageManager
            $db = Yii::$app->db;
            $db->createCommand()->insert('{{%ImageManager}}', [
                'fileName'    => $originalName,
                'fileHash'    => $fileHash,
                'customer_id' => $customerId ? (int)$customerId : null,
                'contractId'  => null,
                'groupName'   => $groupName,
                'created'     => date('Y-m-d H:i:s'),
                'modified'    => date('Y-m-d H:i:s'),
                'createdBy'   => Yii::$app->user->id ?? null,
                'modifiedBy'  => Yii::$app->user->id ?? null,
            ])->execute();

            $imageId = $db->getLastInsertID();

            // Save to images/imagemanager/
            $imageManagerDir = Yii::getAlias('@backend/web/images/imagemanager');
            if (!is_dir($imageManagerDir)) mkdir($imageManagerDir, 0755, true);

            $destFilename = $imageId . '_' . $fileHash . '.' . $ext;
            $destPath = $imageManagerDir . '/' . $destFilename;
            $webPath = '/images/imagemanager/' . $destFilename;

            file_put_contents($destPath, $binaryData);

            // Create thumbnail
            $thumbWebPath = null;
            $thumbDir = Yii::getAlias('@backend/web/uploads/customers/documents/thumbs');
            if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);
            $thumbFile = 'thumb_' . $destFilename;
            $thumbFullPath = $thumbDir . '/' . $thumbFile;
            if (VisionService::createThumbnail($destPath, $thumbFullPath, 150, 150)) {
                $thumbWebPath = '/uploads/customers/documents/thumbs/' . $thumbFile;
            }

            return [
                'success' => true,
                'photo' => [
                    'id'    => (int)$imageId,
                    'path'  => $webPath,
                    'thumb' => $thumbWebPath ?: $webPath,
                    'size'  => strlen($binaryData),
                    'type'  => $photoType,
                    'group_name' => $groupName,
                ],
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Classify an already-uploaded document with AI
     * POST: file_path (web path to image), image_id (os_ImageManager ID)
     */
    public function actionClassify()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $webPath = Yii::$app->request->post('file_path');
        if (!$webPath) {
            return ['success' => false, 'error' => 'مسار الملف مطلوب'];
        }

        $filePath = Yii::getAlias('@backend/web') . $webPath;

        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'الملف غير موجود'];
        }

        $customerId = Yii::$app->request->post('customer_id');
        $imageId = Yii::$app->request->post('image_id');

        $result = VisionService::classify($filePath, $customerId ? (int)$customerId : null);

        // Update groupName in os_ImageManager if we have an image_id and classification succeeded
        if ($result['success'] && $imageId && !empty($result['classification']['type'])) {
            Yii::$app->db->createCommand()->update(
                '{{%ImageManager}}',
                ['groupName' => (string)$result['classification']['type']],
                ['id' => (int)$imageId]
            )->execute();
        }

        return [
            'success' => $result['success'],
            'classification' => $result['classification'] ?? null,
            'text_preview' => mb_substr($result['text'] ?? '', 0, 300),
            'labels' => array_slice($result['labels'] ?? [], 0, 8),
            'error' => $result['error'] ?? null,
            'response_time' => $result['response_time_ms'] ?? 0,
        ];
    }

    /**
     * Get usage statistics — local tracking data
     */
    public function actionUsageStats()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return VisionService::getUsageStats();
    }

    /**
     * Get LIVE Google Cloud data — real billing + real usage metrics
     * Pulls directly from Google Billing API & Monitoring API
     */
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

        $imageId = Yii::$app->request->post('image_id');
        $groupName = Yii::$app->request->post('group_name');

        if (!$imageId) {
            return ['success' => false, 'error' => 'معرف الصورة مطلوب'];
        }

        try {
            Yii::$app->db->createCommand()->update(
                '{{%ImageManager}}',
                ['groupName' => (string)$groupName],
                ['id' => (int)$imageId]
            )->execute();

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete an uploaded file
     * POST: file_path, image_id (os_ImageManager ID)
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $webPath = Yii::$app->request->post('file_path');
        $imageId = Yii::$app->request->post('image_id');

        if (!$webPath && !$imageId) {
            return ['success' => false, 'error' => 'مسار الملف أو معرف الصورة مطلوب'];
        }

        try {
            // Delete the physical file
            if ($webPath) {
                $filePath = Yii::getAlias('@backend/web') . $webPath;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                // Delete thumbnail
                $thumbPath = str_replace(basename($webPath), 'thumbs/thumb_' . basename($webPath), Yii::getAlias('@backend/web/uploads/customers/documents/') . basename($webPath));
                if (file_exists($thumbPath)) {
                    unlink($thumbPath);
                }
            }

            // Delete from os_ImageManager if we have the ID
            if ($imageId) {
                // Get the record to find the file
                $record = Yii::$app->db->createCommand(
                    "SELECT id, fileName, fileHash FROM {{%ImageManager}} WHERE id = :id",
                    [':id' => (int)$imageId]
                )->queryOne();

                if ($record) {
                    $ext = pathinfo($record['fileName'], PATHINFO_EXTENSION);
                    $imgPath = Yii::getAlias('@backend/web/images/imagemanager/') . $record['id'] . '_' . $record['fileHash'] . '.' . $ext;
                    if (file_exists($imgPath)) {
                        unlink($imgPath);
                    }
                    Yii::$app->db->createCommand()->delete('{{%ImageManager}}', ['id' => (int)$imageId])->execute();
                }
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
