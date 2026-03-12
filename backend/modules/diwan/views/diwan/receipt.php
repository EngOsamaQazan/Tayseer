<?php
/**
 * قسم الديوان — إيصال المعاملة (للطباعة)
 */

use yii\helpers\Html;

$this->title = 'قسم الديوان';
?>

<?= $this->render('@app/views/layouts/_diwan-tabs', ['activeTab' => 'transactions']) ?>

<style>
@media print {
    .no-print, .fin-tabs-bar { display: none !important; }
    .sidebar-wrapper, .topbar, .page-footer, .breadcrumb-title, .main-sidebar, .main-header, .main-footer { display: none !important; }
    .content-wrapper { margin: 0 !important; padding: 0 !important; }
    body { background: #fff !important; }
}
.dw-receipt { max-width: 800px; margin: 0 auto; background: #fff; padding: 36px; }
.dw-receipt-hdr { text-align: center; border-bottom: 3px double var(--fin-primary,#800020); padding-bottom: 18px; margin-bottom: 22px; }
.dw-receipt-hdr h1 { font-size: 22px; font-weight: 800; color: var(--fin-primary,#800020); margin: 0 0 4px; }
.dw-receipt-hdr .sub { font-size: 13px; color: #777; }
.dw-receipt-hdr .rnum { font-size: 16px; font-weight: 700; color: #333; margin-top: 10px; }

.dw-rmeta { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 22px; }
.dw-rmeta .mi { padding: 10px 14px; background: #f8f9fa; border-radius: 6px; }
.dw-rmeta .ml { font-size: 10px; color: #888; font-weight: 700; }
.dw-rmeta .mv { font-size: 14px; color: #333; font-weight: 600; }

.dw-rtable { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
.dw-rtable th { background: var(--fin-primary,#800020); color: #fff; padding: 8px 12px; font-size: 12px; font-weight: 700; }
.dw-rtable td { padding: 8px 12px; border-bottom: 1px solid #eee; font-size: 12px; }
.dw-rtable tr:nth-child(even) { background: #fafafa; }

.dw-sig-row { display: grid; grid-template-columns: 1fr 1fr; gap: 36px; margin-top: 44px; }
.dw-sig-box { text-align: center; padding-top: 16px; }
.dw-sig-box .sl { font-weight: 700; color: #555; margin-bottom: 4px; }
.dw-sig-box .sn { font-size: 13px; color: #333; margin-bottom: 36px; }
.dw-sig-box .sline { border-top: 1px solid #999; width: 180px; margin: 0 auto; padding-top: 4px; font-size: 11px; color: #888; }

.dw-print-btn {
    display: inline-flex; align-items: center; gap: 6px; padding: 10px 24px;
    background: var(--fin-primary,#800020); color: #fff; border: none; border-radius: 8px;
    font-weight: 700; font-size: 13px; cursor: pointer;
}
.dw-print-btn:hover { background: #5e1430; }
</style>

<div class="no-print" style="text-align:center; margin-bottom:16px;">
    <button onclick="window.print()" class="dw-print-btn">
        <i class="fa fa-print"></i> طباعة الإيصال
    </button>
    <?= Html::a('<i class="fa fa-arrow-right"></i> رجوع', ['view', 'id' => $model->id], [
        'class' => 'btn btn-default btn-sm', 'style' => 'border-radius:6px; margin-right:6px;',
    ]) ?>
</div>

<div class="dw-receipt">
    <div class="dw-receipt-hdr">
        <h1>شركة جدل للأجهزة الكهربائية</h1>
        <div class="sub">قسم الديوان — إيصال استلام / تسليم وثائق</div>
        <div class="rnum">إيصال رقم: <?= Html::encode($model->receipt_number) ?></div>
    </div>

    <div class="dw-rmeta">
        <div class="mi">
            <div class="ml">نوع المعاملة</div>
            <div class="mv"><?= Html::encode($model->transaction_type) ?></div>
        </div>
        <div class="mi">
            <div class="ml">التاريخ</div>
            <div class="mv"><?= Yii::$app->formatter->asDatetime($model->transaction_date, 'php:Y/m/d h:i A') ?></div>
        </div>
        <div class="mi">
            <div class="ml">من موظف</div>
            <div class="mv"><?= Html::encode($model->fromEmployee ? ($model->fromEmployee->name ?: $model->fromEmployee->username) : '—') ?></div>
        </div>
        <div class="mi">
            <div class="ml">إلى موظف</div>
            <div class="mv"><?= Html::encode($model->toEmployee ? ($model->toEmployee->name ?: $model->toEmployee->username) : '—') ?></div>
        </div>
    </div>

    <?php if (!empty($model->notes)): ?>
    <div style="background:#fffde7; padding:10px 14px; border-radius:6px; margin-bottom:18px;">
        <strong>ملاحظات:</strong> <?= nl2br(Html::encode($model->notes)) ?>
    </div>
    <?php endif; ?>

    <table class="dw-rtable">
        <thead>
            <tr>
                <th style="width:50px">#</th>
                <th>رقم العقد</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($model->details as $i => $detail): ?>
            <tr>
                <td style="font-weight:700; text-align:center;"><?= $i + 1 ?></td>
                <td><?= Html::encode($detail->contract_number) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="text-align:center; font-size:12px; color:#888; margin-bottom:10px;">
        إجمالي العقود: <strong style="color:var(--fin-primary,#800020);"><?= count($model->details) ?></strong>
    </div>

    <div class="dw-sig-row">
        <div class="dw-sig-box">
            <div class="sl">المُسلِّم</div>
            <div class="sn"><?= Html::encode($model->fromEmployee ? ($model->fromEmployee->name ?: $model->fromEmployee->username) : '—') ?></div>
            <div class="sline">التوقيع</div>
        </div>
        <div class="dw-sig-box">
            <div class="sl">المُستلِم</div>
            <div class="sn"><?= Html::encode($model->toEmployee ? ($model->toEmployee->name ?: $model->toEmployee->username) : '—') ?></div>
            <div class="sline">التوقيع</div>
        </div>
    </div>
</div>
