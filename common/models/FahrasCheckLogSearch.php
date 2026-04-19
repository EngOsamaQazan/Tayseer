<?php

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Search model for {@see FahrasCheckLog}.
 *
 * Filters drive the audit-log GridView at `/customers/fahras-log` so the
 * compliance team can answer questions like:
 *   • "Show me every override done by user X in the last week."
 *   • "Show me every cannot_sell verdict ever issued for ID 9XXXXXXXXX."
 *   • "Show me every Fahras outage (verdict=error) on 2026-04-19."
 */
class FahrasCheckLogSearch extends FahrasCheckLog
{
    /** Free-text id_number / name / phone filter (single search box). */
    public $q;

    /** ISO date strings (YYYY-MM-DD). */
    public $dateFrom;
    public $dateTo;

    /** Whether to show only override rows. */
    public $onlyOverrides;

    public function rules(): array
    {
        return [
            [['id', 'user_id', 'customer_id', 'override_user_id', 'http_status', 'duration_ms'], 'integer'],
            [['id_number', 'name', 'phone', 'verdict', 'reason_code', 'source', 'request_id', 'q', 'dateFrom', 'dateTo'], 'safe'],
            [['onlyOverrides', 'from_cache'], 'boolean'],
        ];
    }

    /**
     * @inheritDoc
     * Bypass parent scenarios so the safe-list above applies verbatim.
     */
    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params, int $pageSize = 25): ActiveDataProvider
    {
        $query = FahrasCheckLog::find();

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'sort'       => ['defaultOrder' => ['created_at' => SORT_DESC]],
            'pagination' => ['pageSize' => $pageSize],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // Invalid filter input — return an empty set rather than leaking everything.
            $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id'               => $this->id,
            'user_id'          => $this->user_id,
            'customer_id'      => $this->customer_id,
            'verdict'          => $this->verdict,
            'source'           => $this->source,
            'override_user_id' => $this->override_user_id,
            'request_id'       => $this->request_id,
        ]);

        $query
            ->andFilterWhere(['like', 'id_number',   $this->id_number])
            ->andFilterWhere(['like', 'name',        $this->name])
            ->andFilterWhere(['like', 'phone',       $this->phone])
            ->andFilterWhere(['like', 'reason_code', $this->reason_code]);

        // Free-text quick-search across id_number / name / phone.
        if ($this->q !== null && $this->q !== '') {
            $q = trim((string)$this->q);
            $query->andWhere([
                'or',
                ['like', 'id_number', $q],
                ['like', 'name',      $q],
                ['like', 'phone',     $q],
            ]);
        }

        // Date-range filter on `created_at` (Unix timestamps).
        if ($this->dateFrom) {
            $ts = strtotime($this->dateFrom . ' 00:00:00');
            if ($ts) $query->andWhere(['>=', 'created_at', $ts]);
        }
        if ($this->dateTo) {
            $ts = strtotime($this->dateTo . ' 23:59:59');
            if ($ts) $query->andWhere(['<=', 'created_at', $ts]);
        }

        if ($this->onlyOverrides) {
            $query->andWhere(['is not', 'override_user_id', null]);
        }

        return $dataProvider;
    }
}
