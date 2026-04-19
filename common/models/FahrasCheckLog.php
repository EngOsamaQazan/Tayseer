<?php

namespace common\models;

use common\services\dto\FahrasVerdict;
use Yii;
use yii\db\ActiveRecord;

/**
 * ActiveRecord for the {@see m260420_100000_create_fahras_check_log} table.
 *
 * @property int         $id
 * @property int|null    $user_id
 * @property int|null    $customer_id
 * @property string      $id_number
 * @property string|null $name
 * @property string|null $phone
 * @property string      $verdict
 * @property string|null $reason_code
 * @property string|null $reason_ar
 * @property mixed       $matches_json
 * @property int|null    $override_user_id
 * @property string|null $override_reason
 * @property int|null    $http_status
 * @property string|null $request_id
 * @property int|null    $duration_ms
 * @property bool|int    $from_cache
 * @property string|null $source
 * @property int         $created_at
 */
class FahrasCheckLog extends ActiveRecord
{
    public const SOURCE_STEP1   = 'step1';
    public const SOURCE_FINISH  = 'finish';
    public const SOURCE_MANUAL  = 'manual';
    public const SOURCE_SEARCH  = 'search';

    public static function tableName(): string
    {
        return '{{%fahras_check_log}}';
    }

    public function rules(): array
    {
        return [
            [['id_number', 'verdict', 'created_at'], 'required'],
            [['id_number'], 'string', 'max' => 20],
            [['name'], 'string', 'max' => 255],
            [['phone'], 'string', 'max' => 30],
            [['verdict'], 'string', 'max' => 20],
            [['reason_code', 'source'], 'string', 'max' => 60],
            [['reason_ar', 'override_reason'], 'string'],
            [['user_id', 'customer_id', 'override_user_id', 'http_status', 'duration_ms', 'created_at'], 'integer'],
            [['from_cache'], 'boolean'],
            [['matches_json'], 'safe'],
            [['request_id'], 'string', 'max' => 36],
        ];
    }

    /**
     * Persist a single Fahras check attempt.
     *
     * @param FahrasVerdict $v        The verdict (success or failure).
     * @param array         $context  Free-form context: [user_id, customer_id,
     *                                id_number, name, phone, source,
     *                                override_user_id, override_reason].
     */
    public static function record(FahrasVerdict $v, array $context = []): ?self
    {
        try {
            $row = new self();
            $row->user_id          = $context['user_id']          ?? (Yii::$app->user->id ?? null);
            $row->customer_id      = $context['customer_id']      ?? null;
            $row->id_number        = (string)($context['id_number'] ?? '');
            $row->name             = isset($context['name'])  ? mb_substr((string)$context['name'], 0, 255, 'UTF-8') : null;
            $row->phone            = isset($context['phone']) ? mb_substr((string)$context['phone'], 0, 30, 'UTF-8') : null;
            $row->verdict          = $v->verdict;
            $row->reason_code      = $v->reasonCode ?: null;
            $row->reason_ar        = $v->reasonAr ?: null;
            $row->matches_json     = $v->matches ? json_encode($v->matches, JSON_UNESCAPED_UNICODE) : null;
            $row->override_user_id = $context['override_user_id'] ?? null;
            $row->override_reason  = $context['override_reason']  ?? null;
            $row->http_status      = $v->httpStatus;
            $row->request_id       = $v->requestId;
            $row->duration_ms      = $v->durationMs;
            $row->from_cache       = $v->fromCache ? 1 : 0;
            $row->source           = $context['source'] ?? self::SOURCE_STEP1;
            $row->created_at       = time();

            if (!$row->save()) {
                Yii::warning('FahrasCheckLog save failed: ' . print_r($row->errors, true), 'fahras');
                return null;
            }
            return $row;
        } catch (\Throwable $e) {
            Yii::warning('FahrasCheckLog::record threw: ' . $e->getMessage(), 'fahras');
            return null;
        }
    }

    /**
     * Decode the matches column into an array (handy for views).
     */
    public function getMatches(): array
    {
        if (empty($this->matches_json)) return [];
        if (is_array($this->matches_json)) return $this->matches_json;
        $j = json_decode((string)$this->matches_json, true);
        return is_array($j) ? $j : [];
    }

    public function getVerdictLabel(): string
    {
        return [
            FahrasVerdict::VERDICT_CAN_SELL      => 'يمكن البيع',
            FahrasVerdict::VERDICT_CONTACT_FIRST => 'اتصل أولاً',
            FahrasVerdict::VERDICT_CANNOT_SELL   => 'لا يمكن البيع',
            FahrasVerdict::VERDICT_NO_RECORD     => 'لا يوجد سجل',
            FahrasVerdict::VERDICT_ERROR         => 'فشل الفحص',
        ][$this->verdict] ?? $this->verdict;
    }

    public function getVerdictBadgeClass(): string
    {
        return [
            FahrasVerdict::VERDICT_CAN_SELL      => 'bg-success',
            FahrasVerdict::VERDICT_CONTACT_FIRST => 'bg-warning text-dark',
            FahrasVerdict::VERDICT_CANNOT_SELL   => 'bg-danger',
            FahrasVerdict::VERDICT_NO_RECORD     => 'bg-info',
            FahrasVerdict::VERDICT_ERROR         => 'bg-secondary',
        ][$this->verdict] ?? 'bg-light';
    }
}
