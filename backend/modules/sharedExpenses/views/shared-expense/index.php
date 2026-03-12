<?php

use yii\helpers\Url;
use yii\helpers\Html;
use backend\modules\sharedExpenses\models\SharedExpenseAllocation;
use common\helper\Permissions;

/** @var yii\web\View $this */
/** @var backend\modules\sharedExpenses\models\SharedExpenseAllocationSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int $totalAllocations */
/** @var float $totalDistributed */
/** @var int $draftCount */
/** @var int $approvedCount */

$this->title = 'توزيع المصاريف المشتركة';
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

$methods = SharedExpenseAllocation::getAllocationMethods();
?>

<style>
:root {
    --se-primary: #8b5cf6;
    --se-primary-light: #ede9fe;
    --se-primary-dark: #7c3aed;
    --se-success: #059669;
    --se-warning: #d97706;
    --se-border: #e2e8f0;
    --se-bg: #f8fafc;
    --se-r: 12px;
    --se-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}
.se-page { padding: 24px; max-width: 1400px; margin: 0 auto; font-family: 'Segoe UI', Tahoma, sans-serif; }

.se-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
.se-header h1 { font-size: 22px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 10px; }
.se-header h1 i { color: var(--se-primary); font-size: 20px; }
.se-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none !important; border: none; cursor: pointer; transition: all .2s; }
.se-btn-primary { background: var(--se-primary); color: #fff !important; }
.se-btn-primary:hover { background: var(--se-primary-dark); color: #fff !important; }

.se-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px; }
.se-stat { background: #fff; border-radius: var(--se-r); padding: 18px; box-shadow: var(--se-shadow); border: 1px solid var(--se-border); display: flex; align-items: center; gap: 14px; }
.se-stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.se-stat-value { font-size: 22px; font-weight: 700; color: #1e293b; line-height: 1; }
.se-stat-label { font-size: 12px; color: #64748b; margin-top: 2px; }

.se-table-wrap { background: #fff; border-radius: var(--se-r); box-shadow: var(--se-shadow); border: 1px solid var(--se-border); overflow: hidden; }
.se-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.se-table thead th { background: var(--se-bg); padding: 12px 16px; font-weight: 600; color: #475569; border-bottom: 2px solid var(--se-border); text-align: right; white-space: nowrap; font-size: 12px; }
.se-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.se-table tbody tr:hover { background: #faf5ff; }
.se-table tbody td { padding: 14px 16px; color: #334155; vertical-align: middle; }
.se-table .se-name { font-weight: 600; color: #1e293b; }
.se-badge { display: inline-block; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 6px; }
.se-badge-draft { background: #fef3c7; color: #92400e; }
.se-badge-approved { background: #dcfce7; color: #15803d; }
.se-badge-method { background: var(--se-primary-light); color: var(--se-primary-dark); }
.se-actions { display: flex; gap: 6px; }
.se-actions a { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; font-size: 13px; transition: all .15s; text-decoration: none; }
.se-act-view { background: #f0fdf4; color: #16a34a; }
.se-act-view:hover { background: #dcfce7; }
.se-act-edit { background: #eff6ff; color: #2563eb; }
.se-act-edit:hover { background: #dbeafe; }
.se-act-del { background: #fef2f2; color: #dc2626; }
.se-act-del:hover { background: #fee2e2; }

.se-pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-top: 1px solid var(--se-border); font-size: 13px; color: #64748b; }
.se-pagination .pagination { margin: 0; }
.se-pagination .pagination li a, .se-pagination .pagination li span { border-radius: 6px; margin: 0 2px; font-size: 13px; padding: 5px 12px; }

.se-empty { text-align: center; padding: 60px 20px; color: #94a3b8; }
.se-empty i { font-size: 48px; margin-bottom: 12px; display: block; color: var(--se-primary); opacity: .4; }
.se-empty p { font-size: 15px; }

@media (max-width: 768px) {
    .se-page { padding: 12px; }
    .se-header { flex-direction: column; align-items: flex-start; }
    .se-stats { grid-template-columns: 1fr 1fr; }
    .se-table-wrap { overflow-x: auto; }
}
</style>

<div class="se-page">

    <div class="se-header">
        <h1><i class="fa fa-share-alt"></i> <?= $this->title ?></h1>
        <div>
            <?= Html::a('<i class="fa fa-plus"></i> إنشاء توزيع جديد', ['create'], ['class' => 'se-btn se-btn-primary']) ?>
        </div>
    </div>

    <div class="se-stats">
        <div class="se-stat">
            <div class="se-stat-icon" style="background:rgba(139,92,246,.1);color:var(--se-primary)">
                <i class="fa fa-share-alt"></i>
            </div>
            <div>
                <div class="se-stat-value"><?= $totalAllocations ?></div>
                <div class="se-stat-label">إجمالي التوزيعات</div>
            </div>
        </div>
        <div class="se-stat">
            <div class="se-stat-icon" style="background:rgba(5,150,105,.1);color:#059669">
                <i class="fa fa-money"></i>
            </div>
            <div>
                <div class="se-stat-value"><?= number_format($totalDistributed, 2) ?></div>
                <div class="se-stat-label">إجمالي المبالغ الموزّعة</div>
            </div>
        </div>
        <div class="se-stat">
            <div class="se-stat-icon" style="background:rgba(217,119,6,.1);color:#d97706">
                <i class="fa fa-clock-o"></i>
            </div>
            <div>
                <div class="se-stat-value"><?= $draftCount ?></div>
                <div class="se-stat-label">مسودات</div>
            </div>
        </div>
        <div class="se-stat">
            <div class="se-stat-icon" style="background:rgba(5,150,105,.1);color:#059669">
                <i class="fa fa-check-circle"></i>
            </div>
            <div>
                <div class="se-stat-value"><?= $approvedCount ?></div>
                <div class="se-stat-label">معتمدة</div>
            </div>
        </div>
    </div>

    <div class="se-table-wrap">
        <?php if (count($models) > 0): ?>
            <table class="se-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>اسم التوزيع</th>
                        <th>المبلغ الإجمالي</th>
                        <th>طريقة التوزيع</th>
                        <th>تاريخ التوزيع</th>
                        <th>الحالة</th>
                        <th style="width:120px">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($models as $m): ?>
                        <tr>
                            <td style="color:#94a3b8;font-size:12px"><?= $m->id ?></td>
                            <td><span class="se-name"><?= Html::encode($m->name) ?></span></td>
                            <td style="font-weight:600"><?= number_format($m->total_amount, 2) ?></td>
                            <td><span class="se-badge se-badge-method"><?= Html::encode($methods[$m->allocation_method] ?? $m->allocation_method) ?></span></td>
                            <td><?= Html::encode($m->allocation_date) ?></td>
                            <td>
                                <?php if ($m->status === 'معتمد'): ?>
                                    <span class="se-badge se-badge-approved">معتمد</span>
                                <?php else: ?>
                                    <span class="se-badge se-badge-draft">مسودة</span>
                                <?php endif ?>
                            </td>
                            <td>
                                <div class="se-actions">
                                    <?= Html::a('<i class="fa fa-eye"></i>', ['view', 'id' => $m->id], ['class' => 'se-act-view', 'title' => 'عرض']) ?>
                                    <?php if ($m->status !== 'معتمد'): ?>
                                        <?= Html::a('<i class="fa fa-pencil"></i>', ['update', 'id' => $m->id], ['class' => 'se-act-edit', 'title' => 'تعديل']) ?>
                                        <?= Html::a('<i class="fa fa-trash"></i>', ['delete', 'id' => $m->id], [
                                            'class' => 'se-act-del',
                                            'title' => 'حذف',
                                            'data-method' => 'post',
                                            'data-confirm' => 'هل أنت متأكد من حذف هذا التوزيع؟',
                                        ]) ?>
                                    <?php endif ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
            <div class="se-pagination">
                <span>عرض <?= count($models) ?> من <?= $totalCount ?></span>
                <?= \yii\widgets\LinkPager::widget([
                    'pagination' => $pagination,
                    'options' => ['class' => 'pagination pagination-sm'],
                ]) ?>
            </div>
        <?php else: ?>
            <div class="se-empty">
                <i class="fa fa-share-alt"></i>
                <p>لا يوجد توزيعات مصاريف مشتركة حالياً</p>
                <?= Html::a('<i class="fa fa-plus"></i> إنشاء توزيع جديد', ['create'], ['class' => 'se-btn se-btn-primary', 'style' => 'margin-top:12px']) ?>
            </div>
        <?php endif ?>
    </div>

</div>
