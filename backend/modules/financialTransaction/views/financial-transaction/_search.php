<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  فلاتر ذكية — Desktop-first مع Responsive
 *  ────────────────────────────────────────────────────────────
 *  • الفلاتر الأساسية (الشركة، النوع، التاريخ) ظاهرة دائماً
 *  • الفلاتر المتقدمة مخفية خلف زر واضح
 *  • زر إعادة تعيين الفلاتر
 * ═══════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use backend\helpers\FlatpickrWidget;

$cache = Yii::$app->cache;
$p = Yii::$app->params;
$d = $p['time_duration'];
$db = Yii::$app->db;

$users     = ArrayHelper::map($cache->getOrSet($p['key_users'], fn() => $db->createCommand($p['users_query'])->queryAll(), $d), 'id', 'username');
$companies = ArrayHelper::map($cache->getOrSet($p['key_company'], fn() => $db->createCommand($p['company_query'])->queryAll(), $d), 'id', 'name');
$documents = ArrayHelper::map($cache->getOrSet($p['key_document_number'], fn() => $db->createCommand($p['document_number_query'])->queryAll(), $d), 'document_number', 'document_number');

$hasAdvanced = !empty($model->created_by) || !empty($model->Restriction) || !empty($model->document_number) || !empty($model->number_row);
?>

<section class="fin-filter" aria-label="تصفية النتائج" x-data="{ open: <?= $hasAdvanced ? 'true' : 'false' ?> }">
    <?php $form = ActiveForm::begin(['id' => 'fin-search', 'method' => 'get', 'action' => ['index']]) ?>

    <!-- الفلاتر الأساسية -->
    <div class="fin-filter-main">
        <div class="fin-f-field fin-f--grow">
            <label><i class="fa fa-building-o"></i> المُستثمر</label>
            <?= Html::activeDropDownList($model, 'company_id', $companies, ['prompt' => 'جميع المُستثمرين', 'class' => 'fin-f-input']) ?>
        </div>
        <div class="fin-f-field fin-f--sm">
            <label><i class="fa fa-exchange"></i> النوع</label>
            <?= Html::activeDropDownList($model, 'type', ['' => 'الكل', 1 => 'دائنة', 2 => 'مدينة'], ['class' => 'fin-f-input']) ?>
        </div>
        <div class="fin-f-field fin-f--grow">
            <label><i class="fa fa-calendar"></i> التاريخ</label>
            <?= FlatpickrWidget::widget([
                'model' => $model, 'attribute' => 'date',
                'options' => ['placeholder' => 'اختر التاريخ', 'class' => 'fin-f-input'],
                'pluginOptions' => ['dateFormat' => 'Y-m-d'],
            ]) ?>
        </div>
        <div class="fin-f-btns">
            <?= Html::submitButton('<i class="fa fa-search"></i> <span>بحث</span>', ['class' => 'fin-btn fin-btn--search']) ?>
            <?= Html::a('<i class="fa fa-eraser"></i>', ['index'], ['class' => 'fin-btn fin-btn--reset', 'title' => 'إعادة تعيين الفلاتر']) ?>
            <button type="button" class="fin-btn fin-btn--toggle" id="btnAdvanced" title="فلاتر متقدمة"
                    @click="open = !open" :class="{ 'active': open }">
                <i class="fa fa-sliders"></i> <span>متقدم</span>
                <?php if ($hasAdvanced): ?><span class="fin-dot"></span><?php endif ?>
            </button>
        </div>
    </div>

    <!-- الفلاتر المتقدمة -->
    <div class="fin-filter-adv" id="advPanel" x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2">
        <div class="fin-f-divider"><span>فلاتر متقدمة</span></div>
        <div class="fin-filter-row">
            <div class="fin-f-field fin-f--sm">
                <label>القيد</label>
                <?= Html::activeDropDownList($model, 'Restriction', ['' => 'الكل', 1 => 'مقيّد', 2 => 'غير مقيّد'], ['class' => 'fin-f-input']) ?>
            </div>
            <div class="fin-f-field fin-f--grow">
                <label>المنشئ</label>
                <?= Html::activeDropDownList($model, 'created_by', $users, ['prompt' => 'جميع المستخدمين', 'class' => 'fin-f-input']) ?>
            </div>
            <div class="fin-f-field fin-f--sm">
                <label>المستند</label>
                <?= Html::activeDropDownList($model, 'document_number', $documents, ['prompt' => 'الكل', 'class' => 'fin-f-input']) ?>
            </div>
            <div class="fin-f-field fin-f--xs">
                <label>نتائج/صفحة</label>
                <?= Html::activeTextInput($model, 'number_row', ['placeholder' => '20', 'type' => 'number', 'min' => 1, 'class' => 'fin-f-input']) ?>
            </div>
        </div>
    </div>

    <?php ActiveForm::end() ?>
</section>

<?php
/* OLD jQuery - replaced by Alpine.js
$this->registerJs("
    $('#btnAdvanced').on('click', function(){
        $('#advPanel').slideToggle(200);
        $(this).toggleClass('active');
    });
", \yii\web\View::POS_READY);
*/
?>
