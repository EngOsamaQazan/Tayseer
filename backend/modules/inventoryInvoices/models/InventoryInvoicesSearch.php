<?php

namespace backend\modules\inventoryInvoices\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\modules\inventoryInvoices\models\InventoryInvoices;

/**
 * InventoryInvoicesSearch represents the model behind the search form about `backend\modules\inventoryInvoices\models\InventoryInvoices`.
 */
class InventoryInvoicesSearch extends InventoryInvoices
{
    /** @var int|null for filter only (may not exist on table until migration) */
    public $branch_id;
    /** @var string|null for filter only */
    public $status;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'inventory_items_id', 'company_id', 'type', 'suppliers_id', 'created_at', 'updated_at', 'created_by', 'last_updated_by', 'is_deleted', 'branch_id'], 'integer'],
            [['date', 'status'], 'safe'],
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
        $query = InventoryInvoices::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $filter = ['id' => $this->id];
        $schema = $query->modelClass::getTableSchema();
        if ($schema && $schema->getColumn('branch_id')) {
            $filter['branch_id'] = $this->branch_id;
        }
        if ($schema && $schema->getColumn('status')) {
            $filter['status'] = $this->status;
        }
        $query->andFilterWhere($filter)->andWhere(['is_deleted' => 0]);

        return $dataProvider;
    }
}
