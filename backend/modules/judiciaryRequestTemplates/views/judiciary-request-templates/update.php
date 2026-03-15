<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\JudiciaryRequestTemplate */

$this->title = 'تعديل: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'قوالب الطلبات', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'تعديل';
?>
<div class="judiciary-request-template-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
