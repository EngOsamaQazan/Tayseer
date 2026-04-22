<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\web\BadRequestHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use common\filters\UploadRateLimit;
use common\services\media\GroupNameRegistry;
use common\services\media\MediaContext;

/**
 * Phase 1 / M1.2 — Public-ish endpoints for the unified Media subsystem.
 *
 * Today this controller serves only one route, but it is the natural
 * home for everything we will add in later phases:
 *   • actionLabels   — JSON dump of GroupNameRegistry, consumed by
 *                       the standalone Fahras scripts and the unified
 *                       MediaUploader JS bundle.
 *   • actionHealth   — (Phase 7) orphan/processing-status counters.
 *   • actionUpload   — (Phase 6) the single upload endpoint that the
 *                       unified MediaUploader posts to.
 *   • actionLabelsCss — (Phase 6) CSS variables generated from the
 *                       registry's per-group palette.
 *
 * Authentication: actionLabels is readable by any logged-in user
 * (the labels themselves are not sensitive, but we still gate the
 * endpoint behind login to avoid trivially exposing the schema to
 * the public internet).
 */
class MediaController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['labels', 'upload', 'health'],
                        'allow'   => true,
                        'roles'   => ['@'], // any logged-in user
                    ],
                ],
            ],
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => [
                    'labels' => ['GET'],
                    'upload' => ['POST'],
                    'health' => ['GET'],
                ],
            ],
            // Phase 7 / M7.1 — 30 uploads / minute / user (or IP for guests).
            // Anything more is almost certainly a runaway browser tab or a
            // misbehaving integration; legitimate batch imports run via
            // the console controllers, not this endpoint.
            'rateLimit' => [
                'class'  => UploadRateLimit::class,
                'only'   => ['upload'],
                'limit'  => 30,
                'window' => 60,
            ],
        ];
    }

    /**
     * GET /admin/index.php?r=media/labels
     *
     * Returns the full GroupNameRegistry as JSON, consumed by:
     *   • backend/web/fahras/{api,client-attachments,relations}.php
     *     (replaces the 3 hand-maintained $docTypes copies)
     *   • backend/web/js/media-uploader/core.js (Phase 6)
     *
     * Cached for 5 minutes (the registry is a code constant; a longer
     * TTL would just force a deploy delay when we add a new group).
     *
     * Response shape:
     *   {
     *     "groups": {
     *       "0": { "label_ar": "...", "label_en": "...", "entity": ["customer"],
     *              "wizard": true, "max_bytes": 10485760, "mimes": ["image/", "application/pdf"] },
     *       …
     *     },
     *     "wizard_groups": ["0", "0_front", …],
     *     "entity_types":  ["customer", "contract", …]
     *   }
     */
    public function actionLabels(): Response
    {
        $resp = Yii::$app->response;
        $resp->format = Response::FORMAT_JSON;

        // ETag for cheap revalidation. The registry is a code
        // constant so the SHA-256 of its serialised dump is the
        // perfect cache key — it changes IFF the code changes.
        $payload = [
            'groups'        => GroupNameRegistry::all(),
            'wizard_groups' => GroupNameRegistry::wizardGroups(),
            'entity_types'  => GroupNameRegistry::entityTypes(),
        ];
        $etag = '"' . substr(hash('sha256', serialize($payload)), 0, 16) . '"';

        $req = Yii::$app->request;
        $ifNoneMatch = $req->headers->get('If-None-Match');
        if ($ifNoneMatch !== null && $ifNoneMatch === $etag) {
            $resp->statusCode = 304;
            return $resp;
        }

        $resp->headers->set('ETag', $etag);
        $resp->headers->set('Cache-Control', 'private, max-age=300');
        $resp->data = $payload;
        return $resp;
    }

    /**
     * POST /admin/index.php?r=media/upload
     *
     * Phase 6 / M6.1 — single upload endpoint that the unified
     * MediaUploader JS bundle posts to. Accepts multipart/form-data:
     *
     *   file           — the file (single, repeated keys not supported
     *                    here; the JS uploader sends one request per file
     *                    so progress bars stay accurate per item)
     *   entity_type    — required; one of GroupNameRegistry::entityTypes()
     *   entity_id      — int (optional; null is legitimate for wizard
     *                    pre-customer uploads, MediaService::adoptOrphans
     *                    will adopt them later)
     *   group_name     — required; one of GroupNameRegistry keys
     *   uploaded_via   — caller label (lawyer_form, employee_form,
     *                    company_form, wizard, smart_media, …); default
     *                    'unified_uploader' so legacy dashboards still
     *                    have something to filter on
     *   auto_classify  — '1' to run VisionService classification (only
     *                    meaningful for wizard scans of customer IDs)
     *
     * Response (HTTP 200):
     *   { "success": true, "file": MediaResult::toArray(), "ai": null|{...} }
     *
     * Errors return HTTP 400 with `{success:false, error:"..."}`. The
     * uploader recovers gracefully and shows the message in the row.
     */
    public function actionUpload(): Response
    {
        $resp = Yii::$app->response;
        $resp->format = Response::FORMAT_JSON;

        try {
            $file = UploadedFile::getInstanceByName('file');
            if ($file === null || $file->error !== UPLOAD_ERR_OK) {
                throw new BadRequestHttpException(
                    'No file in request (or upload error code '
                    . ($file->error ?? 'n/a') . ').'
                );
            }

            $req         = Yii::$app->request;
            $entityType  = trim((string)$req->post('entity_type', ''));
            $entityIdRaw = $req->post('entity_id', null);
            $groupName   = trim((string)$req->post('group_name', ''));
            $via         = trim((string)$req->post('uploaded_via', 'unified_uploader')) ?: 'unified_uploader';
            $autoClass   = (string)$req->post('auto_classify', '0') === '1';

            if ($entityType === '') {
                throw new BadRequestHttpException('entity_type is required');
            }
            if ($groupName === '') {
                throw new BadRequestHttpException('group_name is required');
            }
            if (!GroupNameRegistry::validate($groupName, $entityType)) {
                throw new BadRequestHttpException(
                    "Invalid (group_name, entity_type) pair: '$groupName' / '$entityType'"
                );
            }

            $entityId = null;
            if ($entityIdRaw !== null && $entityIdRaw !== '') {
                $entityId = (int)$entityIdRaw;
                if ($entityId <= 0) {
                    throw new BadRequestHttpException('entity_id must be a positive integer');
                }
            }

            $ctx = new MediaContext(
                entityType:   $entityType,
                entityId:     $entityId,
                groupName:    $groupName,
                uploadedVia:  $via,
                userId:       Yii::$app->user->isGuest ? null : (int)Yii::$app->user->id,
                autoClassify: $autoClass,
                originalName: $file->name,
            );

            $result = Yii::$app->media->store($file, $ctx);

            $resp->statusCode = 200;
            $resp->data = [
                'success' => true,
                'file'    => $result->toArray(),
                'ai'      => $result->aiClassification,
            ];
            return $resp;
        } catch (\InvalidArgumentException $e) {
            // MediaService::store throws InvalidArgumentException for
            // user-fixable issues (mime not allowed, file too large,
            // unknown entity, …). Surface them as 400 so the JS shows
            // a useful inline error rather than a generic toast.
            $resp->statusCode = 400;
            $resp->data = ['success' => false, 'error' => $e->getMessage()];
            return $resp;
        } catch (BadRequestHttpException $e) {
            $resp->statusCode = 400;
            $resp->data = ['success' => false, 'error' => $e->getMessage()];
            return $resp;
        } catch (\Throwable $e) {
            Yii::error('media/upload failed: ' . $e->getMessage(), __METHOD__);
            $resp->statusCode = 500;
            $resp->data = ['success' => false, 'error' => 'Upload failed.'];
            return $resp;
        }
    }

    /**
     * GET /admin/index.php?r=media/health
     *
     * Phase 7 / M7.2 — operational dashboard for the unified media
     * pipeline. Returns counters that surface stuck files, orphaned
     * uploads, processing-queue health, and storage growth per
     * entity_type. Consumed by:
     *   • the admin dashboard widget (DashboardMediaHealthWidget)
     *   • external monitoring (curl + jq in cron)
     *
     * Cached for 60s to keep the dashboard refresh cheap.
     */
    public function actionHealth(): Response
    {
        $resp = Yii::$app->response;
        $resp->format = Response::FORMAT_JSON;

        $cache = Yii::$app->cache ?? null;
        $cacheKey = 'media-health:v1';
        if ($cache !== null) {
            $cached = $cache->get($cacheKey);
            if (is_array($cached)) {
                $resp->headers->set('X-Cache', 'HIT');
                $resp->data = $cached;
                return $resp;
            }
        }

        $db = Yii::$app->db;
        $now = new \DateTimeImmutable('now');
        $cutoff24h = $now->modify('-24 hours')->format('Y-m-d H:i:s');

        // — Orphan rows older than 24h (no entity assignment) —
        $orphans = (int)$db->createCommand(
            "SELECT COUNT(*) FROM os_ImageManager
              WHERE deleted_at IS NULL
                AND entity_type IS NULL
                AND (customer_id IS NULL OR customer_id = 0)
                AND (contractId IS NULL OR contractId = '' OR contractId = '0')
                AND created < :cutoff",
            [':cutoff' => $cutoff24h]
        )->queryScalar();

        // — Failed processing —
        $failed = (int)$db->createCommand(
            "SELECT COUNT(*) FROM os_ImageManager
              WHERE deleted_at IS NULL AND processing_status = 'failed'"
        )->queryScalar();

        // — Pending in queue (jobs waiting for thumbnail / OCR / scan) —
        $pending = (int)$db->createCommand(
            "SELECT COUNT(*) FROM os_ImageManager
              WHERE deleted_at IS NULL AND processing_status = 'pending'"
        )->queryScalar();

        $processing = (int)$db->createCommand(
            "SELECT COUNT(*) FROM os_ImageManager
              WHERE deleted_at IS NULL AND processing_status = 'processing'"
        )->queryScalar();

        // — Storage usage by entity_type —
        $byEntity = $db->createCommand(
            "SELECT COALESCE(entity_type, '_unknown') AS entity_type,
                    COUNT(*) AS files,
                    COALESCE(SUM(file_size), 0) AS bytes
               FROM os_ImageManager
              WHERE deleted_at IS NULL
              GROUP BY entity_type
              ORDER BY bytes DESC"
        )->queryAll();

        $totalFiles = 0; $totalBytes = 0;
        foreach ($byEntity as &$row) {
            $row['files'] = (int)$row['files'];
            $row['bytes'] = (int)$row['bytes'];
            $row['size_human'] = self::formatBytes($row['bytes']);
            $totalFiles += $row['files'];
            $totalBytes += $row['bytes'];
        }
        unset($row);

        // — Soft-deleted files awaiting hard cleanup —
        $softDeleted = (int)$db->createCommand(
            "SELECT COUNT(*) FROM os_ImageManager WHERE deleted_at IS NOT NULL"
        )->queryScalar();

        // — Avg pending → ready latency over the last 24h —
        $latencyMs = null;
        try {
            $latencyMs = (float)$db->createCommand(
                "SELECT AVG(TIMESTAMPDIFF(SECOND, created, modified) * 1000)
                   FROM os_ImageManager
                  WHERE processing_status = 'ready'
                    AND created >= :since",
                [':since' => $cutoff24h]
            )->queryScalar();
        } catch (\Throwable $_) {
            // `modified` column might be missing on very old installs.
        }

        $payload = [
            'generated_at'   => $now->format(DATE_ATOM),
            'orphans_over_24h' => $orphans,
            'failed'         => $failed,
            'pending'        => $pending,
            'processing'     => $processing,
            'soft_deleted'   => $softDeleted,
            'total_files'    => $totalFiles,
            'total_bytes'    => $totalBytes,
            'total_size_human' => self::formatBytes($totalBytes),
            'avg_pending_to_ready_ms' => $latencyMs !== null ? round($latencyMs) : null,
            'by_entity_type' => $byEntity,
            'health'         => [
                'orphans_ok' => $orphans === 0,
                'failed_ok'  => $failed < 10,
                'queue_ok'   => $pending < 100,
            ],
        ];
        $payload['ok'] = $payload['health']['orphans_ok']
            && $payload['health']['failed_ok']
            && $payload['health']['queue_ok'];

        if ($cache !== null) {
            $cache->set($cacheKey, $payload, 60);
        }

        $resp->headers->set('X-Cache', 'MISS');
        $resp->headers->set('Cache-Control', 'private, max-age=60');
        $resp->data = $payload;
        return $resp;
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int)floor(log($bytes, 1024));
        $i = max(0, min($i, count($units) - 1));
        return number_format($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
