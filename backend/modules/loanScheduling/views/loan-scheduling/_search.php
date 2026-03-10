<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  فلاتر ذكية — شاشة التسويات المالية
 * ═══════════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

/* ═══ بيانات مرجعية (كاش) ═══ */
$cache = Yii::$app->cache;
$p     = Yii::$app->params;
$d     = $p['time_duration'];
$db    = Yii::$app->db;

$users = ArrayHelper::map(
    $cache->getOrSet($p['key_users'], fn() => $db->createCommand($p['users_query'])->queryAll(), $d),
    'id', 'username'
);

$statusList = [
    'pending'  => 'معلّقة',
    'approved' => 'مُوافَق',
    'rejected' => 'مرفوضة',
];

/* هل هناك فلاتر متقدمة مفعّلة ═══ */
$advancedActive = !empty($model->created_by)
    || !empty($model->last_update_by)
    || !empty($model->status_action_by)
    || !empty($model->monthly_installment);
?>

<section class="fin-filter" x-data="{ open: <?= $advancedActive ? 'true' : 'false' ?> }">
    <?php $form = ActiveForm::begin([
        'id'     => 'loan-search',
        'method' => 'get',
        'action' => ['index'],
    ]) ?>

    <!-- ═══ فلاتر أساسية ═══ -->
    <div class="fin-filter-main">
        <div class="fin-f-group">
            <label class="fin-f-lbl"><i class="fa fa-file-text-o"></i> رقم العقد</label>
            <?= Html::activeTextInput($model, 'contract_id', [
                'class' => 'fin-input', 'placeholder' => 'رقم العقد', 'type' => 'number',
            ]) ?>
        </div>
        <div class="fin-f-group">
            <label class="fin-f-lbl"><i class="fa fa-flag"></i> الحالة</label>
            <?= Html::activeDropDownList($model, 'status', $statusList, [
                'class' => 'fin-sel', 'prompt' => '-- الكل --',
            ]) ?>
        </div>

        <div class="fin-f-btn">
            <?= Html::submitButton('<i class="fa fa-search"></i> بحث', ['class' => 'fin-btn fin-btn--search']) ?>
            <?= Html::a('<i class="fa fa-refresh"></i> تعيين', ['index'], ['class' => 'fin-btn fin-btn--reset']) ?>
            <button type="button" class="fin-btn fin-btn--toggle" id="btnAdvLoan" title="فلاتر متقدمة"
                    @click="open = !open" :class="{ 'fin-btn--toggle-active': open }">
                <i class="fa fa-sliders"></i> متقدم
            </button>
        </div>
    </div>

    <!-- ═══ فلاتر متقدمة ═══ -->
    <div class="fin-filter-advanced" id="advLoanFilters" x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2">
        <div class="fin-adv-grid">
            <div class="fin-f-group">
                <label class="fin-f-lbl"><i class="fa fa-user"></i> أنشئ بواسطة</label>
                <?= Html::activeDropDownList($model, 'created_by', $users, [
                    'class' => 'fin-sel', 'prompt' => '-- الكل --',
                ]) ?>
            </div>
            <div class="fin-f-group">
                <label class="fin-f-lbl"><i class="fa fa-user-circle"></i> آخر تعديل بواسطة</label>
                <?= Html::activeDropDownList($model, 'last_update_by', $users, [
                    'class' => 'fin-sel', 'prompt' => '-- الكل --',
                ]) ?>
            </div>
            <div class="fin-f-group">
                <label class="fin-f-lbl"><i class="fa fa-gavel"></i> اتخذ القرار بواسطة</label>
                <?= Html::activeDropDownList($model, 'status_action_by', $users, [
                    'class' => 'fin-sel', 'prompt' => '-- الكل --',
                ]) ?>
            </div>
            <div class="fin-f-group">
                <label class="fin-f-lbl"><i class="fa fa-money"></i> القسط الشهري</label>
                <?= Html::activeTextInput($model, 'monthly_installment', [
                    'class' => 'fin-input', 'placeholder' => '0.00', 'type' => 'number', 'step' => '0.01',
                ]) ?>
            </div>
        </div>
    </div>

    <?php ActiveForm::end() ?>
</section>

<?php
/* OLD jQuery - replaced by Alpine.js
$advJs = <<<'JSBLOCK'
$("#btnAdvLoan").on("click",function(){
    var panel=$("#advLoanFilters");
    if(panel.is(":visible")){panel.slideUp(200);$(this).removeClass("fin-btn--toggle-active");}
    else{panel.slideDown(200);$(this).addClass("fin-btn--toggle-active");}
});
JSBLOCK;
$this->registerJs($advJs, \yii\web\View::POS_READY);
*/
?>
