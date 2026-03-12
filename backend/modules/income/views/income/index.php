<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use johnitvn\ajaxcrud\BulkButtonWidget;
use backend\widgets\ExportButtons;

/* @var $this yii\web\View */
/* @var $searchModel app\models\IncomeSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $incomeSummary */
$this->title = Yii::t('app', 'Income');
$this->params['breadcrumbs'][] = $this->title;

CrudAsset::register($this);
echo $customer_id;
exit();
?>
<p>

    <?= Html::a(Yii::t('app', 'Create Installment'), ['create'], ['class' => 'btn btn-success']) ?>
</p>
<h1>مجموع الاقساط الكلي:<?= $incomeSummary->userTotalInstallment; ?></h1>
<h1>مجموع الاقساط المدفوعة:<?= ($incomeSummary->userPaidInstallment) ? $incomeSummary->userPaidInstallment : 0; ?></h1>
<h1>مجموع الاقساط الغير مدفوعة:<?= ($incomeSummary->userUnPaidInstallment) ? $incomeSummary->userUnPaidInstallment : 0; ?></h1>
<h1>مجموع الاقساط المتأخرة:<?= ($incomeSummary->userOverdueInstallment) ? $incomeSummary->userOverdueInstallment : 0; ?></h1>
<div class="installment-index">
    <div id="ajaxCrudDatatable">
        <?=
        GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'rowOptions' => function($model) {
                if ($model->is_made_payment == 1) {
                    return ['class' => 'success'];
                }
                else if ($model->date < date('Y-m-d') && $model->is_made_payment == 0) {
                    return ['class' => 'info'];
                }
            },
                    'filterModel' => $searchModel,
                    'pjax' => true,
                    'columns' => require(__DIR__ . '/_columns.php'),
                    'toolbar' => [
                        ['content' =>
                            Html::a('<i class="glyphicon glyphicon-plus"></i>', ['create'], ['role' => 'modal-remote', 'title' => 'Create new Installments', 'class' => 'btn btn-default']) .
                            Html::a('<i class="glyphicon glyphicon-repeat"></i>', [''], ['data-pjax' => 1, 'class' => 'btn btn-default', 'title' => 'Reset Grid']) .
                            '{toggleData}' .
                            ExportButtons::widget([
                                'excelRoute' => ['export-excel', 'customer_id' => $customer_id],
                                'pdfRoute' => ['export-pdf', 'customer_id' => $customer_id],
                            ])
                        ],
                    ],
                    'striped' => true,
                    'condensed' => true,
                    'responsive' => true,
                    'panel' => [
                        'type' => 'primary',
                        'heading' => '<i class="glyphicon glyphicon-list"></i> Installments listing',
                        'before' => '<em>* Resize table columns just like a spreadsheet by dragging the column edges.</em>',
                        'after' => BulkButtonWidget::widget([
                            'buttons' => Html::a('<i class="glyphicon glyphicon-trash"></i>&nbsp; Delete All', ["bulkdelete"], [
                                "class" => "btn btn-danger btn-xs",
                                'role' => 'modal-remote-bulk',
                                'data-confirm' => false, 'data-method' => false, // for overide yii data api
                                'data-request-method' => 'post',
                                'data-confirm-title' => 'Are you sure?',
                                'data-confirm-message' => 'Are you sure want to delete this item'
                            ]),
                        ]) .
                        '<div class="clearfix"></div>',
                    ]
                ])
                ?>
            </div>
        </div>
        <?php
        Modal::begin([
            "id" => "ajaxCrudModal",
            "footer" => "", // always need it for jquery plugin
        ])
        ?>
        <?php Modal::end(); ?>
