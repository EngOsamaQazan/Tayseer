<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

$this->title = 'قائمة التدفقات النقدية';
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = $this->title;

$fyOptions = ArrayHelper::map($fiscalYears, 'id', 'name');

function renderCashFlowSection($items, $title, $icon, $color) {
    $total = 0;
    $html = '<div class="box" style="border-top:3px solid ' . $color . ';">';
    $html .= '<div class="box-header"><h4 class="box-title"><i class="fa ' . $icon . '" style="color:' . $color . ';"></i> ' . $title . '</h4></div>';
    $html .= '<div class="box-body no-padding"><table class="table table-condensed">';
    
    if (empty($items)) {
        $html .= '<tr><td class="text-center text-muted">لا توجد حركات</td></tr>';
    } else {
        foreach ($items as $data) {
            $net = $data['total_debit'] - $data['total_credit'];
            if ($net == 0) continue;
            $total += $net;
            $html .= '<tr>';
            $html .= '<td style="font-family:monospace; width:70px;">' . $data['account']->code . '</td>';
            $html .= '<td>' . Html::encode($data['account']->name_ar) . '</td>';
            $html .= '<td class="text-left" style="font-weight:600; color:' . ($net > 0 ? '#28a745' : '#dc3545') . ';">' . ($net > 0 ? '+' : '') . number_format($net, 2) . '</td>';
            $html .= '</tr>';
        }
    }
    
    $html .= '</table></div>';
    $html .= '<div class="box-footer" style="font-weight:800; font-size:15px;">';
    $html .= 'صافي ' . $title . ': <span style="color:' . ($total >= 0 ? '#28a745' : '#dc3545') . ';">' . ($total >= 0 ? '+' : '') . number_format($total, 2) . '</span>';
    $html .= '</div></div>';
    return [$html, $total];
}
?>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-line-chart"></i> <?= $this->title ?></h3>
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

        <?php
        [$opHtml, $opTotal] = renderCashFlowSection($operating, 'الأنشطة التشغيلية', 'fa-cog', '#17a2b8');
        [$invHtml, $invTotal] = renderCashFlowSection($investing, 'الأنشطة الاستثمارية', 'fa-building', '#ffc107');
        [$finHtml, $finTotal] = renderCashFlowSection($financing, 'الأنشطة التمويلية', 'fa-bank', '#6f42c1');
        $netCashFlow = $opTotal + $invTotal + $finTotal;
        ?>

        <?= $opHtml ?>
        <?= $invHtml ?>
        <?= $finHtml ?>

        <div class="box <?= $netCashFlow >= 0 ? 'box-success' : 'box-danger' ?>" style="margin-top:15px;">
            <div class="box-body text-center" style="padding:25px;">
                <h3 style="margin:0; font-weight:800; color:<?= $netCashFlow >= 0 ? '#28a745' : '#dc3545' ?>;">
                    <i class="fa fa-<?= $netCashFlow >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                    صافي التدفق النقدي: <?= ($netCashFlow >= 0 ? '+' : '') . number_format($netCashFlow, 2) ?>
                </h3>
            </div>
        </div>
    </div>
</div>
