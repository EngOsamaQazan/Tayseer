<?php
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use common\components\CompanyChecked;

$CompanyChecked = new CompanyChecked();
$primary_company = $CompanyChecked->findPrimaryCompany();
if ($primary_company == '') {
    $companyName = '';

}
else {
    $companyName = $primary_company->name;
}

NavBar::begin([
    'brandLabel' => $companyName,
    //'brandUrl' => Yii::$app->homeUrl,
    'options' => [
        //'class' => 'navbar-inverse navbar-fixed-top',
    ],
]);
$menuItems = [
    ['label' => Yii::t('app', 'Home'), 'url' => ['/course/index']],
    ['label' => Yii::t('app', 'Content'), 'url' => ['/course/index'], 'linkOptions' =>
        ['<li class="divider"></li>',
            '<li class="dropdown-header">Dropdown Header</li>'
        ]
    ],
    ['label' => Yii::t('app', 'Assesment'), 'url' => ['/exam/index']],
    ['label' => Yii::t('app', 'User'), 'url' => ['/course/user']],
];

echo Nav::widget([
    'options' => ['class' => 'navbar-nav navbar-right'],
    'items' => $menuItems,
]);
NavBar::end();
?>