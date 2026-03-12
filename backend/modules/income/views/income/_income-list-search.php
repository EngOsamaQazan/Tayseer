<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  فلاتر ذكية — شاشة الدفعات
 *  ─────────────────────────────────────────────────────────────────
 *  فلاتر أساسية دائمة: التاريخ من/إلى ، الشركة ، نوع الدفع
 *  فلاتر متقدمة (قابلة للطي): اسم الدافع ، التصنيف ، عدد النتائج
 *  زر بحث + إعادة تعيين
 * ═══════════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;

/* @var $this yii\web\View */
/* @var $model backend\modules\income\models\IncomeSearch */

/* ═══ بيانات مرجعية (كاش) ═══ */
$cache = Yii::$app->cache;
$p     = Yii::$app->params;
$d     = $p['time_duration'];
$db    = Yii::$app->db;

$companies = ArrayHelper::map(
    $cache->getOrSet($p['key_company'], fn() => $db->createCommand($p['company_query'])->queryAll(), $d), 'id', 'name');
$payTypes = ArrayHelper::map(
    $cache->getOrSet($p['key_payment_type'], fn() => $db->createCommand($p['payment_type_query'])->queryAll(), $d), 'id', 'name');
$incTypes = ArrayHelper::map(
    $cache->getOrSet($p['key_income_category'], fn() => $db->createCommand($p['income_category_query'])->queryAll(), $d), 'id', 'name');
$byNames = ArrayHelper::map(
    array_filter(
        $cache->getOrSet($p['key_income_by'], fn() => $db->createCommand($p['income_by_query'])->queryAll(), $d),
        fn($row) => $row['_by'] !== null && $row['_by'] !== ''
    ), '_by', '_by');

/* هل هناك فلاتر متقدمة مفعّلة ═══ */
$hasAdv = !empty($model->_by) || !empty($model->type) || !empty($model->number_row);
?>

<section class="fin-filter" aria-label="فلاتر البحث" x-data="{ open: <?= $hasAdv ? 'true' : 'false' ?> }">
    <?php $form = ActiveForm::begin([
        'id'      => 'inc-search',
        'method'  => 'get',
        'action'  => ['income-list'],
        'options' => ['class' => 'fin-filter-form'],
    ]) ?>

    <!-- ══ فلاتر أساسية ══ -->
    <div class="fin-filter-main">
        <div class="fin-f-group">
            <label class="fin-f-label">من</label>
            <?= FlatpickrWidget::widget([
                'model'         => $model,
                'attribute'     => 'date_from',
                'options'       => ['class' => 'form-control fin-input', 'placeholder' => 'من تاريخ'],
                'pluginOptions' => ['dateFormat' => 'Y-m-d'],
            ]) ?>
        </div>
        <div class="fin-f-group">
            <label class="fin-f-label">إلى</label>
            <?= FlatpickrWidget::widget([
                'model'         => $model,
                'attribute'     => 'date_to',
                'options'       => ['class' => 'form-control fin-input', 'placeholder' => 'إلى تاريخ'],
                'pluginOptions' => ['dateFormat' => 'Y-m-d'],
            ]) ?>
        </div>
        <div class="fin-f-group">
            <label class="fin-f-label">الشركة</label>
            <?= Html::activeDropDownList($model, 'company_id', $companies, ['class' => 'form-control fin-sel', 'prompt' => '— الكل —']) ?>
        </div>
        <div class="fin-f-group">
            <label class="fin-f-label">نوع الدفع</label>
            <?= Html::activeDropDownList($model, 'payment_type', $payTypes, ['class' => 'form-control fin-sel', 'prompt' => '— الكل —']) ?>
        </div>

        <div class="fin-f-btn">
            <?= Html::submitButton('<i class="fa fa-search"></i> بحث', ['class' => 'fin-btn fin-btn--search']) ?>
            <?= Html::a('<i class="fa fa-refresh"></i> تعيين', ['income-list'], ['class' => 'fin-btn fin-btn--reset']) ?>
            <button type="button" class="fin-btn fin-btn--toggle" id="incBtnAdvanced" title="فلاتر متقدمة"
                    @click="open = !open" :class="{ 'fin-btn--active': open }">
                متقدم <?= $hasAdv ? '<span class="fin-badge-active">●</span>' : '' ?>
            </button>
        </div>
    </div>

    <!-- ══ فلاتر متقدمة (قابلة للطي) ══ -->
    <div class="fin-filter-adv" id="incFilterAdv" x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2">
        <div class="fin-f-group">
            <label class="fin-f-label">الدافع</label>
            <?= Html::activeDropDownList($model, '_by', $byNames, ['class' => 'form-control fin-sel', 'prompt' => '— الكل —']) ?>
        </div>
        <div class="fin-f-group">
            <label class="fin-f-label">التصنيف</label>
            <?= Html::activeDropDownList($model, 'type', $incTypes, ['class' => 'form-control fin-sel', 'prompt' => '— الكل —']) ?>
        </div>
        <div class="fin-f-group">
            <label class="fin-f-label">عدد النتائج</label>
            <?= Html::activeTextInput($model, 'number_row', ['class' => 'form-control fin-input', 'placeholder' => 'مثلاً: 50', 'type' => 'number', 'min' => 1]) ?>
        </div>
    </div>

    <?php ActiveForm::end() ?>
</section>

<?php
/* OLD jQuery - replaced by Alpine.js
$advJs = <<<'JSINC'
$("#incBtnAdvanced").on("click",function(){
    var adv=$("#incFilterAdv");
    adv.slideToggle(200);
    $(this).toggleClass("fin-btn--active");
});
JSINC;
$this->registerJs($advJs, \yii\web\View::POS_READY);
*/
?>
