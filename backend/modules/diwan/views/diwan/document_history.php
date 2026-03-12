<?php
/**
 * قسم الديوان — سجل وثيقة
 */

use yii\helpers\Html;

$this->title = 'قسم الديوان';
?>

<?= $this->render('@app/views/layouts/_diwan-tabs', ['activeTab' => 'search']) ?>

<style>
.dw-hist { background: #fff; border-radius: 10px; box-shadow: 0 1px 8px rgba(0,0,0,.05); padding: 28px; max-width: 900px; }
.dw-doc-info {
    background: linear-gradient(135deg, var(--fin-primary,#800020), #a02050); color: #fff;
    border-radius: 10px; padding: 20px; margin-bottom: 24px;
}
.dw-doc-info h3 { font-size: 18px; font-weight: 800; margin: 0 0 10px; }
.dw-doc-info .info-row { display: flex; gap: 28px; flex-wrap: wrap; }
.dw-doc-info .info-item { flex: 1; min-width: 130px; }
.dw-doc-info .il { font-size: 10px; opacity: .7; font-weight: 600; }
.dw-doc-info .iv { font-size: 14px; font-weight: 700; }

.dw-tl { position: relative; padding: 0; }
.dw-tl::before {
    content: ''; position: absolute; right: 18px; top: 0; bottom: 0;
    width: 2px; background: #e0e0e0; border-radius: 2px;
}
.dw-tl-item { position: relative; padding: 0 44px 24px 0; }
.dw-tl-item::before {
    content: ''; position: absolute; right: 12px; top: 5px;
    width: 14px; height: 14px; border-radius: 50%;
    border: 3px solid var(--fin-primary,#800020); background: #fff; z-index: 1;
}
.dw-tl-item:last-child { padding-bottom: 0; }
.dw-tl-content { background: #f8f9fa; border-radius: 8px; padding: 14px 16px; border-right: 3px solid var(--fin-primary,#800020); }
.dw-tl-content.recv { border-right-color: #4CAF50; }
.dw-tl-content.dlvr { border-right-color: #FF9800; }

.tl-date { font-size: 11px; color: #888; margin-bottom: 4px; }
.tl-type { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 700; margin-bottom: 4px; }
.tl-recv { background: #e8f5e9; color: #2e7d32; }
.tl-dlvr { background: #fff3e0; color: #e65100; }
.tl-detail { font-size: 12px; color: #555; }
.tl-detail strong { color: #333; }
</style>

<div class="dw-hist">

    <!-- معلومات الوثيقة -->
    <div class="dw-doc-info">
        <h3><i class="fa fa-file-text-o"></i> عقد رقم: <?= Html::encode($contractNumber) ?></h3>
        <div class="info-row">
            <div class="info-item">
                <div class="il">الحامل الحالي</div>
                <div class="iv">
                    <?= $tracker && $tracker->currentHolder
                        ? Html::encode($tracker->currentHolder->name ?: $tracker->currentHolder->username)
                        : 'غير محدد' ?>
                </div>
            </div>
            <div class="info-item">
                <div class="il">الحالة</div>
                <div class="iv"><?= $tracker ? Html::encode($tracker->status) : 'غير متتبع' ?></div>
            </div>
            <div class="info-item">
                <div class="il">عدد الحركات</div>
                <div class="iv"><?= count($history) ?></div>
            </div>
        </div>
    </div>

    <!-- التسلسل الزمني -->
    <h4 style="font-size:15px; font-weight:700; margin-bottom:16px;">
        <i class="fa fa-history"></i> سجل الحركات
    </h4>

    <?php if (empty($history)): ?>
    <div style="text-align:center; padding:36px; color:#999;">
        <i class="fa fa-clock-o" style="font-size:36px; display:block; margin-bottom:10px;"></i>
        لا توجد حركات مسجلة لهذه الوثيقة
    </div>
    <?php else: ?>
    <div class="dw-tl">
        <?php foreach ($history as $item): ?>
            <?php
            $t = $item->transaction;
            $isRecv = $t->transaction_type === 'استلام';
            $tcls = $isRecv ? 'recv' : 'dlvr';
            $bcls = $isRecv ? 'tl-recv' : 'tl-dlvr';
            ?>
            <div class="dw-tl-item">
                <div class="dw-tl-content <?= $tcls ?>">
                    <div class="tl-date">
                        <i class="fa fa-calendar"></i>
                        <?= Yii::$app->formatter->asDatetime($t->transaction_date, 'php:Y/m/d h:i A') ?>
                    </div>
                    <span class="tl-type <?= $bcls ?>"><?= Html::encode($t->transaction_type) ?></span>
                    <div class="tl-detail">
                        من <strong><?= Html::encode($t->fromEmployee ? ($t->fromEmployee->name ?: $t->fromEmployee->username) : '—') ?></strong>
                        &larr;
                        إلى <strong><?= Html::encode($t->toEmployee ? ($t->toEmployee->name ?: $t->toEmployee->username) : '—') ?></strong>
                    </div>
                    <?php if (!empty($t->notes)): ?>
                    <div style="margin-top:4px; font-size:11px; color:#888;">
                        <i class="fa fa-sticky-note-o"></i> <?= Html::encode($t->notes) ?>
                    </div>
                    <?php endif; ?>
                    <div style="margin-top:4px;">
                        <?= Html::a('إيصال #' . Html::encode($t->receipt_number), ['receipt', 'id' => $t->id], [
                            'style' => 'font-size:10px; color:var(--fin-primary,#800020);',
                        ]) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div style="margin-top:20px;">
        <?= Html::a('<i class="fa fa-arrow-right"></i> رجوع للبحث', ['search'], [
            'class' => 'btn btn-default btn-sm', 'style' => 'border-radius:6px;',
        ]) ?>
    </div>
</div>
