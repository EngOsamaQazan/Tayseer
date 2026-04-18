<?php

namespace backend\modules\jobs\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * JobsSearch represents the model behind the search form of `backend\modules\jobs\models\Jobs`.
 */
class JobsSearch extends Jobs
{
    public $customersCount;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'job_type', 'status'], 'integer'],
            [['name', 'address_city', 'address_area', 'email'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Jobs::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC],
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'job_type' => $this->job_type,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'address_city', $this->address_city])
            ->andFilterWhere(['like', 'address_area', $this->address_area])
            ->andFilterWhere(['like', 'email', $this->email]);

        return $dataProvider;
    }

    /**
     * Lightweight KPI counters for the index hero strip.
     * Cached for 60s to keep the page snappy; safe because soft-delete /
     * status changes are infrequent compared to filtering.
     *
     * @return array{total:int, active:int, inactive:int, top_city:string|null, top_city_count:int}
     */
    public static function getDashboardStats(): array
    {
        $cache = Yii::$app->cache;
        $key = 'jobs:index:stats:v1';
        $hit = $cache->get($key);
        if (is_array($hit)) {
            return $hit;
        }

        $base = Jobs::find();
        $total    = (int) (clone $base)->count();
        $active   = (int) (clone $base)->andWhere(['status' => Jobs::STATUS_ACTIVE])->count();
        $inactive = max(0, $total - $active);

        $topRow = (clone $base)
            ->select(['address_city', 'cnt' => 'COUNT(*)'])
            ->andWhere(['not', ['address_city' => null]])
            ->andWhere(['<>', 'address_city', ''])
            ->groupBy('address_city')
            ->orderBy(['cnt' => SORT_DESC])
            ->asArray()
            ->one();

        $stats = [
            'total'           => $total,
            'active'          => $active,
            'inactive'        => $inactive,
            'top_city'        => $topRow['address_city'] ?? null,
            'top_city_count'  => (int) ($topRow['cnt'] ?? 0),
        ];

        $cache->set($key, $stats, 60);
        return $stats;
    }
}
