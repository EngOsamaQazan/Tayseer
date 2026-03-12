<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  شاشة استيراد كشوف الحسابات البنكية — كشف تلقائي + معاينة
 *  ─────────────────────────────────────────────────────────────────
 *  المرحلة 1: رفع الملف (شركة + بنك + ملف Excel)
 *  المرحلة 2: معاينة البيانات المكتشفة + تأكيد الاستيراد
 * ═══════════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use backend\modules\companies\models\Companies;
use backend\modules\companyBanks\models\CompanyBanks;
use backend\modules\bancks\models\Bancks;

$this->title = 'استيراد كشف حساب بنكي';
$this->params['breadcrumbs'][] = ['label' => 'الحركات المالية', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$hasPreview = !empty($preview);
$currentStep = $hasPreview ? 2 : 1;

/* بيانات البنوك */
$bankIds   = ArrayHelper::map(CompanyBanks::find()->all(), 'bank_id', 'bank_id');
$bankNames = ArrayHelper::map(Bancks::find()->where(['in', 'id', $bankIds])->all(), 'id', 'name');
$companies = ArrayHelper::map(Companies::find()->all(), 'id', 'name');
?>

<div class="fin-page">

    <!-- ═══ شريط الخطوات (Stepper) ═══ -->
    <div class="fin-stepper">
        <div class="fin-step <?= $currentStep >= 1 ? 'fin-step--active' : '' ?>">
            <span class="fin-step-num">1</span>
            <span class="fin-step-lbl">رفع الملف</span>
        </div>
        <div class="fin-step-line <?= $currentStep >= 2 ? 'fin-step-line--active' : '' ?>"></div>
        <div class="fin-step <?= $currentStep >= 2 ? 'fin-step--active' : '' ?>">
            <span class="fin-step-num">2</span>
            <span class="fin-step-lbl">معاينة وتأكيد</span>
        </div>
    </div>

    <!-- ═══ رسائل النظام ═══ -->
    <?php foreach (Yii::$app->session->getAllFlashes() as $type => $msg): ?>
        <div class="fin-alert fin-alert--<?= $type === 'error' ? 'danger' : $type ?>">
            <i class="fa fa-<?= $type === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <?= nl2br(Html::encode(is_array($msg) ? $msg[0] : $msg)) ?>
        </div>
    <?php endforeach ?>

    <!-- ╔═══════════════════════════════════════════════╗
         ║  المرحلة 1: نموذج رفع الملف                  ║
         ╚═══════════════════════════════════════════════╝ -->
    <section class="fin-import-card">
        <div class="fin-import-head">
            <i class="fa fa-cloud-upload"></i>
            <h3>رفع كشف الحساب</h3>
            <p>اختر الشركة والبنك ثم ارفع ملف Excel — النظام يكتشف الأعمدة تلقائياً</p>
        </div>

        <?php $form = ActiveForm::begin([
            'id' => 'import-form',
            'options' => ['enctype' => 'multipart/form-data'],
        ]) ?>

        <div class="fin-import-fields">
            <div class="fin-import-field">
                <label><i class="fa fa-building-o"></i> الشركة</label>
                <?= $form->field($model, 'company_id')->widget(Select2::class, [
                    'data' => $companies,
                    'options' => ['placeholder' => 'اختر الشركة...'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label(false) ?>
            </div>
            <div class="fin-import-field">
                <label><i class="fa fa-university"></i> البنك</label>
                <?= $form->field($model, 'bank_id')->widget(Select2::class, [
                    'data' => $bankNames,
                    'options' => ['placeholder' => 'اختر البنك...'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label(false) ?>
            </div>
            <div class="fin-import-field fin-import-field--file">
                <label><i class="fa fa-file-excel-o"></i> ملف Excel</label>
                <?= $form->field($model, 'excel_file')->fileInput([
                    'accept' => '.xlsx,.xls',
                    'class' => 'fin-file-input',
                ])->label(false) ?>
                <small class="fin-file-hint">يدعم: .xlsx و .xls — أي تنسيق كشف حساب من أي بنك</small>
            </div>
        </div>

        <?php if (!$hasPreview): ?>
        <div class="fin-import-actions">
            <?= Html::submitButton('<i class="fa fa-search"></i> تحليل الملف', ['class' => 'fin-btn fin-btn--search fin-btn--lg']) ?>
            <?= Html::a('<i class="fa fa-arrow-right"></i> العودة', ['index'], ['class' => 'fin-btn fin-btn--reset fin-btn--lg']) ?>
        </div>
        <?php endif ?>

        <?php ActiveForm::end() ?>
    </section>

    <?php if ($hasPreview): ?>
    <!-- ╔═══════════════════════════════════════════════════════╗
         ║  المرحلة 2: نتيجة التحليل + معاينة + تأكيد          ║
         ╚═══════════════════════════════════════════════════════╝ -->

    <!-- ═══ شريط حالة الكشف ═══ -->
    <section class="fin-detect-status">
        <div class="fin-detect-head">
            <h4><i class="fa fa-magic"></i> نتيجة الكشف التلقائي</h4>
            <?php if ($analysis): ?>
            <span class="fin-confidence fin-confidence--<?= $analysis['confidence'] >= 70 ? 'high' : ($analysis['confidence'] >= 40 ? 'mid' : 'low') ?>">
                ثقة: <?= $analysis['confidence'] ?>%
            </span>
            <?php endif ?>
        </div>
        <div class="fin-detect-chips">
            <?php
            $fieldLabels = [
                'date' => 'التاريخ', 'description' => 'البيان',
                'debit' => 'المدين', 'credit' => 'الدائن',
                'amount' => 'المبلغ', 'balance' => 'الرصيد',
            ];
            foreach ($fieldLabels as $field => $label):
                $found = isset($mapping[$field]);
                $col   = $found ? $mapping[$field] : null;
                $header = $col && isset($analysis['originalHeaders'][$col]) ? $analysis['originalHeaders'][$col] : '';
            ?>
            <div class="fin-chip <?= $found ? 'fin-chip--ok' : 'fin-chip--miss' ?>">
                <i class="fa fa-<?= $found ? 'check' : 'times' ?>"></i>
                <span class="fin-chip-lbl"><?= $label ?></span>
                <?php if ($found): ?>
                <span class="fin-chip-col"><?= $col ?><?= $header ? ": $header" : '' ?></span>
                <?php endif ?>
            </div>
            <?php endforeach ?>
        </div>
    </section>

    <!-- ═══ تعديل ربط الأعمدة يدوياً ═══ -->
    <form method="post" id="confirm-form" action="<?= \yii\helpers\Url::to(['import-file']) ?>">
        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
        <input type="hidden" name="confirm" value="1">
        <input type="hidden" name="temp_file" value="<?= Html::encode($tempFile) ?>">
        <input type="hidden" name="company_id" value="<?= Html::encode($model->company_id) ?>">
        <input type="hidden" name="bank_id" value="<?= Html::encode($model->bank_id) ?>">
        <input type="hidden" name="data_start_row" value="<?= $analysis['dataStartRow'] ?? 2 ?>">

        <section class="fin-mapping-card">
            <h4><i class="fa fa-columns"></i> ربط الأعمدة — يمكنك التعديل يدوياً إذا لزم الأمر</h4>
            <div class="fin-mapping-grid">
                <?php foreach ($fieldLabels as $field => $label): ?>
                <div class="fin-mapping-item">
                    <label><?= $label ?></label>
                    <select name="mapping_<?= $field ?>" class="fin-f-input">
                        <option value="">-- غير محدد --</option>
                        <?php foreach ($availableColumns as $col => $colLabel): ?>
                        <option value="<?= $col ?>" <?= (isset($mapping[$field]) && $mapping[$field] === $col) ? 'selected' : '' ?>>
                            <?= Html::encode($colLabel) ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <?php endforeach ?>
            </div>
        </section>

        <!-- ═══ جدول المعاينة ═══ -->
        <section class="fin-preview-card">
            <h4><i class="fa fa-eye"></i> معاينة البيانات (أول <?= count($preview) ?> صفوف)</h4>
            <div class="fin-table-wrap">
                <table class="fin-table">
                    <thead>
                        <tr>
                            <th class="fin-th">#</th>
                            <th class="fin-th">التاريخ</th>
                            <th class="fin-th">البيان</th>
                            <th class="fin-th fin-th--center">النوع</th>
                            <th class="fin-th fin-th--num">المبلغ</th>
                            <th class="fin-th">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview as $i => $row): ?>
                        <?php
                            $hasErr    = !empty($row['errors']);
                            $isOpening = !empty($row['openingBalance']);
                            /* تحديد كلاس الصف */
                            if ($isOpening) {
                                $rowClass = 'fin-row--opening';
                            } elseif ($hasErr) {
                                $rowClass = 'fin-row--warn';
                            } else {
                                $rowClass = $row['type'] == 1 ? 'fin-row--credit' : 'fin-row--debit';
                            }
                        ?>
                        <tr class="fin-row <?= $rowClass ?>">
                            <td class="fin-td" style="opacity:.5"><?= $row['row_number'] ?></td>
                            <td class="fin-td fin-td--date"><?= Html::encode($row['date'] ?? '—') ?></td>
                            <td class="fin-td fin-td--desc"><?= Html::encode($row['description'] ?? '—') ?></td>
                            <td class="fin-td fin-td--type">
                                <?php if ($isOpening): ?>
                                    <span class="fin-pill fin-pill--opening"><i class="fa fa-ban"></i> رصيد افتتاحي</span>
                                <?php elseif ($row['type'] == 1): ?>
                                    <span class="fin-pill fin-pill--credit"><i class="fa fa-arrow-down"></i> دائنة</span>
                                <?php elseif ($row['type'] == 2): ?>
                                    <span class="fin-pill fin-pill--debit"><i class="fa fa-arrow-up"></i> مدينة</span>
                                <?php else: ?>
                                    <span class="fin-na">—</span>
                                <?php endif ?>
                            </td>
                            <td class="fin-td fin-td--amount">
                                <?php if ($isOpening): ?>
                                    <span class="fin-amt" style="opacity:.4;text-decoration:line-through"><?= number_format($row['amount'], 2) ?></span>
                                <?php else: ?>
                                    <span class="fin-amt <?= $row['type'] == 1 ? 'fin-amt--credit' : 'fin-amt--debit' ?>">
                                        <?= number_format($row['amount'], 2) ?>
                                    </span>
                                <?php endif ?>
                            </td>
                            <td class="fin-td">
                                <?php if ($isOpening): ?>
                                    <span class="fin-badge fin-badge--skip" title="رصيد افتتاحي — سيتم تخطيه">
                                        <i class="fa fa-forward"></i> سيُتخطى
                                    </span>
                                <?php elseif ($hasErr): ?>
                                    <span class="fin-badge fin-badge--err" title="<?= Html::encode(implode(', ', $row['errors'])) ?>">
                                        <i class="fa fa-exclamation-triangle"></i> خطأ
                                    </span>
                                <?php else: ?>
                                    <span class="fin-badge fin-badge--ok"><i class="fa fa-check"></i> جاهز</span>
                                <?php endif ?>
                            </td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ═══ ملخص الاستيراد ═══ -->
        <?php if ($summary): ?>
        <section class="fin-import-summary">
            <div class="fin-ov-card fin-ov--count">
                <div class="fin-ov-icon"><i class="fa fa-list-ol"></i></div>
                <div class="fin-ov-body">
                    <span class="fin-ov-num"><?= number_format($summary['importableRows'] ?? $summary['totalRows']) ?></span>
                    <span class="fin-ov-lbl">حركات ستُستورد</span>
                </div>
            </div>
            <div class="fin-ov-card fin-ov--credit">
                <div class="fin-ov-icon"><i class="fa fa-arrow-down"></i></div>
                <div class="fin-ov-body">
                    <span class="fin-ov-num"><?= number_format($summary['totalCredit'], 2) ?></span>
                    <span class="fin-ov-lbl">إجمالي الدائن</span>
                </div>
            </div>
            <div class="fin-ov-card fin-ov--debit">
                <div class="fin-ov-icon"><i class="fa fa-arrow-up"></i></div>
                <div class="fin-ov-body">
                    <span class="fin-ov-num"><?= number_format($summary['totalDebit'], 2) ?></span>
                    <span class="fin-ov-lbl">إجمالي المدين</span>
                </div>
            </div>
            <?php if (!empty($summary['skippedOpeningBalance'])): ?>
            <div class="fin-ov-card" style="border-color:#8b5cf6">
                <div class="fin-ov-icon" style="background:#ede9fe;color:#6d28d9"><i class="fa fa-ban"></i></div>
                <div class="fin-ov-body">
                    <span class="fin-ov-num" style="color:#6d28d9"><?= $summary['skippedOpeningBalance'] ?></span>
                    <span class="fin-ov-lbl">رصيد افتتاحي (مُستبعد)</span>
                </div>
            </div>
            <?php endif ?>
            <?php if ($summary['errorRows'] > 0): ?>
            <div class="fin-ov-card" style="border-color:#f59e0b">
                <div class="fin-ov-icon" style="background:#fef3c7;color:#b45309"><i class="fa fa-exclamation-triangle"></i></div>
                <div class="fin-ov-body">
                    <span class="fin-ov-num" style="color:#b45309"><?= $summary['errorRows'] ?></span>
                    <span class="fin-ov-lbl">صفوف بها أخطاء (ستُتخطى)</span>
                </div>
            </div>
            <?php endif ?>
        </section>
        <?php endif ?>

        <!-- ═══ تفاصيل الصفوف التي بها أخطاء ═══ -->
        <?php if (!empty($errorRows)): ?>
        <section class="fin-errors-card">
            <h4><i class="fa fa-exclamation-triangle"></i> تفاصيل الصفوف التي بها أخطاء (<?= count($errorRows) ?> صف)</h4>
            <div class="fin-table-wrap">
                <table class="fin-table">
                    <thead>
                        <tr>
                            <th class="fin-th">رقم الصف</th>
                            <th class="fin-th">التاريخ</th>
                            <th class="fin-th">البيان</th>
                            <th class="fin-th">المبلغ</th>
                            <th class="fin-th">الخطأ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($errorRows as $eRow): ?>
                        <tr class="fin-row fin-row--warn">
                            <td class="fin-td" style="font-weight:700"><?= $eRow['row_number'] ?></td>
                            <td class="fin-td"><?= Html::encode($eRow['date'] ?? '—') ?></td>
                            <td class="fin-td"><?= Html::encode(mb_substr($eRow['description'] ?? '—', 0, 60)) ?></td>
                            <td class="fin-td"><?= $eRow['amount'] > 0 ? number_format($eRow['amount'], 2) : '—' ?></td>
                            <td class="fin-td">
                                <?php foreach ($eRow['errors'] as $err): ?>
                                    <span class="fin-badge fin-badge--err"><i class="fa fa-times-circle"></i> <?= Html::encode($err) ?></span>
                                <?php endforeach ?>
                            </td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif ?>

        <!-- ═══ أزرار التأكيد ═══ -->
        <div class="fin-import-confirm">
            <button type="submit" class="fin-btn fin-btn--add fin-btn--lg">
                <i class="fa fa-check-circle"></i> تأكيد الاستيراد
            </button>
            <?= Html::a('<i class="fa fa-times"></i> إلغاء', ['import-file'], ['class' => 'fin-btn fin-btn--reset fin-btn--lg']) ?>
        </div>
    </form>
    <?php endif ?>
</div>
