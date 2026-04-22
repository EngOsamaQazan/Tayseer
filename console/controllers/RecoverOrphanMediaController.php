<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;
use yii\helpers\Console;

/**
 * Recover orphan Media rows (`os_ImageManager.customer_id IS NULL`) created
 * during legacy customer-onboarding wizard sessions where the draft payload
 * exceeded MAX_DRAFT_BYTES and the image_id was silently dropped from
 * `wizard_drafts.payload`. The fix that prevents this happening again lives
 * in `backend\modules\customers\controllers\WizardController::adoptOrphanedScanMedia()`.
 * This command exists to clean up rows created BEFORE that fix shipped.
 *
 * Matching heuristic (deliberately conservative — never adopt a row we are
 * not >95% confident belongs to the customer we are about to attach it to):
 *   1. Orphan Media row has `customer_id IS NULL` AND a wizard-known
 *      `groupName` ('0', '0_front', '0_back', '1', '2', '4', '4_front',
 *      '4_back', '5', '6', '7', '8', '9'). Anything outside this set is
 *      likely from another module (contracts, smart_media…) and is left
 *      alone.
 *   2. We pick the customer that the SAME uploader (`createdBy`) created
 *      SOONEST AFTER the upload, within a `--window` minute window
 *      (default: 120 = the longest realistic wizard session). This is
 *      the wizard run the orphan was uploaded for.
 *   3. We refuse to adopt if zero or more-than-one customer matches in
 *      the window — those are reported as "ambiguous" and left orphan
 *      for human review.
 *
 * Usage:
 *   php yii recover-orphan-media          (dry-run, no DB writes)
 *   php yii recover-orphan-media --apply  (actually update os_ImageManager)
 *   php yii recover-orphan-media --apply --window=180
 *   php yii recover-orphan-media --apply --user-id=42  (limit to one uploader)
 */
class RecoverOrphanMediaController extends Controller
{
    /** @var bool Set to apply the changes. Without it, the command runs as a dry-run. */
    public $apply = false;

    /** @var int Time window (minutes) — how long after upload a wizard might still be open. */
    public $window = 120;

    /** @var int|null If set, restrict the sweep to orphans uploaded by this user id. */
    public $userId = null;

    /** @var int|null If set, restrict to orphans created on/after this unix timestamp. */
    public $since = null;

    /** @var int|null If set, restrict to orphans created on/before this unix timestamp. */
    public $until = null;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'apply', 'window', 'userId', 'since', 'until',
        ]);
    }

    public function optionAliases()
    {
        return [
            'a' => 'apply',
            'w' => 'window',
            'u' => 'userId',
        ];
    }

    /**
     * Wizard-known groupName buckets — mirrors the Fahras docTypes mapping
     * in backend/web/fahras/{client-attachments,relations,api}.php.
     */
    private const WIZARD_GROUPS = [
        '0', '0_front', '0_back',
        '1', '2',
        '4', '4_front', '4_back',
        '5', '6', '7', '8', '9',
    ];

    public function actionIndex()
    {
        $db = Yii::$app->db;
        $windowSec = max(60, (int)$this->window * 60);

        $this->stdout("\n=== Orphan Media Recovery ===\n", Console::FG_CYAN, Console::BOLD);
        $this->stdout(sprintf(
            "Mode: %s | Window: %d min | %s | %s\n\n",
            $this->apply ? 'APPLY (will UPDATE)' : 'DRY-RUN (no writes)',
            (int)$this->window,
            $this->userId ? ('user_id=' . (int)$this->userId) : 'all uploaders',
            $this->since || $this->until
                ? 'created BETWEEN ' . ($this->since ? date('Y-m-d', $this->since) : '*')
                  . ' AND ' . ($this->until ? date('Y-m-d', $this->until) : '*')
                : 'no date filter'
        ));

        // ── 1. Pull every candidate orphan in one go. ──
        $q = (new Query())
            ->select(['id', 'fileName', 'groupName', 'createdBy', 'created'])
            ->from('os_ImageManager')
            ->where(['customer_id' => null, 'groupName' => self::WIZARD_GROUPS])
            ->andWhere(['is not', 'createdBy', null])
            ->orderBy(['created' => SORT_ASC]);

        if ($this->userId) {
            $q->andWhere(['createdBy' => (int)$this->userId]);
        }
        if ($this->since) {
            $q->andWhere(['>=', 'created', date('Y-m-d H:i:s', (int)$this->since)]);
        }
        if ($this->until) {
            $q->andWhere(['<=', 'created', date('Y-m-d H:i:s', (int)$this->until)]);
        }

        $orphans = $q->all($db);
        $totalOrphans = count($orphans);

        if ($totalOrphans === 0) {
            $this->stdout("No orphan media rows match the filters. Nothing to do.\n", Console::FG_GREEN);
            return ExitCode::OK;
        }

        $this->stdout("Found {$totalOrphans} orphan media row(s) in wizard groupName buckets.\n\n");

        // ── 2. Pre-load uploaders' customer-creation history, batched. ──
        // For each uploader we'll need to walk forward in time to find the
        // customer matching each orphan. Cheaper to fetch each user's
        // customer creations once than to query per orphan.
        $uploaderIds = array_unique(array_map(static fn ($r) => (int)$r['createdBy'], $orphans));

        $customersByUploader = []; // [uploaderId => [['id' => …, 'created_at' => …], …] sorted asc]
        $customerRows = (new Query())
            ->select(['id', 'created_by', 'created_at', 'name'])
            ->from('{{%customers}}')
            ->where(['created_by' => $uploaderIds])
            ->andWhere(['or',
                ['is_deleted' => 0],
                ['is_deleted' => null],
            ])
            ->orderBy(['created_by' => SORT_ASC, 'created_at' => SORT_ASC])
            ->all($db);

        foreach ($customerRows as $row) {
            $uid = (int)$row['created_by'];
            $customersByUploader[$uid][] = [
                'id'         => (int)$row['id'],
                'created_at' => (int)$row['created_at'],
                'name'       => (string)$row['name'],
            ];
        }

        // ── 3. Walk every orphan, decide its fate. ──
        $stats = [
            'adopted'   => 0,
            'ambiguous' => 0,
            'no_match'  => 0,
            'errors'    => 0,
        ];
        $adoptionsByCustomer = []; // [customer_id => [[orphan_row], …]]

        foreach ($orphans as $m) {
            $uploaderId = (int)$m['createdBy'];
            $uploadedTs = strtotime((string)$m['created']);
            if ($uploadedTs === false) {
                $stats['errors']++;
                $this->stdout("  ! row #{$m['id']} has unparseable created='{$m['created']}', skipped.\n", Console::FG_RED);
                continue;
            }

            // Allow 5 minutes of clock-skew on the lower bound — wizards
            // sometimes finish a few seconds before the last upload row's
            // created timestamp due to DB write ordering.
            $minTs = $uploadedTs - 300;
            $maxTs = $uploadedTs + $windowSec;

            $candidates = [];
            foreach ($customersByUploader[$uploaderId] ?? [] as $c) {
                if ($c['created_at'] < $minTs) continue;
                if ($c['created_at'] > $maxTs) break;
                $candidates[] = $c;
            }

            if (count($candidates) === 0) {
                $stats['no_match']++;
                continue;
            }
            if (count($candidates) > 1) {
                $stats['ambiguous']++;
                $names = implode(', ', array_map(
                    static fn ($c) => "#{$c['id']} ({$c['name']})",
                    $candidates
                ));
                $this->stdout(sprintf(
                    "  ? media #%d (gn=%s, by user %d, at %s) is ambiguous — %d customers in window: %s\n",
                    (int)$m['id'], $m['groupName'], $uploaderId, $m['created'],
                    count($candidates), $names
                ), Console::FG_YELLOW);
                continue;
            }

            // Exactly one candidate — high confidence.
            $cust = $candidates[0];
            $adoptionsByCustomer[$cust['id']][] = $m + ['_target_name' => $cust['name']];
            $stats['adopted']++;
        }

        // ── 4. Print the planned adoptions, grouped by customer. ──
        $this->stdout("\n--- Planned adoptions ({$stats['adopted']} row(s) across "
            . count($adoptionsByCustomer) . " customer(s)) ---\n", Console::FG_CYAN);

        foreach ($adoptionsByCustomer as $custId => $rows) {
            $name = $rows[0]['_target_name'] ?? '';
            $this->stdout("  → customer #{$custId} ({$name}): " . count($rows) . " row(s)\n", Console::FG_GREEN);
            foreach ($rows as $r) {
                $this->stdout(sprintf(
                    "      media #%d  gn=%-8s  %s  (%s)\n",
                    (int)$r['id'], $r['groupName'], $r['created'], $r['fileName']
                ));
            }
        }

        // ── 5. Apply or pretend. ──
        if (!$this->apply) {
            $this->stdout("\nDRY-RUN summary: adopted=" . $stats['adopted']
                . ", ambiguous=" . $stats['ambiguous']
                . ", no_match=" . $stats['no_match']
                . ", errors=" . $stats['errors'] . "\n", Console::FG_CYAN, Console::BOLD);
            $this->stdout("\nRe-run with --apply to actually update os_ImageManager.\n\n");
            return ExitCode::OK;
        }

        $this->stdout("\nApplying updates...\n", Console::FG_CYAN);
        $totalUpdated = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($adoptionsByCustomer as $custId => $rows) {
            $ids = array_map(static fn ($r) => (int)$r['id'], $rows);
            try {
                $affected = $db->createCommand()->update(
                    'os_ImageManager',
                    ['customer_id' => (int)$custId, 'modified' => $now],
                    ['and',
                        ['id' => $ids],
                        ['customer_id' => null],
                    ]
                )->execute();
                $totalUpdated += (int)$affected;
                $this->stdout("  ✓ customer #{$custId}: {$affected} row(s) adopted.\n", Console::FG_GREEN);
            } catch (\Throwable $e) {
                $stats['errors']++;
                $this->stdout("  ✗ customer #{$custId} failed: " . $e->getMessage() . "\n", Console::FG_RED);
                Yii::error('RecoverOrphanMedia: customer #' . $custId
                    . ' update failed: ' . $e->getMessage(), __METHOD__);
            }
        }

        $this->stdout(sprintf(
            "\nDONE. Updated %d row(s). Skipped: ambiguous=%d, no_match=%d, errors=%d.\n\n",
            $totalUpdated, $stats['ambiguous'], $stats['no_match'], $stats['errors']
        ), Console::FG_CYAN, Console::BOLD);

        // Best-effort cache invalidation so Fahras shows the rescued rows
        // immediately without waiting for TTL expiry.
        try {
            $params = Yii::$app->params;
            $cache  = Yii::$app->cache;
            if (!empty($params['key_customers']) && !empty($params['customers_query'])) {
                $cache->set(
                    $params['key_customers'],
                    $db->createCommand($params['customers_query'])->queryAll(),
                    $params['time_duration'] ?? 3600
                );
                $this->stdout("Customers cache refreshed.\n", Console::FG_GREEN);
            }
        } catch (\Throwable $e) {
            $this->stdout("Cache refresh skipped: " . $e->getMessage() . "\n", Console::FG_YELLOW);
        }

        return ExitCode::OK;
    }
}
