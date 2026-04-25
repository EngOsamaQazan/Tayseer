<?php

namespace backend\services\judiciary;

use Yii;
use yii\db\Exception as DbException;
use backend\modules\judiciary\models\Judiciary;
use backend\modules\judiciary\models\JudiciaryBatch;
use backend\modules\judiciary\models\JudiciaryBatchItem;
use backend\modules\judiciaryActions\models\JudiciaryActions;
use backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions;
use backend\modules\contractDocumentFile\models\ContractDocumentFile;
use backend\modules\contracts\models\Contracts;
use backend\modules\customers\models\ContractsCustomers;
use backend\modules\JudiciaryInformAddress\model\JudiciaryInformAddress;
use backend\modules\followUp\helper\ContractCalculations;

/**
 * Drives the batch creation workflow end-to-end.
 *
 * Lifecycle:
 *   1) startBatch()      → snapshot config, create batch + N pending items, return ID + chunks plan.
 *   2) executeChunk()    → process N contracts inside one DB transaction; rollback only that chunk on failure.
 *   3) finalizeBatch()   → finalize counters/status, refresh judiciary list cache.
 *   4) revertBatch()     → soft-delete cases created within the 72h window (only those untouched since).
 *
 * Why chunked: 100 cases × 2 parties × deadline/stage hooks = heavy. A single
 * transaction would risk timeouts and eat the whole batch on any single error.
 * Per-chunk transactions isolate failures and let the UI render real progress.
 */
class BatchCreateService
{
    public const CHUNK_SIZE   = 10;
    public const MAX_CONTRACTS = 100;

    /** Cached resolved id of the "تجهيز قضية" action for this request. */
    private ?int $cachedCasePrepActionId = null;

    /* ──────────────────────────── public API ──────────────────────────── */

    /**
     * Create the batch row + one item row per contract.
     *
     * @param array $config {
     *   contract_ids: int[],
     *   shared: array{
     *      court_id:int, lawyer_id:int, type_id:int, percentage:float, year:string,
     *      address_mode:'fixed'|'random', address_id:?int, company_id:?int,
     *      auto_print:bool
     *   },
     *   overrides: array<int contract_id, array> // per-row overrides
     *   entry_method: 'paste'|'excel'|'selection',
     *   user_id: int
     * }
     * @return array{batch_id:int, batch_uuid:string, total:int, chunks:int}
     */
    public function startBatch(array $config): array
    {
        $contractIds = array_values(array_unique(array_map('intval', $config['contract_ids'] ?? [])));
        $contractIds = array_filter($contractIds, fn($id) => $id > 0);

        if (empty($contractIds)) {
            throw new \InvalidArgumentException('لا توجد عقود في الدفعة');
        }
        if (count($contractIds) > self::MAX_CONTRACTS) {
            throw new \InvalidArgumentException('الحد الأعلى للدفعة ' . self::MAX_CONTRACTS . ' عقد');
        }

        $shared = $config['shared'] ?? [];
        $this->validateShared($shared);

        $overrides = $config['overrides'] ?? [];
        $userId    = (int)($config['user_id'] ?? Yii::$app->user->id);
        $entry     = $config['entry_method'] ?? JudiciaryBatch::ENTRY_PASTE;

        // Persist the auto-action name in shared_data (resolved per-tenant at execution).
        $shared['auto_action_name'] = $shared['auto_action_name'] ?? 'تجهيز قضية';

        $batch = new JudiciaryBatch();
        $batch->batch_uuid     = $this->uuidv4();
        $batch->created_by     = $userId;
        $batch->created_at     = time();
        $batch->entry_method   = in_array($entry, [JudiciaryBatch::ENTRY_PASTE, JudiciaryBatch::ENTRY_EXCEL, JudiciaryBatch::ENTRY_SELECTION], true)
            ? $entry : JudiciaryBatch::ENTRY_PASTE;
        $batch->contract_count = count($contractIds);
        $batch->success_count  = 0;
        $batch->failed_count   = 0;
        $batch->status         = JudiciaryBatch::STATUS_RUNNING;
        $batch->setSharedData($shared);

        if (!$batch->save()) {
            throw new DbException('فشل إنشاء سجل الدفعة: ' . json_encode($batch->errors, JSON_UNESCAPED_UNICODE));
        }

        $now = time();
        // Bulk insert items for performance — one round trip instead of N.
        $rows = [];
        foreach ($contractIds as $cid) {
            $rowOv = $overrides[$cid] ?? [];
            $rows[] = [
                'batch_id'                 => $batch->id,
                'contract_id'              => $cid,
                'judiciary_id'             => null,
                'previous_contract_status' => null,
                'status'                   => JudiciaryBatchItem::STATUS_PENDING,
                'error_message'            => null,
                'overrides'                => empty($rowOv) ? null : json_encode($rowOv, JSON_UNESCAPED_UNICODE),
                'created_at'               => $now,
            ];
        }
        Yii::$app->db->createCommand()->batchInsert(
            JudiciaryBatchItem::tableName(),
            ['batch_id', 'contract_id', 'judiciary_id', 'previous_contract_status', 'status', 'error_message', 'overrides', 'created_at'],
            $rows
        )->execute();

        return [
            'batch_id'   => $batch->id,
            'batch_uuid' => $batch->batch_uuid,
            'total'      => count($contractIds),
            'chunks'     => (int)ceil(count($contractIds) / self::CHUNK_SIZE),
            'chunk_size' => self::CHUNK_SIZE,
        ];
    }

    /**
     * Process one chunk (default 10 contracts) inside its own transaction.
     *
     * The caller (controller) computes which item ids belong to which chunk and
     * passes them in. We only act on items still in 'pending' state, so the
     * client can safely retry a chunk if the network call dropped.
     *
     * @return array{processed:int, success:int, failed:int, details: array<int, array{contract_id:int, status:string, judiciary_id:?int, message:?string}>}
     */
    public function executeChunk(int $batchId, array $itemIds): array
    {
        if (empty($itemIds)) {
            return ['processed' => 0, 'success' => 0, 'failed' => 0, 'details' => []];
        }
        /** @var JudiciaryBatch|null $batch */
        $batch = JudiciaryBatch::findOne($batchId);
        if (!$batch) {
            throw new \InvalidArgumentException('الدفعة غير موجودة');
        }
        $shared = $batch->getSharedData();

        /** @var JudiciaryBatchItem[] $items */
        $items = JudiciaryBatchItem::find()
            ->where(['id' => $itemIds, 'batch_id' => $batchId, 'status' => JudiciaryBatchItem::STATUS_PENDING])
            ->all();

        $details = [];
        $success = 0;
        $failed  = 0;

        foreach ($items as $item) {
            $tx = Yii::$app->db->beginTransaction();
            try {
                $this->createOneCase($item, $shared);
                $tx->commit();
                $item->status = JudiciaryBatchItem::STATUS_SUCCESS;
                $item->save(false);
                $success++;
                $details[] = [
                    'contract_id'  => $item->contract_id,
                    'status'       => 'success',
                    'judiciary_id' => $item->judiciary_id,
                    'message'      => null,
                ];
            } catch (\Throwable $e) {
                $tx->rollBack();
                // Discard in-memory dirty attributes (e.g., judiciary_id set
                // during createOneCase) so they don't leak past the rollback.
                $item->refresh();
                $item->status        = JudiciaryBatchItem::STATUS_FAILED;
                $item->error_message = mb_substr($e->getMessage(), 0, 500);
                $item->save(false);
                $failed++;
                $details[] = [
                    'contract_id'  => $item->contract_id,
                    'status'       => 'failed',
                    'judiciary_id' => null,
                    'message'      => $item->error_message,
                ];
            }
        }

        // Atomic counter bump — avoids races with parallel chunks.
        Yii::$app->db->createCommand()
            ->update(JudiciaryBatch::tableName(),
                [
                    'success_count' => new \yii\db\Expression('success_count + :s', [':s' => $success]),
                    'failed_count'  => new \yii\db\Expression('failed_count + :f', [':f' => $failed]),
                ],
                ['id' => $batchId])
            ->execute();

        return ['processed' => count($items), 'success' => $success, 'failed' => $failed, 'details' => $details];
    }

    /**
     * Mark batch terminal status + bust the judiciary list cache.
     */
    public function finalizeBatch(int $batchId): array
    {
        /** @var JudiciaryBatch|null $batch */
        $batch = JudiciaryBatch::findOne($batchId);
        if (!$batch) {
            throw new \InvalidArgumentException('الدفعة غير موجودة');
        }
        // Recompute counters from items in case of edge cases (e.g., out-of-band saves).
        $totals = (new \yii\db\Query())
            ->from(JudiciaryBatchItem::tableName())
            ->where(['batch_id' => $batchId])
            ->select([
                'success' => "SUM(CASE WHEN status='success' THEN 1 ELSE 0 END)",
                'failed'  => "SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END)",
            ])
            ->one();
        $success = (int)($totals['success'] ?? 0);
        $failed  = (int)($totals['failed']  ?? 0);

        $batch->success_count = $success;
        $batch->failed_count  = $failed;
        $batch->status = $failed === 0
            ? JudiciaryBatch::STATUS_COMPLETED
            : ($success > 0 ? JudiciaryBatch::STATUS_PARTIAL : JudiciaryBatch::STATUS_PARTIAL);
        $batch->save(false);

        $this->refreshJudiciaryCache();

        $createdJudiciaryIds = JudiciaryBatchItem::find()
            ->select('judiciary_id')
            ->where(['batch_id' => $batchId, 'status' => JudiciaryBatchItem::STATUS_SUCCESS])
            ->andWhere(['IS NOT', 'judiciary_id', null])
            ->column();

        return [
            'batch_id'             => $batchId,
            'batch_uuid'           => $batch->batch_uuid,
            'success'              => $success,
            'failed'               => $failed,
            'status'               => $batch->status,
            'created_judiciary_ids'=> array_map('intval', $createdJudiciaryIds),
            'auto_print'           => (bool)($batch->getSharedData()['auto_print'] ?? false),
        ];
    }

    /**
     * Audit-only check: who/when/what is reverteable. Does not mutate.
     *
     * @return array{
     *   allowed:bool, reason:?string,
     *   total:int, revertable:int, locked:int,
     *   locked_items: array<int, array{contract_id:int, judiciary_id:int, reason:string}>
     * }
     */
    public function canRevertBatch(int $batchId, int $userId, bool $isManager): array
    {
        /** @var JudiciaryBatch|null $batch */
        $batch = JudiciaryBatch::findOne($batchId);
        if (!$batch) {
            return ['allowed' => false, 'reason' => 'الدفعة غير موجودة', 'total' => 0, 'revertable' => 0, 'locked' => 0, 'locked_items' => []];
        }
        if ($batch->status === JudiciaryBatch::STATUS_REVERTED) {
            return ['allowed' => false, 'reason' => 'الدفعة سبق التراجع عنها', 'total' => 0, 'revertable' => 0, 'locked' => 0, 'locked_items' => []];
        }
        if (!$batch->isWithinRevertWindow()) {
            return ['allowed' => false, 'reason' => 'انتهت نافذة التراجع (72 ساعة)', 'total' => 0, 'revertable' => 0, 'locked' => 0, 'locked_items' => []];
        }
        if ($batch->created_by !== $userId && !$isManager) {
            return ['allowed' => false, 'reason' => 'التراجع متاح للمنشئ أو المدير فقط', 'total' => 0, 'revertable' => 0, 'locked' => 0, 'locked_items' => []];
        }

        /** @var JudiciaryBatchItem[] $items */
        $items = JudiciaryBatchItem::find()
            ->where(['batch_id' => $batchId, 'status' => JudiciaryBatchItem::STATUS_SUCCESS])
            ->andWhere(['IS NOT', 'judiciary_id', null])
            ->all();

        $revertable = 0;
        $locked = [];
        foreach ($items as $it) {
            if ($this->itemHasPostCreationActions($it)) {
                $locked[] = [
                    'contract_id'  => (int)$it->contract_id,
                    'judiciary_id' => (int)$it->judiciary_id,
                    'reason'       => 'تم تسجيل إجراءات لاحقة على هذه القضية',
                ];
            } else {
                $revertable++;
            }
        }

        return [
            'allowed'      => true,
            'reason'       => null,
            'total'        => count($items),
            'revertable'   => $revertable,
            'locked'       => count($locked),
            'locked_items' => $locked,
        ];
    }

    /**
     * Soft-delete every revertable case in the batch.
     *
     * @return array{success:int, skipped:int, locked:int}
     */
    public function revertBatch(int $batchId, string $reason, int $userId, bool $isManager): array
    {
        $check = $this->canRevertBatch($batchId, $userId, $isManager);
        if (!$check['allowed']) {
            throw new \DomainException($check['reason'] ?? 'التراجع غير مسموح');
        }

        /** @var JudiciaryBatch $batch */
        $batch = JudiciaryBatch::findOne($batchId);

        /** @var JudiciaryBatchItem[] $items */
        $items = JudiciaryBatchItem::find()
            ->where(['batch_id' => $batchId, 'status' => JudiciaryBatchItem::STATUS_SUCCESS])
            ->andWhere(['IS NOT', 'judiciary_id', null])
            ->all();

        $reverted = 0;
        $locked   = 0;

        foreach ($items as $it) {
            if ($this->itemHasPostCreationActions($it)) {
                $locked++;
                continue;
            }
            $tx = Yii::$app->db->beginTransaction();
            try {
                $jid = (int)$it->judiciary_id;
                // Soft-delete the auto-action rows (created with action_date = today, judiciary_id = jid).
                Yii::$app->db->createCommand()
                    ->update(
                        JudiciaryCustomersActions::tableName(),
                        ['is_deleted' => 1],
                        ['judiciary_id' => $jid]
                    )->execute();
                // Soft-delete the contract document file row created by the batch.
                Yii::$app->db->createCommand()
                    ->update(
                        ContractDocumentFile::tableName(),
                        ['is_deleted' => 1],
                        ['contract_id' => $jid, 'document_type' => 'judiciary file']
                    )->execute();
                // Soft-delete the case itself.
                Yii::$app->db->createCommand()
                    ->update(Judiciary::tableName(), ['is_deleted' => 1], ['id' => $jid])
                    ->execute();
                // Bring back the contract's computed status now that no active case exists.
                Contracts::refreshContractStatus((int)$it->contract_id);

                $it->status = JudiciaryBatchItem::STATUS_REVERTED;
                $it->save(false);
                $tx->commit();
                $reverted++;
            } catch (\Throwable $e) {
                $tx->rollBack();
                Yii::warning('Revert failed for item ' . $it->id . ': ' . $e->getMessage(), __METHOD__);
                $locked++;
            }
        }

        $batch->status        = JudiciaryBatch::STATUS_REVERTED;
        $batch->reverted_at   = time();
        $batch->reverted_by   = $userId;
        $batch->revert_reason = mb_substr($reason, 0, 255);
        $batch->save(false);

        $this->refreshJudiciaryCache();

        return ['success' => $reverted, 'skipped' => 0, 'locked' => $locked];
    }

    /* ──────────────────────────── internals ──────────────────────────── */

    private function validateShared(array $shared): void
    {
        foreach (['court_id', 'lawyer_id'] as $req) {
            if (empty($shared[$req])) {
                throw new \InvalidArgumentException("حقل {$req} مطلوب في البيانات المشتركة");
            }
        }
        if (!isset($shared['address_mode']) || !in_array($shared['address_mode'], ['fixed', 'random'], true)) {
            throw new \InvalidArgumentException('address_mode يجب أن يكون fixed أو random');
        }
        if ($shared['address_mode'] === 'fixed' && empty($shared['address_id'])) {
            throw new \InvalidArgumentException('عند اختيار موطن ثابت يجب تمرير address_id');
        }
    }

    /**
     * Per-contract creation. Always called inside a transaction by executeChunk().
     * Mirrors the side-effects of JudiciaryController::actionCreate() so that
     * the resulting cases are indistinguishable from individually-created ones.
     */
    private function createOneCase(JudiciaryBatchItem $item, array $shared): void
    {
        $contractId = (int)$item->contract_id;

        $contract = Contracts::findOne($contractId);
        if (!$contract) {
            throw new \RuntimeException('العقد غير موجود');
        }

        // Race-guard: skip if a non-deleted case already exists for this contract.
        $existing = Judiciary::find()->where(['contract_id' => $contractId, 'is_deleted' => 0])->one();
        if ($existing) {
            $item->judiciary_id = (int)$existing->id;
            throw new \RuntimeException('للعقد قضية مسبقة #' . $existing->id);
        }

        $row = $this->mergeOverrides($shared, $item->getOverridesArray());

        $vb = ContractCalculations::fromView($contractId);
        $remaining  = $vb ? (float)$vb['remaining'] : 0.0;
        $percentage = (float)($row['percentage'] ?? 0);
        $lawyerCost = $percentage > 0 ? round($remaining * ($percentage / 100), 2) : 0.0;

        $addressId = $this->resolveAddressId(
            (string)($row['address_mode'] ?? 'fixed'),
            isset($row['address_id']) && $row['address_id'] !== '' ? (int)$row['address_id'] : null
        );

        // Snapshot contract.status BEFORE mutation, for audit.
        $item->previous_contract_status = $contract->status;

        $model = new Judiciary();
        $model->contract_id                 = $contractId;
        $model->court_id                    = (int)$row['court_id'];
        $model->type_id                     = (int)($row['type_id'] ?? 0) ?: $this->resolveDefaultTypeId();
        $model->lawyer_id                   = (int)$row['lawyer_id'];
        $model->company_id                  = !empty($row['company_id']) ? (int)$row['company_id'] : (int)$contract->company_id;
        $model->judiciary_inform_address_id = $addressId;
        $model->lawyer_cost                 = $lawyerCost;
        $model->case_cost                   = 0;
        $model->year                        = (string)($row['year'] ?? date('Y'));
        $model->income_date                 = date('Y-m-d');

        if (!$model->save(false)) {
            throw new \RuntimeException('فشل حفظ القضية');
        }

        $item->judiciary_id = (int)$model->id;
        $item->save(false);

        // Mirror legacy: keep contract.company_id consistent with case.
        if ($model->company_id) {
            Contracts::updateAll(['company_id' => $model->company_id], ['id' => $contractId]);
        }

        $autoActionId = $this->resolveCasePreparationActionId();

        $contractCustomers = ContractsCustomers::find()
            ->where(['contract_id' => $contractId])
            ->all();
        if (empty($contractCustomers)) {
            // Some contracts may have no party rows; record one case-level action with customers_id=0
            // is forbidden (NOT NULL). Skip silently — case is still created.
            return;
        }
        foreach ($contractCustomers as $cc) {
            $action = new JudiciaryCustomersActions();
            $action->judiciary_id          = (int)$model->id;
            $action->customers_id          = (int)$cc->customer_id;
            $action->judiciary_actions_id  = $autoActionId;
            $action->note                  = null;
            $action->action_date           = date('Y-m-d');
            $action->save(false);
        }

        $docFile = new ContractDocumentFile();
        $docFile->document_type = 'judiciary file';
        $docFile->contract_id   = (int)$model->id;
        $docFile->save(false);
    }

    /**
     * Merge per-row overrides on top of shared config. Only whitelisted keys.
     */
    private function mergeOverrides(array $shared, array $overrides): array
    {
        $allowed = ['lawyer_id', 'type_id', 'company_id', 'address_id', 'address_mode'];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $overrides) && $overrides[$k] !== null && $overrides[$k] !== '') {
                $shared[$k] = $overrides[$k];
            }
        }
        return $shared;
    }

    /**
     * Resolve "تجهيز قضية" action id, idempotently creating it if missing.
     * Caches per-request to avoid repeat lookups during a chunk.
     */
    public function resolveCasePreparationActionId(): int
    {
        if ($this->cachedCasePrepActionId !== null) {
            return $this->cachedCasePrepActionId;
        }

        // Prefer match by action_type, then by Arabic name (singular/plural variants).
        $row = Yii::$app->db->createCommand(
            "SELECT id FROM " . JudiciaryActions::tableName() . "
             WHERE (action_type = :t OR name REGEXP :rx)
               AND (is_deleted = 0 OR is_deleted IS NULL)
             ORDER BY (action_type = :t) DESC, id ASC
             LIMIT 1",
            [':t' => 'case_preparation', ':rx' => 'تجهيز[[:space:]]*(القضية|قضية|القضيه|قضيه)']
        )->queryScalar();

        if ($row) {
            $this->cachedCasePrepActionId = (int)$row;
            return $this->cachedCasePrepActionId;
        }

        // Idempotent seed for this tenant.
        $now = time();
        Yii::$app->db->createCommand()->insert(JudiciaryActions::tableName(), [
            'name'          => 'تجهيز قضية',
            'action_type'   => 'case_preparation',
            'action_nature' => 'process',
            'is_deleted'    => 0,
        ])->execute();

        $newId = (int)Yii::$app->db->getLastInsertID();
        $this->cachedCasePrepActionId = $newId;
        return $newId;
    }

    private function resolveDefaultTypeId(): int
    {
        // Prefer "تنفيذ" by name, fall back to first available type.
        $row = (new \yii\db\Query())
            ->from('os_judiciary_type')
            ->select('id')
            ->where(['like', 'name', 'تنفيذ'])
            ->andWhere(['or', ['is_deleted' => 0], ['is_deleted' => null]])
            ->limit(1)
            ->scalar();
        if ($row) return (int)$row;
        $row = (new \yii\db\Query())
            ->from('os_judiciary_type')
            ->select('id')
            ->andWhere(['or', ['is_deleted' => 0], ['is_deleted' => null]])
            ->orderBy(['id' => SORT_ASC])
            ->limit(1)
            ->scalar();
        return (int)($row ?: 1);
    }

    private function resolveAddressId(string $mode, $fixedId): int
    {
        $fixedId = ($fixedId === null || $fixedId === '') ? null : (int)$fixedId;
        if ($mode === 'fixed' && $fixedId) {
            return $fixedId;
        }
        // Random — uniform across all available addresses.
        $row = (new \yii\db\Query())
            ->from(JudiciaryInformAddress::tableName())
            ->select('id')
            ->andWhere(['or', ['is_deleted' => 0], ['is_deleted' => null]])
            ->orderBy(new \yii\db\Expression('RAND()'))
            ->limit(1)
            ->scalar();
        return (int)($row ?: 1);
    }

    /**
     * Returns true when the case has any post-creation customer-action that
     * wasn't part of the original "تجهيز قضية" auto-batch.
     *
     * Heuristic: a case starts with one auto-action per party (created by
     * createOneCase). If the count of non-deleted actions exceeds the number
     * of parties on that contract, something else has been logged → locked.
     */
    private function itemHasPostCreationActions(JudiciaryBatchItem $it): bool
    {
        if (!$it->judiciary_id) return false;

        $autoActionId = $this->resolveCasePreparationActionId();

        $partyCount = (int)ContractsCustomers::find()
            ->where(['contract_id' => $it->contract_id])
            ->count();

        $totalActions = (int)Yii::$app->db->createCommand(
            "SELECT COUNT(*) FROM " . JudiciaryCustomersActions::tableName() .
            " WHERE judiciary_id = :j AND (is_deleted = 0 OR is_deleted IS NULL)",
            [':j' => (int)$it->judiciary_id]
        )->queryScalar();

        $autoActions = (int)Yii::$app->db->createCommand(
            "SELECT COUNT(*) FROM " . JudiciaryCustomersActions::tableName() .
            " WHERE judiciary_id = :j AND judiciary_actions_id = :a AND (is_deleted = 0 OR is_deleted IS NULL)",
            [':j' => (int)$it->judiciary_id, ':a' => $autoActionId]
        )->queryScalar();

        // Anything beyond the original auto-actions = manual modification → block revert.
        if ($totalActions > $autoActions) return true;
        // Or if the operator added MORE auto-actions manually (rare but possible).
        if ($autoActions > $partyCount) return true;
        return false;
    }

    private function refreshJudiciaryCache(): void
    {
        try {
            if (isset(Yii::$app->params['key_judiciary_contract'])) {
                Yii::$app->cache->set(
                    Yii::$app->params['key_judiciary_contract'],
                    Yii::$app->db->createCommand(Yii::$app->params['judiciary_contract_query'])->queryAll(),
                    Yii::$app->params['time_duration'] ?? 3600
                );
                Yii::$app->cache->set(
                    Yii::$app->params['key_judiciary_year'],
                    Yii::$app->db->createCommand(Yii::$app->params['judiciary_year_query'])->queryAll(),
                    Yii::$app->params['time_duration'] ?? 3600
                );
            }
        } catch (\Throwable $e) {
            // Cache is best-effort; never let it sink a batch.
        }
    }

    private function uuidv4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
