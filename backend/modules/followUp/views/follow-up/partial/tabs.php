<?php
/**
 * تبويبات المتابعة - بناء من الصفر
 * تعرض: أرقام الهواتف، البيانات المالية، الدفعات، التسويات، إجراءات قضائية
 */
use yii\helpers\Url;
use yii\helpers\Html;
use common\helper\Permissions;

$isManager = Yii::$app->user->can(Permissions::MANAGER);
$contractModel = $contractCalculations->contract_model;
?>

<!-- ═══ شريط التبويبات ═══ -->
<ul class="nav nav-tabs" style="margin-bottom:15px">
    <li><a data-toggle="tab" href="#tab-phones"><i class="fa fa-phone"></i> أرقام الهواتف</a></li>
    <li><a data-toggle="tab" href="#tab-financial"><i class="fa fa-money"></i> البيانات المالية</a></li>
    <li><a data-toggle="tab" href="#tab-payments"><i class="fa fa-credit-card"></i> الدفعات</a></li>
    <li><a data-toggle="tab" href="#tab-settlements"><i class="fa fa-balance-scale"></i> التسويات</a></li>
    <li><a data-toggle="tab" href="#tab-judiciary-actions"><i class="fa fa-gavel"></i> إجراءات قضائية</a></li>

    <!-- قائمة الإجراءات المنسدلة -->
    <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
            <i class="fa fa-cogs"></i> إجراءات <span class="caret"></span>
        </a>
        <ul class="dropdown-menu">
            <li><?= Html::a('<i class="fa fa-image"></i> صور العملاء', '#', ['data-toggle' => 'modal', 'data-target' => '#customerImagesModal']) ?></li>
            <li><?= Html::a('<i class="fa fa-exchange"></i> تغيير حالة العقد', '#', ['data-toggle' => 'modal', 'data-target' => '#changeStatusModal']) ?></li>
            <li><?= Html::a('<i class="fa fa-check-square-o"></i> للتدقيق', '#', ['data-toggle' => 'modal', 'data-target' => '#auditModal']) ?></li>
            <li class="divider"></li>
            <li><?= Html::a('<i class="fa fa-print"></i> كشف حساب', ['printer', 'contract_id' => $contract_id], ['target' => '_blank']) ?></li>
            <li><?= Html::a('<i class="fa fa-file-text-o"></i> براءة الذمة', ['clearance', 'contract_id' => $contract_id], ['target' => '_blank']) ?></li>
            <?php if ($isManager): ?>
                <li class="divider"></li>
                <?php if ($contractModel->is_can_not_contact == 1): ?>
                    <li><?= Html::a('<i class="fa fa-phone text-success"></i> يوجد أرقام هواتف', ['/contracts/contracts/is-connect', 'contract_id' => $contract_id]) ?></li>
                <?php else: ?>
                    <li><?= Html::a('<i class="fa fa-phone-slash text-danger"></i> لا يوجد أرقام هواتف', ['/contracts/contracts/is-not-connect', 'contract_id' => $contract_id]) ?></li>
                <?php endif ?>
            <?php endif ?>
        </ul>
    </li>
</ul>

<!-- ═══ محتوى التبويبات ═══ -->
<div class="tab-content">
    <!-- تبويب أرقام الهواتف -->
    <div id="tab-phones" class="tab-pane fade">
        <?= $this->render('tabs/phone_numbers.php', [
            'contractCalculations' => $contractCalculations,
            'contract_id' => $contract_id,
            'model' => $model,
        ]) ?>
    </div>

    <!-- تبويب البيانات المالية -->
    <div id="tab-financial" class="tab-pane fade">
        <?= $this->render('tabs/financial.php', [
            'modelsPhoneNumbersFollwUps' => $modelsPhoneNumbersFollwUps,
            'contractCalculations' => $contractCalculations,
            'contract_id' => $contract_id,
            'model' => $model,
        ]) ?>
    </div>

    <!-- تبويب الدفعات -->
    <div id="tab-payments" class="tab-pane fade">
        <?= $this->render('tabs/payments.php', [
            'contract_id' => $contract_id,
            'model' => $model,
        ]) ?>
    </div>

    <!-- تبويب التسويات -->
    <div id="tab-settlements" class="tab-pane fade">
        <?= $this->render('tabs/loan_scheduling.php', [
            'contract_id' => $contract_id,
            'model' => $model,
            'contractCalculations' => $contractCalculations,
        ]) ?>
    </div>

    <!-- تبويب إجراءات العملاء القضائية -->
    <div id="tab-judiciary-actions" class="tab-pane fade">
        <?= $this->render('tabs/judiciary_customers_actions.php', [
            'contract_id' => $contract_id,
            'model' => $model,
        ]) ?>
    </div>
</div>
