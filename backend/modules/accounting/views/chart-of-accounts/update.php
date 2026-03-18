<?php

use yii\helpers\Html;

$this->title = 'تعديل: ' . $model->code . ' - ' . $model->name_ar;
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = ['label' => 'شجرة الحسابات', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'تعديل';
?>

<?= $this->render('_form', ['model' => $model]) ?>
