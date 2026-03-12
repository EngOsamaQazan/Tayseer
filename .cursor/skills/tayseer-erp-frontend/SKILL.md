---
name: tayseer-erp-frontend
description: "Enterprise ERP frontend skill for Tayseer (نظام تيسير). Yii2 + AdminLTE + Bootstrap + Kartik widgets. Use when building views, forms, tables, dashboards, reports, modules, or any frontend UI in the Tayseer ERP system. Covers RTL Arabic, RBAC-aware views, GridView, Select2, form patterns, CSS theming, and ISO 9241 usability compliance."
---

# Tayseer ERP Frontend — Enterprise UI Skill

Comprehensive frontend development guide for نظام تيسير (Tayseer ERP), an installment/financing management system built on Yii2 Advanced + AdminLTE 2 + Bootstrap 3.

## Companion Skills

This skill orchestrates with:
- **accessibility-compliance** — WCAG 2.2 + ISO 9241-171 patterns
- **interaction-design** — Microinteractions, motion, feedback patterns
- **ui-ux-pro-max** — Design intelligence, color palettes, typography

Reference these sub-skills for detailed guidance on their domains.

---

## Tech Stack Summary

| Layer | Technology |
|-------|------------|
| Framework | Yii2 Advanced (PHP 8.3) |
| Admin UI | AdminLTE 2 (`dmstr/yii2-adminlte-asset`) |
| CSS | Bootstrap 3, custom theme (`jadal-theme.css`) |
| Widgets | Kartik GridView, Select2, FileInput, DynamicForm |
| Date Picker | Flatpickr (Arabic locale) |
| Fonts | Cairo (primary), Noto Kufi Arabic (headings) |
| Direction | RTL (`dir="rtl"`, `lang="ar-JO"`) |
| Color Scheme | Burgundy primary (`#800020`), professional neutrals |

---

## Design Tokens (from jadal-theme.css)

Always use these CSS variables for consistency:

```css
:root {
  --clr-primary: #800020;     /* Burgundy — brand */
  --clr-primary-dark: #5c0017;
  --clr-primary-light: #a3324d;
  --clr-accent: #d4a853;      /* Gold accent */
  --clr-success: #28a745;
  --clr-danger: #dc3545;
  --clr-warning: #ffc107;
  --clr-info: #17a2b8;
  --clr-bg: #f4f6f9;          /* Page background */
  --clr-surface: #ffffff;     /* Cards/boxes */
  --clr-text: #333333;
  --clr-text-muted: #777777;
  --clr-border: #e0e0e0;
  --font-primary: 'Cairo', sans-serif;
  --font-heading: 'Noto Kufi Arabic', 'Cairo', sans-serif;
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
  --shadow-md: 0 2px 8px rgba(0,0,0,0.12);
  --radius-sm: 4px;
  --radius-md: 8px;
  --radius-lg: 12px;
}
```

---

## Module Structure

Every backend module follows this structure:

```
backend/modules/{moduleName}/
├── Module.php
├── controllers/
│   └── {ModelName}Controller.php
├── models/
│   ├── {ModelName}.php
│   └── {ModelName}Search.php
├── views/{model-name}/
│   ├── index.php          # List with GridView
│   ├── create.php         # Create wrapper
│   ├── update.php         # Update wrapper
│   ├── view.php           # Detail view
│   ├── _form.php          # Shared form
│   ├── _columns.php       # GridView columns (optional)
│   └── _search.php        # Search filters (optional)
└── assets/ (optional)
```

---

## View Patterns

### 1. Index View (List Page)

```php
<?php
use yii\helpers\Html;
use kartik\grid\GridView;
use kartik\grid\SerialColumn;
use kartik\grid\ActionColumn;
use yii\widgets\Pjax;

$this->title = 'العنوان';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-list"></i> <?= $this->title ?></h3>
        <div class="box-tools">
            <?= Html::a('<i class="fa fa-plus"></i> إضافة جديد', ['create'], [
                'class' => 'btn btn-success btn-sm',
            ]) ?>
        </div>
    </div>
    <div class="box-body">
        <?php Pjax::begin(['id' => 'grid-pjax']); ?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'id' => 'main-grid',
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'hover' => true,
            'toggleData' => false,
            'summary' => '<span class="text-muted">عرض {begin}-{end} من {totalCount}</span>',
            'pager' => [
                'firstPageLabel' => 'الأولى',
                'lastPageLabel' => 'الأخيرة',
                'prevPageLabel' => 'السابق',
                'nextPageLabel' => 'التالي',
                'maxButtonCount' => 5,
            ],
            'columns' => [
                ['class' => SerialColumn::class, 'header' => '#'],
                // ... data columns ...
                [
                    'class' => ActionColumn::class,
                    'header' => 'الإجراءات',
                    'template' => '{view} {update} {delete}',
                    'contentOptions' => ['class' => 'text-center', 'style' => 'white-space:nowrap;'],
                ],
            ],
        ]) ?>
        <?php Pjax::end(); ?>
    </div>
</div>
```

**Rules:**
- Always wrap GridView in `Pjax::begin/end` for AJAX pagination
- Always provide Arabic pager labels
- Always use `summary` with Arabic text
- Use `box box-primary` wrapper with header and tools
- Include a page icon in box-title using Font Awesome
- Action buttons in `box-tools` section
- **CRITICAL:** Any `role="modal-remote"` link inside `Pjax::begin/end` or GridView with `'pjax' => true` MUST have `'data-pjax' => 0` — otherwise PJAX intercepts the click and the modal never opens
- Modal templates MUST include `<div class="modal-footer"></div>` — without it, controller footer buttons are silently lost

### 2. Form View (_form.php)

```php
<?php
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

$form = ActiveForm::begin([
    'id' => 'main-form',
    'options' => ['class' => 'jadal-form'],
    'fieldConfig' => [
        'template' => "{label}\n{input}\n{hint}\n{error}",
        'labelOptions' => ['class' => 'control-label'],
    ],
]);
?>

<div class="box box-primary">
    <div class="box-body">
        <!-- Section: Personal Information -->
        <fieldset class="jadal-fieldset">
            <legend><i class="fa fa-user"></i> المعلومات الشخصية</legend>
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'first_name')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'الاسم الأول',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'last_name')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'اسم العائلة',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'status')->widget(Select2::class, [
                        'data' => $statusOptions,
                        'options' => ['placeholder' => 'اختر الحالة...'],
                        'pluginOptions' => [
                            'allowClear' => true,
                            'dir' => 'rtl',
                        ],
                    ]) ?>
                </div>
            </div>
        </fieldset>
    </div>
    <div class="box-footer jadal-form-actions">
        <?= Html::submitButton(
            $model->isNewRecord ? '<i class="fa fa-save"></i> حفظ' : '<i class="fa fa-check"></i> تحديث',
            ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
        ) ?>
        <?= Html::a('<i class="fa fa-times"></i> إلغاء', ['index'], ['class' => 'btn btn-default']) ?>
    </div>
</div>

<?php ActiveForm::end(); ?>
```

**Rules:**
- Group fields into `<fieldset class="jadal-fieldset">` with `<legend>`
- Use `<div class="row"><div class="col-md-4">` for layout (3 columns)
- Financial fields: `col-md-3` (4 columns)
- Long text (textarea, address): `col-md-6` or `col-md-12`
- Always use Select2 for dropdowns with `'dir' => 'rtl'`
- Always use Flatpickr for dates (not DatePicker)
- Submit button: `btn-success` for create, `btn-primary` for update
- Always provide a cancel button linking to index

### 3. Detail View (view.php)

```php
<?php
use yii\helpers\Html;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'القائمة', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <!-- Main Content -->
    <div class="col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-eye"></i> <?= Html::encode($this->title) ?></h3>
                <div class="box-tools">
                    <?= Html::a('<i class="fa fa-edit"></i> تعديل', ['update', 'id' => $model->id], [
                        'class' => 'btn btn-primary btn-sm'
                    ]) ?>
                </div>
            </div>
            <div class="box-body">
                <fieldset class="so-fieldset">
                    <legend>البيانات الأساسية</legend>
                    <div class="so-grid">
                        <div class="cv-field">
                            <span class="cv-label">الاسم</span>
                            <span class="cv-value"><?= Html::encode($model->name) ?></span>
                        </div>
                        <div class="cv-field">
                            <span class="cv-label">الحالة</span>
                            <span class="cv-value">
                                <span class="label label-<?= $model->getStatusCssClass() ?>">
                                    <?= $model->getStatusLabel() ?>
                                </span>
                            </span>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
    </div>
    <!-- Side Panel -->
    <div class="col-md-3">
        <div class="box box-default so-risk-panel">
            <div class="box-header"><h3 class="box-title">ملخص</h3></div>
            <div class="box-body">
                <!-- Stats cards, quick actions -->
            </div>
        </div>
    </div>
</div>
```

**Rules:**
- Main content: `col-md-9`, side panel: `col-md-3`
- Use `so-fieldset`, `so-grid`, `cv-field`, `cv-label`, `cv-value` classes
- Status badges use Bootstrap `label label-{type}` classes
- Actions in box-tools header
- Side panel for summary, stats, quick actions

---

## Kartik Widget Configuration Standards

### GridView Columns

```php
// Standard text column
[
    'attribute' => 'name',
    'label' => 'الاسم',
    'headerOptions' => ['class' => 'text-center'],
    'contentOptions' => ['class' => 'text-right'],
],

// Status column with badge
[
    'attribute' => 'status',
    'label' => 'الحالة',
    'format' => 'raw',
    'value' => function ($model) {
        $colors = ['active' => 'success', 'inactive' => 'danger', 'pending' => 'warning'];
        $labels = ['active' => 'فعال', 'inactive' => 'غير فعال', 'pending' => 'معلق'];
        $color = $colors[$model->status] ?? 'default';
        $label = $labels[$model->status] ?? $model->status;
        return '<span class="label label-' . $color . '">' . $label . '</span>';
    },
    'filter' => ['active' => 'فعال', 'inactive' => 'غير فعال', 'pending' => 'معلق'],
    'contentOptions' => ['class' => 'text-center'],
],

// Currency column
[
    'attribute' => 'amount',
    'label' => 'المبلغ',
    'format' => ['currency', 'JOD'],
    'headerOptions' => ['class' => 'text-center'],
    'contentOptions' => ['class' => 'text-left', 'style' => 'font-weight:600;'],
    'pageSummary' => true,
    'pageSummaryFunc' => GridView::F_SUM,
],

// Date column
[
    'attribute' => 'created_at',
    'label' => 'تاريخ الإنشاء',
    'format' => ['date', 'php:Y-m-d'],
    'headerOptions' => ['class' => 'text-center'],
    'contentOptions' => ['class' => 'text-center'],
],

// Relational column
[
    'attribute' => 'customer_id',
    'label' => 'العميل',
    'value' => 'customer.full_name',
    'filter' => Select2::widget([
        'model' => $searchModel,
        'attribute' => 'customer_id',
        'data' => $customerList,
        'options' => ['placeholder' => 'الكل'],
        'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
    ]),
],
```

### Select2 Patterns

```php
// Basic dropdown
$form->field($model, 'category_id')->widget(Select2::class, [
    'data' => ArrayHelper::map($categories, 'id', 'name'),
    'options' => ['placeholder' => 'اختر...'],
    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
])

// AJAX search dropdown
$form->field($model, 'customer_id')->widget(Select2::class, [
    'options' => ['placeholder' => 'ابحث عن عميل...'],
    'pluginOptions' => [
        'allowClear' => true,
        'dir' => 'rtl',
        'minimumInputLength' => 2,
        'ajax' => [
            'url' => Url::to(['customer/search']),
            'dataType' => 'json',
            'delay' => 300,
            'data' => new JsExpression('function(params) { return {q:params.term}; }'),
            'results' => new JsExpression('function(data) { return {results:data.items}; }'),
        ],
    ],
])

// Dependent dropdown
$form->field($model, 'city_id')->widget(Select2::class, [
    'data' => [],
    'options' => ['placeholder' => 'اختر المدينة...', 'id' => 'city-select'],
    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
])
// JS to load cities when governorate changes
```

### Flatpickr Date Picker

```php
use backend\helpers\FlatpickrWidget;

$form->field($model, 'date_field')->widget(FlatpickrWidget::class, [
    'locale' => 'ar',
    'options' => ['placeholder' => 'اختر التاريخ...'],
])
```

---

## CSS Patterns

### Box Styling

```css
.box {
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    border-top: 3px solid var(--clr-primary);
    margin-bottom: 20px;
}
.box-header .box-title {
    font-family: var(--font-heading);
    font-weight: 700;
    font-size: 16px;
}
.box-header .box-title i {
    margin-left: 8px; /* RTL: icon before text */
    color: var(--clr-primary);
}
```

### Fieldset Styling

```css
.jadal-fieldset {
    border: 1px solid var(--clr-border);
    border-radius: var(--radius-md);
    padding: 20px;
    margin-bottom: 20px;
}
.jadal-fieldset legend {
    font-family: var(--font-heading);
    font-weight: 700;
    font-size: 15px;
    color: var(--clr-primary);
    border-bottom: 2px solid var(--clr-primary);
    padding-bottom: 8px;
    margin-bottom: 16px;
    width: auto;
    padding: 0 12px;
}
```

### Stats Cards

```css
.stat-card {
    background: var(--clr-surface);
    border-radius: var(--radius-md);
    padding: 16px;
    text-align: center;
    box-shadow: var(--shadow-sm);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
.stat-card .stat-value {
    font-size: 28px;
    font-weight: 800;
    color: var(--clr-primary);
    font-family: var(--font-heading);
}
.stat-card .stat-label {
    font-size: 13px;
    color: var(--clr-text-muted);
    margin-top: 4px;
}
```

### Form Actions

```css
.jadal-form-actions {
    background: #f8f9fa;
    border-top: 1px solid var(--clr-border);
    padding: 15px 20px;
    text-align: left; /* RTL: buttons align to left */
    display: flex;
    gap: 8px;
    flex-direction: row-reverse; /* RTL: primary action first */
}
```

### Table Enhancements

```css
.kv-grid-table thead th {
    background: #f5f6f8;
    font-family: var(--font-heading);
    font-weight: 600;
    font-size: 13px;
    color: var(--clr-text);
    border-bottom: 2px solid var(--clr-primary);
    white-space: nowrap;
}
.kv-grid-table tbody tr:hover {
    background-color: rgba(128, 0, 32, 0.04);
}
.kv-grid-table tbody td {
    vertical-align: middle;
    font-size: 13px;
}
```

---

## RTL Rules (Critical)

1. **Always** set `dir="rtl"` on `<html>` and `lang="ar"`
2. **Use logical CSS properties** when possible:
   - `margin-inline-start` instead of `margin-right`
   - `padding-inline-end` instead of `padding-left`
   - `text-align: start` instead of `text-align: right`
3. **Flex direction**: Use `flex-direction: row-reverse` for button groups
4. **Icons before text**: In RTL, `<i>` before text means icon appears on the right
5. **Select2**: Always pass `'dir' => 'rtl'` in pluginOptions
6. **Flatpickr**: Always pass `'locale' => 'ar'`
7. **Grid pager**: Always use Arabic labels (الأولى، الأخيرة، السابق، التالي)
8. **Breadcrumbs**: Flow right-to-left automatically with AdminLTE RTL

---

## Naming Conventions

| Item | Convention | Example |
|------|-----------|---------|
| Module directory | camelCase | `financialTransaction` |
| Controller | PascalCase + Controller | `ContractsController` |
| Model | PascalCase | `CustomerInformation` |
| View files | kebab-case | `customer-info/index.php` |
| CSS classes | kebab-case with prefix | `jadal-fieldset`, `so-grid`, `cv-field` |
| JS functions | camelCase | `handleStatusChange()` |
| DB tables | snake_case with prefix | `os_customers` |

---

## ISO 9241 Compliance Checklist

Apply these when building any view:

### ISO 9241-110: Interaction Principles

- [ ] **Suitability for task** — Only show relevant fields/actions for the task
- [ ] **Self-descriptiveness** — Labels, placeholders, and hints are clear in Arabic
- [ ] **Conformity with expectations** — Consistent layout across all modules
- [ ] **Learnability** — Use familiar patterns (box > fieldset > fields)
- [ ] **Controllability** — User can cancel, undo, or go back
- [ ] **Error tolerance** — Validate client-side, show clear Arabic error messages
- [ ] **Individualization** — Respect RBAC, show only permitted actions

### ISO 9241-125: Visual Presentation

- [ ] **Text readability** — Cairo font, minimum 13px body, 15px+ headings
- [ ] **Color contrast** — 4.5:1 ratio for text (use `--clr-text` on `--clr-bg`)
- [ ] **Information density** — Balance data richness with whitespace
- [ ] **Visual hierarchy** — Headings > Labels > Values > Muted text
- [ ] **Consistent spacing** — 8px base unit (8, 16, 20, 24, 32)

### ISO 9241-143: Forms

- [ ] **Logical grouping** — Related fields in same fieldset
- [ ] **Tab order** — Natural RTL flow, top to bottom
- [ ] **Required indicators** — Red asterisk on required labels
- [ ] **Input constraints** — maxlength, min/max for numbers
- [ ] **Contextual help** — Hint text below complex fields
- [ ] **Error proximity** — Error message directly below the field

---

## Dashboard Components

### KPI Stats Row

```php
<div class="row">
    <?php foreach ($stats as $stat): ?>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="color: <?= $stat['color'] ?>">
                <i class="fa fa-<?= $stat['icon'] ?> fa-2x"></i>
            </div>
            <div class="stat-value"><?= number_format($stat['value']) ?></div>
            <div class="stat-label"><?= $stat['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
```

### Action Buttons Standard

```php
// Primary action (create/add)
Html::a('<i class="fa fa-plus"></i> إضافة', ['create'], ['class' => 'btn btn-success btn-sm'])

// Edit action
Html::a('<i class="fa fa-edit"></i> تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary btn-sm'])

// Delete action (always with confirmation)
Html::a('<i class="fa fa-trash"></i> حذف', ['delete', 'id' => $id], [
    'class' => 'btn btn-danger btn-sm',
    'data' => ['confirm' => 'هل أنت متأكد من الحذف؟', 'method' => 'post'],
])

// Print action
Html::a('<i class="fa fa-print"></i> طباعة', ['print', 'id' => $id], [
    'class' => 'btn btn-default btn-sm',
    'target' => '_blank',
])

// Export action
Html::a('<i class="fa fa-file-excel-o"></i> تصدير', ['export'], ['class' => 'btn btn-info btn-sm'])
```

---

## Financial Data Display

For contracts, payments, and financial reports:

```php
// Currency formatting (always JOD)
Yii::$app->formatter->asCurrency($amount, 'JOD')

// Or manual:
number_format($amount, 3) . ' د.أ'

// Credit/Debit colors
'credit' => 'color: var(--clr-success); font-weight: 600;'
'debit'  => 'color: var(--clr-danger); font-weight: 600;'

// Financial summary row
'pageSummary' => true,
'pageSummaryFunc' => GridView::F_SUM,
'pageSummaryOptions' => ['class' => 'text-left', 'style' => 'font-weight:800;'],
```

---

## Print-Friendly Views

For reports and documents that need printing:

```php
// Register print CSS
$this->registerCss('@media print {
    .no-print, .main-header, .main-sidebar, .main-footer,
    .box-tools, .breadcrumb, .btn { display: none !important; }
    .content-wrapper { margin: 0 !important; padding: 0 !important; }
    .box { border: none !important; box-shadow: none !important; }
    body { font-size: 12pt; direction: rtl; }
    table { page-break-inside: auto; }
    tr { page-break-inside: avoid; }
}');
```

---

## Pre-Delivery Checklist

Before delivering any frontend view, verify:

### Structure
- [ ] Page title set (`$this->title`)
- [ ] Breadcrumbs configured
- [ ] Wrapped in `box box-primary`
- [ ] Box has header with icon and title
- [ ] Actions in box-tools

### Forms
- [ ] Fields grouped in fieldsets with legend
- [ ] Select2 with `dir => rtl` for all dropdowns
- [ ] Flatpickr with `locale => ar` for dates
- [ ] Required fields marked
- [ ] Submit + Cancel buttons in `jadal-form-actions`
- [ ] Client-side validation enabled

### Tables
- [ ] Wrapped in Pjax
- [ ] Arabic summary text
- [ ] Arabic pager labels
- [ ] Status columns use colored badges
- [ ] Currency columns formatted with JOD
- [ ] Action column is last, centered, nowrap
- [ ] Filters configured for searchable columns

### Accessibility (ISO 9241-171)
- [ ] All form inputs have labels
- [ ] Images have alt text in Arabic
- [ ] Buttons have descriptive text (not just icons)
- [ ] Color is not the only indicator (use icons + text)
- [ ] Tab order follows RTL reading direction

### RTL
- [ ] No hardcoded left/right margins or paddings
- [ ] Flex containers use proper RTL direction
- [ ] Text alignment uses `start`/`end` not `left`/`right`
- [ ] Icons appear in correct position for RTL

### Performance
- [ ] GridView uses Pjax (no full page reloads)
- [ ] Select2 uses AJAX for large datasets (>50 items)
- [ ] Images are optimized
- [ ] No inline styles (use CSS classes)
