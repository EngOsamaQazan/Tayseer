# خطة التحويل الشاملة — نظام تيسير ERP

## الوضع الحالي

| البند | القيمة |
|-------|--------|
| PHP | ^8.3 → يجب تحديثه إلى ^8.5 |
| Bootstrap | مختلط: BS3 + BS4 + BS5 (BS5 هو الهدف) |
| Layout | Vuexy (الجديد) — AdminLTE 3 مُعطّل |
| Modals | BS3 `yii\bootstrap\Modal` → BS5 HTML |
| CRUD | `johnitvn/ajaxcrud` → `tayseer-gridview-modal.js` |
| Icons | Glyphicon (BS3) → FontAwesome |
| محوّل | 6 شاشات (~8%) |
| متبقي | ~67 شاشة index + ~15 شاشة form/view |

---

## المراحل

```
المرحلة 0: البنية التحتية + Composer (يوم واحد)
المرحلة 1: الشاشات البسيطة — Lookup Tables (3-4 أيام)
المرحلة 2: الشاشات المتوسطة — Core Business (5-6 أيام)
المرحلة 3: الشاشات المعقدة — FollowUp + Inventory + HR (5-7 أيام)
المرحلة 4: المحاسبة + التقارير + Dektrium (3-4 أيام)
المرحلة 5: تنظيف نهائي + PHP 8.5.4 (2-3 أيام)
```

---

## المرحلة 0: البنية التحتية + Composer

### 0.1 تحديث `composer.json`

```json
{
  "require": {
    "php": "^8.5",
    // حذف الحزم القديمة:
    // ❌ "yiisoft/yii2-bootstrap": "~2.0.0"
    // ❌ "yiisoft/yii2-bootstrap4": "~2.0"
    // ❌ "johnitvn/yii2-ajaxcrud": "~2.1"
    // ❌ "dektrium/yii2-user": "^0.9.14"  ← إبقاؤها مؤقتاً حتى المرحلة 4
  }
}
```

**الإجراءات:**
- [ ] تغيير `"php": "^8.3"` → `"php": "^8.5"`
- [ ] إزالة `"yiisoft/yii2-bootstrap": "~2.0.0"` (BS3)
- [ ] إزالة `"yiisoft/yii2-bootstrap4": "~2.0"` (BS4)
- [ ] إزالة `"johnitvn/yii2-ajaxcrud": "~2.1"` بعد إتمام تحويل جميع الشاشات
- [ ] تشغيل `composer update`
- [ ] التأكد من عدم وجود أخطاء

### 0.2 تنظيف Asset Bundles في `config/main.php`

بعد إزالة الحزم القديمة من composer، تنظيف suppressions من `assetManager.bundles`:

```php
// إزالة هذه بعد حذف الحزم:
'yii\bootstrap\BootstrapAsset' => ['css' => []],        // ← حذف
'yii\bootstrap\BootstrapPluginAsset' => ['js' => []],    // ← حذف
'yii\bootstrap4\BootstrapAsset' => ['css' => []],        // ← حذف
'yii\bootstrap4\BootstrapPluginAsset' => ['js' => []],   // ← حذف
'johnitvn\ajaxcrud\CrudAsset' => [...]                   // ← حذف
```

### 0.3 حذف `OldAppAsset.php`

الملف `backend/assets/OldAppAsset.php` معطّل بالكامل (كل شيء commented out) — يجب حذفه.

### 0.4 حذف مجلد `old_layout`

المجلد `backend/web/old_layout/` يحتوي 154 ملف من الـ layout القديم — حذف كامل.

---

## المرحلة 1: الشاشات البسيطة — Lookup Tables

> هذه شاشات إعدادات بسيطة: GridView + CRUD بدون منطق معقد.
> تتبع نفس النمط بالضبط → يمكن تحويلها بسرعة (batch).

### الإجراء لكل شاشة:
1. إزالة `use yii\bootstrap\Modal;`
2. إزالة `use johnitvn\ajaxcrud\CrudAsset;` و `CrudAsset::register($this);`
3. تسجيل CSS/JS المشتركة
4. استبدال `Modal::begin/end` بـ BS5 HTML Modal
5. إضافة `data-label` لكل عمود في `_columns.php`
6. استبدال `glyphicon` بـ `fa`
7. استبدال `data-dismiss` بـ `data-bs-dismiss`
8. فحص واختبار

### 1.1 — إعدادات المواقع والعناوين (5 شاشات)

| # | الموديول | الملف | _columns |
|---|----------|-------|----------|
| 1 | city | `index.php` | `_columns.php` |
| 2 | location | `index.php` | `_columns.php` |
| 3 | address | `index.php` | `_columns.php` |
| 4 | citizen | `index.php` | `_columns.php` |
| 5 | bancks | `index.php` | `_columns.php` |

### 1.2 — إعدادات التصنيفات (8 شاشات)

| # | الموديول | الملف |
|---|----------|-------|
| 6 | status | `index.php` |
| 7 | paymentType | `index.php` |
| 8 | contactType | `index.php` |
| 9 | connectionResponse | `index.php` |
| 10 | hearAboutUs | `index.php` |
| 11 | feelings | `index.php` |
| 12 | cousins | `index.php` |
| 13 | rejesterFollowUpType | `index.php` |

### 1.3 — إعدادات المستندات (3 شاشات)

| # | الموديول | الملف |
|---|----------|-------|
| 14 | documentType | `index.php` |
| 15 | documentStatus | `index.php` |
| 16 | contractDocumentFile | `index.php` |

### 1.4 — إعدادات أخرى (6 شاشات)

| # | الموديول | الملف |
|---|----------|-------|
| 17 | workdays | `index.php` |
| 18 | holidays | `index.php` |
| 19 | department | `index.php` |
| 20 | companyBanks | `index.php` |
| 21 | judiciaryType | `index.php` |
| 22 | sms | `index.php` |

### 1.5 — شاشات مالية بسيطة (3 شاشات)

| # | الموديول | الملف |
|---|----------|-------|
| 23 | expenseCategories | `index.php` |
| 24 | incomeCategory | `index.php` |
| 25 | realEstate | `index.php` |

### 1.6 — HR بسيطة + أخرى (5 شاشات)

| # | الموديول | الملف |
|---|----------|-------|
| 26 | leaveTypes | `index.php` |
| 27 | leavePolicy | `index.php` |
| 28 | attendance | `index.php` |
| 29 | authAssignment | `index.php` |
| 30 | employee | `index.php` |

**المجموع: 30 شاشة بسيطة**

---

## المرحلة 2: الشاشات المتوسطة — Core Business

> شاشات بها GridView + CRUD + بعض المنطق الإضافي (فلترة، أزرار خاصة)

### 2.1 — المالية (6 شاشات)

| # | الموديول | الملف | ملاحظات |
|---|----------|-------|---------|
| 31 | financialTransaction | `index.php` | يحتوي `data-toggle` + `.modal()` |
| 32 | financialTransaction | `import_grid_view.php` | شاشة استيراد |
| 33 | invoice | `index.php` | + `_form.php` يحتوي glyphicon |
| 34 | income | `index.php` | + `_form.php` يحتوي glyphicon |
| 35 | collection | `index.php` | + `_form.php` glyphicon + data-toggle |
| 36 | divisionsCollection | `index.php` | — |

### 2.2 — القضايا والإجراءات (4 شاشات)

| # | الموديول | الملف | ملاحظات |
|---|----------|-------|---------|
| 37 | judiciaryActions | `index.php` | 4 مرات `btn-default` |
| 38 | movment | `index.php` | — |
| 39 | phoneNumbers | `index.php` | — |
| 40 | notification | `_all-user-msg.php` | — |

### 2.3 — الموارد البشرية (4 شاشات)

| # | الموديول | الملف | ملاحظات |
|---|----------|-------|---------|
| 41 | hr | `hr-employee/index.php` | `data-toggle` (2) |
| 42 | hr | `hr-attendance/index.php` | — |
| 43 | leaveRequest | `index.php` | + `suspended_vacations.php` |
| 44 | leaveRequest | `suspended_vacations.php` | — |

### 2.4 — المستودعات (8 شاشات)

| # | الموديول | الملف | ملاحظات |
|---|----------|-------|---------|
| 45 | inventoryItems | `index.php` | — |
| 46 | inventoryItems | `items.php` | — |
| 47 | inventoryItems | `serial-numbers.php` | — |
| 48 | inventoryItems | `index_item_query.php` | — |
| 49 | inventoryItems | `dashboard.php` | — |
| 50 | inventoryStockLocations | `index.php` | — |
| 51 | itemsInventoryInvoices | `index.php` | — |
| 52 | inventorySuppliers | `index.php` | — |

### 2.5 — أخرى (5 شاشات)

| # | الموديول | الملف |
|---|----------|-------|
| 53 | inventoryItemQuantities | `index.php` |
| 54 | inventoryInvoices | `index.php` |
| 55 | items | `index.php` |
| 56 | documentHolder | `index.php` |
| 57 | documentHolder | `manager_index.php` |

**المجموع: 27 شاشة متوسطة**

---

## المرحلة 3: الشاشات المعقدة — FollowUp + Inventory Forms + HR Views

> شاشات بها Modals + JS مخصص + تبويبات + منطق معقد

### 3.1 — FollowUp Module (7 ملفات)

| # | الملف | المشاكل |
|---|-------|---------|
| 58 | `followUp/_form.php` | BS3 Modal + CrudAsset |
| 59 | `followUp/panel.php` | BS3 Modal + CrudAsset + `data-toggle` + `.modal()` |
| 60 | `followUp/view.php` | CrudAsset |
| 61 | `followUp/index.php` | BS3 Modal + CrudAsset |
| 62 | `followUp/modals.php` | **`data-dismiss` × 11!** + `well` + `label label-` |
| 63 | `followUp/partial/follow-up-view.php` | BS3 Modal + CrudAsset |
| 64 | `followUp/partial/tabs/financial.php` | BS3 Modal |

**إضافات FollowUp:**
- `partial/tabs/phone_numbers.php` → `data-toggle` × 3
- `partial/tabs/loan_scheduling.php` → `data-toggle`
- `partial/phone_numbers_follow_up.php` → glyphicon × 2
- `partial/tabs.php` → `data-toggle` × 6
- `partial/tabs/judiciary_customers_actions.php` → glyphicon

### 3.2 — Inventory Forms (3 ملفات)

| # | الملف | المشاكل |
|---|-------|---------|
| 65 | `inventoryInvoices/create-wizard.php` | BS3 Modal + CrudAsset + `data-dismiss` |
| 66 | `inventoryInvoices/view.php` | `data-dismiss` |
| 67 | `inventoryItems/settings.php` | `data-dismiss` × 2 + `data-toggle` × 2 + `.modal()` |

### 3.3 — HR Views (5 ملفات)

| # | الملف | المشاكل |
|---|-------|---------|
| 68 | `hr/hr-employee/view.php` | **`data-toggle` × 7!** + `label label-` |
| 69 | `hr/hr-loan/index.php` | `data-toggle` × 2 |
| 70 | `hr/hr-leave/index.php` | `data-toggle` |
| 71 | `hr/hr-evaluation/index.php` | `data-toggle` × 2 |
| 72 | `hr/hr-field/mobile.php` | `${var}` string interpolation (PHP 8.5) |

### 3.4 — Forms أخرى مع Glyphicon (5 ملفات)

| # | الملف | المشاكل |
|---|-------|---------|
| 73 | `collection/_form.php` | glyphicon + `data-toggle` |
| 74 | `collection/view.php` | glyphicon |
| 75 | `invoice/_form.php` | glyphicon × 3 |
| 76 | `income/_form.php` | glyphicon × 3 |
| 77 | `jobs/view.php` | **`data-toggle` × 5** |

### 3.5 — شاشات أخرى معقدة (4 ملفات)

| # | الملف | المشاكل |
|---|-------|---------|
| 78 | `judiciary/_form.php` | BS3 Modal × 2 + CrudAsset + `.modal()` |
| 79 | `judiciary/report.php` | BS3 Modal + CrudAsset |
| 80 | `loanScheduling/index.php` | `.modal()` |
| 81 | `contracts/_legal_columns.php` | `data-toggle` × 2 + glyphicon |

**المجموع: ~24 شاشة معقدة**

---

## المرحلة 4: المحاسبة + التقارير + Dektrium

### 4.1 — المحاسبة (6 ملفات)

| # | الملف | المشاكل |
|---|-------|---------|
| 82 | `accounting/accounts-receivable/index.php` | `data-dismiss` × 2 + `.modal()` |
| 83 | `accounting/accounts-payable/index.php` | `data-dismiss` × 2 + `.modal()` |
| 84 | `accounting/chart-of-accounts/index.php` | `label label-` |
| 85 | `accounting/chart-of-accounts/tree.php` | `label label-` |
| 86 | `accounting/fiscal-year/view.php` | `label label-` |
| 87 | `accounting/fiscal-year/index.php` | `label label-` |

### 4.2 — التقارير (5 ملفات)

| # | الملف | المشاكل |
|---|-------|---------|
| 88 | `reports/follow-up-reports/index.php` | BS3 Modal + CrudAsset |
| 89 | `reports/customers-judiciary-actions-report/index.php` | BS3 Modal + CrudAsset |
| 90 | `reports/judiciary/index.php` | BS3 Modal + CrudAsset |
| 91 | `reports/judiciary/report.php` | BS3 Modal + CrudAsset |
| 92 | `reports/income-reports/index.php` | BS3 Modal + CrudAsset |

### 4.3 — Dektrium User Module (15 ملف — 131 ملف إجمالي)

**المشكلة:** الحزمة `dektrium/yii2-user` متوقفة عن التطوير منذ 2018 وتحتوي:
- `yii\bootstrap\ActiveForm` (BS3) — 3 ملفات
- `yii\bootstrap\Nav` / `Tabs` — 4 ملفات
- `panel panel-*` (BS3) — في views
- Implicitly nullable parameters (PHP 8.5 deprecated) — 6 ملفات
- 131 ملف PHP إجمالي

**الخيارات:**
1. **تحديث Views فقط** — تعديل الـ views المحلية في `backend/modules/dektrium/user/views/`
2. **استبدال كامل** — الانتقال إلى `2amigos/yii2-usuario` (الخليفة الرسمي)

**القرار الموصى به:** تحديث الـ views فقط (أقل مخاطرة)

| # | الملف | المشاكل |
|---|-------|---------|
| 93 | `admin/create.php` | ActiveForm BS3 + Tabs BS3 |
| 94 | `admin/update.php` | Tabs BS3 |
| 95 | `admin/_account.php` | ActiveForm BS3 |
| 96 | `admin/_profile.php` | ActiveForm BS3 |
| 97 | `admin/_menu.php` | Nav BS3 |
| 98 | `admin/index.php` | glyphicon × 2 |
| 99 | `profile/show.php` | glyphicon × 4 |
| 100 | `security/login.php` | `panel panel-` |
| 101 | `registration/*.php` | `panel panel-` |
| 102 | `recovery/*.php` | `panel panel-` |
| 103 | `settings/*.php` | `panel panel-` |

### 4.4 — شاشات أخرى (5 ملفات)

| # | الملف | المشاكل |
|---|-------|---------|
| 104 | `documentHolder/archives.php` | BS3 Modal + CrudAsset |
| 105 | `permissions-management/index.php` | `data-dismiss` + `.modal()` |
| 106 | `layouts/absolute.php` | `yii\bootstrap\Nav` |
| 107 | `layouts/print-template-1.php` | BS3 Modal |
| 108 | `followUpReport/_columns.php` | glyphicon |

**المجموع: ~26 ملف**

---

## المرحلة 5: تنظيف نهائي + PHP 8.5.4

### 5.1 — PHP 8.5.4 Compatibility

#### Implicitly Nullable Parameters (Deprecated in 8.4, Error in 9.0)

```php
// ❌ قديم — deprecated in PHP 8.5
function foo(string $x = null) {}

// ✅ جديد
function foo(?string $x = null) {}
```

**الملفات المتأثرة (في كود المشروع فقط):**
- `customers/components/VisionService.php` (2 مرات)
- `financialTransaction/helpers/BankStatementAnalyzer.php` (1 مرة)
- `dektrium/user/traits/EventTrait.php` (1 مرة)
- `dektrium/user/events/ResetPasswordEvent.php` (2 مرات)
- `dektrium/user/Mailer.php` (1 مرة)
- `dektrium/user/models/Profile.php` (1 مرة)

#### `${var}` String Interpolation (Deprecated in 8.2)

```php
// ❌ قديم
"Hello ${name}"

// ✅ جديد
"Hello {$name}"
```

**الملف المتأثر:**
- `hr/views/hr-field/mobile.php`

### 5.2 — CSS Cleanup

- [ ] مراجعة `backend/web/css/site.css` — إزالة مراجع glyphicon
- [ ] مراجعة `backend/web/css/custom.css` — إزالة مراجع glyphicon
- [ ] مراجعة `backend/web/css/members/custom.css` — إزالة glyphicon
- [ ] مراجعة `backend/web/css/members/style.css` — إزالة glyphicon
- [ ] مراجعة `backend/web/js/tayseer-modern.js` — إزالة glyphicon references

### 5.3 — JS Cleanup

- [ ] مراجعة `backend/web/resources/js/app.js` — glyphicon + `well` references
- [ ] حذف ملفات JS القديمة غير المستخدمة:
  - `backend/web/js/jquery-3.3.1.min.js` (يتم تحميل jQuery 3.7.1 من CDN)
  - `backend/web/js/bootstrap.js` / `bootstrap.min.js` (BS3/BS4 bundles)
  - `backend/web/js/popper.min.js` (مُدمج في bootstrap.bundle.js)
  - `backend/web/js-new/*.js` (نسخة قديمة)

### 5.4 — Composer Final Cleanup

```bash
# بعد إتمام جميع التحويلات:
composer remove yiisoft/yii2-bootstrap
composer remove yiisoft/yii2-bootstrap4
composer remove johnitvn/yii2-ajaxcrud
composer update
```

### 5.5 — AppAsset Cleanup

```php
// في backend/assets/AppAsset.php
// إزالة:
'css/bootstrap-fileinput.css',  // ← قديم
'plugins/iCheck/square/blue.css', // ← قديم إذا لم يعد مستخدماً
```

### 5.6 — حذف الملفات والمجلدات القديمة

- [ ] `backend/web/old_layout/` — **154 ملف** — حذف كامل
- [ ] `backend/assets/OldAppAsset.php` — حذف
- [ ] ملفات Bootstrap القديمة في `backend/web/js/` و `backend/web/css/`

---

## جدول تحويل المصطلحات المرجعي

| # | القديم | الجديد | البحث |
|---|--------|--------|-------|
| 1 | `use yii\bootstrap\Modal;` | حذف | grep |
| 2 | `use johnitvn\ajaxcrud\CrudAsset;` | حذف | grep |
| 3 | `CrudAsset::register($this);` | `registerCssFile + registerJsFile` | grep |
| 4 | `Modal::begin([...])` | BS5 HTML Modal | grep |
| 5 | `Modal::end();` | (مُدمج مع #4) | grep |
| 6 | `glyphicon glyphicon-plus` | `fa fa-plus` | grep |
| 7 | `glyphicon glyphicon-pencil` | `fa fa-pencil` | grep |
| 8 | `glyphicon glyphicon-trash` | `fa fa-trash` | grep |
| 9 | `glyphicon glyphicon-eye-open` | `fa fa-eye` | grep |
| 10 | `glyphicon glyphicon-repeat` | `fa fa-refresh` | grep |
| 11 | `glyphicon glyphicon-search` | `fa fa-search` | grep |
| 12 | `data-dismiss="modal"` | `data-bs-dismiss="modal"` | grep |
| 13 | `data-toggle="modal"` | `data-bs-toggle="modal"` | grep |
| 14 | `data-toggle="tab"` | `data-bs-toggle="tab"` | grep |
| 15 | `data-toggle="collapse"` | `data-bs-toggle="collapse"` | grep |
| 16 | `data-toggle="dropdown"` | `data-bs-toggle="dropdown"` | grep |
| 17 | `data-toggle="tooltip"` | `data-bs-toggle="tooltip"` | grep |
| 18 | `$('#x').modal('show')` | `bootstrap.Modal.getOrCreateInstance(el).show()` | grep |
| 19 | `$('#x').modal('hide')` | `bootstrap.Modal.getOrCreateInstance(el).hide()` | grep |
| 20 | `panel panel-default` | `card` | grep |
| 21 | `panel-heading` | `card-header` | grep |
| 22 | `panel-body` | `card-body` | grep |
| 23 | `panel-footer` | `card-footer` | grep |
| 24 | `label label-success` | `badge bg-success` | grep |
| 25 | `label label-danger` | `badge bg-danger` | grep |
| 26 | `label label-warning` | `badge bg-warning text-dark` | grep |
| 27 | `label label-info` | `badge bg-info` | grep |
| 28 | `label label-default` | `badge bg-secondary` | grep |
| 29 | `label label-primary` | `badge bg-primary` | grep |
| 30 | `btn-default` | `btn-secondary` | grep |
| 31 | `well` | `card card-body bg-light` أو `p-3 bg-light rounded` | grep |
| 32 | `yii\bootstrap\ActiveForm` | `yii\widgets\ActiveForm` | grep |
| 33 | `yii\bootstrap\Nav` | Plain HTML أو `yii\bootstrap5\Nav` | grep |
| 34 | `yii\bootstrap\Tabs` | Plain HTML أو `yii\bootstrap5\Tabs` | grep |
| 35 | `function foo(Type $x = null)` | `function foo(?Type $x = null)` | regex |
| 36 | `"${var}"` | `"{$var}"` | regex |

---

## أمر التحقق بعد كل مرحلة

```bash
# التحقق أن لم يتبق أي BS3 Modal
grep -rl "yii\\\\bootstrap\\\\Modal" backend/modules/*/views/

# التحقق أن لم يتبق أي CrudAsset
grep -rl "CrudAsset::register" backend/modules/*/views/

# التحقق من glyphicon
grep -rl "glyphicon" backend/modules/*/views/

# التحقق من data-dismiss (يجب أن يكون 0)
grep -rn "data-dismiss=" backend/modules/*/views/

# التحقق من data-toggle (يجب أن يكون 0)
grep -rn 'data-toggle=' backend/modules/*/views/

# التحقق من jQuery modal API
grep -rn "\.modal(" backend/modules/*/views/ backend/web/js/

# التحقق من yii\bootstrap\ namespace
grep -rl "yii\\\\bootstrap\\\\" backend/modules/*/views/

# التحقق من PHP 8.5 compatibility
# (implicitly nullable — يجب مراجعة يدوية)
```

---

## الملخص الكمي

| المرحلة | عدد الملفات | الأيام المقدرة |
|---------|------------|---------------|
| 0 — البنية التحتية | 5 | 1 |
| 1 — شاشات بسيطة | 30 | 3-4 |
| 2 — شاشات متوسطة | 27 | 5-6 |
| 3 — شاشات معقدة | 24 | 5-7 |
| 4 — محاسبة + تقارير + dektrium | 26 | 3-4 |
| 5 — تنظيف نهائي + PHP 8.5 | 15+ | 2-3 |
| **المجموع** | **~127 ملف** | **19-25 يوم** |

---

## ترتيب الأولوية (أيهم أولاً)

1. **المرحلة 0** — لا يمكن البدء بدونها
2. **المرحلة 1** — أسرع وأسهل، تُعطي momentum وتقلل العدد بسرعة
3. **المرحلة 5.1** — PHP 8.5 fixes يمكن عملها بالتوازي مع أي مرحلة
4. **المرحلة 2** — الشاشات الأكثر استخداماً من المستخدمين
5. **المرحلة 3** — تحتاج انتباه وفحص دقيق
6. **المرحلة 4** — أقل استخداماً يومياً

---

## ملاحظات مهمة

1. **الـ Skill الموجود** `gridview-responsive-upgrade` يحتوي الخطوات التفصيلية لتحويل كل شاشة index
2. **CrudAsset مُعطّل فعلياً** عبر `assetManager.bundles` في config — لكن الكود لا يزال يستدعيه
3. **BS3 CSS/JS مُعطّل فعلياً** عبر `assetManager.bundles` — لكن الـ PHP classes لا تزال مُستدعاة
4. **لا يجب حذف حزم Composer قبل إزالة جميع الاستدعاءات** — وإلا ستظهر أخطاء class not found
5. **يمكن عمل المراحل 1-4 بالتوازي** إذا كان هناك أكثر من مطور
