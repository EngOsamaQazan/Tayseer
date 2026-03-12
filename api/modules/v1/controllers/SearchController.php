<?php

namespace backend\modules\api\controllers;

use yii\rest\Controller;
use backend\modules\contracts\models\Contracts;
use backend\modules\customers\models\ContractsCustomers;
use backend\modules\customers\models\Customers;
use backend\modules\address\models\Address;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;

class SearchController extends Controller
{
    /**
     * Search in Contracts, ContractsCustomers, Customers, and Address models.
     *
     * @return ActiveDataProvider
     * @throws NotFoundHttpException
     */
    public function actionIndex()
    {
        $params = \Yii::$app->request->queryParams;

        $query = Contracts::find()
            ->joinWith('contractsCustomers')
            ->joinWith('contractsCustomers.customer')
            ->joinWith('contractsCustomers.customer.address');

        // Search in Contracts model by ID
        if (!empty($params['id'])) {
            $query->andFilterWhere(['contracts.id' => $params['id']]);
        }

        // Search in Customers model by name, city, id_number, birth_date, primary_phone_number, and social_security_number
        if (!empty($params['name'])) {
            $query->andFilterWhere(['like', 'customers.name', $params['name']]);
        }
        if (!empty($params['city'])) {
            $query->andFilterWhere(['like', 'address.city', $params['city']]);
        }
        if (!empty($params['id_number'])) {
            $query->andFilterWhere(['customers.id_number' => $params['id_number']]);
        }
        if (!empty($params['birth_date'])) {
            $query->andFilterWhere(['customers.birth_date' => $params['birth_date']]);
        }
        if (!empty($params['primary_phone_number'])) {
            $query->andFilterWhere(['customers.primary_phone_number' => $params['primary_phone_number']]);
        }
        if (!empty($params['social_security_number'])) {
            $query->andFilterWhere(['customers.social_security_number' => $params['social_security_number']]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if ($dataProvider->getCount() === 0) {
            throw new NotFoundHttpException('No results found.');
        }

        return $dataProvider;
    }
}
