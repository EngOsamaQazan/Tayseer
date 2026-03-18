<?php

$this->title = 'تعديل القيد: ' . $model->entry_number;
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = ['label' => 'القيود اليومية', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'تعديل';
?>

<?= $this->render('_form', ['model' => $model, 'lines' => $lines]) ?>
