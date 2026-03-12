<?php
/**
 * عرض تفاصيل فاتورة المخزون — تصميم متوافق مع النظام
 */
use yii\helpers\Html;
use yii\helpers\Url;
use common\helper\Permissions;
use backend\modules\inventoryInvoices\models\InventoryInvoices;

/* @var $this yii\web\View */
/* @var $model InventoryInvoices */

$isAjax = Yii::$app->request->isAjax;
$this->title = 'فاتورة #' . $model->id;
if (!$isAjax) {
    $this->params['breadcrumbs'][] = ['label' => 'إدارة المخزون', 'url' => ['index']];
    $this->params['breadcrumbs'][] = $this->title;
}
$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);

$statusLabels = InventoryInvoices::getStatusList();
$statusLabel = $statusLabels[$model->status] ?? $model->status;

$statusColors = [
    InventoryInvoices::STATUS_DRAFT             => ['bg' => '#f1f5f9', 'color' => '#64748b', 'icon' => 'fa-pencil'],
    InventoryInvoices::STATUS_PENDING_RECEPTION  => ['bg' => '#fef3c7', 'color' => '#d97706', 'icon' => 'fa-clock-o'],
    InventoryInvoices::STATUS_APPROVED_SALES     => ['bg' => '#e0f2fe', 'color' => '#0369a1', 'icon' => 'fa-check'],
    InventoryInvoices::STATUS_PENDING_MANAGER    => ['bg' => '#fef3c7', 'color' => '#d97706', 'icon' => 'fa-hourglass-half'],
    InventoryInvoices::STATUS_APPROVED_FINAL     => ['bg' => '#dcfce7', 'color' => '#15803d', 'icon' => 'fa-check-circle'],
    InventoryInvoices::STATUS_REJECTED_MANAGER   => ['bg' => '#fee2e2', 'color' => '#b91c1c', 'icon' => 'fa-times-circle'],
];
$sc = $statusColors[$model->status] ?? $statusColors[InventoryInvoices::STATUS_DRAFT];

$typeLabels = InventoryInvoices::getTypeList();
$typeLabel = $typeLabels[$model->type] ?? '—';
$typeColors = [0 => ['bg' => '#dcfce7', 'c' => '#15803d'], 1 => ['bg' => '#fef3c7', 'c' => '#d97706'], 2 => ['bg' => '#e0f2fe', 'c' => '#0369a1']];
$tc = $typeColors[$model->type] ?? $typeColors[0];

$supplierName = $model->suppliers ? $model->suppliers->name : '—';
$companyName = $model->company ? $model->company->name : '—';
$locationName = $model->stockLocation ? $model->stockLocation->locations_name : '—';
$createdByName = $model->createdBy ? ($model->createdBy->profile->name ?? $model->createdBy->username) : '—';
$approvedByName = $model->approvedByUser ? ($model->approvedByUser->profile->name ?? $model->approvedByUser->username) : null;
$lineItems = $model->lineItems;
$netTotal = ($model->total_amount ?: 0) - ($model->discount_amount ?: 0);
?>

<?php if (!$isAjax): ?>
<?= $this->render('@app/views/layouts/_inventory-tabs', ['activeTab' => 'invoices']) ?>
<?php endif ?>

<style>
.inv-view { --fin-border:#e2e8f0; --fin-r:10px; --fin-shadow:0 1px 3px rgba(0,0,0,.05); --fin-surface:#fff; max-width:960px; margin:0 auto; font-family:'Cairo','Segoe UI',Tahoma,sans-serif; }
.inv-view-hdr { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px; margin-bottom:24px; }
.inv-view-hdr h2 { margin:0; font-size:22px; font-weight:800; color:#1e293b; display:flex; align-items:center; gap:10px; }
.inv-view-hdr h2 .inv-id { color:#0369a1; }
.inv-status-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 16px; border-radius:20px; font-size:13px; font-weight:700; }
.inv-view-actions { display:flex; flex-wrap:wrap; gap:8px; }
.inv-view-actions .btn { font-weight:700; border-radius:8px; padding:8px 18px; font-size:13px; }

.inv-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:14px; margin-bottom:20px; }
.inv-card { background:var(--fin-surface); border:1px solid var(--fin-border); border-radius:var(--fin-r); padding:16px 18px; box-shadow:var(--fin-shadow); }
.inv-card-lbl { font-size:11.5px; font-weight:700; color:#94a3b8; margin-bottom:4px; text-transform:uppercase; letter-spacing:.3px; }
.inv-card-val { font-size:15px; font-weight:700; color:#1e293b; }
.inv-card-val.inv-amount { font-size:20px; color:#0369a1; font-family:'Cairo',monospace; }
.inv-card-val .inv-pill { display:inline-flex; align-items:center; gap:4px; padding:3px 12px; border-radius:16px; font-size:12px; font-weight:700; }

.inv-section { background:var(--fin-surface); border:1px solid var(--fin-border); border-radius:var(--fin-r); box-shadow:var(--fin-shadow); margin-bottom:20px; overflow:hidden; }
.inv-section-hdr { padding:14px 20px; font-size:14px; font-weight:800; color:#334155; background:#f8fafc; border-bottom:1px solid var(--fin-border); display:flex; align-items:center; gap:8px; }
.inv-section-body { padding:0; }
.inv-section-body table { width:100%; border-collapse:collapse; }
.inv-section-body th { padding:10px 16px; font-size:12px; font-weight:700; color:#64748b; background:#f8fafc; text-align:right; border-bottom:1px solid var(--fin-border); }
.inv-section-body td { padding:12px 16px; font-size:13.5px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.inv-section-body tr:last-child td { border-bottom:none; }
.inv-section-body .inv-total-row td { font-weight:800; font-size:14px; background:#f8fafc; border-top:2px solid var(--fin-border); }

.inv-meta { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:12px; padding:16px 20px; }
.inv-meta-item { display:flex; flex-direction:column; }
.inv-meta-lbl { font-size:11px; color:#94a3b8; font-weight:600; }
.inv-meta-val { font-size:13px; color:#475569; font-weight:600; }

.inv-rejection { background:#fef2f2; border:1px solid #fecaca; border-radius:var(--fin-r); padding:16px 20px; margin-bottom:20px; }
.inv-rejection strong { color:#b91c1c; }

.inv-flash { margin-bottom:16px; }
.inv-flash .alert { border-radius:var(--fin-r); font-weight:600; }

@media(max-width:600px){
    .inv-cards{grid-template-columns:1fr 1fr;}
    .inv-view-hdr{flex-direction:column;align-items:flex-start;}
    .inv-meta{grid-template-columns:1fr;}
}
</style>

<div class="inv-view">
    <?php foreach (Yii::$app->session->getAllFlashes() as $type => $message): ?>
    <div class="inv-flash">
        <div class="alert alert-<?= $type === 'error' ? 'danger' : Html::encode($type) ?>">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?= $message ?>
        </div>
    </div>
    <?php endforeach ?>

    <!-- ═══ Header ═══ -->
    <div class="inv-view-hdr">
        <div>
            <h2>
                <i class="fa fa-file-text-o" style="color:#94a3b8"></i>
                فاتورة <span class="inv-id">#<?= $model->id ?></span>
                <?php if ($model->invoice_number): ?>
                    <small style="font-size:14px;color:#64748b;font-weight:600">(<?= Html::encode($model->invoice_number) ?>)</small>
                <?php endif ?>
            </h2>
            <span class="inv-status-badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;margin-top:8px">
                <i class="fa <?= $sc['icon'] ?>"></i> <?= Html::encode($statusLabel) ?>
            </span>
        </div>
        <?php if (!$isAjax): ?>
        <div class="inv-view-actions">
            <?= Html::a('<i class="fa fa-arrow-right"></i> العودة للقائمة', ['index'], ['class' => 'btn btn-default']) ?>
            <?php if (Permissions::can(Permissions::INVINV_UPDATE)): ?>
                <?= Html::a('<i class="fa fa-pencil"></i> تعديل', ['update', 'id' => $model->id], ['class' => 'btn btn-info']) ?>
            <?php endif ?>
        </div>
        <?php endif ?>
    </div>

    <!-- ═══ Info Cards ═══ -->
    <div class="inv-cards">
        <div class="inv-card">
            <div class="inv-card-lbl"><i class="fa fa-map-marker"></i> موقع التخزين</div>
            <div class="inv-card-val"><?= Html::encode($locationName) ?></div>
        </div>
        <div class="inv-card">
            <div class="inv-card-lbl"><i class="fa fa-truck"></i> المورد</div>
            <div class="inv-card-val"><?= Html::encode($supplierName) ?></div>
        </div>
        <div class="inv-card">
            <div class="inv-card-lbl"><i class="fa fa-building"></i> الشركة</div>
            <div class="inv-card-val"><?= Html::encode($companyName) ?></div>
        </div>
        <div class="inv-card">
            <div class="inv-card-lbl"><i class="fa fa-credit-card"></i> طريقة الدفع</div>
            <div class="inv-card-val">
                <span class="inv-pill" style="background:<?= $tc['bg'] ?>;color:<?= $tc['c'] ?>"><?= Html::encode($typeLabel) ?></span>
            </div>
        </div>
        <div class="inv-card">
            <div class="inv-card-lbl"><i class="fa fa-calendar"></i> التاريخ</div>
            <div class="inv-card-val"><?= Html::encode($model->date ?: '—') ?></div>
        </div>
        <div class="inv-card">
            <div class="inv-card-lbl"><i class="fa fa-money"></i> المبلغ الإجمالي</div>
            <div class="inv-card-val inv-amount"><?= number_format($model->total_amount ?: 0, 2) ?></div>
        </div>
        <?php if ($model->discount_amount > 0): ?>
        <div class="inv-card">
            <div class="inv-card-lbl"><i class="fa fa-tag"></i> الخصم</div>
            <div class="inv-card-val" style="color:#d97706"><?= number_format($model->discount_amount, 2) ?></div>
        </div>
        <div class="inv-card">
            <div class="inv-card-lbl"><i class="fa fa-calculator"></i> الصافي</div>
            <div class="inv-card-val inv-amount"><?= number_format($netTotal, 2) ?></div>
        </div>
        <?php endif ?>
    </div>

    <?php if ($model->rejection_reason): ?>
    <div class="inv-rejection">
        <strong><i class="fa fa-exclamation-triangle"></i> سبب الرفض:</strong>
        <?= Html::encode($model->rejection_reason) ?>
    </div>
    <?php endif ?>

    <?php if ($model->invoice_notes): ?>
    <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:var(--fin-r);padding:14px 20px;margin-bottom:20px;color:#0c4a6e;font-weight:600;">
        <i class="fa fa-sticky-note-o"></i> <strong>ملاحظات:</strong> <?= Html::encode($model->invoice_notes) ?>
    </div>
    <?php endif ?>

    <!-- ═══ Line Items ═══ -->
    <?php if (!empty($lineItems)): ?>
    <div class="inv-section">
        <div class="inv-section-hdr">
            <i class="fa fa-cubes"></i> بنود الفاتورة
            <span style="background:#e0e7ff;color:#4338ca;padding:2px 10px;border-radius:12px;font-size:12px;margin-right:8px"><?= count($lineItems) ?> صنف</span>
        </div>
        <div class="inv-section-body">
            <table>
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>الصنف</th>
                        <th style="width:90px">الكمية</th>
                        <th style="width:110px">سعر الوحدة</th>
                        <th style="width:120px">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $runningTotal = 0;
                    foreach ($lineItems as $i => $line):
                        $itemName = '—';
                        $item = \backend\modules\inventoryItems\models\InventoryItems::findOne($line->inventory_items_id);
                        if ($item) $itemName = $item->name;
                        $lineTotal = ($line->number ?: 0) * ($line->single_price ?: 0);
                        $runningTotal += $lineTotal;
                    ?>
                    <tr>
                        <td style="color:#94a3b8;font-weight:700"><?= $i + 1 ?></td>
                        <td><strong><?= Html::encode($itemName) ?></strong></td>
                        <td style="direction:ltr;text-align:center"><?= (int) $line->number ?></td>
                        <td style="direction:ltr;text-align:center"><?= number_format($line->single_price ?: 0, 2) ?></td>
                        <td style="direction:ltr;text-align:center;font-weight:700"><?= number_format($lineTotal, 2) ?></td>
                    </tr>
                    <?php endforeach ?>
                    <tr class="inv-total-row">
                        <td colspan="4" style="text-align:left">المجموع</td>
                        <td style="direction:ltr;text-align:center;color:#0369a1"><?= number_format($runningTotal, 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif ?>

    <!-- ═══ Approval Actions ═══ -->
    <?php if (!$model->isNewRecord): ?>
    <?php
        $user = Yii::$app->user->identity;
        $isBranchSales = $user && method_exists($user, 'hasCategory') && $user->hasCategory('sales_employee');
        $canApprove = Permissions::can(Permissions::INVINV_APPROVE);
    ?>
    <?php if ($canApprove && ($model->status === InventoryInvoices::STATUS_PENDING_RECEPTION || $model->status === InventoryInvoices::STATUS_PENDING_MANAGER)): ?>
    <div class="inv-section" style="border-color:#d4d4d8">
        <div class="inv-section-hdr" style="background:#fafaf9">
            <i class="fa fa-gavel"></i> إجراءات الموافقة
        </div>
        <div style="padding:20px;display:flex;flex-wrap:wrap;gap:10px;">
            <?php if ($model->status === InventoryInvoices::STATUS_PENDING_RECEPTION && $isBranchSales): ?>
                <?= Html::a('<i class="fa fa-check"></i> موافقة استلام (الفرع)', ['approve-reception', 'id' => $model->id], [
                    'class' => 'btn btn-success', 'data-method' => 'post',
                    'data-confirm' => 'تأكيد الموافقة على استلام الفاتورة؟',
                    'style' => 'font-weight:700;border-radius:8px;padding:10px 24px',
                ]) ?>
                <?= Html::a('<i class="fa fa-times"></i> رفض استلام', ['reject-reception', 'id' => $model->id], [
                    'class' => 'btn btn-warning',
                    'style' => 'font-weight:700;border-radius:8px;padding:10px 24px',
                ]) ?>
            <?php endif ?>
            <?php if ($model->status === InventoryInvoices::STATUS_PENDING_MANAGER): ?>
                <?= Html::a('<i class="fa fa-check-circle"></i> موافقة المدير وترحيل', ['approve-manager', 'id' => $model->id], [
                    'class' => 'btn btn-primary', 'data-method' => 'post',
                    'data-confirm' => 'تأكيد الموافقة النهائية وترحيل الفاتورة إلى المخزون؟',
                    'style' => 'font-weight:700;border-radius:8px;padding:10px 24px',
                ]) ?>
                <?= Html::a('<i class="fa fa-ban"></i> رفض المدير', ['reject-manager', 'id' => $model->id], [
                    'class' => 'btn btn-danger', 'data-method' => 'post',
                    'data-confirm' => 'تأكيد رفض الفاتورة؟',
                    'style' => 'font-weight:700;border-radius:8px;padding:10px 24px',
                ]) ?>
            <?php endif ?>
        </div>
    </div>
    <?php endif ?>
    <?php endif ?>

    <!-- ═══ Metadata ═══ -->
    <div class="inv-section">
        <div class="inv-section-hdr"><i class="fa fa-info-circle"></i> معلومات إضافية</div>
        <div class="inv-meta">
            <div class="inv-meta-item">
                <span class="inv-meta-lbl">أنشئ بواسطة</span>
                <span class="inv-meta-val"><?= Html::encode($createdByName) ?></span>
            </div>
            <div class="inv-meta-item">
                <span class="inv-meta-lbl">تاريخ الإنشاء</span>
                <span class="inv-meta-val"><?= $model->created_at ? date('Y-m-d H:i', $model->created_at) : '—' ?></span>
            </div>
            <div class="inv-meta-item">
                <span class="inv-meta-lbl">آخر تحديث</span>
                <span class="inv-meta-val"><?= $model->updated_at ? date('Y-m-d H:i', $model->updated_at) : '—' ?></span>
            </div>
            <?php if ($approvedByName): ?>
            <div class="inv-meta-item">
                <span class="inv-meta-lbl">تمت الموافقة بواسطة</span>
                <span class="inv-meta-val"><?= Html::encode($approvedByName) ?></span>
            </div>
            <?php endif ?>
            <?php if ($model->approved_at): ?>
            <div class="inv-meta-item">
                <span class="inv-meta-lbl">تاريخ الموافقة</span>
                <span class="inv-meta-val"><?= date('Y-m-d H:i', $model->approved_at) ?></span>
            </div>
            <?php endif ?>
            <?php if ($model->posted_at): ?>
            <div class="inv-meta-item">
                <span class="inv-meta-lbl">تاريخ الترحيل</span>
                <span class="inv-meta-val" style="color:#15803d;font-weight:700"><?= Html::encode($model->posted_at) ?></span>
            </div>
            <?php endif ?>
        </div>
    </div>
</div>
