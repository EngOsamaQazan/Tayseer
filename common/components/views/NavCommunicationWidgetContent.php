<?php

use yii\bootstrap\NavBar;
use yii\bootstrap\Tabs;

NavBar::begin([
    'brandUrl' => Yii::$app->homeUrl,
    'options' => [
        'class' => 'navbar-inverse',
    ],
]);

$cid = Yii::$app->request->getQueryParam('cid');

echo Tabs::widget([
    'items' => [
        [
            'label' => 'Emails',
            'url' => ['/communication/email-messages','cid'=>$cid],
            'active' => Yii::$app->controller->id=='communication/email-messages',
        ],
        [
            'label' => 'Membership Subscription',
            'url' => ['/communication/membership-subscription','cid'=>$cid],
            'active' => false,
        ],
        [
            'label' => 'Activity Subscription',
            'url' => ['/communication/activity-subscription','cid'=>$cid],
            'active' => false,
        ],
        [
            'label' => 'Activity Reminder',
            'url' => ['/communication/activity-reminder','cid'=>$cid],
            'active' => false,
        ],
        [
            'label' => 'Default Messages',
            'url' => ['/communication/default-messages','cid'=>$cid],
            'active' => false,
        ],
    ]
]);

NavBar::end();
