<?php
/**
 * علاوة تلقائية حسب الأقدمية — إدخال المعطيات ثم المعاينة قبل الاعتماد.
 *
 * @var $effectiveDate string
 */

use yii\helpers\Html;

$this->title = 'علاوة تلقائية (حسب الأقدمية)';
?>
<style>
.inc-bulk-page { padding: 20px; max-width: 700px; }
.inc-bulk-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 28px; box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
.inc-bulk-card .form-group label { font-weight: 600; color: #334155; font-size: 13px; }
.inc-bulk-card .form-control { border-radius: 8px; border-color: #d1d5db; }
.inc-bulk-card .form-control:focus { border-color: #800020; box-shadow: 0 0 0 3px rgba(128,0,32,0.1); }
.inc-bulk-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.inc-bulk-header h1 { font-size: 20px; font-weight: 700; color: #800020; margin: 0; }
.inc-bulk-hint {
    background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;
    padding: 10px 14px; font-size: 12px; color: #166534; margin-bottom: 16px;
}
</style>

<div class="inc-bulk-page">
    <div class="inc-bulk-header">
        <h1><i class="fa fa-magic"></i> <?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="fa fa-arrow-right"></i> العودة', ['increments'], ['class' => 'btn btn-default btn-sm', 'style' => 'border-radius:8px']) ?>
    </div>

    <div class="inc-bulk-card">
        <div class="inc-bulk-hint">
            <i class="fa fa-info-circle"></i>
            أدخل نوع العلاوة وتاريخ السريان ثم اضغط <strong>معاينة</strong> لعرض قائمة الموظفين وعدد سنوات خدمة كل منهم وإجمالي العلاوة. بعد المعاينة يمكنك <strong>اعتماد</strong> أو <strong>رفض</strong>.
        </div>

        <form id="increment-bulk-form" method="post" action="<?= \yii\helpers\Url::to(['increment-bulk']) ?>">
            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
            <input type="hidden" name="preview" value="1">

            <div class="form-group">
                <label for="increment_type">نوع العلاوة <span class="text-danger">*</span></label>
                <select id="increment_type" name="increment_type" class="form-control" required>
                    <option value="">— اختر النوع —</option>
                    <option value="fixed">مبلغ ثابت (بالدينار)</option>
                    <option value="percentage">نسبة مئوية من الراتب الأساسي (%)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="amount">المبلغ / النسبة <span class="text-danger">*</span></label>
                <input type="number" id="amount" name="amount" class="form-control" step="0.01" min="0.01" placeholder="مثال: 50 أو 5 للنسبة" required>
            </div>

            <div class="form-group">
                <label for="effective_date">تاريخ السريان <span class="text-danger">*</span></label>
                <input type="date" id="effective_date" name="effective_date" class="form-control" value="<?= Html::encode($effectiveDate) ?>" required>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px">
                <?= Html::a('إلغاء', ['increments'], ['class' => 'btn btn-default', 'style' => 'border-radius:8px']) ?>
                <button type="submit" class="btn" style="background:#17a2b8;color:#fff;border-radius:8px">
                    <i class="fa fa-eye"></i> معاينة
                </button>
            </div>
        </form>
    </div>
</div>
