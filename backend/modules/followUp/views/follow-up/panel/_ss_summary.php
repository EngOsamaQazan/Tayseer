<?php
/**
 * Side card showing the customer's most recent Social Security statement,
 * styled to match the follow-up panel (OCP design tokens).
 *
 * Renders nothing when the customer has no SS statement on file. Sits inside
 * the right column of the panel, below the AI suggestions card.
 *
 * @var \yii\web\View $this
 * @var \backend\modules\customers\models\CustomerSsStatement|null $statement
 * @var int $customerId
 * @var int $statementCount  total statements stored for this customer
 */

use yii\helpers\Html;

if ($statement === null) {
    return;
}

$money = static function ($v): string {
    if ($v === null || $v === '' || !is_numeric($v)) return '—';
    return number_format((float)$v, 2) . ' د.أ';
};
$date = static function ($v): string {
    return empty($v) ? '—' : Html::encode((string)$v);
};

$subscriptions = $statement->subscriptions;
$salaries      = $statement->salaries;
$activeCount   = 0;
foreach ($subscriptions as $sub) {
    if ($sub->isActive()) $activeCount++;
}
?>
<div class="ocp-ss-card" role="region" aria-labelledby="ocp-ss-card-title">
    <div class="ocp-ss-card__header">
        <div class="ocp-ss-card__header-icon">
            <i class="fa fa-id-badge"></i>
        </div>
        <span class="ocp-ss-card__header-title" id="ocp-ss-card-title">كشف الضمان الاجتماعي</span>
        <?php if (!empty($statement->is_current)): ?>
            <span class="ocp-ss-card__pill">الأحدث</span>
        <?php endif ?>
    </div>

    <div class="ocp-ss-card__body">
        <!-- ── Headline: latest salary ── -->
        <div class="ocp-ss-card__headline">
            <div class="ocp-ss-card__headline-label">آخر راتب شهري</div>
            <div class="ocp-ss-card__headline-value">
                <?= $money($statement->latest_monthly_salary) ?>
                <?php if ($statement->latest_salary_year): ?>
                    <small>(<?= (int)$statement->latest_salary_year ?>)</small>
                <?php endif ?>
            </div>
        </div>

        <!-- ── Key facts grid ── -->
        <dl class="ocp-ss-card__facts">
            <?php if (!empty($statement->current_employer_name)): ?>
            <div>
                <dt>جهة العمل الحالية</dt>
                <dd><?= Html::encode($statement->current_employer_name) ?></dd>
            </div>
            <?php endif ?>

            <div>
                <dt>حالة الاشتراك</dt>
                <dd>
                    <?php if ($statement->active_subscription): ?>
                        <span class="ocp-ss-card__chip ocp-ss-card__chip--ok">
                            <i class="fa fa-check-circle"></i> نشط
                        </span>
                    <?php else: ?>
                        <span class="ocp-ss-card__chip ocp-ss-card__chip--muted">غير نشط</span>
                    <?php endif ?>
                </dd>
            </div>

            <?php if ($statement->total_subscription_months): ?>
            <div>
                <dt>إجمالي أشهر الاشتراك</dt>
                <dd><?= (int)$statement->total_subscription_months ?> شهر</dd>
            </div>
            <?php endif ?>

            <?php if (!empty($statement->social_security_number)): ?>
            <div>
                <dt>رقم التأمين</dt>
                <dd class="ocp-ss-card__mono"><?= Html::encode($statement->social_security_number) ?></dd>
            </div>
            <?php endif ?>

            <div>
                <dt>تاريخ الكشف</dt>
                <dd><?= $date($statement->statement_date) ?></dd>
            </div>

            <?php if ($statement->join_date): ?>
            <div>
                <dt>تاريخ الالتحاق بالضمان</dt>
                <dd><?= $date($statement->join_date) ?></dd>
            </div>
            <?php endif ?>
        </dl>

        <!-- ── Subscriptions table (collapsible) ── -->
        <?php if (!empty($subscriptions)): ?>
        <details class="ocp-ss-card__details">
            <summary>
                <i class="fa fa-list-ul"></i>
                <span>فترات الاشتراك</span>
                <span class="ocp-ss-card__count"><?= count($subscriptions) ?></span>
                <?php if ($activeCount > 0): ?>
                    <span class="ocp-ss-card__count ocp-ss-card__count--ok"><?= $activeCount ?> نشطة</span>
                <?php endif ?>
                <i class="fa fa-chevron-down ocp-ss-card__chevron"></i>
            </summary>
            <div class="ocp-ss-card__table-wrap">
                <table class="ocp-ss-card__table">
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
                            <td class="ocp-ss-card__est" title="<?= Html::encode($sub->establishment_name ?? '') ?>">
                                <?php if (!empty($sub->establishment_name)): ?>
                                    <?= Html::encode($sub->establishment_name) ?>
                                <?php elseif (!empty($sub->establishment_no)): ?>
                                    <span class="ocp-ss-card__muted">#<?= Html::encode($sub->establishment_no) ?></span>
                                <?php else: ?>
                                    —
                                <?php endif ?>
                            </td>
                            <td><?= $date($sub->from_date) ?></td>
                            <td>
                                <?php if ($sub->isActive()): ?>
                                    <span class="ocp-ss-card__inline-ok">نشط</span>
                                <?php else: ?>
                                    <?= $date($sub->to_date) ?>
                                <?php endif ?>
                            </td>
                            <td class="ocp-ss-card__num">
                                <?= $sub->salary !== null ? number_format((float)$sub->salary, 0) : '—' ?>
                            </td>
                            <td class="ocp-ss-card__num">
                                <?= $sub->months !== null ? (int)$sub->months : '—' ?>
                            </td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </details>
        <?php endif ?>

        <!-- ── Salary history (collapsible) ── -->
        <?php if (!empty($salaries)): ?>
        <details class="ocp-ss-card__details">
            <summary>
                <i class="fa fa-line-chart"></i>
                <span>سجل الرواتب السنوي</span>
                <span class="ocp-ss-card__count"><?= count($salaries) ?></span>
                <i class="fa fa-chevron-down ocp-ss-card__chevron"></i>
            </summary>
            <div class="ocp-ss-card__table-wrap">
                <table class="ocp-ss-card__table">
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
                            <td class="ocp-ss-card__num"><?= (int)$sal->year ?></td>
                            <td class="ocp-ss-card__num">
                                <?= $sal->salary !== null ? number_format((float)$sal->salary, 0) : '—' ?>
                            </td>
                            <td class="ocp-ss-card__est" title="<?= Html::encode($sal->establishment_name ?? '') ?>">
                                <?php if (!empty($sal->establishment_name)): ?>
                                    <?= Html::encode($sal->establishment_name) ?>
                                <?php elseif (!empty($sal->establishment_no)): ?>
                                    <span class="ocp-ss-card__muted">#<?= Html::encode($sal->establishment_no) ?></span>
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
        <div class="ocp-ss-card__footer-note">
            <i class="fa fa-history"></i>
            هناك <strong><?= (int)($statementCount - 1) ?></strong>
            <?= $statementCount - 1 === 1 ? 'كشف سابق' : 'كشوفات سابقة' ?> محفوظة في السجل.
        </div>
        <?php endif ?>
    </div>
</div>

<style>
/* ── OCP-themed Social Security card — follow-up panel right column ── */
.ocp-ss-card {
    background: var(--ocp-surface);
    border: 1px solid var(--ocp-border);
    border-radius: var(--ocp-radius-lg);
    overflow: hidden;
    margin-top: var(--ocp-space-lg);
    box-shadow: 0 1px 2px rgba(17, 24, 39, .04);
}
.ocp-ss-card__header {
    display: flex;
    align-items: center;
    gap: var(--ocp-space-sm);
    padding: var(--ocp-space-md) var(--ocp-space-lg);
    background: linear-gradient(90deg, rgba(13,159,110,0.06) 0%, rgba(13,159,110,0.02) 100%);
    border-bottom: 1px solid var(--ocp-border);
}
.ocp-ss-card__header-icon {
    width: 28px;
    height: 28px;
    border-radius: var(--ocp-radius-sm);
    background: var(--ocp-success-bg);
    color: var(--ocp-success);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}
.ocp-ss-card__header-title {
    flex: 1;
    font-weight: 700;
    font-size: 13.5px;
    color: var(--ocp-text);
}
.ocp-ss-card__pill {
    font-size: 10.5px;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: var(--ocp-radius-full);
    background: var(--ocp-success-bg);
    color: var(--ocp-success);
    border: 1px solid rgba(13,159,110,0.25);
    letter-spacing: .2px;
}
.ocp-ss-card__body {
    padding: var(--ocp-space-lg);
    display: flex;
    flex-direction: column;
    gap: var(--ocp-space-md);
}
.ocp-ss-card__headline {
    background: var(--ocp-primary-bg);
    border: 1px solid rgba(108,29,69,0.12);
    border-radius: var(--ocp-radius-md);
    padding: var(--ocp-space-md) var(--ocp-space-lg);
    text-align: center;
}
.ocp-ss-card__headline-label {
    font-size: 11.5px;
    color: var(--ocp-text-secondary);
    font-weight: 600;
    margin-bottom: 2px;
}
.ocp-ss-card__headline-value {
    font-size: 22px;
    font-weight: 800;
    color: var(--ocp-primary);
    line-height: 1.2;
}
.ocp-ss-card__headline-value small {
    font-size: 11px;
    font-weight: 600;
    color: var(--ocp-text-muted);
    margin-inline-start: 4px;
}
.ocp-ss-card__facts {
    margin: 0;
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--ocp-space-xs);
    background: var(--ocp-border-light);
    border-radius: var(--ocp-radius-md);
    padding: var(--ocp-space-md);
}
.ocp-ss-card__facts > div {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--ocp-space-md);
    padding: 6px 0;
    border-bottom: 1px dashed rgba(0,0,0,0.06);
}
.ocp-ss-card__facts > div:last-child { border-bottom: none; }
.ocp-ss-card__facts dt {
    font-size: 12px;
    font-weight: 600;
    color: var(--ocp-text-secondary);
    margin: 0;
}
.ocp-ss-card__facts dd {
    margin: 0;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--ocp-text);
    text-align: end;
    max-width: 60%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.ocp-ss-card__chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11.5px;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: var(--ocp-radius-full);
}
.ocp-ss-card__chip--ok {
    background: var(--ocp-success-bg);
    color: var(--ocp-success);
}
.ocp-ss-card__chip--muted {
    background: var(--ocp-border-light);
    color: var(--ocp-text-muted);
}
.ocp-ss-card__mono {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    letter-spacing: .3px;
}

/* ── Collapsible details (subscriptions / salaries) ── */
.ocp-ss-card__details {
    border: 1px solid var(--ocp-border);
    border-radius: var(--ocp-radius-md);
    background: var(--ocp-surface);
    overflow: hidden;
}
.ocp-ss-card__details summary {
    list-style: none;
    cursor: pointer;
    user-select: none;
    display: flex;
    align-items: center;
    gap: var(--ocp-space-sm);
    padding: 10px var(--ocp-space-md);
    font-size: 12.5px;
    font-weight: 700;
    color: var(--ocp-text);
    background: var(--ocp-border-light);
}
.ocp-ss-card__details summary::-webkit-details-marker { display: none; }
.ocp-ss-card__details summary i.fa-line-chart,
.ocp-ss-card__details summary i.fa-list-ul { color: var(--ocp-primary); }
.ocp-ss-card__details summary > span:first-of-type { flex: 1; }
.ocp-ss-card__chevron {
    transition: transform .2s;
    color: var(--ocp-text-muted);
    font-size: 11px;
}
.ocp-ss-card__details[open] .ocp-ss-card__chevron { transform: rotate(180deg); }
.ocp-ss-card__count {
    display: inline-flex;
    align-items: center;
    font-size: 10.5px;
    font-weight: 700;
    padding: 1px 8px;
    border-radius: var(--ocp-radius-full);
    background: var(--ocp-surface);
    color: var(--ocp-text-secondary);
    border: 1px solid var(--ocp-border);
}
.ocp-ss-card__count--ok {
    background: var(--ocp-success-bg);
    color: var(--ocp-success);
    border-color: rgba(13,159,110,0.25);
}
.ocp-ss-card__table-wrap {
    max-height: 280px;
    overflow: auto;
    background: var(--ocp-surface);
    border-top: 1px solid var(--ocp-border);
}
.ocp-ss-card__table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11.5px;
}
.ocp-ss-card__table th,
.ocp-ss-card__table td {
    padding: 6px 8px;
    border-bottom: 1px solid var(--ocp-border-light);
    text-align: right;
    vertical-align: middle;
}
.ocp-ss-card__table thead th {
    background: var(--ocp-border-light);
    color: var(--ocp-text-secondary);
    font-weight: 700;
    position: sticky;
    top: 0;
    z-index: 1;
    font-size: 11px;
    border-bottom: 1px solid var(--ocp-border);
}
.ocp-ss-card__table tbody tr:last-child td { border-bottom: none; }
.ocp-ss-card__table tbody tr.is-active td { background: var(--ocp-success-bg); }
.ocp-ss-card__num {
    font-family: 'Courier New', monospace;
    font-weight: 700;
    text-align: center !important;
    white-space: nowrap;
}
.ocp-ss-card__est {
    max-width: 160px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.ocp-ss-card__inline-ok { color: var(--ocp-success); font-weight: 700; }
.ocp-ss-card__muted { color: var(--ocp-text-muted); }
.ocp-ss-card__footer-note {
    text-align: center;
    font-size: 11.5px;
    color: var(--ocp-text-secondary);
    padding: 8px;
    background: var(--ocp-border-light);
    border-radius: var(--ocp-radius-sm);
}
.ocp-ss-card__footer-note strong { color: var(--ocp-primary); }
</style>
