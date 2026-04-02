<?php
/**
 * نموذج ديناميكي - أرقام المعرّفين
 * يستقبل $cousins من _form.php لتجنب N+1
 */
use yii\helpers\Html;
use wbraganca\dynamicform\DynamicFormWidget;
use backend\helpers\PhoneInputWidget;

DynamicFormWidget::begin([
    'widgetContainer' => 'dynamicform_wrapper2',
    'widgetBody' => '.container-items2',
    'widgetItem' => '.phone-numbers-item',
    'limit' => 50,
    'min' => 1,
    'insertButton' => '.phone-numbers-add-item',
    'deleteButton' => '.phone-numbers-remove-item',
    'model' => $modelsPhoneNumbers[0],
    'formId' => 'smart-onboarding-form',
    'formFields' => ['phone_number', 'owner_name', 'phone_number_owner'],
]);
?>

<div class="container-items2">
    <?php foreach ($modelsPhoneNumbers as $i => $phone): ?>
        <div class="phone-numbers-item card">
            <div class="card-body">
                <?php if (!$phone->isNewRecord) echo Html::activeHiddenInput($phone, "[{$i}]id") ?>
                <div class="row">
                    <div class="col-md-3">
                        <?= $form->field($phone, "[{$i}]phone_number")->widget(PhoneInputWidget::class, [
                            'options' => ['class' => 'form-control'],
                        ])->label('الهاتف') ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($phone, "[{$i}]owner_name")->textInput(['maxlength' => true, 'placeholder' => 'اسم صاحب الرقم'])->label('الاسم') ?>
                    </div>
                    <div class="col-md-2">
                        <?= $form->field($phone, "[{$i}]fb_account")->textInput(['maxlength' => true, 'placeholder' => 'فيسبوك'])->label('فيسبوك') ?>
                    </div>
                    <div class="col-md-3">
                        <?= $form->field($phone, "[{$i}]phone_number_owner")->dropDownList(
                            $cousins,
                            ['prompt' => '-- صلة القرابة --']
                        )->label('صلة القرابة') ?>
                    </div>
                    <div class="col-md-1">
                        <div style="margin-top:26px">
                            <button type="button" class="phone-numbers-remove-item btn btn-danger btn-xs" title="حذف"><i class="fa fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach ?>
</div>

<button type="button" class="phone-numbers-add-item btn btn-success btn-xs"><i class="fa fa-plus"></i> إضافة معرّف</button>

<?php DynamicFormWidget::end() ?>

<?php
$utilsUrl = \backend\helpers\PhoneInputAsset::register($this)->baseUrl . '/js/utils.js';
$absUtilsUrl = \yii\helpers\Url::to($utilsUrl, true);
$phoneAfterInsertJs = <<<JS
$('.dynamicform_wrapper2').on('afterInsert', function(e, item) {
    var \$item = $(item);
    var telInput = \$item.find('input[type="tel"]')[0];
    if (!telInput) return;

    // Clean cloned intl-tel-input wrapper if present
    var \$iti = \$item.find('.iti');
    if (\$iti.length) {
        \$iti.find('.iti__country-container').remove();
        $(telInput).unwrap();
    }
    $(telInput).removeClass('iti__tel-input');
    telInput.removeAttribute('data-intl-tel-input-id');
    telInput.style.paddingLeft = '';
    telInput.placeholder = '';
    delete telInput._iti;
    $(telInput).val('');

    var iti = window.intlTelInput(telInput, {
        initialCountry: 'jo',
        countryOrder: ['jo','ps','sa','iq','eg','sy','lb','ae'],
        separateDialCode: true,
        countrySearch: true,
        formatAsYouType: true,
        strictMode: true,
        countryNameLocale: 'ar',
        i18n: {
            searchPlaceholder: 'بحث عن دولة...',
            noCountrySelected: 'اختر الدولة',
            countryListAriaLabel: 'قائمة الدول',
            searchEmptyState: 'لا توجد نتائج'
        },
        loadUtils: function(){ return import('$absUtilsUrl'); }
    });
    telInput._iti = iti;

    var form = telInput.closest('form');
    if (form) {
        form.addEventListener('submit', function(){
            if (telInput._iti) telInput.value = telInput._iti.getNumber();
        });
    }
});
JS;
$this->registerJs($phoneAfterInsertJs);
?>
