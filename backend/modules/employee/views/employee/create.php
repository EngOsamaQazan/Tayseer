<?php
use yii\bootstrap\Tabs;

/* @var $this yii\web\View */
/* @var $model backend\models\Employee */
?>
<div class="questions-bank box box-primary">

    <?= Tabs::widget([
        'encodeLabels' => false,
        'items' => [
            [
                'label' => '<i class="fa fa-user"></i> ' . Yii::t('app', 'employee'),
                'content' => $this->render('_form', ['model' => $model]),
                'active' => true,
            ],
            [
                'label' => '<i class="fa fa-list-alt"></i> ' . Yii::t('app', 'leave policy'),
                'content' => '',
                'headerOptions' => ['class' => 'disabled'],
            ],
        ],
    ]) ?>

</div>
