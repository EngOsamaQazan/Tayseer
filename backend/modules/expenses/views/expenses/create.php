<?php
/**
 * شاشة إضافة مصروف جديد
 * 
 * @var yii\web\View $this
 * @var backend\modules\expenses\models\Expenses $model
 */

$this->title = Yii::t('app', 'إضافة مصروف جديد');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'المصاريف'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="expenses-create">
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
