<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\Lawyers */

$this->title = Yii::t('app', 'Create Lawyers');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Lawyers'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="lawyers-create">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
