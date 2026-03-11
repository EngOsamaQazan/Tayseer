<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  سجل الموظفين — الصفحة الرئيسية
 *  ──────────────────────────────────────
 *  قائمة الموظفين مع البحث والفلترة + GridView
 * ═══════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\grid\GridView;
use yii\bootstrap\Modal;
use yii\widgets\Pjax;
use backend\helpers\NameHelper;
use common\helper\Permissions;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string $searchName */
/** @var string $searchCode */
/** @var string $searchDepartment */
/** @var string $searchStatus */
/** @var array $departments */

$this->title = 'سجل الموظفين';

/* ─── تسجيل CSS ─── */
$this->registerCssFile(Yii::getAlias('@web') . '/css/hr.css', ['depends' => ['yii\web\YiiAsset']]);

/* ─── URLs ─── */
$indexUrl  = Url::to(['index']);
$createUrl = Url::to(['create']);
$exportUrl = Url::to(['export']);

/* ─── صورة افتراضية ─── */
$defaultAvatar = Yii::getAlias('@web') . '/img/default-avatar.png';

/* ─── خريطة الحالات (employee_type في قاعدة البيانات) ─── */
$statusMap = [
    'Active'     => ['label' => 'نشط',   'class' => 'label-success'],
    'Suspended'  => ['label' => 'موقوف', 'class' => 'label-danger'],
];

/* ─── خريطة نوع التوظيف (employee_status في قاعدة البيانات) ─── */
$employmentTypeMap = [
    'Full_time'  => 'دوام كامل',
    'Part_time'  => 'دوام جزئي',
];
?>

<div class="hr-page">

    <!-- ╔═══════════════════════════════════════╗
         ║  العنوان وأزرار الإجراءات             ║
         ╚═══════════════════════════════════════╝ -->
    <div class="hr-page-header">
        <div class="hr-page-header-right">
            <h1 class="hr-page-title">
                <i class="fa fa-users"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <span class="hr-page-subtitle">إدارة بيانات الموظفين ومتابعة حالاتهم</span>
        </div>
        <div class="hr-page-header-left">
            <?php if (Permissions::can(Permissions::EMP_CREATE)): ?>
            <?= Html::a(
                '<i class="fa fa-plus"></i> إضافة موظف جديد',
                $createUrl,
                [
                    'class' => 'btn hr-btn-primary',
                    'id' => 'btn-create-employee',
                ]
            ) ?>
            <?php endif ?>
            <?= Html::a(
                '<i class="fa fa-file-excel-o"></i> تصدير',
                $exportUrl,
                ['class' => 'btn btn-default hr-btn-export']
            ) ?>
        </div>
    </div>

    <!-- ╔═══════════════════════════════════════╗
         ║  شريط البحث والفلترة                  ║
         ╚═══════════════════════════════════════╝ -->
    <div class="hr-search-bar">
        <?= Html::beginForm($indexUrl, 'get', ['class' => 'hr-search-form']) ?>

            <div class="hr-search-group">
                <div class="hr-search-field">
                    <i class="fa fa-search hr-search-icon"></i>
                    <?= Html::textInput('search_name', $searchName, [
                        'class' => 'form-control hr-search-input',
                        'placeholder' => 'بحث بالاسم...',
                        'autocomplete' => 'off',
                    ]) ?>
                </div>

                <div class="hr-search-field">
                    <i class="fa fa-id-card hr-search-icon"></i>
                    <?= Html::textInput('search_code', $searchCode, [
                        'class' => 'form-control hr-search-input',
                        'placeholder' => 'رقم الموظف...',
                        'autocomplete' => 'off',
                    ]) ?>
                </div>

                <div class="hr-search-field">
                    <?= Html::dropDownList('search_department', $searchDepartment, $departments, [
                        'class' => 'form-control hr-search-select',
                        'prompt' => '— جميع الأقسام —',
                    ]) ?>
                </div>

                <div class="hr-search-field">
                    <?= Html::dropDownList('search_status', $searchStatus, [
                        'Active'    => 'نشط',
                        'Suspended' => 'موقوف',
                    ], [
                        'class' => 'form-control hr-search-select',
                        'prompt' => '— جميع الحالات —',
                    ]) ?>
                </div>
            </div>

            <div class="hr-search-actions">
                <?= Html::submitButton('<i class="fa fa-filter"></i> بحث', [
                    'class' => 'btn hr-btn-primary btn-sm',
                ]) ?>
                <?= Html::a('<i class="fa fa-times"></i> مسح', $indexUrl, [
                    'class' => 'btn btn-default btn-sm',
                ]) ?>
            </div>

        <?= Html::endForm() ?>
    </div>

    <!-- ╔═══════════════════════════════════════╗
         ║  جدول الموظفين — GridView              ║
         ╚═══════════════════════════════════════╝ -->
    <?php Pjax::begin(['id' => 'crud-datatable-pjax', 'timeout' => 10000]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'id' => 'hr-employees-grid',
        'tableOptions' => ['class' => 'table table-hover hr-grid-table'],
        'headerRowOptions' => ['class' => 'hr-grid-header'],
        'pjax' => false, // already wrapped in Pjax
        'responsive' => true,
        'hover' => true,
        'striped' => false,
        'bordered' => false,
        'condensed' => false,
        'summary' => '<div class="hr-grid-summary">عرض {begin}-{end} من أصل {totalCount} موظف</div>',
        'layout' => "{summary}\n{items}\n<div class='hr-grid-footer'>{pager}</div>",
        'pager' => [
            'options' => ['class' => 'pagination hr-pagination'],
            'maxButtonCount' => 7,
            'firstPageLabel' => '<i class="fa fa-angle-double-right"></i>',
            'lastPageLabel' => '<i class="fa fa-angle-double-left"></i>',
            'prevPageLabel' => '<i class="fa fa-angle-right"></i>',
            'nextPageLabel' => '<i class="fa fa-angle-left"></i>',
        ],
        'columns' => [
            [
                'class' => 'kartik\grid\SerialColumn',
                'header' => '#',
                'headerOptions' => ['style' => 'width:50px;text-align:center'],
                'contentOptions' => ['style' => 'text-align:center;font-weight:600;color:#6b7280'],
            ],
            [
                'header' => 'صورة',
                'headerOptions' => ['style' => 'width:60px;text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
                'format' => 'raw',
                'value' => function ($model) use ($defaultAvatar) {
                    $avatar = !empty($model['avatar']) ? $model['avatar'] : $defaultAvatar;
                    return Html::img($avatar, [
                        'class' => 'hr-grid-avatar t-zoomable',
                        'style' => 'cursor:zoom-in',
                        'onerror' => "this.src='" . $defaultAvatar . "'",
                        'alt' => '',
                    ]);
                },
            ],
            [
                'header' => 'اسم الموظف',
                'format' => 'raw',
                'value' => function ($model) {
                    $name = Html::encode($model['name'] ? NameHelper::short($model['name']) : $model['username']);
                    $email = Html::encode($model['email'] ?? '');
                    $mobile = Html::encode($model['mobile'] ?? '');
                    $html = '<div class="hr-grid-name-cell">';
                    $html .= '<span class="hr-grid-name">' . $name . '</span>';
                    if ($email) {
                        $html .= '<span class="hr-grid-sub"><i class="fa fa-envelope-o"></i> ' . $email . '</span>';
                    }
                    if ($mobile) {
                        $html .= '<span class="hr-grid-sub"><i class="fa fa-phone"></i> ' . $mobile . '</span>';
                    }
                    $html .= '</div>';
                    return $html;
                },
            ],
            [
                'header' => 'رقم الموظف',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
                'format' => 'raw',
                'value' => function ($model) {
                    $code = $model['employee_code'] ?? null;
                    return $code
                        ? '<span class="hr-badge hr-badge--code">' . Html::encode($code) . '</span>'
                        : '<span class="text-muted">—</span>';
                },
            ],
            [
                'header' => 'القسم',
                'value' => function ($model) {
                    return $model['department_name'] ?? '—';
                },
            ],
            [
                'header' => 'المسمى',
                'value' => function ($model) {
                    return $model['designation_name'] ?? '—';
                },
            ],
            [
                'header' => 'نوع التوظيف',
                'headerOptions' => ['style' => 'text-align:center'],
                'contentOptions' => ['style' => 'text-align:center'],
                'format' => 'raw',
                'value' => function ($model) use ($employmentTypeMap) {
                    // employee_status stores employment type (Full_time/Part_time)
                    $type = $model['employee_status'] ?? null;
                    if (!$type) return '<span class="text-muted">—</span>';
                    $label = $employmentTypeMap[$type] ?? $type;
                    return '<span class="hr-badge hr-badge--type">' . Html::encode($label) . '</span>';
                },
            ],
            [
                'header' => 'الحالة',
                'headerOptions' => ['style' => 'text-align:center;width:100px'],
                'contentOptions' => ['style' => 'text-align:center'],
                'format' => 'raw',
                'value' => function ($model) use ($statusMap) {
                    // employee_type stores activation state (Active/Suspended)
                    $status = $model['employee_type'] ?? null;
                    if (!$status) return '<span class="text-muted">—</span>';
                    $info = $statusMap[$status] ?? ['label' => $status, 'class' => 'label-default'];
                    return '<span class="label ' . $info['class'] . ' hr-status-label">'
                        . Html::encode($info['label'])
                        . '</span>';
                },
            ],
            [
                'class' => 'kartik\grid\ActionColumn',
                'header' => 'إجراءات',
                'headerOptions' => ['style' => 'text-align:center;width:170px'],
                'contentOptions' => ['style' => 'text-align:center;white-space:nowrap'],
                'template' => '{toggle} {statement} {view} {update} {delete}',
                'buttons' => [
                    'toggle' => function ($url, $model) {
                        // employee_type stores activation state (Active/Suspended)
                        $isSuspended = ($model['employee_type'] === 'Suspended');
                        $icon = $isSuspended ? 'fa-toggle-off' : 'fa-toggle-on';
                        $title = $isSuspended ? 'تفعيل الموظف' : 'تعطيل الموظف';
                        $btnClass = $isSuspended
                            ? 'hr-action-btn hr-action-btn--activate'
                            : 'hr-action-btn hr-action-btn--suspend';
                        $confirmMsg = $isSuspended
                            ? 'هل أنت متأكد من تفعيل هذا الموظف؟'
                            : 'هل أنت متأكد من تعطيل هذا الموظف؟ لن يظهر في مسيرات الرواتب.';
                        return Html::a(
                            '<i class="fa ' . $icon . '"></i>',
                            Url::to(['toggle-status', 'id' => $model['id']]),
                            [
                                'class' => 'btn btn-sm ' . $btnClass,
                                'title' => $title,
                                'data-toggle' => 'tooltip',
                                'data-confirm' => $confirmMsg,
                                'data-method' => 'post',
                                'data-pjax' => '1',
                            ]
                        );
                    },
                    'statement' => function ($url, $model) {
                        return Html::a(
                            '<i class="fa fa-file-text-o"></i>',
                            Url::to(['statement', 'id' => $model['id']]),
                            [
                                'class' => 'btn btn-sm hr-action-btn',
                                'style' => 'background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe',
                                'title' => 'كشف الحساب السنوي',
                                'data-toggle' => 'tooltip',
                            ]
                        );
                    },
                    'view' => function ($url, $model) {
                        return Html::a(
                            '<i class="fa fa-eye"></i>',
                            Url::to(['view', 'id' => $model['id']]),
                            [
                                'class' => 'btn btn-sm hr-action-btn hr-action-btn--view',
                                'title' => 'عرض',
                                'data-toggle' => 'tooltip',
                            ]
                        );
                    },
                    'update' => function ($url, $model) {
                        if (!Permissions::can(Permissions::EMP_UPDATE)) return '';
                        $extendedId = $model['extended_id'] ?? null;
                        if (!$extendedId) {
                            if (!Permissions::can(Permissions::EMP_CREATE)) return '';
                            return Html::a(
                                '<i class="fa fa-plus-circle"></i>',
                                Url::to(['create', 'user_id' => $model['id']]),
                                [
                                    'class' => 'btn btn-sm hr-action-btn hr-action-btn--create',
                                    'title' => 'إنشاء ملف موسع',
                                    'data-toggle' => 'tooltip',
                                ]
                            );
                        }
                        return Html::a(
                            '<i class="fa fa-pencil"></i>',
                            Url::to(['update', 'id' => $extendedId]),
                            [
                                'class' => 'btn btn-sm hr-action-btn hr-action-btn--edit',
                                'title' => 'تعديل',
                                'data-toggle' => 'tooltip',
                            ]
                        );
                    },
                    'delete' => function ($url, $model) {
                        if (!Permissions::can(Permissions::EMP_DELETE)) return '';
                        $extendedId = $model['extended_id'] ?? null;
                        if (!$extendedId) return '';
                        return Html::a(
                            '<i class="fa fa-trash"></i>',
                            Url::to(['delete', 'id' => $extendedId]),
                            [
                                'class' => 'btn btn-sm hr-action-btn hr-action-btn--delete',
                                'title' => 'حذف',
                                'data-toggle' => 'tooltip',
                                'data-confirm' => 'هل أنت متأكد من حذف سجل هذا الموظف؟',
                                'data-method' => 'post',
                                'data-pjax' => '1',
                            ]
                        );
                    },
                ],
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div><!-- /.hr-page -->

<?php if ($suspendedCount > 0): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var table = document.querySelector('#hr-employees-grid table.hr-grid-table tbody');
    if (!table) return;
    var rows = table.querySelectorAll('tr');
    var activeCount = <?= $activeCount ?>;
    var suspendedCount = <?= $suspendedCount ?>;

    // إدراج صف فاصل بعد آخر مستخدم نشط
    if (activeCount > 0 && activeCount < rows.length) {
        var sepRow = document.createElement('tr');
        sepRow.className = 'hr-suspended-separator';
        var sepCell = document.createElement('td');
        sepCell.colSpan = rows[0].cells.length;
        sepCell.innerHTML = '<div style="display:flex;align-items:center;gap:12px;padding:6px 0">'
            + '<div style="flex:1;height:1px;background:linear-gradient(to left,transparent,#fca5a5,transparent)"></div>'
            + '<span style="font-size:12px;font-weight:700;color:#991b1b;background:#fee2e2;padding:4px 14px;border-radius:20px;white-space:nowrap">'
            + '<i class="fa fa-ban"></i> معطّلون (' + suspendedCount + ')</span>'
            + '<div style="flex:1;height:1px;background:linear-gradient(to right,transparent,#fca5a5,transparent)"></div></div>';
        sepRow.appendChild(sepCell);
        rows[activeCount].parentNode.insertBefore(sepRow, rows[activeCount]);
    }

    // تطبيق style على صفوف المعطلين
    var allRows = table.querySelectorAll('tr:not(.hr-suspended-separator)');
    for (var i = activeCount; i < allRows.length; i++) {
        allRows[i].style.opacity = '0.55';
        allRows[i].style.background = '#fff8f8';
    }
});
</script>
<?php endif; ?>


<!-- ══════════════════════════════════════════════════════
     مودال AJAX للعرض والتعديل
     ══════════════════════════════════════════════════════ -->
<?php
Modal::begin([
    'id' => 'hr-modal',
    'size' => Modal::SIZE_LARGE,
    'header' => '<h4 class="modal-title" id="hr-modal-title"></h4>',
    'headerOptions' => ['class' => 'hr-modal-header'],
    'options' => [
        'tabindex' => false,
        'class' => 'hr-modal',
    ],
]);
echo '<div id="hr-modal-content"></div>';
Modal::end();
?>


<?php
/* ═══════════════════════════════════════════════════════════════
 *  JavaScript
 * ═══════════════════════════════════════════════════════════════ */
$js = <<<JS

// Tooltips
$('[data-toggle="tooltip"]').tooltip({container: 'body', placement: 'top'});

// Re-init tooltips after Pjax reload
$(document).on('pjax:complete', function() {
    $('[data-toggle="tooltip"]').tooltip({container: 'body', placement: 'top'});
});

JS;

$this->registerJs($js, \yii\web\View::POS_READY);

/* ─── Inline CSS enhancements ─── */
$css = <<<CSS

/* ─── Page Header ─── */
.hr-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}
.hr-page-header-right { flex: 1; }
.hr-page-header-left {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-shrink: 0;
}
.hr-page-title {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 4px;
}
.hr-page-title i {
    color: #800020;
    margin-left: 8px;
}
.hr-page-subtitle {
    font-size: 13px;
    color: #64748b;
}

/* ─── Primary Button ─── */
.hr-btn-primary {
    background: #800020 !important;
    border-color: #800020 !important;
    color: #fff !important;
    border-radius: 8px;
    padding: 8px 18px;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.2s;
}
.hr-btn-primary:hover {
    background: #600018 !important;
    border-color: #600018 !important;
    color: #fff !important;
    box-shadow: 0 4px 12px rgba(128,0,32,0.3);
}
.hr-btn-export {
    border-radius: 8px;
    padding: 8px 18px;
    font-size: 13px;
}

/* ─── Search Bar ─── */
.hr-search-bar {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.hr-search-form {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.hr-search-group {
    display: flex;
    flex: 1;
    gap: 10px;
    flex-wrap: wrap;
}
.hr-search-field {
    position: relative;
    flex: 1;
    min-width: 160px;
}
.hr-search-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
    z-index: 2;
}
.hr-search-input {
    padding-right: 36px !important;
    border-radius: 8px !important;
    border: 1px solid #e2e8f0 !important;
    font-size: 13px !important;
    height: 38px !important;
}
.hr-search-input:focus {
    border-color: #800020 !important;
    box-shadow: 0 0 0 3px rgba(128,0,32,0.08) !important;
}
.hr-search-select {
    border-radius: 8px !important;
    border: 1px solid #e2e8f0 !important;
    font-size: 13px !important;
    height: 38px !important;
}
.hr-search-select:focus {
    border-color: #800020 !important;
    box-shadow: 0 0 0 3px rgba(128,0,32,0.08) !important;
}
.hr-search-actions {
    display: flex;
    gap: 6px;
    flex-shrink: 0;
}

/* ─── GridView Styling ─── */
.hr-grid-table {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
}
.hr-grid-table thead th {
    background: #f8fafc !important;
    color: #475569 !important;
    font-weight: 600;
    font-size: 12px;
    padding: 12px 14px !important;
    border-bottom: 2px solid #e2e8f0 !important;
    white-space: nowrap;
}
.hr-grid-table tbody td {
    padding: 10px 14px !important;
    vertical-align: middle !important;
    font-size: 13px;
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
}
.hr-grid-table tbody tr:hover {
    background: #fefce8 !important;
}

/* ─── Avatar ─── */
.hr-grid-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #f1f5f9;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

/* ─── Name Cell ─── */
.hr-grid-name-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.hr-grid-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 13px;
}
.hr-grid-sub {
    font-size: 11px;
    color: #94a3b8;
}
.hr-grid-sub i {
    margin-left: 3px;
    width: 12px;
    text-align: center;
}

/* ─── Badges ─── */
.hr-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}
.hr-badge--code {
    background: #f0f4ff;
    color: #3b52a3;
    font-family: monospace;
    letter-spacing: 0.5px;
}
.hr-badge--type {
    background: #f0fdf4;
    color: #166534;
}
.hr-status-label {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

/* ─── Action Buttons ─── */
.hr-action-btn {
    width: 32px;
    height: 32px;
    padding: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e2e8f0;
    background: #fff;
    transition: all 0.15s;
    margin: 0 2px;
}
.hr-action-btn:hover { transform: translateY(-1px); box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
.hr-action-btn--view { color: #0284c7; }
.hr-action-btn--view:hover { background: #e0f2fe; border-color: #0284c7; color: #0284c7; }
.hr-action-btn--edit { color: #d97706; }
.hr-action-btn--edit:hover { background: #fef3c7; border-color: #d97706; color: #d97706; }
.hr-action-btn--create { color: #059669; }
.hr-action-btn--create:hover { background: #d1fae5; border-color: #059669; color: #059669; }
.hr-action-btn--delete { color: #dc2626; }
.hr-action-btn--delete:hover { background: #fee2e2; border-color: #dc2626; color: #dc2626; }
.hr-action-btn--suspend { color: #059669; font-size: 16px; }
.hr-action-btn--suspend:hover { background: #d1fae5; border-color: #059669; color: #059669; }
.hr-action-btn--activate { color: #9ca3af; font-size: 16px; }
.hr-action-btn--activate:hover { background: #f3f4f6; border-color: #9ca3af; color: #6b7280; }

/* ─── Grid Summary & Pager ─── */
.hr-grid-summary {
    font-size: 12px;
    color: #64748b;
    padding: 8px 4px;
}
.hr-grid-footer {
    display: flex;
    justify-content: center;
    padding: 12px 0;
}
.hr-pagination > li > a,
.hr-pagination > li > span {
    border-radius: 8px !important;
    margin: 0 2px;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-size: 13px;
    min-width: 36px;
    text-align: center;
}
.hr-pagination > .active > a,
.hr-pagination > .active > span {
    background: #800020 !important;
    border-color: #800020 !important;
    color: #fff !important;
}

/* ─── Modal ─── */
.hr-modal .modal-header {
    background: linear-gradient(135deg, #800020, #a0003a);
    color: #fff;
    border-bottom: none;
    padding: 16px 20px;
}
.hr-modal .modal-header .close {
    color: #fff;
    opacity: 0.8;
}
.hr-modal .modal-title {
    color: #fff;
    font-weight: 700;
}
.hr-modal .modal-content {
    border: none;
    border-radius: 12px;
    overflow: hidden;
}

CSS;

$this->registerCss($css);
?>
