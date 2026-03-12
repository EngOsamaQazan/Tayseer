<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\FollowUp */

$this->title = Yii::t('app', 'Create Follow Up');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Follow Ups'), 'url' => ['index?contract_id='.$contract_id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="follow-up-create box box-primary box-primary">

    <?= $this->render('_form', [
        'model' => $model,
        'contract_id' => $contract_id,
        'contract_model' => $contract_model
    ]) ?>

</div>
