<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

$this->title = 'الميزانية العمومية (المركز المالي)';
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = $this->title;

$fyOptions = ArrayHelper::map($fiscalYears, 'id', 'name');
$totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity + $netIncome;
?>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-university"></i> <?= $this->title ?></h3>
        <div class="box-tools">
            <?= Html::a('<i class="fa fa-balance-scale"></i> ميزان المراجعة', ['trial-balance', 'fiscal_year_id' => $fiscalYearId], ['class' => 'btn btn-default btn-sm']) ?>
            <?= Html::a('<i class="fa fa-file-text"></i> قائمة الدخل', ['income-statement', 'fiscal_year_id' => $fiscalYearId], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>
    <div class="box-body">
        <form method="get" class="form-inline" style="margin-bottom:20px;">
            <div class="form-group" style="margin-left:10px;">
                <label>السنة المالية:</label>
                <select name="fiscal_year_id" class="form-control">
                    <option value="">الكل</option>
                    <?php foreach ($fyOptions as $fId => $fName): ?>
                    <option value="<?= $fId ?>" <?= $fiscalYearId == $fId ? 'selected' : '' ?>><?= Html::encode($fName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-left:10px;">
                <label>حتى تاريخ:</label>
                <input type="date" name="date_to" class="form-control" value="<?= Html::encode($dateTo) ?>">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> عرض</button>
        </form>

        <div class="row">
            <!-- Assets -->
            <div class="col-md-6">
                <div class="box box-info">
                    <div class="box-header"><h4 class="box-title"><i class="fa fa-briefcase"></i> الأصول</h4></div>
                    <div class="box-body no-padding">
                        <table class="table table-condensed">
                            <tbody>
                                <?php foreach ($assets as $data): ?>
                                <?php if ($data['balance'] == 0) continue; ?>
                                <tr>
                                    <td style="font-family:monospace; width:70px;"><?= $data['account']->code ?></td>
                                    <td><?= Html::encode($data['account']->name_ar) ?></td>
                                    <td class="text-left" style="font-weight:600;"><?= number_format($data['balance'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background:#d4edda; font-weight:800; font-size:15px;">
                                    <td colspan="2">إجمالي الأصول</td>
                                    <td class="text-left"><?= number_format($totalAssets, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Liabilities + Equity -->
            <div class="col-md-6">
                <div class="box box-warning">
                    <div class="box-header"><h4 class="box-title"><i class="fa fa-credit-card"></i> الخصوم</h4></div>
                    <div class="box-body no-padding">
                        <table class="table table-condensed">
                            <tbody>
                                <?php foreach ($liabilities as $data): ?>
                                <?php if ($data['balance'] == 0) continue; ?>
                                <tr>
                                    <td style="font-family:monospace; width:70px;"><?= $data['account']->code ?></td>
                                    <td><?= Html::encode($data['account']->name_ar) ?></td>
                                    <td class="text-left" style="font-weight:600;"><?= number_format($data['balance'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background:#fff3cd; font-weight:700;">
                                    <td colspan="2">إجمالي الخصوم</td>
                                    <td class="text-left"><?= number_format($totalLiabilities, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="box box-primary">
                    <div class="box-header"><h4 class="box-title"><i class="fa fa-bank"></i> حقوق الملكية</h4></div>
                    <div class="box-body no-padding">
                        <table class="table table-condensed">
                            <tbody>
                                <?php foreach ($equity as $data): ?>
                                <?php if ($data['balance'] == 0) continue; ?>
                                <tr>
                                    <td style="font-family:monospace; width:70px;"><?= $data['account']->code ?></td>
                                    <td><?= Html::encode($data['account']->name_ar) ?></td>
                                    <td class="text-left" style="font-weight:600;"><?= number_format($data['balance'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr style="font-style:italic;">
                                    <td></td>
                                    <td><?= $netIncome >= 0 ? 'صافي ربح الفترة' : 'صافي خسارة الفترة' ?></td>
                                    <td class="text-left" style="font-weight:600; color:<?= $netIncome >= 0 ? '#28a745' : '#dc3545' ?>;"><?= number_format($netIncome, 2) ?></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr style="background:#d6ecf5; font-weight:700;">
                                    <td colspan="2">إجمالي حقوق الملكية</td>
                                    <td class="text-left"><?= number_format($totalEquity + $netIncome, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div style="background:#f5f6f8; padding:12px 16px; border-radius:8px; font-weight:800; font-size:15px; text-align:center;">
                    الخصوم + حقوق الملكية = <?= number_format($totalLiabilitiesAndEquity, 2) ?>
                </div>
            </div>
        </div>

        <div class="box <?= abs($totalAssets - $totalLiabilitiesAndEquity) < 0.01 ? 'box-success' : 'box-danger' ?>" style="margin-top:15px;">
            <div class="box-body text-center" style="padding:15px;">
                <?php if (abs($totalAssets - $totalLiabilitiesAndEquity) < 0.01): ?>
                    <span class="text-success" style="font-size:16px; font-weight:700;"><i class="fa fa-check-circle"></i> المعادلة المحاسبية متوازنة: الأصول = الخصوم + حقوق الملكية</span>
                <?php else: ?>
                    <span class="text-danger" style="font-size:16px; font-weight:700;"><i class="fa fa-times-circle"></i> المعادلة غير متوازنة — الفرق: <?= number_format(abs($totalAssets - $totalLiabilitiesAndEquity), 2) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
