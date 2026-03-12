<?php
/**
 * أعمدة جدول القضايا
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use common\helper\Permissions;
use backend\helpers\NameHelper;

$isPendingFilter = (bool) Yii::$app->request->get('pending_requests');

$columns = [
    /* # */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'id',
        'label' => '#',
        'headerOptions' => ['style' => 'width:5%;text-align:center'],
        'contentOptions' => ['style' => 'text-align:center;font-weight:600;color:#94A3B8;font-size:11px', 'data-label' => '#'],
    ],

    /* رقم العقد */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'contract_id',
        'label' => 'العقد',
        'format' => 'raw',
        'value' => fn($m) => '<a href="' . Url::to(['/followUp/follow-up/index', 'contract_id' => $m->contract_id]) . '" style="font-weight:700;color:#800020;text-decoration:none">' . $m->contract_id . '</a>',
        'headerOptions' => ['style' => 'width:7%;text-align:center'],
        'contentOptions' => ['style' => 'text-align:center', 'data-label' => 'العقد'],
    ],

    /* الأطراف (عميل + وظيفة) */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'الأطراف',
        'format' => 'raw',
        'value' => function ($m) {
            static $jobCache = [];
            $customers = $m->customersAndGuarantor;
            if (empty($customers)) return '<span style="color:#CBD5E1">—</span>';

            $rows = [];
            foreach ($customers as $c) {
                $full = $c->name;
                $short = NameHelper::short($full);
                $jid = $c->job_title;
                $jobName = '';
                if ($jid) {
                    if (!isset($jobCache[$jid])) {
                        $job = \backend\modules\jobs\models\Jobs::findOne($jid);
                        $jobCache[$jid] = $job ? $job->name : '';
                    }
                    $jobName = $jobCache[$jid];
                }
                $nameHtml = '<span style="font-weight:600;color:#1E293B;font-size:11px" title="' . Html::encode($full) . '">' . Html::encode($short) . '</span>';
                if ($jobName) {
                    $nameHtml .= ' <span style="display:inline-block;padding:0 5px;border-radius:4px;font-size:9px;font-weight:600;background:#F0FDF4;color:#15803D;vertical-align:middle">' . Html::encode($jobName) . '</span>';
                }
                $rows[] = $nameHtml;
            }
            return '<div style="display:flex;flex-direction:column;gap:2px;max-height:60px;overflow-y:auto;scrollbar-width:thin">' . implode('', array_map(fn($r) => '<div style="white-space:nowrap">' . $r . '</div>', $rows)) . '</div>';
        },
        'headerOptions' => ['style' => 'width:22%'],
        'contentOptions' => ['style' => 'max-width:220px;padding:4px 6px', 'data-label' => 'الأطراف'],
    ],

    /* المحكمة */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'court_id',
        'label' => 'المحكمة',
        'format' => 'raw',
        'value' => function ($m) {
            $name = $m->court->name ?? '—';
            return '<span style="font-size:11px;color:#475569">' . Html::encode($name) . '</span>';
        },
        'headerOptions' => ['style' => 'width:14%'],
        'contentOptions' => ['style' => 'max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap', 'data-label' => 'المحكمة'],
    ],

    /* المحامي */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'lawyer_id',
        'label' => 'المحامي',
        'format' => 'raw',
        'value' => function ($m) {
            $full = $m->lawyer->name ?? '—';
            if ($full === '—') return '<span style="color:#CBD5E1">—</span>';
            $short = NameHelper::short($full);
            return '<span style="font-size:11px;color:#475569" title="' . Html::encode($full) . '">' . Html::encode($short) . '</span>';
        },
        'headerOptions' => ['style' => 'width:10%'],
        'contentOptions' => ['style' => 'white-space:nowrap', 'data-label' => 'المحامي'],
    ],

    /* رقم القضية - السنة */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'judiciary_number',
        'label' => 'رقم القضية',
        'format' => 'raw',
        'value' => function ($m) {
            $num = $m->judiciary_number ?: '—';
            $year = $m->year ?: '';
            if ($year) {
                return '<span style="font-family:\'Courier New\',monospace;font-weight:700;color:#1E293B;font-size:12px">' . $num . '</span>'
                     . '<span style="color:#94A3B8;font-size:10px;margin:0 2px">-</span>'
                     . '<span style="font-family:\'Courier New\',monospace;color:#64748B;font-size:11px">' . $year . '</span>';
            }
            return '<span style="font-family:\'Courier New\',monospace;color:#64748B">' . $num . '</span>';
        },
        'headerOptions' => ['style' => 'width:10%;text-align:center'],
        'contentOptions' => ['style' => 'text-align:center;white-space:nowrap', 'data-label' => 'رقم القضية'],
    ],

    /* آخر إجراء لكل طرف */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'آخر إجراء',
        'format' => 'raw',
        'value' => function ($m) {
            static $cache = null;
            if ($cache === null) {
                $cache = [];
                $db = Yii::$app->db;
                $tblJa = $db->tablePrefix . 'judiciary_actions';
                $jaColumns = $db->getTableSchema($tblJa)->columnNames;
                $hasNature = in_array('action_nature', $jaColumns);
                $natureSel = $hasNature ? ', ja.action_nature' : '';

                $rows = $db->createCommand(
                    "SELECT jca.judiciary_id, jca.customers_id, ja.name AS act_name,
                            cu.name AS cust_name, jca.action_date $natureSel
                     FROM {{%judiciary_customers_actions}} jca
                     INNER JOIN {{%judiciary_actions}} ja ON ja.id = jca.judiciary_actions_id
                     INNER JOIN {{%customers}} cu ON cu.id = jca.customers_id
                     WHERE jca.is_deleted = 0
                       AND jca.id = (
                           SELECT MAX(j2.id) FROM {{%judiciary_customers_actions}} j2
                           WHERE j2.judiciary_id = jca.judiciary_id
                             AND j2.customers_id = jca.customers_id
                             AND j2.is_deleted = 0
                       )"
                )->queryAll();
                foreach ($rows as $r) {
                    $jid = (int)$r['judiciary_id'];
                    if (!isset($cache[$jid])) $cache[$jid] = [];
                    $cache[$jid][] = $r;
                }
            }
            $items = $cache[$m->id] ?? [];
            if (empty($items)) return '<span style="color:#CBD5E1;font-size:11px">لا يوجد</span>';

            $natureColors = [
                'request' => '#3B82F6', 'document' => '#8B5CF6',
                'doc_status' => '#F59E0B', 'process' => '#10B981',
            ];

            $html = '';
            foreach ($items as $a) {
                $clr = $natureColors[$a['action_nature'] ?? ''] ?? '#64748B';
                $actName = $a['act_name'];
                $custShort = NameHelper::short($a['cust_name']);
                $date = $a['action_date'] ? date('m-d', strtotime($a['action_date'])) : '';

                $html .= '<div style="display:flex;align-items:baseline;gap:4px;white-space:nowrap;overflow:hidden">'
                    . '<span style="font-size:10px;font-weight:600;color:' . $clr . ';overflow:hidden;text-overflow:ellipsis" title="' . Html::encode($actName) . '">' . Html::encode($actName) . '</span>'
                    . '<span style="font-size:9px;color:#94A3B8;flex-shrink:0">' . Html::encode($custShort) . ($date ? ' · ' . $date : '') . '</span>'
                    . '</div>';
            }
            return '<div style="display:flex;flex-direction:column;gap:2px;max-width:220px;max-height:60px;overflow-y:auto;scrollbar-width:thin">' . $html . '</div>';
        },
        'headerOptions' => ['style' => 'width:16%'],
        'contentOptions' => ['style' => 'padding:4px 6px', 'data-label' => 'آخر إجراء'],
    ],

    /* الإجراءات */
    $isPendingFilter ? [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'الطلبات المعلّقة',
        'format' => 'raw',
        'value' => function ($m) {
            static $pendingCache = null;
            if ($pendingCache === null) {
                $pendingCache = [];
                $rows = Yii::$app->db->createCommand(
                    "SELECT jca.id, jca.judiciary_id, ja.name AS action_name, cu.name AS customer_name, jca.action_date
                     FROM {{%judiciary_customers_actions}} jca
                     INNER JOIN {{%judiciary_actions}} ja ON ja.id = jca.judiciary_actions_id
                     INNER JOIN {{%customers}} cu ON cu.id = jca.customers_id
                     WHERE jca.request_status = 'pending'
                       AND (jca.is_deleted = 0 OR jca.is_deleted IS NULL)"
                )->queryAll();
                foreach ($rows as $r) {
                    $jid = (int)$r['judiciary_id'];
                    if (!isset($pendingCache[$jid])) $pendingCache[$jid] = [];
                    $pendingCache[$jid][] = $r;
                }
            }
            $items = $pendingCache[$m->id] ?? [];
            if (empty($items)) return '<span style="color:#CBD5E1">—</span>';

            $html = '';
            foreach ($items as $a) {
                $id = $a['id'];
                $custShort = NameHelper::short($a['customer_name']);
                $date = $a['action_date'] ? date('m-d', strtotime($a['action_date'])) : '';
                $html .= '<div class="pra-item" id="pra-' . $id . '">'
                    . '<div class="pra-row">'
                    . '<div class="pra-info">'
                    . '<span class="pra-name">' . Html::encode($a['action_name']) . '</span>'
                    . '<span class="pra-meta">' . Html::encode($custShort) . ($date ? ' · ' . $date : '') . '</span>'
                    . '</div>'
                    . '<div class="pra-btns" id="pra-btns-' . $id . '">'
                    . '<button type="button" class="pra-btn pra-approve" data-id="' . $id . '" title="موافقة"><i class="fa fa-check"></i></button>'
                    . '<button type="button" class="pra-btn pra-reject" data-id="' . $id . '" title="رفض"><i class="fa fa-times"></i></button>'
                    . '</div>'
                    . '</div>'
                    . '<div class="pra-df" id="pra-df-' . $id . '">'
                    . '<textarea class="pra-input" placeholder="نص القرار (اختياري)..." rows="1"></textarea>'
                    . '<div class="pra-df-actions">'
                    . '<button type="button" class="pra-submit" data-id="' . $id . '">تأكيد</button>'
                    . '<button type="button" class="pra-df-cancel">إلغاء</button>'
                    . '</div></div>'
                    . '</div>';
            }
            return '<div class="pra-wrap">' . $html . '</div>';
        },
        'headerOptions' => ['style' => 'width:22%;text-align:center'],
        'contentOptions' => ['style' => 'padding:4px 6px;overflow:visible', 'data-label' => 'طلبات معلقة'],
    ] : false,

    [
        'class' => '\kartik\grid\DataColumn',
        'label' => '',
        'format' => 'raw',
        'value' => function ($m) {
            $addActionUrl = Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/create-followup-judicary-custamer-action', 'contractID' => $m->contract_id]);
            $editUrl  = Url::to(['/judiciary/judiciary/update', 'id' => $m->id, 'contract_id' => $m->contract_id]);
            $printUrl = Url::to(['/judiciary/judiciary/print-case', 'id' => $m->id]);
            $followUrl = Url::to(['/followUp/follow-up/index', 'contract_id' => $m->contract_id]);
            $delUrl   = Url::to(['/judiciary/judiciary/delete', 'id' => $m->id]);

            $timelineUrl = Url::to(['/judiciary/judiciary/case-timeline', 'id' => $m->id]);

            $viewUrl = Url::to(['/judiciary/judiciary/view', 'id' => $m->id]);

            $menu = '';
            $menu .= '<a href="' . $viewUrl . '" data-pjax="0"><i class="fa fa-eye" style="color:#8B5CF6"></i> مشاهدة</a>';
            if (Permissions::can(Permissions::JUD_CREATE)) {
                $menu .= '<a href="' . $addActionUrl . '" role="modal-remote" data-pjax="0"><i class="fa fa-plus" style="color:#16A34A"></i> إضافة إجراء</a>';
                $menu .= '<div class="jud-act-divider"></div>';
            }
            if (Permissions::can(Permissions::JUD_UPDATE)) {
                $menu .= '<a href="' . $editUrl . '" data-pjax="0"><i class="fa fa-pencil" style="color:#3B82F6"></i> تعديل</a>';
            }
            $menu .= '<a href="' . $printUrl . '" data-pjax="0" target="_blank"><i class="fa fa-print" style="color:#0EA5E9"></i> طباعة</a>';
            if (Permissions::can(Permissions::JUD_DELETE)) {
                $menu .= '<div class="jud-act-divider"></div>';
                $menu .= '<a href="' . $delUrl . '" data-method="post" data-confirm="هل أنت متأكد من حذف هذه القضية؟"><i class="fa fa-trash" style="color:#EF4444"></i> حذف</a>';
            }

            $caseLabel = $m->judiciary_number ? ($m->judiciary_number . '/' . ($m->year ?: '')) : ('#' . $m->id);
            $customers = $m->customersAndGuarantor ?? [];
            $firstCustomer = !empty($customers) ? NameHelper::short(reset($customers)->name) : '';
            $pinLabel = $caseLabel;
            $pinExtra = $firstCustomer;

            return '<div style="display:flex;align-items:center;gap:4px;justify-content:center">'
                . '<button type="button" class="pin-btn" data-item-id="' . $m->id . '" data-label="' . Html::encode($pinLabel) . '" data-extra="' . Html::encode($pinExtra) . '" title="تثبيت"><i class="fa fa-thumb-tack"></i></button>'
                . '<button type="button" class="jud-timeline-btn" data-url="' . $timelineUrl . '" data-label="' . Html::encode($caseLabel) . '" title="متابعة القضية"><i class="fa fa-dashboard"></i> متابعة</button>'
                . '<div class="jud-act-wrap">'
                . '<button type="button" class="jud-act-trigger"><i class="fa fa-ellipsis-v"></i></button>'
                . '<div class="jud-act-menu">' . $menu . '</div>'
                . '</div>'
                . '</div>';
        },
        'headerOptions' => ['style' => 'width:12%;text-align:center'],
        'contentOptions' => ['style' => 'text-align:center;overflow:visible;position:relative;white-space:nowrap', 'data-label' => ''],
    ],
];

return array_values(array_filter($columns));
