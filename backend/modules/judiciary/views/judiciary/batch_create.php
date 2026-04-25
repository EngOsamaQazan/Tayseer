<?php
/**
 * Batch Judiciary Case Creation Wizard.
 *
 * Three-step wizard:
 *   1) Input — pick how to feed contract IDs (paste / Excel / system selection)
 *   2) Review — preview table, shared common-data form, per-row overrides, templates
 *   3) Execute — chunked AJAX run with live progress + per-contract log
 *
 * @var \yii\web\View $this
 * @var int[] $preselected      Optional: contract ids passed via GET (e.g. from legal-dept screen)
 * @var string $defaultTab      paste|excel|selection|preview
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

use backend\modules\judiciaryType\models\JudiciaryType;
use backend\modules\court\models\Court;
use backend\modules\lawyers\models\Lawyers;
use backend\modules\JudiciaryInformAddress\model\JudiciaryInformAddress;
use backend\modules\companies\models\Companies;
use backend\modules\contracts\models\Contracts;
use backend\modules\jobs\models\Jobs;

$this->title = 'تجهيز القضايا — معالج جماعي';
$this->params['breadcrumbs'][] = ['label' => 'القضاء', 'url' => ['/judiciary/judiciary/index']];
$this->params['breadcrumbs'][] = $this->title;

$db    = Yii::$app->db;
$cache = Yii::$app->cache;
$p     = Yii::$app->params;
$d     = $p['time_duration'] ?? 3600;

$courts    = ArrayHelper::map(Court::find()->asArray()->all(), 'id', 'name');
$types     = ArrayHelper::map(JudiciaryType::find()->asArray()->all(), 'id', 'name');
$lawyers   = ArrayHelper::map(Lawyers::find()->asArray()->all(), 'id', 'name');
$addresses = ArrayHelper::map(JudiciaryInformAddress::find()->asArray()->all(), 'id', 'address');
$companies = ArrayHelper::map(Companies::find()->asArray()->all(), 'id', 'name');
$years     = array_combine(range((int)date('Y'), 2010), range((int)date('Y'), 2010));

$contractTypeLabels = Contracts::getTypeLabels();
$contractStatusLabels = [
    'active' => 'نشط',
    'judiciary_active' => 'قضاء فعّال',
    'judiciary_paid' => 'قضاء مسدد',
    'judiciary' => 'قضاء (الكل)',
    'legal_department' => 'قانوني',
    'settlement' => 'تسوية',
    'finished' => 'منتهي',
    'canceled' => 'ملغي',
];

$jobTypeRows = !empty($p['key_job_type']) && !empty($p['job_type_query'])
    ? $cache->getOrSet($p['key_job_type'], fn() => $db->createCommand($p['job_type_query'])->queryAll(), $d)
    : [];
$jobTypes = ArrayHelper::map($jobTypeRows, 'id', 'name');

$jobs = $cache->getOrSet('lookup_jobs', fn() => ArrayHelper::map(
    Jobs::find()->andWhere(['or', ['is_deleted' => 0], ['is_deleted' => null]])->orderBy(['name' => SORT_ASC])->asArray()->all(),
    'id',
    'name'
), 3600);

$defaultType = null;
foreach ($types as $tid => $tname) {
    if (mb_strpos($tname, 'تنفيذ') !== false) { $defaultType = $tid; break; }
}

$endpoints = [
    'parse'         => Url::to(['batch-parse-input']),
    'start'         => Url::to(['batch-start']),
    'execute'       => Url::to(['batch-execute-chunk']),
    'finalize'      => Url::to(['batch-finalize']),
    'printRedirect' => Url::to(['batch-print-redirect']),
    'history'       => Url::to(['batch-history']),
    'index'         => Url::to(['index']),
    'tplList'       => Url::to(['batch-template-list']),
    'tplSave'       => Url::to(['batch-template-save']),
    'tplDelete'     => Url::to(['batch-template-delete']),
    'search'        => Url::to(['contract-search']),
];

$bootstrap = [
    'preselected' => array_values($preselected ?? []),
    'defaultTab'  => $defaultTab,
    'endpoints'   => $endpoints,
    'csrf'        => [
        'param' => Yii::$app->request->csrfParam,
        'token' => Yii::$app->request->csrfToken,
    ],
    'addresses'   => array_map(fn($id, $addr) => ['id' => (int)$id, 'address' => $addr], array_keys($addresses), array_values($addresses)),
    'lawyers'     => array_map(fn($id, $name) => ['id' => (int)$id, 'name' => $name], array_keys($lawyers), array_values($lawyers)),
    'types'       => array_map(fn($id, $name) => ['id' => (int)$id, 'name' => $name], array_keys($types), array_values($types)),
    'companies'   => array_map(fn($id, $name) => ['id' => (int)$id, 'name' => $name], array_keys($companies), array_values($companies)),
    'defaultType' => $defaultType,
    'maxContracts'=> 100,
    'chunkSize'   => 10,
];
?>

<style>
.bw-page { max-width: 1500px; margin: 0 auto; padding: 0 16px 40px; }
.bw-header {
    display: flex; align-items: center; gap: 16px; margin-bottom: 20px;
    padding: 18px 24px; background: linear-gradient(135deg, #1a365d 0%, #2d3748 100%);
    border-radius: 12px; color: #fff; flex-wrap: wrap;
}
.bw-header h1 { font-size: 22px; font-weight: 700; margin: 0; }
.bw-header .bw-spacer { flex: 1; }
.bw-header a { color: #fff; text-decoration: none; }
.bw-header .bw-link {
    background: rgba(255,255,255,.12); padding: 8px 14px; border-radius: 8px;
    border: 1px solid rgba(255,255,255,.25); font-weight: 600; font-size: 13px;
}
.bw-header .bw-link:hover { background: rgba(255,255,255,.22); }

.bw-stepper { display: flex; gap: 8px; margin-bottom: 20px; }
.bw-step {
    flex: 1; display: flex; align-items: center; gap: 10px;
    padding: 14px 18px; background: #fff; border: 2px solid #e2e8f0;
    border-radius: 10px; font-weight: 600; color: #64748b;
}
.bw-step.active { border-color: #1a365d; color: #1a365d; background: #eef4fb; }
.bw-step.done   { border-color: #16a34a; color: #16a34a; background: #f0fdf4; }
.bw-step .bw-num {
    display: inline-flex; width: 28px; height: 28px; border-radius: 50%;
    background: currentColor; color: #fff; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 800;
}
.bw-step .bw-num span { color: #fff; }
.bw-step.done .bw-num::after { content: '✓'; }
.bw-step.done .bw-num span { display: none; }

.bw-card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06); padding: 20px; margin-bottom: 18px; }
.bw-card h3 { font-size: 16px; font-weight: 700; margin: 0 0 14px; color: #1a365d; }

.bw-tabs { display: flex; gap: 4px; border-bottom: 2px solid #e2e8f0; margin-bottom: 18px; }
.bw-tab {
    padding: 10px 18px; cursor: pointer; font-weight: 600; color: #64748b;
    border-bottom: 3px solid transparent; margin-bottom: -2px; user-select: none;
}
.bw-tab.active { color: #1a365d; border-color: #1a365d; }
.bw-tab-pane { display: none; }
.bw-tab-pane.active { display: block; }

.bw-textarea { width: 100%; min-height: 130px; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: monospace; font-size: 14px; }
.bw-drop {
    border: 2px dashed #cbd5e1; border-radius: 12px; padding: 32px;
    text-align: center; color: #64748b; cursor: pointer; transition: all .15s;
}
.bw-drop:hover, .bw-drop.dragover { border-color: #1a365d; background: #eef4fb; color: #1a365d; }
.bw-drop input[type=file] { display: none; }

.bw-filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px 10px; margin-bottom: 14px; }
.bw-filters label { font-size: 12px; color: #475569; font-weight: 600; display: block; margin-bottom: 4px; }
.bw-filters input, .bw-filters select { width: 100%; padding: 7px 9px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; min-height: 36px; }
.bw-filters .select2-container { width: 100% !important; }
.bw-filters .select2-container--bootstrap4 .select2-selection--multiple { min-height: 36px; border-color: #cbd5e1; border-radius: 6px; }
.bw-filters .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice { background: #1a365d; color: #fff; border: none; padding: 1px 8px; border-radius: 4px; font-size: 12px; margin: 3px 4px 0 0; }
.bw-filters .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove { color: #fff; margin-left: 4px; }
.bw-filters .select2-container--bootstrap4 .select2-search--inline .select2-search__field { font-size: 12px; padding: 4px; }
.bw-filters .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__rendered { padding: 1px 4px; }

.bw-table-wrap { overflow-x: auto; }
.bw-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.bw-table th, .bw-table td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; text-align: right; vertical-align: middle; }
.bw-table thead th { background: #f8fafc; font-weight: 700; color: #334155; position: sticky; top: 0; }
.bw-table tbody tr:hover { background: #f8fafc; }
.bw-table .bw-warn-row { background: #fff7ed; }
.bw-table .bw-warn-row:hover { background: #ffedd5; }
.bw-table select, .bw-table input { padding: 4px 6px; border: 1px solid #cbd5e1; border-radius: 5px; font-size: 12px; min-width: 110px; }
.bw-table .bw-rm { background: #fee2e2; color: #b91c1c; border: none; padding: 4px 9px; border-radius: 6px; cursor: pointer; font-size: 12px; }
.bw-table .bw-rm:hover { background: #fecaca; }

.bw-shared-grid { display: grid; grid-template-columns: 360px 1fr; gap: 18px; }
@media (max-width: 992px) { .bw-shared-grid { grid-template-columns: 1fr; } }
.bw-shared-form { background: #f8fafc; padding: 16px; border-radius: 10px; position: sticky; top: 16px; align-self: start; }
.bw-shared-form .bw-fg { margin-bottom: 12px; }
.bw-shared-form label { font-size: 12px; font-weight: 600; color: #1a365d; display: block; margin-bottom: 5px; }
.bw-shared-form select, .bw-shared-form input { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; }
.bw-shared-form .bw-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.bw-tpl-row { display: flex; gap: 6px; align-items: end; margin-bottom: 12px; }
.bw-tpl-row select { flex: 1; }
.bw-btn-mini { padding: 7px 12px; border: 1px solid #cbd5e1; background: #fff; border-radius: 6px; font-size: 12px; cursor: pointer; }
.bw-btn-mini:hover { background: #f1f5f9; }
.bw-btn-mini-danger { color: #b91c1c; border-color: #fecaca; }

.bw-summary { display: flex; gap: 16px; padding: 12px 16px; background: #f0f9ff; border-radius: 8px; margin-top: 12px; flex-wrap: wrap; }
.bw-summary > div { flex: 1; min-width: 130px; }
.bw-summary .bw-sum-label { font-size: 11px; color: #64748b; }
.bw-summary .bw-sum-val { font-size: 18px; font-weight: 800; color: #1a365d; }

.bw-actions { display: flex; gap: 10px; margin-top: 18px; justify-content: flex-end; flex-wrap: wrap; }
.bw-btn { padding: 10px 22px; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; }
.bw-btn-primary { background: #1a365d; color: #fff; }
.bw-btn-primary:hover { background: #2d3748; }
.bw-btn-primary:disabled { background: #94a3b8; cursor: not-allowed; }
.bw-btn-secondary { background: #fff; color: #1a365d; border: 1px solid #cbd5e1; }
.bw-btn-secondary:hover { background: #f1f5f9; }

.bw-progress-wrap { background: #e2e8f0; border-radius: 999px; height: 28px; overflow: hidden; }
.bw-progress { height: 100%; background: linear-gradient(90deg,#1a365d,#3b82f6); width: 0%; transition: width .3s; color: #fff; font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: 13px; }
.bw-log { max-height: 320px; overflow-y: auto; background: #0f172a; color: #e2e8f0; padding: 12px 14px; border-radius: 8px; font-family: monospace; font-size: 12px; margin-top: 14px; }
.bw-log .bw-ok { color: #22c55e; }
.bw-log .bw-err { color: #f87171; }

.bw-pill { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; }
.bw-pill-ok { background: #dcfce7; color: #166534; }
.bw-pill-warn { background: #fef3c7; color: #92400e; }
.bw-pill-err { background: #fee2e2; color: #991b1b; }
</style>

<div class="bw-page" id="bw-root">

    <div class="bw-header">
        <h1>تجهيز القضايا — معالج جماعي</h1>
        <span class="bw-count">الحد: 100 عقد</span>
        <div class="bw-spacer"></div>
        <a href="<?= Url::to(['batch-history']) ?>" class="bw-link"><i class="fa fa-history"></i> تاريخ الدفعات</a>
        <a href="<?= Url::to(['index']) ?>" class="bw-link"><i class="fa fa-arrow-right"></i> رجوع</a>
    </div>

    <div class="bw-stepper">
        <div class="bw-step active" data-step="1"><span class="bw-num"><span>1</span></span> الإدخال</div>
        <div class="bw-step" data-step="2"><span class="bw-num"><span>2</span></span> المراجعة والإعداد</div>
        <div class="bw-step" data-step="3"><span class="bw-num"><span>3</span></span> التنفيذ</div>
    </div>

    <!-- ─── Step 1: Input ─── -->
    <section class="bw-card" id="bw-step-1">
        <div class="bw-tabs">
            <div class="bw-tab" data-tab="paste">📋 لصق أرقام عقود</div>
            <div class="bw-tab" data-tab="excel">📊 رفع ملف Excel</div>
            <div class="bw-tab" data-tab="selection">🔎 اختيار من النظام</div>
        </div>

        <!-- Paste -->
        <div class="bw-tab-pane" data-pane="paste">
            <p style="color:#64748b;margin-bottom:8px;font-size:13px;">الصق أرقام العقود — أي فاصل (سطر، فاصلة، مسافة، إلخ).</p>
            <textarea id="bw-paste" class="bw-textarea" placeholder="مثال:&#10;1234, 1235&#10;1236&#10;1240"></textarea>
            <div style="margin-top:10px; text-align:left;">
                <button type="button" class="bw-btn bw-btn-primary" id="bw-paste-go">تحليل</button>
            </div>
        </div>

        <!-- Excel -->
        <div class="bw-tab-pane" data-pane="excel">
            <label class="bw-drop" id="bw-drop">
                <input type="file" id="bw-file" accept=".xlsx,.csv">
                <div style="font-size:34px;">📁</div>
                <div style="font-weight:600;margin:8px 0;">اسحب الملف هنا أو انقر للاختيار</div>
                <div style="font-size:12px;">الصيغ المسموحة: .xlsx ، .csv — الحد 5 ميغا</div>
            </label>
            <p style="color:#64748b;margin-top:8px;font-size:12px;">يكتشف النظام تلقائيّاً عمود رقم العقد من العناوين (id / contract_id / رقم العقد ...). إن لم يجد → يستخدم العمود الأول.</p>
        </div>

        <!-- Selection -->
        <div class="bw-tab-pane" data-pane="selection">
            <div class="bw-filters">
                <div>
                    <label>حالة العقد</label>
                    <select id="bw-f-status" multiple data-bw-multi="1" data-placeholder="— الكل —">
                        <?php foreach ($contractStatusLabels as $k => $lbl): ?>
                            <option value="<?= Html::encode($k) ?>"><?= Html::encode($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>نوع العقد</label>
                    <select id="bw-f-type" multiple data-bw-multi="1" data-placeholder="— جميع الأنواع —">
                        <?php foreach ($contractTypeLabels as $k => $lbl): ?>
                            <option value="<?= Html::encode($k) ?>"><?= Html::encode($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>الشركة</label>
                    <select id="bw-f-company" multiple data-bw-multi="1" data-placeholder="— الكل —">
                        <?php foreach ($companies as $cid => $cname): ?>
                            <option value="<?= (int)$cid ?>"><?= Html::encode($cname) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($jobTypes)): ?>
                <div>
                    <label>نوع الوظيفة</label>
                    <select id="bw-f-jobtype" multiple data-bw-multi="1" data-placeholder="— الكل —">
                        <?php foreach ($jobTypes as $jtid => $jtn): ?>
                            <option value="<?= (int)$jtid ?>"><?= Html::encode($jtn) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <?php if (!empty($jobs)): ?>
                <div>
                    <label>جهة العمل</label>
                    <select id="bw-f-job" multiple data-bw-multi="1" data-placeholder="— الكل —">
                        <?php foreach ($jobs as $jid => $jname): ?>
                            <option value="<?= (int)$jid ?>"><?= Html::encode($jname) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div><label>من تاريخ بيع</label><input type="date" id="bw-f-from"></div>
                <div><label>إلى تاريخ بيع</label><input type="date" id="bw-f-to"></div>
                <div>
                    <label>بحث سريع</label>
                    <input type="text" id="bw-f-q" placeholder="رقم عقد أو اسم">
                </div>
                <div style="display:flex;align-items:end;gap:6px;">
                    <button type="button" class="bw-btn bw-btn-primary" id="bw-f-go" style="flex:1;">بحث</button>
                    <button type="button" class="bw-btn bw-btn-secondary" id="bw-f-clear" style="padding:10px 12px;">مسح</button>
                </div>
            </div>
            <div class="bw-table-wrap">
                <table class="bw-table" id="bw-search-table">
                    <thead>
                        <tr>
                            <th style="width:36px;"><input type="checkbox" id="bw-sel-all"></th>
                            <th>#</th>
                            <th>العميل</th>
                            <th>تاريخ البيع</th>
                            <th>المتبقي</th>
                            <th>الحالة</th>
                            <th>قضية</th>
                        </tr>
                    </thead>
                    <tbody><tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:20px;">— استخدم الفلاتر ثم اضغط «بحث» —</td></tr></tbody>
                </table>
            </div>
            <div style="margin-top:10px;display:flex;justify-content:space-between;align-items:center;">
                <span id="bw-sel-count" style="color:#475569;font-size:13px;">لم يتم اختيار أي عقد</span>
                <button type="button" class="bw-btn bw-btn-primary" id="bw-sel-go">اعتماد المختار</button>
            </div>
        </div>
    </section>

    <!-- ─── Step 2: Review + Common Data ─── -->
    <section class="bw-card" id="bw-step-2" style="display:none;">
        <div class="bw-shared-grid">
            <!-- LEFT: shared form -->
            <div class="bw-shared-form">
                <h3>البيانات المشتركة</h3>

                <div class="bw-tpl-row">
                    <select id="bw-tpl-load"><option value="">-- تحميل قالب --</option></select>
                    <button type="button" class="bw-btn-mini" id="bw-tpl-save">💾 حفظ</button>
                </div>

                <div class="bw-fg">
                    <label>المحكمة *</label>
                    <select id="bw-court" required>
                        <option value="">— اختر —</option>
                        <?php foreach ($courts as $id => $name): ?>
                            <option value="<?= (int)$id ?>"><?= Html::encode($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bw-fg">
                    <label>المحامي *</label>
                    <select id="bw-lawyer" required>
                        <option value="">— اختر —</option>
                        <?php foreach ($lawyers as $id => $name): ?>
                            <option value="<?= (int)$id ?>"><?= Html::encode($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bw-row2">
                    <div class="bw-fg">
                        <label>نوع القضية</label>
                        <select id="bw-type">
                            <?php foreach ($types as $id => $name): ?>
                                <option value="<?= (int)$id ?>" <?= ($defaultType === $id) ? 'selected' : '' ?>><?= Html::encode($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="bw-fg">
                        <label>السنة</label>
                        <select id="bw-year">
                            <?php foreach ($years as $y): ?>
                                <option value="<?= (int)$y ?>"><?= (int)$y ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="bw-fg">
                    <label>نسبة المحامي %</label>
                    <input type="number" id="bw-percentage" min="0" step="0.1" value="0">
                </div>

                <div class="bw-fg">
                    <label>الموطن المختار *</label>
                    <select id="bw-address" required>
                        <option value="random">🎲 موطن عشوائي لكل قضية</option>
                        <?php foreach ($addresses as $id => $addr): ?>
                            <option value="<?= (int)$id ?>"><?= Html::encode(mb_strimwidth($addr, 0, 80, '…')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bw-fg">
                    <label>الشركة (افتراضية لكل عقد)</label>
                    <select id="bw-company">
                        <option value="">— حسب العقد —</option>
                        <?php foreach ($companies as $id => $name): ?>
                            <option value="<?= (int)$id ?>"><?= Html::encode($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bw-fg" style="background:#fff;padding:10px;border-radius:6px;border:1px dashed #cbd5e1;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" id="bw-auto-print" checked> طباعة فورية بعد الإنشاء
                    </label>
                </div>
            </div>

            <!-- RIGHT: preview table -->
            <div>
                <h3>العقود المختارة <span id="bw-preview-count" style="color:#64748b;font-weight:500;font-size:13px;"></span></h3>
                <div class="bw-table-wrap">
                    <table class="bw-table" id="bw-preview-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>العميل</th>
                                <th>المتبقي</th>
                                <th>المحامي (Override)</th>
                                <th>النوع</th>
                                <th>الشركة</th>
                                <th>الموطن</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="bw-summary">
                    <div><div class="bw-sum-label">عدد العقود</div><div class="bw-sum-val" id="bw-sum-count">0</div></div>
                    <div><div class="bw-sum-label">إجمالي المتبقي</div><div class="bw-sum-val" id="bw-sum-rem">0</div></div>
                    <div><div class="bw-sum-label">إجمالي أتعاب المحامي</div><div class="bw-sum-val" id="bw-sum-fee">0</div></div>
                </div>
            </div>
        </div>

        <div class="bw-actions">
            <button type="button" class="bw-btn bw-btn-secondary" id="bw-back-1">← رجوع</button>
            <button type="button" class="bw-btn bw-btn-primary" id="bw-go-3">بدء التنفيذ ←</button>
        </div>
    </section>

    <!-- ─── Step 3: Execute ─── -->
    <section class="bw-card" id="bw-step-3" style="display:none;">
        <h3>التنفيذ</h3>
        <div class="bw-progress-wrap"><div class="bw-progress" id="bw-progress">0%</div></div>
        <div id="bw-log" class="bw-log"></div>
        <div class="bw-actions" id="bw-final-actions" style="display:none;">
            <button type="button" class="bw-btn bw-btn-secondary" onclick="location.href='<?= Url::to(['index']) ?>'">العودة للقضايا</button>
            <button type="button" class="bw-btn bw-btn-primary" id="bw-print-go" style="display:none;">طباعة جماعية</button>
        </div>
    </section>
</div>

<script>
window.BW_BOOT = <?= Json::htmlEncode($bootstrap) ?>;
</script>
<?php
$this->registerJsFile(
    Yii::$app->request->baseUrl . '/js/judiciary-batch-create.js?v=' . (Yii::$app->params['assetVersion'] ?? time()),
    ['depends' => [\yii\web\YiiAsset::class]]
);
