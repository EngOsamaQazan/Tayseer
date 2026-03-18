<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

$this->title = 'دفتر حساب: ' . $account->code . ' - ' . $account->name_ar;
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = ['label' => 'الأستاذ العام', 'url' => ['index']];
$this->params['breadcrumbs'][] = $account->code;

$fyOptions = ArrayHelper::map($fiscalYears, 'id', 'name');
?>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-file-text-o"></i>
            <span style="font-family:monospace; color:var(--clr-primary, #800020);"><?= Html::encode($account->code) ?></span>
            <?= Html::encode($account->name_ar) ?>
            <?= $account->getTypeBadge() ?>
        </h3>
    </div>
    <div class="box-body">
        <form method="get" class="form-inline" style="margin-bottom:20px;">
            <input type="hidden" name="id" value="<?= $account->id ?>">
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

        <div class="row" style="margin-bottom:15px;">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($account->opening_balance, 2) ?></div>
                    <div class="stat-label">الرصيد الافتتاحي</div>
                </div>
            </div>
            <?php
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($lines as $line) {
                $totalDebit += (float)$line->debit;
                $totalCredit += (float)$line->credit;
            }
            $closingBalance = $account->opening_balance;
            if ($account->nature === 'debit') {
                $closingBalance += $totalDebit - $totalCredit;
            } else {
                $closingBalance += $totalCredit - $totalDebit;
            }
            ?>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value" style="color:#28a745;"><?= number_format($totalDebit, 2) ?></div>
                    <div class="stat-label">مجموع المدين</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value" style="color:#dc3545;"><?= number_format($totalCredit, 2) ?></div>
                    <div class="stat-label">مجموع الدائن</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?= number_format($closingBalance, 2) ?></div>
                    <div class="stat-label">الرصيد الختامي</div>
                </div>
            </div>
        </div>

        <?php if (empty($lines)): ?>
            <div class="text-center text-muted" style="padding:30px;">
                <p>لا توجد حركات على هذا الحساب.</p>
            </div>
        <?php else: ?>
        <table class="table table-bordered table-striped table-condensed">
            <thead>
                <tr style="background:#f5f6f8;">
                    <th class="text-center">#</th>
                    <th class="text-center">التاريخ</th>
                    <th class="text-center">رقم القيد</th>
                    <th>البيان</th>
                    <th class="text-center">مدين</th>
                    <th class="text-center">دائن</th>
                    <th class="text-center">الرصيد</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $runningBalance = $account->opening_balance;
                foreach ($lines as $i => $line):
                    if ($account->nature === 'debit') {
                        $runningBalance += (float)$line->debit - (float)$line->credit;
                    } else {
                        $runningBalance += (float)$line->credit - (float)$line->debit;
                    }
                ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td class="text-center"><?= $line->journalEntry->entry_date ?></td>
                    <td class="text-center">
                        <?= Html::a($line->journalEntry->entry_number, ['/accounting/journal-entry/view', 'id' => $line->journal_entry_id], ['style' => 'font-family:monospace;']) ?>
                    </td>
                    <td><?= Html::encode($line->description ?: $line->journalEntry->description) ?></td>
                    <td class="text-left" style="font-weight:600; <?= $line->debit > 0 ? 'color:#28a745;' : '' ?>"><?= $line->debit > 0 ? number_format($line->debit, 2) : '' ?></td>
                    <td class="text-left" style="font-weight:600; <?= $line->credit > 0 ? 'color:#dc3545;' : '' ?>"><?= $line->credit > 0 ? number_format($line->credit, 2) : '' ?></td>
                    <td class="text-left" style="font-weight:700;"><?= number_format($runningBalance, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
