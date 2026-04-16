# Tayseer ERP — Component Library Reference

> مكتبة المكونات الموحدة — مرجع لجميع مكونات واجهة المستخدم

---

## 1. Buttons (الأزرار)

### Primary Action
```html
<a href="#" class="btn btn-primary">
    <i class="fa fa-plus"></i> إضافة
</a>
```

### Secondary / Outline
```html
<button class="btn btn-outline-secondary">
    <i class="fa fa-refresh"></i> تحديث
</button>
```

### Danger
```html
<button class="btn btn-danger" data-confirm-msg="هل أنت متأكد؟">
    <i class="fa fa-trash"></i> حذف
</button>
```

### Button Sizes
- `.btn-lg` — كبير (forms الرئيسية)
- `.btn` — عادي (أغلب الحالات)
- `.btn-sm` — صغير (toolbars)

### Custom Buttons (ct-* pattern — pages like Customers)
```html
<a href="#" class="ct-btn ct-btn-primary">
    <i class="fa fa-plus"></i> <span class="ct-hide-xs">إضافة</span>
</a>
<button class="ct-btn ct-btn-outline">تصدير</button>
<button class="ct-btn ct-btn-ghost">فلتر</button>
```

---

## 2. Cards (البطاقات)

### Standard Card
```html
<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">العنوان</h5></div>
    <div class="card-body">المحتوى</div>
</div>
```

### Dashboard Card
```html
<div class="db-card">
    <div class="db-card-header">
        <h3><i class="fa fa-chart-bar"></i> العنوان</h3>
        <span class="db-badge">123</span>
    </div>
    <div class="db-card-body">...</div>
</div>
```

### KPI Card (Dashboard)
```html
<a href="/contracts" class="db-kpi" style="--kpi-color:#800020">
    <div class="db-kpi-icon"><i class="fa fa-file-text"></i></div>
    <div class="db-kpi-body">
        <div class="db-kpi-label">إجمالي العقود</div>
        <div class="db-kpi-value">1,234</div>
        <div class="db-kpi-sub">قيمة: 500,000 د.أ</div>
    </div>
</a>
```

### Legacy Card (AdminLTE shim → Bootstrap 5)
```html
<!-- Auto-mapped via CSS shim in tayseer-vuexy.css -->
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">العنوان</h3>
    </div>
    <div class="box-body">المحتوى</div>
</div>
```

---

## 3. Tables (الجداول)

### Kartik GridView (standard)
```php
<?= GridView::widget([
    'id' => 'crud-datatable',
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,  // enables filter row
    'columns' => require(__DIR__ . '/_columns.php'),
    'toolbar' => [
        ['content' =>
            Html::a('<i class="fa fa-plus"></i>', ['create'],
                ['class' => 'btn btn-secondary', 'role' => 'modal-remote'])
            . Html::a('<i class="fa fa-refresh"></i>', [''],
                ['data-pjax' => 1, 'class' => 'btn btn-secondary'])
            . '{toggleData}'
        ],
    ],
    'panel' => ['type' => 'default'],
]) ?>
```

### Custom Table (ct-* pattern)
```html
<table class="ct-table" role="grid">
    <thead><tr><th>العنوان</th></tr></thead>
    <tbody><tr><td data-label="العنوان">القيمة</td></tr></tbody>
</table>
```

### Auto-features (injected by tayseer-responsive.js)
- **data-label**: تُضاف تلقائياً لجميع `<td>` من عناوين الأعمدة
- **Quick Search**: شريط بحث يُحقن فوق كل GridView
- **Column Toggle**: زر إظهار/إخفاء الأعمدة

---

## 4. Forms (النماذج)

### Standard Yii2 Field
```php
<?= $form->field($model, 'name')->textInput([
    'placeholder' => 'الاسم الكامل',
    'class' => 'form-control',
]) ?>
```

### Select2
```php
<?= $form->field($model, 'status')->widget(\kartik\select2\Select2::class, [
    'data' => $statusList,
    'options' => ['placeholder' => 'اختر الحالة'],
    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
]) ?>
```

### Date Picker (Flatpickr)
```php
<?= $form->field($model, 'date')->textInput([
    'class' => 'form-control flatpickr',
    'data-flatpickr' => json_encode(['dateFormat' => 'Y-m-d']),
]) ?>
```

### Wizard Form (Smart Onboarding)
```html
<div class="so-stepper">
    <div class="so-step active" data-step="0" aria-current="step">
        <span class="so-step-num"><i class="fa fa-user so-step-icon"></i></span>
        <span class="so-step-label">البيانات الشخصية</span>
    </div>
</div>
<div class="so-section active" data-step="0">
    <div class="so-fieldset">
        <h3 class="so-fieldset-title"><i class="fa fa-user"></i> عنوان القسم</h3>
        <div class="so-grid so-grid-3">...</div>
    </div>
</div>
```

---

## 5. Modals (النوافذ المنبثقة)

### Global Modal (gModal)
```html
<div class="modal fade" id="ajaxCrudModal" tabindex="-1"
     aria-hidden="true" aria-labelledby="ajaxCrudModalLabel"
     aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ajaxCrudModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">...</div>
            <div class="modal-footer"></div>
        </div>
    </div>
</div>
```

### Trigger
```html
<a href="/module/action" role="modal-remote" data-pjax="0">Open</a>
```

---

## 6. Notifications (الإشعارات)

### Toast (TyToast API)
```javascript
TyToast({ type: 'success', message: 'تم الحفظ بنجاح' });
TyToast({ type: 'error', title: 'خطأ', message: 'فشل في الاتصال', duration: 8000 });
TyToast({ type: 'warning', message: 'تحذير مهم' });
TyToast({ type: 'info', message: 'معلومة' });
```

Types: `success` | `error` | `warning` | `info`

---

## 7. Status Badges (شارات الحالة)

```html
<span class="badge bg-success">نشط</span>
<span class="badge bg-danger">قضائي</span>
<span class="badge bg-warning text-dark">معلّق</span>

<!-- Dashboard pill -->
<span class="db-status-pill" style="background:#28a745">نشط</span>

<!-- Customer page badge -->
<span class="ct-badge ct-st-active">لا</span>
<span class="ct-badge ct-st-judiciary">نعم</span>
```

---

## 8. Navigation

### Sidebar (Vuexy)
```html
<aside class="layout-menu" role="navigation" aria-label="القائمة الرئيسية">
    <ul class="menu-inner">
        <li class="menu-item active">
            <a href="/" class="menu-link">
                <i class="menu-icon fa fa-home"></i>
                <div>الرئيسية</div>
            </a>
        </li>
        <li class="menu-item">
            <a href="#" class="menu-link menu-toggle"
               aria-expanded="false" aria-haspopup="true">
                <i class="menu-icon fa fa-file-text"></i>
                <div>العقود</div>
            </a>
            <ul class="menu-sub">...</ul>
        </li>
    </ul>
</aside>
```

### Tabs (ARIA-compliant)
```html
<div class="ocp-tabs" role="tablist" aria-label="تبويبات العقد">
    <button role="tab" aria-selected="true" aria-controls="tab-panel-1"
            id="tab-btn-1" class="ocp-tab active">
        تبويب 1
    </button>
</div>
<div role="tabpanel" id="tab-panel-1" aria-labelledby="tab-btn-1">
    محتوى التبويب
</div>
```

---

## 9. Loading States

### Skeleton Loading
```html
<div class="ty-skeleton" style="width:60%;height:16px"></div>
<div class="ty-skeleton" style="width:80%;height:12px;margin-top:8px"></div>
```

### Pjax Loading (auto via JS)
Applied automatically: `.ty-pjax-loading` class added to container during PJAX.

---

## 10. Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Alt + H` | الرئيسية |
| `Alt + C` | العملاء |
| `Alt + Q` | العقود |
| `Alt + F` | المتابعة |
| `Alt + S` | البحث السريع |
| `Alt + /` | عرض الاختصارات |
| `Escape` | إغلاق النافذة |

---

## ARIA & Accessibility Patterns

| Pattern | Implementation |
|---------|---------------|
| Skip Link | `<a href="#main-content" class="ty-skip-link">` |
| Main Landmark | `<main id="main-content" role="main">` |
| Navigation | `<aside role="navigation" aria-label="القائمة الرئيسية">` |
| Modal | `aria-modal="true" role="dialog" aria-labelledby="..."` |
| Tabs | `role="tablist"` + `role="tab"` + `role="tabpanel"` |
| Focus Visible | `:focus-visible { outline: 3px solid var(--bs-primary) }` |
| Target Size | All interactive elements ≥ 44×44px |

---

**آخر تحديث:** 2026-04-16
