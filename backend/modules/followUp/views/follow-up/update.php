<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\FollowUp */

$this->title = Yii::t('app', 'Update Follow Up: {name}', [
            'name' => $model->id,
        ]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Follow Ups'), 'url' => ['index?contract_id=' . $contract_id]];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="follow-up-update box box-primary box-primary">

    <?=
    $this->render('_form', [
        'model' => $model,
        'contract_id' => $contract_id,
        'contract_model' => $contract_model,
        'modelsPhoneNumbersFollwUps' => $modelsPhoneNumbersFollwUps
    ])
    ?>

</div>
<div class="follow-up-index box box-primary box-primary">
    <legend><h3><?= Yii::t('app', 'التحويل للدائرة الفانونية') ?></h3></legend>
    <div class="row">

        <div class="col-sm-3 col-xs-3" ><h3>قيمة العقد :<?= $model->next->total_value ?></h3></div>
        <div class="col-sm-2 col-xs-2" ><h3>رقم العقد :<?= $model->next->id ?></h3></div>
        <div class="col-sm-5 col-xs-5" ><h3>عملاء العقد:<?= join(', ', yii\helpers\ArrayHelper::map($model->next->customers, 'id', 'name')); ?></h3></div>
        <div class="col-sm-2 col-xs-2" >
            <?php
            if ($model->getNext()) {
                echo Html::a(Yii::t('app', 'العقد التالي'), ['create', 'contract_id' => $model->next->id], ['class' => 'btn btn-success']);
            } else {
                echo "لا مزيد من النتائج";
            }
            ?>
        </div>
    </div>
</div>
<div class="follow-up-index box box-primary box-primary">
    <legend><h3><?= Yii::t('app', 'معلومات العقد تالي') ?></h3></legend>
    <div class="row">

        <div class="col-sm-3 col-xs-3" ><h3>قيمة العقد :<?= $model->next->total_value ?></h3></div>
        <div class="col-sm-2 col-xs-2" ><h3>رقم العقد :<?= $model->next->id ?></h3></div>
        <div class="col-sm-5 col-xs-5" ><h3>عملاء العقد:<?= join(', ', yii\helpers\ArrayHelper::map($model->next->customers, 'id', 'name')); ?></h3></div>
        <div class="col-sm-2 col-xs-2" >
            <?php
            $nextID = $model->getNextContractID($contract_id);
            if (  $nextID > 0) {
                echo Html::a(Yii::t('app', 'العقد التالي'), ['create', 'contract_id' =>$nextID ], ['class' => 'btn btn-success']);
            } else {
                echo "لا مزيد من النتائج";
            }
            ?>
        </div>
    </div>
</div>
