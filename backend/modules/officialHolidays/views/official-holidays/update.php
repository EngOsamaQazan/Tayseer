<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Holiday */

$this->title = 'تعديل: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'العطل الرسمية', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'تعديل';
?>
<div class="official-holidays-update">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
