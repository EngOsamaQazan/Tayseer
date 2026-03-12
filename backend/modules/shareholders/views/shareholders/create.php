<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\modules\shareholders\models\Shareholders $model */

$this->title = 'إضافة مساهم جديد';
$this->params['breadcrumbs'][] = ['label' => 'المساهمين', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="shareholders-create">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
