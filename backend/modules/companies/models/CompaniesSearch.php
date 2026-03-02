<?php

namespace backend\modules\companies\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\companies\models\Companies;

/**
 * CompaniesSearch represents the model behind the search form about `backend\modules\companies\models\Companies`.
 */
class CompaniesSearch extends Companies
{
    /**
     * @inheritdoc
     */
    public $number_row;
    public $q;
    public function rules()
    {
        return [
            [['id', 'created_by', 'created_at', 'updated_at', 'is_deleted'], 'integer'],
            [['name', 'phone_number', 'logo', 'last_updated_by','is_deleted','is_primary_company', 'q'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Companies::find();

        if(!empty($params['CompaniesSearch']['number_row'])){

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => $params['CompaniesSearch']['number_row'],
                ],
            ]);
        }else{
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
        }
        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
        $this->applyUnifiedSearch($query);

        $query->andFilterWhere(['=', 'name', $this->name])
            ->andFilterWhere(['=', 'phone_number', $this->phone_number])
            ->andFilterWhere(['=', 'logo', $this->logo])
            ->andWhere(['is_deleted' => 0])->andWhere(['is_deleted' => false]);

        return $dataProvider;
    }

    private static $cwIdx = 0;

    private function applyUnifiedSearch($query)
    {
        if (empty($this->q)) return;
        $q = trim($this->q);

        $words = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY);
        $nameNorm = "REPLACE(REPLACE(REPLACE(REPLACE(os_companies.name, 'ة', 'ه'), 'أ', 'ا'), 'إ', 'ا'), 'ى', 'ي')";
        $nameNormNoSpace = "REPLACE($nameNorm, ' ', '')";

        foreach ($words as $w) {
            $wNorm = str_replace(['أ', 'إ', 'آ'], 'ا', $w);
            $wNorm = str_replace('ة', 'ه', $wNorm);
            $wNorm = str_replace('ى', 'ي', $wNorm);
            $p = ':cw' . (self::$cwIdx++);
            $nameExpr = new \yii\db\Expression(
                "($nameNorm LIKE $p OR $nameNormNoSpace LIKE $p)",
                [$p => '%' . $wNorm . '%']
            );
            $or = ['or', $nameExpr,
                ['like', 'os_companies.phone_number', $w],
            ];
            if (is_numeric($w)) {
                $or[] = ['=', 'os_companies.id', (int)$w];
            }
            $query->andWhere($or);
        }
    }

    public function searchCounter($params)
    {
        $query = Companies::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $this->applyUnifiedSearch($query);

        $query->andFilterWhere([
            'id' => $this->id,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
        $query->andFilterWhere(['=', 'name', $this->name])
            ->andFilterWhere(['=', 'phone_number', $this->phone_number])
            ->andFilterWhere(['=', 'logo', $this->logo])
            ->andWhere(['is_deleted' => 0])->andWhere(['is_deleted' => false]);

        return $query->count();
    }
}
