<?php

use backend\modules\divisionsCollection\models\DivisionsCollection;
use common\helper\LoanContract;
use backend\helpers\FlatpickrWidget;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use backend\widgets\ExportButtons;

/* @var $this yii\web\View */
/* @var $model common\models\Collection */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="questions-bank box box-primary">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'date')->widget(FlatpickrWidget::class, ['pluginOptions' => [
                'dateFormat' => 'Y-m-d',
            ]]);
            ?>
        </div>

        <div class="col-lg-6">
            <?= $form->field($model, 'custamers_id')->dropDownList(yii\helpers\ArrayHelper::map(\backend\modules\customers\models\Customers::find()->innerJoin('os_contracts_customers', 'os_customers.id = os_contracts_customers.customer_id')->where(['os_contracts_customers.contract_id' => $contract_id])->all(), 'id', 'name'), ['prompt' => '-- اختر العميل --', 'class' => 'form-control']) ?>
        </div>
        <div class="col-lg-6">
            <?= $form->field($model, 'judiciary_id')->dropDownList(yii\helpers\ArrayHelper::map(\backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $contract_id])->all(), 'id', 'id'), ['prompt' => '-- اختر القضية --', 'class' => 'form-control']) ?>
        </div>

        <div class="col-lg-6">
            <?= $form->field($model, 'amount')->textInput()->label('قيمة الحسم الشهري') ?>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <?= $form->field($model, 'notes')->textarea(['rows' => 6]) ?>
        </div>
    </div>
    <?php if (!Yii::$app->request->isAjax) { ?>
        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
            <?php if ($model->isNewRecord) {
                ?>
                <button type="button" class="btn btn-primary button-t1">عرض التفاصيل</button>
            <?php } ?>
        </div>
    <?php } ?>


    <?php ActiveForm::end(); ?>
    <?php
    if (!$model->isNewRecord) {


        $query = DivisionsCollection::find()->where(['collection_id' => $model->id]);

        $divisionsCollection = new ActiveDataProvider([
            'query' => $query,
        ]);

        echo kartik\grid\GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $divisionsCollection,
            'pjax' => true,
            'columns' => [
                [
                    'class' => '\kartik\grid\DataColumn',
                    'attribute' => 'collection_id',
                ],
                [
                    'class' => '\kartik\grid\DataColumn',
                    'attribute' => 'month',
                ],
                [
                    'class' => '\kartik\grid\DataColumn',
                    'attribute' => 'year',
                ],
                [
                    'class' => '\kartik\grid\DataColumn',
                    'attribute' => 'amount',
                ],
                [
                    'class' => '\kartik\grid\DataColumn',
                    'attribute' => 'Edit',
                    'value' => function ($model) {
                        return '<button type="button" class="btn btn-primary model-amount" year="' . $model->year . '" month="' . $model->month . '" model-id="' . $model->id . '" data-bs-toggle="modal" data-bs-target="#exampleModal">
 Edit
</button>';
                    },
                    'format' => 'raw',
                ],
            ],
            'toolbar' => [
                ['content' =>
                    Html::a('<i class="fa fa-refresh"></i>', [''],
                        ['data-pjax' => 1, 'class' => 'btn btn-secondary', 'title' => 'Reset Grid']) .
                    '{toggleData}' .
                    ExportButtons::widget([
                        'excelRoute' => ['export-view-excel', 'id' => $model->id],
                        'pdfRoute' => ['export-view-pdf', 'id' => $model->id],
                    ])
                ],
            ],
            'striped' => false,
            'condensed' => false,
            'responsive' => false,
            'panel' => [
                'type' => false,
                'heading' => false,
                'after' => false,

            ]
        ]);
    } ?>

</div>

<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label for="pwd">الميلغ الشهري</label>
                            <input type="text" class="form-control amount" id="pwd">
                        </div>
                    </div>
                    <input type="hidden" class="amount-id">
                    <input type="hidden" class="month">
                    <input type="hidden" class="year">
                </div>


            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary save-amount">Save changes</button>
            </div>
        </div>
    </div>
</div>

<?php if ($model->isNewRecord) {
    ?>
    <div class="questions-bank box box-primary div-t1"
         x-data="{ show: false }" @show-details.window="show = true"
         x-show="show" x-transition x-cloak>
        <table class="table t1">
            <thead>
            <tr>
                <th scope="col">Month</th>
                <th scope="col">Year</th>
                <th scope="col">Amount</th>
            </tr>
            </thead>
        </table>
    </div>
    <?php
} ?>
<?php
$script = "let id = $(this).val();
$.post('" . \yii\helpers\Url::to(['/collection/collection/find-custamers']) . "',{id:id},function(val){

let array = JSON.parse(val);
$('.custam').text(array);
})";
$this->registerJs(<<<SCRIPT
$(document).on('change','.contract',function(){
$script
});



SCRIPT
);
?>
<?php
$totle_value = new LoanContract();
$totle_value = $totle_value->findContract($contract_id);
$vb = \backend\modules\followUp\helper\ContractCalculations::fromView($contract_id);
$total_amount = $vb ? $vb['totalDebt'] : (float)$totle_value->total_value;
if (!$model->isNewRecord) {
    $amount_change_code = "
let id = $(this).attr('model-id');
let month = $(this).attr('month');
let year = $(this).attr('year');
$('.amount-id').val(id);
$('.month').val(month);
$('.year').val(year);
";

    $amount_change_code2 = "
let amount = $('.amount').val();
let amount_id = $('.amount-id').val();
let month = $('.month').val();
let year = $('.year').val();
let collection_id = " . $model->id . ";
let contract_id = " . $model->contract_id . ";
let total_amount = " . $total_amount . ";
$.post('" . Url::to(['/collection/collection/update-amount']) . "',{total_amount:total_amount,month:month,amount_id:amount_id,amount:amount,collection_id:collection_id,year:year,contract_id:contract_id},function(w){
location.reload();

})
";
    $this->registerJs(<<<SCRIPT
$(document).on('click','.model-amount',function(){
$amount_change_code
});
$(document).on('click','.save-amount',function(){
$amount_change_code2
});
SCRIPT
    );
}
?>
<?php
if ($model->isNewRecord) {

    $document_tabel = "
/* OLD jQuery - replaced by Alpine.js: \$('.div-t1').css('display','block'); */
window.dispatchEvent(new Event('show-details'));
let amount = parseInt($('#collection-amount').val());
let totle_value_price = " . $totle_value_price . ";
let total_month = Math.ceil( parseInt((totle_value_price/ amount)));
   var year = new Date($('#collection-date').val()).getFullYear();
                    var d = new Date($('#collection-date').val());
                    var m = d.getMonth();
                    var m = m+1;
                    var y = 0;
    let all_amount =0;
                      for (count = 0; count <= total_month; count++) { 
                          all_amount = all_amount + amount;
            if (all_amount > totle_value_price) {
                amount1 = (all_amount - totle_value_price);
                amount1 =  amount - amount1;
            } else {
                amount1 = amount;
            }
                    $('.t1').append('<tr><td>'+m+'</td><td>'+year+'</td><td>'+ amount1 + '</td></tr>');
                    
                    if (m <= 11) {
                    m = m + 1;
                } else {
                    m = 1;
                    year = year + 1;
                }  
                      }
                      
";
    $this->registerJs(<<<SCRIPT
$(document).on('click','.button-t1',function(){
$document_tabel
})
SCRIPT
    );
}
?>

