<?php
use yii\helpers\Html;
?>
<div class="follow-up-index box box-primary box-primary">
    <legend>
        <h3><?= Yii::t('app', 'معلومات العقد تالي') ?></h3>
    </legend>
    <div class="row">
        <div class="col-sm-3 col-xs-3">
            <h3>قيمة العقد :<?= $model->next->total_value ?></h3>
        </div>
        <div class="col-sm-2 col-xs-2">
            <h3>رقم العقد :<?= $model->next->id ?></h3>
        </div>
        <div class="col-sm-5 col-xs-5">
            <h3>عملاء
                العقد:<?= join(', ', yii\helpers\ArrayHelper::map($model->next->customers, 'id', 'name')); ?></h3>
        </div>
        <div class="col-sm-2 col-xs-2">
            <?php
            $nextID = $model->getNextContractID($contract_id);
            $nextIDForManager = $model->getNextContractIDForManager($contract_id);
            if (Yii::$app->user->can('Manger')) {
                if ($nextIDForManager > 0) {
                    echo Html::a(Yii::t('app', 'العقد التالي'), ['index', 'contract_id' => $nextIDForManager], ['class' => 'btn btn-success']);
                } else {
                    echo "لا مزيد من النتائج";
                }
            } else {
                if ($nextID > 0) {
                    echo Html::a(Yii::t('app', 'العقد التالي'), ['index', 'contract_id' => $nextID], ['class' => 'btn btn-success']);
                } else {
                    echo "لا مزيد من النتائج";
                }
            }

            ?>
        </div>
    </div>
</div>