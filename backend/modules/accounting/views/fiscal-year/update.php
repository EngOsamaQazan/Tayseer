<?php

$this->title = 'تعديل: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = ['label' => 'السنوات المالية', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'تعديل';
?>

<?= $this->render('_form', ['model' => $model]) ?>
