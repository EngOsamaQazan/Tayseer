<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  فلاتر ذكية — شاشة المصاريف
 *  ─────────────────────────────────────────────────────────────────
 *  الوضع الافتراضي: التاريخ + التصنيف + الشركة
 *  متقدم: المنشئ، المبلغ، المستند، العقد، عدد النتائج
 * ═══════════════════════════════════════════════════════════════════
 *
 * @var yii\web\View $this
 * @var backend\modules\expenses\models\ExpensesSearch $model
 */

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;

/* ═══ بيانات مرجعية (كاش) ═══ */
$cache = Yii::$app->cache;
$p     = Yii::$app->params;
$d     = $p['time_duration'];
$db    = Yii::$app->db;

$categories = ArrayHelper::map(
    $cache->getOrSet($p['key_expenses_category'], fn() => $db->createCommand($p['expenses_category_query'])->queryAll(), $d),
    'id', 'name'
);

$users = ArrayHelper::map(
    $cache->getOrSet($p['key_users'], fn() => $db->createCommand($p['users_query'])->queryAll(), $d),
    'id', 'username'
);

/* ═══ هل هناك فلاتر متقدمة مفعّلة؟ ═══ */
$advancedActive = !empty($model->created_by)
    || !empty($model->amount_from)
    || !empty($model->amount_to)
    || !empty($model->document_number)
    || !empty($model->contract_id)
    || !empty($model->receiver_number)
    || !empty($model->number_row);
?>

<section class="fin-filter" x-data="{ open: <?= $advancedActive ? 'true' : 'false' ?> }">
    <?php $form = ActiveForm::begin([
        'id'     => 'exp-search',
        'method' => 'get',
        'action' => ['index'],
    ]) ?>

    <!-- ═══ فلاتر أساسية ═══ -->
    <div class="fin-filter-main">
        <!-- التاريخ (من / إلى) -->
        <div class="fin-f-group fin-f-group--date">
            <label class="fin-f-lbl"><i class="fa fa-calendar"></i> من</label>
            <?= FlatpickrWidget::widget([
                'model'     => $model,
                'attribute' => 'date_from',
                'options'   => ['placeholder' => 'من تاريخ', 'class' => 'fin-input'],
                'pluginOptions' => ['dateFormat' => 'Y-m-d'],
            ]) ?>
        </div>
        <div class="fin-f-group fin-f-group--date">
            <label class="fin-f-lbl"><i class="fa fa-calendar"></i> إلى</label>
            <?= FlatpickrWidget::widget([
                'model'     => $model,
                'attribute' => 'date_to',
                'options'   => ['placeholder' => 'إلى تاريخ', 'class' => 'fin-input'],
                'pluginOptions' => ['dateFormat' => 'Y-m-d'],
            ]) ?>
        </div>

        <!-- التصنيف -->
        <div class="fin-f-group">
            <label class="fin-f-lbl"><i class="fa fa-tags"></i> التصنيف</label>
            <?= Html::activeDropDownList($model, 'category_id', $categories, [
                'class' => 'fin-sel', 'prompt' => '-- الكل --',
            ]) ?>
        </div>

        <!-- أزرار -->
        <div class="fin-f-btn">
            <?= Html::submitButton('<i class="fa fa-search"></i> بحث', ['class' => 'fin-btn fin-btn--search']) ?>
            <?= Html::a('<i class="fa fa-refresh"></i> تعيين', ['index'], ['class' => 'fin-btn fin-btn--reset']) ?>
            <button type="button" class="fin-btn fin-btn--toggle" id="btnAdvExp" title="فلاتر متقدمة"
                    @click="open = !open" :class="{ 'fin-btn--toggle-active': open }">
                <i class="fa fa-sliders"></i> متقدم
            </button>
        </div>
    </div>

    <!-- ═══ فلاتر متقدمة ═══ -->
    <div class="fin-filter-advanced" id="advExpFilters" x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2">
        <div class="fin-adv-grid">
            <!-- المنشئ -->
            <div class="fin-f-group">
                <label class="fin-f-lbl"><i class="fa fa-user"></i> أنشئ بواسطة</label>
                <?= Html::activeDropDownList($model, 'created_by', $users, [
                    'class' => 'fin-sel', 'prompt' => '-- الكل --',
                ]) ?>
            </div>
            <!-- رقم المستلم -->
            <div class="fin-f-group">
                <label class="fin-f-lbl"><i class="fa fa-id-card-o"></i> رقم المستلم</label>
                <?= Html::activeTextInput($model, 'receiver_number', [
                    'class' => 'fin-input', 'placeholder' => 'رقم المستلم',
                ]) ?>
            </div>
            <!-- رقم العقد -->
            <div class="fin-f-group">
                <label class="fin-f-lbl"><i class="fa fa-file-text-o"></i> رقم العقد</label>
                <?= Html::activeTextInput($model, 'contract_id', [
                    'class' => 'fin-input', 'placeholder' => 'رقم العقد', 'type' => 'number',
                ]) ?>
            </div>
            <!-- رقم المستند -->
            <div class="fin-f-group">
                <label class="fin-f-lbl"><i class="fa fa-barcode"></i> رقم المستند</label>
                <?= Html::activeTextInput($model, 'document_number', [
                    'class' => 'fin-input', 'placeholder' => 'رقم المستند', 'type' => 'number',
                ]) ?>
            </div>
            <!-- نطاق المبلغ -->
            <div class="fin-f-group">
                <label class="fin-f-lbl"><i class="fa fa-money"></i> من مبلغ</label>
                <?= Html::activeTextInput($model, 'amount_from', [
                    'class' => 'fin-input', 'placeholder' => '0.00', 'type' => 'number', 'step' => '0.01',
                ]) ?>
            </div>
            <div class="fin-f-group">
                <label class="fin-f-lbl"><i class="fa fa-money"></i> إلى مبلغ</label>
                <?= Html::activeTextInput($model, 'amount_to', [
                    'class' => 'fin-input', 'placeholder' => '0.00', 'type' => 'number', 'step' => '0.01',
                ]) ?>
            </div>
            <!-- عدد النتائج -->
            <div class="fin-f-group">
                <label class="fin-f-lbl"><i class="fa fa-list-ol"></i> نتائج/صفحة</label>
                <?= Html::activeTextInput($model, 'number_row', [
                    'class' => 'fin-input', 'placeholder' => '20', 'type' => 'number',
                ]) ?>
            </div>
        </div>
    </div>

    <?php ActiveForm::end() ?>
</section>

<?php
/* OLD jQuery - replaced by Alpine.js
$advJs = <<<'JSBLOCK'
$("#btnAdvExp").on("click",function(){
    var panel=$("#advExpFilters");
    if(panel.is(":visible")){panel.slideUp(200);$(this).removeClass("fin-btn--toggle-active");}
    else{panel.slideDown(200);$(this).addClass("fin-btn--toggle-active");}
});
JSBLOCK;
$this->registerJs($advJs, \yii\web\View::POS_READY);
*/
?>
