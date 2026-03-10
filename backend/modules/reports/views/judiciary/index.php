<?php
/**
 * التقارير القضائية — تصميم احترافي
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use backend\widgets\ExportButtons;
use backend\modules\judiciaryType\models\JudiciaryType;
use backend\modules\lawyers\models\Lawyers;
use backend\helpers\FlatpickrWidget;

$this->title = 'التقارير القضائية';
$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);

CrudAsset::register($this);

$court = Yii::$app->cache->getOrSet("l1", function () {
    return Yii::$app->db->createCommand(Yii::$app->params['court_query'])->queryAll();
}, Yii::$app->params['time_duration']);
?>

<?= $this->render('@app/views/layouts/_reports-tabs', ['activeTab' => 'judiciary']) ?>

<style>
.rp-page { padding: 16px 0; }
.rp-filter-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px 24px 18px; margin-bottom: 20px; box-shadow: 0 1px 6px rgba(0,0,0,0.04); }
.rp-filter-header { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
.rp-filter-header i { font-size: 18px; color: #7c3aed; }
.rp-filter-header h3 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0; }
.rp-filter-header .toggle-btn { margin-right: auto; margin-left: 0; background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 16px; padding: 4px 8px; border-radius: 6px; }
.rp-filter-header .toggle-btn:hover { background: #f1f5f9; color: #334155; }
.rp-filter-body .form-group { margin-bottom: 12px; }
.rp-filter-body .form-group label { font-size: 12.5px; font-weight: 600; color: #475569; }
.rp-filter-body .form-control { border-radius: 8px; border: 1.5px solid #e2e8f0; font-size: 13px; }
.rp-filter-body .btn-primary { background: #7c3aed; border-color: #7c3aed; border-radius: 8px; font-weight: 600; padding: 8px 28px; }
.rp-filter-body .btn-primary:hover { background: #6d28d9; border-color: #6d28d9; }
.rp-total-badge { display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #7c3aed, #8b5cf6); color: #fff; padding: 10px 24px; border-radius: 10px; font-weight: 700; font-size: 16px; margin-bottom: 16px; }
.rp-total-badge i { font-size: 18px; }
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
            <h3>فلترة القضايا</h3>
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
                'id' => 'judiciary-search-form',
                'method' => 'get',
                'action' => ['/reports/reports/judiciary-index'],
            ]);
            ?>
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($searchModel, 'court_id')->widget(kartik\select2\Select2::class, [
                        'data' => ArrayHelper::map($court, 'id', 'name'),
                        'options' => ['placeholder' => 'اختر المحكمة...'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label('المحكمة') ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($searchModel, 'type_id')->widget(kartik\select2\Select2::class, [
                        'data' => Yii::$app->cache->getOrSet(Yii::$app->params["key_judiciary_type"], function () {
                            return ArrayHelper::map(JudiciaryType::find()->all(), 'id', 'name');
                        }, Yii::$app->params['time_duration']),
                        'options' => ['placeholder' => 'اختر النوع...'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label('نوع القضية') ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($searchModel, 'lawyer_id')->widget(kartik\select2\Select2::class, [
                        'data' => Yii::$app->cache->getOrSet(Yii::$app->params["key_lawyer"], function () {
                            return ArrayHelper::map(Lawyers::find()->all(), 'id', 'name');
                        }, Yii::$app->params['time_duration']),
                        'options' => ['placeholder' => 'اختر المحامي...'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label('المحامي') ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'contract_id')->widget(kartik\select2\Select2::class, [
                        'data' => Yii::$app->cache->getOrSet(Yii::$app->params["key_judiciary_contract"], function () {
                            return ArrayHelper::map(\backend\modules\judiciary\models\Judiciary::find()->all(), 'contract_id', 'contract_id');
                        }, Yii::$app->params['time_duration']),
                        'options' => ['placeholder' => 'اختر العقد...'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label('رقم العقد') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'from_income_date')->widget(FlatpickrWidget::class, [
                        'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                        'options' => ['placeholder' => 'من...'],
                    ])->label('من تاريخ الورود') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'to_income_date')->widget(FlatpickrWidget::class, [
                        'pluginOptions' => ['dateFormat' => 'Y-m-d'],
                        'options' => ['placeholder' => 'إلى...'],
                    ])->label('إلى تاريخ الورود') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'year')->widget(kartik\select2\Select2::class, [
                        'data' => $searchModel->year(),
                        'options' => ['placeholder' => 'اختر السنة...'],
                        'pluginOptions' => ['allowClear' => true],
                    ])->label('السنة') ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'lawyer_cost')->textInput(['placeholder' => 'أتعاب المحامي'])->label('أتعاب المحامي') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'case_cost')->textInput(['placeholder' => 'تكلفة القضية'])->label('تكلفة القضية') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'judiciary_number')->textInput(['placeholder' => 'رقم القضية'])->label('رقم القضية') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'number_row')->textInput(['maxlength' => true, 'placeholder' => 'عدد النتائج'])->label('عدد الصفوف') ?>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; margin-top: 6px;">
                <?= Html::submitButton('<i class="fa fa-search"></i> بحث', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('<i class="fa fa-eraser"></i> مسح', ['/reports/reports/judiciary-index'], ['class' => 'btn btn-default', 'style' => 'border-radius: 8px;']) ?>
            </div>
            <?php yii\widgets\ActiveForm::end() ?>
        </div>
    </div>

    <?php if (!empty($hasFilter)): ?>
    <!-- إجمالي -->
    <div class="rp-total-badge">
        <i class="fa fa-gavel"></i>
        عدد القضايا: <?= isset($counter) ? number_format($counter) : '0' ?>
    </div>

    <!-- الجدول -->
    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'columns' => require(__DIR__ . '/_columns.php'),
            'summary' => '',
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'toolbar' => [
                ['content' =>
                    Html::a('<i class="fa fa-repeat"></i>', ['/reports/reports/judiciary-index'], ['data-pjax' => 1, 'class' => 'btn btn-default', 'title' => 'تحديث']) .
                    '{toggleData}' .
                    ExportButtons::widget([
                        'excelRoute' => '/reports/reports/export-jud-index-excel',
                        'pdfRoute'   => '/reports/reports/export-jud-index-pdf',
                    ])
                ],
            ],
            'panel' => [
                'type' => 'default',
                'heading' => '<i class="fa fa-gavel"></i> <strong>القضايا</strong>',
            ],
        ]) ?>
    </div>
    <?php else: ?>
    <div style="text-align:center; padding:60px 20px; background:#fff; border-radius:14px; border:1px solid #e2e8f0;">
        <i class="fa fa-search" style="font-size:48px; color:#cbd5e1; margin-bottom:16px; display:block;"></i>
        <h4 style="color:#64748b; font-weight:700; margin-bottom:8px;">استخدم الفلتر أعلاه لعرض النتائج</h4>
        <p style="color:#94a3b8; font-size:13px;">حدد معايير البحث ثم اضغط "بحث" لعرض التقارير القضائية</p>
    </div>
    <?php endif; ?>
</div>

<?php Modal::begin(["id" => "ajaxCrudModal", "footer" => ""]) ?>
<?php Modal::end(); ?>
