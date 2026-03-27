<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int         $id
 * @property int         $user_id
 * @property string      $draft_key
 * @property int         $is_auto        1 = حفظ تلقائي, 0 = مسودة محفوظة يدوياً
 * @property string|null $draft_label
 * @property string|null $items_summary  ملخص أسماء الأصناف (للعرض السريع)
 * @property string      $draft_data     JSON
 * @property int         $updated_at
 */
class WizardDraft extends ActiveRecord
{
    const MAX_SAVED_DRAFTS = 3;

    public static function tableName()
    {
        return '{{%wizard_drafts}}';
    }

    /* ─── الحفظ التلقائي (مسودة واحدة auto لكل مستخدم) ─── */

    public static function loadAutoDraft($userId, $draftKey)
    {
        return static::find()
            ->where(['user_id' => $userId, 'draft_key' => $draftKey, 'is_auto' => 1])
            ->one();
    }

    public static function saveAutoDraft($userId, $draftKey, $data)
    {
        $model = static::loadAutoDraft($userId, $draftKey);
        if (!$model) {
            $model = new static();
            $model->user_id   = $userId;
            $model->draft_key = $draftKey;
            $model->is_auto   = 1;
        }
        $json = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);
        $model->draft_data = $json;
        $model->items_summary = static::extractSummary($json);
        $model->updated_at = time();
        return $model->save(false);
    }

    public static function clearAutoDraft($userId, $draftKey)
    {
        return static::deleteAll(['user_id' => $userId, 'draft_key' => $draftKey, 'is_auto' => 1]);
    }

    /* ─── المسودات المحفوظة يدوياً (حد أقصى 3) ─── */

    public static function listSavedDrafts($userId, $draftKey)
    {
        return static::find()
            ->where(['user_id' => $userId, 'draft_key' => $draftKey, 'is_auto' => 0])
            ->orderBy(['updated_at' => SORT_DESC])
            ->all();
    }

    public static function saveDraftSlot($userId, $draftKey, $data, $label = null)
    {
        $existing = static::listSavedDrafts($userId, $draftKey);
        if (count($existing) >= self::MAX_SAVED_DRAFTS) {
            $oldest = end($existing);
            $oldest->delete();
        }
        $model = new static();
        $model->user_id     = $userId;
        $model->draft_key   = $draftKey;
        $model->is_auto     = 0;
        $json = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);
        $model->draft_data  = $json;
        $model->draft_label = $label ?: ('مسودة ' . date('Y-m-d H:i'));
        $model->items_summary = static::extractSummary($json);
        $model->updated_at  = time();
        return $model->save(false);
    }

    public static function deleteSavedDraft($userId, $draftKey, $draftId)
    {
        return static::deleteAll([
            'id'        => $draftId,
            'user_id'   => $userId,
            'draft_key' => $draftKey,
            'is_auto'   => 0,
        ]);
    }

    public static function loadSavedDraft($userId, $draftKey, $draftId)
    {
        return static::find()
            ->where([
                'id'        => $draftId,
                'user_id'   => $userId,
                'draft_key' => $draftKey,
                'is_auto'   => 0,
            ])
            ->one();
    }

    /* ─── backward compat aliases ─── */

    public static function loadDraft($userId, $draftKey)
    {
        return static::loadAutoDraft($userId, $draftKey);
    }

    public static function saveDraft($userId, $draftKey, $data)
    {
        return static::saveAutoDraft($userId, $draftKey, $data);
    }

    public static function clearDraft($userId, $draftKey)
    {
        return static::clearAutoDraft($userId, $draftKey);
    }

    /* ─── helpers ─── */

    protected static function extractSummary($json)
    {
        $decoded = is_string($json) ? json_decode($json, true) : $json;
        if (!$decoded || empty($decoded['selectedItems'])) return null;
        $names = [];
        foreach ($decoded['selectedItems'] as $item) {
            $names[] = $item['name'] ?? $item['text'] ?? '?';
        }
        $summary = implode('، ', $names);
        return mb_strlen($summary) > 250 ? mb_substr($summary, 0, 247) . '...' : $summary;
    }
}
