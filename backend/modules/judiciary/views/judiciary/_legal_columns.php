<?php
/**
 * أعمدة جدول المحولين للشكوى (الدائرة القانونية)
 * مطابق لتنسيق جدول القضايا الرئيسي
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use common\helper\Permissions;
use backend\helpers\NameHelper;
use backend\modules\judiciary\models\Judiciary;

$isManager = Yii::$app->user->can(\common\helper\Permissions::MANAGER);

$contractIds = ArrayHelper::getColumn($dataProvider->getModels(), 'id');
$judiciaryMap = [];
if (!empty($contractIds)) {
    $judRecords = Judiciary::find()
        ->where(['contract_id' => $contractIds])
        ->orderBy(['id' => SORT_DESC])
        ->all();
    foreach ($judRecords as $jud) {
        if (!isset($judiciaryMap[$jud->contract_id])) {
            $judiciaryMap[$jud->contract_id] = $jud;
        }
    }
}

static $jobsMap = null;
static $jobToTypeMap = null;
static $jobTypesMap = null;
if ($jobsMap === null) {
    $jobsRows = \backend\modules\jobs\models\Jobs::find()->select(['id', 'name', 'job_type'])->asArray()->all();
    $jobsMap = ArrayHelper::map($jobsRows, 'id', 'name');
    $jobToTypeMap = ArrayHelper::map($jobsRows, 'id', 'job_type');
    $jobTypesMap = ArrayHelper::map(
        \backend\modules\jobs\models\JobsType::find()->select(['id', 'name'])->asArray()->all(), 'id', 'name'
    );
}

$balanceMap = [];
if (!empty($contractIds)) {
    $balRows = Yii::$app->db->createCommand(
        "SELECT contract_id, remaining_balance, total_value, total_expenses, total_lawyer_cost FROM {{%vw_contract_balance}} WHERE contract_id IN (" . implode(',', array_map('intval', $contractIds)) . ")"
    )->queryAll();
    foreach ($balRows as $b) {
        $balanceMap[(int)$b['contract_id']] = $b;
    }
}

return [
    /* # */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'id',
        'label' => '#',
        'format' => 'raw',
        'value' => fn($m) => '<a href="' . Url::to(['/followUp/follow-up/panel', 'contract_id' => $m->id]) . '" style="font-weight:700;color:#800020;text-decoration:none">' . $m->id . '</a>',
        'headerOptions' => ['style' => 'width:5%;text-align:center'],
        'contentOptions' => ['style' => 'text-align:center', 'data-label' => '#'],
    ],

    /* الأطراف */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'الأطراف',
        'format' => 'raw',
        'value' => function ($m) {
            $customers = $m->customersAndGuarantor;
            if (empty($customers)) return '<span style="color:#CBD5E1">—</span>';

            $rows = [];
            foreach ($customers as $c) {
                $full = $c->name;
                $short = NameHelper::short($full);
                $nameHtml = '<span style="font-weight:600;color:#1E293B;font-size:11px" title="' . Html::encode($full) . '">' . Html::encode($short) . '</span>';
                if (!empty($c->id_number)) {
                    $nameHtml .= ' <span style="font-size:10px;color:#6B7280;direction:ltr;unicode-bidi:embed;letter-spacing:.3px">(' . Html::encode($c->id_number) . ')</span>';
                }
                $rows[] = $nameHtml;
            }
            return '<div style="display:flex;flex-direction:column;gap:2px;max-height:60px;overflow-y:auto;scrollbar-width:thin">' . implode('', array_map(fn($r) => '<div style="white-space:nowrap">' . $r . '</div>', $rows)) . '</div>';
        },
        'headerOptions' => ['style' => 'width:22%'],
        'contentOptions' => ['style' => 'padding:4px 6px;overflow:hidden;text-overflow:ellipsis', 'data-label' => 'الأطراف'],
    ],

    /* الإجمالي */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'total_value',
        'label' => 'الإجمالي',
        'format' => 'raw',
        'value' => fn($m) => '<span style="font-weight:600;color:#1E293B;font-size:12px;font-feature-settings:\'tnum\'">' . number_format((float)$m->total_value, 0) . '</span>',
        'headerOptions' => ['style' => 'width:9%;text-align:center'],
        'contentOptions' => ['style' => 'text-align:center;white-space:nowrap', 'data-label' => 'الإجمالي'],
    ],

    /* المتبقي */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'remaining',
        'label' => 'المتبقي',
        'format' => 'raw',
        'value' => function ($m) use ($balanceMap) {
            $b = $balanceMap[$m->id] ?? null;
            $remaining = $b ? (float)($b['remaining_balance'] ?? 0) : 0;
            $clr = $remaining > 0 ? '#DC2626' : '#16A34A';
            return '<span style="font-weight:700;color:' . $clr . ';font-size:12px;font-feature-settings:\'tnum\'">' . number_format($remaining, 0) . '</span>';
        },
        'headerOptions' => ['style' => 'width:9%;text-align:center'],
        'contentOptions' => ['style' => 'text-align:center;white-space:nowrap', 'data-label' => 'المتبقي'],
    ],

    /* الوظيفة */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'الوظيفة',
        'format' => 'raw',
        'value' => function ($m) use ($jobsMap) {
            $cust = ($m->customersAndGuarantor)[0] ?? null;
            $jid = $cust ? $cust->job_title : null;
            $jobName = $jid ? ($jobsMap[$jid] ?? '—') : '—';
            if ($jobName === '—') return '<span style="color:#CBD5E1">—</span>';
            return '<span style="font-size:11px;color:#475569">' . Html::encode($jobName) . '</span>';
        },
        'headerOptions' => ['style' => 'width:12%'],
        'contentOptions' => ['style' => 'overflow:hidden;text-overflow:ellipsis;white-space:nowrap', 'data-label' => 'الوظيفة'],
    ],

    /* حالة القضية */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'حالة القضية',
        'format' => 'raw',
        'value' => function ($m) use ($judiciaryMap) {
            $jud = $judiciaryMap[$m->id] ?? null;
            if ($jud) {
                $stage = $jud->furthest_stage;
                $label = $stage ? Judiciary::getStageLabel($stage) : 'مسجّلة';
                return '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:6px;font-size:10px;font-weight:600;background:#DCFCE7;color:#166534"><i class="fa fa-check-circle"></i> ' . Html::encode($label) . '</span>';
            }
            return '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:6px;font-size:10px;font-weight:600;background:#FEF9C3;color:#854D0E"><i class="fa fa-clock-o"></i> بانتظار القضية</span>';
        },
        'headerOptions' => ['style' => 'width:11%;text-align:center'],
        'contentOptions' => ['style' => 'text-align:center;padding:4px 6px', 'data-label' => 'حالة القضية'],
    ],

    /* الإجراءات */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => '',
        'format' => 'raw',
        'value' => function ($m) use ($judiciaryMap, $isManager) {
            $jud = $judiciaryMap[$m->id] ?? null;
            $hasCase = (bool) $jud;

            $menu = '';
            $menu .= '<a href="' . Url::to(['/followUp/follow-up/panel', 'contract_id' => $m->id]) . '" data-pjax="0"><i class="fa fa-dashboard" style="color:#3B82F6"></i> لوحة التحكم</a>';
            $menu .= '<a href="' . Url::to(['/contracts/contracts/update', 'id' => $m->id]) . '" data-pjax="0"><i class="fa fa-pencil" style="color:#8B5CF6"></i> تعديل العقد</a>';
            $menu .= '<a href="' . Url::to(['/contracts/contracts/print-preview', 'id' => $m->id]) . '" data-pjax="0" target="_blank"><i class="fa fa-print" style="color:#0EA5E9"></i> طباعة</a>';
            $menu .= '<div class="jud-act-divider"></div>';
            $menu .= '<a href="' . Url::to(['/contractInstallment/contract-installment/index', 'contract_id' => $m->id]) . '" data-pjax="0"><i class="fa fa-money" style="color:#16A34A"></i> الدفعات</a>';
            $menu .= '<a href="' . Url::to(['/followUp/follow-up/index', 'contract_id' => $m->id]) . '" data-pjax="0"><i class="fa fa-comments" style="color:#6366F1"></i> المتابعة</a>';

            if ($hasCase) {
                $menu .= '<div class="jud-act-divider"></div>';
                $menu .= '<a href="' . Url::to(['/judiciary/judiciary/update', 'id' => $jud->id, 'contract_id' => $m->id]) . '" data-pjax="0"><i class="fa fa-gavel" style="color:#DC2626"></i> ملف القضية</a>';
                $menu .= '<a href="' . Url::to(['/collection/collection/create', 'contract_id' => $m->id]) . '" data-pjax="0"><i class="fa fa-hand-paper-o" style="color:#F59E0B"></i> تحصيل</a>';
            } else {
                $menu .= '<div class="jud-act-divider"></div>';
                $menu .= '<a href="' . Url::to(['/judiciary/judiciary/create', 'contract_id' => $m->id]) . '" data-pjax="0"><i class="fa fa-gavel" style="color:#DC2626"></i> إنشاء قضية</a>';
            }

            if ($isManager) {
                $menu .= '<div class="jud-act-divider"></div>';
                $menu .= '<a href="#" class="yeas-finish" data-url="' . Url::to(['/contracts/contracts/finish', 'id' => $m->id]) . '"><i class="fa fa-check-circle" style="color:#16A34A"></i> إنهاء العقد</a>';
                $menu .= '<a href="#" class="yeas-cancel" data-url="' . Url::to(['/contracts/contracts/cancel', 'id' => $m->id]) . '"><i class="fa fa-ban" style="color:#DC2626"></i> إلغاء العقد</a>';
            }

            $caseIcon = $hasCase
                ? '<span style="color:#16A34A;font-size:14px" title="تم إنشاء القضية"><i class="fa fa-check-circle"></i></span>'
                : '<a href="' . Url::to(['/judiciary/judiciary/create', 'contract_id' => $m->id]) . '" data-pjax="0" class="jud-quick-action" title="إنشاء قضية"><i class="fa fa-gavel"></i></a>';

            return '<div style="display:flex;align-items:center;gap:4px;justify-content:center">'
                . $caseIcon
                . '<div class="jud-act-wrap">'
                . '<button type="button" class="jud-act-trigger"><i class="fa fa-ellipsis-v"></i></button>'
                . '<div class="jud-act-menu">' . $menu . '</div>'
                . '</div>'
                . '</div>';
        },
        'headerOptions' => ['style' => 'width:10%;text-align:center'],
        'contentOptions' => ['style' => 'text-align:center;overflow:visible;position:relative;white-space:nowrap', 'data-label' => ''],
    ],
];
