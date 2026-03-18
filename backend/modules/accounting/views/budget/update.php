<?php
$this->title = 'تعديل الموازنة: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = ['label' => 'الموازنات', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'تعديل';
?>
<?= $this->render('_form', ['model' => $model]) ?>
