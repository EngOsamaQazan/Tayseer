<?php

use yii\bootstrap\NavBar;
use yii\bootstrap\Tabs;

NavBar::begin([
    'brandUrl' => Yii::$app->homeUrl,
    'options' => [
        'class' => 'navbar-inverse',
    ],
]);

$homeActivate = $userActivate = false;

$userMenu = ['active','register','invoice'];

$controller = \Yii::$app->controller->id;
$action = \Yii::$app->controller->action->id;

$currentUrlParts = explode('/', (string)\Yii::$app->request->url);

$class_id = $currentUrlParts[count($currentUrlParts)-1];

if (!in_array($action, $userMenu)){
    $homeActivate = true;
}
echo Tabs::widget([
    'items' => [
        [
            'label' => 'Membership Plan',
            'url' => ['/member-ship-plan/index'],
            'active' => $homeActivate,
        ],
        [
            'label' => 'Active Membership',
            'url' => ['/member-ship-plan/active'],
            'active' => ($action == 'active' || $action == 'register' || $action == 'invoice')? true : false,
        ],
        [
            'label' => 'Expired Membership',
            'url' => ['#'],
            'active' => false,
        ],
        [
            'label' => 'Lead',
            'url' => ['#'],
            'active' => false,
        ],
        [
            'label' => 'Unconfirmed Payment',
            'url' => ['#'],
            'active' => false,
        ],
        [
            'label' => 'Reports',
            'url' => ['#'],
            'active' => false,
        ],
    ]
]);

NavBar::end();
