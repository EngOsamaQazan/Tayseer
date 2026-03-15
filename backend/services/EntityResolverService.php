<?php

namespace backend\services;

use Yii;
use backend\dto\EntityDTO;
use backend\models\JudiciaryAuthority;

class EntityResolverService
{
    /**
     * Resolve a single entity by type + source ID.
     */
    public function resolve(string $type, int $sourceId): ?EntityDTO
    {
        switch ($type) {
            case 'bank':
                $row = Yii::$app->db->createCommand(
                    'SELECT id, bank_name FROM {{%bancks}} WHERE id = :id AND is_deleted = 0',
                    [':id' => $sourceId]
                )->queryOne();
                return $row ? new EntityDTO('bank', (int)$row['id'], $row['bank_name'], 'os_bancks') : null;

            case 'employer':
                $row = Yii::$app->db->createCommand(
                    'SELECT id, name FROM {{%jobs}} WHERE id = :id AND is_deleted = 0',
                    [':id' => $sourceId]
                )->queryOne();
                return $row ? new EntityDTO('employer', (int)$row['id'], $row['name'], 'os_jobs') : null;

            case 'administrative':
                $auth = JudiciaryAuthority::findOne($sourceId);
                return $auth ? new EntityDTO('administrative', $auth->id, $auth->name, 'os_judiciary_authorities') : null;

            default:
                return null;
        }
    }

    /**
     * Unified search across all entity types for Select2 AJAX.
     */
    public function search(string $query, ?string $type = null, ?int $companyId = null): array
    {
        $results = [];
        $like = '%' . $query . '%';

        if (!$type || $type === 'bank') {
            $cmd = Yii::$app->db->createCommand(
                'SELECT id, bank_name AS name FROM {{%bancks}} WHERE bank_name LIKE :q AND is_deleted = 0 LIMIT 20',
                [':q' => $like]
            );
            foreach ($cmd->queryAll() as $row) {
                $results[] = new EntityDTO('bank', (int)$row['id'], $row['name'], 'os_bancks');
            }
        }

        if (!$type || $type === 'employer') {
            $sql = 'SELECT id, name FROM {{%jobs}} WHERE name LIKE :q AND is_deleted = 0';
            $params = [':q' => $like];
            if ($companyId) {
                $sql .= ' AND company_id = :cid';
                $params[':cid'] = $companyId;
            }
            $sql .= ' LIMIT 20';
            foreach (Yii::$app->db->createCommand($sql, $params)->queryAll() as $row) {
                $results[] = new EntityDTO('employer', (int)$row['id'], $row['name'], 'os_jobs');
            }
        }

        if (!$type || $type === 'administrative') {
            $q = JudiciaryAuthority::find()->andWhere(['like', 'name', $query])->limit(20);
            if ($companyId) {
                $q->andWhere(['or', ['company_id' => $companyId], ['company_id' => null]]);
            }
            foreach ($q->all() as $auth) {
                $results[] = new EntityDTO('administrative', $auth->id, $auth->name, 'os_judiciary_authorities');
            }
        }

        return $results;
    }

    /**
     * Get dropdown list for forms, optionally filtered by type.
     */
    public function getDropdownList(?string $type = null, ?int $companyId = null): array
    {
        $cacheKey = 'entity_dropdown_' . ($type ?? 'all') . '_' . ($companyId ?? '0');
        $cache = Yii::$app->cache ?? null;

        if ($cache) {
            $cached = $cache->get($cacheKey);
            if ($cached !== false) {
                return $cached;
            }
        }

        $list = [];

        if (!$type || $type === 'bank') {
            $banks = Yii::$app->db->createCommand(
                'SELECT id, bank_name FROM {{%bancks}} WHERE is_deleted = 0 ORDER BY bank_name'
            )->queryAll();
            foreach ($banks as $row) {
                $list[] = ['id' => 'bank_' . $row['id'], 'text' => $row['bank_name'], 'type' => 'bank', 'source_id' => (int)$row['id']];
            }
        }

        if (!$type || $type === 'employer') {
            $sql = 'SELECT id, name FROM {{%jobs}} WHERE is_deleted = 0';
            $params = [];
            if ($companyId) {
                $sql .= ' AND company_id = :cid';
                $params[':cid'] = $companyId;
            }
            $sql .= ' ORDER BY name';
            foreach (Yii::$app->db->createCommand($sql, $params)->queryAll() as $row) {
                $list[] = ['id' => 'employer_' . $row['id'], 'text' => $row['name'], 'type' => 'employer', 'source_id' => (int)$row['id']];
            }
        }

        if (!$type || $type === 'administrative') {
            $q = JudiciaryAuthority::find()->orderBy('name');
            if ($companyId) {
                $q->andWhere(['or', ['company_id' => $companyId], ['company_id' => null]]);
            }
            foreach ($q->all() as $auth) {
                $list[] = ['id' => 'administrative_' . $auth->id, 'text' => $auth->name, 'type' => 'administrative', 'source_id' => $auth->id];
            }
        }

        if ($cache) {
            $cache->set($cacheKey, $list, 3600);
        }

        return $list;
    }

    /**
     * Quick display name lookup for grid columns and detail views.
     */
    public function getDisplayName(string $type, int $sourceId): string
    {
        $entity = $this->resolve($type, $sourceId);
        return $entity ? $entity->display_name : '';
    }
}
