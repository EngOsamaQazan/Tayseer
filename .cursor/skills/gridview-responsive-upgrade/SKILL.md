---
name: gridview-responsive-upgrade
description: "Upgrade any Kartik GridView page to responsive mobile cards + Bootstrap 5 modals. Removes CrudAsset/Bootstrap 3, adds shared CSS/JS, adds data-label to columns. Use when making a GridView page responsive, fixing Bootstrap 3 conflicts, or converting CrudAsset pages to Bootstrap 5."
---

# GridView Responsive Upgrade Skill

Systematic, repeatable pattern for converting any CrudAsset/Kartik GridView page in Tayseer ERP from Bootstrap 3 scrolling tables to Bootstrap 5 responsive card layouts on mobile.

## When to Use

- Making any GridView index page responsive
- Fixing Bootstrap 3 / Bootstrap 5 conflicts
- Removing CrudAsset dependency from a page
- Converting `yii\bootstrap\Modal` to Bootstrap 5

## Shared Assets (Already Created)

| File | Purpose |
|------|---------|
| `backend/web/css/tayseer-gridview-responsive.css` | Shared CSS for panel styling, table styling, mobile card layout, pagination, modals |
| `backend/web/js/tayseer-gridview-modal.js` | Shared JS handling for `role="modal-remote"` Bootstrap 5 modals, delete confirmations, form submissions |

## Step-by-Step Conversion

### Step 1: Clean index.php — Remove Bootstrap 3

Remove these `use` statements:

```php
// REMOVE these:
use yii\bootstrap\Modal;
use johnitvn\ajaxcrud\CrudAsset;
```

Remove `CrudAsset::register($this);`

### Step 2: Register Shared Assets

Add at the top of the PHP file, after title/breadcrumbs:

```php
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/tayseer-gridview-responsive.css?v=1');
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/tayseer-gridview-modal.js?v=1', [
    'depends' => [\yii\web\JqueryAsset::class],
]);
```

### Step 3: Replace Modal::begin/end with Bootstrap 5 HTML

Replace:

```php
<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '']) ?>
<?php Modal::end(); ?>
```

With:

```html
<div class="modal fade" id="ajaxCrudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div style="text-align:center;padding:40px">
                    <i class="fa fa-spinner fa-spin" style="font-size:24px;color:var(--ty-clr-primary,#800020)"></i>
                </div>
            </div>
        </div>
    </div>
</div>
```

### Step 4: Add data-label to _columns.php

For every column in the `_columns.php` file, add `contentOptions`:

```php
[
    'class' => '\kartik\grid\DataColumn',
    'attribute' => 'name',
    'contentOptions' => ['data-label' => 'الاسم'],  // Arabic label
],
```

For ActionColumn:

```php
[
    'class' => 'kartik\grid\ActionColumn',
    'contentOptions' => ['data-label' => ''],  // Empty label for actions
    // ... rest of config
],
```

For SerialColumn:

```php
[
    'class' => 'kartik\grid\SerialColumn',
    'contentOptions' => ['data-label' => '#'],
],
```

### Step 5: Replace glyphicon icons with FontAwesome

```
glyphicon-plus    → fa fa-plus
glyphicon-repeat  → fa fa-refresh
glyphicon-pencil  → fa fa-pencil
glyphicon-trash   → fa fa-trash
glyphicon-eye     → fa fa-eye
```

### Step 6: Update delete confirmations (if using data-request-method)

The shared `tayseer-gridview-modal.js` automatically handles elements with:
- `data-request-method="post"` and `data-confirm-message="..."` — shows Bootstrap 5 confirmation dialog
- `role="modal-remote"` — loads content via AJAX into the modal

### Step 7: Update jQuery .modal() calls to Bootstrap 5

Replace:

```javascript
$('#myModal').modal('show');
```

With:

```javascript
var el = document.getElementById('myModal');
if (el && typeof bootstrap !== 'undefined') bootstrap.Modal.getOrCreateInstance(el).show();
```

Replace `data-dismiss="modal"` with `data-bs-dismiss="modal"` in HTML.

## How the Mobile Card Layout Works

The CSS in `tayseer-gridview-responsive.css` does this at `≤767px`:

1. **Hides `<thead>`** — column headers become invisible
2. **Each `<tr>` becomes a card** — `display: block` with border, border-radius, shadow, margin
3. **Each `<td>` becomes a flex row** — `display: flex; justify-content: space-between`
4. **`::before` pseudo-element** reads `data-label` attribute — shows the column name on the left
5. **Last `<td>` (actions)** gets a top border separator
6. **Filters row** is hidden on mobile

## CSS Variables (Customizable)

```css
--ty-clr-primary: #800020;
--ty-clr-primary-dark: #650019;
--ty-clr-surface: #fff;
--ty-clr-bg: #FAFBFC;
--ty-clr-border: #E2E8F0;
--ty-clr-text: #1E293B;
--ty-clr-text-muted: #64748B;
--ty-radius-sm: 6px;
--ty-radius-md: 10px;
```

## Checklist — Quick Reference

- [ ] Remove `use yii\bootstrap\Modal;`
- [ ] Remove `use johnitvn\ajaxcrud\CrudAsset;` and `CrudAsset::register($this);`
- [ ] Register `tayseer-gridview-responsive.css`
- [ ] Register `tayseer-gridview-modal.js`
- [ ] Replace `Modal::begin/end` with Bootstrap 5 HTML
- [ ] Modal HTML includes `<div class="modal-footer"></div>` (not just header + body)
- [ ] Modal dialog has `modal-dialog-centered modal-dialog-scrollable` classes
- [ ] Add `data-label` to every column in `_columns.php`
- [ ] Replace `glyphicon` icons with `fa` icons
- [ ] Replace `data-dismiss` with `data-bs-dismiss`
- [ ] Replace jQuery `.modal('show')` with Bootstrap 5 API
- [ ] Remove any inline `<style>` blocks that duplicate shared CSS patterns
- [ ] **All `role="modal-remote"` links inside PJAX containers have `data-pjax="0"`**
- [ ] **Modal JS handler parses JSON `{title, content, footer}` — NOT raw HTML**
- [ ] **Custom JS files implement full modal lifecycle (load/submit/forceClose/refresh)**
- [ ] Test: desktop table view
- [ ] Test: mobile card layout
- [ ] Test: modal (view/create/update/delete) — verify modal actually opens and shows content
- [ ] Test: multi-step modals (e.g. select → form) if applicable
- [ ] Test: pagination

## Critical Pitfalls (MUST READ Before Rebuilding Any Screen)

### Pitfall 1: Modal Handler Must Parse JSON — NOT Raw HTML

Controllers return JSON `{title, content, footer, forceClose, size}` for AJAX requests. The JS modal handler **MUST** use `dataType: 'json'` and render each field into the correct modal section:

```javascript
// ❌ WRONG — treats response as HTML, modal shows nothing or [object Object]
$.get(href, function (html) {
    $modal.find('.modal-body').html(html);
});

// ✅ CORRECT — parses JSON response structure
$.ajax({
    url: href, type: 'GET', dataType: 'json',
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
}).done(function (data) {
    if (data.forceClose) { hideModal(); return; }
    if (data.title)   $modal.find('.modal-title').html(data.title);
    if (data.content) $modal.find('.modal-body').html(data.content);
    if (data.footer)  $modal.find('.modal-footer').html(data.footer);
    if (data.size)    setModalSize(data.size);
});
```

### Pitfall 2: PJAX Intercepts `role="modal-remote"` Clicks

When `role="modal-remote"` links are inside a `Pjax::begin/end` container OR a GridView with `'pjax' => true`, **PJAX intercepts the click** before the modal handler fires. The modal never opens.

**Fix:** Add `'data-pjax' => 0` to every `role="modal-remote"` link inside PJAX containers:

```php
// ❌ WRONG — PJAX intercepts this click, modal never opens
Html::a('إضافة إجراء', ['create'], [
    'class' => 'btn btn-success',
    'role' => 'modal-remote',
])

// ✅ CORRECT — data-pjax=0 tells PJAX to ignore this link
Html::a('إضافة إجراء', ['create'], [
    'class' => 'btn btn-success',
    'role' => 'modal-remote',
    'data-pjax' => 0,
])
```

**Check ALL places** where `role="modal-remote"` appears inside PJAX:
- Toolbar buttons in GridView (`'toolbar' => [...]`)
- Action menu links in `_columns.php`
- Inline row action buttons

### Pitfall 3: Modal HTML Must Include `modal-footer`

The modal template **MUST** have a `<div class="modal-footer"></div>` element. Without it, controller-provided footer buttons (إغلاق, إضافة, حفظ) are silently lost.

```html
<!-- ✅ Complete modal template -->
<div class="modal fade" id="ajaxCrudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer"></div>  <!-- ← REQUIRED -->
        </div>
    </div>
</div>
```

### Pitfall 4: Multi-Step Modal Flows Need Form Submission + Nested modal-remote

Some create flows have 2 steps (e.g. select judiciary → fill form). The modal handler must support:

1. **Nested `role="modal-remote"` clicks inside the modal body** — links loaded via AJAX that open a new form in the same modal
2. **Form submission via AJAX** — intercept `form submit` and `[type="submit"]` click inside the modal, POST as JSON, render response
3. **File uploads** — detect `input[type="file"]` and use `FormData` with `processData: false, contentType: false`
4. **`forceClose` handling** — when save succeeds, close modal and refresh the active tab/grid

### Pitfall 5: Custom JS Files Need Full Modal Handler

When a page uses its own JS file (like `judiciary-v2.js`) instead of the shared `tayseer-gridview-modal.js`, that JS file must implement the **complete** modal handler including:

- `loadRemoteModal(href)` — AJAX GET with JSON parsing
- `submitModalForm($form)` — AJAX POST with file upload support
- `renderModalResponse(data)` — handle title/content/footer/forceClose/size
- `refreshActiveTab()` — reload the current tab/grid after CRUD operations
- Event delegation for `[role="modal-remote"]`, `[type="submit"]`, `form submit`
- Delete confirmation modal for `[data-request-method="post"]`

Reference implementation: `backend/modules/judiciaryCustomersActions/views/judiciary-customers-actions/index.php` (inline JS block).

---

## Pages Already Converted

- `contracts/index.php` — Custom table (not GridView), already had external CSS/JS, only Bootstrap 3 Modal replaced
- `lawyers/index.php` — Standard CrudAsset pattern, fully converted
- `court/index.php` — Standard CrudAsset pattern, fully converted
- `jobs/index.php` — Custom fin-page layout + CrudAsset, fully converted
- `judiciary/index.php` — Full rewrite with dedicated `judiciary-v2.css` / `judiciary-v2.js`
- `customers/index.php` — Full rewrite with dedicated `customers-v2.css` / `customers-v2.js`

## Pages Remaining (use this skill to convert them)

Any page that still has `use yii\bootstrap\Modal;` or `CrudAsset::register($this);` needs this conversion.

To find them:

```bash
grep -rl "yii\\bootstrap\\Modal" backend/modules/*/views/*/index.php
grep -rl "CrudAsset::register" backend/modules/*/views/*/index.php
```
