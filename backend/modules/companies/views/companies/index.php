<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use common\helper\Permissions;

/** @var yii\web\View $this */
/** @var backend\modules\companies\models\CompaniesSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int $searchCounter */

$this->title = 'المُستثمرين';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCss('.content-header,.page-header { display: none !important; }');

$models = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();
$totalCount = $dataProvider->getTotalCount();

$canCreate = Permissions::can(Permissions::COMP_CREATE);
$canUpdate = Permissions::can(Permissions::COMP_UPDATE);
$canDelete = Permissions::can(Permissions::COMP_DELETE);

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
?>

<style>
:root {
    --inv-primary: #7c3aed;
    --inv-primary-light: #ede9fe;
    --inv-success: #059669;
    --inv-border: #e2e8f0;
    --inv-bg: #f8fafc;
    --inv-r: 12px;
    --inv-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}
.inv-page { padding: 24px; max-width: 1400px; margin: 0 auto; font-family: 'Segoe UI', Tahoma, sans-serif; }

/* Header */
.inv-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
.inv-header h1 { font-size: 22px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 10px; }
.inv-header h1 i { color: var(--inv-primary); font-size: 20px; }
.inv-header-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.inv-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none !important; border: none; cursor: pointer; transition: all .2s; }
.inv-btn-primary { background: var(--inv-primary); color: #fff !important; }
.inv-btn-primary:hover { background: #6d28d9; color: #fff !important; }
.inv-btn-outline { background: #fff; color: #475569 !important; border: 1px solid var(--inv-border); }
.inv-btn-outline:hover { background: var(--inv-bg); }

/* Stats */
.inv-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px; }
.inv-stat { background: #fff; border-radius: var(--inv-r); padding: 18px; box-shadow: var(--inv-shadow); border: 1px solid var(--inv-border); display: flex; align-items: center; gap: 14px; }
.inv-stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.inv-stat-value { font-size: 22px; font-weight: 700; color: #1e293b; line-height: 1; }
.inv-stat-label { font-size: 12px; color: #64748b; margin-top: 2px; }

/* Search */
.inv-search { background: #fff; border-radius: var(--inv-r); padding: 16px 20px; box-shadow: var(--inv-shadow); border: 1px solid var(--inv-border); margin-bottom: 20px; display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
.inv-search .form-group { margin-bottom: 0; flex: 1; min-width: 160px; }
.inv-search .form-group label { font-size: 12px; color: #64748b; margin-bottom: 4px; }
.inv-search .form-control { border-radius: 8px; border: 1px solid var(--inv-border); font-size: 13px; height: 38px; }
.inv-search-btn { padding: 8px 20px; border-radius: 8px; background: var(--inv-primary); color: #fff; border: none; font-size: 13px; font-weight: 600; cursor: pointer; height: 38px; white-space: nowrap; }
.inv-search-btn:hover { background: #6d28d9; }

/* Table */
.inv-table-wrap { background: #fff; border-radius: var(--inv-r); box-shadow: var(--inv-shadow); border: 1px solid var(--inv-border); overflow: hidden; }
.inv-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.inv-table thead th { background: var(--inv-bg); padding: 12px 16px; font-weight: 600; color: #475569; border-bottom: 2px solid var(--inv-border); text-align: right; white-space: nowrap; font-size: 12px; text-transform: uppercase; }
.inv-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.inv-table tbody tr:hover { background: #faf5ff; }
.inv-table tbody td { padding: 14px 16px; color: #334155; vertical-align: middle; }
.inv-table .inv-logo { width: 42px; height: 42px; border-radius: 8px; object-fit: contain; background: #f8fafc; border: 1px solid var(--inv-border); }
.inv-table .inv-name { font-weight: 600; color: #1e293b; }
.inv-table .inv-primary-badge { display: inline-block; background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 6px; margin-right: 6px; }
.inv-table .inv-actions { display: flex; gap: 6px; }
.inv-table .inv-actions a { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; font-size: 13px; transition: all .15s; text-decoration: none; }
.inv-table .inv-act-edit { background: #eff6ff; color: #2563eb; }
.inv-table .inv-act-edit:hover { background: #dbeafe; }
.inv-table .inv-act-del { background: #fef2f2; color: #dc2626; }
.inv-table .inv-act-del:hover { background: #fee2e2; }
.inv-table .inv-act-view { background: #f0fdf4; color: #16a34a; }
.inv-table .inv-act-view:hover { background: #dcfce7; }

/* Pagination */
.inv-pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-top: 1px solid var(--inv-border); font-size: 13px; color: #64748b; }
.inv-pagination .pagination { margin: 0; }
.inv-pagination .pagination li a, .inv-pagination .pagination li span { border-radius: 6px; margin: 0 2px; font-size: 13px; padding: 5px 12px; }

/* Empty */
.inv-empty { text-align: center; padding: 60px 20px; color: #94a3b8; }
.inv-empty i { font-size: 48px; margin-bottom: 12px; display: block; }
.inv-empty p { font-size: 15px; }

@media (max-width: 768px) {
    .inv-page { padding: 12px; }
    .inv-header { flex-direction: column; align-items: flex-start; }
    .inv-stats { grid-template-columns: 1fr 1fr; }
    .inv-search { flex-direction: column; }
    .inv-table-wrap { overflow-x: auto; }
}
</style>

<div class="inv-page">

    <!-- Header -->
    <div class="inv-header">
        <h1><i class="fa fa-building"></i> <?= $this->title ?></h1>
        <div class="inv-header-actions">
            <?php if ($canCreate): ?>
                <?= Html::a('<i class="fa fa-plus"></i> إضافة مُستثمر', ['create'], ['class' => 'inv-btn inv-btn-primary']) ?>
            <?php endif ?>
        </div>
    </div>

    <!-- Stats -->
    <div class="inv-stats">
        <div class="inv-stat">
            <div class="inv-stat-icon" style="background:<?= 'rgba(124,58,237,.1)' ?>;color:var(--inv-primary)">
                <i class="fa fa-building"></i>
            </div>
            <div>
                <div class="inv-stat-value"><?= $totalCount ?></div>
                <div class="inv-stat-label">إجمالي المُستثمرين</div>
            </div>
        </div>
        <div class="inv-stat">
            <div class="inv-stat-icon" style="background:rgba(5,150,105,.1);color:#059669">
                <i class="fa fa-star"></i>
            </div>
            <div>
                <?php
                try {
                    $primaryCount = (int) Yii::$app->db->createCommand("SELECT COUNT(*) FROM os_companies WHERE is_primary_company = 1 AND is_deleted = 0")->queryScalar();
                } catch (\Exception $e) { $primaryCount = 0; }
                ?>
                <div class="inv-stat-value"><?= $primaryCount ?></div>
                <div class="inv-stat-label">الشركة الرئيسية</div>
            </div>
        </div>
        <div class="inv-stat">
            <div class="inv-stat-icon" style="background:rgba(29,78,216,.1);color:#1d4ed8">
                <i class="fa fa-search"></i>
            </div>
            <div>
                <div class="inv-stat-value"><?= $searchCounter ?></div>
                <div class="inv-stat-label">نتائج البحث</div>
            </div>
        </div>
    </div>

    <!-- Search -->
    <form method="get" action="<?= Url::to(['index']) ?>" class="inv-search" id="inv-search-form">
        <div class="form-group" style="flex:3;min-width:250px">
            <label><i class="fa fa-search"></i> بحث</label>
            <input type="text" name="CompaniesSearch[q]" value="<?= Html::encode($searchModel->q) ?>"
                   class="form-control" placeholder="الاسم، رقم الهاتف..."
                   aria-label="بحث" autocomplete="off">
        </div>
        <button type="submit" class="inv-search-btn"><i class="fa fa-search"></i> بحث</button>
        <?php if (!empty($searchModel->q)): ?>
            <?= Html::a('<i class="fa fa-times"></i> مسح', ['index'], ['class' => 'inv-btn inv-btn-outline', 'style' => 'height:38px']) ?>
        <?php endif ?>
    </form>

    <!-- Table -->
    <div class="inv-table-wrap">
        <?php if (count($models) > 0): ?>
            <table class="inv-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th style="width:60px">الشعار</th>
                        <th>الاسم</th>
                        <th>الهاتف</th>
                        <th>البريد الإلكتروني</th>
                        <th>العنوان</th>
                        <th>أنشئ بواسطة</th>
                        <th style="width:120px">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($models as $i => $m): ?>
                        <?php
                        $logo = !empty($m->logo) ? Url::to(['/' . $m->logo]) : Url::to([Yii::$app->params['companies_logo'] ?? '/images/default-company.png']);
                        $createdBy = $m->createdBy->username ?? '—';
                        $isPrimary = $m->is_primary_company ? true : false;
                        ?>
                        <tr>
                            <td style="color:#94a3b8;font-size:12px"><?= $m->id ?></td>
                            <td><img src="<?= $logo ?>" class="inv-logo" alt=""></td>
                            <td>
                                <span class="inv-name"><?= Html::encode($m->name) ?></span>
                                <?php if ($isPrimary): ?>
                                    <span class="inv-primary-badge">رئيسي</span>
                                <?php endif ?>
                            </td>
                            <td dir="ltr" style="text-align:right"><?= Html::encode($m->phone_number) ?></td>
                            <td><?= Html::encode($m->company_email ?: '—') ?></td>
                            <td><?= Html::encode($m->company_address ?: '—') ?></td>
                            <td style="font-size:12px;color:#64748b"><?= Html::encode($createdBy) ?></td>
                            <td>
                                <div class="inv-actions">
                                    <?= Html::a('<i class="fa fa-eye"></i>', ['view', 'id' => $m->id], ['class' => 'inv-act-view', 'title' => 'عرض']) ?>
                                    <?php if ($canUpdate): ?>
                                        <?= Html::a('<i class="fa fa-pencil"></i>', ['update', 'id' => $m->id], ['class' => 'inv-act-edit', 'title' => 'تعديل']) ?>
                                    <?php endif ?>
                                    <?php if ($canDelete): ?>
                                        <?= Html::a('<i class="fa fa-trash"></i>', ['delete', 'id' => $m->id], [
                                            'class' => 'inv-act-del',
                                            'title' => 'حذف',
                                            'data-method' => 'post',
                                            'data-confirm' => 'هل أنت متأكد من حذف هذا المُستثمر؟',
                                        ]) ?>
                                    <?php endif ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
            <div class="inv-pagination">
                <span>عرض <?= count($models) ?> من <?= $totalCount ?></span>
                <?= \yii\widgets\LinkPager::widget([
                    'pagination' => $pagination,
                    'options' => ['class' => 'pagination pagination-sm'],
                    'linkContainerOptions' => ['class' => ''],
                ]) ?>
            </div>
        <?php else: ?>
            <div class="inv-empty">
                <i class="fa fa-building-o"></i>
                <p>لا يوجد مُستثمرين حالياً</p>
                <?php if ($canCreate): ?>
                    <?= Html::a('<i class="fa fa-plus"></i> إضافة مُستثمر جديد', ['create'], ['class' => 'inv-btn inv-btn-primary', 'style' => 'margin-top:12px']) ?>
                <?php endif ?>
            </div>
        <?php endif ?>
    </div>

</div>
