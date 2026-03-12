<?php

use yii\helpers\Url;
use yii\helpers\Html;
use backend\modules\profitDistribution\models\ProfitDistributionModel;
use common\helper\Permissions;

/** @var yii\web\View $this */
/** @var backend\modules\profitDistribution\models\ProfitDistributionSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'توزيع الأرباح';
$this->params['breadcrumbs'][] = $this->title;

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

$totalDistributed = (float) ProfitDistributionModel::find()
    ->where(['status' => [ProfitDistributionModel::STATUS_APPROVED, ProfitDistributionModel::STATUS_DISTRIBUTED]])
    ->sum('distribution_amount') ?: 0;
$pendingCount = (int) ProfitDistributionModel::find()
    ->where(['status' => ProfitDistributionModel::STATUS_DRAFT])
    ->count();
$portfolioCount = (int) ProfitDistributionModel::find()
    ->where(['distribution_type' => ProfitDistributionModel::TYPE_PORTFOLIO])
    ->count();
$shareholderCount = (int) ProfitDistributionModel::find()
    ->where(['distribution_type' => ProfitDistributionModel::TYPE_SHAREHOLDERS])
    ->count();
?>

<style>
:root {
    --pd-primary: #059669;
    --pd-primary-light: #d1fae5;
    --pd-border: #e2e8f0;
    --pd-bg: #f8fafc;
    --pd-r: 12px;
    --pd-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}
.pd-page { padding: 24px; max-width: 1400px; margin: 0 auto; font-family: 'Segoe UI', Tahoma, sans-serif; }

.pd-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
.pd-header h1 { font-size: 22px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 10px; }
.pd-header h1 i { color: var(--pd-primary); font-size: 20px; }
.pd-header-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.pd-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none !important; border: none; cursor: pointer; transition: all .2s; }
.pd-btn-primary { background: var(--pd-primary); color: #fff !important; }
.pd-btn-primary:hover { background: #047857; color: #fff !important; }
.pd-btn-secondary { background: #7c3aed; color: #fff !important; }
.pd-btn-secondary:hover { background: #6d28d9; color: #fff !important; }
.pd-btn-outline { background: #fff; color: #475569 !important; border: 1px solid var(--pd-border); }
.pd-btn-outline:hover { background: var(--pd-bg); }

.pd-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px; }
.pd-stat { background: #fff; border-radius: var(--pd-r); padding: 18px; box-shadow: var(--pd-shadow); border: 1px solid var(--pd-border); display: flex; align-items: center; gap: 14px; }
.pd-stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.pd-stat-value { font-size: 22px; font-weight: 700; color: #1e293b; line-height: 1; }
.pd-stat-label { font-size: 12px; color: #64748b; margin-top: 2px; }

.pd-search { background: #fff; border-radius: var(--pd-r); padding: 16px 20px; box-shadow: var(--pd-shadow); border: 1px solid var(--pd-border); margin-bottom: 20px; display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
.pd-search .form-group { margin-bottom: 0; flex: 1; min-width: 140px; }
.pd-search .form-group label { font-size: 12px; color: #64748b; margin-bottom: 4px; }
.pd-search .form-control { border-radius: 8px; border: 1px solid var(--pd-border); font-size: 13px; height: 38px; }
.pd-search-btn { padding: 8px 20px; border-radius: 8px; background: var(--pd-primary); color: #fff; border: none; font-size: 13px; font-weight: 600; cursor: pointer; height: 38px; white-space: nowrap; }
.pd-search-btn:hover { background: #047857; }

.pd-table-wrap { background: #fff; border-radius: var(--pd-r); box-shadow: var(--pd-shadow); border: 1px solid var(--pd-border); overflow: hidden; }
.pd-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.pd-table thead th { background: var(--pd-bg); padding: 12px 16px; font-weight: 600; color: #475569; border-bottom: 2px solid var(--pd-border); text-align: right; white-space: nowrap; font-size: 12px; text-transform: uppercase; }
.pd-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.pd-table tbody tr:hover { background: #f0fdf4; }
.pd-table tbody td { padding: 14px 16px; color: #334155; vertical-align: middle; }

.pd-badge { display: inline-block; font-size: 11px; font-weight: 600; padding: 2px 10px; border-radius: 6px; }
.pd-badge-portfolio { background: #dbeafe; color: #1d4ed8; }
.pd-badge-shareholders { background: #f3e8ff; color: #7c3aed; }
.pd-badge-draft { background: #fef3c7; color: #b45309; }
.pd-badge-approved { background: #dcfce7; color: #15803d; }
.pd-badge-distributed { background: #d1fae5; color: #059669; }

.pd-actions { display: flex; gap: 6px; }
.pd-actions a { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; font-size: 13px; transition: all .15s; text-decoration: none; }
.pd-act-view { background: #f0fdf4; color: #16a34a; }
.pd-act-view:hover { background: #dcfce7; }

.pd-pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-top: 1px solid var(--pd-border); font-size: 13px; color: #64748b; }
.pd-pagination .pagination { margin: 0; }
.pd-pagination .pagination li a, .pd-pagination .pagination li span { border-radius: 6px; margin: 0 2px; font-size: 13px; padding: 5px 12px; }

.pd-empty { text-align: center; padding: 60px 20px; color: #94a3b8; }
.pd-empty i { font-size: 48px; margin-bottom: 12px; display: block; }
.pd-empty p { font-size: 15px; }

@media (max-width: 768px) {
    .pd-page { padding: 12px; }
    .pd-header { flex-direction: column; align-items: flex-start; }
    .pd-stats { grid-template-columns: 1fr 1fr; }
    .pd-search { flex-direction: column; }
    .pd-table-wrap { overflow-x: auto; }
}
</style>

<div class="pd-page">

    <div class="pd-header">
        <h1><i class="fa fa-line-chart"></i> <?= $this->title ?></h1>
        <div class="pd-header-actions">
            <?php if ($canManage): ?>
                <?= Html::a('<i class="fa fa-calculator"></i> احتساب أرباح محفظة', ['create-portfolio'], ['class' => 'pd-btn pd-btn-primary']) ?>
                <?= Html::a('<i class="fa fa-users"></i> توزيع أرباح على المساهمين', ['create-shareholders'], ['class' => 'pd-btn pd-btn-secondary']) ?>
            <?php endif ?>
        </div>
    </div>

    <div class="pd-stats">
        <div class="pd-stat">
            <div class="pd-stat-icon" style="background:rgba(5,150,105,.1);color:var(--pd-primary)">
                <i class="fa fa-money"></i>
            </div>
            <div>
                <div class="pd-stat-value"><?= number_format($totalDistributed, 2) ?></div>
                <div class="pd-stat-label">إجمالي الموزّع</div>
            </div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-icon" style="background:rgba(245,158,11,.1);color:#f59e0b">
                <i class="fa fa-clock-o"></i>
            </div>
            <div>
                <div class="pd-stat-value"><?= $pendingCount ?></div>
                <div class="pd-stat-label">مسودات معلّقة</div>
            </div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-icon" style="background:rgba(37,99,235,.1);color:#2563eb">
                <i class="fa fa-briefcase"></i>
            </div>
            <div>
                <div class="pd-stat-value"><?= $portfolioCount ?></div>
                <div class="pd-stat-label">توزيعات المحافظ</div>
            </div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-icon" style="background:rgba(124,58,237,.1);color:#7c3aed">
                <i class="fa fa-pie-chart"></i>
            </div>
            <div>
                <div class="pd-stat-value"><?= $shareholderCount ?></div>
                <div class="pd-stat-label">توزيعات المساهمين</div>
            </div>
        </div>
    </div>

    <form method="get" action="<?= Url::to(['index']) ?>" class="pd-search">
        <div class="form-group">
            <label>نوع التوزيع</label>
            <?= Html::dropDownList('ProfitDistributionSearch[distribution_type]', $searchModel->distribution_type, [
                '' => '— الكل —',
                'محفظة' => 'محفظة',
                'مساهمين' => 'مساهمين',
            ], ['class' => 'form-control']) ?>
        </div>
        <div class="form-group">
            <label>الحالة</label>
            <?= Html::dropDownList('ProfitDistributionSearch[status]', $searchModel->status, [
                '' => '— الكل —',
                'مسودة' => 'مسودة',
                'معتمد' => 'معتمد',
                'موزّع' => 'موزّع',
            ], ['class' => 'form-control']) ?>
        </div>
        <button type="submit" class="pd-search-btn"><i class="fa fa-search"></i> بحث</button>
        <?php if (!empty($searchModel->distribution_type) || !empty($searchModel->status)): ?>
            <?= Html::a('<i class="fa fa-times"></i> مسح', ['index'], ['class' => 'pd-btn pd-btn-outline', 'style' => 'height:38px']) ?>
        <?php endif ?>
    </form>

    <div class="pd-table-wrap">
        <?php if (count($models) > 0): ?>
            <table class="pd-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>الفترة</th>
                        <th>النوع</th>
                        <th>المحفظة / الشركة</th>
                        <th>صافي الربح</th>
                        <th>المبلغ الموزّع</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                        <th style="width:80px">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($models as $m): ?>
                        <?php
                        $typeBadge = $m->distribution_type === 'محفظة'
                            ? '<span class="pd-badge pd-badge-portfolio">محفظة</span>'
                            : '<span class="pd-badge pd-badge-shareholders">مساهمين</span>';

                        $statusBadge = '';
                        if ($m->status === 'مسودة') $statusBadge = '<span class="pd-badge pd-badge-draft">مسودة</span>';
                        elseif ($m->status === 'معتمد') $statusBadge = '<span class="pd-badge pd-badge-approved">معتمد</span>';
                        elseif ($m->status === 'موزّع') $statusBadge = '<span class="pd-badge pd-badge-distributed">موزّع</span>';
                        ?>
                        <tr>
                            <td style="color:#94a3b8;font-size:12px"><?= $m->id ?></td>
                            <td><?= Html::encode($m->period_from) ?> — <?= Html::encode($m->period_to) ?></td>
                            <td><?= $typeBadge ?></td>
                            <td><?= $m->company ? Html::encode($m->company->name) : '—' ?></td>
                            <td style="font-weight:600"><?= number_format((float) $m->net_profit, 2) ?></td>
                            <td style="font-weight:600;color:var(--pd-primary)"><?= number_format((float) $m->distribution_amount, 2) ?></td>
                            <td><?= $statusBadge ?></td>
                            <td style="font-size:12px;color:#64748b"><?= $m->created_at ? date('Y-m-d', $m->created_at) : '—' ?></td>
                            <td>
                                <div class="pd-actions">
                                    <?= Html::a('<i class="fa fa-eye"></i>', ['view', 'id' => $m->id], ['class' => 'pd-act-view', 'title' => 'عرض']) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
            <div class="pd-pagination">
                <span>عرض <?= count($models) ?> من <?= $totalCount ?></span>
                <?= \yii\widgets\LinkPager::widget([
                    'pagination' => $pagination,
                    'options' => ['class' => 'pagination pagination-sm'],
                    'linkContainerOptions' => ['class' => ''],
                ]) ?>
            </div>
        <?php else: ?>
            <div class="pd-empty">
                <i class="fa fa-line-chart"></i>
                <p>لا يوجد توزيعات أرباح حالياً</p>
                <?php if ($canManage): ?>
                    <div style="margin-top:16px;display:flex;gap:10px;justify-content:center">
                        <?= Html::a('<i class="fa fa-calculator"></i> احتساب أرباح محفظة', ['create-portfolio'], ['class' => 'pd-btn pd-btn-primary']) ?>
                        <?= Html::a('<i class="fa fa-users"></i> توزيع على المساهمين', ['create-shareholders'], ['class' => 'pd-btn pd-btn-secondary']) ?>
                    </div>
                <?php endif ?>
            </div>
        <?php endif ?>
    </div>

</div>
