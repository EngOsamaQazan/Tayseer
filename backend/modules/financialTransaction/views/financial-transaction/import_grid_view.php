<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use johnitvn\ajaxcrud\BulkButtonWidget;
use backend\modules\financialTransaction\models\FinancialTransaction;
use backend\widgets\ExportButtons;

/* @var $this yii\web\View */
/* @var $searchModel backend\modules\financialTransaction\models\FinancialTransactionSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $dataTransferExpenses*/
/* @var $dataTransfer*/

$this->title = Yii::t('app', 'Expenses');
$this->params['breadcrumbs'][] = $this->title;
CrudAsset::register($this);
?>
<?= $this->render('_search', [
    'model' => $searchModel
]) ?>
<?= Html::a(Yii::t('app', 'Import File'), Url::to(['financial-transaction/import-file']), ['class' => 'btn btn-primary', 'style' => "margin-top: 20px"]) ?>
<?= Html::a(Yii::t('app', 'Transfer Data').'  '.$dataTransfer, Url::to(['financial-transaction/transfer-data']), ['class' => 'btn btn-primary', 'style' => "margin-top: 20px;margin-left:10px"]) ?>
<?= Html::a(Yii::t('app', 'Transfer Data To expenses').'  '.$dataTransferExpenses, Url::to(['financial-transaction/transfer-data-to-expenses']), ['class' => 'btn btn-primary', 'style' => "margin-top: 20px;margin-left:10px"]) ?>

    <div class="expenses-index" style="margin-top: 20px">
        <div id="ajaxCrudDatatable">
            <?= GridView::widget([
                'id' => 'crud-datatable',
                'dataProvider' => $dataProvider,
                'summary' => '',
                'columns' => require(__DIR__ . '/_columns.php'),
                'toolbar' => [
                    ['content' =>
                        Html::a('<i class="glyphicon glyphicon-plus"></i>', ['create'],
                            ['title' => 'Create new financial transaction', 'class' => 'btn btn-default']) .
                        Html::a('<i class="glyphicon glyphicon-repeat"></i>', [''],
                            ['data-pjax' => 1, 'class' => 'btn btn-default', 'title' => 'Reset Grid']) .
                        '{toggleData}' .
                        ExportButtons::widget([
                            'excelRoute' => ['export-excel'],
                            'pdfRoute' => ['export-pdf'],
                        ])
                    ],
                ],
                'striped' => true,
                'condensed' => true,
                'responsive' => true,
                'panel' => [
                    'type' => 'default',
                ]
            ]) ?>
        </div>
    </div>
<?php Modal::begin([
    "id" => "ajaxCrudModal",
    "footer" => "",// always need it for jquery plugin
]) ?>
<?php Modal::end(); ?>
<?php

$typeIncome = FinancialTransaction::TYPE_INCOME;
$typeOutcome = FinancialTransaction::TYPE_OUTCOME;
$TypeIncomeMonth = FinancialTransaction::TYPE_INCOME_MONTHLY;
$TypeIncomeOther = FinancialTransaction::TYPE_INCOME_OTHER;
$this->registerJs(<<<SCRIPT
    $(document).on('change','.category-change',function(){
        let newCategoryID = $(this).val();
        let expenseID = $(this).attr('data-id');
        $.post('update-category',{category_id:newCategoryID,id:expenseID},function(response){
        });
    });
    $(document).on('change','.type',function(){
        let newType = $(this).val();
        let expenseID = $(this).attr('data-id');
        $.post('update-type',{type:newType,id:expenseID},function(response){
         
        });
    });

    $(document).on('change','.type',function(){
        let typeVal = $(this).val();
        if(typeVal == $typeIncome){
            $(this).closest('tr').find('td').eq(4).find('.income-type-list').show();
            $(this).closest('tr').find('td').eq(3).find('.category-list').hide();
        } else if(typeVal == $typeOutcome){
            $(this).closest('tr').find('td').eq(4).find('.income-type-list').hide();
            $(this).closest('tr').find('td').eq(5).find('.contract-id-list').hide();
            $(this).closest('tr').find('td').eq(3).find('.category-list').show();
        }
    });   
       $(document).on('change','.income_type',function(){
         let incomeVal = $(this).val();
         if(incomeVal == 8){
          $(this).closest('tr').find('td').eq(5).find('.contract-id-list').show();
         }else if (incomeVal != 8){
            $(this).closest('tr').find('td').eq(5).find('.contract-id-list').hide();

         }
       });
    
    $(document).on('change','.income_type',function(){
        let newTypeIncome = $(this).val();
        let expenseID = $(this).attr('data-id');
        $.post('update-type-income',{type_income:newTypeIncome,id:expenseID},function(response){
        });
    });

    $(document).on('change','.contract',function(){
        let contract = $(this).val();
        let expenseID = $(this).attr('data-id');
        $.post('contract',{contract:contract,id:expenseID},function(response){
       
        });
    });
$(document).on('change','.company',function(){
   let company = $(this).val();
   let expenseID = $(this).attr('data-id');
   $.post('update-company',{company:company,id:expenseID},function(response){
    

   });
});
SCRIPT
);
?>