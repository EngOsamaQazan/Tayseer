<?php
/**
 * عرض تفاصيل رقم تسلسلي
 */
use yii\helpers\Html;
use yii\widgets\DetailView;
use backend\modules\inventoryItems\models\InventorySerialNumber;
?>

<style>
.sn-detail-card { padding: 0; }
.sn-detail-header { background: linear-gradient(135deg, #0369a1 0%, #0284c7 100%); color: #fff; padding: 20px 24px; border-radius: 8px 8px 0 0; }
.sn-detail-serial { font-family: 'Courier New', monospace; font-size: 22px; font-weight: 800; letter-spacing: 1.5px; direction: ltr; text-align: center; margin-bottom: 8px; }
.sn-detail-item { font-size: 14px; opacity: 0.9; text-align: center; }
.sn-detail-status { text-align: center; margin-top: 10px; }
.sn-detail-status .sn-badge { font-size: 13px; padding: 5px 16px; }
/* شارات الحالة — مُحددة بـ .sn-detail-header لتجنب تعارض الألوان مع الصفحة الرئيسية */
.sn-detail-header .sn-badge--available { background: rgba(255,255,255,0.25); color: #fff; border: 1px solid rgba(255,255,255,0.5); }
.sn-detail-header .sn-badge--reserved { background: #fef3c7; color: #92400e; }
.sn-detail-header .sn-badge--sold { background: #ede9fe; color: #5b21b6; }
.sn-detail-header .sn-badge--returned { background: #dbeafe; color: #1e40af; }
.sn-detail-header .sn-badge--defective { background: #fee2e2; color: #991b1b; }
.sn-detail-body { padding: 16px; }
.sn-detail-body table.detail-view th { width: 140px; font-weight: 700; color: #475569; background: #f8fafc; font-size: 13px; }
.sn-detail-body table.detail-view td { font-size: 13.5px; color: #1e293b; }
</style>

<div class="sn-detail-card">
    <div class="sn-detail-header">
        <div class="sn-detail-serial"><?= Html::encode($model->serial_number) ?></div>
        <div class="sn-detail-item">
            <i class="fa fa-cube"></i>
            <?= $model->item ? Html::encode($model->item->item_name) : 'غير محدد' ?>
        </div>
        <div class="sn-detail-status">
            <span class="sn-badge <?= $model->getStatusCssClass() ?>">
                <i class="fa <?= $model->getStatusIcon() ?>"></i>
                <?= Html::encode($model->getStatusLabel()) ?>
            </span>
        </div>
    </div>

    <div class="sn-detail-body">
        <?= DetailView::widget([
            'model' => $model,
            'attributes' => [
                'id',
                [
                    'attribute' => 'item_id',
                    'value' => $model->item ? $model->item->item_name . ' (' . $model->item->item_barcode . ')' : '—',
                ],
                [
                    'attribute' => 'supplier_id',
                    'value' => $model->supplier ? $model->supplier->name : '—',
                ],
                [
                    'attribute' => 'location_id',
                    'value' => $model->location ? $model->location->locations_name : '—',
                ],
                [
                    'attribute' => 'contract_id',
                    'value' => $model->contract_id ?: '—',
                ],
                [
                    'attribute' => 'received_at',
                    'value' => $model->received_at ? date('Y-m-d', $model->received_at) : '—',
                ],
                [
                    'attribute' => 'sold_at',
                    'value' => $model->sold_at ? date('Y-m-d H:i', $model->sold_at) : '—',
                ],
                'note',
                [
                    'attribute' => 'created_by',
                    'value' => $model->createdByUser ? $model->createdByUser->username : '—',
                ],
                [
                    'attribute' => 'created_at',
                    'value' => $model->created_at ? date('Y-m-d H:i', $model->created_at) : '—',
                ],
                [
                    'attribute' => 'updated_at',
                    'value' => $model->updated_at ? date('Y-m-d H:i', $model->updated_at) : '—',
                ],
            ],
        ]) ?>
    </div>
</div>
