<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = $model->isNewRecord ? 'إضافة وردية' : 'تعديل وردية: ' . $model->title;

$dayNames = [
    0 => 'الأحد', 1 => 'الاثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء',
    4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت',
];
$selectedDays = is_array($model->working_days) ? $model->working_days : [0, 1, 2, 3, 4];
?>

<style>
.hr-page { padding: 20px; max-width: 720px; margin: 0 auto; }
.hr-page-header {
    display: flex; align-items: center; gap: 12px; margin-bottom: 24px;
}
.hr-page-header h1 {
    font-size: 22px; font-weight: 700; color: var(--clr-primary, #800020); margin: 0;
}
.sf-card {
    background: #fff; border-radius: 12px; padding: 28px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 1px solid #f0f0f0;
    margin-bottom: 20px;
}
.sf-card h3 {
    font-size: 15px; font-weight: 700; color: #334155; margin: 0 0 18px;
    padding-bottom: 10px; border-bottom: 1px solid #f0f0f0;
}
.sf-row { display: flex; gap: 16px; margin-bottom: 14px; }
.sf-row > * { flex: 1; }
.sf-label {
    display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;
}
.sf-input {
    width: 100%; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
    font-size: 14px; transition: border-color .2s;
}
.sf-input:focus { border-color: var(--clr-primary, #800020); outline: none; }
.sf-days { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
.sf-day-btn {
    width: 60px; padding: 8px 0; border-radius: 8px; border: 2px solid #e2e8f0;
    text-align: center; font-size: 12px; font-weight: 600; cursor: pointer;
    transition: all .2s; background: #fff; color: #64748b; user-select: none;
}
.sf-day-btn.selected {
    border-color: var(--clr-primary, #800020); background: var(--clr-primary, #800020);
    color: #fff;
}
.sf-day-btn:hover { border-color: var(--clr-primary, #800020); }
.sf-check {
    display: flex; align-items: center; gap: 10px; margin-bottom: 14px;
    font-size: 14px; color: #475569; cursor: pointer;
}
.sf-check input { width: 18px; height: 18px; accent-color: var(--clr-primary, #800020); }
.sf-actions { display: flex; gap: 10px; justify-content: flex-end; }
.sf-actions .btn { padding: 10px 24px; font-size: 14px; font-weight: 600; border-radius: 8px; }
</style>

<div class="hr-page">
    <div class="hr-page-header">
        <a href="<?= Url::to(['index']) ?>" class="btn btn-secondary btn-sm">
            <i class="fa fa-arrow-right"></i>
        </a>
        <h1><i class="fa fa-clock-o"></i> <?= $this->title ?></h1>
    </div>

    <?php $form = ActiveForm::begin(['id' => 'shift-form']); ?>

    <div class="sf-card">
        <h3><i class="fa fa-info-circle"></i> البيانات الأساسية</h3>
        <div class="sf-row">
            <div>
                <label class="sf-label">اسم الوردية *</label>
                <?= Html::activeTextInput($model, 'title', ['class' => 'sf-input', 'placeholder' => 'مثال: الوردية الصباحية']) ?>
            </div>
        </div>
        <div class="sf-row">
            <div>
                <label class="sf-label">وقت البداية *</label>
                <?= Html::activeInput('time', $model, 'start_at', ['class' => 'sf-input']) ?>
            </div>
            <div>
                <label class="sf-label">وقت النهاية *</label>
                <?= Html::activeInput('time', $model, 'end_at', ['class' => 'sf-input']) ?>
            </div>
        </div>
    </div>

    <div class="sf-card">
        <h3><i class="fa fa-calendar"></i> أيام العمل</h3>
        <div class="sf-days" id="days-picker">
            <?php foreach ($dayNames as $num => $name): ?>
                <div class="sf-day-btn <?= in_array($num, $selectedDays) ? 'selected' : '' ?>"
                     data-day="<?= $num ?>"
                     onclick="toggleDay(this)">
                    <?= $name ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php foreach ($selectedDays as $d): ?>
            <input type="hidden" name="HrWorkShift[working_days][]" value="<?= $d ?>" class="day-hidden">
        <?php endforeach; ?>
    </div>

    <div class="sf-card">
        <h3><i class="fa fa-sliders"></i> سياسات الوقت</h3>
        <div class="sf-row">
            <div>
                <label class="sf-label">فترة السماح للتأخير (دقائق)</label>
                <?= Html::activeTextInput($model, 'grace_minutes', ['class' => 'sf-input', 'type' => 'number', 'min' => 0]) ?>
            </div>
            <div>
                <label class="sf-label">خروج مبكر مسموح (دقائق)</label>
                <?= Html::activeTextInput($model, 'early_leave_minutes', ['class' => 'sf-input', 'type' => 'number', 'min' => 0]) ?>
            </div>
        </div>
        <div class="sf-row">
            <div>
                <label class="sf-label">احتساب إضافي بعد (دقائق)</label>
                <?= Html::activeTextInput($model, 'overtime_after_minutes', ['class' => 'sf-input', 'type' => 'number', 'min' => 0]) ?>
            </div>
            <div>
                <label class="sf-label">مدة الاستراحة (دقائق)</label>
                <?= Html::activeTextInput($model, 'break_duration_minutes', ['class' => 'sf-input', 'type' => 'number', 'min' => 0]) ?>
            </div>
        </div>
    </div>

    <div class="sf-card" x-data="{ flexible: <?= $model->is_flexible ? 'true' : 'false' ?> }">
        <h3><i class="fa fa-random"></i> المرونة</h3>
        <label class="sf-check">
            <input type="hidden" name="HrWorkShift[is_flexible]" value="0">
            <input type="checkbox" name="HrWorkShift[is_flexible]" value="1"
                   id="is-flexible-check" <?= $model->is_flexible ? 'checked' : '' ?>
                   x-model="flexible">
            تفعيل الوردية المرنة (يمكن للموظف البدء ضمن نافذة زمنية)
        </label>
        <div class="sf-row" id="flex-row" x-show="flexible" x-transition.duration.200ms x-cloak>
            <div>
                <label class="sf-label">نافذة المرونة (دقائق)</label>
                <?= Html::activeTextInput($model, 'flex_window_minutes', ['class' => 'sf-input', 'type' => 'number', 'min' => 0]) ?>
            </div>
            <div></div>
        </div>
    </div>

    <div class="sf-actions">
        <a href="<?= Url::to(['index']) ?>" class="btn btn-secondary">إلغاء</a>
        <?= Html::submitButton($model->isNewRecord ? 'إنشاء الوردية' : 'حفظ التعديلات', [
            'class' => 'btn btn-primary',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<script>
function toggleDay(el) {
    el.classList.toggle('selected');
    rebuildDayInputs();
}
function rebuildDayInputs() {
    document.querySelectorAll('.day-hidden').forEach(function(e){e.remove()});
    var form = document.getElementById('shift-form');
    document.querySelectorAll('.sf-day-btn.selected').forEach(function(el) {
        var inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'HrWorkShift[working_days][]';
        inp.value = el.dataset.day;
        inp.className = 'day-hidden';
        form.appendChild(inp);
    });
}
</script>
