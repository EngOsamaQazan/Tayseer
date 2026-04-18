<?php
/**
 * Sidebar card showing the customer's most recent Social Security statement.
 *
 * Renders nothing when the customer has no statement on file. Designed to
 * sit inside `.cf-sidebar`, alongside the financial summary.
 *
 * @var \yii\web\View $this
 * @var \backend\modules\customers\models\CustomerSsStatement|null $statement
 * @var int $customerId
 * @var int $statementCount  total statements stored for this customer
 * @var \backend\modules\customers\models\Customers|null $customer
 */

use yii\helpers\Html;

if ($statement === null) {
    return;
}

/** Format a money value with Arabic dinar suffix. */
$money = static function ($v): string {
    if ($v === null || $v === '' || !is_numeric($v)) return '—';
    return number_format((float)$v, 2) . ' د.أ';
};

/** Format a date value (YYYY-MM-DD) for display. */
$date = static function ($v): string {
    if (empty($v)) return '—';
    return Html::encode((string)$v);
};

$subscriptions = $statement->subscriptions;
$salaries      = $statement->salaries;
$activeCount   = 0;
foreach ($subscriptions as $sub) {
    if ($sub->isActive()) $activeCount++;
}
?>
<div class="cf-summary cf-ss-card" style="margin-top:16px">
    <div class="cf-sum-hd" style="display:flex;align-items:center;justify-content:space-between;gap:8px">
        <h4 style="display:flex;align-items:center;gap:6px;margin:0">
            <i class="fa fa-id-badge" style="color:var(--cf-teal)"></i>
            كشف الضمان الاجتماعي
        </h4>
        <?php if (!empty($statement->is_current)): ?>
            <span style="font-size:10.5px;font-weight:700;color:var(--cf-ok);background:var(--cf-ok-l);
                         border:1px solid var(--cf-ok-b);padding:2px 8px;border-radius:999px">
                الأحدث
            </span>
        <?php endif ?>
    </div>

    <div class="cf-sum-bd">
        <!-- ── Headline row: latest salary ── -->
        <div class="cf-sum-row">
            <span class="cf-sum-label">آخر راتب شهري</span>
            <span class="cf-sum-val big" style="color:var(--cf-navy)"><?= $money($statement->latest_monthly_salary) ?></span>
        </div>

        <?php if ($statement->latest_salary_year): ?>
        <div class="cf-sum-row">
            <span class="cf-sum-label">سنة الراتب</span>
            <span class="cf-sum-val"><?= (int)$statement->latest_salary_year ?></span>
        </div>
        <?php endif ?>

        <?php if (!empty($statement->current_employer_name)): ?>
        <div class="cf-sum-row">
            <span class="cf-sum-label">جهة العمل الحالية</span>
            <span class="cf-sum-val" style="font-size:12.5px;text-align:left;direction:rtl;max-width:60%"><?= Html::encode($statement->current_employer_name) ?></span>
        </div>
        <?php endif ?>

        <div class="cf-sum-row">
            <span class="cf-sum-label">الاشتراك</span>
            <?php if ($statement->active_subscription): ?>
                <span class="cf-sum-val ok" style="display:inline-flex;align-items:center;gap:4px">
                    <i class="fa fa-check-circle"></i> نشط
                </span>
            <?php else: ?>
                <span class="cf-sum-val" style="color:var(--cf-text3)">غير نشط</span>
            <?php endif ?>
        </div>

        <?php if ($statement->total_subscription_months): ?>
        <div class="cf-sum-row">
            <span class="cf-sum-label">إجمالي أشهر الاشتراك</span>
            <span class="cf-sum-val"><?= (int)$statement->total_subscription_months ?> شهر</span>
        </div>
        <?php endif ?>

        <?php if (!empty($statement->social_security_number)): ?>
        <div class="cf-sum-row">
            <span class="cf-sum-label">رقم التأمين</span>
            <span class="cf-sum-val" style="font-family:'Courier New',monospace;font-size:12px"><?= Html::encode($statement->social_security_number) ?></span>
        </div>
        <?php endif ?>

        <div class="cf-sum-divider"></div>

        <div class="cf-sum-row">
            <span class="cf-sum-label">تاريخ الكشف</span>
            <span class="cf-sum-val"><?= $date($statement->statement_date) ?></span>
        </div>

        <?php if ($statement->join_date): ?>
        <div class="cf-sum-row">
            <span class="cf-sum-label">تاريخ الالتحاق بالضمان</span>
            <span class="cf-sum-val"><?= $date($statement->join_date) ?></span>
        </div>
        <?php endif ?>

        <!-- ── Subscriptions table (collapsible) ── -->
        <?php if (!empty($subscriptions)): ?>
        <details class="cf-ss-details" style="margin-top:10px">
            <summary style="cursor:pointer;font-size:12.5px;font-weight:700;color:var(--cf-navy);padding:6px 0">
                <i class="fa fa-list-ul"></i>
                فترات الاشتراك (<?= count($subscriptions) ?>)
                <?php if ($activeCount > 0): ?>
                    <span style="color:var(--cf-ok);font-weight:600;font-size:11px">— <?= $activeCount ?> نشطة</span>
                <?php endif ?>
            </summary>
            <div class="cf-ss-table-wrap">
                <table class="cf-ss-table">
                    <thead>
                        <tr>
                            <th>المنشأة</th>
                            <th>من</th>
                            <th>إلى</th>
                            <th>الراتب</th>
                            <th>الأشهر</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscriptions as $sub): ?>
                        <tr<?= $sub->isActive() ? ' class="is-active"' : '' ?>>
                            <td class="cf-ss-est">
                                <?php if (!empty($sub->establishment_name)): ?>
                                    <?= Html::encode($sub->establishment_name) ?>
                                <?php elseif (!empty($sub->establishment_no)): ?>
                                    <span style="color:var(--cf-text3)">#<?= Html::encode($sub->establishment_no) ?></span>
                                <?php else: ?>
                                    —
                                <?php endif ?>
                            </td>
                            <td><?= $date($sub->from_date) ?></td>
                            <td>
                                <?php if ($sub->isActive()): ?>
                                    <span style="color:var(--cf-ok);font-weight:700">نشط</span>
                                <?php else: ?>
                                    <?= $date($sub->to_date) ?>
                                <?php endif ?>
                            </td>
                            <td class="cf-ss-num"><?= $sub->salary !== null ? number_format((float)$sub->salary, 0) : '—' ?></td>
                            <td class="cf-ss-num"><?= $sub->months !== null ? (int)$sub->months : '—' ?></td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </details>
        <?php endif ?>

        <!-- ── Salary history (collapsible) ── -->
        <?php if (!empty($salaries)): ?>
        <details class="cf-ss-details">
            <summary style="cursor:pointer;font-size:12.5px;font-weight:700;color:var(--cf-navy);padding:6px 0">
                <i class="fa fa-line-chart"></i>
                سجل الرواتب السنوي (<?= count($salaries) ?>)
            </summary>
            <div class="cf-ss-table-wrap">
                <table class="cf-ss-table">
                    <thead>
                        <tr>
                            <th>السنة</th>
                            <th>الراتب</th>
                            <th>المنشأة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salaries as $sal): ?>
                        <tr>
                            <td class="cf-ss-num"><?= (int)$sal->year ?></td>
                            <td class="cf-ss-num"><?= $sal->salary !== null ? number_format((float)$sal->salary, 0) : '—' ?></td>
                            <td class="cf-ss-est">
                                <?php if (!empty($sal->establishment_name)): ?>
                                    <?= Html::encode($sal->establishment_name) ?>
                                <?php elseif (!empty($sal->establishment_no)): ?>
                                    <span style="color:var(--cf-text3)">#<?= Html::encode($sal->establishment_no) ?></span>
                                <?php else: ?>
                                    —
                                <?php endif ?>
                            </td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </details>
        <?php endif ?>

        <?php if ($statementCount > 1): ?>
        <div style="margin-top:8px;padding:6px 8px;background:var(--cf-bg);border-radius:var(--cf-r-sm);
                    font-size:11.5px;color:var(--cf-text2);text-align:center">
            <i class="fa fa-history"></i>
            هناك <strong style="color:var(--cf-navy)"><?= (int)($statementCount - 1) ?></strong>
            <?= $statementCount - 1 === 1 ? 'كشف سابق' : 'كشوفات سابقة' ?> محفوظة في السجل.
        </div>
        <?php endif ?>
    </div>
</div>

<style>
/* ── SS sidebar card — scoped to .cf-ss-card ─────────────────── */
.cf-ss-card .cf-ss-details {
    border-top: 1px solid var(--cf-border);
    padding-top: 6px;
    margin-top: 4px;
}
.cf-ss-card .cf-ss-details summary {
    list-style: none;
    user-select: none;
}
.cf-ss-card .cf-ss-details summary::-webkit-details-marker { display: none; }
.cf-ss-card .cf-ss-details[open] summary {
    border-bottom: 1px dashed var(--cf-border);
    margin-bottom: 6px;
}
.cf-ss-card .cf-ss-table-wrap {
    max-height: 240px;
    overflow: auto;
    border: 1px solid var(--cf-border);
    border-radius: var(--cf-r-sm);
    background: #fff;
}
.cf-ss-card .cf-ss-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11.5px;
}
.cf-ss-card .cf-ss-table th,
.cf-ss-card .cf-ss-table td {
    padding: 5px 7px;
    border-bottom: 1px solid #f1f5f9;
    text-align: right;
    vertical-align: middle;
}
.cf-ss-card .cf-ss-table thead th {
    background: var(--cf-bg);
    color: var(--cf-text2);
    font-weight: 700;
    position: sticky;
    top: 0;
    z-index: 1;
    font-size: 11px;
}
.cf-ss-card .cf-ss-table tbody tr:last-child td { border-bottom: none; }
.cf-ss-card .cf-ss-table tbody tr.is-active td { background: var(--cf-ok-l); }
.cf-ss-card .cf-ss-table .cf-ss-num {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    text-align: center;
    white-space: nowrap;
}
.cf-ss-card .cf-ss-table .cf-ss-est {
    max-width: 140px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
