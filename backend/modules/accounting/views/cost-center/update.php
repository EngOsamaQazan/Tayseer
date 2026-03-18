<?php

$this->title = 'تعديل: ' . $model->code . ' - ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = ['label' => 'مراكز التكلفة', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'تعديل';
?>

<?= $this->render('_form', ['model' => $model]) ?>
