<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use backend\models\PinnedItem;

class PinController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['actions' => ['toggle', 'list'], 'allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['toggle' => ['post']],
            ],
        ];
    }

    public function actionToggle()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        PinnedItem::ensureTable();

        $type = Yii::$app->request->post('type');
        $itemId = (int)Yii::$app->request->post('item_id');
        $label = Yii::$app->request->post('label', '');
        $extra = Yii::$app->request->post('extra', '');

        if (!$type || !$itemId) {
            return ['success' => false, 'message' => 'Missing params'];
        }

        $result = PinnedItem::togglePin(
            Yii::$app->user->id, $type, $itemId, $label, $extra
        );

        $pins = PinnedItem::getPinnedItems(Yii::$app->user->id, $type);
        $result['success'] = true;
        $result['pins'] = array_map(function ($p) {
            return [
                'id' => $p->id,
                'item_id' => $p->item_id,
                'label' => $p->label,
                'extra_info' => $p->extra_info,
                'created_at' => $p->created_at,
            ];
        }, $pins);

        return $result;
    }

    public function actionList($type)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        PinnedItem::ensureTable();

        $pins = PinnedItem::getPinnedItems(Yii::$app->user->id, $type);
        return [
            'success' => true,
            'pins' => array_map(function ($p) {
                return [
                    'id' => $p->id,
                    'item_id' => $p->item_id,
                    'label' => $p->label,
                    'extra_info' => $p->extra_info,
                    'created_at' => $p->created_at,
                ];
            }, $pins),
        ];
    }
}
