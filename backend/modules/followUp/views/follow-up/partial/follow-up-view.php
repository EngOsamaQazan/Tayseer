<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use johnitvn\ajaxcrud\BulkButtonWidget;
use yii\widgets\ActiveForm;
use yii\data\ActiveDataProvider;
use backend\modules\contractInstallment\models\ContractInstallment;
use backend\modules\contracts\models\Contracts;

use yii\widgets\Pjax;
use common\helper\LoanContract;
use yii\helpers\ArrayHelper;
use backend\modules\followUp\helper\ContractCalculations;
use yii\base\View;

/* @var $this yii\web\View */
/* @var $model common\models\FollowUp */
/* @var $form yii\widgets\ActiveForm */

$contractCalculations = new ContractCalculations($contract_id);
CrudAsset::register($this);
?>


    <div class="tab-content">
        <div id="menu1" class="tab-pane fade">
            <?= $this->render('phone_numbers.php', ['contractCalculations' => $contractCalculations, 'contract_id' => $contract_id, 'model' => $model]) ?>
        </div>
        <div id="menu2" class="tab-pane fade">
            <?= $this->render('financial.php', ['modelsPhoneNumbersFollwUps' => $modelsPhoneNumbersFollwUps, 'contractCalculations' => $contractCalculations, 'contract_id' => $contract_id, 'model' => $model]) ?>
        </div>
        <div id="menu3" class="tab-pane fade">
            <?= $this->render('payments.php', ['contract_id' => $contract_id, 'model' => $model]) ?>
        </div>
        <div id="menu4" class="tab-pane fade">
            <?= $this->render('loan_scheduling.php', ['contract_id' => $contract_id, 'model' => $model, 'contractCalculations' => $contractCalculations]) ?>
        </div>
        <div id="menu5" class="tab-pane fade">
            <?= $this->render('judiciary_customers_actions.php', ['contract_id' => $contract_id, 'model' => $model]) ?>
        </div>
    </div>
<?php
$script = <<< JS
$(document).ready(function() {
  var textarea = $("#sms_text");
  textarea.keydown(function(event) {
    var numbOfchars = textarea.val();
    var len = numbOfchars.length;
    $("#char_count").text(len);
  });                    
});
                        $(document).ready(function() {
                         $('#send_sms').click(function (event) {
                            $.ajax({
                                url: "send-sms?phone_number=" + $("#phone_number").val() + "&text=" + $('#sms_text').val(),
                            }).done(function ( data ) {
                                console.log(data);
                            })
                        });
                        });
JS;
$this->registerJs($script);
?>