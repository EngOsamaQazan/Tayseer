<?php
/**
 * عرض تفاصيل العقد
 */
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use backend\modules\contracts\models\Contracts;
use backend\modules\inventoryItems\models\ContractInventoryItem;

$this->title = 'العقد #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'العقود', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile(Yii::$app->request->baseUrl . '/css/contract-form.css', ['depends' => [\yii\web\JqueryAsset::class]]);

$customers   = $model->customers;
$guarantors  = $model->customersGuarantor;
$company     = $model->company;
$seller      = $model->seller;

$statusLabels = ['active' => 'نشط', 'judiciary' => 'قضاء', 'legal_department' => 'قانوني', 'finished' => 'منتهي', 'canceled' => 'ملغي', 'settlement' => 'تسوية'];
$statusColors = ['active' => '--cf-ok', 'judiciary' => '--cf-err', 'legal_department' => '--cf-blue', 'finished' => '--cf-text3', 'canceled' => '--cf-text3', 'settlement' => '--cf-teal'];

$items = ContractInventoryItem::find()->where(['contract_id' => $model->id])->all();

$calc = new \backend\modules\followUp\helper\ContractCalculations($model->id);
$totalDebt     = $calc->totalDebt();
$paidAmount    = $calc->paidAmount();
$remaining     = $calc->remainingAmount();
$adjustments   = $calc->totalAdjustments();
$isJudiciaryPaid = $model->isJudiciaryPaid();
?>

<div class="cf">

<!-- ═══ Header ═══ -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px">
    <div style="display:flex;align-items:center;gap:12px">
        <h3 style="margin:0;font-size:20px;font-weight:800;color:var(--cf-navy)"><i class="fa fa-file-text-o"></i> العقد #<?= $model->id ?></h3>
        <?php
            $st = $model->status;
            $stLabel = $statusLabels[$st] ?? $st;
            if ($isJudiciaryPaid) $stLabel = 'قضائي مسدد';
        ?>
        <span style="padding:4px 14px;border-radius:20px;font-size:12px;font-weight:700;color:#fff;background:var(<?= $statusColors[$st] ?? '--cf-text3' ?>)">
            <?= $stLabel ?>
        </span>
    </div>
    <div style="display:flex;gap:8px">
        <?= Html::a('<i class="fa fa-pencil"></i> تعديل', ['update', 'id' => $model->id], ['class' => 'btn cf-btn-save', 'style' => 'height:36px;padding:0 16px;font-size:12.5px']) ?>
        <?= Html::a('<i class="fa fa-print"></i> طباعة', ['print-preview', 'id' => $model->id], ['class' => 'btn cf-btn-print', 'style' => 'height:36px;padding:0 16px;font-size:12.5px']) ?>
        <?= Html::a('<i class="fa fa-arrow-right"></i> العقود', ['index'], ['class' => 'btn', 'style' => 'height:36px;padding:0 16px;font-size:12.5px;background:var(--cf-bg);border:1.5px solid var(--cf-border);color:var(--cf-text2);border-radius:var(--cf-r-sm);font-weight:600']) ?>
    </div>
</div>

<div class="cf-layout">
<!-- ══════════════════════ Main Column ══════════════════════ -->
<div class="cf-main">

<!-- ─── Customer & Guarantors ─── -->
<div class="cf-card">
    <div class="cf-card-hd"><i class="fa fa-user cf-ic-customer"></i><span class="cf-card-title">العميل والكفلاء</span></div>
    <div class="cf-card-bd">
        <div style="margin-bottom:10px">
            <label class="cf-label">العملاء</label>
            <div class="cf-chips">
                <?php if (!empty($customers)): ?>
                    <?php foreach ($customers as $c): ?>
                        <span class="cf-chip">
                            <em class="cf-chip-id">#<?= $c->id ?></em>
                            <?= Html::encode($c->name) ?>
                            <?php if ($c->id_number): ?>
                                <small style="color:var(--cf-text3);font-size:11px;margin-right:4px"><?= Html::encode($c->id_number) ?></small>
                            <?php endif ?>
                        </span>
                    <?php endforeach ?>
                <?php else: ?>
                    <span style="color:var(--cf-text3);font-size:13px">لا يوجد عملاء</span>
                <?php endif ?>
            </div>
        </div>
        <?php if (!empty($guarantors)): ?>
        <div>
            <label class="cf-label">الكفلاء</label>
            <div class="cf-chips">
                <?php foreach ($guarantors as $g): ?>
                    <span class="cf-chip" style="background:#fef3c7;border-color:#fde68a;color:#92400e">
                        <em class="cf-chip-id" style="color:#b45309">#<?= $g->id ?></em>
                        <?= Html::encode($g->name) ?>
                    </span>
                <?php endforeach ?>
            </div>
        </div>
        <?php endif ?>
    </div>
</div>

<!-- ─── Contract Info ─── -->
<div class="cf-card">
    <div class="cf-card-hd"><i class="fa fa-file-text-o cf-ic-contract"></i><span class="cf-card-title">معلومات العقد</span></div>
    <div class="cf-card-bd" style="padding:0">
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <tbody>
                <tr style="border-bottom:1px solid #f1f5f9">
                    <td style="padding:10px 16px;font-weight:700;color:var(--cf-text2);width:140px">رقم العقد</td>
                    <td style="padding:10px 16px;font-weight:600;color:var(--cf-navy)">#<?= $model->id ?></td>
                    <td style="padding:10px 16px;font-weight:700;color:var(--cf-text2);width:140px">النوع</td>
                    <td style="padding:10px 16px"><?= $model->type === 'solidarity' ? 'تضامني' : 'عادي' ?></td>
                </tr>
                <tr style="border-bottom:1px solid #f1f5f9">
                    <td style="padding:10px 16px;font-weight:700;color:var(--cf-text2)">الشركة</td>
                    <td style="padding:10px 16px"><?= $company ? Html::encode($company->name) : '—' ?></td>
                    <td style="padding:10px 16px;font-weight:700;color:var(--cf-text2)">البائع</td>
                    <td style="padding:10px 16px"><?= $seller ? Html::encode($seller->username ?? $seller->name ?? '—') : '—' ?></td>
                </tr>
                <tr style="border-bottom:1px solid #f1f5f9">
                    <td style="padding:10px 16px;font-weight:700;color:var(--cf-text2)">تاريخ البيع</td>
                    <td style="padding:10px 16px"><?= $model->Date_of_sale ?: '—' ?></td>
                    <td style="padding:10px 16px;font-weight:700;color:var(--cf-text2)">تاريخ أول قسط</td>
                    <td style="padding:10px 16px"><?= $model->first_installment_date ?: '—' ?></td>
                </tr>
                <?php if ($model->notes): ?>
                <tr>
                    <td style="padding:10px 16px;font-weight:700;color:var(--cf-text2)">ملاحظات</td>
                    <td colspan="3" style="padding:10px 16px;color:var(--cf-text2);font-size:12.5px"><?= Html::encode($model->notes) ?></td>
                </tr>
                <?php endif ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ─── Devices ─── -->
<?php if (!empty($items)): ?>
<div class="cf-card">
    <div class="cf-card-hd"><i class="fa fa-barcode cf-ic-device"></i><span class="cf-card-title">الأجهزة</span><span class="cf-nav-badge"><?= count($items) ?></span></div>
    <div class="cf-card-bd" style="padding:0">
        <table class="cf-dev-table">
            <thead><tr><th>#</th><th>الجهاز</th><th>الرقم التسلسلي</th><th>النوع</th></tr></thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td class="cf-td-num"><?= $i + 1 ?></td>
                    <td><?= $item->item ? Html::encode($item->item->item_name ?? '—') : '—' ?></td>
                    <td class="cf-td-serial"><?= $item->serialNumber ? Html::encode($item->serialNumber->serial_number) : '—' ?></td>
                    <td><?php if ($item->serial_number_id): ?>
                        <span class="cf-dev-badge serial"><i class="fa fa-barcode"></i> سيريال</span>
                    <?php else: ?>
                        <span class="cf-dev-badge manual"><i class="fa fa-cube"></i> يدوي</span>
                    <?php endif ?></td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>

<!-- ─── Adjustments ─── -->
<?= $this->render('_adjustments', ['contract_id' => $model->id]) ?>

</div><!-- /cf-main -->

<!-- ══════════════════════ Sidebar ══════════════════════ -->
<aside class="cf-sidebar">
    <div class="cf-summary">
        <div class="cf-sum-hd"><h4><i class="fa fa-calculator"></i> الحسابات المالية</h4></div>
        <div class="cf-sum-bd">
            <?php if (!empty($items)): ?>
            <div class="cf-sum-devices"><i class="fa fa-mobile"></i> الأجهزة: <b><?= count($items) ?></b></div>
            <?php endif ?>
            <div class="cf-sum-row"><span class="cf-sum-label">إجمالي العقد</span><span class="cf-sum-val big"><?= number_format($model->total_value, 0) ?> د.أ</span></div>
            <div class="cf-sum-row"><span class="cf-sum-label">الدفعة الأولى</span><span class="cf-sum-val"><?= number_format($model->first_installment_value ?? 0, 0) ?> د.أ</span></div>
            <div class="cf-sum-row"><span class="cf-sum-label">القسط الشهري</span><span class="cf-sum-val ok"><?= number_format($model->monthly_installment_value ?? 0, 0) ?> د.أ</span></div>
            <?php if ($model->commitment_discount): ?>
            <div class="cf-sum-row"><span class="cf-sum-label">خصم الالتزام</span><span class="cf-sum-val"><?= number_format($model->commitment_discount, 0) ?> د.أ</span></div>
            <?php endif ?>

            <div class="cf-sum-divider"></div>

            <div class="cf-sum-row"><span class="cf-sum-label">إجمالي الدين</span><span class="cf-sum-val" style="font-weight:800"><?= number_format($totalDebt, 2) ?> د.أ</span></div>
            <div class="cf-sum-row"><span class="cf-sum-label">المدفوع</span><span class="cf-sum-val ok"><?= number_format($paidAmount, 2) ?> د.أ</span></div>
            <?php if ($adjustments > 0): ?>
            <div class="cf-sum-row"><span class="cf-sum-label">الخصومات</span><span class="cf-sum-val" style="color:var(--cf-warn)">-<?= number_format($adjustments, 2) ?> د.أ</span></div>
            <?php endif ?>

            <div class="cf-sum-divider"></div>

            <div class="cf-sum-row">
                <span class="cf-sum-label" style="font-weight:700;font-size:13px">المتبقي</span>
                <span class="cf-sum-val big" style="color:<?= $remaining <= 0 ? 'var(--cf-ok)' : 'var(--cf-err)' ?>"><?= number_format($remaining, 2) ?> د.أ</span>
            </div>

            <?php if ($remaining <= 0): ?>
            <div style="text-align:center;padding:8px;background:var(--cf-ok-l);border-radius:var(--cf-r-sm);color:var(--cf-ok);font-size:12px;font-weight:700;margin-top:8px">
                <i class="fa fa-check-circle"></i> تم السداد بالكامل
            </div>
            <?php endif ?>
        </div>

        <div class="cf-sum-actions">
            <?= Html::a('<i class="fa fa-pencil"></i> تعديل العقد', ['update', 'id' => $model->id], ['class' => 'btn cf-btn-save']) ?>
            <?= Html::a('<i class="fa fa-print"></i> طباعة', ['print-preview', 'id' => $model->id], ['class' => 'btn cf-btn-print']) ?>
            <?= Html::a('<i class="fa fa-comments-o"></i> المتابعة', ['/followUp/follow-up/panel', 'contract_id' => $model->id], ['class' => 'btn', 'style' => 'background:var(--cf-teal);color:#fff;border:none']) ?>
        </div>
    </div>
</aside>

</div><!-- /cf-layout -->
</div>
