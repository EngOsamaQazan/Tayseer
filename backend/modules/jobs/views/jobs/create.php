<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\modules\jobs\models\Jobs */

$this->title = 'إضافة جهة عمل جديدة';
$this->params['breadcrumbs'][] = ['label' => 'جهات العمل', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="jobs-create">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
