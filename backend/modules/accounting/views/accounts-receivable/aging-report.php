<?php

use yii\helpers\Html;

$this->title = 'تقرير أعمار الذمم المدينة';
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = ['label' => 'الذمم المدينة', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$totalAging = array_sum($aging);
$agingColors = ['جاري' => '#28a745', '1-30 يوم' => '#ffc107', '31-60 يوم' => '#fd7e14', '61-90 يوم' => '#dc3545', '+90 يوم' => '#6c1b2a'];
?>

<div class="row" style="margin-bottom:20px;">
    <?php foreach ($aging as $bucket => $amount): ?>
    <div class="col-md col-sm-6" style="flex:1; min-width:150px; padding:0 8px; margin-bottom:10px;">
        <div class="stat-card" style="border-right:4px solid <?= $agingColors[$bucket] ?>;">
            <div class="stat-value" style="color:<?= $agingColors[$bucket] ?>; font-size:22px;"><?= number_format($amount, 2) ?></div>
            <div class="stat-label"><?= $bucket ?></div>
            <?php if ($totalAging > 0): ?>
            <div style="margin-top:5px;">
                <div style="background:#eee; border-radius:4px; height:6px; overflow:hidden;">
                    <div style="background:<?= $agingColors[$bucket] ?>; width:<?= round($amount / $totalAging * 100) ?>%; height:100%;"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-clock-o"></i> <?= $this->title ?></h3>
    </div>
    <div class="box-body">
        <?php if (empty($receivables)): ?>
            <div class="text-center text-muted" style="padding:40px;">
                <i class="fa fa-check-circle fa-3x text-success"></i>
                <p style="margin-top:15px;">لا توجد ذمم مدينة مستحقة.</p>
            </div>
        <?php else: ?>
        <table class="table table-bordered table-condensed table-striped table-hover">
            <thead>
                <tr style="background:#f5f6f8;">
                    <th class="text-center">#</th>
                    <th>العميل</th>
                    <th class="text-center">الاستحقاق</th>
                    <th class="text-center">المبلغ</th>
                    <th class="text-center">المدفوع</th>
                    <th class="text-center">المتبقي</th>
                    <th class="text-center">أيام التأخير</th>
                    <th class="text-center">الفئة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($receivables as $i => $r):
                    $days = $r->getAgingDays();
                    $bucket = $r->getAgingBucket();
                    $color = $agingColors[$bucket] ?? '#666';
                ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td><?= $r->customer ? Html::encode($r->customer->first_name . ' ' . ($r->customer->last_name ?? '')) : 'غير محدد' ?></td>
                    <td class="text-center"><?= $r->due_date ?: '—' ?></td>
                    <td class="text-left" style="font-weight:600;"><?= number_format($r->amount, 2) ?></td>
                    <td class="text-left" style="color:#28a745;"><?= number_format($r->paid_amount, 2) ?></td>
                    <td class="text-left" style="font-weight:700; color:<?= $color ?>;"><?= number_format($r->balance, 2) ?></td>
                    <td class="text-center" style="font-weight:700; color:<?= $color ?>;"><?= $days > 0 ? $days . ' يوم' : '—' ?></td>
                    <td class="text-center"><span style="color:<?= $color ?>; font-weight:700;"><?= $bucket ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f5f6f8; font-weight:800;">
                    <td colspan="3" class="text-left">الإجمالي</td>
                    <td class="text-left"><?= number_format(array_sum(array_column($receivables, 'amount')), 2) ?></td>
                    <td class="text-left" style="color:#28a745;"><?= number_format(array_sum(array_column($receivables, 'paid_amount')), 2) ?></td>
                    <td class="text-left" style="color:#dc3545;"><?= number_format(array_sum(array_column($receivables, 'balance')), 2) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>
</div>
