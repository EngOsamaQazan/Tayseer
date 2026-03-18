<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

$this->title = 'الأستاذ العام';
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = $this->title;

$fyOptions = ArrayHelper::map($fiscalYears, 'id', 'name');
?>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-th-list"></i> <?= $this->title ?></h3>
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

        <?php if (empty($dataProvider->allModels)): ?>
            <div class="text-center text-muted" style="padding:40px;">
                <i class="fa fa-th-list fa-3x"></i>
                <p style="margin-top:15px;">لا توجد حركات محاسبية مرحّلة بعد.</p>
            </div>
        <?php else: ?>
        <table class="table table-bordered table-striped table-hover table-condensed">
            <thead>
                <tr style="background:#f5f6f8;">
                    <th class="text-center">رقم الحساب</th>
                    <th>اسم الحساب</th>
                    <th class="text-center">النوع</th>
                    <th class="text-center">الرصيد الافتتاحي</th>
                    <th class="text-center">مجموع المدين</th>
                    <th class="text-center">مجموع الدائن</th>
                    <th class="text-center">الرصيد الختامي</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $grandDebit = 0;
                $grandCredit = 0;
                $grandOpening = 0;
                $grandClosing = 0;
                foreach ($dataProvider->allModels as $row):
                    $account = $row['account'];
                    $grandDebit += $row['total_debit'];
                    $grandCredit += $row['total_credit'];
                    $grandOpening += $row['opening_balance'];
                    $grandClosing += $row['closing_balance'];
                ?>
                <tr>
                    <td class="text-center" style="font-family:monospace; font-weight:700;">
                        <?= Html::a($account->code, ['account', 'id' => $account->id, 'fiscal_year_id' => $fiscalYearId]) ?>
                    </td>
                    <td><?= Html::encode($account->name_ar) ?></td>
                    <td class="text-center"><?= $account->getTypeBadge() ?></td>
                    <td class="text-left"><?= number_format($row['opening_balance'], 2) ?></td>
                    <td class="text-left" style="color:#28a745; font-weight:600;"><?= number_format($row['total_debit'], 2) ?></td>
                    <td class="text-left" style="color:#dc3545; font-weight:600;"><?= number_format($row['total_credit'], 2) ?></td>
                    <td class="text-left" style="font-weight:700;"><?= number_format($row['closing_balance'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f5f6f8; font-weight:800; font-size:14px;">
                    <td colspan="3" class="text-left">الإجمالي</td>
                    <td class="text-left"><?= number_format($grandOpening, 2) ?></td>
                    <td class="text-left" style="color:#28a745;"><?= number_format($grandDebit, 2) ?></td>
                    <td class="text-left" style="color:#dc3545;"><?= number_format($grandCredit, 2) ?></td>
                    <td class="text-left"><?= number_format($grandClosing, 2) ?></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>
</div>
