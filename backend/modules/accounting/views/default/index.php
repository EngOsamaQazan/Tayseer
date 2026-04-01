<?php

use yii\helpers\Html;

$this->title = 'لوحة تحكم المحاسبة';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row" style="margin-bottom:20px;">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card" style="border-right:4px solid var(--clr-primary, #800020);">
            <div class="stat-value" style="color:var(--clr-primary, #800020);"><?= $stats['posted_count'] ?></div>
            <div class="stat-label">قيود مرحّلة</div>
            <small class="text-muted"><?= $stats['draft_count'] ?> مسودة</small>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card" style="border-right:4px solid #dc3545;">
            <div class="stat-value" style="color:#dc3545;"><?= number_format($stats['receivable_balance'], 2) ?></div>
            <div class="stat-label">الذمم المدينة</div>
            <?php if ($stats['overdue_receivables'] > 0): ?>
            <small class="text-danger"><?= number_format($stats['overdue_receivables'], 2) ?> متأخرة</small>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card" style="border-right:4px solid #ffc107;">
            <div class="stat-value" style="color:#ffc107;"><?= number_format($stats['payable_balance'], 2) ?></div>
            <div class="stat-label">الذمم الدائنة</div>
            <?php if ($stats['overdue_payables'] > 0): ?>
            <small class="text-danger"><?= number_format($stats['overdue_payables'], 2) ?> متأخرة</small>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card" style="border-right:4px solid #28a745;">
            <div class="stat-value" style="color:#28a745;"><?= $stats['accounts_count'] ?></div>
            <div class="stat-label">حسابات فعالة</div>
            <small class="text-muted"><?= $stats['fiscal_year'] ?></small>
        </div>
    </div>
</div>

<!-- Quick Links -->
<div class="row" style="margin-bottom:25px;">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-th-large"></i> الوصول السريع</h3>
            </div>
            <div class="box-body">
                <div class="row text-center">
                    <div class="col-md-2 col-sm-4 col-xs-6" style="margin-bottom:15px;">
                        <?= Html::a('<i class="fa fa-pencil-square fa-2x" style="color:var(--clr-primary, #800020);"></i><br>قيد جديد', ['/accounting/journal-entry/create'], ['style' => 'display:block; padding:15px; border-radius:8px; text-decoration:none; color:#333; transition:all .2s; border:1px solid transparent;', 'class' => 'quick-link']) ?>
                    </div>
                    <div class="col-md-2 col-sm-4 col-xs-6" style="margin-bottom:15px;">
                        <?= Html::a('<i class="fa fa-balance-scale fa-2x" style="color:#17a2b8;"></i><br>ميزان المراجعة', ['/accounting/financial-statements/trial-balance'], ['style' => 'display:block; padding:15px; border-radius:8px; text-decoration:none; color:#333;', 'class' => 'quick-link']) ?>
                    </div>
                    <div class="col-md-2 col-sm-4 col-xs-6" style="margin-bottom:15px;">
                        <?= Html::a('<i class="fa fa-file-text fa-2x" style="color:#28a745;"></i><br>قائمة الدخل', ['/accounting/financial-statements/income-statement'], ['style' => 'display:block; padding:15px; border-radius:8px; text-decoration:none; color:#333;', 'class' => 'quick-link']) ?>
                    </div>
                    <div class="col-md-2 col-sm-4 col-xs-6" style="margin-bottom:15px;">
                        <?= Html::a('<i class="fa fa-university fa-2x" style="color:#6f42c1;"></i><br>المركز المالي', ['/accounting/financial-statements/balance-sheet'], ['style' => 'display:block; padding:15px; border-radius:8px; text-decoration:none; color:#333;', 'class' => 'quick-link']) ?>
                    </div>
                    <div class="col-md-2 col-sm-4 col-xs-6" style="margin-bottom:15px;">
                        <?= Html::a('<i class="fa fa-arrow-circle-down fa-2x" style="color:#dc3545;"></i><br>الذمم المدينة', ['/accounting/accounts-receivable/index'], ['style' => 'display:block; padding:15px; border-radius:8px; text-decoration:none; color:#333;', 'class' => 'quick-link']) ?>
                    </div>
                    <div class="col-md-2 col-sm-4 col-xs-6" style="margin-bottom:15px;">
                        <?= Html::a('<i class="fa fa-pie-chart fa-2x" style="color:#ffc107;"></i><br>الموازنات', ['/accounting/budget/index'], ['style' => 'display:block; padding:15px; border-radius:8px; text-decoration:none; color:#333;', 'class' => 'quick-link']) ?>
                    </div>
                </div>
                <div style="text-align:center;margin-top:5px;padding-top:12px;border-top:1px solid #eee">
                    <?= Html::a('<i class="fa fa-book"></i> تصدير القوائم المالية الكاملة (PDF)', ['/accounting/financial-statements/export-pdf'], ['class' => 'btn btn-primary btn-lg', 'target' => '_blank', 'style' => 'min-width:320px;font-weight:700']) ?>
                    <p style="margin-top:8px;color:#718096;font-size:12px">ميزانية عمومية + قائمة دخل + تدفقات نقدية + تغيرات حقوق الملكية + إيضاحات — جاهزة للمحاسب القانوني</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Journal Entries -->
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-clock-o"></i> آخر القيود</h3>
        <div class="box-tools">
            <?= Html::a('عرض الكل <i class="fa fa-arrow-left"></i>', ['/accounting/journal-entry/index'], ['class' => 'btn btn-secondary btn-sm']) ?>
        </div>
    </div>
    <div class="box-body no-padding">
        <?php if (empty($recentEntries)): ?>
            <div class="text-center text-muted" style="padding:40px;">
                <i class="fa fa-pencil-square-o fa-3x"></i>
                <p style="margin-top:10px;">لا توجد قيود بعد. <?= Html::a('أنشئ أول قيد', ['/accounting/journal-entry/create']) ?></p>
            </div>
        <?php else: ?>
        <table class="table table-hover" style="margin-bottom:0;">
            <thead>
                <tr style="background:#f5f6f8;">
                    <th>رقم القيد</th>
                    <th>التاريخ</th>
                    <th>الوصف</th>
                    <th class="text-center">المبلغ</th>
                    <th class="text-center">الحالة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentEntries as $entry): ?>
                <tr>
                    <td style="font-family:monospace; font-weight:700;">
                        <?= Html::a($entry->entry_number, ['/accounting/journal-entry/view', 'id' => $entry->id]) ?>
                    </td>
                    <td><?= $entry->entry_date ?></td>
                    <td><?= Html::encode(mb_substr($entry->description, 0, 50)) ?></td>
                    <td class="text-left" style="font-weight:600;"><?= number_format($entry->total_debit, 2) ?></td>
                    <td class="text-center"><?= $entry->getStatusBadge() ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
