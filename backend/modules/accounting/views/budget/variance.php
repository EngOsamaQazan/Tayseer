<?php

use yii\helpers\Html;

$this->title = 'تقرير انحراف الموازنة: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = ['label' => 'الموازنات', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'تقرير الانحراف';

$months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
?>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-bar-chart"></i> تقرير الانحراف عن الموازنة</h3>
        <div class="box-tools">
            <?= Html::a('<i class="fa fa-arrow-left"></i> العودة للموازنة', ['view', 'id' => $model->id], ['class' => 'btn btn-secondary btn-sm']) ?>
        </div>
    </div>
    <div class="box-body" style="overflow-x:auto;">
        <?php if (empty($lines)): ?>
            <div class="text-center text-muted" style="padding:40px;">
                <p>لا توجد بنود في الموازنة.</p>
            </div>
        <?php else: ?>
        <table class="table table-bordered table-condensed" style="min-width:1400px; font-size:12px;">
            <thead>
                <tr style="background:#f5f6f8;">
                    <th rowspan="2" style="vertical-align:middle;">الحساب</th>
                    <?php for ($m = 0; $m < 12; $m++): ?>
                    <th colspan="3" class="text-center" style="font-size:11px; border-bottom:0;"><?= $months[$m] ?></th>
                    <?php endfor; ?>
                    <th colspan="3" class="text-center" style="font-weight:800; border-bottom:0;">السنوي</th>
                </tr>
                <tr style="background:#f5f6f8; font-size:10px;">
                    <?php for ($m = 0; $m < 13; $m++): ?>
                    <th class="text-center" style="color:#17a2b8;">مخطط</th>
                    <th class="text-center" style="color:#28a745;">فعلي</th>
                    <th class="text-center" style="color:#dc3545;">انحراف</th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $totals = ['budget' => array_fill(1, 12, 0), 'actual' => array_fill(1, 12, 0)];
                $annualBudgetTotal = 0;
                $annualActualTotal = 0;
                foreach ($lines as $line):
                    $annualActual = 0;
                ?>
                <tr>
                    <td style="font-weight:600; white-space:nowrap;"><?= $line->account ? $line->account->code . ' ' . Html::encode($line->account->name_ar) : '' ?></td>
                    <?php for ($m = 1; $m <= 12; $m++):
                        $budgeted = (float)$line->{"period_{$m}"};
                        $actual = (float)($monthlyActuals[$line->id][$m] ?? 0);
                        $var = $budgeted - $actual;
                        $annualActual += $actual;
                        $totals['budget'][$m] += $budgeted;
                        $totals['actual'][$m] += $actual;
                    ?>
                    <td class="text-left" style="color:#17a2b8;"><?= $budgeted > 0 ? number_format($budgeted, 0) : '' ?></td>
                    <td class="text-left" style="color:#28a745;"><?= $actual != 0 ? number_format($actual, 0) : '' ?></td>
                    <td class="text-left" style="color:<?= $var >= 0 ? '#28a745' : '#dc3545' ?>; font-weight:700;">
                        <?= $var != 0 ? ($var > 0 ? '+' : '') . number_format($var, 0) : '' ?>
                    </td>
                    <?php endfor; ?>
                    <?php
                    $annualVar = $line->annual_total - $annualActual;
                    $annualBudgetTotal += $line->annual_total;
                    $annualActualTotal += $annualActual;
                    ?>
                    <td class="text-left" style="font-weight:700; color:#17a2b8;"><?= number_format($line->annual_total, 0) ?></td>
                    <td class="text-left" style="font-weight:700; color:#28a745;"><?= number_format($annualActual, 0) ?></td>
                    <td class="text-left" style="font-weight:800; color:<?= $annualVar >= 0 ? '#28a745' : '#dc3545' ?>;">
                        <?= ($annualVar > 0 ? '+' : '') . number_format($annualVar, 0) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f5f6f8; font-weight:800;">
                    <td>الإجمالي</td>
                    <?php for ($m = 1; $m <= 12; $m++):
                        $tVar = $totals['budget'][$m] - $totals['actual'][$m];
                    ?>
                    <td class="text-left" style="color:#17a2b8;"><?= number_format($totals['budget'][$m], 0) ?></td>
                    <td class="text-left" style="color:#28a745;"><?= number_format($totals['actual'][$m], 0) ?></td>
                    <td class="text-left" style="color:<?= $tVar >= 0 ? '#28a745' : '#dc3545' ?>;">
                        <?= $tVar != 0 ? ($tVar > 0 ? '+' : '') . number_format($tVar, 0) : '' ?>
                    </td>
                    <?php endfor; ?>
                    <?php $totalVar = $annualBudgetTotal - $annualActualTotal; ?>
                    <td class="text-left" style="color:#17a2b8;"><?= number_format($annualBudgetTotal, 0) ?></td>
                    <td class="text-left" style="color:#28a745;"><?= number_format($annualActualTotal, 0) ?></td>
                    <td class="text-left" style="color:<?= $totalVar >= 0 ? '#28a745' : '#dc3545' ?>; font-size:14px;">
                        <?= ($totalVar > 0 ? '+' : '') . number_format($totalVar, 0) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>
</div>

<div class="box box-info" style="margin-top:10px;">
    <div class="box-body">
        <div class="row text-center">
            <div class="col-md-4">
                <h4 style="color:#17a2b8;">الموازنة المخططة</h4>
                <h2 style="font-weight:800; color:#17a2b8;"><?= number_format($annualBudgetTotal ?? 0, 2) ?></h2>
            </div>
            <div class="col-md-4">
                <h4 style="color:#28a745;">المصروف الفعلي</h4>
                <h2 style="font-weight:800; color:#28a745;"><?= number_format($annualActualTotal ?? 0, 2) ?></h2>
            </div>
            <div class="col-md-4">
                <?php $pct = ($annualBudgetTotal ?? 0) > 0 ? round(($annualActualTotal ?? 0) / ($annualBudgetTotal ?? 1) * 100, 1) : 0; ?>
                <h4>نسبة الاستهلاك</h4>
                <h2 style="font-weight:800; color:<?= $pct > 100 ? '#dc3545' : ($pct > 80 ? '#ffc107' : '#28a745') ?>;"><?= $pct ?>%</h2>
                <div style="background:#eee; border-radius:4px; height:10px; overflow:hidden; margin-top:5px;">
                    <div style="background:<?= $pct > 100 ? '#dc3545' : ($pct > 80 ? '#ffc107' : '#28a745') ?>; width:<?= min($pct, 100) ?>%; height:100%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
