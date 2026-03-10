# Advanced Patterns Reference

Detailed patterns for complex UI scenarios in Tayseer ERP.

## AJAX CRUD (Modal-Based)

For modules using modal-based create/update/view:

```php
<?php
// index.php with AJAX CRUD
use johnitvn\ajaxcrud\CrudAsset;
use yii\bootstrap\Modal;

CrudAsset::register($this);
?>

<?php Modal::begin([
    'id' => 'ajaxCrudModal',
    'header' => '<h4 class="modal-title"></h4>',
    'options' => ['tabindex' => false],
    'size' => Modal::SIZE_LARGE,
]); ?>
<?php Modal::end(); ?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        // ... columns ...
        [
            'class' => ActionColumn::class,
            'template' => '{view} {update} {delete}',
            'buttons' => [
                'view' => function ($url) {
                    return Html::a('<i class="fa fa-eye"></i>', $url, [
                        'class' => 'btn btn-xs btn-info',
                        'role' => 'modal-remote',
                        'title' => 'عرض',
                    ]);
                },
                'update' => function ($url) {
                    return Html::a('<i class="fa fa-edit"></i>', $url, [
                        'class' => 'btn btn-xs btn-primary',
                        'role' => 'modal-remote',
                        'title' => 'تعديل',
                    ]);
                },
            ],
        ],
    ],
]) ?>
```

## Dynamic Forms (Repeating Rows)

For invoice items, payment schedules, etc:

```php
<?php
use wbraganca\dynamicform\DynamicFormWidget;
?>

<?php DynamicFormWidget::begin([
    'widgetContainer' => 'dynamicform_wrapper',
    'widgetBody' => '.container-items',
    'widgetItem' => '.item',
    'limit' => 50,
    'min' => 1,
    'insertButton' => '.add-item',
    'deleteButton' => '.remove-item',
    'model' => $modelsItems[0],
    'formId' => 'main-form',
    'formFields' => ['description', 'quantity', 'unit_price'],
]); ?>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th class="text-center" style="width:50px">#</th>
            <th>الوصف</th>
            <th style="width:120px">الكمية</th>
            <th style="width:150px">سعر الوحدة</th>
            <th style="width:150px">المجموع</th>
            <th style="width:50px"></th>
        </tr>
    </thead>
    <tbody class="container-items">
        <?php foreach ($modelsItems as $i => $modelItem): ?>
        <tr class="item">
            <td class="text-center serial-number"></td>
            <td>
                <?= $form->field($modelItem, "[{$i}]description")->label(false)->textInput() ?>
            </td>
            <td>
                <?= $form->field($modelItem, "[{$i}]quantity")->label(false)->textInput([
                    'type' => 'number', 'min' => 1, 'class' => 'form-control qty-input'
                ]) ?>
            </td>
            <td>
                <?= $form->field($modelItem, "[{$i}]unit_price")->label(false)->textInput([
                    'type' => 'number', 'step' => '0.001', 'class' => 'form-control price-input'
                ]) ?>
            </td>
            <td class="text-left">
                <span class="line-total">0.000</span> د.أ
            </td>
            <td>
                <button type="button" class="remove-item btn btn-danger btn-xs">
                    <i class="fa fa-minus"></i>
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" class="text-left"><strong>المجموع الكلي</strong></td>
            <td class="text-left"><strong class="grand-total">0.000</strong> د.أ</td>
            <td>
                <button type="button" class="add-item btn btn-success btn-xs">
                    <i class="fa fa-plus"></i>
                </button>
            </td>
        </tr>
    </tfoot>
</table>

<?php DynamicFormWidget::end(); ?>
```

## Multi-Step Form (Wizard)

For complex entities like contracts:

```php
<div class="box box-primary">
    <div class="box-header">
        <!-- Step indicators -->
        <ul class="jadal-wizard-steps">
            <li class="active" data-step="1">
                <span class="step-number">1</span>
                <span class="step-label">بيانات العقد</span>
            </li>
            <li data-step="2">
                <span class="step-number">2</span>
                <span class="step-label">بيانات العميل</span>
            </li>
            <li data-step="3">
                <span class="step-number">3</span>
                <span class="step-label">جدول الأقساط</span>
            </li>
            <li data-step="4">
                <span class="step-number">4</span>
                <span class="step-label">المراجعة والتأكيد</span>
            </li>
        </ul>
    </div>
    <div class="box-body">
        <div class="wizard-step active" id="step-1">
            <!-- Step 1 fields -->
        </div>
        <div class="wizard-step" id="step-2">
            <!-- Step 2 fields -->
        </div>
        <!-- ... -->
    </div>
    <div class="box-footer jadal-form-actions">
        <button type="button" class="btn btn-default wizard-prev" style="display:none;">
            <i class="fa fa-arrow-right"></i> السابق
        </button>
        <button type="button" class="btn btn-primary wizard-next">
            التالي <i class="fa fa-arrow-left"></i>
        </button>
        <button type="submit" class="btn btn-success wizard-submit" style="display:none;">
            <i class="fa fa-check"></i> حفظ العقد
        </button>
    </div>
</div>
```

Wizard CSS:

```css
.jadal-wizard-steps {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    counter-reset: step;
    direction: rtl;
}
.jadal-wizard-steps li {
    flex: 1;
    text-align: center;
    position: relative;
    padding: 12px 0;
}
.jadal-wizard-steps li::after {
    content: '';
    position: absolute;
    top: 24px;
    left: 0;
    width: 50%;
    height: 2px;
    background: var(--clr-border);
}
.jadal-wizard-steps li.active::after,
.jadal-wizard-steps li.completed::after {
    background: var(--clr-primary);
}
.jadal-wizard-steps .step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--clr-border);
    color: var(--clr-text-muted);
    font-weight: 700;
    margin-bottom: 4px;
}
.jadal-wizard-steps li.active .step-number {
    background: var(--clr-primary);
    color: #fff;
}
.jadal-wizard-steps li.completed .step-number {
    background: var(--clr-success);
    color: #fff;
}
.jadal-wizard-steps .step-label {
    display: block;
    font-size: 12px;
    color: var(--clr-text-muted);
    font-family: var(--font-heading);
}
.jadal-wizard-steps li.active .step-label {
    color: var(--clr-primary);
    font-weight: 700;
}
```

## Status Workflow Display

For entities with status progression (contracts, tasks, cases):

```php
<div class="jadal-status-flow">
    <?php
    $statuses = ['new' => 'جديد', 'review' => 'قيد المراجعة', 'approved' => 'موافق عليه', 'active' => 'فعال', 'closed' => 'مغلق'];
    $currentIndex = array_search($model->status, array_keys($statuses));
    $i = 0;
    foreach ($statuses as $key => $label):
        $class = $i < $currentIndex ? 'completed' : ($i === $currentIndex ? 'current' : 'pending');
    ?>
    <div class="status-step <?= $class ?>">
        <div class="status-dot"></div>
        <span class="status-label"><?= $label ?></span>
    </div>
    <?php $i++; endforeach; ?>
</div>
```

```css
.jadal-status-flow {
    display: flex;
    justify-content: space-between;
    padding: 16px 0;
    direction: rtl;
}
.status-step {
    text-align: center;
    flex: 1;
    position: relative;
}
.status-step::before {
    content: '';
    position: absolute;
    top: 8px;
    right: 50%;
    width: 100%;
    height: 2px;
    background: var(--clr-border);
    z-index: 0;
}
.status-step:first-child::before { display: none; }
.status-step.completed::before { background: var(--clr-success); }
.status-step.current::before { background: var(--clr-primary); }

.status-dot {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--clr-border);
    margin: 0 auto 6px;
    position: relative;
    z-index: 1;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px var(--clr-border);
}
.status-step.completed .status-dot {
    background: var(--clr-success);
    box-shadow: 0 0 0 2px var(--clr-success);
}
.status-step.current .status-dot {
    background: var(--clr-primary);
    box-shadow: 0 0 0 2px var(--clr-primary);
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0% { box-shadow: 0 0 0 2px var(--clr-primary); }
    50% { box-shadow: 0 0 0 6px rgba(128,0,32,0.2); }
    100% { box-shadow: 0 0 0 2px var(--clr-primary); }
}

.status-label {
    font-size: 11px;
    color: var(--clr-text-muted);
    font-family: var(--font-heading);
}
.status-step.current .status-label {
    color: var(--clr-primary);
    font-weight: 700;
}
```

## Search Filters Panel

For index pages with advanced search:

```php
<!-- _search.php -->
<?php
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

$form = ActiveForm::begin([
    'action' => ['index'],
    'method' => 'get',
    'options' => ['class' => 'jadal-search-panel'],
]);
?>

<div class="box box-default collapsed-box">
    <div class="box-header with-border" data-widget="collapse">
        <h3 class="box-title"><i class="fa fa-search"></i> بحث متقدم</h3>
        <div class="box-tools">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-plus"></i>
            </button>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-3">
                <?= $form->field($model, 'name')->textInput(['placeholder' => 'الاسم...']) ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'status')->widget(Select2::class, [
                    'data' => $statusOptions,
                    'options' => ['placeholder' => 'الكل'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ]) ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'date_from')->widget(FlatpickrWidget::class, [
                    'locale' => 'ar',
                    'options' => ['placeholder' => 'من تاريخ...'],
                ]) ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'date_to')->widget(FlatpickrWidget::class, [
                    'locale' => 'ar',
                    'options' => ['placeholder' => 'إلى تاريخ...'],
                ]) ?>
            </div>
        </div>
    </div>
    <div class="box-footer">
        <?= Html::submitButton('<i class="fa fa-search"></i> بحث', ['class' => 'btn btn-primary btn-sm']) ?>
        <?= Html::a('<i class="fa fa-refresh"></i> مسح', ['index'], ['class' => 'btn btn-default btn-sm']) ?>
    </div>
</div>

<?php ActiveForm::end(); ?>
```

## Responsive Table Patterns

For tables that need to work on mobile:

```css
@media (max-width: 768px) {
    .kv-grid-table {
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .kv-grid-table thead th {
        font-size: 11px;
        padding: 6px 4px;
    }
    .kv-grid-table tbody td {
        font-size: 12px;
        padding: 6px 4px;
    }
    .box-header .box-title {
        font-size: 14px;
    }
    .box-tools .btn {
        font-size: 11px;
        padding: 4px 8px;
    }
    .stat-card .stat-value {
        font-size: 22px;
    }
}
```

## Toast Notifications

For user feedback after actions:

```javascript
// Success
toastr.success('تم الحفظ بنجاح', 'نجاح');

// Error
toastr.error('حدث خطأ أثناء الحفظ', 'خطأ');

// Warning
toastr.warning('يرجى التأكد من البيانات المدخلة', 'تنبيه');

// Info
toastr.info('جاري تحميل البيانات...', 'معلومة');

// Toastr RTL config (already in app)
toastr.options = {
    rtl: true,
    positionClass: 'toast-top-left',
    timeOut: 4000,
    progressBar: true,
    closeButton: true,
};
```

## RBAC-Aware View Patterns

Always check permissions before showing actions:

```php
// Show button only if user has permission
<?php if (Yii::$app->user->can('create-contract')): ?>
    <?= Html::a('<i class="fa fa-plus"></i> عقد جديد', ['create'], ['class' => 'btn btn-success']) ?>
<?php endif; ?>

// Show column only if user has permission
'columns' => array_filter([
    ['class' => SerialColumn::class],
    'name',
    'status',
    Yii::$app->user->can('view-financial') ? [
        'attribute' => 'amount',
        'format' => ['currency', 'JOD'],
    ] : null,
    Yii::$app->user->can('manage-records') ? [
        'class' => ActionColumn::class,
        'template' => '{update} {delete}',
    ] : null,
]),
```
