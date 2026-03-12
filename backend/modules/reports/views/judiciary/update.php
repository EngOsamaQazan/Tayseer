<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\modules\judiciary\models\Judiciary */

$this->title = Yii::t('app', 'Update Judiciary');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Judiciary'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="judiciary-update">
    <div class="questions-bank box box-primary">
        <?=
        $this->render('_form', [
            'model' => $model,
            'modelCustomerAction'=>$modelCustomerAction
        ])
        ?>

    </div>
