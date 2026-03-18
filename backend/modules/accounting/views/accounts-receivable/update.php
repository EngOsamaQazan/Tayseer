<?php
$this->title = 'تعديل الذمة المدينة #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = ['label' => 'الذمم المدينة', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'تعديل';
?>
<?= $this->render('_form', ['model' => $model]) ?>
