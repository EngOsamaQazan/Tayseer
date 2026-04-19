<?php

use common\models\FahrasCheckLog;
use common\services\dto\FahrasVerdict;
use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this         yii\web\View                              */
/* @var $searchModel  \common\models\FahrasCheckLogSearch       */
/* @var $dataProvider yii\data\ActiveDataProvider               */

$this->title = 'سجل فحوصات الفهرس';
$this->params['breadcrumbs'] = [
    ['label' => 'العملاء', 'url' => ['/customers/customers/index']],
    $this->title,
];

$verdictOptions = [
    ''                                       => 'الكل',
    FahrasVerdict::VERDICT_CAN_SELL          => 'يمكن البيع',
    FahrasVerdict::VERDICT_CONTACT_FIRST     => 'اتصل أولاً',
    FahrasVerdict::VERDICT_CANNOT_SELL       => 'لا يمكن البيع',
    FahrasVerdict::VERDICT_NO_RECORD         => 'لا يوجد سجل',
    FahrasVerdict::VERDICT_ERROR             => 'فشل الفحص',
];

$sourceOptions = [
    ''                            => 'الكل',
    FahrasCheckLog::SOURCE_STEP1  => 'الخطوة 1',
    FahrasCheckLog::SOURCE_FINISH => 'إنهاء الإنشاء',
    FahrasCheckLog::SOURCE_MANUAL => 'تجاوز يدوي',
    FahrasCheckLog::SOURCE_SEARCH => 'بحث',
];

// User cache (avoid N queries when rendering rows).
$userIds = [];
foreach ($dataProvider->getModels() as $row) {
    if ($row->user_id)          $userIds[] = (int)$row->user_id;
    if ($row->override_user_id) $userIds[] = (int)$row->override_user_id;
}
$userIds = array_values(array_unique($userIds));
$users = [];
if ($userIds && class_exists(\common\models\User::class)) {
    /** @var array $userRows */
    $userRows = \common\models\User::find()
        ->select(['id', 'username'])
        ->where(['id' => $userIds])
        ->asArray()
        ->all();
    foreach ($userRows as $u) {
        $users[(int)$u['id']] = $u['username'];
    }
}
$userName = static function (?int $id) use ($users) {
    if (!$id) return '—';
    return Html::encode($users[$id] ?? ('#' . $id));
};
?>

<div class="fahras-log-index">
    <?= GridView::widget([
        'id'           => 'fahras-log-grid',
        'dataProvider' => $dataProvider,
        'filterModel'  => $searchModel,
        'pjax'         => true,
        'striped'      => true,
        'condensed'    => true,
        'responsive'   => true,
        'hover'        => true,
        'summary'      => '<div class="text-muted py-2">إجمالي السجلات: {totalCount}</div>',
        'panel' => [
            'type'    => 'default',
            'heading' => '<i class="fa fa-shield"></i> ' . Html::encode($this->title),
            'before'  => $this->render('_filters', ['searchModel' => $searchModel, 'verdictOptions' => $verdictOptions, 'sourceOptions' => $sourceOptions]),
        ],
        'columns' => [
            [
                'class' => 'kartik\grid\SerialColumn',
                'header' => '#',
                'hAlign' => 'center',
                'width'  => '50px',
            ],

            // Created at — primary chronology axis.
            [
                'attribute' => 'created_at',
                'label'     => 'التاريخ',
                'format'    => 'raw',
                'value'     => static function (FahrasCheckLog $m) {
                    return '<span dir="ltr">' . Yii::$app->formatter->asDatetime($m->created_at, 'php:Y-m-d H:i') . '</span>';
                },
                'filter' => false,
                'width'  => '140px',
            ],

            // Verdict pill — most important column.
            [
                'attribute' => 'verdict',
                'label'     => 'القرار',
                'format'    => 'raw',
                'filter'    => $verdictOptions,
                'value'     => static function (FahrasCheckLog $m) {
                    return '<span class="badge ' . $m->getVerdictBadgeClass() . '">' .
                        Html::encode($m->getVerdictLabel()) . '</span>';
                },
                'width' => '120px',
            ],

            [
                'attribute' => 'id_number',
                'label'     => 'الرقم الوطني',
                'format'    => 'raw',
                'value'     => static function (FahrasCheckLog $m) {
                    return '<span dir="ltr" class="text-monospace">' . Html::encode($m->id_number) . '</span>';
                },
            ],

            [
                'attribute' => 'name',
                'label'     => 'الاسم',
                'value'     => static function (FahrasCheckLog $m) {
                    return $m->name ?: '—';
                },
            ],

            [
                'attribute' => 'phone',
                'label'     => 'الهاتف',
                'format'    => 'raw',
                'value'     => static function (FahrasCheckLog $m) {
                    return $m->phone
                        ? '<span dir="ltr" class="text-monospace">' . Html::encode($m->phone) . '</span>'
                        : '—';
                },
            ],

            [
                'attribute' => 'source',
                'label'     => 'المصدر',
                'filter'    => $sourceOptions,
                'value'     => static function (FahrasCheckLog $m) use ($sourceOptions) {
                    return $sourceOptions[$m->source] ?? ($m->source ?: '—');
                },
                'width' => '120px',
            ],

            [
                'label'  => 'بواسطة',
                'format' => 'raw',
                'value'  => static function (FahrasCheckLog $m) use ($userName) {
                    return $userName($m->user_id);
                },
                'filter' => false,
            ],

            // Override marker — manager who bypassed the block.
            [
                'label'  => 'تجاوز',
                'format' => 'raw',
                'value'  => static function (FahrasCheckLog $m) use ($userName) {
                    if (!$m->override_user_id) return '<span class="text-muted">—</span>';
                    return '<span class="badge bg-warning text-dark">' .
                        '<i class="fa fa-key"></i> ' . $userName($m->override_user_id) . '</span>';
                },
                'filter' => Html::activeCheckbox(
                    $searchModel,
                    'onlyOverrides',
                    ['label' => 'فقط', 'class' => 'form-check-input']
                ),
                'width'  => '120px',
            ],

            [
                'class'    => 'kartik\grid\ActionColumn',
                'template' => '{view}',
                'buttons' => [
                    'view' => static function ($url, FahrasCheckLog $m) {
                        return Html::a(
                            '<i class="fa fa-eye"></i>',
                            ['view', 'id' => $m->id],
                            ['class' => 'btn btn-sm btn-outline-primary', 'title' => 'تفاصيل']
                        );
                    },
                ],
                'width' => '60px',
            ],
        ],
    ]) ?>
</div>
