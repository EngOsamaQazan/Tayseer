<?php
/**
 * تقرير إيرادات العملاء — تصميم احترافي
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap5\Modal;
use kartik\grid\GridView;
use backend\widgets\ExportButtons;

$this->title = 'تقرير الإيرادات';
$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);
?>

<?= $this->render('@app/views/layouts/_reports-tabs', ['activeTab' => 'income']) ?>

<style>
.rp-page { padding: 16px 0; }
.rp-filter-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px 24px 18px; margin-bottom: 20px; box-shadow: 0 1px 6px rgba(0,0,0,0.04); }
.rp-filter-header { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
.rp-filter-header i { font-size: 18px; color: #6366f1; }
.rp-filter-header h3 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0; }
.rp-filter-header .toggle-btn { margin-right: auto; margin-left: 0; background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 16px; padding: 4px 8px; border-radius: 6px; }
.rp-filter-header .toggle-btn:hover { background: #f1f5f9; color: #334155; }
.rp-filter-body .row { margin-bottom: 0; }
.rp-filter-body .form-group { margin-bottom: 12px; }
.rp-filter-body .form-group label { font-size: 12.5px; font-weight: 600; color: #475569; }
.rp-filter-body .form-control { border-radius: 8px; border: 1.5px solid #e2e8f0; font-size: 13px; }
.rp-filter-body .btn-primary { background: #4f46e5; border-color: #4f46e5; border-radius: 8px; font-weight: 600; padding: 8px 28px; }
.rp-filter-body .btn-primary:hover { background: #4338ca; border-color: #4338ca; }

.rp-total-badge { display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; padding: 10px 24px; border-radius: 10px; font-weight: 700; font-size: 16px; margin-bottom: 16px; }
.rp-total-badge i { font-size: 18px; }

/* تحسين الجدول */
.rp-page .kv-grid-container { border-radius: 12px; overflow: hidden; }
.rp-page .panel { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 6px rgba(0,0,0,0.04); }
.rp-page .panel-heading { background: #f8fafc !important; border-bottom: 1px solid #e2e8f0; padding: 14px 18px; }
.rp-page .table > thead > tr > th { background: #f1f5f9; font-weight: 700; font-size: 12.5px; color: #475569; border-bottom: 2px solid #e2e8f0; padding: 12px; }
.rp-page .table > tbody > tr > td { padding: 12px; font-size: 13px; vertical-align: middle; }
.rp-page .table > tbody > tr:hover { background: #f8fafc; }
</style>

<div class="rp-page">

    <!-- فلاتر البحث -->
    <div class="rp-filter-card" x-data="{ open: true }">
        <div class="rp-filter-header">
            <i class="fa fa-filter"></i>
            <h3>فلترة النتائج</h3>
            <!-- OLD jQuery - replaced by Alpine.js
            <button type="button" class="toggle-btn" onclick="$(this).find('i').toggleClass('fa-chevron-up fa-chevron-down'); $(this).closest('.rp-filter-card').find('.rp-filter-body').slideToggle(200);">
                <i class="fa fa-chevron-up"></i>
            </button>
            -->
            <button type="button" class="toggle-btn" @click="open = !open">
                <i class="fa" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
        </div>
        <div class="rp-filter-body" x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2">
            <?php
            $form = yii\widgets\ActiveForm::begin([
                'id' => 'income-search-form',
                'method' => 'get',
                'action' => ['reports/total-customer-payments-index'],
            ]);
            ?>
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($searchModel, 'created_by')->widget(kartik\select2\Select2::class, [
                        'data' => yii\helpers\ArrayHelper::map(Yii::$app->cache->getOrSet(Yii::$app->params["key_users"], function () {
                            return Yii::$app->db->createCommand(Yii::$app->params['users_query'])->queryAll();
                        }, Yii::$app->params['time_duration']), 'id', 'username'),
                        'options' => ['placeholder' => 'اختر الموظف...'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label('الموظف') ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($searchModel, 'type')->widget(kartik\select2\Select2::class, [
                        'data' => yii\helpers\ArrayHelper::map(Yii::$app->cache->getOrSet(Yii::$app->params["key_income_category"], function () {
                            return Yii::$app->db->createCommand(Yii::$app->params['income_category_query'])->queryAll();
                        }, Yii::$app->params['time_duration']), 'id', 'name'),
                        'options' => ['placeholder' => 'اختر التصنيف...', 'multiple' => true],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label('تصنيف الإيراد') ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($searchModel, '_by')->widget(kartik\select2\Select2::class, [
                        'data' => yii\helpers\ArrayHelper::map(array_filter(Yii::$app->cache->getOrSet(Yii::$app->params["key_income_by"], function () {
                            return Yii::$app->db->createCommand(Yii::$app->params['income_by_query'])->queryAll();
                        }, Yii::$app->params['time_duration']), fn($row) => $row['_by'] !== null), '_by', '_by'),
                        'options' => ['placeholder' => 'اختر العميل...'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label('العميل') ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'date_from')->widget(\backend\helpers\FlatpickrWidget::class, [
                        'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                        'options' => ['placeholder' => 'من...'],
                    ])->label('من تاريخ القبض') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'date_to')->widget(\backend\helpers\FlatpickrWidget::class, [
                        'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                        'options' => ['placeholder' => 'إلى...'],
                    ])->label('إلى تاريخ القبض') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'from_date')->widget(\backend\helpers\FlatpickrWidget::class, [
                        'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                        'options' => ['placeholder' => 'من...'],
                    ])->label('من تاريخ الإنشاء') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'to_date')->widget(\backend\helpers\FlatpickrWidget::class, [
                        'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                        'options' => ['placeholder' => 'إلى...'],
                    ])->label('إلى تاريخ الإنشاء') ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'followed_by')->widget(kartik\select2\Select2::class, [
                        'data' => yii\helpers\ArrayHelper::map(Yii::$app->cache->getOrSet(Yii::$app->params["key_users"], function () {
                            return Yii::$app->db->createCommand(Yii::$app->params['users_query'])->queryAll();
                        }, Yii::$app->params['time_duration']), 'id', 'username'),
                        'options' => ['placeholder' => 'اختر المتابع...'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label('المتابع') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'income_status')->widget(kartik\select2\Select2::class, [
                        'data' => ['', \Yii::t('app', 'active'), \Yii::t('app', 'judiciary')],
                        'language' => 'ar',
                        'options' => ['placeholder' => 'اختر الحالة...'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label('الحالة') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'company_id')->widget(kartik\select2\Select2::class, [
                        'data' => yii\helpers\ArrayHelper::map(Yii::$app->cache->getOrSet(Yii::$app->params["key_company"], function () {
                            return Yii::$app->db->createCommand(Yii::$app->params['company_query'])->queryAll();
                        }, Yii::$app->params['time_duration']), 'id', 'name'),
                        'options' => ['placeholder' => 'اختر الشركة...', 'multiple' => true],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label('الشركة') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'payment_type')->widget(kartik\select2\Select2::class, [
                        'data' => yii\helpers\ArrayHelper::map(Yii::$app->cache->getOrSet(Yii::$app->params["key_payment_type"], function () {
                            return Yii::$app->db->createCommand(Yii::$app->params['payment_type_query'])->queryAll();
                        }, Yii::$app->params['time_duration']), 'id', 'name'),
                        'options' => ['placeholder' => 'اختر طريقة الدفع...'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label('طريقة الدفع') ?>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; margin-top: 6px;">
                <?= Html::submitButton('<i class="fa fa-search"></i> بحث', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('<i class="fa fa-eraser"></i> مسح', ['reports/total-customer-payments-index'], ['class' => 'btn btn-secondary', 'style' => 'border-radius: 8px;']) ?>
            </div>
            <?php yii\widgets\ActiveForm::end() ?>
        </div>
    </div>

    <!-- إجمالي -->
    <div class="rp-total-badge">
        <i class="fa fa-calculator"></i>
        إجمالي الإيرادات: <?= $sumTotalCustomerPayments ? number_format($sumTotalCustomerPayments, 2) : '0.00' ?>
    </div>

    <!-- الجدول -->
    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'columns' => require(__DIR__ . '/_columns.php'),
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'toolbar' => [
                ['content' =>
                    Html::a('<i class="fa fa-repeat"></i>', ['reports/total-customer-payments-index'], ['data-pjax' => 1, 'class' => 'btn btn-secondary', 'title' => 'تحديث']) .
                    '{toggleData}' .
                    ExportButtons::widget([
                        'excelRoute' => '/reports/reports/export-total-payments-excel',
                        'pdfRoute'   => '/reports/reports/export-total-payments-pdf',
                    ])
                ],
            ],
            'panel' => [
                'type' => 'default',
                'heading' => '<i class="fa fa-money"></i> <strong>تقرير مدفوعات العملاء</strong>',
            ],
        ]) ?>
    </div>
</div>

<?php Modal::begin(["id" => "ajaxCrudModal", "footer" => ""]) ?>
<?php Modal::end(); ?>
