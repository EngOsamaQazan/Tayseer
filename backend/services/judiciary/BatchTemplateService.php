<?php

namespace backend\services\judiciary;

use Yii;
use backend\modules\judiciary\models\JudiciaryBatchTemplate;
use common\helper\Permissions;

/**
 * Thin CRUD wrapper around os_judiciary_batch_templates.
 *
 * Templates are intentionally company-wide-shared: every JUD_CREATE user
 * can list/load any template. Delete is restricted: only the template's
 * creator or a JUD_DELETE-bearing user may remove one.
 */
class BatchTemplateService
{
    /**
     * @param bool $includeData when true, embeds the JSON `data` payload in each row.
     * @return array<int, array{id:int, name:string, created_by:int, created_by_name:?string, usage_count:int, created_at:int, data?:array}>
     */
    public function listTemplates(bool $includeData = false): array
    {
        $select = [
            't.id', 't.name', 't.created_by', 't.usage_count', 't.created_at',
            'created_by_name' => 'u.username',
        ];
        if ($includeData) {
            $select[] = 't.data';
        }

        $rows = (new \yii\db\Query())
            ->from(JudiciaryBatchTemplate::tableName() . ' t')
            ->leftJoin('os_user u', 'u.id = t.created_by')
            ->select($select)
            ->where(['t.is_deleted' => 0])
            ->orderBy(['t.usage_count' => SORT_DESC, 't.created_at' => SORT_DESC])
            ->all();

        return array_map(function ($r) use ($includeData) {
            $out = [
                'id'              => (int)$r['id'],
                'name'            => (string)$r['name'],
                'created_by'      => (int)$r['created_by'],
                'created_by_name' => $r['created_by_name'] ?? null,
                'usage_count'     => (int)$r['usage_count'],
                'created_at'      => (int)$r['created_at'],
            ];
            if ($includeData) {
                $decoded = json_decode((string)($r['data'] ?? ''), true);
                $out['data'] = is_array($decoded) ? $decoded : [];
            }
            return $out;
        }, $rows);
    }

    public function saveTemplate(string $name, array $data, int $userId): int
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('اسم القالب مطلوب');
        }
        if (mb_strlen($name) > 100) {
            $name = mb_substr($name, 0, 100);
        }

        $tpl = new JudiciaryBatchTemplate();
        $tpl->name        = $name;
        $tpl->created_by  = $userId;
        $tpl->setDataArray($this->sanitizeData($data));

        if (!$tpl->save()) {
            throw new \RuntimeException('فشل حفظ القالب: ' . json_encode($tpl->errors, JSON_UNESCAPED_UNICODE));
        }
        return (int)$tpl->id;
    }

    /**
     * @return array{id:int, name:string, data:array}|null
     */
    public function loadTemplate(int $id): ?array
    {
        /** @var JudiciaryBatchTemplate|null $tpl */
        $tpl = JudiciaryBatchTemplate::findOne($id);
        if (!$tpl || (int)$tpl->is_deleted === 1) return null;

        return [
            'id'   => (int)$tpl->id,
            'name' => $tpl->name,
            'data' => $tpl->getDataArray(),
        ];
    }

    public function deleteTemplate(int $id, int $userId): bool
    {
        /** @var JudiciaryBatchTemplate|null $tpl */
        $tpl = JudiciaryBatchTemplate::findOne($id);
        if (!$tpl || (int)$tpl->is_deleted === 1) return false;

        $isOwner   = ((int)$tpl->created_by === $userId);
        $isManager = Permissions::can(Permissions::JUD_DELETE);
        if (!$isOwner && !$isManager) {
            throw new \DomainException('حذف القوالب متاح للمنشئ أو من يمتلك صلاحية حذف القضاء');
        }

        $tpl->is_deleted = 1;
        return (bool)$tpl->save(false);
    }

    public function incrementUsage(int $id): void
    {
        Yii::$app->db->createCommand()
            ->update(
                JudiciaryBatchTemplate::tableName(),
                ['usage_count' => new \yii\db\Expression('usage_count + 1')],
                ['id' => $id]
            )->execute();
    }

    /**
     * Whitelist + cast — never trust the wizard JSON blindly.
     */
    private function sanitizeData(array $data): array
    {
        $clean = [];

        foreach (['court_id', 'lawyer_id', 'type_id', 'company_id', 'address_id'] as $intKey) {
            if (isset($data[$intKey]) && $data[$intKey] !== '') {
                $clean[$intKey] = (int)$data[$intKey];
            }
        }
        if (isset($data['percentage']) && $data['percentage'] !== '') {
            $clean['percentage'] = (float)$data['percentage'];
        }
        foreach (['year', 'address_mode', 'auto_action_name'] as $strKey) {
            if (isset($data[$strKey])) {
                $clean[$strKey] = (string)$data[$strKey];
            }
        }
        if (isset($data['auto_print'])) {
            $clean['auto_print'] = (bool)$data['auto_print'];
        }
        return $clean;
    }
}
