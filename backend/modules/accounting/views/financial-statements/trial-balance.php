<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

$this->title = 'ميزان المراجعة';
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = $this->title;

$fyOptions = ArrayHelper::map($fiscalYears, 'id', 'name');
?>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-balance-scale"></i> <?= $this->title ?></h3>
        <div class="box-tools">
            <?= Html::a('<i class="fa fa-file-text"></i> قائمة الدخل', ['income-statement', 'fiscal_year_id' => $fiscalYearId], ['class' => 'btn btn-default btn-sm']) ?>
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

        <table class="table table-bordered table-condensed table-striped">
            <thead>
                <tr style="background:#f5f6f8;">
                    <th style="width:80px;" class="text-center">رقم الحساب</th>
                    <th>اسم الحساب</th>
                    <th class="text-center" style="width:120px;">مدين</th>
                    <th class="text-center" style="width:120px;">دائن</th>
                    <th class="text-center" style="width:120px;">رصيد مدين</th>
                    <th class="text-center" style="width:120px;">رصيد دائن</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sumDebit = 0;
                $sumCredit = 0;
                $balDebit = 0;
                $balCredit = 0;
                foreach ($balances as $data):
                    $account = $data['account'];
                    if ($data['total_debit'] == 0 && $data['total_credit'] == 0 && $data['balance'] == 0) continue;

                    $sumDebit += $data['total_debit'];
                    $sumCredit += $data['total_credit'];

                    $isDebitBalance = ($account->nature === 'debit' && $data['balance'] >= 0) || ($account->nature === 'credit' && $data['balance'] < 0);
                    if ($isDebitBalance) {
                        $balDebit += abs($data['balance']);
                    } else {
                        $balCredit += abs($data['balance']);
                    }
                ?>
                <tr>
                    <td class="text-center" style="font-family:monospace; font-weight:700;"><?= Html::encode($account->code) ?></td>
                    <td><?= Html::encode($account->name_ar) ?></td>
                    <td class="text-left"><?= $data['total_debit'] > 0 ? number_format($data['total_debit'], 2) : '' ?></td>
                    <td class="text-left"><?= $data['total_credit'] > 0 ? number_format($data['total_credit'], 2) : '' ?></td>
                    <td class="text-left" style="color:#28a745; font-weight:600;"><?= $isDebitBalance ? number_format(abs($data['balance']), 2) : '' ?></td>
                    <td class="text-left" style="color:#dc3545; font-weight:600;"><?= !$isDebitBalance ? number_format(abs($data['balance']), 2) : '' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f5f6f8; font-weight:800; font-size:14px;">
                    <td colspan="2" class="text-left">المجموع</td>
                    <td class="text-left"><?= number_format($sumDebit, 2) ?></td>
                    <td class="text-left"><?= number_format($sumCredit, 2) ?></td>
                    <td class="text-left" style="color:#28a745;"><?= number_format($balDebit, 2) ?></td>
                    <td class="text-left" style="color:#dc3545;"><?= number_format($balCredit, 2) ?></td>
                </tr>
                <tr>
                    <td colspan="6" class="text-center" style="font-size:13px;">
                        <?php $diff = abs($balDebit - $balCredit); ?>
                        <?php if ($diff < 0.01): ?>
                            <span class="text-success"><i class="fa fa-check-circle"></i> الميزان متوازن</span>
                        <?php else: ?>
                            <span class="text-danger"><i class="fa fa-times-circle"></i> الميزان غير متوازن — الفرق: <?= number_format($diff, 2) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
