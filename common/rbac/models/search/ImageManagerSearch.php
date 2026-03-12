<?php

// namespace noam148\imagemanager\models;

namespace common\models\search;

use Yii;
use noam148\imagemanager\models\ImageManagerSearch as BaseImageManagerSearch;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use noam148\imagemanager\models\ImageManager;
use noam148\imagemanager\Module;

/**
 * ImageManagerSearch represents the model behind the search form about `common\modules\imagemanager\models\ImageManager`.
 */
class ImageManagerSearch extends BaseImageManagerSearch
{
    public $globalSearch;
    public $groupName;
    public $contractId;
	
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['globalSearch', 'groupName', 'contractId'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    // public function scenarios()
    // {
    //     // bypass scenarios() implementation in the parent class
    //     return Model::scenarios();
    // }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = ImageManager::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
			'pagination' => [
				'pagesize' => 5,
			],
			'sort'=> ['defaultOrder' => ['created'=>SORT_DESC]]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // Get the module instance
        $module = Module::getInstance();
        // $module = $this->controllers->module->id;

        // echo "<pre>";
        // print_r($module);die;

        if ($module->setBlameableBehavior) {
            $query->andWhere(['createdBy' => Yii::$app->user->id]);
        }

        $query->orFilterWhere(['like', 'fileName', $this->globalSearch])
            ->orFilterWhere(['like', 'created', $this->globalSearch])
            ->orFilterWhere(['like', 'modified', $this->globalSearch]);
            
            if(isset($params['group-name'])) {
                $query->andFilterWhere(['groupName' => $params['group-name']]);
            }
            if(isset($params['contract-id'])) {
                $query->andFilterWhere(['contractId' => $params['contract-id']]);
            }

        return $dataProvider;
    }
}
