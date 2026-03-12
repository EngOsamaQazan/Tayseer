<?php
namespace backend\modules\reports\models;

use yii\base\Model;
use yii\data\SqlDataProvider;

class CustomersJudiciaryActionsSearch extends Model
{
    public $customer_id;
    public $customer_name;
    public $court_name;
    // Add other fields as necessary

    public function rules()
    {
        return [
            // Define validation rules
            [['customer_id', 'customer_name', 'court_name'], 'safe'],
        ];
    }

    public function search($params)
    {
        $this->load($params);

        $sql = "SELECT DISTINCT
        os_contracts_customers.contract_id,
        os_customers.id AS customer_id,
        os_customers.name AS customer_name,
        os_court.name AS court_name,
        os_judiciary.id AS judiciary_id,
        os_judiciary_actions.name AS judiciary_actions_name
    FROM 
        os_customers 
    JOIN 
        os_contracts_customers ON os_contracts_customers.customer_id = os_customers.id
    JOIN 
        os_contracts ON os_contracts.id = os_contracts_customers.contract_id
    LEFT JOIN 
        os_judiciary ON os_judiciary.contract_id = os_contracts.id
    LEFT JOIN 
        os_judiciary_customers_actions ON os_judiciary.id = os_judiciary_customers_actions.judiciary_id
    LEFT JOIN 
        os_judiciary_actions ON os_judiciary_actions.id = os_judiciary_customers_actions.judiciary_actions_id
    LEFT JOIN 
        os_court ON os_court.id = os_judiciary.court_id
    WHERE
        os_judiciary_customers_actions.created_at = (
            SELECT MAX(created_at) 
            FROM os_judiciary_customers_actions 
            WHERE os_judiciary_customers_actions.judiciary_id = os_judiciary.id
        ) OR os_judiciary_customers_actions.id IS NULL
    ORDER BY `os_customers`.`name` ASC"; // Base SQL query

        // Modify $sql based on search criteria

        return new SqlDataProvider([
            'sql' => $sql,
            // Configure pagination and sorting
        ]);
    }
}
