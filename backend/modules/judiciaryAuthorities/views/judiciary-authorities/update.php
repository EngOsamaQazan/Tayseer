<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\JudiciaryAuthority */

$this->title = 'تعديل: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'الجهات الرسمية', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="judiciary-authorities-update">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
