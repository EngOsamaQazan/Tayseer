<?php
/**
 * شاشة تعديل المصروف
 * 
 * @var yii\web\View $this
 * @var backend\modules\expenses\models\Expenses $model
 */

$this->title = Yii::t('app', 'تعديل المصروف');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'المصاريف'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="expenses-update">
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
