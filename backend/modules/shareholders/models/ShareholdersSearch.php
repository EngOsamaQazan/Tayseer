<?php

namespace backend\modules\shareholders\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class ShareholdersSearch extends Shareholders
{
    public $q;

    public function rules()
    {
        return [
            [['id', 'share_count', 'is_active', 'is_deleted', 'created_at', 'updated_at', 'created_by'], 'integer'],
            [['name', 'phone', 'email', 'national_id', 'join_date', 'documents', 'notes', 'q'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    private static $cwIdx = 0;

    private function applyUnifiedSearch($query)
    {
        if (empty($this->q)) return;
        $q = trim($this->q);

        $words = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY);
        $nameNorm = "REPLACE(REPLACE(REPLACE(REPLACE(os_shareholders.name, 'ة', 'ه'), 'أ', 'ا'), 'إ', 'ا'), 'ى', 'ي')";
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
                ['like', 'os_shareholders.phone', $w],
                ['like', 'os_shareholders.national_id', $w],
                ['like', 'os_shareholders.email', $w],
            ];
            if (is_numeric($w)) {
                $or[] = ['=', 'os_shareholders.id', (int)$w];
            }
            $query->andWhere($or);
        }
    }

    public function search($params)
    {
        $query = Shareholders::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $this->applyUnifiedSearch($query);

        $query->andFilterWhere([
            'id' => $this->id,
            'share_count' => $this->share_count,
            'is_active' => $this->is_active,
            'created_by' => $this->created_by,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'phone', $this->phone])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'national_id', $this->national_id]);

        return $dataProvider;
    }

    public function searchCounter($params)
    {
        $query = Shareholders::find();

        $this->load($params);

        if (!$this->validate()) {
            return 0;
        }

        $this->applyUnifiedSearch($query);

        $query->andFilterWhere([
            'id' => $this->id,
            'share_count' => $this->share_count,
            'is_active' => $this->is_active,
            'created_by' => $this->created_by,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'phone', $this->phone])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'national_id', $this->national_id]);

        return $query->count();
    }
}
