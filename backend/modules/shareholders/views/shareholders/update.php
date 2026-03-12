<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\modules\shareholders\models\Shareholders $model */

$this->title = 'تعديل بيانات مساهم: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'المساهمين', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="shareholders-update">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
