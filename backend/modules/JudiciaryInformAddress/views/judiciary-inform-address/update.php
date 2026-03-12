<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\modules\JudiciaryInformAddress\model\JudiciaryInformAddress $model */

$this->title = Yii::t('app', 'Update Judiciary Inform Address: {name}', [
    'name' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Judiciary Inform Addresses'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="judiciary-inform-address-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
