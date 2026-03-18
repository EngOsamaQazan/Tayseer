<?php
$this->title = 'تعديل الذمة الدائنة #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = ['label' => 'الذمم الدائنة', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'تعديل';
?>
<?= $this->render('_form', ['model' => $model]) ?>
