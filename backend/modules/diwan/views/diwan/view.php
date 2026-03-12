<?php
/**
 * قسم الديوان — عرض تفاصيل معاملة
 */

use yii\helpers\Html;

$this->title = 'قسم الديوان';
$typeCls = $model->transaction_type === 'استلام' ? 'dw-badge--recv' : 'dw-badge--dlvr';
?>

<?= $this->render('@app/views/layouts/_diwan-tabs', ['activeTab' => 'transactions']) ?>

<style>
.dw-view { background: #fff; border-radius: 10px; box-shadow: 0 1px 8px rgba(0,0,0,.05); padding: 28px; max-width: 900px; }
.dw-view-hdr { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 10px; }
.dw-view-hdr h2 { font-size: 18px; font-weight: 800; color: #333; margin: 0; }
.dw-view-acts { display: flex; gap: 6px; }
.dw-detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
.dw-detail-item { padding: 14px; background: #f8f9fa; border-radius: 8px; }
.dw-detail-item .dl { font-size: 10px; color: #888; font-weight: 700; text-transform: uppercase; margin-bottom: 3px; }
.dw-detail-item .dv { font-size: 14px; color: #333; font-weight: 600; }
.dw-badge { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.dw-badge--recv { background: #e8f5e9; color: #2e7d32; }
.dw-badge--dlvr { background: #fff3e0; color: #e65100; }
.dw-contracts { background: #f8f9fa; border-radius: 8px; overflow: hidden; }
.dw-contracts .cl-hdr { padding: 12px 18px; font-weight: 700; font-size: 13px; background: #f0f0f0; }
.dw-contracts table { margin: 0; }
.dw-contracts table td { font-size: 12px; }
</style>

<div class="dw-view">
    <div class="dw-view-hdr">
        <div>
            <h2><i class="fa fa-file-text-o"></i> معاملة #<?= $model->id ?></h2>
            <p style="color:#888; margin-top:3px; font-size:12px;">
                <code><?= Html::encode($model->receipt_number) ?></code>
            </p>
        </div>
        <div class="dw-view-acts">
            <?= Html::a('<i class="fa fa-print"></i> طباعة', ['receipt', 'id' => $model->id], [
                'class' => 'btn btn-default btn-sm', 'style' => 'border-radius:6px;', 'target' => '_blank',
            ]) ?>
            <?= Html::a('<i class="fa fa-arrow-right"></i> رجوع', ['transactions'], [
                'class' => 'btn btn-default btn-sm', 'style' => 'border-radius:6px;',
            ]) ?>
        </div>
    </div>

    <div class="dw-detail-grid">
        <div class="dw-detail-item">
            <div class="dl">نوع المعاملة</div>
            <div class="dv"><span class="dw-badge <?= $typeCls ?>"><?= Html::encode($model->transaction_type) ?></span></div>
        </div>
        <div class="dw-detail-item">
            <div class="dl">من موظف</div>
            <div class="dv"><?= Html::encode($model->fromEmployee ? ($model->fromEmployee->name ?: $model->fromEmployee->username) : '—') ?></div>
        </div>
        <div class="dw-detail-item">
            <div class="dl">إلى موظف</div>
            <div class="dv"><?= Html::encode($model->toEmployee ? ($model->toEmployee->name ?: $model->toEmployee->username) : '—') ?></div>
        </div>
        <div class="dw-detail-item">
            <div class="dl">تاريخ المعاملة</div>
            <div class="dv"><?= Yii::$app->formatter->asDatetime($model->transaction_date, 'php:Y/m/d h:i A') ?></div>
        </div>
        <div class="dw-detail-item">
            <div class="dl">أنشئ بواسطة</div>
            <div class="dv"><?= Html::encode($model->createdByUser ? ($model->createdByUser->name ?: $model->createdByUser->username) : '—') ?></div>
        </div>
        <div class="dw-detail-item">
            <div class="dl">عدد العقود</div>
            <div class="dv"><span class="badge" style="background:var(--fin-primary,#800020); font-size:13px;"><?= count($model->details) ?></span></div>
        </div>
    </div>

    <?php if (!empty($model->notes)): ?>
    <div style="background:#fffde7; border-radius:8px; padding:14px; margin-bottom:20px; border-right:4px solid #FFC107;">
        <strong style="color:#F57F17;"><i class="fa fa-sticky-note-o"></i> ملاحظات:</strong>
        <p style="margin:6px 0 0; color:#555;"><?= nl2br(Html::encode($model->notes)) ?></p>
    </div>
    <?php endif; ?>

    <div class="dw-contracts">
        <div class="cl-hdr"><i class="fa fa-list-ol"></i> العقود في هذه المعاملة (<?= count($model->details) ?>)</div>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>رقم العقد</th>
                    <th>سجل الوثيقة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($model->details as $i => $detail): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td style="font-weight:700;"><?= Html::encode($detail->contract_number) ?></td>
                    <td>
                        <?= Html::a('<i class="fa fa-history"></i> السجل', ['document-history', 'contract_number' => $detail->contract_number], [
                            'class' => 'btn btn-xs btn-default',
                        ]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
