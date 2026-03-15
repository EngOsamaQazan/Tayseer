<?php

namespace api\modules\v1\controllers;

use yii\rest\Controller;
use backend\models\Media;
use backend\helpers\MediaHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class CustomerImagesController extends Controller
{
    public function actionIndex($customer_id)
    {
        $images = Media::find()
            ->where(['groupName' => 'coustmers'])
            ->andWhere(['contractId' => $customer_id])
            ->all();

        if (empty($images)) {
            throw new NotFoundHttpException('No images found for the given customer ID.');
        }

        $response = [];
        foreach ($images as $image) {
            $response[] = [
                'id'  => $image->id,
                'url' => $image->getAbsoluteUrl(),
            ];
        }

        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }
}
