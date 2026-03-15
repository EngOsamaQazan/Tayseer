<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Holiday */

$this->title = 'إضافة عطلة رسمية';
$this->params['breadcrumbs'][] = ['label' => 'العطل الرسمية', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="official-holidays-create">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
