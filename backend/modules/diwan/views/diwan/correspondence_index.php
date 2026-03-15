<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ListView;
use yii\grid\GridView;
use backend\modules\diwan\models\DiwanCorrespondence;

$this->title = 'المراسلات والتبليغات';
if (!isset($judiciaryId)) {
    $this->params['breadcrumbs'][] = ['label' => 'الديوان', 'url' => ['index']];
    $this->params['breadcrumbs'][] = $this->title;
}
echo $this->render('@backend/views/layouts/_diwan-tabs', ['activeTab' => 'correspondence']);

$typeLabels = DiwanCorrespondence::getCommunicationTypeLabels();
$statusLabels = DiwanCorrespondence::getStatusLabels();
$typeIcons = [
    'notification' => 'fa-bell',
    'outgoing_letter' => 'fa-paper-plane',
    'incoming_response' => 'fa-reply',
];
$typeColors = [
    'notification' => '#F59E0B',
    'outgoing_letter' => '#3B82F6',
    'incoming_response' => '#10B981',
];
?>

<style>
.corr-page{direction:rtl;font-family:'Tajawal','Segoe UI',sans-serif}
.corr-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.corr-tab{padding:8px 20px;border-radius:20px;font-size:13px;font-weight:600;border:1px solid #E2E8F0;background:#fff;cursor:pointer;text-decoration:none;color:#475569;transition:all .2s}
.corr-tab:hover,.corr-tab.active{border-color:#3B82F6;background:#EFF6FF;color:#2563EB}
.corr-card{background:#fff;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden}
</style>

<div class="corr-page">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
        <h2 style="font-size:20px;font-weight:700;color:#1E293B;margin:0">
            <i class="fa fa-envelope" style="color:#3B82F6"></i> المراسلات والتبليغات
        </h2>
        <div style="display:flex;gap:8px">
            <button class="btn btn-warning btn-sm" onclick="showAddForm('notification')"><i class="fa fa-bell"></i> تبليغ جديد</button>
            <button class="btn btn-primary btn-sm" onclick="showAddForm('outgoing_letter')"><i class="fa fa-paper-plane"></i> كتاب صادر</button>
            <button class="btn btn-success btn-sm" onclick="showAddForm('incoming_response')"><i class="fa fa-reply"></i> رد وارد</button>
        </div>
    </div>

    <div class="corr-tabs">
        <?php
        $currentType = Yii::$app->request->get('DiwanCorrespondenceSearch')['communication_type'] ?? '';
        $baseParams = $judiciaryId ? ['correspondence-index', 'judiciary_id' => $judiciaryId] : ['correspondence-index'];
        ?>
        <a href="<?= Url::to($baseParams) ?>" class="corr-tab <?= !$currentType ? 'active' : '' ?>">الكل</a>
        <?php foreach ($typeLabels as $key => $label): ?>
            <a href="<?= Url::to(array_merge($baseParams, ['DiwanCorrespondenceSearch[communication_type]' => $key])) ?>"
               class="corr-tab <?= $currentType === $key ? 'active' : '' ?>"
               style="border-right:3px solid <?= $typeColors[$key] ?>">
                <i class="fa <?= $typeIcons[$key] ?>"></i> <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="corr-card">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'tableOptions' => ['class' => 'table table-striped table-hover', 'style' => 'font-size:13px'],
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                [
                    'attribute' => 'communication_type',
                    'value' => function ($m) use ($typeLabels, $typeIcons, $typeColors) {
                        $icon = $typeIcons[$m->communication_type] ?? 'fa-file';
                        $color = $typeColors[$m->communication_type] ?? '#64748B';
                        $label = $typeLabels[$m->communication_type] ?? $m->communication_type;
                        return '<span style="color:' . $color . '"><i class="fa ' . $icon . '"></i> ' . $label . '</span>';
                    },
                    'format' => 'raw',
                    'filter' => $typeLabels,
                ],
                [
                    'attribute' => 'correspondence_date',
                    'format' => 'date',
                ],
                [
                    'label' => 'المستلم/المرسل',
                    'value' => function ($m) {
                        return $m->getRecipientDisplayName();
                    },
                ],
                [
                    'attribute' => 'reference_number',
                ],
                [
                    'attribute' => 'purpose',
                    'value' => function ($m) {
                        $labels = DiwanCorrespondence::getPurposeLabels();
                        return $labels[$m->purpose] ?? $m->purpose;
                    },
                    'filter' => DiwanCorrespondence::getPurposeLabels(),
                ],
                [
                    'attribute' => 'status',
                    'value' => function ($m) use ($statusLabels) {
                        $label = $statusLabels[$m->status] ?? $m->status;
                        $colors = ['draft'=>'#9CA3AF','sent'=>'#3B82F6','delivered'=>'#10B981','responded'=>'#8B5CF6','closed'=>'#64748B'];
                        $c = $colors[$m->status] ?? '#64748B';
                        return '<span style="color:' . $c . ';font-weight:600">' . $label . '</span>';
                    },
                    'format' => 'raw',
                    'filter' => $statusLabels,
                ],
                [
                    'attribute' => 'content_summary',
                    'value' => function ($m) {
                        return mb_substr($m->content_summary ?? '', 0, 60) . (mb_strlen($m->content_summary ?? '') > 60 ? '…' : '');
                    },
                ],
            ],
        ]) ?>
    </div>
</div>

<script>
function showAddForm(type) {
    alert('نموذج إضافة ' + type + ' — سيتم بناؤه في تحسينات الواجهة');
}
</script>
