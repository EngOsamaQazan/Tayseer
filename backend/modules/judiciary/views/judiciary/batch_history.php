<?php
/**
 * Batch history & revert screen.
 *
 * Lists every batch the current user can see (own batches for plain users;
 * all batches for managers with JUD_DELETE), with a revert action gated
 * client-side to the 72-hour window.
 *
 * @var \yii\web\View $this
 * @var \backend\modules\judiciary\models\JudiciaryBatch[] $batches
 * @var bool $isManager
 * @var int $userId
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;
use backend\modules\judiciary\models\JudiciaryBatch;
use common\models\User;

$this->title = 'تاريخ الدفعات الجماعية';
$this->params['breadcrumbs'][] = ['label' => 'القضاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'المعالج الجماعي', 'url' => ['batch-create']];
$this->params['breadcrumbs'][] = $this->title;

$REVERT_WINDOW = JudiciaryBatch::REVERT_WINDOW_SECONDS;

$endpoints = [
    'revert'        => Url::to(['batch-revert']),
    'printRedirect' => Url::to(['batch-print-redirect']),
];

$creatorIds = array_unique(array_map(fn($b) => (int)$b->created_by, $batches));
$creatorNames = [];
if (!empty($creatorIds)) {
    $rows = User::find()->select(['id', 'username'])->where(['id' => $creatorIds])->asArray()->all();
    foreach ($rows as $r) $creatorNames[(int)$r['id']] = $r['username'];
}

$statusLabels = [
    JudiciaryBatch::STATUS_RUNNING   => ['قيد التشغيل', '#fef3c7', '#92400e'],
    JudiciaryBatch::STATUS_COMPLETED => ['مكتملة', '#dcfce7', '#166534'],
    JudiciaryBatch::STATUS_PARTIAL   => ['جزئية', '#fef9c3', '#854d0e'],
    JudiciaryBatch::STATUS_REVERTED  => ['متراجَع عنها', '#e2e8f0', '#475569'],
];
$entryLabels = [
    JudiciaryBatch::ENTRY_PASTE     => 'لصق',
    JudiciaryBatch::ENTRY_EXCEL     => 'Excel',
    JudiciaryBatch::ENTRY_SELECTION => 'اختيار',
];
?>

<style>
.bh-page { max-width: 1400px; margin: 0 auto; padding: 0 16px 40px; }
.bh-header { display: flex; align-items: center; gap: 14px; margin-bottom: 18px; padding: 16px 22px; background: linear-gradient(135deg,#1a365d,#2d3748); border-radius: 12px; color: #fff; }
.bh-header h1 { font-size: 20px; font-weight: 700; margin: 0; }
.bh-header .bh-spacer { flex:1; }
.bh-header a { background: rgba(255,255,255,.15); color: #fff; text-decoration: none; padding: 8px 14px; border-radius: 8px; font-weight: 600; font-size: 13px; }

.bh-card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06); padding: 6px; }
.bh-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.bh-table th, .bh-table td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: right; vertical-align: middle; }
.bh-table thead th { background: #f8fafc; font-weight: 700; color: #334155; }
.bh-table tbody tr:hover { background: #f8fafc; }
.bh-pill { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
.bh-bar { display: inline-block; width: 100px; height: 8px; background: #e2e8f0; border-radius: 4px; vertical-align: middle; overflow: hidden; }
.bh-bar > span { display: block; height: 100%; background: linear-gradient(90deg,#1a365d,#3b82f6); }
.bh-actions button, .bh-actions a { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid #cbd5e1; background: #fff; color: #1a365d; text-decoration: none; }
.bh-actions button:hover { background: #f1f5f9; }
.bh-actions .bh-revert { color: #b91c1c; border-color: #fecaca; background: #fff; }
.bh-actions .bh-revert:hover { background: #fee2e2; }
.bh-actions .bh-revert:disabled { color: #94a3b8; border-color: #e2e8f0; cursor: not-allowed; background: #f8fafc; }
.bh-empty { text-align: center; padding: 36px; color: #94a3b8; }
</style>

<div class="bh-page">
    <div class="bh-header">
        <h1>تاريخ الدفعات الجماعية</h1>
        <span style="opacity:.85;font-size:12px;"><?= $isManager ? 'كافة الدفعات (مدير)' : 'دفعاتي فقط' ?></span>
        <div class="bh-spacer"></div>
        <a href="<?= Url::to(['batch-create']) ?>"><i class="fa fa-plus"></i> دفعة جديدة</a>
        <a href="<?= Url::to(['index']) ?>" style="margin-right:6px;"><i class="fa fa-arrow-right"></i> العودة</a>
    </div>

    <div class="bh-card">
        <table class="bh-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>التاريخ</th>
                    <th>المنشئ</th>
                    <th>الإدخال</th>
                    <th>العقود</th>
                    <th>النجاح</th>
                    <th>الفشل</th>
                    <th>الحالة</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($batches)): ?>
                    <tr><td colspan="9" class="bh-empty">لا توجد دفعات بعد</td></tr>
                <?php else: foreach ($batches as $b):
                    $age = time() - (int)$b->created_at;
                    $withinWindow = $age <= $REVERT_WINDOW;
                    $isOwner = ((int)$b->created_by === $userId);
                    $canRevertBtn = ($b->status !== JudiciaryBatch::STATUS_REVERTED) && $withinWindow && ($isOwner || $isManager);
                    [$lbl, $bg, $fg] = $statusLabels[$b->status] ?? [$b->status, '#e2e8f0', '#475569'];
                    $pct = $b->contract_count > 0 ? (int)round(($b->success_count / $b->contract_count) * 100) : 0;
                ?>
                    <tr>
                        <td><strong>#<?= (int)$b->id ?></strong></td>
                        <td><?= Yii::$app->formatter->asDatetime($b->created_at) ?></td>
                        <td><?= Html::encode($creatorNames[(int)$b->created_by] ?? '#' . $b->created_by) ?></td>
                        <td><?= Html::encode($entryLabels[$b->entry_method] ?? $b->entry_method) ?></td>
                        <td><?= (int)$b->contract_count ?></td>
                        <td>
                            <span class="bh-bar"><span style="width:<?= $pct ?>%"></span></span>
                            <span style="margin-right:6px;"><?= (int)$b->success_count ?> (<?= $pct ?>%)</span>
                        </td>
                        <td><?= (int)$b->failed_count ?></td>
                        <td><span class="bh-pill" style="background:<?= $bg ?>;color:<?= $fg ?>;"><?= $lbl ?></span></td>
                        <td class="bh-actions">
                            <?php if ($b->success_count > 0): ?>
                                <a href="<?= Url::to(['batch-print-redirect', 'batch_id' => $b->id]) ?>" target="_blank">طباعة</a>
                            <?php endif; ?>
                            <?php if ($canRevertBtn): ?>
                                <button type="button" class="bh-revert" data-id="<?= (int)$b->id ?>" data-count="<?= (int)$b->success_count ?>">تراجع</button>
                            <?php elseif ($b->status === JudiciaryBatch::STATUS_REVERTED): ?>
                                <span style="color:#94a3b8;font-size:11px;">تم التراجع</span>
                            <?php elseif (!$withinWindow): ?>
                                <span style="color:#94a3b8;font-size:11px;" title="تجاوزت 72 ساعة">انتهت النافذة</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function(){
    var EP = <?= Json::htmlEncode($endpoints) ?>;
    var CSRF = { param: '<?= Yii::$app->request->csrfParam ?>', token: '<?= Yii::$app->request->csrfToken ?>' };

    document.querySelectorAll('.bh-revert').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = btn.dataset.id;
            var n = btn.dataset.count;
            var reason = prompt('سبب التراجع عن الدفعة #' + id + ' (' + n + ' قضية):', '');
            if (reason === null) return;

            btn.disabled = true;
            var fd = new FormData();
            fd.append('batch_id', id);
            fd.append('reason', reason);
            fd.append(CSRF.param, CSRF.token);
            fetch(EP.revert, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(resp){
                    if (!resp.ok) { alert(resp.message || 'فشل التراجع'); btn.disabled = false; return; }
                    var msg = 'تم التراجع: ' + resp.success + ' قضية.';
                    if (resp.locked > 0) msg += '\n' + resp.locked + ' قضية محجوبة (لها إجراءات لاحقة).';
                    alert(msg);
                    location.reload();
                })
                .catch(function(e){ alert('خطأ: ' + e.message); btn.disabled = false; });
        });
    });
})();
</script>
