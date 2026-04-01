<?php

use yii\helpers\Url;
use yii\helpers\Html;
use kartik\grid\GridView;
use backend\widgets\ExportButtons;

/* @var $this yii\web\View */
/* @var $searchModel app\models\IncomeSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $incomeSummary */
$this->title = Yii::t('app', 'Income');
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile(Yii::$app->request->baseUrl . '/css/tayseer-gridview-responsive.css?v=1');
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/tayseer-gridview-modal.js?v=1', [
    'depends' => [\yii\web\JqueryAsset::class],
]);
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
                            Html::a('<i class="fa fa-plus"></i>', ['create'], ['role' => 'modal-remote', 'title' => 'Create new Installments', 'class' => 'btn btn-secondary', 'data-pjax' => 0]) .
                            Html::a('<i class="fa fa-refresh"></i>', [''], ['data-pjax' => 1, 'class' => 'btn btn-secondary', 'title' => 'Reset Grid']) .
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
                        'heading' => '<i class="fa fa-list"></i> Installments listing',
                        'before' => '<em>* Resize table columns just like a spreadsheet by dragging the column edges.</em>',
                        'after' => \johnitvn\ajaxcrud\BulkButtonWidget::widget([
                            'buttons' => Html::a('<i class="fa fa-trash"></i>&nbsp; Delete All', ["bulkdelete"], [
                                "class" => "btn btn-danger btn-xs",
                                'role' => 'modal-remote-bulk',
                                'data-confirm' => false, 'data-method' => false, // for overide yii data api
                                'data-request-method' => 'post',
                                'data-confirm-title' => 'Are you sure?',
                                'data-confirm-message' => 'Are you sure want to delete this item',
                                'data-pjax' => 0,
                            ]),
                        ]) .
                        '<div class="clearfix"></div>',
                    ]
                ])
                ?>
            </div>
        </div>
        <div class="modal fade" id="ajaxCrudModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <div style="text-align:center;padding:40px">
                            <i class="fa fa-spinner fa-spin" style="font-size:24px;color:var(--ty-clr-primary,#800020)"></i>
                        </div>
                    </div>
                    <div class="modal-footer"></div>
                </div>
            </div>
        </div>
