<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

$this->title = 'قائمة الدخل';
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = $this->title;

$fyOptions = ArrayHelper::map($fiscalYears, 'id', 'name');
?>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-file-text"></i> <?= $this->title ?></h3>
        <div class="box-tools">
            <?= Html::a('<i class="fa fa-balance-scale"></i> ميزان المراجعة', ['trial-balance', 'fiscal_year_id' => $fiscalYearId], ['class' => 'btn btn-default btn-sm']) ?>
            <?= Html::a('<i class="fa fa-university"></i> الميزانية العمومية', ['balance-sheet', 'fiscal_year_id' => $fiscalYearId], ['class' => 'btn btn-default btn-sm']) ?>
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
                <label>من:</label>
                <input type="date" name="date_from" class="form-control" value="<?= Html::encode($dateFrom) ?>">
            </div>
            <div class="form-group" style="margin-left:10px;">
                <label>إلى:</label>
                <input type="date" name="date_to" class="form-control" value="<?= Html::encode($dateTo) ?>">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> عرض</button>
        </form>

        <div class="row">
            <div class="col-md-6">
                <div class="box box-success">
                    <div class="box-header"><h4 class="box-title"><i class="fa fa-arrow-down"></i> الإيرادات</h4></div>
                    <div class="box-body no-padding">
                        <table class="table table-condensed">
                            <tbody>
                                <?php foreach ($revenue as $data): ?>
                                <tr>
                                    <td style="font-family:monospace; width:70px;"><?= $data['account']->code ?></td>
                                    <td><?= Html::encode($data['account']->name_ar) ?></td>
                                    <td class="text-left" style="font-weight:600; color:#28a745;"><?= number_format($data['balance'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($revenue)): ?>
                                <tr><td colspan="3" class="text-center text-muted">لا توجد إيرادات</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background:#f5f6f8; font-weight:800;">
                                    <td colspan="2">إجمالي الإيرادات</td>
                                    <td class="text-left" style="color:#28a745; font-size:16px;"><?= number_format($totalRevenue, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="box box-danger">
                    <div class="box-header"><h4 class="box-title"><i class="fa fa-arrow-up"></i> المصروفات</h4></div>
                    <div class="box-body no-padding">
                        <table class="table table-condensed">
                            <tbody>
                                <?php foreach ($expenses as $data): ?>
                                <tr>
                                    <td style="font-family:monospace; width:70px;"><?= $data['account']->code ?></td>
                                    <td><?= Html::encode($data['account']->name_ar) ?></td>
                                    <td class="text-left" style="font-weight:600; color:#dc3545;"><?= number_format($data['balance'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($expenses)): ?>
                                <tr><td colspan="3" class="text-center text-muted">لا توجد مصروفات</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background:#f5f6f8; font-weight:800;">
                                    <td colspan="2">إجمالي المصروفات</td>
                                    <td class="text-left" style="color:#dc3545; font-size:16px;"><?= number_format($totalExpenses, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="box <?= $netIncome >= 0 ? 'box-success' : 'box-danger' ?>" style="margin-top:0;">
            <div class="box-body text-center" style="padding:25px;">
                <h3 style="margin:0; font-weight:800; color:<?= $netIncome >= 0 ? '#28a745' : '#dc3545' ?>;">
                    <i class="fa fa-<?= $netIncome >= 0 ? 'trending-up' : 'trending-down' ?>"></i>
                    <?= $netIncome >= 0 ? 'صافي الربح' : 'صافي الخسارة' ?>:
                    <?= number_format(abs($netIncome), 2) ?>
                </h3>
            </div>
        </div>
    </div>
</div>
