<?php

use yii\helpers\Url;
use yii\helpers\Html;
use common\helper\Permissions;

/** @var yii\web\View $this */
/** @var backend\modules\capitalTransactions\models\CapitalTransactionsSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var backend\modules\companies\models\Companies|null $company */
/** @var float $totalDeposits */
/** @var float $totalWithdrawals */
/** @var float $currentBalance */

$companyName = $company ? $company->name : '';
$this->title = 'حركات رأس المال' . ($companyName ? ' - ' . $companyName : '');
$this->params['breadcrumbs'][] = ['label' => 'المحافظ', 'url' => ['/companies/companies/index']];
if ($company) {
    $this->params['breadcrumbs'][] = ['label' => $companyName, 'url' => ['/companies/companies/view', 'id' => $company->id]];
}
$this->params['breadcrumbs'][] = 'حركات رأس المال';

$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCss('.content-header,.page-header { display: none !important; }');

echo $this->render('@backend/views/_section_tabs', [
    'group' => 'investment',
    'tabs'  => [
        ['label' => 'المحافظ الاستثمارية', 'icon' => 'fa-briefcase',  'url' => ['/companies/companies/index']],
        ['label' => 'حركات رأس المال',    'icon' => 'fa-exchange',    'url' => ['/capitalTransactions/capital-transactions/index']],
        ['label' => 'المساهمين',           'icon' => 'fa-users',       'url' => ['/shareholders/shareholders/index']],
        ['label' => 'المصاريف المشتركة',   'icon' => 'fa-share-alt',   'url' => ['/sharedExpenses/shared-expense/index']],
        ['label' => 'توزيع الأرباح',       'icon' => 'fa-pie-chart',   'url' => ['/profitDistribution/profit-distribution/index']],
    ],
]);

$models = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();
$totalCount = $dataProvider->getTotalCount();

$canManage = Permissions::can('المستثمرين');
$companyId = $company ? $company->id : null;
?>

<style>
:root {
    --ct-primary: #f59e0b;
    --ct-primary-dark: #d97706;
    --ct-primary-light: #fef3c7;
    --ct-success: #059669;
    --ct-danger: #dc2626;
    --ct-info: #2563eb;
    --ct-border: #e2e8f0;
    --ct-bg: #f8fafc;
    --ct-r: 12px;
    --ct-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}
.ct-page { padding: 24px; max-width: 100%; margin: 0; font-family: 'Segoe UI', Tahoma, sans-serif; }

.ct-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
.ct-header h1 { font-size: 22px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 10px; }
.ct-header h1 i { color: var(--ct-primary); font-size: 20px; }
.ct-header-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.ct-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none !important; border: none; cursor: pointer; transition: all .2s; }
.ct-btn-primary { background: var(--ct-primary); color: #fff !important; }
.ct-btn-primary:hover { background: var(--ct-primary-dark); color: #fff !important; }
.ct-btn-outline { background: #fff; color: #475569 !important; border: 1px solid var(--ct-border); }
.ct-btn-outline:hover { background: var(--ct-bg); }

.ct-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px; }
.ct-stat { background: #fff; border-radius: var(--ct-r); padding: 18px; box-shadow: var(--ct-shadow); border: 1px solid var(--ct-border); display: flex; align-items: center; gap: 14px; }
.ct-stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.ct-stat-value { font-size: 22px; font-weight: 700; color: #1e293b; line-height: 1; direction: ltr; text-align: right; }
.ct-stat-label { font-size: 12px; color: #64748b; margin-top: 4px; }

.ct-search { background: #fff; border-radius: var(--ct-r); padding: 16px 20px; box-shadow: var(--ct-shadow); border: 1px solid var(--ct-border); margin-bottom: 20px; display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
.ct-search .form-group { margin-bottom: 0; flex: 1; min-width: 140px; }
.ct-search .form-group label { font-size: 12px; color: #64748b; margin-bottom: 4px; }
.ct-search .form-control { border-radius: 8px; border: 1px solid var(--ct-border); font-size: 13px; height: 38px; }
.ct-search-btn { padding: 8px 20px; border-radius: 8px; background: var(--ct-primary); color: #fff; border: none; font-size: 13px; font-weight: 600; cursor: pointer; height: 38px; white-space: nowrap; }
.ct-search-btn:hover { background: var(--ct-primary-dark); }

.ct-table-wrap { background: #fff; border-radius: var(--ct-r); box-shadow: var(--ct-shadow); border: 1px solid var(--ct-border); overflow: hidden; }
.ct-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.ct-table thead th { background: var(--ct-bg); padding: 12px 16px; font-weight: 600; color: #475569; border-bottom: 2px solid var(--ct-border); text-align: right; white-space: nowrap; font-size: 12px; text-transform: uppercase; }
.ct-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.ct-table tbody tr:hover { background: #fffbeb; }
.ct-table tbody td { padding: 14px 16px; color: #334155; vertical-align: middle; }

.ct-badge { display: inline-block; font-size: 11px; font-weight: 600; padding: 3px 12px; border-radius: 6px; }
.ct-badge-deposit { background: #dcfce7; color: #15803d; }
.ct-badge-withdraw { background: #fee2e2; color: #dc2626; }
.ct-badge-return { background: #dbeafe; color: #1d4ed8; }

.ct-amount-positive { color: #059669; font-weight: 700; }
.ct-amount-negative { color: #dc2626; font-weight: 700; }
.ct-amount-neutral { color: #475569; font-weight: 600; }

.ct-table .ct-actions { display: flex; gap: 6px; }
.ct-table .ct-actions a { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; font-size: 13px; transition: all .15s; text-decoration: none; }
.ct-act-view { background: #fef3c7; color: #b45309; }
.ct-act-view:hover { background: #fde68a; }
.ct-act-del { background: #fef2f2; color: #dc2626; }
.ct-act-del:hover { background: #fee2e2; }

.ct-pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-top: 1px solid var(--ct-border); font-size: 13px; color: #64748b; }
.ct-pagination .pagination { margin: 0; }
.ct-pagination .pagination li a, .ct-pagination .pagination li span { border-radius: 6px; margin: 0 2px; font-size: 13px; padding: 5px 12px; }

.ct-empty { text-align: center; padding: 60px 20px; color: #94a3b8; }
.ct-empty i { font-size: 48px; margin-bottom: 12px; display: block; color: var(--ct-primary); opacity: .5; }
.ct-empty p { font-size: 15px; }

@media (max-width: 768px) {
    .ct-page { padding: 12px; }
    .ct-header { flex-direction: column; align-items: flex-start; }
    .ct-stats { grid-template-columns: 1fr; }
    .ct-search { flex-direction: column; }
    .ct-table-wrap { overflow-x: auto; }
}
</style>

<div class="ct-page">

    <div class="ct-header">
        <h1><i class="fa fa-exchange"></i> <?= Html::encode($this->title) ?></h1>
        <div class="ct-header-actions">
            <?php if ($company): ?>
                <?= Html::a('<i class="fa fa-arrow-right"></i> العودة للمحفظة', ['/companies/companies/view', 'id' => $company->id], ['class' => 'ct-btn ct-btn-outline']) ?>
            <?php endif ?>
            <?php if ($canManage): ?>
                <?= Html::a('<i class="fa fa-plus"></i> إضافة حركة', $companyId ? ['create', 'company_id' => $companyId] : ['create'], ['class' => 'ct-btn ct-btn-primary']) ?>
            <?php endif ?>
        </div>
    </div>

    <div class="ct-stats">
        <div class="ct-stat">
            <div class="ct-stat-icon" style="background:rgba(5,150,105,.1);color:#059669">
                <i class="fa fa-arrow-down"></i>
            </div>
            <div>
                <div class="ct-stat-value ct-amount-positive"><?= number_format($totalDeposits, 2) ?></div>
                <div class="ct-stat-label">إجمالي الإيداعات</div>
            </div>
        </div>
        <div class="ct-stat">
            <div class="ct-stat-icon" style="background:rgba(220,38,38,.1);color:#dc2626">
                <i class="fa fa-arrow-up"></i>
            </div>
            <div>
                <div class="ct-stat-value ct-amount-negative"><?= number_format($totalWithdrawals, 2) ?></div>
                <div class="ct-stat-label">إجمالي السحوبات</div>
            </div>
        </div>
        <div class="ct-stat">
            <div class="ct-stat-icon" style="background:rgba(245,158,11,.1);color:var(--ct-primary)">
                <i class="fa fa-balance-scale"></i>
            </div>
            <div>
                <div class="ct-stat-value" style="color:<?= $currentBalance >= 0 ? '#059669' : '#dc2626' ?>"><?= number_format($currentBalance, 2) ?></div>
                <div class="ct-stat-label">الرصيد الحالي</div>
            </div>
        </div>
    </div>

    <form method="get" action="<?= Url::to(['index']) ?>" class="ct-search">
        <?php if ($companyId): ?>
            <input type="hidden" name="company_id" value="<?= $companyId ?>">
        <?php endif ?>
        <div class="form-group">
            <label>نوع العملية</label>
            <select name="CapitalTransactionsSearch[transaction_type]" class="form-control">
                <option value="">الكل</option>
                <option value="إيداع" <?= $searchModel->transaction_type === 'إيداع' ? 'selected' : '' ?>>إيداع</option>
                <option value="سحب" <?= $searchModel->transaction_type === 'سحب' ? 'selected' : '' ?>>سحب</option>
                <option value="إعادة_رأس_مال" <?= $searchModel->transaction_type === 'إعادة_رأس_مال' ? 'selected' : '' ?>>إعادة رأس مال</option>
            </select>
        </div>
        <div class="form-group">
            <label>من تاريخ</label>
            <input type="date" name="CapitalTransactionsSearch[date_from]" class="form-control"
                   value="<?= Html::encode($searchModel->date_from) ?>">
        </div>
        <div class="form-group">
            <label>إلى تاريخ</label>
            <input type="date" name="CapitalTransactionsSearch[date_to]" class="form-control"
                   value="<?= Html::encode($searchModel->date_to) ?>">
        </div>
        <button type="submit" class="ct-search-btn"><i class="fa fa-search"></i> بحث</button>
        <?php if (!empty($searchModel->transaction_type) || !empty($searchModel->date_from) || !empty($searchModel->date_to)): ?>
            <?= Html::a('<i class="fa fa-times"></i> مسح', $companyId ? ['index', 'company_id' => $companyId] : ['index'], ['class' => 'ct-btn ct-btn-outline', 'style' => 'height:38px']) ?>
        <?php endif ?>
    </form>

    <div class="ct-table-wrap">
        <?php if (count($models) > 0): ?>
            <table class="ct-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <?php if (!$company): ?>
                            <th>المحفظة</th>
                        <?php endif ?>
                        <th>التاريخ</th>
                        <th>نوع العملية</th>
                        <th>المبلغ</th>
                        <th>الرصيد بعد العملية</th>
                        <th>طريقة الدفع</th>
                        <th>رقم المرجع</th>
                        <th>ملاحظات</th>
                        <th style="width:80px">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($models as $m): ?>
                        <?php
                        $isDeposit = $m->transaction_type === 'إيداع';
                        $isWithdraw = $m->transaction_type === 'سحب';
                        $isReturn = $m->transaction_type === 'إعادة_رأس_مال';

                        if ($isDeposit) {
                            $badgeClass = 'ct-badge-deposit';
                            $badgeText = 'إيداع';
                            $amountClass = 'ct-amount-positive';
                            $amountPrefix = '+';
                        } elseif ($isWithdraw) {
                            $badgeClass = 'ct-badge-withdraw';
                            $badgeText = 'سحب';
                            $amountClass = 'ct-amount-negative';
                            $amountPrefix = '-';
                        } else {
                            $badgeClass = 'ct-badge-return';
                            $badgeText = 'إعادة رأس مال';
                            $amountClass = 'ct-amount-negative';
                            $amountPrefix = '-';
                        }
                        ?>
                        <tr>
                            <td style="color:#94a3b8;font-size:12px"><?= $m->id ?></td>
                            <?php if (!$company): ?>
                                <td><?= Html::encode($m->company ? $m->company->name : '—') ?></td>
                            <?php endif ?>
                            <td><?= Html::encode($m->transaction_date) ?></td>
                            <td><span class="ct-badge <?= $badgeClass ?>"><?= $badgeText ?></span></td>
                            <td><span class="<?= $amountClass ?>"><?= $amountPrefix ?> <?= number_format($m->amount, 2) ?></span></td>
                            <td class="ct-amount-neutral"><?= $m->balance_after !== null ? number_format($m->balance_after, 2) : '—' ?></td>
                            <td><?= Html::encode($m->payment_method ?: '—') ?></td>
                            <td><?= Html::encode($m->reference_number ?: '—') ?></td>
                            <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= Html::encode($m->notes) ?>"><?= Html::encode($m->notes ?: '—') ?></td>
                            <td>
                                <div class="ct-actions">
                                    <?= Html::a('<i class="fa fa-eye"></i>', ['view', 'id' => $m->id], ['class' => 'ct-act-view', 'title' => 'عرض']) ?>
                                    <?php if ($canManage): ?>
                                        <?= Html::a('<i class="fa fa-trash"></i>', ['delete', 'id' => $m->id], [
                                            'class' => 'ct-act-del',
                                            'title' => 'حذف',
                                            'data-method' => 'post',
                                            'data-confirm' => 'هل أنت متأكد من حذف هذه الحركة؟',
                                        ]) ?>
                                    <?php endif ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
            <div class="ct-pagination">
                <span>عرض <?= count($models) ?> من <?= $totalCount ?></span>
                <?= \yii\widgets\LinkPager::widget([
                    'pagination' => $pagination,
                    'options' => ['class' => 'pagination pagination-sm'],
                    'linkContainerOptions' => ['class' => ''],
                ]) ?>
            </div>
        <?php else: ?>
            <div class="ct-empty">
                <i class="fa fa-exchange"></i>
                <p>لا توجد حركات رأس مال حالياً</p>
                <?php if ($canManage): ?>
                    <?= Html::a('<i class="fa fa-plus"></i> إضافة حركة جديدة', $companyId ? ['create', 'company_id' => $companyId] : ['create'], ['class' => 'ct-btn ct-btn-primary', 'style' => 'margin-top:12px']) ?>
                <?php endif ?>
            </div>
        <?php endif ?>
    </div>

</div>
