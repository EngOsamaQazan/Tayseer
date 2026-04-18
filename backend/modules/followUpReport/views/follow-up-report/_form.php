<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;
use yii\helpers\ArrayHelper;
use backend\modules\customers\models\Customers;
use yii\helpers\Url;
use backend\modules\companies\models\Companies;

$this->registerJsFile('/js/Tafqeet.js');
/* @var $this yii\web\View */
/* @var $model app\models\contracts */
/* @var $form yii\widgets\ActiveForm */
?>

<style>[x-cloak] { display: none !important; }</style>

<div class="contracts-form"
     x-data="{ contractType: '<?= $model->type ?? 'normal' ?>', showUpdateBtn: false }">

    <?php $form = ActiveForm::begin(); ?>

    <legend><?= Yii::t('app', 'Contracts Information') ?></legend>
    <div class="row">
        <div class="col-sm-4 col-xs-4" id="solidarity_contract"
             x-show="contractType === 'solidarity'" x-cloak
             x-transition.opacity.duration.200ms>
            <?=
            $form->field($model, 'customers_ids')->textInput(['placeholder' => 'ابحث بالاسم أو الرقم الوطني...', 'class' => 'form-control'])->label(Yii::t('app', 'Customer Name'));
            ?>

        </div>
        <div class="col-sm-4 col-xs-4" id="normal_contract"
             x-show="contractType === 'normal'"
             x-transition.opacity.duration.200ms>
            <?=
            $form->field($model, 'customer_id')->textInput(['placeholder' => 'ابحث بالاسم أو الرقم الوطني...', 'class' => 'form-control'])->label(Yii::t('app', 'Customer Name'));
            ?>

        </div>
        <div class="col-sm-4 col-xs-4" >
            <?php $model->isNewRecord == 1 ? $model->type = 'normal' : $model->type; ?>
            <?= $form->field($model, 'type', ['inputOptions' => ['id' => 'contract_type']])->radioList(['normal' => Yii::t('app', 'normal'), 'solidarity' => Yii::t('app', 'solidarity')])->label(Yii::t('app', 'Contract Type')); ?>
        </div>
        <div class="col-sm-2 col-xs-2" id="updateCustomer"
             x-show="contractType === 'normal' && showUpdateBtn" x-cloak
             x-transition.opacity.duration.200ms>
            <button title="add customer" class="btn btn-primary" onclick="updateCustomer($('#contracts-customer_id').select2('data')[0].id)" ><?=Yii::t('app', 'Update Customer')?></button>
        </div>

        <div class="col-sm-2 col-xs-2">
            <button title="add customer" class="btn btn-primary" onclick=" window.open('<?= Yii::$app->urlManager->createUrl('/customers/wizard') ?>', '_blank');
                    return false;"><?=Yii::t('app', 'Add Customer')?></button>
        </div>
    </div>

    <div id="customer_data"
         x-show="contractType === 'normal'"
         x-transition.opacity.duration.200ms>
        <div class="row">
            <div class="col-sm-12 col-xs-12">
                <?=
                $form->field($model, 'guarantors_ids')->textInput(['placeholder' => 'ابحث بالاسم أو الرقم الوطني...', 'class' => 'form-control'])->label(Yii::t('app', 'Guarantors Search'));
                ?>

            </div>
        </div>
        <div class="row">
            <div class="col-sm-3 col-xs-3">
                <label for="total_contracts"><?=Yii::t('app', 'Total Contracts')?></label>
                <?= Html::input('text', 'total_contracts', '', ['class' => 'form-control', 'id' => 'total_contracts', 'readOnly' => true]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6 col-xs-6">
                <label for="name"><?=Yii::t('app', 'Name')?></label>
                <?= Html::input('text', 'name', '', ['class' => 'form-control', 'id' => 'name', 'readOnly' => true]) ?>
            </div>
            <div class="col-sm-6 col-xs-6">
                <label for="id id_number"><?=Yii::t('app', 'Id Number')?></label>
                <?= Html::input('text', 'id_number', '', ['class' => 'form-control', 'id' => 'id_number', 'readOnly' => true]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6 col-xs-6">
                <label for="birth_date"><?=Yii::t('app', 'Birth Date')?></label>
                <?= Html::input('text', 'birth_date', '', ['class' => 'form-control', 'id' => 'birth_date', 'readOnly' => true]) ?>
            </div>
            <div class="col-sm-6 col-xs-6">
                <label for="email"><?=Yii::t('app', 'Email')?></label>
                <?= Html::input('text', 'email', '', ['class' => 'form-control', 'id' => 'email', 'readOnly' => true]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6 col-xs-6">
                <label for="job_title"><?=Yii::t('app', 'Job Title')?></label>
                <?= Html::input('text', 'job_title', '', ['class' => 'form-control', 'id' => 'job_title', 'readOnly' => true]) ?>
            </div>
            <div class="col-sm-6 col-xs-6">
                <label for="bank_name"><?=Yii::t('app', 'Bank Name')?></label>
                <?= Html::input('text', 'bank_name', '', ['class' => 'form-control', 'id' => 'bank_name', 'readOnly' => true]) ?>
            </div>
        </div>
    </div>
    <fieldset>
        <legend><?= Yii::t('app', 'Basic Information') ?></legend>
        <div class="row">

            <div class="col-sm-6 col-xs-6">
                <?= $form->field($model, 'company_id')->dropDownList(yii\helpers\ArrayHelper::map(Companies::find()->all(), 'id', 'name'), ['prompt' => \Yii::t('app', 'أختر شركة')])->label(Yii::t('app', 'Select Company')) ?>
            </div>
            <div class="col-sm-6 col-xs-6">
                <?php
                if($model->isNewRecord){
                    $model->Date_of_sale=date('Y-m-d');
                }
                ?>
                <?=
                $form->field($model, 'Date_of_sale')->widget(FlatpickrWidget::class, [
                    'options' => ['placeholder' => Yii::t('app', 'Enter Date of sale ...')],
                    'pluginOptions' => [
                        'dateFormat' => 'Y-m-d',
                    ]
                ]);
                ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6 col-xs-6">
                <?=
                $form->field($model, 'first_installment_value')->textInput([
                    'type' => 'number',
                    'step' => '1',
                    'min' => '0',
                    'class' => 'form-control',
                    'placeholder' => '0',
                    'onchange' => 'installment_table()',
                ]);
                ?>
            </div>

        </div>
        <div class="row">
            <div class="col-sm-2 col-xs-2">
                <?=
                $form->field($model, 'total_value')->textInput([
                    'type' => 'number',
                    'step' => '1',
                    'min' => '0',
                    'class' => 'form-control',
                    'placeholder' => '0',
                    'onchange' => 'installment_table()',
                ]);
                ?>
            </div>
            <div class="col-sm-10 col-xs-10">
                <label for="total_in_words"><?=Yii::t('app', 'Total In Words')?></label>
                <?= Html::input('text', 'total_in_words', '', ['class' => 'form-control', 'id' => 'total_in_words', 'readOnly' => true]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6 col-xs-6">
                <?=
                $form->field($model, 'monthly_installment_value')->textInput([
                    'type' => 'number',
                    'step' => '1',
                    'min' => '0',
                    'class' => 'form-control',
                    'placeholder' => '0',
                    'onchange' => 'installment_table()',
                ]);
                ?>
            </div>
            <div class="col-sm-6 col-xs-6">
                <label for="installment_count"><?=Yii::t('app', 'Income\'s count')?></label>
                <?= Html::input('text', 'installment_count', '', ['class' => 'form-control', 'id' => 'installment_count', 'readOnly' => true]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6 col-xs-6">
                <?=
                $form->field($model, 'first_installment_date')->widget(FlatpickrWidget::class, [
                    'options' => ['placeholder' => Yii::t('app', 'Enter  Date of first Income sale ...')],
                    'pluginOptions' => [
                        'dateFormat' => 'Y-m-d',
                    ], 'pluginEvents' => [
                        "change" => "function() {installment_table()}",
                    ],
                ]);
                ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-xs-12">
                <?= $form->field($model, 'notes')->textarea(['rows' => 6]) ?>
            </div>
        </div>
        <?php
        $image_manager_random_id = rand(100000000, 1000000000);
        $model->image_manager_id = $image_manager_random_id;
        ?>


        <?= $form->field($model, 'selected_image')->hiddenInput()?>

        <?php if (!$model->isNewRecord && !empty($model->selected_image) && $model->selectedImagePath) { ?>
            <div class="image-wrapper">
                <img id="contracts-contract_images_image" alt="Thumbnail"
                     width="400px"
                     class="img-responsive img-preview"
                     src="<?= $model->selectedImagePath ?>">
            </div>
        <?php } ?>


        <?= $form->field($model, 'image_manager_id')->hiddenInput()->label(false); ?>

        <div class="row">
            <?= $form->field($model, 'contract_images')->fileInput(['accept' => 'image/*'])->label(false) ?>
        </div>



        <?php if (!Yii::$app->request->isAjax) { ?>
            <div class="form-group">
                <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
            </div>
        <?php } ?>

        <?php ActiveForm::end(); ?>
        <div class="row">

            <div class="col-sm-12 col-xs-12">
                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col"><?=Yii::t('app', 'Amount')?></th>
                        <th scope="col"><?=Yii::t('app', 'Month')?></th>
                        <th scope="col"><?=Yii::t('app', 'Year')?></th>
                    </tr>
                    </thead>
                    <tbody id="installment_table_body">

                    </tbody>
                </table>
            </div>
        </div>

        <script type="text/javascript">
            //the dropdown list id; This doesn't have to be a dropdown it can be any field type.
            function getCustomerData(id) {
                //the dropdown list selected locations id
                //var id = $('#customers_ids').val();
                var url = "<?= Url::to(['customers/customer-data']); ?>" + "?id=" + id;
                //call the action we created above in the conrller
                $.get(url, function (data) {
                        //get the JSON data from the action
                        //var data = $.parseJSON(data);
                        //check if the system found any data
                        if (data !== null) {
                            $("#total_contracts").val(data.contracts_info.count).blur();
                            $("#name").val(data.model.name).blur();
                            $("#city").val(data.model.city).blur();
                            $("#bank_name").val(data.model.bank_name).blur();
                            $("#job_title").val(data.model.job_title).blur();
                            $("#id_number").val(data.model.id_number).blur();
                            $("#birth_date").val(data.model.birth_date).blur();
                            $("#email").val(data.model.email).blur();
                            $("#contracts_info").val(data.contracts_info.count).blur();
                            $("#user_status").val(data.contracts_info.email).blur();
                            /* OLD jQuery - replaced by Alpine.js: $('#updateCustomer').show(); */
                            Alpine.$data(document.querySelector('.contracts-form')).showUpdateBtn = true;
                        } else {
                            //if data wasn't found the alert.
                            alert('We\'re sorry but we couldn\'t load the the customer data!');
                        }
                    }
                );
            }
            function updateCustomer(id) {
                var url = "<?= Url::to(['customers/update']); ?>" + "?id=" + id;
                window.open(url, '_blank');
            }
            function installment_table() {
                var total_value = $("#contracts-total_value").val();
                var first_installment_value = $("#contracts-first_installment_value").val();
                var monthly_installment_value = $("#contracts-monthly_installment_value").val();
                if (total_value > 0 && first_installment_value > 0 && monthly_installment_value > 0) {
                    $('#installment_table_body').empty();
                    instalments_count = 0;
                    instalments_count = total_value / monthly_installment_value;
                    var year = new Date($('#contracts-first_installment_date').val()).getFullYear();
                    var d = new Date($('#contracts-first_installment_date').val());
                    var m = d.getMonth();
                    var y = 0;

                    for (i = 0; i < instalments_count; i++) {

                        if (i == parseInt(instalments_count)) {
                            var mounthly_instalment = total_value - (monthly_installment_value * (i));
                        } else {
                            var mounthly_instalment = monthly_installment_value;
                        }
                        if (m == 12) {
                            m = 1
                            y++;
                        } else {
                            m++;
                        }
                        var year_string = year + y;
                        var row = '<tr><th scope="row">' + (i + 1) + '</th><td>' + mounthly_instalment + '</td><td>' + m + '</td><td>' + year_string + '</td></tr>';
                        $('#installment_table_body').append(row);
                    }
                    $('#installment_count').val(i);
                }
            }
            function customers_aria(item) {
                /* OLD jQuery - replaced by Alpine.js
                if (item == 'normal') {
                    $('#solidarity_contract').hide();
                    $('#normal_contract').show();
                    $('#customer_data').show();
                    $('#updateCustomer').show();
                } else {
                    $('#solidarity_contract').show();
                    $('#normal_contract').hide();
                    $('#customer_data').hide();
                    $('#updateCustomer').hide();
                }
                */
                var _d = Alpine.$data(document.querySelector('.contracts-form'));
                _d.contractType = item;
                if (item === 'normal') _d.showUpdateBtn = true;
            }
        </script>
        <?php
        if (!$model->isNewRecord) {
            $script = <<< JS
    $(document).ready(function(){
            $('#total_in_words').val(tafqeet($("#contracts-total_value").val())+' دينار اردني فقط لاغير');
            installment_table();
            customers_aria('$model->type');
            //getCustomerData($('#contracts-customer_id').select2('data')[0].id);
    }); 
JS;
            $this->registerJs($script, $this::POS_END);
        }
        ?>
        <?php
        $script = <<< JS
$(document).ready(function(){
    $("#contracts-total_value").change(function () {
                $('#total_in_words').val(tafqeet($(this).val())+' دينار اردني فقط لاغير');
            });
}); 
JS;
        $this->registerJs($script, $this::POS_END);
        ?>
        <?php
        $script = <<< JS
$(document).ready(function () {
    $("[name='Contracts[type]']").change(function () {
       customers_aria(\$(this).val());
       
    });
}); 

JS;
        $this->registerJs($script, $this::POS_END);
        ?>
</div>

