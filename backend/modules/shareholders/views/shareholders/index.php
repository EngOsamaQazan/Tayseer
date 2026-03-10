<?php

use yii\helpers\Url;
use yii\helpers\Html;
use backend\modules\companies\models\Companies;
use backend\widgets\UnifiedSearchWidget;
use common\helper\Permissions;

/** @var yii\web\View $this */
/** @var backend\modules\shareholders\models\ShareholdersSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int $searchCounter */

$this->title = 'المساهمين';
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

$totalSharesAll = (int) \backend\modules\shareholders\models\Shareholders::find()->sum('share_count');
$activeCount = (int) \backend\modules\shareholders\models\Shareholders::find()->where(['is_active' => 1])->count();

$primaryCompany = Companies::find()->where(['is_primary_company' => 1])->one();
$companyTotalShares = $primaryCompany ? (int) $primaryCompany->total_shares : 0;
?>

<style>
:root {
    --sh-primary: #0ea5e9;
    --sh-primary-light: #e0f2fe;
    --sh-success: #059669;
    --sh-border: #e2e8f0;
    --sh-bg: #f8fafc;
    --sh-r: 12px;
    --sh-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}
.sh-page { padding: 24px; max-width: 1400px; margin: 0 auto; font-family: 'Segoe UI', Tahoma, sans-serif; }

.sh-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
.sh-header h1 { font-size: 22px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 10px; }
.sh-header h1 i { color: var(--sh-primary); font-size: 20px; }
.sh-header-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.sh-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none !important; border: none; cursor: pointer; transition: all .2s; }
.sh-btn-primary { background: var(--sh-primary); color: #fff !important; }
.sh-btn-primary:hover { background: #0284c7; color: #fff !important; }
.sh-btn-outline { background: #fff; color: #475569 !important; border: 1px solid var(--sh-border); }
.sh-btn-outline:hover { background: var(--sh-bg); }

.sh-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px; }
.sh-stat { background: #fff; border-radius: var(--sh-r); padding: 18px; box-shadow: var(--sh-shadow); border: 1px solid var(--sh-border); display: flex; align-items: center; gap: 14px; }
.sh-stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.sh-stat-value { font-size: 22px; font-weight: 700; color: #1e293b; line-height: 1; }
.sh-stat-label { font-size: 12px; color: #64748b; margin-top: 2px; }

.sh-search { background: #fff; border-radius: var(--sh-r); padding: 16px 20px; box-shadow: var(--sh-shadow); border: 1px solid var(--sh-border); margin-bottom: 20px; display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
.sh-search .form-group { margin-bottom: 0; flex: 1; min-width: 160px; }
.sh-search .form-group label { font-size: 12px; color: #64748b; margin-bottom: 4px; }
.sh-search .form-control { border-radius: 8px; border: 1px solid var(--sh-border); font-size: 13px; height: 38px; }
.sh-search-btn { padding: 8px 20px; border-radius: 8px; background: var(--sh-primary); color: #fff; border: none; font-size: 13px; font-weight: 600; cursor: pointer; height: 38px; white-space: nowrap; }
.sh-search-btn:hover { background: #0284c7; }

.sh-table-wrap { background: #fff; border-radius: var(--sh-r); box-shadow: var(--sh-shadow); border: 1px solid var(--sh-border); overflow: hidden; }
.sh-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.sh-table thead th { background: var(--sh-bg); padding: 12px 16px; font-weight: 600; color: #475569; border-bottom: 2px solid var(--sh-border); text-align: right; white-space: nowrap; font-size: 12px; text-transform: uppercase; }
.sh-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.sh-table tbody tr:hover { background: #f0f9ff; }
.sh-table tbody td { padding: 14px 16px; color: #334155; vertical-align: middle; }
.sh-table .sh-name { font-weight: 600; color: #1e293b; }
.sh-table .sh-badge-active { display: inline-block; background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 600; padding: 2px 10px; border-radius: 6px; }
.sh-table .sh-badge-inactive { display: inline-block; background: #fee2e2; color: #dc2626; font-size: 11px; font-weight: 600; padding: 2px 10px; border-radius: 6px; }
.sh-table .sh-actions { display: flex; gap: 6px; }
.sh-table .sh-actions a { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; font-size: 13px; transition: all .15s; text-decoration: none; }
.sh-table .sh-act-view { background: #f0fdf4; color: #16a34a; }
.sh-table .sh-act-view:hover { background: #dcfce7; }
.sh-table .sh-act-edit { background: #eff6ff; color: #2563eb; }
.sh-table .sh-act-edit:hover { background: #dbeafe; }
.sh-table .sh-act-del { background: #fef2f2; color: #dc2626; }
.sh-table .sh-act-del:hover { background: #fee2e2; }

.sh-ownership { font-size: 12px; color: #0ea5e9; font-weight: 600; }

.sh-pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-top: 1px solid var(--sh-border); font-size: 13px; color: #64748b; }
.sh-pagination .pagination { margin: 0; }
.sh-pagination .pagination li a, .sh-pagination .pagination li span { border-radius: 6px; margin: 0 2px; font-size: 13px; padding: 5px 12px; }

.sh-empty { text-align: center; padding: 60px 20px; color: #94a3b8; }
.sh-empty i { font-size: 48px; margin-bottom: 12px; display: block; }
.sh-empty p { font-size: 15px; }

@media (max-width: 768px) {
    .sh-page { padding: 12px; }
    .sh-header { flex-direction: column; align-items: flex-start; }
    .sh-stats { grid-template-columns: 1fr 1fr; }
    .sh-search { flex-direction: column; }
    .sh-table-wrap { overflow-x: auto; }
}
</style>

<div class="sh-page">

    <div class="sh-header">
        <h1><i class="fa fa-users"></i> <?= $this->title ?></h1>
        <div class="sh-header-actions">
            <?php if ($canManage): ?>
                <?= Html::a('<i class="fa fa-plus"></i> إضافة مساهم', ['create'], ['class' => 'sh-btn sh-btn-primary']) ?>
            <?php endif ?>
        </div>
    </div>

    <div class="sh-stats">
        <div class="sh-stat">
            <div class="sh-stat-icon" style="background:rgba(14,165,233,.1);color:var(--sh-primary)">
                <i class="fa fa-users"></i>
            </div>
            <div>
                <div class="sh-stat-value"><?= $totalCount ?></div>
                <div class="sh-stat-label">إجمالي المساهمين</div>
            </div>
        </div>
        <div class="sh-stat">
            <div class="sh-stat-icon" style="background:rgba(124,58,237,.1);color:#7c3aed">
                <i class="fa fa-pie-chart"></i>
            </div>
            <div>
                <div class="sh-stat-value"><?= number_format($totalSharesAll) ?></div>
                <div class="sh-stat-label">إجمالي الأسهم</div>
            </div>
        </div>
        <div class="sh-stat">
            <div class="sh-stat-icon" style="background:rgba(5,150,105,.1);color:#059669">
                <i class="fa fa-check-circle"></i>
            </div>
            <div>
                <div class="sh-stat-value"><?= $activeCount ?></div>
                <div class="sh-stat-label">المساهمين النشطين</div>
            </div>
        </div>
    </div>

    <form method="get" action="<?= Url::to(['index']) ?>" class="sh-search" id="sh-search-form">
        <div class="form-group" style="flex:3;min-width:250px">
            <label><i class="fa fa-search"></i> بحث</label>
            <?= UnifiedSearchWidget::widget([
                'name'         => 'ShareholdersSearch[q]',
                'value'        => $searchModel->q,
                'searchUrl'    => Url::to(['search-suggest']),
                'placeholder'  => 'الاسم، رقم الهاتف، رقم الهوية...',
                'formSelector' => '#sh-search-form',
            ]) ?>
        </div>
        <button type="submit" class="sh-search-btn"><i class="fa fa-search"></i> بحث</button>
        <?php if (!empty($searchModel->q)): ?>
            <?= Html::a('<i class="fa fa-times"></i> مسح', ['index'], ['class' => 'sh-btn sh-btn-outline', 'style' => 'height:38px']) ?>
        <?php endif ?>
    </form>

    <div class="sh-table-wrap">
        <?php if (count($models) > 0): ?>
            <table class="sh-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>الاسم</th>
                        <th>الهاتف</th>
                        <th>عدد الأسهم</th>
                        <th>نسبة الملكية</th>
                        <th>تاريخ الانضمام</th>
                        <th>الحالة</th>
                        <th style="width:120px">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($models as $i => $m): ?>
                        <?php
                        $ownershipPct = ($companyTotalShares > 0) ? round(($m->share_count / $companyTotalShares) * 100, 2) : 0;
                        ?>
                        <tr>
                            <td style="color:#94a3b8;font-size:12px"><?= $m->id ?></td>
                            <td><span class="sh-name"><?= Html::encode($m->name) ?></span></td>
                            <td dir="ltr" style="text-align:right"><?= Html::encode($m->phone ?: '—') ?></td>
                            <td><?= number_format($m->share_count) ?></td>
                            <td><span class="sh-ownership"><?= $ownershipPct ?>%</span></td>
                            <td><?= Html::encode($m->join_date ?: '—') ?></td>
                            <td>
                                <?php if ($m->is_active): ?>
                                    <span class="sh-badge-active">فعّال</span>
                                <?php else: ?>
                                    <span class="sh-badge-inactive">غير فعّال</span>
                                <?php endif ?>
                            </td>
                            <td>
                                <div class="sh-actions">
                                    <?= Html::a('<i class="fa fa-eye"></i>', ['view', 'id' => $m->id], ['class' => 'sh-act-view', 'title' => 'عرض']) ?>
                                    <?php if ($canManage): ?>
                                        <?= Html::a('<i class="fa fa-pencil"></i>', ['update', 'id' => $m->id], ['class' => 'sh-act-edit', 'title' => 'تعديل']) ?>
                                        <?= Html::a('<i class="fa fa-trash"></i>', ['delete', 'id' => $m->id], [
                                            'class' => 'sh-act-del',
                                            'title' => 'حذف',
                                            'data-method' => 'post',
                                            'data-confirm' => 'هل أنت متأكد من حذف هذا المساهم؟',
                                        ]) ?>
                                    <?php endif ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
            <div class="sh-pagination">
                <span>عرض <?= count($models) ?> من <?= $totalCount ?></span>
                <?= \yii\widgets\LinkPager::widget([
                    'pagination' => $pagination,
                    'options' => ['class' => 'pagination pagination-sm'],
                    'linkContainerOptions' => ['class' => ''],
                ]) ?>
            </div>
        <?php else: ?>
            <div class="sh-empty">
                <i class="fa fa-users"></i>
                <p>لا يوجد مساهمين حالياً</p>
                <?php if ($canManage): ?>
                    <?= Html::a('<i class="fa fa-plus"></i> إضافة مساهم جديد', ['create'], ['class' => 'sh-btn sh-btn-primary', 'style' => 'margin-top:12px']) ?>
                <?php endif ?>
            </div>
        <?php endif ?>
    </div>

</div>
