<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\modules\jobs\models\Jobs */

$this->title = 'تعديل جهة العمل: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'جهات العمل', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'تعديل';
?>

<div class="jobs-update">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
