<?php
use yii\helpers\Html;
use yii\helpers\Url;
?>

<style>
.ud-table{width:100%;border-collapse:separate;border-spacing:0;font-size:13px;direction:rtl}
.ud-table thead th{background:#F8FAFC;color:#64748B;font-weight:600;padding:10px 12px;border-bottom:2px solid #E2E8F0;text-align:right;font-size:12px;white-space:nowrap}
.ud-table tbody tr{transition:background .15s}
.ud-table tbody tr:hover{background:#F1F5F9}
.ud-table tbody td{padding:10px 12px;border-bottom:1px solid #F1F5F9;vertical-align:middle}
.ud-case-link{color:#2563EB;font-weight:600;text-decoration:none;cursor:pointer;transition:color .15s}
.ud-case-link:hover{color:#1D4ED8;text-decoration:underline}
.ud-customer{color:#475569;font-weight:500}
.ud-date{color:#94A3B8;font-size:12px;direction:ltr;text-align:left}
.ud-court{font-size:11px;color:#8B5CF6;background:#F5F3FF;padding:2px 8px;border-radius:6px;display:inline-block}
.ud-note{color:#64748B;font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ud-empty{text-align:center;padding:40px 20px;color:#94A3B8}
.ud-empty i{font-size:40px;display:block;margin-bottom:12px;color:#CBD5E1}
.ud-scroll{max-height:450px;overflow-y:auto}
.ud-contract-badge{font-size:11px;color:#0D9488;background:#F0FDFA;padding:2px 8px;border-radius:6px;display:inline-block;margin-right:4px}
</style>

<div class="ud-scroll">
<?php if (empty($rows)): ?>
    <div class="ud-empty">
        <i class="fa fa-inbox"></i>
        <p>لا توجد سجلات تستخدم هذا الإجراء</p>
    </div>
<?php else: ?>
    <table class="ud-table">
        <thead>
            <tr>
                <th>#</th>
                <th>رقم القضية</th>
                <th>العميل</th>
                <th>المحكمة</th>
                <th>تاريخ الإجراء</th>
                <th>ملاحظات</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $i => $row): ?>
            <tr>
                <td style="color:#94A3B8;font-size:12px"><?= $i + 1 ?></td>
                <td>
                    <?php if (!empty($row['judiciary_id'])): ?>
                        <a href="<?= Url::to(['/judiciary/view', 'id' => $row['judiciary_id']]) ?>"
                           class="ud-case-link" target="_blank"
                           title="فتح ملف القضية">
                            <i class="fa fa-gavel"></i>
                            <?= Html::encode($row['judiciary_number'] ?: $row['judiciary_id']) ?>
                        </a>
                        <?php if (!empty($row['year'])): ?>
                            <span style="color:#94A3B8;font-size:11px"> / <?= Html::encode($row['year']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($row['contract_id'])): ?>
                            <a href="<?= Url::to(['/judiciary/update', 'id' => $row['judiciary_id']]) ?>"
                               class="ud-contract-badge" target="_blank" title="العقد رقم <?= $row['contract_id'] ?>">
                                <i class="fa fa-file-text-o"></i> عقد #<?= $row['contract_id'] ?>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color:#CBD5E1">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($row['customer_name'])): ?>
                        <span class="ud-customer"><?= Html::encode($row['customer_name']) ?></span>
                    <?php else: ?>
                        <span style="color:#CBD5E1">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($row['court_name'])): ?>
                        <span class="ud-court"><?= Html::encode($row['court_name']) ?></span>
                    <?php else: ?>
                        <span style="color:#CBD5E1">—</span>
                    <?php endif; ?>
                </td>
                <td class="ud-date"><?= Html::encode($row['action_date'] ?: '—') ?></td>
                <td>
                    <?php if (!empty($row['note'])): ?>
                        <span class="ud-note" title="<?= Html::encode($row['note']) ?>"><?= Html::encode($row['note']) ?></span>
                    <?php else: ?>
                        <span style="color:#CBD5E1">—</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>
