<?php

namespace backend\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Phase 7 / M7.2 — Dashboard widget for the unified media pipeline.
 *
 * Renders the same payload that MediaController::actionHealth returns
 * as JSON, but inline (no HTTP round-trip from the dashboard view).
 * The widget gracefully degrades if the unified columns are missing
 * (e.g. someone forgot to run the migration), so the dashboard never
 * crashes because of a media-system problem.
 *
 * Drop into any view:
 *
 *   use backend\widgets\MediaHealthWidget;
 *   echo MediaHealthWidget::widget();
 */
class MediaHealthWidget extends Widget
{
    /** Optional title override. */
    public string $title = 'صحة نظام الميديا الموحّد';

    /** Cache TTL (seconds). 0 = no cache. */
    public int $cacheTtl = 60;

    public function run(): string
    {
        try {
            $stats = $this->collect();
        } catch (\Throwable $e) {
            Yii::warning('MediaHealthWidget collect failed: ' . $e->getMessage(), __METHOD__);
            return $this->renderError($e->getMessage());
        }

        return $this->renderHealth($stats);
    }

    private function collect(): array
    {
        $cache = Yii::$app->cache ?? null;
        $key   = 'media-health-widget:v1';
        if ($cache !== null && $this->cacheTtl > 0) {
            $cached = $cache->get($key);
            if (is_array($cached)) return $cached;
        }

        $db = Yii::$app->db;
        $schema = $db->getTableSchema('os_ImageManager', true);
        if ($schema === null) {
            throw new \RuntimeException('os_ImageManager table not present');
        }
        $hasUnified = isset($schema->columns['entity_type']);
        $hasDeleted = isset($schema->columns['deleted_at']);
        $hasStatus  = isset($schema->columns['processing_status']);
        $hasSize    = isset($schema->columns['file_size']);

        $cutoff24h = (new \DateTimeImmutable('-24 hours'))->format('Y-m-d H:i:s');

        $alive = $hasDeleted ? 'deleted_at IS NULL' : '1=1';

        $orphans = $hasUnified
            ? (int)$db->createCommand(
                "SELECT COUNT(*) FROM os_ImageManager
                  WHERE $alive
                    AND entity_type IS NULL
                    AND (customer_id IS NULL OR customer_id = 0)
                    AND (contractId IS NULL OR contractId = '' OR contractId = '0')
                    AND created < :cutoff",
                [':cutoff' => $cutoff24h]
            )->queryScalar()
            : 0;

        $failed = $hasStatus
            ? (int)$db->createCommand(
                "SELECT COUNT(*) FROM os_ImageManager
                  WHERE $alive AND processing_status = 'failed'"
            )->queryScalar()
            : 0;

        $pending = $hasStatus
            ? (int)$db->createCommand(
                "SELECT COUNT(*) FROM os_ImageManager
                  WHERE $alive AND processing_status = 'pending'"
            )->queryScalar()
            : 0;

        $totalFiles = (int)$db->createCommand(
            "SELECT COUNT(*) FROM os_ImageManager WHERE $alive"
        )->queryScalar();

        $totalBytes = $hasSize
            ? (int)$db->createCommand(
                "SELECT COALESCE(SUM(file_size), 0) FROM os_ImageManager WHERE $alive"
            )->queryScalar()
            : 0;

        $byEntity = [];
        if ($hasUnified) {
            $byEntity = $db->createCommand(
                "SELECT COALESCE(entity_type, '_unknown') AS entity_type,
                        COUNT(*) AS files,
                        COALESCE(SUM(" . ($hasSize ? 'file_size' : '0') . "), 0) AS bytes
                   FROM os_ImageManager
                  WHERE $alive
                  GROUP BY entity_type
                  ORDER BY bytes DESC, files DESC
                  LIMIT 10"
            )->queryAll();
        }

        $stats = [
            'orphans_over_24h' => $orphans,
            'failed'           => $failed,
            'pending'          => $pending,
            'total_files'      => $totalFiles,
            'total_bytes'      => $totalBytes,
            'by_entity'        => $byEntity,
            'has_unified'      => $hasUnified,
            'has_status'       => $hasStatus,
            'has_size'         => $hasSize,
        ];

        if ($cache !== null && $this->cacheTtl > 0) {
            $cache->set($key, $stats, $this->cacheTtl);
        }
        return $stats;
    }

    private function renderHealth(array $s): string
    {
        $alertClass = ($s['orphans_over_24h'] > 0 || $s['failed'] >= 10)
            ? 'border-warning'
            : 'border-success';

        $cards = [
            ['الملفات الكلية', number_format($s['total_files']),                 'fa-images',        '#0d6efd'],
            ['الحجم الكلي',    self::formatBytes($s['total_bytes']),             'fa-hdd',           '#16a34a'],
            ['يتيمة > 24س',    number_format($s['orphans_over_24h']),            'fa-unlink',        $s['orphans_over_24h'] > 0 ? '#dc2626' : '#6b7280'],
            ['فشلت المعالجة',   number_format($s['failed']),                      'fa-triangle-exclamation', $s['failed'] > 0 ? '#dc2626' : '#6b7280'],
            ['قيد المعالجة',    number_format($s['pending']),                     'fa-spinner',       $s['pending'] > 50 ? '#ea580c' : '#6b7280'],
        ];

        $cardsHtml = '';
        foreach ($cards as [$label, $value, $icon, $color]) {
            $cardsHtml .= '<div class="col-md-2 col-sm-4 col-6 mb-3">'
                . '<div class="p-3 rounded shadow-sm bg-white text-center" style="border-top:3px solid ' . $color . ';">'
                . '<i class="fa-solid ' . Html::encode($icon) . ' fa-2x" style="color:' . $color . ';"></i>'
                . '<div class="mt-2 fw-bold" style="font-size:1.4rem;">' . Html::encode($value) . '</div>'
                . '<div class="text-muted small">' . Html::encode($label) . '</div>'
                . '</div></div>';
        }

        $byEntityHtml = '';
        if (!empty($s['by_entity'])) {
            $rows = '';
            foreach ($s['by_entity'] as $row) {
                $rows .= '<tr>'
                    . '<td>' . Html::encode($row['entity_type']) . '</td>'
                    . '<td class="text-end">' . number_format((int)$row['files']) . '</td>'
                    . '<td class="text-end">' . self::formatBytes((int)$row['bytes']) . '</td>'
                    . '</tr>';
            }
            $byEntityHtml =
                '<div class="mt-3"><h6 class="text-muted mb-2">الاستهلاك حسب نوع الكيان</h6>'
                . '<div class="table-responsive"><table class="table table-sm table-striped mb-0">'
                . '<thead><tr><th>نوع الكيان</th><th class="text-end">الملفات</th><th class="text-end">المساحة</th></tr></thead>'
                . '<tbody>' . $rows . '</tbody></table></div></div>';
        }

        return '<div class="card ' . $alertClass . ' mb-3" style="border-width:2px;">'
            . '<div class="card-header bg-white d-flex justify-content-between align-items-center">'
                . '<h5 class="mb-0"><i class="fa-solid fa-photo-film me-2"></i>' . Html::encode($this->title) . '</h5>'
                . '<small class="text-muted">يُحدَّث تلقائياً كل ' . (int)$this->cacheTtl . ' ثانية</small>'
            . '</div>'
            . '<div class="card-body">'
                . '<div class="row">' . $cardsHtml . '</div>'
                . $byEntityHtml
            . '</div>'
            . '</div>';
    }

    private function renderError(string $msg): string
    {
        return '<div class="alert alert-warning mb-3">'
            . '<i class="fa-solid fa-circle-exclamation me-1"></i>'
            . Html::encode($this->title) . ' غير متاح: ' . Html::encode($msg)
            . '</div>';
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
