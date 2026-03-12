<?php
/**
 * مودال اختيار القضية — كل قضية رابط يفتح فورم الإجراء المحدث مباشرة
 */
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $judiciaries array */
?>

<style>
.sjm { direction:rtl;font-family:var(--ocp-font-family,'Tajawal',sans-serif);font-size:13px; }
.sjm-search {
    width:100%;padding:10px 14px 10px 36px;border:1px solid #D1D5DB;border-radius:10px;
    font-size:13px;outline:none;margin-bottom:12px;background:#fff;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394A3B8' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:10px center;
}
.sjm-search:focus { border-color:#3B82F6;box-shadow:0 0 0 3px rgba(59,130,246,.1); }
.sjm-list { max-height:400px;overflow-y:auto;border:1px solid #E2E8F0;border-radius:10px; }
.sjm-item {
    display:flex;align-items:center;gap:12px;padding:10px 14px;
    border-bottom:1px solid #F1F5F9;cursor:pointer;transition:all .15s;
    text-decoration:none;color:inherit;
}
.sjm-item:last-child { border-bottom:none; }
.sjm-item:hover { background:#F0F9FF;text-decoration:none;color:inherit; }
.sjm-item-icon {
    width:36px;height:36px;border-radius:8px;background:#DBEAFE;
    display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.sjm-item-number { font-weight:700;color:#1E293B;font-family:'Courier New',monospace; }
.sjm-item-court { font-size:11px;color:#64748B; }
.sjm-empty { padding:30px;text-align:center;color:#94A3B8;font-size:13px; }
</style>

<div class="sjm" x-data="{ searchQuery: '' }">
    <p style="color:#64748B;margin-bottom:10px"><i class="fa fa-info-circle"></i> اضغط على القضية لفتح نموذج إضافة الإجراء</p>

    <input type="text" class="sjm-search" id="sjm-search" placeholder="ابحث برقم القضية أو اسم المحكمة..." x-model="searchQuery">

    <div class="sjm-list" id="sjm-list">
        <?php if (empty($judiciaries)): ?>
            <div class="sjm-empty"><i class="fa fa-inbox"></i> لا توجد قضايا مسجلة</div>
        <?php else: ?>
            <?php foreach ($judiciaries as $j): ?>
            <a class="sjm-item"
               href="<?= Url::to(['create-followup-judicary-custamer-action', 'contractID' => $j['contract_id']]) ?>"
               role="modal-remote"
               title="إضافة إجراء للقضية <?= Html::encode($j['judiciary_number'] . '/' . $j['year']) ?>"
               data-search="<?= Html::encode($j['judiciary_number'] . ' ' . $j['year'] . ' ' . ($j['court_name'] ?: '')) ?>"
               x-show="searchQuery === '' || '<?= Html::encode(strtolower($j['judiciary_number'] . ' ' . $j['year'] . ' ' . ($j['court_name'] ?: ''))) ?>'.includes(searchQuery.toLowerCase())"
               x-transition>
                <div class="sjm-item-icon"><i class="fa fa-gavel" style="color:#2563EB;font-size:15px"></i></div>
                <div style="flex:1;min-width:0">
                    <div class="sjm-item-number"><?= Html::encode($j['judiciary_number']) ?>/<?= Html::encode($j['year']) ?></div>
                    <div class="sjm-item-court"><?= Html::encode($j['court_name'] ?: '—') ?></div>
                </div>
                <i class="fa fa-chevron-left" style="color:#CBD5E1;font-size:11px"></i>
            </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Alpine.js handles filtering via x-show + x-model on searchQuery -->
