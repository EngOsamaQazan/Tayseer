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
    'resume'        => Url::to(['batch-resume']),
    'details'       => Url::to(['batch-details']),
    'execute'       => Url::to(['batch-execute-chunk']),
    'finalize'      => Url::to(['batch-finalize']),
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
.bh-actions .bh-resume { color: #166534; border-color: #bbf7d0; background: #f0fdf4; }
.bh-actions .bh-resume:hover { background: #dcfce7; }
.bh-actions .bh-details { color: #1d4ed8; border-color: #bfdbfe; background: #eff6ff; }
.bh-actions .bh-details:hover { background: #dbeafe; }
.bh-empty { text-align: center; padding: 36px; color: #94a3b8; }

/* Details modal */
.bh-modal-bg { position: fixed; inset: 0; background: rgba(15,23,42,.55); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 24px; }
.bh-modal-bg.bh-show { display: flex; }
.bh-modal { background: #fff; border-radius: 14px; max-width: 1000px; width: 100%; max-height: calc(100vh - 48px); display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0,0,0,.3); overflow: hidden; }
.bh-modal-head { padding: 16px 22px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 14px; background: linear-gradient(135deg,#1a365d,#2d3748); color: #fff; }
.bh-modal-head h2 { margin: 0; font-size: 16px; font-weight: 700; }
.bh-modal-head .bh-close { margin-inline-start: auto; background: rgba(255,255,255,.15); border: 0; color: #fff; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: 700; }
.bh-modal-body { padding: 16px 22px; overflow: auto; }
.bh-summary { display: flex; gap: 18px; flex-wrap: wrap; padding: 12px 14px; background: #f8fafc; border-radius: 8px; margin-bottom: 14px; font-size: 13px; }
.bh-summary div b { color: #1a365d; }
.bh-items { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.bh-items th, .bh-items td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; text-align: right; vertical-align: middle; }
.bh-items thead th { background: #f8fafc; color: #334155; font-weight: 700; position: sticky; top: 0; }
.bh-items .bh-item-ok { color: #166534; font-weight: 700; }
.bh-items .bh-item-fail { color: #991b1b; font-weight: 700; }
.bh-items .bh-item-pend { color: #92400e; font-weight: 700; }
.bh-items a { color: #1d4ed8; text-decoration: none; }
.bh-items a:hover { text-decoration: underline; }
.bh-progress { margin: 12px 0; height: 22px; background: #f1f5f9; border-radius: 6px; overflow: hidden; position: relative; }
.bh-progress > span { display: block; height: 100%; background: linear-gradient(90deg,#1a365d,#3b82f6); transition: width .3s; }
.bh-progress > b { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 12px; font-weight: 700; mix-blend-mode: difference; }
.bh-resume-log { font-family: monospace; font-size: 12px; max-height: 180px; overflow: auto; background: #0f172a; color: #e2e8f0; border-radius: 6px; padding: 10px; margin-top: 10px; }
.bh-resume-log .ok { color: #4ade80; }
.bh-resume-log .err { color: #fca5a5; }
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
                            <button type="button" class="bh-details" data-id="<?= (int)$b->id ?>">تفاصيل</button>
                            <?php if ($b->status === JudiciaryBatch::STATUS_PARTIAL && ($isOwner || $isManager)): ?>
                                <button type="button" class="bh-resume" data-id="<?= (int)$b->id ?>" data-count="<?= (int)$b->failed_count ?>">استكمال</button>
                            <?php endif; ?>
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

<!-- Details / Resume modal -->
<div class="bh-modal-bg" id="bh-modal" role="dialog" aria-modal="true">
    <div class="bh-modal">
        <div class="bh-modal-head">
            <h2 id="bh-modal-title">تفاصيل الدفعة</h2>
            <button type="button" class="bh-close" id="bh-modal-close">إغلاق</button>
        </div>
        <div class="bh-modal-body" id="bh-modal-body">
            <div style="text-align:center;color:#94a3b8;padding:30px;">جارِ التحميل...</div>
        </div>
    </div>
</div>

<script>
(function(){
    var EP = <?= Json::htmlEncode($endpoints) ?>;
    var CSRF = { param: '<?= Yii::$app->request->csrfParam ?>', token: '<?= Yii::$app->request->csrfToken ?>' };
    var CHUNK = 10;

    var STATUS_BADGE = {
        success: ['نجاح', 'bh-item-ok'],
        failed:  ['فشل', 'bh-item-fail'],
        pending: ['معلّق', 'bh-item-pend']
    };

    function postJson(url, data) {
        var fd = new FormData();
        Object.keys(data).forEach(function(k){
            if (Array.isArray(data[k])) data[k].forEach(function(v){ fd.append(k+'[]', v); });
            else fd.append(k, data[k]);
        });
        fd.append(CSRF.param, CSRF.token);
        return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function(r){ return r.json(); });
    }

    var modal     = document.getElementById('bh-modal');
    var modalBody = document.getElementById('bh-modal-body');
    var modalTtl  = document.getElementById('bh-modal-title');
    function openModal(title) { modalTtl.textContent = title || 'تفاصيل الدفعة'; modal.classList.add('bh-show'); }
    function closeModal() { modal.classList.remove('bh-show'); }
    document.getElementById('bh-modal-close').addEventListener('click', closeModal);
    modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });

    function escHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]; }); }
    function fmtMoney(n) { try { return Number(n).toLocaleString('ar-EG', {minimumFractionDigits: 2, maximumFractionDigits: 2}); } catch(e){ return n; } }

    function renderDetails(data) {
        var b = data.batch;
        var pct = b.contract_count > 0 ? Math.round((b.success_count / b.contract_count) * 100) : 0;
        var rows = data.items.map(function(it){
            var sb = STATUS_BADGE[it.status] || [it.status, ''];
            var jud = it.judiciary_id
                ? '<a href="<?= Url::to(['view']) ?>?id=' + it.judiciary_id + '" target="_blank">#' + it.judiciary_id + '</a>'
                : '<span style="color:#94a3b8;">—</span>';
            var contract = '<a href="<?= Url::to(['/contracts/contracts/view']) ?>?id=' + it.contract_id + '" target="_blank">#' + it.contract_id + '</a>';
            var clients = escHtml(it.client_names || '—');
            var error = it.error ? '<span style="color:#991b1b;font-size:11px;">' + escHtml(it.error) + '</span>' : '';
            return '<tr>'
                + '<td>' + contract + '</td>'
                + '<td>' + clients + '</td>'
                + '<td style="text-align:left;">' + fmtMoney(it.remaining || 0) + '</td>'
                + '<td>' + jud + '</td>'
                + '<td><span class="' + sb[1] + '">' + sb[0] + '</span> ' + error + '</td>'
                + '</tr>';
        }).join('');

        modalBody.innerHTML =
            '<div class="bh-summary">'
              + '<div><b>الدفعة:</b> #' + b.id + '</div>'
              + '<div><b>إجمالي العقود:</b> ' + b.contract_count + '</div>'
              + '<div><b>الناجحة:</b> ' + b.success_count + ' (' + pct + '%)</div>'
              + '<div><b>الفاشلة:</b> ' + b.failed_count + '</div>'
              + '<div><b>الحالة:</b> ' + escHtml(b.status) + '</div>'
            + '</div>'
            + '<table class="bh-items">'
              + '<thead><tr><th>العقد</th><th>العملاء</th><th>الرصيد المتبقي</th><th>القضية</th><th>الحالة</th></tr></thead>'
              + '<tbody>' + (rows || '<tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:18px;">لا توجد بنود</td></tr>') + '</tbody>'
            + '</table>';
    }

    /* ───── Details ───── */
    document.querySelectorAll('.bh-details').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = btn.dataset.id;
            openModal('تفاصيل الدفعة #' + id);
            modalBody.innerHTML = '<div style="text-align:center;color:#94a3b8;padding:30px;">جارِ التحميل...</div>';
            fetch(EP.details + '?batch_id=' + encodeURIComponent(id), { credentials:'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(resp){
                    if (!resp.ok) { modalBody.innerHTML = '<div style="color:#991b1b;padding:20px;">' + escHtml(resp.message || 'فشل تحميل التفاصيل') + '</div>'; return; }
                    renderDetails(resp);
                })
                .catch(function(e){ modalBody.innerHTML = '<div style="color:#991b1b;padding:20px;">خطأ شبكة: ' + escHtml(e.message) + '</div>'; });
        });
    });

    /* ───── Resume ───── */
    document.querySelectorAll('.bh-resume').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = parseInt(btn.dataset.id, 10);
            var n = parseInt(btn.dataset.count, 10) || 0;
            if (!window.confirm('سيتم إعادة تشغيل ' + n + ' عقد فاشلة في الدفعة #' + id + '. متابعة؟')) return;

            openModal('استكمال الدفعة #' + id);
            modalBody.innerHTML =
                '<div class="bh-progress"><span style="width:0%"></span><b>0%</b></div>'
                + '<div class="bh-resume-log" id="bh-resume-log"></div>';

            var bar = modalBody.querySelector('.bh-progress > span');
            var lbl = modalBody.querySelector('.bh-progress > b');
            var logBox = document.getElementById('bh-resume-log');
            function rlog(msg, cls) {
                var line = document.createElement('div');
                if (cls) line.className = cls;
                line.textContent = msg;
                logBox.appendChild(line);
                logBox.scrollTop = logBox.scrollHeight;
            }

            postJson(EP.resume, { batch_id: id }).then(function(plan){
                if (!plan.ok) { rlog(plan.message || 'فشل بدء الاستكمال', 'err'); return; }
                rlog('▶ بدأ الاستكمال — ' + plan.total + ' عقد، ' + plan.chunks + ' دفعات.', 'ok');

                var done = 0, total = plan.chunks || 1;
                function setPct() {
                    var pct = Math.round((done / total) * 100);
                    bar.style.width = pct + '%'; lbl.textContent = pct + '%';
                }
                function runChunk() {
                    if (done >= total) { finish(); return; }
                    postJson(EP.execute, { batch_id: id, chunk_index: done }).then(function(resp){
                        if (!resp.ok) { rlog('✗ دفعة #' + done + ': ' + (resp.message || 'فشل'), 'err'); }
                        else (resp.details || []).forEach(function(d){
                            if (d.status === 'success') rlog('✓ عقد ' + d.contract_id + ' → قضية #' + d.judiciary_id, 'ok');
                            else rlog('✗ عقد ' + d.contract_id + ' — ' + (d.message || 'فشل'), 'err');
                        });
                        done++; setPct(); runChunk();
                    }).catch(function(e){ rlog('خطأ شبكة: ' + e.message, 'err'); done++; setPct(); runChunk(); });
                }
                function finish() {
                    postJson(EP.finalize, { batch_id: id }).then(function(resp){
                        if (resp.ok) {
                            rlog('— انتهى الاستكمال. نجاح: ' + resp.success + '، فشل: ' + resp.failed, resp.failed === 0 ? 'ok' : 'err');
                            setTimeout(function(){ location.reload(); }, 1500);
                        } else { rlog(resp.message || 'فشل إنهاء الدفعة', 'err'); }
                    });
                }
                runChunk();
            }).catch(function(e){ rlog('خطأ شبكة: ' + e.message, 'err'); });
        });
    });

    /* ───── Revert (existing) ───── */
    document.querySelectorAll('.bh-revert').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = btn.dataset.id;
            var n = btn.dataset.count;
            var reason = prompt('سبب التراجع عن الدفعة #' + id + ' (' + n + ' قضية):', '');
            if (reason === null) return;

            btn.disabled = true;
            postJson(EP.revert, { batch_id: id, reason: reason })
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
