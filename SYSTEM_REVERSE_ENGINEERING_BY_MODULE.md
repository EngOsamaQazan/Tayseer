# هندسة عكسية لنظام تيسير — حسب الوحدة (Module)

**تاريخ التوليد:** 2026-03-26

## ملخص عام

| المؤشر | العدد |
|--------|------:|
| إجمالي الشاشات | 464 |
| إجمالي الفورمز | 151 |
| إجمالي ملفات الـ View | 720 |
| عدد الوحدات المكتشفة | 77 |

## الوحدة: `accounting`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 36 | 8 | 43 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `accounts-payable/aging-report` | تقرير أعمار الذمم الدائنة | واجهة عرض | `modules/accounting/views/accounts-payable/aging-report.php` |
| `accounts-payable/create` | ذمة دائنة جديدة | إنشاء سجل | `modules/accounting/views/accounts-payable/create.php` |
| `accounts-payable/index` | الذمم الدائنة | قائمة / فهرس | `modules/accounting/views/accounts-payable/index.php` |
| `accounts-payable/update` | تعديل الذمة الدائنة # | تعديل سجل | `modules/accounting/views/accounts-payable/update.php` |
| `accounts-receivable/aging-report` | تقرير أعمار الذمم المدينة | واجهة عرض | `modules/accounting/views/accounts-receivable/aging-report.php` |
| `accounts-receivable/create` | ذمة مدينة جديدة | إنشاء سجل | `modules/accounting/views/accounts-receivable/create.php` |
| `accounts-receivable/index` | الذمم المدينة | قائمة / فهرس | `modules/accounting/views/accounts-receivable/index.php` |
| `accounts-receivable/update` | تعديل الذمة المدينة # | تعديل سجل | `modules/accounting/views/accounts-receivable/update.php` |
| `ai-insights/index` | التحليل الذكي والتوصيات | قائمة / فهرس | `modules/accounting/views/ai-insights/index.php` |
| `budget/create` | موازنة جديدة | إنشاء سجل | `modules/accounting/views/budget/create.php` |
| `budget/index` | الموازنات | قائمة / فهرس | `modules/accounting/views/budget/index.php` |
| `budget/update` | تعديل الموازنة:  | تعديل سجل | `modules/accounting/views/budget/update.php` |
| `budget/variance` | تقرير انحراف الموازنة:  | واجهة عرض | `modules/accounting/views/budget/variance.php` |
| `budget/view` | — | عرض تفاصيل | `modules/accounting/views/budget/view.php` |
| `chart-of-accounts/create` | إضافة حساب جديد | إنشاء سجل | `modules/accounting/views/chart-of-accounts/create.php` |
| `chart-of-accounts/index` | شجرة الحسابات | قائمة / فهرس | `modules/accounting/views/chart-of-accounts/index.php` |
| `chart-of-accounts/tree` | شجرة الحسابات - عرض شجري | واجهة عرض | `modules/accounting/views/chart-of-accounts/tree.php` |
| `chart-of-accounts/update` | تعديل:  | تعديل سجل | `modules/accounting/views/chart-of-accounts/update.php` |
| `cost-center/create` | إضافة مركز تكلفة | إنشاء سجل | `modules/accounting/views/cost-center/create.php` |
| `cost-center/index` | مراكز التكلفة | قائمة / فهرس | `modules/accounting/views/cost-center/index.php` |
| `cost-center/update` | تعديل:  | تعديل سجل | `modules/accounting/views/cost-center/update.php` |
| `default/index` | لوحة تحكم المحاسبة | قائمة / فهرس | `modules/accounting/views/default/index.php` |
| `financial-statements/balance-sheet` | الميزانية العمومية (المركز المالي) | واجهة عرض | `modules/accounting/views/financial-statements/balance-sheet.php` |
| `financial-statements/cash-flow` | قائمة التدفقات النقدية | واجهة عرض | `modules/accounting/views/financial-statements/cash-flow.php` |
| `financial-statements/income-statement` | قائمة الدخل | واجهة عرض | `modules/accounting/views/financial-statements/income-statement.php` |
| `financial-statements/trial-balance` | ميزان المراجعة | واجهة عرض | `modules/accounting/views/financial-statements/trial-balance.php` |
| `fiscal-year/create` | إضافة سنة مالية | إنشاء سجل | `modules/accounting/views/fiscal-year/create.php` |
| `fiscal-year/index` | السنوات المالية | قائمة / فهرس | `modules/accounting/views/fiscal-year/index.php` |
| `fiscal-year/update` | تعديل:  | تعديل سجل | `modules/accounting/views/fiscal-year/update.php` |
| `fiscal-year/view` | — | عرض تفاصيل | `modules/accounting/views/fiscal-year/view.php` |
| `general-ledger/account` | دفتر حساب:  | واجهة عرض | `modules/accounting/views/general-ledger/account.php` |
| `general-ledger/index` | الأستاذ العام | قائمة / فهرس | `modules/accounting/views/general-ledger/index.php` |
| `journal-entry/create` | قيد يومية جديد | إنشاء سجل | `modules/accounting/views/journal-entry/create.php` |
| `journal-entry/index` | القيود اليومية | قائمة / فهرس | `modules/accounting/views/journal-entry/index.php` |
| `journal-entry/update` | تعديل القيد:  | تعديل سجل | `modules/accounting/views/journal-entry/update.php` |
| `journal-entry/view` | قيد رقم  | عرض تفاصيل | `modules/accounting/views/journal-entry/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/accounting/views/accounts-payable/_form.php` |
| `_form` | ملف _form | `modules/accounting/views/accounts-receivable/_form.php` |
| `_form` | ملف _form | `modules/accounting/views/budget/_form.php` |
| `view` | ActiveForm داخل الملف | `modules/accounting/views/budget/view.php` |
| `_form` | ملف _form | `modules/accounting/views/chart-of-accounts/_form.php` |
| `_form` | ملف _form | `modules/accounting/views/cost-center/_form.php` |
| `_form` | ملف _form | `modules/accounting/views/fiscal-year/_form.php` |
| `_form` | ملف _form | `modules/accounting/views/journal-entry/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/accounting/views/accounts-payable/_form.php`
- `modules/accounting/views/accounts-payable/aging-report.php`
- `modules/accounting/views/accounts-payable/create.php`
- `modules/accounting/views/accounts-payable/index.php`
- `modules/accounting/views/accounts-payable/update.php`
- `modules/accounting/views/accounts-receivable/_form.php`
- `modules/accounting/views/accounts-receivable/aging-report.php`
- `modules/accounting/views/accounts-receivable/create.php`
- `modules/accounting/views/accounts-receivable/index.php`
- `modules/accounting/views/accounts-receivable/update.php`
- `modules/accounting/views/ai-insights/index.php`
- `modules/accounting/views/budget/_form.php`
- `modules/accounting/views/budget/create.php`
- `modules/accounting/views/budget/index.php`
- `modules/accounting/views/budget/update.php`
- `modules/accounting/views/budget/variance.php`
- `modules/accounting/views/budget/view.php`
- `modules/accounting/views/chart-of-accounts/_form.php`
- `modules/accounting/views/chart-of-accounts/create.php`
- `modules/accounting/views/chart-of-accounts/index.php`
- `modules/accounting/views/chart-of-accounts/tree.php`
- `modules/accounting/views/chart-of-accounts/update.php`
- `modules/accounting/views/cost-center/_form.php`
- `modules/accounting/views/cost-center/create.php`
- `modules/accounting/views/cost-center/index.php`
- `modules/accounting/views/cost-center/update.php`
- `modules/accounting/views/default/index.php`
- `modules/accounting/views/financial-statements/balance-sheet.php`
- `modules/accounting/views/financial-statements/cash-flow.php`
- `modules/accounting/views/financial-statements/income-statement.php`
- `modules/accounting/views/financial-statements/trial-balance.php`
- `modules/accounting/views/fiscal-year/_form.php`
- `modules/accounting/views/fiscal-year/create.php`
- `modules/accounting/views/fiscal-year/index.php`
- `modules/accounting/views/fiscal-year/update.php`
- `modules/accounting/views/fiscal-year/view.php`
- `modules/accounting/views/general-ledger/account.php`
- `modules/accounting/views/general-ledger/index.php`
- `modules/accounting/views/journal-entry/_form.php`
- `modules/accounting/views/journal-entry/create.php`
- `modules/accounting/views/journal-entry/index.php`
- `modules/accounting/views/journal-entry/update.php`
- `modules/accounting/views/journal-entry/view.php`

</details>

## الوحدة: `address`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `address/create` | — | إنشاء سجل | `modules/address/views/address/create.php` |
| `address/index` | Addresses | قائمة / فهرس | `modules/address/views/address/index.php` |
| `address/update` | — | تعديل سجل | `modules/address/views/address/update.php` |
| `address/view` | — | عرض تفاصيل | `modules/address/views/address/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/address/views/address/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/address/views/address/_columns.php`
- `modules/address/views/address/_form.php`
- `modules/address/views/address/create.php`
- `modules/address/views/address/index.php`
- `modules/address/views/address/update.php`
- `modules/address/views/address/view.php`

</details>

## الوحدة: `attendance`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `attendance/create` | — | إنشاء سجل | `modules/attendance/views/attendance/create.php` |
| `attendance/index` | Attendances | قائمة / فهرس | `modules/attendance/views/attendance/index.php` |
| `attendance/update` | — | تعديل سجل | `modules/attendance/views/attendance/update.php` |
| `attendance/view` | — | عرض تفاصيل | `modules/attendance/views/attendance/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/attendance/views/attendance/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/attendance/views/attendance/_columns.php`
- `modules/attendance/views/attendance/_form.php`
- `modules/attendance/views/attendance/create.php`
- `modules/attendance/views/attendance/index.php`
- `modules/attendance/views/attendance/update.php`
- `modules/attendance/views/attendance/view.php`

</details>

## الوحدة: `authAssignment`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `auth-assignment/create` | — | إنشاء سجل | `modules/authAssignment/views/auth-assignment/create.php` |
| `auth-assignment/index` | Auth Assignments | قائمة / فهرس | `modules/authAssignment/views/auth-assignment/index.php` |
| `auth-assignment/update` | — | تعديل سجل | `modules/authAssignment/views/auth-assignment/update.php` |
| `auth-assignment/view` | — | عرض تفاصيل | `modules/authAssignment/views/auth-assignment/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/authAssignment/views/auth-assignment/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/authAssignment/views/auth-assignment/_columns.php`
- `modules/authAssignment/views/auth-assignment/_form.php`
- `modules/authAssignment/views/auth-assignment/create.php`
- `modules/authAssignment/views/auth-assignment/index.php`
- `modules/authAssignment/views/auth-assignment/update.php`
- `modules/authAssignment/views/auth-assignment/view.php`

</details>

## الوحدة: `bancks`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `bancks/create` | — | إنشاء سجل | `modules/bancks/views/bancks/create.php` |
| `bancks/index` | Bancks | قائمة / فهرس | `modules/bancks/views/bancks/index.php` |
| `bancks/update` | — | تعديل سجل | `modules/bancks/views/bancks/update.php` |
| `bancks/view` | — | عرض تفاصيل | `modules/bancks/views/bancks/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/bancks/views/bancks/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/bancks/views/bancks/_columns.php`
- `modules/bancks/views/bancks/_form.php`
- `modules/bancks/views/bancks/create.php`
- `modules/bancks/views/bancks/index.php`
- `modules/bancks/views/bancks/update.php`
- `modules/bancks/views/bancks/view.php`

</details>

## الوحدة: `capitalTransactions`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 5 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `capital-transactions/create` | إضافة حركة رأس مال | إنشاء سجل | `modules/capitalTransactions/views/capital-transactions/create.php` |
| `capital-transactions/index` | حركات رأس المال | قائمة / فهرس | `modules/capitalTransactions/views/capital-transactions/index.php` |
| `capital-transactions/update` | تعديل حركة رأس مال # | تعديل سجل | `modules/capitalTransactions/views/capital-transactions/update.php` |
| `capital-transactions/view` | عرض حركة رأس مال # | عرض تفاصيل | `modules/capitalTransactions/views/capital-transactions/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/capitalTransactions/views/capital-transactions/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/capitalTransactions/views/capital-transactions/_form.php`
- `modules/capitalTransactions/views/capital-transactions/create.php`
- `modules/capitalTransactions/views/capital-transactions/index.php`
- `modules/capitalTransactions/views/capital-transactions/update.php`
- `modules/capitalTransactions/views/capital-transactions/view.php`

</details>

## الوحدة: `citizen`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `citizen/create` | — | إنشاء سجل | `modules/citizen/views/citizen/create.php` |
| `citizen/index` | Citizens | قائمة / فهرس | `modules/citizen/views/citizen/index.php` |
| `citizen/update` | — | تعديل سجل | `modules/citizen/views/citizen/update.php` |
| `citizen/view` | — | عرض تفاصيل | `modules/citizen/views/citizen/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/citizen/views/citizen/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/citizen/views/citizen/_columns.php`
- `modules/citizen/views/citizen/_form.php`
- `modules/citizen/views/citizen/create.php`
- `modules/citizen/views/citizen/index.php`
- `modules/citizen/views/citizen/update.php`
- `modules/citizen/views/citizen/view.php`

</details>

## الوحدة: `city`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `city/create` | — | إنشاء سجل | `modules/city/views/city/create.php` |
| `city/index` | Cities | قائمة / فهرس | `modules/city/views/city/index.php` |
| `city/update` | — | تعديل سجل | `modules/city/views/city/update.php` |
| `city/view` | — | عرض تفاصيل | `modules/city/views/city/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/city/views/city/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/city/views/city/_columns.php`
- `modules/city/views/city/_form.php`
- `modules/city/views/city/create.php`
- `modules/city/views/city/index.php`
- `modules/city/views/city/update.php`
- `modules/city/views/city/view.php`

</details>

## الوحدة: `collection`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `collection/create` | — | إنشاء سجل | `modules/collection/views/collection/create.php` |
| `collection/index` | Collections | قائمة / فهرس | `modules/collection/views/collection/index.php` |
| `collection/update` | — | تعديل سجل | `modules/collection/views/collection/update.php` |
| `collection/view` | — | عرض تفاصيل | `modules/collection/views/collection/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/collection/views/collection/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/collection/views/collection/_columns.php`
- `modules/collection/views/collection/_form.php`
- `modules/collection/views/collection/create.php`
- `modules/collection/views/collection/index.php`
- `modules/collection/views/collection/update.php`
- `modules/collection/views/collection/view.php`

</details>

## الوحدة: `companies`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 5 | 2 | 8 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `companies/_parital/company_banks` | — | واجهة عرض | `modules/companies/views/companies/_parital/company_banks.php` |
| `companies/create` | إضافة مُستثمر جديد | إنشاء سجل | `modules/companies/views/companies/create.php` |
| `companies/index` | المُستثمرين | قائمة / فهرس | `modules/companies/views/companies/index.php` |
| `companies/update` | تعديل بيانات مُستثمر | تعديل سجل | `modules/companies/views/companies/update.php` |
| `companies/view` | عرض المُستثمر:  | عرض تفاصيل | `modules/companies/views/companies/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/companies/views/companies/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/companies/views/companies/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/companies/views/companies/_columns.php`
- `modules/companies/views/companies/_form.php`
- `modules/companies/views/companies/_parital/company_banks.php`
- `modules/companies/views/companies/_search.php`
- `modules/companies/views/companies/create.php`
- `modules/companies/views/companies/index.php`
- `modules/companies/views/companies/update.php`
- `modules/companies/views/companies/view.php`

</details>

## الوحدة: `companyBanks`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 5 | 1 | 7 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `company-banks/create` | — | إنشاء سجل | `modules/companyBanks/views/company-banks/create.php` |
| `company-banks/index` | Company Banks | قائمة / فهرس | `modules/companyBanks/views/company-banks/index.php` |
| `company-banks/update` | — | تعديل سجل | `modules/companyBanks/views/company-banks/update.php` |
| `company-banks/view` | — | عرض تفاصيل | `modules/companyBanks/views/company-banks/view.php` |
| `default/index` | — | قائمة / فهرس | `modules/companyBanks/views/default/index.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/companyBanks/views/company-banks/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/companyBanks/views/company-banks/_columns.php`
- `modules/companyBanks/views/company-banks/_form.php`
- `modules/companyBanks/views/company-banks/create.php`
- `modules/companyBanks/views/company-banks/index.php`
- `modules/companyBanks/views/company-banks/update.php`
- `modules/companyBanks/views/company-banks/view.php`
- `modules/companyBanks/views/default/index.php`

</details>

## الوحدة: `connectionResponse`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `connection-response/create` | — | إنشاء سجل | `modules/connectionResponse/views/connection-response/create.php` |
| `connection-response/index` | Connection Responses | قائمة / فهرس | `modules/connectionResponse/views/connection-response/index.php` |
| `connection-response/update` | — | تعديل سجل | `modules/connectionResponse/views/connection-response/update.php` |
| `connection-response/view` | — | عرض تفاصيل | `modules/connectionResponse/views/connection-response/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/connectionResponse/views/connection-response/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/connectionResponse/views/connection-response/_columns.php`
- `modules/connectionResponse/views/connection-response/_form.php`
- `modules/connectionResponse/views/connection-response/create.php`
- `modules/connectionResponse/views/connection-response/index.php`
- `modules/connectionResponse/views/connection-response/update.php`
- `modules/connectionResponse/views/connection-response/view.php`

</details>

## الوحدة: `contactType`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `contact-type/create` | — | إنشاء سجل | `modules/contactType/views/contact-type/create.php` |
| `contact-type/index` | Contact Types | قائمة / فهرس | `modules/contactType/views/contact-type/index.php` |
| `contact-type/update` | — | تعديل سجل | `modules/contactType/views/contact-type/update.php` |
| `contact-type/view` | — | عرض تفاصيل | `modules/contactType/views/contact-type/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/contactType/views/contact-type/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/contactType/views/contact-type/_columns.php`
- `modules/contactType/views/contact-type/_form.php`
- `modules/contactType/views/contact-type/create.php`
- `modules/contactType/views/contact-type/index.php`
- `modules/contactType/views/contact-type/update.php`
- `modules/contactType/views/contact-type/view.php`

</details>

## الوحدة: `contractDocumentFile`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `contract-document-file/create` | — | إنشاء سجل | `modules/contractDocumentFile/views/contract-document-file/create.php` |
| `contract-document-file/index` | Contract Document Files | قائمة / فهرس | `modules/contractDocumentFile/views/contract-document-file/index.php` |
| `contract-document-file/update` | — | تعديل سجل | `modules/contractDocumentFile/views/contract-document-file/update.php` |
| `contract-document-file/view` | — | عرض تفاصيل | `modules/contractDocumentFile/views/contract-document-file/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/contractDocumentFile/views/contract-document-file/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/contractDocumentFile/views/contract-document-file/_columns.php`
- `modules/contractDocumentFile/views/contract-document-file/_form.php`
- `modules/contractDocumentFile/views/contract-document-file/create.php`
- `modules/contractDocumentFile/views/contract-document-file/index.php`
- `modules/contractDocumentFile/views/contract-document-file/update.php`
- `modules/contractDocumentFile/views/contract-document-file/view.php`

</details>

## الوحدة: `contractInstallment`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 6 | 2 | 8 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `contract-installment/create` | إضافة دفعة جديدة | إنشاء سجل | `modules/contractInstallment/views/contract-installment/create.php` |
| `contract-installment/index` | أقساط العقد رقم | قائمة / فهرس | `modules/contractInstallment/views/contract-installment/index.php` |
| `contract-installment/print` | — | طباعة / تقرير | `modules/contractInstallment/views/contract-installment/print.php` |
| `contract-installment/update` | تعديل الدفعة رقم | تعديل سجل | `modules/contractInstallment/views/contract-installment/update.php` |
| `contract-installment/verify-receipt` | تحقق من الإيصال | واجهة عرض | `modules/contractInstallment/views/contract-installment/verify-receipt.php` |
| `contract-installment/view` | تفاصيل الدفعة رقم | عرض تفاصيل | `modules/contractInstallment/views/contract-installment/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/contractInstallment/views/contract-installment/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/contractInstallment/views/contract-installment/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/contractInstallment/views/contract-installment/_form.php`
- `modules/contractInstallment/views/contract-installment/_search.php`
- `modules/contractInstallment/views/contract-installment/create.php`
- `modules/contractInstallment/views/contract-installment/index.php`
- `modules/contractInstallment/views/contract-installment/print.php`
- `modules/contractInstallment/views/contract-installment/update.php`
- `modules/contractInstallment/views/contract-installment/verify-receipt.php`
- `modules/contractInstallment/views/contract-installment/view.php`

</details>

## الوحدة: `contracts`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 8 | 5 | 19 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `contracts/create` | إنشاء عقد جديد | إنشاء سجل | `modules/contracts/views/contracts/create.php` |
| `contracts/first_page` | — | واجهة عرض | `modules/contracts/views/contracts/first_page.php` |
| `contracts/index-legal-department` | الدائرة القانونية | واجهة عرض | `modules/contracts/views/contracts/index-legal-department.php` |
| `contracts/index` | العقود | قائمة / فهرس | `modules/contracts/views/contracts/index.php` |
| `contracts/print` | ابحث واختر الكفلاء... | طباعة / تقرير | `modules/contracts/views/contracts/print.php` |
| `contracts/second_page` | — | واجهة عرض | `modules/contracts/views/contracts/second_page.php` |
| `contracts/update` | تعديل العقد # | تعديل سجل | `modules/contracts/views/contracts/update.php` |
| `contracts/view` | العقد # | عرض تفاصيل | `modules/contracts/views/contracts/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/contracts/views/contracts/_form.php` |
| `_legal_department_search` | ActiveForm داخل الملف | `modules/contracts/views/contracts/_legal_department_search.php` |
| `_legal_search_v2` | ActiveForm داخل الملف | `modules/contracts/views/contracts/_legal_search_v2.php` |
| `_search` | ActiveForm داخل الملف | `modules/contracts/views/contracts/_search.php` |
| `print` | ActiveForm داخل الملف | `modules/contracts/views/contracts/print.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/contracts/views/contracts/_adjustments.php`
- `modules/contracts/views/contracts/_columns.php`
- `modules/contracts/views/contracts/_contract_print.php`
- `modules/contracts/views/contracts/_draft_print.php`
- `modules/contracts/views/contracts/_form.php`
- `modules/contracts/views/contracts/_legal_columns.php`
- `modules/contracts/views/contracts/_legal_department_search.php`
- `modules/contracts/views/contracts/_legal_search_v2.php`
- `modules/contracts/views/contracts/_print_overlay.php`
- `modules/contracts/views/contracts/_print_preview.php`
- `modules/contracts/views/contracts/_search.php`
- `modules/contracts/views/contracts/create.php`
- `modules/contracts/views/contracts/first_page.php`
- `modules/contracts/views/contracts/index-legal-department.php`
- `modules/contracts/views/contracts/index.php`
- `modules/contracts/views/contracts/print.php`
- `modules/contracts/views/contracts/second_page.php`
- `modules/contracts/views/contracts/update.php`
- `modules/contracts/views/contracts/view.php`

</details>

## الوحدة: `core-backend`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 8 | 2 | 35 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `permissions-management/index` | إدارة الصلاحيات | قائمة / فهرس | `views/permissions-management/index.php` |
| `site/error` | حدث خطأ | صفحة خطأ | `views/site/error.php` |
| `site/image-manager` | إدارة صور العملاء | إدارة / مدير | `views/site/image-manager.php` |
| `site/index` | لوحة التحكم | قائمة / فهرس | `views/site/index.php` |
| `site/system-settings` | إعدادات النظام | واجهة عرض | `views/site/system-settings.php` |
| `user-tools/index` | أدوات المستخدم | قائمة / فهرس | `views/user-tools/index.php` |
| `user/security/login` | تسجيل الدخول | تسجيل الدخول | `views/user/security/login.php` |
| `v` | — | واجهة عرض | `views/v.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `index` | ActiveForm داخل الملف | `views/user-tools/index.php` |
| `login` | ActiveForm داخل الملف | `views/user/security/login.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `views/_section_tabs.php`
- `views/layouts/_diwan-tabs.php`
- `views/layouts/_financial-tabs.php`
- `views/layouts/_inventory-tabs.php`
- `views/layouts/_menu_items.php`
- `views/layouts/_reports-tabs.php`
- `views/layouts/absolute.php`
- `views/layouts/content.php`
- `views/layouts/footer.php`
- `views/layouts/header.php`
- `views/layouts/left.php`
- `views/layouts/login_layout/content.php`
- `views/layouts/login_layout/main.php`
- `views/layouts/main-login.php`
- `views/layouts/main-v3.php`
- `views/layouts/main.php`
- `views/layouts/modal-ajax.php`
- `views/layouts/navigation.php`
- `views/layouts/overall.php`
- `views/layouts/print-template-1.php`
- `views/layouts/print_cases.php`
- `views/layouts/print_templete_2.php`
- `views/layouts/printe_content.php`
- `views/layouts/printer_content.php`
- `views/layouts/profile_layout/content_profile.php`
- `views/layouts/profile_layout/main_profile.php`
- `views/permissions-management/index.php`
- `views/site/error.php`
- `views/site/image-manager.php`
- `views/site/index.php`
- `views/site/system-settings.php`
- `views/user-tools/index.php`
- `views/user/_alert.php`
- `views/user/security/login.php`
- `views/v.php`

</details>

## الوحدة: `court`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 7 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `court/create` | Create Court | إنشاء سجل | `modules/court/views/court/create.php` |
| `court/index` | Courts | قائمة / فهرس | `modules/court/views/court/index.php` |
| `court/update` | Update Court | تعديل سجل | `modules/court/views/court/update.php` |
| `court/view` | — | عرض تفاصيل | `modules/court/views/court/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/court/views/court/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/court/views/court/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/court/views/court/_columns.php`
- `modules/court/views/court/_form.php`
- `modules/court/views/court/_search.php`
- `modules/court/views/court/create.php`
- `modules/court/views/court/index.php`
- `modules/court/views/court/update.php`
- `modules/court/views/court/view.php`

</details>

## الوحدة: `cousins`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `cousins/create` | — | إنشاء سجل | `modules/cousins/views/cousins/create.php` |
| `cousins/index` | Cousins | قائمة / فهرس | `modules/cousins/views/cousins/index.php` |
| `cousins/update` | — | تعديل سجل | `modules/cousins/views/cousins/update.php` |
| `cousins/view` | — | عرض تفاصيل | `modules/cousins/views/cousins/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/cousins/views/cousins/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/cousins/views/cousins/_columns.php`
- `modules/cousins/views/cousins/_form.php`
- `modules/cousins/views/cousins/create.php`
- `modules/cousins/views/cousins/index.php`
- `modules/cousins/views/cousins/update.php`
- `modules/cousins/views/cousins/view.php`

</details>

## الوحدة: `customers`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 11 | 4 | 15 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `customers/contact_form` | رقم الهاتف الرئيسي | نموذج بيانات | `modules/customers/views/customers/contact_form.php` |
| `customers/contact_update` | — | تعديل سجل | `modules/customers/views/customers/contact_update.php` |
| `customers/create-summary` | تم إضافة العميل بنجاح | إنشاء سجل | `modules/customers/views/customers/create-summary.php` |
| `customers/create` | إضافة عميل جديد | إنشاء سجل | `modules/customers/views/customers/create.php` |
| `customers/index` | العملاء | قائمة / فهرس | `modules/customers/views/customers/index.php` |
| `customers/partial/address` | — | جزء واجهة (partial) | `modules/customers/views/customers/partial/address.php` |
| `customers/partial/customer_documents` | — | جزء واجهة (partial) | `modules/customers/views/customers/partial/customer_documents.php` |
| `customers/partial/phone_numbers` | — | جزء واجهة (partial) | `modules/customers/views/customers/partial/phone_numbers.php` |
| `customers/partial/real_estate` | — | جزء واجهة (partial) | `modules/customers/views/customers/partial/real_estate.php` |
| `customers/update` | تعديل:  | تعديل سجل | `modules/customers/views/customers/update.php` |
| `customers/view` | العميل:  | عرض تفاصيل | `modules/customers/views/customers/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/customers/views/customers/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/customers/views/customers/_search.php` |
| `_smart_form` | ملف _form | `modules/customers/views/customers/_smart_form.php` |
| `contact_form` | ملف _form | `modules/customers/views/customers/contact_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/customers/views/customers/_columns.php`
- `modules/customers/views/customers/_form.php`
- `modules/customers/views/customers/_search.php`
- `modules/customers/views/customers/_smart_form.php`
- `modules/customers/views/customers/contact_form.php`
- `modules/customers/views/customers/contact_update.php`
- `modules/customers/views/customers/create-summary.php`
- `modules/customers/views/customers/create.php`
- `modules/customers/views/customers/index.php`
- `modules/customers/views/customers/partial/address.php`
- `modules/customers/views/customers/partial/customer_documents.php`
- `modules/customers/views/customers/partial/phone_numbers.php`
- `modules/customers/views/customers/partial/real_estate.php`
- `modules/customers/views/customers/update.php`
- `modules/customers/views/customers/view.php`

</details>

## الوحدة: `department`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `department/create` | — | إنشاء سجل | `modules/department/views/department/create.php` |
| `department/index` | Departments | قائمة / فهرس | `modules/department/views/department/index.php` |
| `department/update` | — | تعديل سجل | `modules/department/views/department/update.php` |
| `department/view` | — | عرض تفاصيل | `modules/department/views/department/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/department/views/department/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/department/views/department/_columns.php`
- `modules/department/views/department/_form.php`
- `modules/department/views/department/create.php`
- `modules/department/views/department/index.php`
- `modules/department/views/department/update.php`
- `modules/department/views/department/view.php`

</details>

## الوحدة: `designation`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `designation/create` | — | إنشاء سجل | `modules/designation/views/designation/create.php` |
| `designation/index` | المسميات الوظيفية والأقسام | قائمة / فهرس | `modules/designation/views/designation/index.php` |
| `designation/update` | — | تعديل سجل | `modules/designation/views/designation/update.php` |
| `designation/view` | — | عرض تفاصيل | `modules/designation/views/designation/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/designation/views/designation/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/designation/views/designation/_columns.php`
- `modules/designation/views/designation/_form.php`
- `modules/designation/views/designation/create.php`
- `modules/designation/views/designation/index.php`
- `modules/designation/views/designation/update.php`
- `modules/designation/views/designation/view.php`

</details>

## الوحدة: `divisionsCollection`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 5 | 1 | 7 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `default/index` | — | قائمة / فهرس | `modules/divisionsCollection/views/default/index.php` |
| `divisions-collection/create` | — | إنشاء سجل | `modules/divisionsCollection/views/divisions-collection/create.php` |
| `divisions-collection/index` | Divisions Collections | قائمة / فهرس | `modules/divisionsCollection/views/divisions-collection/index.php` |
| `divisions-collection/update` | — | تعديل سجل | `modules/divisionsCollection/views/divisions-collection/update.php` |
| `divisions-collection/view` | — | عرض تفاصيل | `modules/divisionsCollection/views/divisions-collection/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/divisionsCollection/views/divisions-collection/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/divisionsCollection/views/default/index.php`
- `modules/divisionsCollection/views/divisions-collection/_columns.php`
- `modules/divisionsCollection/views/divisions-collection/_form.php`
- `modules/divisionsCollection/views/divisions-collection/create.php`
- `modules/divisionsCollection/views/divisions-collection/index.php`
- `modules/divisionsCollection/views/divisions-collection/update.php`
- `modules/divisionsCollection/views/divisions-collection/view.php`

</details>

## الوحدة: `diwan`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 10 | 1 | 10 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `diwan/correspondence_index` | المراسلات والتبليغات | قائمة / فهرس | `modules/diwan/views/diwan/correspondence_index.php` |
| `diwan/correspondence_view` | مراسلة # | واجهة عرض | `modules/diwan/views/diwan/correspondence_view.php` |
| `diwan/create` | قسم الديوان | إنشاء سجل | `modules/diwan/views/diwan/create.php` |
| `diwan/document_history` | قسم الديوان | واجهة عرض | `modules/diwan/views/diwan/document_history.php` |
| `diwan/index` | قسم الديوان | قائمة / فهرس | `modules/diwan/views/diwan/index.php` |
| `diwan/receipt` | قسم الديوان | واجهة عرض | `modules/diwan/views/diwan/receipt.php` |
| `diwan/reports` | قسم الديوان | واجهة عرض | `modules/diwan/views/diwan/reports.php` |
| `diwan/search` | قسم الديوان | واجهة عرض | `modules/diwan/views/diwan/search.php` |
| `diwan/transactions` | قسم الديوان | واجهة عرض | `modules/diwan/views/diwan/transactions.php` |
| `diwan/view` | قسم الديوان | عرض تفاصيل | `modules/diwan/views/diwan/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `create` | ActiveForm داخل الملف | `modules/diwan/views/diwan/create.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/diwan/views/diwan/correspondence_index.php`
- `modules/diwan/views/diwan/correspondence_view.php`
- `modules/diwan/views/diwan/create.php`
- `modules/diwan/views/diwan/document_history.php`
- `modules/diwan/views/diwan/index.php`
- `modules/diwan/views/diwan/receipt.php`
- `modules/diwan/views/diwan/reports.php`
- `modules/diwan/views/diwan/search.php`
- `modules/diwan/views/diwan/transactions.php`
- `modules/diwan/views/diwan/view.php`

</details>

## الوحدة: `documentHolder`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 6 | 1 | 10 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `document-holder/archives` | Document Holders | واجهة عرض | `modules/documentHolder/views/document-holder/archives.php` |
| `document-holder/create` | — | إنشاء سجل | `modules/documentHolder/views/document-holder/create.php` |
| `document-holder/index` | Document Holders | قائمة / فهرس | `modules/documentHolder/views/document-holder/index.php` |
| `document-holder/manager_index` | Document Holders | قائمة / فهرس | `modules/documentHolder/views/document-holder/manager_index.php` |
| `document-holder/update` | — | تعديل سجل | `modules/documentHolder/views/document-holder/update.php` |
| `document-holder/view` | — | عرض تفاصيل | `modules/documentHolder/views/document-holder/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/documentHolder/views/document-holder/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/documentHolder/views/document-holder/_archives_columns.php`
- `modules/documentHolder/views/document-holder/_columns.php`
- `modules/documentHolder/views/document-holder/_form.php`
- `modules/documentHolder/views/document-holder/_manager_column.php`
- `modules/documentHolder/views/document-holder/archives.php`
- `modules/documentHolder/views/document-holder/create.php`
- `modules/documentHolder/views/document-holder/index.php`
- `modules/documentHolder/views/document-holder/manager_index.php`
- `modules/documentHolder/views/document-holder/update.php`
- `modules/documentHolder/views/document-holder/view.php`

</details>

## الوحدة: `documentStatus`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `document-status/create` | — | إنشاء سجل | `modules/documentStatus/views/document-status/create.php` |
| `document-status/index` | Document Statuses | قائمة / فهرس | `modules/documentStatus/views/document-status/index.php` |
| `document-status/update` | — | تعديل سجل | `modules/documentStatus/views/document-status/update.php` |
| `document-status/view` | — | عرض تفاصيل | `modules/documentStatus/views/document-status/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/documentStatus/views/document-status/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/documentStatus/views/document-status/_columns.php`
- `modules/documentStatus/views/document-status/_form.php`
- `modules/documentStatus/views/document-status/create.php`
- `modules/documentStatus/views/document-status/index.php`
- `modules/documentStatus/views/document-status/update.php`
- `modules/documentStatus/views/document-status/view.php`

</details>

## الوحدة: `documentType`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `document-type/create` | — | إنشاء سجل | `modules/documentType/views/document-type/create.php` |
| `document-type/index` | Document Types | قائمة / فهرس | `modules/documentType/views/document-type/index.php` |
| `document-type/update` | — | تعديل سجل | `modules/documentType/views/document-type/update.php` |
| `document-type/view` | — | عرض تفاصيل | `modules/documentType/views/document-type/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/documentType/views/document-type/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/documentType/views/document-type/_columns.php`
- `modules/documentType/views/document-type/_form.php`
- `modules/documentType/views/document-type/create.php`
- `modules/documentType/views/document-type/index.php`
- `modules/documentType/views/document-type/update.php`
- `modules/documentType/views/document-type/view.php`

</details>

## الوحدة: `employee`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 8 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `employee/create` | — | إنشاء سجل | `modules/employee/views/employee/create.php` |
| `employee/index` | Employees | قائمة / فهرس | `modules/employee/views/employee/index.php` |
| `employee/update` | ملفي الشخصي -  | تعديل سجل | `modules/employee/views/employee/update.php` |
| `employee/view` | — | عرض تفاصيل | `modules/employee/views/employee/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/employee/views/employee/_form.php` |
| `_leave_policy` | ActiveForm داخل الملف | `modules/employee/views/employee/_leave_policy.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/employee/views/employee/_columns.php`
- `modules/employee/views/employee/_form.php`
- `modules/employee/views/employee/_leave_policy.php`
- `modules/employee/views/employee/_partial/_attachments_table.php`
- `modules/employee/views/employee/create.php`
- `modules/employee/views/employee/index.php`
- `modules/employee/views/employee/update.php`
- `modules/employee/views/employee/view.php`

</details>

## الوحدة: `expenseCategories`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 5 | 3 | 8 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `expense-categories/create` | Create Expense Categories | إنشاء سجل | `modules/expenseCategories/views/expense-categories/create.php` |
| `expense-categories/import` | — | نموذج بيانات | `modules/expenseCategories/views/expense-categories/import.php` |
| `expense-categories/index` | Expense Categories | قائمة / فهرس | `modules/expenseCategories/views/expense-categories/index.php` |
| `expense-categories/update` | Update Expense Categories  | تعديل سجل | `modules/expenseCategories/views/expense-categories/update.php` |
| `expense-categories/view` | — | عرض تفاصيل | `modules/expenseCategories/views/expense-categories/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/expenseCategories/views/expense-categories/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/expenseCategories/views/expense-categories/_search.php` |
| `import` | ActiveForm داخل الملف | `modules/expenseCategories/views/expense-categories/import.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/expenseCategories/views/expense-categories/_columns.php`
- `modules/expenseCategories/views/expense-categories/_form.php`
- `modules/expenseCategories/views/expense-categories/_search.php`
- `modules/expenseCategories/views/expense-categories/create.php`
- `modules/expenseCategories/views/expense-categories/import.php`
- `modules/expenseCategories/views/expense-categories/index.php`
- `modules/expenseCategories/views/expense-categories/update.php`
- `modules/expenseCategories/views/expense-categories/view.php`

</details>

## الوحدة: `expenses`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 7 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `expenses/create` | إضافة مصروف جديد | إنشاء سجل | `modules/expenses/views/expenses/create.php` |
| `expenses/index` | الإدارة المالية | قائمة / فهرس | `modules/expenses/views/expenses/index.php` |
| `expenses/update` | تعديل المصروف | تعديل سجل | `modules/expenses/views/expenses/update.php` |
| `expenses/view` | تفاصيل المصروف | عرض تفاصيل | `modules/expenses/views/expenses/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/expenses/views/expenses/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/expenses/views/expenses/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/expenses/views/expenses/_columns.php`
- `modules/expenses/views/expenses/_form.php`
- `modules/expenses/views/expenses/_search.php`
- `modules/expenses/views/expenses/create.php`
- `modules/expenses/views/expenses/index.php`
- `modules/expenses/views/expenses/update.php`
- `modules/expenses/views/expenses/view.php`

</details>

## الوحدة: `feelings`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `feelings/create` | — | إنشاء سجل | `modules/feelings/views/feelings/create.php` |
| `feelings/index` | Feelings | قائمة / فهرس | `modules/feelings/views/feelings/index.php` |
| `feelings/update` | — | تعديل سجل | `modules/feelings/views/feelings/update.php` |
| `feelings/view` | — | عرض تفاصيل | `modules/feelings/views/feelings/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/feelings/views/feelings/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/feelings/views/feelings/_columns.php`
- `modules/feelings/views/feelings/_form.php`
- `modules/feelings/views/feelings/create.php`
- `modules/feelings/views/feelings/index.php`
- `modules/feelings/views/feelings/update.php`
- `modules/feelings/views/feelings/view.php`

</details>

## الوحدة: `financialTransaction`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 6 | 3 | 9 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `financial-transaction/create` | إضافة حركة مالية | إنشاء سجل | `modules/financialTransaction/views/financial-transaction/create.php` |
| `financial-transaction/import` | استيراد كشف حساب بنكي | نموذج بيانات | `modules/financialTransaction/views/financial-transaction/import.php` |
| `financial-transaction/import_grid_view` | Expenses | واجهة عرض | `modules/financialTransaction/views/financial-transaction/import_grid_view.php` |
| `financial-transaction/index` | الإدارة المالية | قائمة / فهرس | `modules/financialTransaction/views/financial-transaction/index.php` |
| `financial-transaction/update` | تعديل حركة مالية # | تعديل سجل | `modules/financialTransaction/views/financial-transaction/update.php` |
| `financial-transaction/view` | حركة مالية # | عرض تفاصيل | `modules/financialTransaction/views/financial-transaction/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/financialTransaction/views/financial-transaction/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/financialTransaction/views/financial-transaction/_search.php` |
| `import` | ActiveForm داخل الملف | `modules/financialTransaction/views/financial-transaction/import.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/financialTransaction/views/financial-transaction/_columns.php`
- `modules/financialTransaction/views/financial-transaction/_form.php`
- `modules/financialTransaction/views/financial-transaction/_search.php`
- `modules/financialTransaction/views/financial-transaction/create.php`
- `modules/financialTransaction/views/financial-transaction/import.php`
- `modules/financialTransaction/views/financial-transaction/import_grid_view.php`
- `modules/financialTransaction/views/financial-transaction/index.php`
- `modules/financialTransaction/views/financial-transaction/update.php`
- `modules/financialTransaction/views/financial-transaction/view.php`

</details>

## الوحدة: `followUp`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 23 | 3 | 32 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `follow-up/clearance` | — | واجهة عرض | `modules/followUp/views/follow-up/clearance.php` |
| `follow-up/create` | Create Follow Up | إنشاء سجل | `modules/followUp/views/follow-up/create.php` |
| `follow-up/index` | متابعة العقد # | قائمة / فهرس | `modules/followUp/views/follow-up/index.php` |
| `follow-up/modals` | — | واجهة عرض | `modules/followUp/views/follow-up/modals.php` |
| `follow-up/panel` | لوحة تحكم العقد # | واجهة عرض | `modules/followUp/views/follow-up/panel.php` |
| `follow-up/partial/follow-up-view` | — | جزء واجهة (partial) | `modules/followUp/views/follow-up/partial/follow-up-view.php` |
| `follow-up/partial/next_contract` | معلومات العقد تالي | جزء واجهة (partial) | `modules/followUp/views/follow-up/partial/next_contract.php` |
| `follow-up/partial/phone_numbers_follow_up` | — | جزء واجهة (partial) | `modules/followUp/views/follow-up/partial/phone_numbers_follow_up.php` |
| `follow-up/partial/tabs` | — | جزء واجهة (partial) | `modules/followUp/views/follow-up/partial/tabs.php` |
| `follow-up/partial/tabs/actions` | صور العملاء | جزء واجهة (partial) | `modules/followUp/views/follow-up/partial/tabs/actions.php` |
| `follow-up/partial/tabs/financial` |  رسوم القضيه | جزء واجهة (partial) | `modules/followUp/views/follow-up/partial/tabs/financial.php` |
| `follow-up/partial/tabs/judiciary_customers_actions` | — | جزء واجهة (partial) | `modules/followUp/views/follow-up/partial/tabs/judiciary_customers_actions.php` |
| `follow-up/partial/tabs/loan_scheduling` | — | جزء واجهة (partial) | `modules/followUp/views/follow-up/partial/tabs/loan_scheduling.php` |
| `follow-up/partial/tabs/payments` | — | جزء واجهة (partial) | `modules/followUp/views/follow-up/partial/tabs/payments.php` |
| `follow-up/partial/tabs/phone_numbers` | — | جزء واجهة (partial) | `modules/followUp/views/follow-up/partial/tabs/phone_numbers.php` |
| `follow-up/phone_number_create` | — | إنشاء سجل | `modules/followUp/views/follow-up/phone_number_create.php` |
| `follow-up/phone_number_form` | — | نموذج بيانات | `modules/followUp/views/follow-up/phone_number_form.php` |
| `follow-up/phone_number_update` | — | تعديل سجل | `modules/followUp/views/follow-up/phone_number_update.php` |
| `follow-up/printer` | — | طباعة / تقرير | `modules/followUp/views/follow-up/printer.php` |
| `follow-up/tabs` | — | تبويب / محتوى تبويب | `modules/followUp/views/follow-up/tabs.php` |
| `follow-up/update` | التحويل للدائرة الفانونية | تعديل سجل | `modules/followUp/views/follow-up/update.php` |
| `follow-up/verify-statement` | تحقق من كشف الحساب | واجهة عرض | `modules/followUp/views/follow-up/verify-statement.php` |
| `follow-up/view` | — | عرض تفاصيل | `modules/followUp/views/follow-up/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/followUp/views/follow-up/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/followUp/views/follow-up/_search.php` |
| `phone_number_form` | ملف _form | `modules/followUp/views/follow-up/phone_number_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/followUp/views/follow-up/_columns.php`
- `modules/followUp/views/follow-up/_form.php`
- `modules/followUp/views/follow-up/_search.php`
- `modules/followUp/views/follow-up/clearance.php`
- `modules/followUp/views/follow-up/create.php`
- `modules/followUp/views/follow-up/index.php`
- `modules/followUp/views/follow-up/modals.php`
- `modules/followUp/views/follow-up/panel.php`
- `modules/followUp/views/follow-up/panel/_ai_suggestions.php`
- `modules/followUp/views/follow-up/panel/_financial.php`
- `modules/followUp/views/follow-up/panel/_judiciary_tab.php`
- `modules/followUp/views/follow-up/panel/_kanban.php`
- `modules/followUp/views/follow-up/panel/_side_panels.php`
- `modules/followUp/views/follow-up/panel/_timeline.php`
- `modules/followUp/views/follow-up/partial/follow-up-view.php`
- `modules/followUp/views/follow-up/partial/next_contract.php`
- `modules/followUp/views/follow-up/partial/phone_numbers_follow_up.php`
- `modules/followUp/views/follow-up/partial/tabs.php`
- `modules/followUp/views/follow-up/partial/tabs/actions.php`
- `modules/followUp/views/follow-up/partial/tabs/financial.php`
- `modules/followUp/views/follow-up/partial/tabs/judiciary_customers_actions.php`
- `modules/followUp/views/follow-up/partial/tabs/loan_scheduling.php`
- `modules/followUp/views/follow-up/partial/tabs/payments.php`
- `modules/followUp/views/follow-up/partial/tabs/phone_numbers.php`
- `modules/followUp/views/follow-up/phone_number_create.php`
- `modules/followUp/views/follow-up/phone_number_form.php`
- `modules/followUp/views/follow-up/phone_number_update.php`
- `modules/followUp/views/follow-up/printer.php`
- `modules/followUp/views/follow-up/tabs.php`
- `modules/followUp/views/follow-up/update.php`
- `modules/followUp/views/follow-up/verify-statement.php`
- `modules/followUp/views/follow-up/view.php`

</details>

## الوحدة: `followUpReport`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 7 | 5 | 10 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `follow-up-report/create` | Create New Contracts | إنشاء سجل | `modules/followUpReport/views/follow-up-report/create.php` |
| `follow-up-report/first_page` | — | واجهة عرض | `modules/followUpReport/views/follow-up-report/first_page.php` |
| `follow-up-report/index` | — | قائمة / فهرس | `modules/followUpReport/views/follow-up-report/index.php` |
| `follow-up-report/no-contact` | عقود بدون أرقام تواصل | نموذج بيانات | `modules/followUpReport/views/follow-up-report/no-contact.php` |
| `follow-up-report/print` | ابحث واختر الكفلاء... | طباعة / تقرير | `modules/followUpReport/views/follow-up-report/print.php` |
| `follow-up-report/update` | Create New Contracts | تعديل سجل | `modules/followUpReport/views/follow-up-report/update.php` |
| `follow-up-report/view` | — | عرض تفاصيل | `modules/followUpReport/views/follow-up-report/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/followUpReport/views/follow-up-report/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/followUpReport/views/follow-up-report/_search.php` |
| `index` | ActiveForm داخل الملف | `modules/followUpReport/views/follow-up-report/index.php` |
| `no-contact` | ActiveForm داخل الملف | `modules/followUpReport/views/follow-up-report/no-contact.php` |
| `print` | ActiveForm داخل الملف | `modules/followUpReport/views/follow-up-report/print.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/followUpReport/views/follow-up-report/_columns.php`
- `modules/followUpReport/views/follow-up-report/_form.php`
- `modules/followUpReport/views/follow-up-report/_search.php`
- `modules/followUpReport/views/follow-up-report/create.php`
- `modules/followUpReport/views/follow-up-report/first_page.php`
- `modules/followUpReport/views/follow-up-report/index.php`
- `modules/followUpReport/views/follow-up-report/no-contact.php`
- `modules/followUpReport/views/follow-up-report/print.php`
- `modules/followUpReport/views/follow-up-report/update.php`
- `modules/followUpReport/views/follow-up-report/view.php`

</details>

## الوحدة: `hearAboutUs`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `hear-about-us/create` | — | إنشاء سجل | `modules/hearAboutUs/views/hear-about-us/create.php` |
| `hear-about-us/index` | Hear About uses | قائمة / فهرس | `modules/hearAboutUs/views/hear-about-us/index.php` |
| `hear-about-us/update` | — | تعديل سجل | `modules/hearAboutUs/views/hear-about-us/update.php` |
| `hear-about-us/view` | — | عرض تفاصيل | `modules/hearAboutUs/views/hear-about-us/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/hearAboutUs/views/hear-about-us/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/hearAboutUs/views/hear-about-us/_columns.php`
- `modules/hearAboutUs/views/hear-about-us/_form.php`
- `modules/hearAboutUs/views/hear-about-us/create.php`
- `modules/hearAboutUs/views/hear-about-us/index.php`
- `modules/hearAboutUs/views/hear-about-us/update.php`
- `modules/hearAboutUs/views/hear-about-us/view.php`

</details>

## الوحدة: `holidays`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 7 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `holidays/create` | — | إنشاء سجل | `modules/holidays/views/holidays/create.php` |
| `holidays/index` | Holidays | قائمة / فهرس | `modules/holidays/views/holidays/index.php` |
| `holidays/update` | — | تعديل سجل | `modules/holidays/views/holidays/update.php` |
| `holidays/view` | — | عرض تفاصيل | `modules/holidays/views/holidays/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/holidays/views/holidays/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/holidays/views/holidays/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/holidays/views/holidays/_columns.php`
- `modules/holidays/views/holidays/_form.php`
- `modules/holidays/views/holidays/_search.php`
- `modules/holidays/views/holidays/create.php`
- `modules/holidays/views/holidays/index.php`
- `modules/holidays/views/holidays/update.php`
- `modules/holidays/views/holidays/view.php`

</details>

## الوحدة: `hr`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 39 | 7 | 42 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `hr-attendance/create` | إدخال حضور يدوي | إنشاء سجل | `modules/hr/views/hr-attendance/create.php` |
| `hr-attendance/index` | لوحة الحضور والانصراف | قائمة / فهرس | `modules/hr/views/hr-attendance/index.php` |
| `hr-attendance/summary` | ملخص الحضور الشهري | واجهة عرض | `modules/hr/views/hr-attendance/summary.php` |
| `hr-dashboard/index` | الموارد البشرية | قائمة / فهرس | `modules/hr/views/hr-dashboard/index.php` |
| `hr-employee/create` | إضافة بيانات موظف | إنشاء سجل | `modules/hr/views/hr-employee/create.php` |
| `hr-employee/index` | سجل الموظفين | قائمة / فهرس | `modules/hr/views/hr-employee/index.php` |
| `hr-employee/statement` | كشف حساب —  | واجهة عرض | `modules/hr/views/hr-employee/statement.php` |
| `hr-employee/update` | تعديل بيانات الموظف | تعديل سجل | `modules/hr/views/hr-employee/update.php` |
| `hr-employee/view` | ملف الموظف —  | عرض تفاصيل | `modules/hr/views/hr-employee/view.php` |
| `hr-evaluation/index` | تقييمات الأداء | قائمة / فهرس | `modules/hr/views/hr-evaluation/index.php` |
| `hr-field/index` | لوحة المهام الميدانية | قائمة / فهرس | `modules/hr/views/hr-field/index.php` |
| `hr-field/map` | خريطة تتبع المناديب | واجهة عرض | `modules/hr/views/hr-field/map.php` |
| `hr-field/mobile-login` | نظام الحضور والانصراف | تسجيل الدخول | `modules/hr/views/hr-field/mobile-login.php` |
| `hr-field/mobile` | نظام الحضور والانصراف | واجهة عرض | `modules/hr/views/hr-field/mobile.php` |
| `hr-leave/index` | إدارة الإجازات | قائمة / فهرس | `modules/hr/views/hr-leave/index.php` |
| `hr-loan/index` | السلف والقروض | قائمة / فهرس | `modules/hr/views/hr-loan/index.php` |
| `hr-payroll/adjustments` | عمولات وتعديلات —  | واجهة عرض | `modules/hr/views/hr-payroll/adjustments.php` |
| `hr-payroll/components` | مكونات الراتب | واجهة عرض | `modules/hr/views/hr-payroll/components.php` |
| `hr-payroll/create` | إنشاء مسيرة رواتب جديدة | إنشاء سجل | `modules/hr/views/hr-payroll/create.php` |
| `hr-payroll/increment-bulk-preview` | معاينة العلاوة التلقائية | واجهة عرض | `modules/hr/views/hr-payroll/increment-bulk-preview.php` |
| `hr-payroll/increment-bulk` | علاوة تلقائية (حسب الأقدمية) | واجهة عرض | `modules/hr/views/hr-payroll/increment-bulk.php` |
| `hr-payroll/increment-form` | إنشاء علاوة سنوية جديدة | نموذج بيانات | `modules/hr/views/hr-payroll/increment-form.php` |
| `hr-payroll/increments` | العلاوات السنوية | واجهة عرض | `modules/hr/views/hr-payroll/increments.php` |
| `hr-payroll/index` | مسيرات الرواتب | قائمة / فهرس | `modules/hr/views/hr-payroll/index.php` |
| `hr-payroll/payslip` | كشف راتب —  | واجهة عرض | `modules/hr/views/hr-payroll/payslip.php` |
| `hr-payroll/view` | مسيرة الرواتب —  | عرض تفاصيل | `modules/hr/views/hr-payroll/view.php` |
| `hr-report/index` | تقارير الموارد البشرية | قائمة / فهرس | `modules/hr/views/hr-report/index.php` |
| `hr-shift/form` | إضافة وردية | نموذج بيانات | `modules/hr/views/hr-shift/form.php` |
| `hr-shift/index` | إدارة الورديات | قائمة / فهرس | `modules/hr/views/hr-shift/index.php` |
| `hr-tracking-api/attendance-board` | لوحة الحضور الموحّدة | واجهة عرض | `modules/hr/views/hr-tracking-api/attendance-board.php` |
| `hr-tracking-api/live-map` | التتبع المباشر — خريطة حية | واجهة عرض | `modules/hr/views/hr-tracking-api/live-map.php` |
| `hr-tracking-api/mobile-attendance` | — | واجهة عرض | `modules/hr/views/hr-tracking-api/mobile-attendance.php` |
| `hr-tracking-api/mobile-login` | نظام الحضور الذكي | تسجيل الدخول | `modules/hr/views/hr-tracking-api/mobile-login.php` |
| `hr-tracking-report/index` | تحليلات الحضور والتتبع | قائمة / فهرس | `modules/hr/views/hr-tracking-report/index.php` |
| `hr-tracking-report/monthly` | التقرير الشهري —  | واجهة عرض | `modules/hr/views/hr-tracking-report/monthly.php` |
| `hr-tracking-report/punctuality` | تقرير الانضباط الوظيفي | واجهة عرض | `modules/hr/views/hr-tracking-report/punctuality.php` |
| `hr-tracking-report/violations` | تقرير المخالفات والأمان | واجهة عرض | `modules/hr/views/hr-tracking-report/violations.php` |
| `hr-work-zone/form` | إضافة منطقة عمل | نموذج بيانات | `modules/hr/views/hr-work-zone/form.php` |
| `hr-work-zone/index` | مناطق العمل (Geofences) | قائمة / فهرس | `modules/hr/views/hr-work-zone/index.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/hr/views/hr-attendance/_form.php` |
| `index` | ActiveForm داخل الملف | `modules/hr/views/hr-attendance/index.php` |
| `_form` | ملف _form | `modules/hr/views/hr-employee/_form.php` |
| `create` | ActiveForm داخل الملف | `modules/hr/views/hr-payroll/create.php` |
| `increment-form` | ActiveForm داخل الملف | `modules/hr/views/hr-payroll/increment-form.php` |
| `form` | ActiveForm داخل الملف | `modules/hr/views/hr-shift/form.php` |
| `form` | ActiveForm داخل الملف | `modules/hr/views/hr-work-zone/form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/hr/views/_section_tabs.php`
- `modules/hr/views/hr-attendance/_form.php`
- `modules/hr/views/hr-attendance/create.php`
- `modules/hr/views/hr-attendance/index.php`
- `modules/hr/views/hr-attendance/summary.php`
- `modules/hr/views/hr-dashboard/index.php`
- `modules/hr/views/hr-employee/_form.php`
- `modules/hr/views/hr-employee/create.php`
- `modules/hr/views/hr-employee/index.php`
- `modules/hr/views/hr-employee/statement.php`
- `modules/hr/views/hr-employee/update.php`
- `modules/hr/views/hr-employee/view.php`
- `modules/hr/views/hr-evaluation/index.php`
- `modules/hr/views/hr-field/index.php`
- `modules/hr/views/hr-field/map.php`
- `modules/hr/views/hr-field/mobile-login.php`
- `modules/hr/views/hr-field/mobile.php`
- `modules/hr/views/hr-leave/index.php`
- `modules/hr/views/hr-loan/index.php`
- `modules/hr/views/hr-payroll/adjustments.php`
- `modules/hr/views/hr-payroll/components.php`
- `modules/hr/views/hr-payroll/create.php`
- `modules/hr/views/hr-payroll/increment-bulk-preview.php`
- `modules/hr/views/hr-payroll/increment-bulk.php`
- `modules/hr/views/hr-payroll/increment-form.php`
- `modules/hr/views/hr-payroll/increments.php`
- `modules/hr/views/hr-payroll/index.php`
- `modules/hr/views/hr-payroll/payslip.php`
- `modules/hr/views/hr-payroll/view.php`
- `modules/hr/views/hr-report/index.php`
- `modules/hr/views/hr-shift/form.php`
- `modules/hr/views/hr-shift/index.php`
- `modules/hr/views/hr-tracking-api/attendance-board.php`
- `modules/hr/views/hr-tracking-api/live-map.php`
- `modules/hr/views/hr-tracking-api/mobile-attendance.php`
- `modules/hr/views/hr-tracking-api/mobile-login.php`
- `modules/hr/views/hr-tracking-report/index.php`
- `modules/hr/views/hr-tracking-report/monthly.php`
- `modules/hr/views/hr-tracking-report/punctuality.php`
- `modules/hr/views/hr-tracking-report/violations.php`
- `modules/hr/views/hr-work-zone/form.php`
- `modules/hr/views/hr-work-zone/index.php`

</details>

## الوحدة: `income`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 6 | 4 | 11 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `income/create` | — | إنشاء سجل | `modules/income/views/income/create.php` |
| `income/income-item-list` | الإدارة المالية | واجهة عرض | `modules/income/views/income/income-item-list.php` |
| `income/income_list_form` | — | نموذج بيانات | `modules/income/views/income/income_list_form.php` |
| `income/index` | Income | قائمة / فهرس | `modules/income/views/income/index.php` |
| `income/update` | — | تعديل سجل | `modules/income/views/income/update.php` |
| `income/view` | — | عرض تفاصيل | `modules/income/views/income/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/income/views/income/_form.php` |
| `_income-list-search` | ActiveForm داخل الملف | `modules/income/views/income/_income-list-search.php` |
| `_search` | ActiveForm داخل الملف | `modules/income/views/income/_search.php` |
| `income_list_form` | ملف _form | `modules/income/views/income/income_list_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/income/views/income/_columns.php`
- `modules/income/views/income/_form.php`
- `modules/income/views/income/_income-list-columns.php`
- `modules/income/views/income/_income-list-search.php`
- `modules/income/views/income/_search.php`
- `modules/income/views/income/create.php`
- `modules/income/views/income/income-item-list.php`
- `modules/income/views/income/income_list_form.php`
- `modules/income/views/income/index.php`
- `modules/income/views/income/update.php`
- `modules/income/views/income/view.php`

</details>

## الوحدة: `incomeCategory`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `income-category/create` | — | إنشاء سجل | `modules/incomeCategory/views/income-category/create.php` |
| `income-category/index` | Income Categories | قائمة / فهرس | `modules/incomeCategory/views/income-category/index.php` |
| `income-category/update` | — | تعديل سجل | `modules/incomeCategory/views/income-category/update.php` |
| `income-category/view` | — | عرض تفاصيل | `modules/incomeCategory/views/income-category/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/incomeCategory/views/income-category/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/incomeCategory/views/income-category/_columns.php`
- `modules/incomeCategory/views/income-category/_form.php`
- `modules/incomeCategory/views/income-category/create.php`
- `modules/incomeCategory/views/income-category/index.php`
- `modules/incomeCategory/views/income-category/update.php`
- `modules/incomeCategory/views/income-category/view.php`

</details>

## الوحدة: `inventoryInvoices`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 7 | 1 | 10 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `default/index` | — | قائمة / فهرس | `modules/inventoryInvoices/views/default/index.php` |
| `inventory-invoices/create-wizard` | فاتورة توريد جديدة (معالج) | إنشاء سجل | `modules/inventoryInvoices/views/inventory-invoices/create-wizard.php` |
| `inventory-invoices/create` | — | إنشاء سجل | `modules/inventoryInvoices/views/inventory-invoices/create.php` |
| `inventory-invoices/index` | إدارة المخزون | قائمة / فهرس | `modules/inventoryInvoices/views/inventory-invoices/index.php` |
| `inventory-invoices/reject-reception` | رفض استلام الفاتورة # | واجهة عرض | `modules/inventoryInvoices/views/inventory-invoices/reject-reception.php` |
| `inventory-invoices/update` | — | تعديل سجل | `modules/inventoryInvoices/views/inventory-invoices/update.php` |
| `inventory-invoices/view` | فاتورة # | عرض تفاصيل | `modules/inventoryInvoices/views/inventory-invoices/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/inventoryInvoices/views/inventory-invoices/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/inventoryInvoices/views/default/index.php`
- `modules/inventoryInvoices/views/inventory-invoices/_columns.php`
- `modules/inventoryInvoices/views/inventory-invoices/_form.php`
- `modules/inventoryInvoices/views/inventory-invoices/_items_inventory_invoices.php`
- `modules/inventoryInvoices/views/inventory-invoices/create-wizard.php`
- `modules/inventoryInvoices/views/inventory-invoices/create.php`
- `modules/inventoryInvoices/views/inventory-invoices/index.php`
- `modules/inventoryInvoices/views/inventory-invoices/reject-reception.php`
- `modules/inventoryInvoices/views/inventory-invoices/update.php`
- `modules/inventoryInvoices/views/inventory-invoices/view.php`

</details>

## الوحدة: `inventoryItemQuantities`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 7 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `inventory-item-quantities/create` | Create New Item Quantities | إنشاء سجل | `modules/inventoryItemQuantities/views/inventory-item-quantities/create.php` |
| `inventory-item-quantities/index` | إدارة المخزون | قائمة / فهرس | `modules/inventoryItemQuantities/views/inventory-item-quantities/index.php` |
| `inventory-item-quantities/update` | Update Item Quantities | تعديل سجل | `modules/inventoryItemQuantities/views/inventory-item-quantities/update.php` |
| `inventory-item-quantities/view` | — | عرض تفاصيل | `modules/inventoryItemQuantities/views/inventory-item-quantities/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/inventoryItemQuantities/views/inventory-item-quantities/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/inventoryItemQuantities/views/inventory-item-quantities/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/inventoryItemQuantities/views/inventory-item-quantities/_columns.php`
- `modules/inventoryItemQuantities/views/inventory-item-quantities/_form.php`
- `modules/inventoryItemQuantities/views/inventory-item-quantities/_search.php`
- `modules/inventoryItemQuantities/views/inventory-item-quantities/create.php`
- `modules/inventoryItemQuantities/views/inventory-item-quantities/index.php`
- `modules/inventoryItemQuantities/views/inventory-item-quantities/update.php`
- `modules/inventoryItemQuantities/views/inventory-item-quantities/view.php`

</details>

## الوحدة: `inventoryItems`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 10 | 4 | 18 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `inventory-items/create` | Create New Item | إنشاء سجل | `modules/inventoryItems/views/inventory-items/create.php` |
| `inventory-items/dashboard` | إدارة المخزون | لوحة معلومات | `modules/inventoryItems/views/inventory-items/dashboard.php` |
| `inventory-items/index` | إدارة المخزون | قائمة / فهرس | `modules/inventoryItems/views/inventory-items/index.php` |
| `inventory-items/index_item_query` | إدارة المخزون | واجهة عرض | `modules/inventoryItems/views/inventory-items/index_item_query.php` |
| `inventory-items/items` | إدارة المخزون | واجهة عرض | `modules/inventoryItems/views/inventory-items/items.php` |
| `inventory-items/movements` | إدارة المخزون | واجهة عرض | `modules/inventoryItems/views/inventory-items/movements.php` |
| `inventory-items/serial-numbers` | الأرقام التسلسلية — إدارة المخزون | واجهة عرض | `modules/inventoryItems/views/inventory-items/serial-numbers.php` |
| `inventory-items/settings` | إدارة المخزون | واجهة عرض | `modules/inventoryItems/views/inventory-items/settings.php` |
| `inventory-items/update` | Update New Item | تعديل سجل | `modules/inventoryItems/views/inventory-items/update.php` |
| `inventory-items/view` | — | عرض تفاصيل | `modules/inventoryItems/views/inventory-items/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_batch_form` | ملف _form | `modules/inventoryItems/views/inventory-items/_batch_form.php` |
| `_form` | ملف _form | `modules/inventoryItems/views/inventory-items/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/inventoryItems/views/inventory-items/_search.php` |
| `_serial_form` | ملف _form | `modules/inventoryItems/views/inventory-items/_serial_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/inventoryItems/views/inventory-items/_batch_form.php`
- `modules/inventoryItems/views/inventory-items/_columns.php`
- `modules/inventoryItems/views/inventory-items/_columns_item_query.php`
- `modules/inventoryItems/views/inventory-items/_form.php`
- `modules/inventoryItems/views/inventory-items/_search.php`
- `modules/inventoryItems/views/inventory-items/_serial_columns.php`
- `modules/inventoryItems/views/inventory-items/_serial_form.php`
- `modules/inventoryItems/views/inventory-items/_serial_view.php`
- `modules/inventoryItems/views/inventory-items/create.php`
- `modules/inventoryItems/views/inventory-items/dashboard.php`
- `modules/inventoryItems/views/inventory-items/index.php`
- `modules/inventoryItems/views/inventory-items/index_item_query.php`
- `modules/inventoryItems/views/inventory-items/items.php`
- `modules/inventoryItems/views/inventory-items/movements.php`
- `modules/inventoryItems/views/inventory-items/serial-numbers.php`
- `modules/inventoryItems/views/inventory-items/settings.php`
- `modules/inventoryItems/views/inventory-items/update.php`
- `modules/inventoryItems/views/inventory-items/view.php`

</details>

## الوحدة: `inventoryStockLocations`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 7 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `inventory-stock-locations/create` | Create New Location | إنشاء سجل | `modules/inventoryStockLocations/views/inventory-stock-locations/create.php` |
| `inventory-stock-locations/index` | إدارة المخزون | قائمة / فهرس | `modules/inventoryStockLocations/views/inventory-stock-locations/index.php` |
| `inventory-stock-locations/update` | Update Location | تعديل سجل | `modules/inventoryStockLocations/views/inventory-stock-locations/update.php` |
| `inventory-stock-locations/view` | — | عرض تفاصيل | `modules/inventoryStockLocations/views/inventory-stock-locations/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/inventoryStockLocations/views/inventory-stock-locations/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/inventoryStockLocations/views/inventory-stock-locations/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/inventoryStockLocations/views/inventory-stock-locations/_columns.php`
- `modules/inventoryStockLocations/views/inventory-stock-locations/_form.php`
- `modules/inventoryStockLocations/views/inventory-stock-locations/_search.php`
- `modules/inventoryStockLocations/views/inventory-stock-locations/create.php`
- `modules/inventoryStockLocations/views/inventory-stock-locations/index.php`
- `modules/inventoryStockLocations/views/inventory-stock-locations/update.php`
- `modules/inventoryStockLocations/views/inventory-stock-locations/view.php`

</details>

## الوحدة: `inventorySuppliers`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 7 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `inventory-suppliers/create` | Create New Supplier | إنشاء سجل | `modules/inventorySuppliers/views/inventory-suppliers/create.php` |
| `inventory-suppliers/index` | إدارة المخزون | قائمة / فهرس | `modules/inventorySuppliers/views/inventory-suppliers/index.php` |
| `inventory-suppliers/update` | Update Supplier | تعديل سجل | `modules/inventorySuppliers/views/inventory-suppliers/update.php` |
| `inventory-suppliers/view` | — | عرض تفاصيل | `modules/inventorySuppliers/views/inventory-suppliers/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/inventorySuppliers/views/inventory-suppliers/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/inventorySuppliers/views/inventory-suppliers/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/inventorySuppliers/views/inventory-suppliers/_columns.php`
- `modules/inventorySuppliers/views/inventory-suppliers/_form.php`
- `modules/inventorySuppliers/views/inventory-suppliers/_search.php`
- `modules/inventorySuppliers/views/inventory-suppliers/create.php`
- `modules/inventorySuppliers/views/inventory-suppliers/index.php`
- `modules/inventorySuppliers/views/inventory-suppliers/update.php`
- `modules/inventorySuppliers/views/inventory-suppliers/view.php`

</details>

## الوحدة: `invoice`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 8 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `invoice/create` | — | إنشاء سجل | `modules/invoice/views/invoice/create.php` |
| `invoice/index` | Invoices | قائمة / فهرس | `modules/invoice/views/invoice/index.php` |
| `invoice/update` | — | تعديل سجل | `modules/invoice/views/invoice/update.php` |
| `invoice/view` | — | عرض تفاصيل | `modules/invoice/views/invoice/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/invoice/views/invoice/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/invoice/views/invoice/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/invoice/views/invoice/_columns.php`
- `modules/invoice/views/invoice/_customer.php`
- `modules/invoice/views/invoice/_form.php`
- `modules/invoice/views/invoice/_search.php`
- `modules/invoice/views/invoice/create.php`
- `modules/invoice/views/invoice/index.php`
- `modules/invoice/views/invoice/update.php`
- `modules/invoice/views/invoice/view.php`

</details>

## الوحدة: `items`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 7 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `items/create` | Create Item | إنشاء سجل | `modules/items/views/items/create.php` |
| `items/index` | Items | قائمة / فهرس | `modules/items/views/items/index.php` |
| `items/update` | — | تعديل سجل | `modules/items/views/items/update.php` |
| `items/view` | — | عرض تفاصيل | `modules/items/views/items/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/items/views/items/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/items/views/items/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/items/views/items/_columns.php`
- `modules/items/views/items/_form.php`
- `modules/items/views/items/_search.php`
- `modules/items/views/items/create.php`
- `modules/items/views/items/index.php`
- `modules/items/views/items/update.php`
- `modules/items/views/items/view.php`

</details>

## الوحدة: `itemsInventoryInvoices`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 5 | 1 | 7 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `default/index` | — | قائمة / فهرس | `modules/itemsInventoryInvoices/views/default/index.php` |
| `items-inventory-invoices/create` | — | إنشاء سجل | `modules/itemsInventoryInvoices/views/items-inventory-invoices/create.php` |
| `items-inventory-invoices/index` | Items Inventory Invoices | قائمة / فهرس | `modules/itemsInventoryInvoices/views/items-inventory-invoices/index.php` |
| `items-inventory-invoices/update` | — | تعديل سجل | `modules/itemsInventoryInvoices/views/items-inventory-invoices/update.php` |
| `items-inventory-invoices/view` | — | عرض تفاصيل | `modules/itemsInventoryInvoices/views/items-inventory-invoices/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/itemsInventoryInvoices/views/items-inventory-invoices/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/itemsInventoryInvoices/views/default/index.php`
- `modules/itemsInventoryInvoices/views/items-inventory-invoices/_columns.php`
- `modules/itemsInventoryInvoices/views/items-inventory-invoices/_form.php`
- `modules/itemsInventoryInvoices/views/items-inventory-invoices/create.php`
- `modules/itemsInventoryInvoices/views/items-inventory-invoices/index.php`
- `modules/itemsInventoryInvoices/views/items-inventory-invoices/update.php`
- `modules/itemsInventoryInvoices/views/items-inventory-invoices/view.php`

</details>

## الوحدة: `jobs`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 7 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `jobs/create` | إضافة جهة عمل جديدة | إنشاء سجل | `modules/jobs/views/jobs/create.php` |
| `jobs/index` | جهات العمل | قائمة / فهرس | `modules/jobs/views/jobs/index.php` |
| `jobs/update` | تعديل جهة العمل:  | تعديل سجل | `modules/jobs/views/jobs/update.php` |
| `jobs/view` | — | عرض تفاصيل | `modules/jobs/views/jobs/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/jobs/views/jobs/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/jobs/views/jobs/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/jobs/views/jobs/_columns.php`
- `modules/jobs/views/jobs/_form.php`
- `modules/jobs/views/jobs/_search.php`
- `modules/jobs/views/jobs/create.php`
- `modules/jobs/views/jobs/index.php`
- `modules/jobs/views/jobs/update.php`
- `modules/jobs/views/jobs/view.php`

</details>

## الوحدة: `judiciary`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 13 | 2 | 22 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `judiciary/batch_actions` | الإدخال المجمّع | واجهة عرض | `modules/judiciary/views/judiciary/batch_actions.php` |
| `judiciary/batch_create` | تجهيز القضايا — معالج جماعي | إنشاء سجل | `modules/judiciary/views/judiciary/batch_create.php` |
| `judiciary/batch_print` | — | طباعة / تقرير | `modules/judiciary/views/judiciary/batch_print.php` |
| `judiciary/cases_report` | كشف المثابره | واجهة عرض | `modules/judiciary/views/judiciary/cases_report.php` |
| `judiciary/cases_report_print` | كشف المثابره | طباعة / تقرير | `modules/judiciary/views/judiciary/cases_report_print.php` |
| `judiciary/create` | إنشاء قضية - عقد # | إنشاء سجل | `modules/judiciary/views/judiciary/create.php` |
| `judiciary/deadline_dashboard` | لوحة المواعيد النهائية | لوحة معلومات | `modules/judiciary/views/judiciary/deadline_dashboard.php` |
| `judiciary/generate_request` | توليد طلب إجرائي — القضية # | واجهة عرض | `modules/judiciary/views/judiciary/generate_request.php` |
| `judiciary/index` | القسم القانوني | قائمة / فهرس | `modules/judiciary/views/judiciary/index.php` |
| `judiciary/print_case` | — | طباعة / تقرير | `modules/judiciary/views/judiciary/print_case.php` |
| `judiciary/report` | Judiciaries | واجهة عرض | `modules/judiciary/views/judiciary/report.php` |
| `judiciary/update` | تعديل القضية # | تعديل سجل | `modules/judiciary/views/judiciary/update.php` |
| `judiciary/view` | ملف القضية # | عرض تفاصيل | `modules/judiciary/views/judiciary/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/judiciary/views/judiciary/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/judiciary/views/judiciary/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/judiciary/views/judiciary/_columns.php`
- `modules/judiciary/views/judiciary/_form.php`
- `modules/judiciary/views/judiciary/_report_columns.php`
- `modules/judiciary/views/judiciary/_search.php`
- `modules/judiciary/views/judiciary/_tab_actions.php`
- `modules/judiciary/views/judiciary/_tab_cases.php`
- `modules/judiciary/views/judiciary/_tab_collection.php`
- `modules/judiciary/views/judiciary/_tab_legal.php`
- `modules/judiciary/views/judiciary/_tab_persistence.php`
- `modules/judiciary/views/judiciary/batch_actions.php`
- `modules/judiciary/views/judiciary/batch_create.php`
- `modules/judiciary/views/judiciary/batch_print.php`
- `modules/judiciary/views/judiciary/cases_report.php`
- `modules/judiciary/views/judiciary/cases_report_print.php`
- `modules/judiciary/views/judiciary/create.php`
- `modules/judiciary/views/judiciary/deadline_dashboard.php`
- `modules/judiciary/views/judiciary/generate_request.php`
- `modules/judiciary/views/judiciary/index.php`
- `modules/judiciary/views/judiciary/print_case.php`
- `modules/judiciary/views/judiciary/report.php`
- `modules/judiciary/views/judiciary/update.php`
- `modules/judiciary/views/judiciary/view.php`

</details>

## الوحدة: `judiciaryActions`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 9 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `judiciary-actions/create` | — | إنشاء سجل | `modules/judiciaryActions/views/judiciary-actions/create.php` |
| `judiciary-actions/index` | إدارة الإجراءات القضائية | قائمة / فهرس | `modules/judiciaryActions/views/judiciary-actions/index.php` |
| `judiciary-actions/update` | — | تعديل سجل | `modules/judiciaryActions/views/judiciary-actions/update.php` |
| `judiciary-actions/view` | — | عرض تفاصيل | `modules/judiciaryActions/views/judiciary-actions/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/judiciaryActions/views/judiciary-actions/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/judiciaryActions/views/judiciary-actions/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/judiciaryActions/views/judiciary-actions/_columns.php`
- `modules/judiciaryActions/views/judiciary-actions/_confirm_delete.php`
- `modules/judiciaryActions/views/judiciary-actions/_form.php`
- `modules/judiciaryActions/views/judiciary-actions/_search.php`
- `modules/judiciaryActions/views/judiciary-actions/_usage_details.php`
- `modules/judiciaryActions/views/judiciary-actions/create.php`
- `modules/judiciaryActions/views/judiciary-actions/index.php`
- `modules/judiciaryActions/views/judiciary-actions/update.php`
- `modules/judiciaryActions/views/judiciary-actions/view.php`

</details>

## الوحدة: `judiciaryAuthorities`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `judiciary-authorities/create` | إضافة جهة رسمية | إنشاء سجل | `modules/judiciaryAuthorities/views/judiciary-authorities/create.php` |
| `judiciary-authorities/index` | الجهات الرسمية | قائمة / فهرس | `modules/judiciaryAuthorities/views/judiciary-authorities/index.php` |
| `judiciary-authorities/update` | تعديل:  | تعديل سجل | `modules/judiciaryAuthorities/views/judiciary-authorities/update.php` |
| `judiciary-authorities/view` | — | عرض تفاصيل | `modules/judiciaryAuthorities/views/judiciary-authorities/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/judiciaryAuthorities/views/judiciary-authorities/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/judiciaryAuthorities/views/judiciary-authorities/_columns.php`
- `modules/judiciaryAuthorities/views/judiciary-authorities/_form.php`
- `modules/judiciaryAuthorities/views/judiciary-authorities/create.php`
- `modules/judiciaryAuthorities/views/judiciary-authorities/index.php`
- `modules/judiciaryAuthorities/views/judiciary-authorities/update.php`
- `modules/judiciaryAuthorities/views/judiciary-authorities/view.php`

</details>

## الوحدة: `judiciaryCustomersActions`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 5 | 3 | 9 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `judiciary-customers-actions/create-in-contract` | — | إنشاء سجل | `modules/judiciaryCustomersActions/views/judiciary-customers-actions/create-in-contract.php` |
| `judiciary-customers-actions/create` | — | إنشاء سجل | `modules/judiciaryCustomersActions/views/judiciary-customers-actions/create.php` |
| `judiciary-customers-actions/index` | إجراءات العملاء القضائية | قائمة / فهرس | `modules/judiciaryCustomersActions/views/judiciary-customers-actions/index.php` |
| `judiciary-customers-actions/update` | — | تعديل سجل | `modules/judiciaryCustomersActions/views/judiciary-customers-actions/update.php` |
| `judiciary-customers-actions/view` | — | عرض تفاصيل | `modules/judiciaryCustomersActions/views/judiciary-customers-actions/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/judiciaryCustomersActions/views/judiciary-customers-actions/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/judiciaryCustomersActions/views/judiciary-customers-actions/_search.php` |
| `create-in-contract` | ActiveForm داخل الملف | `modules/judiciaryCustomersActions/views/judiciary-customers-actions/create-in-contract.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/judiciaryCustomersActions/views/judiciary-customers-actions/_columns.php`
- `modules/judiciaryCustomersActions/views/judiciary-customers-actions/_form.php`
- `modules/judiciaryCustomersActions/views/judiciary-customers-actions/_search.php`
- `modules/judiciaryCustomersActions/views/judiciary-customers-actions/_select_judiciary.php`
- `modules/judiciaryCustomersActions/views/judiciary-customers-actions/create-in-contract.php`
- `modules/judiciaryCustomersActions/views/judiciary-customers-actions/create.php`
- `modules/judiciaryCustomersActions/views/judiciary-customers-actions/index.php`
- `modules/judiciaryCustomersActions/views/judiciary-customers-actions/update.php`
- `modules/judiciaryCustomersActions/views/judiciary-customers-actions/view.php`

</details>

## الوحدة: `JudiciaryInformAddress`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `judiciary-inform-address/create` | Create Judiciary Inform Address | إنشاء سجل | `modules/JudiciaryInformAddress/views/judiciary-inform-address/create.php` |
| `judiciary-inform-address/index` | Judiciary Inform Addresses | قائمة / فهرس | `modules/JudiciaryInformAddress/views/judiciary-inform-address/index.php` |
| `judiciary-inform-address/update` | — | تعديل سجل | `modules/JudiciaryInformAddress/views/judiciary-inform-address/update.php` |
| `judiciary-inform-address/view` | — | عرض تفاصيل | `modules/JudiciaryInformAddress/views/judiciary-inform-address/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/JudiciaryInformAddress/views/judiciary-inform-address/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/JudiciaryInformAddress/views/judiciary-inform-address/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/JudiciaryInformAddress/views/judiciary-inform-address/_form.php`
- `modules/JudiciaryInformAddress/views/judiciary-inform-address/_search.php`
- `modules/JudiciaryInformAddress/views/judiciary-inform-address/create.php`
- `modules/JudiciaryInformAddress/views/judiciary-inform-address/index.php`
- `modules/JudiciaryInformAddress/views/judiciary-inform-address/update.php`
- `modules/JudiciaryInformAddress/views/judiciary-inform-address/view.php`

</details>

## الوحدة: `judiciaryRequestTemplates`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `judiciary-request-templates/create` | إضافة قالب طلب | إنشاء سجل | `modules/judiciaryRequestTemplates/views/judiciary-request-templates/create.php` |
| `judiciary-request-templates/index` | قوالب الطلبات | قائمة / فهرس | `modules/judiciaryRequestTemplates/views/judiciary-request-templates/index.php` |
| `judiciary-request-templates/update` | تعديل:  | تعديل سجل | `modules/judiciaryRequestTemplates/views/judiciary-request-templates/update.php` |
| `judiciary-request-templates/view` | — | عرض تفاصيل | `modules/judiciaryRequestTemplates/views/judiciary-request-templates/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/judiciaryRequestTemplates/views/judiciary-request-templates/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/judiciaryRequestTemplates/views/judiciary-request-templates/_columns.php`
- `modules/judiciaryRequestTemplates/views/judiciary-request-templates/_form.php`
- `modules/judiciaryRequestTemplates/views/judiciary-request-templates/create.php`
- `modules/judiciaryRequestTemplates/views/judiciary-request-templates/index.php`
- `modules/judiciaryRequestTemplates/views/judiciary-request-templates/update.php`
- `modules/judiciaryRequestTemplates/views/judiciary-request-templates/view.php`

</details>

## الوحدة: `judiciaryType`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 7 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `judiciary-type/create` | Create Judiciary Type | إنشاء سجل | `modules/judiciaryType/views/judiciary-type/create.php` |
| `judiciary-type/index` | Judiciary Types | قائمة / فهرس | `modules/judiciaryType/views/judiciary-type/index.php` |
| `judiciary-type/update` | Update Judiciary Type | تعديل سجل | `modules/judiciaryType/views/judiciary-type/update.php` |
| `judiciary-type/view` | — | عرض تفاصيل | `modules/judiciaryType/views/judiciary-type/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/judiciaryType/views/judiciary-type/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/judiciaryType/views/judiciary-type/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/judiciaryType/views/judiciary-type/_columns.php`
- `modules/judiciaryType/views/judiciary-type/_form.php`
- `modules/judiciaryType/views/judiciary-type/_search.php`
- `modules/judiciaryType/views/judiciary-type/create.php`
- `modules/judiciaryType/views/judiciary-type/index.php`
- `modules/judiciaryType/views/judiciary-type/update.php`
- `modules/judiciaryType/views/judiciary-type/view.php`

</details>

## الوحدة: `lawyers`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 7 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `lawyers/create` | إضافة مفوض / وكيل | إنشاء سجل | `modules/lawyers/views/lawyers/create.php` |
| `lawyers/index` | المفوضين والوكلاء | قائمة / فهرس | `modules/lawyers/views/lawyers/index.php` |
| `lawyers/update` | تعديل:  | تعديل سجل | `modules/lawyers/views/lawyers/update.php` |
| `lawyers/view` | — | عرض تفاصيل | `modules/lawyers/views/lawyers/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/lawyers/views/lawyers/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/lawyers/views/lawyers/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/lawyers/views/lawyers/_columns.php`
- `modules/lawyers/views/lawyers/_form.php`
- `modules/lawyers/views/lawyers/_search.php`
- `modules/lawyers/views/lawyers/create.php`
- `modules/lawyers/views/lawyers/index.php`
- `modules/lawyers/views/lawyers/update.php`
- `modules/lawyers/views/lawyers/view.php`

</details>

## الوحدة: `LawyersImage`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 1 | 0 | 1 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `default/index` | — | قائمة / فهرس | `modules/LawyersImage/views/default/index.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/LawyersImage/views/default/index.php`

</details>

## الوحدة: `leavePolicy`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `leave-policy/create` | — | إنشاء سجل | `modules/leavePolicy/views/leave-policy/create.php` |
| `leave-policy/index` | Leave Policies | قائمة / فهرس | `modules/leavePolicy/views/leave-policy/index.php` |
| `leave-policy/update` | — | تعديل سجل | `modules/leavePolicy/views/leave-policy/update.php` |
| `leave-policy/view` | — | عرض تفاصيل | `modules/leavePolicy/views/leave-policy/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/leavePolicy/views/leave-policy/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/leavePolicy/views/leave-policy/_columns.php`
- `modules/leavePolicy/views/leave-policy/_form.php`
- `modules/leavePolicy/views/leave-policy/create.php`
- `modules/leavePolicy/views/leave-policy/index.php`
- `modules/leavePolicy/views/leave-policy/update.php`
- `modules/leavePolicy/views/leave-policy/view.php`

</details>

## الوحدة: `leaveRequest`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 5 | 1 | 7 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `leave-request/create` | — | إنشاء سجل | `modules/leaveRequest/views/leave-request/create.php` |
| `leave-request/index` | Leave Requests | قائمة / فهرس | `modules/leaveRequest/views/leave-request/index.php` |
| `leave-request/suspended_vacations` | Leave Requests | واجهة عرض | `modules/leaveRequest/views/leave-request/suspended_vacations.php` |
| `leave-request/update` | — | تعديل سجل | `modules/leaveRequest/views/leave-request/update.php` |
| `leave-request/view` | — | عرض تفاصيل | `modules/leaveRequest/views/leave-request/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/leaveRequest/views/leave-request/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/leaveRequest/views/leave-request/_columns.php`
- `modules/leaveRequest/views/leave-request/_form.php`
- `modules/leaveRequest/views/leave-request/create.php`
- `modules/leaveRequest/views/leave-request/index.php`
- `modules/leaveRequest/views/leave-request/suspended_vacations.php`
- `modules/leaveRequest/views/leave-request/update.php`
- `modules/leaveRequest/views/leave-request/view.php`

</details>

## الوحدة: `leaveTypes`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `leave-types/create` | — | إنشاء سجل | `modules/leaveTypes/views/leave-types/create.php` |
| `leave-types/index` | Leave Policies | قائمة / فهرس | `modules/leaveTypes/views/leave-types/index.php` |
| `leave-types/update` | — | تعديل سجل | `modules/leaveTypes/views/leave-types/update.php` |
| `leave-types/view` | — | عرض تفاصيل | `modules/leaveTypes/views/leave-types/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/leaveTypes/views/leave-types/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/leaveTypes/views/leave-types/_columns.php`
- `modules/leaveTypes/views/leave-types/_form.php`
- `modules/leaveTypes/views/leave-types/create.php`
- `modules/leaveTypes/views/leave-types/index.php`
- `modules/leaveTypes/views/leave-types/update.php`
- `modules/leaveTypes/views/leave-types/view.php`

</details>

## الوحدة: `loanScheduling`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 3 | 8 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `loan-scheduling/create` | إنشاء تسوية | إنشاء سجل | `modules/loanScheduling/views/loan-scheduling/create.php` |
| `loan-scheduling/index` | الإدارة المالية | قائمة / فهرس | `modules/loanScheduling/views/loan-scheduling/index.php` |
| `loan-scheduling/update` | — | تعديل سجل | `modules/loanScheduling/views/loan-scheduling/update.php` |
| `loan-scheduling/view` | — | عرض تفاصيل | `modules/loanScheduling/views/loan-scheduling/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form-follow-up` | ActiveForm داخل الملف | `modules/loanScheduling/views/loan-scheduling/_form-follow-up.php` |
| `_form` | ملف _form | `modules/loanScheduling/views/loan-scheduling/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/loanScheduling/views/loan-scheduling/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/loanScheduling/views/loan-scheduling/_columns.php`
- `modules/loanScheduling/views/loan-scheduling/_form-follow-up.php`
- `modules/loanScheduling/views/loan-scheduling/_form.php`
- `modules/loanScheduling/views/loan-scheduling/_search.php`
- `modules/loanScheduling/views/loan-scheduling/create.php`
- `modules/loanScheduling/views/loan-scheduling/index.php`
- `modules/loanScheduling/views/loan-scheduling/update.php`
- `modules/loanScheduling/views/loan-scheduling/view.php`

</details>

## الوحدة: `location`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 7 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `location/create` | — | إنشاء سجل | `modules/location/views/location/create.php` |
| `location/index` | Locations | قائمة / فهرس | `modules/location/views/location/index.php` |
| `location/update` | — | تعديل سجل | `modules/location/views/location/update.php` |
| `location/view` | — | عرض تفاصيل | `modules/location/views/location/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/location/views/location/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/location/views/location/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/location/views/location/_columns.php`
- `modules/location/views/location/_form.php`
- `modules/location/views/location/_search.php`
- `modules/location/views/location/create.php`
- `modules/location/views/location/index.php`
- `modules/location/views/location/update.php`
- `modules/location/views/location/view.php`

</details>

## الوحدة: `movment`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 7 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `movment/create` | Create New Movment | إنشاء سجل | `modules/movment/views/movment/create.php` |
| `movment/index` | Movments | قائمة / فهرس | `modules/movment/views/movment/index.php` |
| `movment/update` | Update Movment | تعديل سجل | `modules/movment/views/movment/update.php` |
| `movment/view` | — | عرض تفاصيل | `modules/movment/views/movment/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/movment/views/movment/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/movment/views/movment/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/movment/views/movment/_columns.php`
- `modules/movment/views/movment/_form.php`
- `modules/movment/views/movment/_search.php`
- `modules/movment/views/movment/create.php`
- `modules/movment/views/movment/index.php`
- `modules/movment/views/movment/update.php`
- `modules/movment/views/movment/view.php`

</details>

## الوحدة: `notification`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 9 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `notification/create` | — | إنشاء سجل | `modules/notification/views/notification/create.php` |
| `notification/index` | Notifications | قائمة / فهرس | `modules/notification/views/notification/index.php` |
| `notification/update` | — | تعديل سجل | `modules/notification/views/notification/update.php` |
| `notification/view` | — | عرض تفاصيل | `modules/notification/views/notification/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/notification/views/notification/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/notification/views/notification/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/notification/views/notification/_all-user-msg.php`
- `modules/notification/views/notification/_columns.php`
- `modules/notification/views/notification/_form.php`
- `modules/notification/views/notification/_search.php`
- `modules/notification/views/notification/_user-columns.php`
- `modules/notification/views/notification/create.php`
- `modules/notification/views/notification/index.php`
- `modules/notification/views/notification/update.php`
- `modules/notification/views/notification/view.php`

</details>

## الوحدة: `officialHolidays`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `official-holidays/create` | إضافة عطلة رسمية | إنشاء سجل | `modules/officialHolidays/views/official-holidays/create.php` |
| `official-holidays/index` | العطل الرسمية | قائمة / فهرس | `modules/officialHolidays/views/official-holidays/index.php` |
| `official-holidays/update` | تعديل:  | تعديل سجل | `modules/officialHolidays/views/official-holidays/update.php` |
| `official-holidays/view` | — | عرض تفاصيل | `modules/officialHolidays/views/official-holidays/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/officialHolidays/views/official-holidays/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/officialHolidays/views/official-holidays/_columns.php`
- `modules/officialHolidays/views/official-holidays/_form.php`
- `modules/officialHolidays/views/official-holidays/create.php`
- `modules/officialHolidays/views/official-holidays/index.php`
- `modules/officialHolidays/views/official-holidays/update.php`
- `modules/officialHolidays/views/official-holidays/view.php`

</details>

## الوحدة: `paymentType`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `payment-type/create` | — | إنشاء سجل | `modules/paymentType/views/payment-type/create.php` |
| `payment-type/index` | Payment Types | قائمة / فهرس | `modules/paymentType/views/payment-type/index.php` |
| `payment-type/update` | — | تعديل سجل | `modules/paymentType/views/payment-type/update.php` |
| `payment-type/view` | — | عرض تفاصيل | `modules/paymentType/views/payment-type/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/paymentType/views/payment-type/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/paymentType/views/payment-type/_columns.php`
- `modules/paymentType/views/payment-type/_form.php`
- `modules/paymentType/views/payment-type/create.php`
- `modules/paymentType/views/payment-type/index.php`
- `modules/paymentType/views/payment-type/update.php`
- `modules/paymentType/views/payment-type/view.php`

</details>

## الوحدة: `phoneNumbers`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `phone-numbers/create` | — | إنشاء سجل | `modules/phoneNumbers/views/phone-numbers/create.php` |
| `phone-numbers/index` | Phone Numbers | قائمة / فهرس | `modules/phoneNumbers/views/phone-numbers/index.php` |
| `phone-numbers/update` | — | تعديل سجل | `modules/phoneNumbers/views/phone-numbers/update.php` |
| `phone-numbers/view` | — | عرض تفاصيل | `modules/phoneNumbers/views/phone-numbers/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/phoneNumbers/views/phone-numbers/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/phoneNumbers/views/phone-numbers/_columns.php`
- `modules/phoneNumbers/views/phone-numbers/_form.php`
- `modules/phoneNumbers/views/phone-numbers/create.php`
- `modules/phoneNumbers/views/phone-numbers/index.php`
- `modules/phoneNumbers/views/phone-numbers/update.php`
- `modules/phoneNumbers/views/phone-numbers/view.php`

</details>

## الوحدة: `profitDistribution`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 4 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `profit-distribution/create-portfolio` | احتساب أرباح محفظة | إنشاء سجل | `modules/profitDistribution/views/profit-distribution/create-portfolio.php` |
| `profit-distribution/create-shareholders` | توزيع أرباح على المساهمين | إنشاء سجل | `modules/profitDistribution/views/profit-distribution/create-shareholders.php` |
| `profit-distribution/index` | توزيع الأرباح | قائمة / فهرس | `modules/profitDistribution/views/profit-distribution/index.php` |
| `profit-distribution/view` | عرض التوزيع # | عرض تفاصيل | `modules/profitDistribution/views/profit-distribution/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `create-portfolio` | ActiveForm داخل الملف | `modules/profitDistribution/views/profit-distribution/create-portfolio.php` |
| `create-shareholders` | ActiveForm داخل الملف | `modules/profitDistribution/views/profit-distribution/create-shareholders.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/profitDistribution/views/profit-distribution/create-portfolio.php`
- `modules/profitDistribution/views/profit-distribution/create-shareholders.php`
- `modules/profitDistribution/views/profit-distribution/index.php`
- `modules/profitDistribution/views/profit-distribution/view.php`

</details>

## الوحدة: `realEstate`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `real-estate/create` | — | إنشاء سجل | `modules/realEstate/views/real-estate/create.php` |
| `real-estate/index` | Real Estates | قائمة / فهرس | `modules/realEstate/views/real-estate/index.php` |
| `real-estate/update` | — | تعديل سجل | `modules/realEstate/views/real-estate/update.php` |
| `real-estate/view` | — | عرض تفاصيل | `modules/realEstate/views/real-estate/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/realEstate/views/real-estate/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/realEstate/views/real-estate/_columns.php`
- `modules/realEstate/views/real-estate/_form.php`
- `modules/realEstate/views/real-estate/create.php`
- `modules/realEstate/views/real-estate/index.php`
- `modules/realEstate/views/real-estate/update.php`
- `modules/realEstate/views/real-estate/view.php`

</details>

## الوحدة: `rejesterFollowUpType`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 7 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `rejester-follow-up-type/create` | — | إنشاء سجل | `modules/rejesterFollowUpType/views/rejester-follow-up-type/create.php` |
| `rejester-follow-up-type/index` | Rejester Follow Up Types | قائمة / فهرس | `modules/rejesterFollowUpType/views/rejester-follow-up-type/index.php` |
| `rejester-follow-up-type/update` | — | تعديل سجل | `modules/rejesterFollowUpType/views/rejester-follow-up-type/update.php` |
| `rejester-follow-up-type/view` | — | عرض تفاصيل | `modules/rejesterFollowUpType/views/rejester-follow-up-type/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/rejesterFollowUpType/views/rejester-follow-up-type/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/rejesterFollowUpType/views/rejester-follow-up-type/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/rejesterFollowUpType/views/rejester-follow-up-type/_columns.php`
- `modules/rejesterFollowUpType/views/rejester-follow-up-type/_form.php`
- `modules/rejesterFollowUpType/views/rejester-follow-up-type/_search.php`
- `modules/rejesterFollowUpType/views/rejester-follow-up-type/create.php`
- `modules/rejesterFollowUpType/views/rejester-follow-up-type/index.php`
- `modules/rejesterFollowUpType/views/rejester-follow-up-type/update.php`
- `modules/rejesterFollowUpType/views/rejester-follow-up-type/view.php`

</details>

## الوحدة: `reports`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 20 | 11 | 31 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `customers-judiciary-actions-report/index` | الحركات القضائية للعملاء | قائمة / فهرس | `modules/reports/views/customers-judiciary-actions-report/index.php` |
| `customers-judiciary-actions-report/view` | — | عرض تفاصيل | `modules/reports/views/customers-judiciary-actions-report/view.php` |
| `default/index` | — | قائمة / فهرس | `modules/reports/views/default/index.php` |
| `due_installment` | monthly Income | واجهة عرض | `modules/reports/views/due_installment.php` |
| `follow-up-reports/index` | تقارير المتابعة | قائمة / فهرس | `modules/reports/views/follow-up-reports/index.php` |
| `follow-up-reports/view` | — | عرض تفاصيل | `modules/reports/views/follow-up-reports/view.php` |
| `income-reports/index` | Income Reports | قائمة / فهرس | `modules/reports/views/income-reports/index.php` |
| `income-reports/TotalCustomerPaymentsIndex` | تقرير الإيرادات | قائمة / فهرس | `modules/reports/views/income-reports/TotalCustomerPaymentsIndex.php` |
| `income-reports/TotalJudiciaryCustomerPaymentsIndex` | إيرادات القضايا | قائمة / فهرس | `modules/reports/views/income-reports/TotalJudiciaryCustomerPaymentsIndex.php` |
| `income-reports/view` | — | عرض تفاصيل | `modules/reports/views/income-reports/view.php` |
| `index` | التقارير | قائمة / فهرس | `modules/reports/views/index.php` |
| `judiciary/create` | Create Judiciary | إنشاء سجل | `modules/reports/views/judiciary/create.php` |
| `judiciary/index` | التقارير القضائية | قائمة / فهرس | `modules/reports/views/judiciary/index.php` |
| `judiciary/report` | Judiciaries | واجهة عرض | `modules/reports/views/judiciary/report.php` |
| `judiciary/update` | Update Judiciary | تعديل سجل | `modules/reports/views/judiciary/update.php` |
| `judiciary/view` | — | عرض تفاصيل | `modules/reports/views/judiciary/view.php` |
| `monthly_installment` | monthly Income | واجهة عرض | `modules/reports/views/monthly_installment.php` |
| `monthly_installment_monthly_beer_user` | monthly Income beer user | واجهة عرض | `modules/reports/views/monthly_installment_monthly_beer_user.php` |
| `reports/index` | التقارير | قائمة / فهرس | `modules/reports/views/reports/index.php` |
| `this_month_installments` | monthly Income | واجهة عرض | `modules/reports/views/this_month_installments.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_search` | ActiveForm داخل الملف | `modules/reports/views/customers-judiciary-actions-report/_search.php` |
| `index` | ActiveForm داخل الملف | `modules/reports/views/customers-judiciary-actions-report/index.php` |
| `_search` | ActiveForm داخل الملف | `modules/reports/views/follow-up-reports/_search.php` |
| `index` | ActiveForm داخل الملف | `modules/reports/views/follow-up-reports/index.php` |
| `_custamer-judiciary-search` | ActiveForm داخل الملف | `modules/reports/views/income-reports/_custamer-judiciary-search.php` |
| `_custamer-search` | ActiveForm داخل الملف | `modules/reports/views/income-reports/_custamer-search.php` |
| `TotalCustomerPaymentsIndex` | ActiveForm داخل الملف | `modules/reports/views/income-reports/TotalCustomerPaymentsIndex.php` |
| `TotalJudiciaryCustomerPaymentsIndex` | ActiveForm داخل الملف | `modules/reports/views/income-reports/TotalJudiciaryCustomerPaymentsIndex.php` |
| `_form` | ملف _form | `modules/reports/views/judiciary/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/reports/views/judiciary/_search.php` |
| `index` | ActiveForm داخل الملف | `modules/reports/views/judiciary/index.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/reports/views/customers-judiciary-actions-report/_columns.php`
- `modules/reports/views/customers-judiciary-actions-report/_search.php`
- `modules/reports/views/customers-judiciary-actions-report/index.php`
- `modules/reports/views/customers-judiciary-actions-report/view.php`
- `modules/reports/views/default/index.php`
- `modules/reports/views/due_installment.php`
- `modules/reports/views/follow-up-reports/_columns.php`
- `modules/reports/views/follow-up-reports/_search.php`
- `modules/reports/views/follow-up-reports/index.php`
- `modules/reports/views/follow-up-reports/view.php`
- `modules/reports/views/income-reports/_columns.php`
- `modules/reports/views/income-reports/_custamer-judiciary-search.php`
- `modules/reports/views/income-reports/_custamer-search.php`
- `modules/reports/views/income-reports/index.php`
- `modules/reports/views/income-reports/TotalCustomerPaymentsIndex.php`
- `modules/reports/views/income-reports/TotalJudiciaryCustomerPaymentsIndex.php`
- `modules/reports/views/income-reports/view.php`
- `modules/reports/views/index.php`
- `modules/reports/views/judiciary/_columns.php`
- `modules/reports/views/judiciary/_form.php`
- `modules/reports/views/judiciary/_report_columns.php`
- `modules/reports/views/judiciary/_search.php`
- `modules/reports/views/judiciary/create.php`
- `modules/reports/views/judiciary/index.php`
- `modules/reports/views/judiciary/report.php`
- `modules/reports/views/judiciary/update.php`
- `modules/reports/views/judiciary/view.php`
- `modules/reports/views/monthly_installment.php`
- `modules/reports/views/monthly_installment_monthly_beer_user.php`
- `modules/reports/views/reports/index.php`
- `modules/reports/views/this_month_installments.php`

</details>

## الوحدة: `sharedExpenses`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 5 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `shared-expense/create` | إنشاء توزيع جديد | إنشاء سجل | `modules/sharedExpenses/views/shared-expense/create.php` |
| `shared-expense/index` | توزيع المصاريف المشتركة | قائمة / فهرس | `modules/sharedExpenses/views/shared-expense/index.php` |
| `shared-expense/update` | تعديل التوزيع:  | تعديل سجل | `modules/sharedExpenses/views/shared-expense/update.php` |
| `shared-expense/view` | عرض التوزيع:  | عرض تفاصيل | `modules/sharedExpenses/views/shared-expense/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/sharedExpenses/views/shared-expense/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/sharedExpenses/views/shared-expense/_form.php`
- `modules/sharedExpenses/views/shared-expense/create.php`
- `modules/sharedExpenses/views/shared-expense/index.php`
- `modules/sharedExpenses/views/shared-expense/update.php`
- `modules/sharedExpenses/views/shared-expense/view.php`

</details>

## الوحدة: `shareholders`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `shareholders/create` | إضافة مساهم جديد | إنشاء سجل | `modules/shareholders/views/shareholders/create.php` |
| `shareholders/index` | المساهمين | قائمة / فهرس | `modules/shareholders/views/shareholders/index.php` |
| `shareholders/update` | تعديل بيانات مساهم:  | تعديل سجل | `modules/shareholders/views/shareholders/update.php` |
| `shareholders/view` | عرض المساهم:  | عرض تفاصيل | `modules/shareholders/views/shareholders/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/shareholders/views/shareholders/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/shareholders/views/shareholders/_form.php`
- `modules/shareholders/views/shareholders/_search.php`
- `modules/shareholders/views/shareholders/create.php`
- `modules/shareholders/views/shareholders/index.php`
- `modules/shareholders/views/shareholders/update.php`
- `modules/shareholders/views/shareholders/view.php`

</details>

## الوحدة: `shares`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `shares/create` | Create Shares | إنشاء سجل | `modules/shares/views/shares/create.php` |
| `shares/index` | Shares | قائمة / فهرس | `modules/shares/views/shares/index.php` |
| `shares/update` | Update {modelClass}:  | تعديل سجل | `modules/shares/views/shares/update.php` |
| `shares/view` | — | عرض تفاصيل | `modules/shares/views/shares/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/shares/views/shares/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/shares/views/shares/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/shares/views/shares/_form.php`
- `modules/shares/views/shares/_search.php`
- `modules/shares/views/shares/create.php`
- `modules/shares/views/shares/index.php`
- `modules/shares/views/shares/update.php`
- `modules/shares/views/shares/view.php`

</details>

## الوحدة: `sms`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 2 | 8 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `sms/create` | — | إنشاء سجل | `modules/sms/views/sms/create.php` |
| `sms/index` | Sms | قائمة / فهرس | `modules/sms/views/sms/index.php` |
| `sms/update` | — | تعديل سجل | `modules/sms/views/sms/update.php` |
| `sms/view` | — | عرض تفاصيل | `modules/sms/views/sms/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/sms/views/sms/_form.php` |
| `_search` | ActiveForm داخل الملف | `modules/sms/views/sms/_search.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/sms/views/sms/_columns.php`
- `modules/sms/views/sms/_form.php`
- `modules/sms/views/sms/_pop_up.php`
- `modules/sms/views/sms/_search.php`
- `modules/sms/views/sms/create.php`
- `modules/sms/views/sms/index.php`
- `modules/sms/views/sms/update.php`
- `modules/sms/views/sms/view.php`

</details>

## الوحدة: `status`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `status/create` | — | إنشاء سجل | `modules/status/views/status/create.php` |
| `status/index` | Statuses | قائمة / فهرس | `modules/status/views/status/index.php` |
| `status/update` | — | تعديل سجل | `modules/status/views/status/update.php` |
| `status/view` | — | عرض تفاصيل | `modules/status/views/status/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/status/views/status/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/status/views/status/_columns.php`
- `modules/status/views/status/_form.php`
- `modules/status/views/status/create.php`
- `modules/status/views/status/index.php`
- `modules/status/views/status/update.php`
- `modules/status/views/status/view.php`

</details>

## الوحدة: `workdays`

### ملخص الوحدة

| الشاشات | الفورمز | الـ Views |
|--------:|--------:|----------:|
| 4 | 1 | 6 |

### الشاشات

| اسم الشاشة في الكود | اسم الشاشة بالعربي | الوظيفة | ملف الـ View |
|---------------------|------------------|---------|--------------|
| `workdays/create` | — | إنشاء سجل | `modules/workdays/views/workdays/create.php` |
| `workdays/index` | Workdays | قائمة / فهرس | `modules/workdays/views/workdays/index.php` |
| `workdays/update` | — | تعديل سجل | `modules/workdays/views/workdays/update.php` |
| `workdays/view` | — | عرض تفاصيل | `modules/workdays/views/workdays/view.php` |

### الفورمز

| اسم الفورم | النوع | ملف الفورم |
|------------|-------|------------|
| `_form` | ملف _form | `modules/workdays/views/workdays/_form.php` |

<details>
<summary><strong>جميع مسارات الـ View لهذه الوحدة</strong></summary>

- `modules/workdays/views/workdays/_columns.php`
- `modules/workdays/views/workdays/_form.php`
- `modules/workdays/views/workdays/create.php`
- `modules/workdays/views/workdays/index.php`
- `modules/workdays/views/workdays/update.php`
- `modules/workdays/views/workdays/view.php`

</details>

---

# خطة تحسين الأداء عبر Database Views — خطة تنفيذ مرحلية

**تاريخ التوليد:** 2026-03-26

---

## الوضع الحالي: Views موجودة فعلاً في الكود

| # | اسم الـ View | مكان الإنشاء | طريقة الإنشاء | التقييم |
|---|---|---|---|---|
| 1 | `os_follow_up_report` | `FollowUpReportController.php` سطر 607 | `CREATE OR REPLACE VIEW` **أثناء كل طلب صفحة** (actionIndex + export) | يُعاد إنشاؤه عند كل فتح للشاشة — **مشكلة أداء يجب إصلاحها** |
| 2 | `os_follow_up_no_contact` | `FollowUpReportController.php` سطر 729 | `CREATE OR REPLACE VIEW` **أثناء كل طلب صفحة** (actionNoContact + export) | نفس المشكلة: إعادة إنشاء الـ View عند كل request |
| 3 | `v_deadline_live` | `JudiciaryDeadlineService.php` سطر 402 | `CREATE OR REPLACE VIEW` مع cache يومي (86400 ثانية) | **أفضل** من السابقتين لأنه يستخدم cache، لكن ما زال يُنشأ من الكود |
| 4 | `tbl_persistence_cache` | `JudiciaryController.php` (عدة أماكن) | جدول cache حقيقي وليس View | تصميم جيد كـ materialized cache |

---

## توصيات التحسين / الإلغاء / الإضافة

### Views حالية تحتاج تحسين

| الـ View | المشكلة | التوصية |
|---|---|---|
| `os_follow_up_report` | يُعاد إنشاؤه (`CREATE OR REPLACE`) **5 مرات** في الكنترولر (index, export Excel, export PDF...) = ~100ms ضائعة كل طلب | **نقله لـ migration ثابت** وحذف `createFollowUpReportView()` من الكنترولر بالكامل |
| `os_follow_up_no_contact` | نفس المشكلة: 3 استدعاءات = إعادة إنشاء عند كل طلب | **نقله لـ migration ثابت** وحذف `createNoContactView()` من الكنترولر |
| `v_deadline_live` | تصميم أفضل (cache يومي)، لكن الـ View نفسه معقد جداً (subqueries متداخلة + CASE كبير) | **إبقاؤه** مع تبسيط: فصل حساب `remaining` في View مساعد (`vw_contract_balance`) يُستخدم هنا وفي أماكن أخرى |

### Views جديدة مطلوبة

| # | اسم الـ View المقترح | يخدم أي شاشات | الفائدة |
|---|---|---|---|
| A | `vw_contract_balance` | العقود، المتابعة، القضائي، التقارير المالية | **View أساسي مشترك** يحسب لكل عقد: المدفوع، المصاريف، أتعاب المحامي، التسويات، المتبقي — يُستخدم في أكثر من 10 شاشات |
| B | `vw_contract_customers_names` | العقود، القضائي، التقارير، التصدير | يجمع أسماء أطراف العقد بـ `GROUP_CONCAT` — نمط متكرر في 8+ أماكن بالكود |
| C | `vw_contracts_overview` | قائمة العقود + التصدير | بيانات العقد + البائع + المتابع + أسماء العملاء + الرصيد في View واحد مسطح |
| D | `vw_judiciary_cases_overview` | قائمة القضايا + التصدير + التقارير | القضية + المحكمة + النوع + المحامي + أسماء الأطراف + آخر إجراء |
| E | `vw_judiciary_actions_feed` | إجراءات العملاء القضائية + التصدير | الإجراء + القضية + العميل + المحكمة + المحامي + المنشئ |
| F | `vw_customers_directory` | قائمة العملاء | العميل + الوظيفة + نوع الوظيفة + رقم الهاتف في View مسطح |
| G | `vw_income_contract_summary` | تقارير المدفوعات (عادي + قضائي) | مجاميع الدفعات لكل عقد مع بيانات العقد الأساسية |
| H | `vw_hr_attendance_daily_summary` | لوحة الحضور + التقرير اليومي | ملخص يومي: حاضر/غائب/متأخر/إجازة لكل يوم |
| I | `vw_hr_attendance_employee_monthly` | التقرير الشهري + الالتزام + المخالفات | ملخص شهري لكل موظف: أيام حضور/غياب/تأخر/ساعات عمل |
| J | `vw_hr_employee_directory` | قائمة الموظفين | الموظف + القسم + المسمى + الكود + النوع في View مسطح |
| K | `vw_inventory_item_balance` | استعلام الأصناف + لوحة المخزون | الصنف + الكمية المتاحة - المباعة = المتبقي |
| L | `vw_payroll_employee_attendance` | إنشاء مسير الرواتب | ملخص حضور الموظف للفترة: أيام عمل/غياب/تأخر/إضافي — يُحسب حالياً داخل الكنترولر |

### Views مقترحة سابقاً يُوصى بإلغاء الحاجة لها

| الـ View | السبب | البديل |
|---|---|---|
| `vw_followup_report_base` | أصبح مغطى بتثبيت `os_follow_up_report` كـ migration + استخدام `vw_contract_balance` | استخدام `os_follow_up_report` الثابت مباشرة |
| `vw_followup_no_contact_base` | نفس السبب | استخدام `os_follow_up_no_contact` الثابت مباشرة |
| `vw_ar_aging` / `vw_ap_aging` | وحدة المحاسبة جديدة وبياناتها قليلة حالياً | تأجيل لحين نمو البيانات — لا حاجة فورية |

---

## خطة التنفيذ المرحلية

### Phase 1 — الأساسيات المشتركة (أعلى عائد / أقل مخاطرة)

> **الهدف:** بناء Views "لبنات أساسية" تُستخدم في عدة شاشات، وإصلاح الـ Views الحالية المكسورة.
> **المدة المقدرة:** 2-3 أيام عمل

| الترتيب | المهمة | المخاطرة | الأثر |
|---|---|---|---|
| 1.1 | إنشاء `vw_contract_balance` — View أساسي يحسب الأرصدة المالية لكل عقد | منخفضة جداً (View قراءة فقط) | **عالي جداً**: يُزيل حسابات SUM المتكررة من 10+ شاشة |
| 1.2 | إنشاء `vw_contract_customers_names` — أسماء أطراف العقد | منخفضة جداً | **عالي**: يُزيل GROUP_CONCAT المتكرر من 8+ شاشة |
| 1.3 | **تثبيت** `os_follow_up_report` — نقل SQL الموجود من الكنترولر إلى migration | منخفضة (الـ SQL موجود ومُختبر) | **عالي**: يُزيل ~100ms من كل طلب صفحة متابعة |
| 1.4 | **تثبيت** `os_follow_up_no_contact` — نفس العملية | منخفضة | **متوسط**: نفس التحسين لشاشة أقل استخداماً |
| 1.5 | حذف `createFollowUpReportView()` و `createNoContactView()` من الكنترولر | منخفضة (بعد تنفيذ 1.3 و 1.4) | تنظيف كود |

**نتيجة Phase 1:** كل الشاشات التي تحسب أرصدة/أسماء عملاء ستكون أسرع، وشاشة المتابعة تتوقف عن إعادة إنشاء View كل مرة.

---

### Phase 2 — الشاشات الأكثر استخداماً (أعلى traffic)

> **الهدف:** تسريع الشاشات اليومية الأكثر فتحاً: العقود، القضايا، العملاء، الإجراءات.
> **المدة المقدرة:** 3-4 أيام عمل
> **يعتمد على:** إتمام Phase 1 (يستخدم `vw_contract_balance` و `vw_contract_customers_names`)

| الترتيب | المهمة | المخاطرة | الأثر |
|---|---|---|---|
| 2.1 | إنشاء `vw_contracts_overview` + ربط `ContractsSearch` به | متوسطة (تعديل Search model) | **عالي**: شاشة العقود من أكثر الشاشات استخداماً |
| 2.2 | إنشاء `vw_judiciary_cases_overview` + ربط `JudiciarySearch` به | متوسطة | **عالي**: شاشة القضايا ثقيلة جداً بسبب subqueries |
| 2.3 | إنشاء `vw_judiciary_actions_feed` + ربط `JudiciaryCustomersActionsSearch` به | متوسطة | **عالي**: شاشة الإجراءات تربط 7 جداول |
| 2.4 | إنشاء `vw_customers_directory` + ربط `CustomersSearch` به | منخفضة-متوسطة | **متوسط**: تبسيط joins الوظائف |
| 2.5 | تحسين `v_deadline_live` — استخدام `vw_contract_balance` بدل الحساب المكرر داخله | منخفضة | **متوسط**: تبسيط الـ View وتوحيد منطق الحساب |

**نتيجة Phase 2:** الشاشات الأربع الأكثر استخداماً يومياً تصبح أسرع بشكل ملموس.

---

### Phase 3 — التقارير والموارد البشرية

> **الهدف:** تسريع التقارير المالية وتقارير الحضور التي تعتمد تجميعات ثقيلة.
> **المدة المقدرة:** 3-4 أيام عمل
> **يعتمد على:** Phase 1 فقط

| الترتيب | المهمة | المخاطرة | الأثر |
|---|---|---|---|
| 3.1 | إنشاء `vw_income_contract_summary` + ربط `IncomeSearch` و تقارير المدفوعات به | منخفضة | **متوسط-عالي**: تقرير المدفوعات بطيء حالياً |
| 3.2 | إنشاء `vw_hr_attendance_daily_summary` + ربط لوحة الحضور به | منخفضة | **متوسط**: يقلل group by المتكرر |
| 3.3 | إنشاء `vw_hr_attendance_employee_monthly` + ربط التقرير الشهري + الالتزام + المخالفات | منخفضة | **متوسط**: 3 شاشات تستفيد من View واحد |
| 3.4 | إنشاء `vw_hr_employee_directory` + ربط قائمة الموظفين | منخفضة | **منخفض-متوسط**: تبسيط joins القسم/المسمى |
| 3.5 | إنشاء `vw_payroll_employee_attendance` + ربط إعداد مسير الرواتب | منخفضة | **متوسط**: حساب الحضور للراتب حالياً يتم داخل الكنترولر |

**نتيجة Phase 3:** كل التقارير والموارد البشرية تعمل على Views جاهزة بدل حسابات مباشرة.

---

### Phase 4 — المخزون والديوان (حسب الحاجة)

> **الهدف:** تحسينات مشروطة بحجم البيانات — تُنفذ فقط إذا ظهر بطء فعلي.
> **المدة المقدرة:** 1-2 يوم عمل

| الترتيب | المهمة | المخاطرة | الأثر |
|---|---|---|---|
| 4.1 | إنشاء `vw_inventory_item_balance` | منخفضة | مشروط بحجم المخزون |
| 4.2 | إنشاء `vw_diwan_transaction_search` | منخفضة | مشروط بحجم حركات الديوان |

---

## ملخص الـ Views النهائي المعتمد

| # | اسم الـ View | الفئة | Phase | الحالة |
|---|---|---|---|---|
| 1 | `vw_contract_balance` | لبنة أساسية | 1 | **جديد** |
| 2 | `vw_contract_customers_names` | لبنة أساسية | 1 | **جديد** |
| 3 | `os_follow_up_report` | متابعة | 1 | **تحسين** (نقل لـ migration) |
| 4 | `os_follow_up_no_contact` | متابعة | 1 | **تحسين** (نقل لـ migration) |
| 5 | `vw_contracts_overview` | عقود | 2 | **جديد** |
| 6 | `vw_judiciary_cases_overview` | قضائي | 2 | **جديد** |
| 7 | `vw_judiciary_actions_feed` | قضائي | 2 | **جديد** |
| 8 | `vw_customers_directory` | عملاء | 2 | **جديد** |
| 9 | `v_deadline_live` | قضائي | 2 | **تحسين** (استخدام vw_contract_balance) |
| 10 | `vw_income_contract_summary` | تقارير | 3 | **جديد** |
| 11 | `vw_hr_attendance_daily_summary` | موارد بشرية | 3 | **جديد** |
| 12 | `vw_hr_attendance_employee_monthly` | موارد بشرية | 3 | **جديد** |
| 13 | `vw_hr_employee_directory` | موارد بشرية | 3 | **جديد** |
| 14 | `vw_payroll_employee_attendance` | موارد بشرية | 3 | **جديد** |
| 15 | `vw_inventory_item_balance` | مخزون | 4 | **جديد (مشروط)** |
| 16 | `vw_diwan_transaction_search` | ديوان | 4 | **جديد (مشروط)** |
| — | `vw_followup_report_base` | — | — | **ملغى** (مغطى بتثبيت os_follow_up_report) |
| — | `vw_followup_no_contact_base` | — | — | **ملغى** (مغطى بتثبيت os_follow_up_no_contact) |
| — | `vw_ar_aging` / `vw_ap_aging` | — | — | **مؤجل** (بيانات المحاسبة قليلة حالياً) |

---

## قواعد عامة للتنفيذ

1. **كل View يُنشأ كـ Yii2 migration** (`m_xxxxxx_create_view_name`) وليس داخل الكنترولر.
2. **لا تعديل على الـ View بدون migration جديد** — لضمان التتبع والـ rollback.
3. **كل View يُختبر أولاً بـ `EXPLAIN`** على بيانات الإنتاج للتأكد من استخدام الـ indexes.
4. **يُنصح بإنشاء index مركب** على الأعمدة المستخدمة في JOIN/WHERE قبل إنشاء الـ View.
5. **الـ Views لا تحل محل الـ indexes** — هي تبسّط الاستعلام، والـ index هو الذي يسرّعه فعلاً.
