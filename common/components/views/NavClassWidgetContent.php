<?php

use yii\bootstrap\NavBar;
use yii\bootstrap\Tabs;
use yii\bootstrap\Nav;
use yii\helpers\Url;

NavBar::begin([
    'brandUrl' => Yii::$app->homeUrl,
    'options' => [
        'class' => 'navbar-inverse',
    ],
]);
$classId=null;
if( Yii::$app->request->get('id')!=null)
    $classId= Yii::$app->request->get('id');
if( Yii::$app->request->get('classId')!=null)
    $classId= Yii::$app->request->get('classId');

$homeActivate = $userActivate = false;

$userMenu = ['class-participant'];

$controller = Yii::$app->controller->id;

$currentUrlParts = explode('/', (string)Yii::$app->request->url);


$class_id = $currentUrlParts[count($currentUrlParts) - 1];

if (in_array($controller, $userMenu)) {
    $userActivate = true;
} elseif ($controller == 'class') {
    $homeActivate = true;
}

echo Tabs::widget([
    'items' => [
        [
            'label' => 'Home',
            'url' => ['/class'],
            'active' => $homeActivate,
        ],
        [
            'label' => 'User',
            'url' => ['/class-participant', 'class_id' => $class_id],
            'active' => $userActivate,
        ],
        [
            'label' => 'Communication',
            'url' => ['/communication/email-messages', 'classId' =>$classId],
            'content' => Nav::widget([
                'options' => ['class' => 'navbar-nav navbar-inverse'],
                'items' => [
                    ['label' => \Yii::t('app', 'Emails'), 'active' => Yii::$app->controller->id == 'email-messages', 'icon' => 'dashboard', 'url' => Url::to(['/communication/email-messages', 'classId' => $classId]),],
                    ['label' => \Yii::t('app', 'SMS'), 'active' => Yii::$app->controller->id == 'sms-messages', 'icon' => 'dashboard', 'url' => Url::to(['/communication/sms-messages', 'classId' =>  $classId]),],
                    //['label' => \Yii::t('app', 'Messages in Action Menu'), 'active' => $controllers == 'default-messages' ? $communicationActive : '', 'icon' => 'dashboard', 'url' => ['/communication/default-messages?classId=' . $classId],],
                ],
            ]),
            'active' => Yii::$app->controller->id == 'email-messages'||Yii::$app->controller->id == 'sms-messages',
        ],
        [
            'label' => 'Review',
            'url' => ['/class/review'],
            'active' => false,
        ],
    ]
]);

NavBar::end();
