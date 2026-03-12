<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\ExpenseCategories */

$this->title = Yii::t('app', 'Update Expense Categories ');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Expense Categories'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="expense-categories-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
