<?php
/**
 * قسم الديوان — بحث الوثائق
 */

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;

$this->title = 'قسم الديوان';
?>

<?= $this->render('@app/views/layouts/_diwan-tabs', ['activeTab' => 'search']) ?>

<style>
.dw-search-bar {
    background: linear-gradient(135deg, var(--fin-primary,#800020), #a02050);
    border-radius: 10px; padding: 28px 24px; text-align: center; margin-bottom: 22px; color: #fff;
}
.dw-search-bar h3 { font-size: 18px; font-weight: 800; margin: 0 0 14px; }
.dw-search-wrap { max-width: 550px; margin: 0 auto; display: flex; gap: 8px; }
.dw-search-wrap input {
    flex: 1; padding: 10px 16px; border-radius: 8px; border: none; font-size: 14px;
    direction: rtl; outline: none;
}
.dw-search-wrap button {
    padding: 10px 22px; border-radius: 8px; border: none; background: #fff;
    color: var(--fin-primary, #800020); font-weight: 700; font-size: 13px; cursor: pointer;
}
.dw-search-wrap button:hover { background: #f0f0f0; }

.dw-result {
    background: #fff; border-radius: 8px; padding: 16px; margin-bottom: 10px;
    box-shadow: 0 1px 6px rgba(0,0,0,.04); display: flex; justify-content: space-between;
    align-items: center; transition: transform .15s;
}
.dw-result:hover { transform: translateX(-3px); box-shadow: 0 3px 12px rgba(0,0,0,.07); }
.dw-result h4 { font-size: 14px; font-weight: 700; color: #333; margin: 0 0 3px; }
.dw-result p  { font-size: 12px; color: #777; margin: 0; }

.dw-status { padding: 4px 12px; border-radius: 16px; font-size: 11px; font-weight: 700; }
.dw-status--active  { background: #e8f5e9; color: #2e7d32; }
.dw-status--archive { background: #f3e5f5; color: #7b1fa2; }
</style>

<div class="diwan-search">

    <!-- شريط البحث -->
    <div class="dw-search-bar">
        <h3><i class="fa fa-search"></i> بحث عن وثيقة</h3>
        <form method="get" action="<?= Url::to(['search']) ?>">
            <div class="dw-search-wrap">
                <input type="text" name="q" value="<?= Html::encode($query) ?>"
                       placeholder="أدخل رقم العقد للبحث..." autofocus>
                <button type="submit"><i class="fa fa-search"></i> بحث</button>
            </div>
        </form>
    </div>

    <!-- نتائج البحث -->
    <?php if (!empty($query)): ?>
    <div style="margin-bottom:22px;">
        <h4 style="font-size:14px; font-weight:700; margin-bottom:12px;">
            نتائج البحث عن "<?= Html::encode($query) ?>" (<?= count($results) ?> نتيجة)
        </h4>

        <?php if (empty($results)): ?>
        <div style="text-align:center; padding:36px; color:#999; background:#fff; border-radius:10px;">
            <i class="fa fa-search" style="font-size:36px; display:block; margin-bottom:10px;"></i>
            لم يتم العثور على وثائق تطابق البحث
        </div>
        <?php else: ?>
            <?php foreach ($results as $tracker): ?>
            <div class="dw-result">
                <div>
                    <h4><i class="fa fa-file-text-o"></i> عقد رقم: <?= Html::encode($tracker->contract_number) ?></h4>
                    <p>
                        الحامل الحالي: <strong><?= Html::encode($tracker->currentHolder ? ($tracker->currentHolder->name ?: $tracker->currentHolder->username) : 'غير محدد') ?></strong>
                        <?php if ($tracker->updated_at): ?>
                            — آخر تحديث: <?= Yii::$app->formatter->asDatetime($tracker->updated_at, 'php:Y/m/d h:i A') ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div style="display:flex; gap:6px; align-items:center;">
                    <span class="dw-status <?= strpos($tracker->status, 'أرشيف') !== false ? 'dw-status--archive' : 'dw-status--active' ?>">
                        <?= Html::encode($tracker->status ?: 'غير محدد') ?>
                    </span>
                    <?= Html::a('<i class="fa fa-history"></i> السجل', ['document-history', 'contract_number' => $tracker->contract_number], [
                        'class' => 'btn btn-xs btn-default', 'style' => 'border-radius:4px;',
                    ]) ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- جميع الوثائق المتتبعة -->
    <div>
        <h4 style="font-size:15px; font-weight:700; margin-bottom:14px;"><i class="fa fa-list"></i> جميع الوثائق المتتبعة</h4>

        <?= GridView::widget([
            'dataProvider' => $allDocuments,
            'responsive' => true,
            'hover' => true,
            'striped' => false,
            'bordered' => false,
            'tableOptions' => ['class' => 'table table-hover'],
            'pjax' => true,
            'columns' => [
                ['class' => 'yii\grid\SerialColumn', 'header' => '#'],
                [
                    'attribute' => 'contract_number',
                    'label' => 'رقم العقد',
                    'contentOptions' => ['style' => 'font-weight:700;'],
                ],
                [
                    'label' => 'الحامل الحالي',
                    'value' => function ($m) {
                        return $m->currentHolder ? ($m->currentHolder->name ?: $m->currentHolder->username) : '—';
                    },
                ],
                [
                    'attribute' => 'status',
                    'label' => 'الحالة',
                    'format' => 'raw',
                    'value' => function ($m) {
                        $cls = strpos($m->status, 'أرشيف') !== false ? 'dw-status--archive' : 'dw-status--active';
                        return '<span class="dw-status ' . $cls . '">' . Html::encode($m->status ?: '—') . '</span>';
                    },
                ],
                [
                    'attribute' => 'updated_at',
                    'label' => 'آخر تحديث',
                    'format' => ['datetime', 'php:Y/m/d h:i A'],
                ],
                [
                    'class' => 'kartik\grid\ActionColumn',
                    'header' => '',
                    'template' => '{history}',
                    'buttons' => [
                        'history' => function ($url, $m) {
                            return Html::a('<i class="fa fa-history"></i> السجل', ['document-history', 'contract_number' => $m->contract_number], [
                                'class' => 'btn btn-xs btn-default',
                            ]);
                        },
                    ],
                ],
            ],
            'summary' => '<span style="font-size:12px; color:#888;">عرض {begin}-{end} من {totalCount} وثيقة</span>',
        ]) ?>
    </div>
</div>
