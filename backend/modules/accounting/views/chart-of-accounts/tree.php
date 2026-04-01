<?php

use yii\helpers\Html;
use backend\modules\accounting\models\Account;

$this->title = 'شجرة الحسابات - عرض شجري';
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = ['label' => 'شجرة الحسابات', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'عرض شجري';

$tree = [];
foreach ($accounts as $account) {
    $tree[$account->parent_id ?? 0][] = $account;
}

function renderBranch($parentId, $tree, $typeColors) {
    if (!isset($tree[$parentId])) return '';
    $html = '<ul class="acc-tree-list">';
    foreach ($tree[$parentId] as $account) {
        $hasChildren = isset($tree[$account->id]);
        $icon = $hasChildren ? 'fa-folder-open text-warning' : 'fa-file-text-o text-muted';
        $typeColor = $typeColors[$account->type] ?? 'default';
        $badgeMap = ['primary' => 'badge bg-primary', 'danger' => 'badge bg-danger', 'info' => 'badge bg-info', 'success' => 'badge bg-success', 'warning' => 'badge bg-warning text-dark', 'default' => 'badge bg-secondary'];
        $typeBadgeCls = $badgeMap[$typeColor] ?? 'badge bg-secondary';

        $html .= '<li class="acc-tree-item">';
        $html .= '<div class="acc-tree-node">';
        $html .= '<i class="fa ' . $icon . '"></i> ';
        $html .= '<span class="acc-code">' . Html::encode($account->code) . '</span> ';
        $html .= '<span class="acc-name">' . Html::encode($account->name_ar) . '</span> ';
        $html .= '<span class="' . $typeBadgeCls . '" style="font-size:0.75em">' . Account::getTypes()[$account->type] . '</span>';
        if ($account->opening_balance != 0) {
            $html .= ' <span class="acc-balance">' . number_format($account->opening_balance, 2) . '</span>';
        }
        $html .= '</div>';
        if ($hasChildren) {
            $html .= renderBranch($account->id, $tree, $typeColors);
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}

$typeColors = [
    'assets' => 'primary',
    'liabilities' => 'danger',
    'equity' => 'info',
    'revenue' => 'success',
    'expenses' => 'warning',
];
?>

<style>
.acc-tree-list {
    list-style: none;
    padding-right: 25px;
    margin: 0;
}
.acc-tree-list:first-child {
    padding-right: 0;
}
.acc-tree-item {
    padding: 4px 0;
}
.acc-tree-node {
    padding: 8px 12px;
    border-radius: 6px;
    transition: background 0.15s;
    display: inline-block;
}
.acc-tree-node:hover {
    background: rgba(128, 0, 32, 0.05);
}
.acc-code {
    font-family: monospace;
    font-weight: 700;
    font-size: 14px;
    color: var(--clr-primary, #800020);
}
.acc-name {
    font-weight: 600;
    font-size: 14px;
}
.acc-balance {
    font-size: 12px;
    color: #666;
    margin-right: 8px;
}
.label-xs {
    font-size: 10px;
    padding: 2px 6px;
}
</style>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-tree"></i> <?= $this->title ?></h3>
        <div class="box-tools">
            <?= Html::a('<i class="fa fa-list"></i> عرض جدولي', ['index'], ['class' => 'btn btn-secondary btn-sm']) ?>
            <?= Html::a('<i class="fa fa-plus"></i> إضافة حساب', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
        </div>
    </div>
    <div class="box-body">
        <?= renderBranch(0, $tree, $typeColors) ?>

        <?php if (empty($accounts)): ?>
            <div class="text-center text-muted" style="padding:40px;">
                <i class="fa fa-tree fa-3x"></i>
                <p style="margin-top:15px; font-size:16px;">لا توجد حسابات بعد. أضف حسابك الأول.</p>
                <?= Html::a('<i class="fa fa-plus"></i> إضافة حساب', ['create'], ['class' => 'btn btn-success']) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
