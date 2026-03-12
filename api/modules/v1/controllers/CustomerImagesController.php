<?php

namespace api\modules\v1\controllers;

use yii\rest\Controller;
use common\models\search\ImageManagerSearch;
use yii\web\NotFoundHttpException;
use yii\web\Response;


class CustomerImagesController extends Controller
{
    public function actionIndex($customer_id)
    {
        $images = \backend\modules\imagemanager\models\Imagemanager::find()->where(['groupName' => 'coustmers'])->andWhere(['contractId' => $customer_id])->all();
        if (empty($images)) {
            throw new NotFoundHttpException('No images found for the given customer ID.');
        }

        $response = [];
        foreach ($images as $image) {
            $response[] = [
                'id' => $image->id,
                'url' => $this->getSelectedImagePath($image),
            ];
        }

        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function getSelectedImagePath($image)
    {
            $file_hash = $image->fileHash;
            $file_extention = pathinfo($image->fileName, PATHINFO_EXTENSION);

            return 'https://jadal.aqssat.co/images/imagemanager/' . $image->id. '_' . $file_hash . '.' . $file_extention;
    }
}
