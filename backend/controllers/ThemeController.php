<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\UserPreference;

class ThemeController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'save' => ['post'],
                ],
            ],
        ];
    }

    /**
     * AJAX POST — save theme preferences.
     * Expects JSON body: { "mode": "dark", "color": "ocean" }
     */
    public function actionSave()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = json_decode(Yii::$app->request->rawBody, true);
        if (!$data) {
            $data = Yii::$app->request->post();
        }

        $mode  = $data['mode'] ?? null;
        $color = $data['color'] ?? null;

        $saved = [];

        if ($mode && in_array($mode, UserPreference::VALID_MODES)) {
            UserPreference::set(UserPreference::KEY_THEME_MODE, $mode);
            $saved['mode'] = $mode;
        }

        if ($color && in_array($color, UserPreference::VALID_COLORS)) {
            UserPreference::set(UserPreference::KEY_THEME_COLOR, $color);
            $saved['color'] = $color;
        }

        return ['success' => true, 'saved' => $saved];
    }

    /**
     * GET — return current theme settings.
     */
    public function actionCurrent()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return UserPreference::getTheme();
    }
}
