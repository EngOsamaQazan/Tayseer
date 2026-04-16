<div align="center">

# نظام تيسير لإدارة شركات التقسيط

### Tayseer ERP — Enterprise Installment Management System

<br>

![Yii2](https://img.shields.io/badge/Yii2-2.0.54+-009688?style=for-the-badge&logo=data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMjggMTI4Ij48cGF0aCBmaWxsPSIjZmZmIiBkPSJNNjQgMTBMMTAgNjRsNTQgNTQgNTQtNTRMNjQgMTBaIi8+PC9zdmc+)
![PHP](https://img.shields.io/badge/PHP-8.5+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![React](https://img.shields.io/badge/React-18-61DAFB?style=for-the-badge&logo=react&logoColor=black)
![Node.js](https://img.shields.io/badge/Node.js-18+-339933?style=for-the-badge&logo=node.js&logoColor=white)
![License](https://img.shields.io/badge/License-BSD-green?style=for-the-badge)

<br>

**منصة متكاملة لإدارة عمليات شركات التقسيط والتمويل**
**تغطي دورة حياة العقد بالكامل — من تسجيل العميل حتى التحصيل والقضاء**

<br>

[الميزات](#-الميزات-الرئيسية) · [البنية](#-البنية-التقنية) · [الأقسام](#-أقسام-النظام) · [التثبيت](#-التثبيت-والتشغيل) · [API](#-واجهة-برمجة-التطبيقات-rest-api) · [النشر](#-النشر-والتوزيع)

</div>

---

## 📊 النظام بالأرقام

<div align="center">

| المقياس | العدد |
|:-------:|:-----:|
| **ملفات PHP** | **1,932** |
| **ملفات JavaScript** | **114** |
| **ملفات CSS** | **130** |
| **وحدات النظام (Modules)** | **75+** |
| **Controllers** | **113** |
| **Models** | **240** |
| **واجهات العرض (Views)** | **737** |
| **جداول قاعدة البيانات** | **180+** |
| **ملفات الترحيل (Migrations)** | **242** |
| **شاشات النظام** | **479** |
| **نماذج إدخال** | **163** |

</div>

---

## 🏗 البنية التقنية

```
نظام تيسير مبني على معمارية متعددة الطبقات (Multi-Tier Architecture)
يعمل على إطار Yii2 Advanced مع فصل كامل بين الطبقات
```

### مخطط البنية العام

```mermaid
graph TB
    subgraph CLIENT["🖥️ طبقة العرض — Frontend Layer"]
        ADMIN["لوحة التحكم<br/>Vuexy + Bootstrap 5 + Kartik"]
        LANDING["الموقع التسويقي<br/>HTML/CSS/JS"]
        JUD_V3["تطبيق القضاء v3<br/>React 18 + Vite + Tailwind"]
        PWA["تطبيق PWA<br/>Service Worker + Manifest"]
    end

    subgraph SERVER["⚙️ طبقة الأعمال — Business Layer"]
        BACKEND["Backend<br/>75+ وحدة Yii2"]
        API["REST API v1<br/>JSON Endpoints"]
        CONSOLE["Console<br/>أوامر CLI + Cron"]
        COMMON["Common<br/>نماذج مشتركة + خدمات"]
    end

    subgraph DATA["🗄️ طبقة البيانات — Data Layer"]
        DB["MySQL 8.0<br/>180+ جدول"]
        CACHE["FileCache<br/>تخزين مؤقت"]
        QUEUE["Queue<br/>AMQP + yii2-queue"]
    end

    subgraph INTEGRATIONS["🔌 التكاملات الخارجية"]
        SMS["SMS Gateway<br/>Taqnyat · Unifonic · Twilio"]
        FCM["Push Notifications<br/>Firebase FCM"]
        MAPS["خرائط<br/>Google Maps API"]
    end

    CLIENT --> SERVER
    SERVER --> DATA
    SERVER --> INTEGRATIONS

    style CLIENT fill:#800020,color:#fff,stroke:#5a0016
    style SERVER fill:#1a1a2e,color:#fff,stroke:#16213e
    style DATA fill:#0f3460,color:#fff,stroke:#0a2647
    style INTEGRATIONS fill:#533483,color:#fff,stroke:#3c1874
```

### الحزمة التقنية الكاملة

```mermaid
graph LR
    subgraph BACKEND_STACK["الواجهة الخلفية"]
        PHP["PHP 8.5+"]
        YII["Yii 2.0.54"]
        RBAC["RBAC<br/>mdm/admin"]
        AUTH["المصادقة<br/>dektrium/user"]
    end

    subgraph FRONTEND_STACK["الواجهة الأمامية"]
        BS5["Bootstrap 5"]
        VUEXY["Vuexy Theme"]
        KARTIK["Kartik Widgets"]
        APEX["ApexCharts"]
        VITE["Vite + Tailwind"]
    end

    subgraph TOOLS["الأدوات والمكتبات"]
        MPDF["mPDF<br/>تقارير PDF"]
        EXCEL["PhpSpreadsheet<br/>تقارير Excel"]
        QR["QR Code<br/>chillerlan"]
        SOFT["Soft Delete<br/>yii2tech"]
    end

    subgraph INFRA["البنية التحتية"]
        GITHUB["GitHub Actions<br/>CI/CD"]
        APACHE["Apache + PHP-FPM"]
        DOCKER["Docker<br/>تطوير محلي"]
    end

    style BACKEND_STACK fill:#2d3436,color:#fff
    style FRONTEND_STACK fill:#800020,color:#fff
    style TOOLS fill:#0c7b93,color:#fff
    style INFRA fill:#27496d,color:#fff
```

---

## 🎯 الميزات الرئيسية

### إدارة العملاء والعقود
- ✅ تسجيل ذكي للعملاء مع نموذج متعدد الخطوات (Wizard)
- ✅ التحقق التلقائي من التكرار والمخاطر
- ✅ رفع المستندات والصور مع معالجة OCR
- ✅ إدارة كاملة لعقود التقسيط بجميع حالاتها
- ✅ جدولة الأقساط التلقائية مع نظام مرن للسداد
- ✅ ربط المنتجات والأرقام التسلسلية بالعقود

### المتابعة والتحصيل
- ✅ لوحة متابعة شاملة لكل عقد (Panel)
- ✅ نظام SMS متعدد المزودين (Taqnyat, Unifonic, Twilio, Vonage)
- ✅ كشوف حساب تفاعلية مع التحقق
- ✅ مسودات رسائل SMS مع قوالب ذكية
- ✅ تقارير متابعة بدون تواصل
- ✅ ذكاء اصطناعي مساعد للمتابعة (AI Feedback)

### القسم القانوني والقضاء
- ✅ إدارة كاملة للقضايا القانونية
- ✅ نظام مواعيد قضائية مع تنبيهات تلقائية
- ✅ إدارة المحامين والمحاكم والجهات
- ✅ قوالب طلبات قانونية
- ✅ عمليات دفعية (Batch) لإنشاء وتنفيذ القضايا
- ✅ أصول محجوزة وتتبع التكاليف
- ✅ **تطبيق قضاء مستقل (v3)** — React + Node.js

### المحاسبة والإدارة المالية
- ✅ شجرة حسابات كاملة (Chart of Accounts)
- ✅ قيود يومية تلقائية ويدوية
- ✅ الأستاذ العام (General Ledger)
- ✅ ذمم مدينة ودائنة (AR/AP)
- ✅ موازنات وسنوات مالية
- ✅ مراكز تكلفة
- ✅ تقارير مالية شاملة (ميزان مراجعة، قائمة دخل، ميزانية)
- ✅ تحليل ذكي بالذكاء الاصطناعي (AI Insights)
- ✅ نظام صناديق نقدية موحد

### الموارد البشرية
- ✅ سجل موظفين شامل مع ملفات رقمية
- ✅ نظام حضور وانصراف مع تتبع GPS
- ✅ مناطق عمل جغرافية (Geofencing)
- ✅ إدارة رواتب ومسيّرات
- ✅ نظام إجازات كامل مع سياسات متعددة
- ✅ تقييمات أداء دورية
- ✅ سلف وقروض موظفين
- ✅ ورديات عمل مرنة
- ✅ لوحة تحكم HR متكاملة

### إدارة المخزون
- ✅ كتالوج أصناف مع أرقام تسلسلية
- ✅ مواقع تخزين متعددة
- ✅ فواتير توريد مع معالج خطوات (Wizard)
- ✅ تتبع حركة المخزون ورصيد كل صنف
- ✅ إدارة موردين
- ✅ ربط المخزون بالعقود

### إدارة الاستثمار
- ✅ إدارة المساهمين ورأس المال
- ✅ حركات رأس المال (إيداع / سحب)
- ✅ توزيع أرباح تلقائي
- ✅ مصاريف مشتركة وتخصيصها
- ✅ سجل أسهم

### الديوان والمراسلات
- ✅ إدارة المعاملات الرسمية
- ✅ تتبع المراسلات الواردة والصادرة
- ✅ أرشفة إلكترونية

---

## 📦 أقسام النظام

### المخطط الهيكلي للوحدات

```mermaid
graph TD
    TAYSEER["🏛️ نظام تيسير"]

    subgraph CORE["العمليات الأساسية"]
        CUSTOMERS["👥 العملاء<br/>9,394 عميل"]
        CONTRACTS["📄 العقود<br/>7,405 عقد"]
        FOLLOWUP["📞 المتابعة<br/>SMS + AI"]
        INCOME["💰 الدخل<br/>إيصالات + تحصيل"]
    end

    subgraph LEGAL["القسم القانوني"]
        JUDICIARY["⚖️ القضايا<br/>5,776 قضية"]
        LAWYERS["👨‍⚖️ المحامين"]
        COURTS["🏛️ المحاكم"]
        DEADLINES["⏰ المواعيد القضائية"]
    end

    subgraph FINANCE["الإدارة المالية"]
        ACCOUNTING["📊 المحاسبة<br/>11 Controller"]
        EXPENSES["📤 المصاريف"]
        FIN_TRANS["🔄 الحركات المالية"]
        INVESTMENT["💎 الاستثمار"]
    end

    subgraph HR_SECTION["الموارد البشرية"]
        HR["👔 HR Suite<br/>14 Controller"]
        ATTENDANCE["📍 الحضور + GPS"]
        PAYROLL["💵 الرواتب"]
        LEAVES["🏖️ الإجازات"]
    end

    subgraph INVENTORY_SECTION["المخزون"]
        ITEMS["📦 الأصناف"]
        STOCK["🏪 المواقع"]
        SUPPLIERS["🚚 الموردون"]
        INVOICES_INV["🧾 فواتير التوريد"]
    end

    subgraph SYSTEM["إدارة النظام"]
        RBAC_SYS["🔐 الصلاحيات<br/>RBAC"]
        USERS["👤 المستخدمون"]
        SETTINGS["⚙️ الإعدادات"]
        NOTIFICATIONS["🔔 الإشعارات"]
    end

    TAYSEER --> CORE
    TAYSEER --> LEGAL
    TAYSEER --> FINANCE
    TAYSEER --> HR_SECTION
    TAYSEER --> INVENTORY_SECTION
    TAYSEER --> SYSTEM

    style TAYSEER fill:#800020,color:#fff,stroke:#5a0016,stroke-width:3px
    style CORE fill:#28a745,color:#fff
    style LEGAL fill:#dc3545,color:#fff
    style FINANCE fill:#17a2b8,color:#fff
    style HR_SECTION fill:#6f42c1,color:#fff
    style INVENTORY_SECTION fill:#fd7e14,color:#fff
    style SYSTEM fill:#343a40,color:#fff
```

### تفصيل الوحدات (75+ وحدة)

<details>
<summary><strong>🟢 العمليات الأساسية (Core Business) — 14 وحدة</strong></summary>

| الوحدة | الوصف | Controllers | ملاحظات |
|:------:|:-----:|:-----------:|:-------:|
| `customers` | إدارة بيانات العملاء، النماذج الذكية، الوسائط | 2 | بحث مقترح، فحص التكرار، التصدير |
| `contracts` | عقود التقسيط، العرض القانوني، ربط المخزون | 1 | +1500 سطر، سير عمل الحالات |
| `contractInstallment` | أقساط العقود والإيصالات | 1 | التحقق من الإيصالات |
| `contractDocumentFile` | مستندات ومرفقات العقود | 1 | |
| `collection` | التحصيل | 1 | |
| `financialTransaction` | الحركات المالية والاستيراد | 1 | استيراد بيانات |
| `income` | الدخل وجدول الأقساط | 1 | |
| `incomeCategory` | تصنيفات الدخل | 1 | |
| `expenses` | المصاريف | 1 | |
| `expenseCategories` | تصنيفات المصاريف | 1 | استيراد مدعوم |
| `invoice` | الفوترة | 1 | |
| `paymentType` | أنواع الدفع | 1 | |
| `bancks` | البنوك | 1 | |
| `companyBanks` | حسابات الشركة البنكية | 2 | |

</details>

<details>
<summary><strong>🔴 القسم القانوني والقضاء — 14 وحدة</strong></summary>

| الوحدة | الوصف | Controllers | ملاحظات |
|:------:|:-----:|:-----------:|:-------:|
| `judiciary` | إدارة القضايا الشاملة | 1 | Controller ضخم: تقارير، تبويبات، تصدير، مراسلات، مواعيد، عمليات دفعية |
| `judiciaryType` | أنواع القضايا | 1 | |
| `judiciaryAuthorities` | الجهات القضائية | 1 | |
| `judiciaryRequestTemplates` | قوالب الطلبات | 1 | |
| `judiciaryActions` | أنواع الإجراءات | 2 | |
| `judiciaryCustomersActions` | إجراءات قضائية للعملاء | 2 | |
| `JudiciaryInformAddress` | عناوين التبليغ | 1 | |
| `lawyers` | المحامون | 1 | |
| `LawyersImage` | صور المحامين | 1 | |
| `court` | المحاكم | 2 | |
| `documentHolder` | حافظ المستندات | 1 | |
| `documentStatus` | حالات المستندات | 1 | |
| `documentType` | أنواع المستندات | 1 | |
| `realEstate` | العقارات | — | نماذج وعروض فقط |

</details>

<details>
<summary><strong>🔵 المحاسبة والإدارة المالية — 11 Controller</strong></summary>

| Controller | الوصف |
|:----------:|:-----:|
| `Default` | لوحة المحاسبة الرئيسية |
| `ChartOfAccounts` | شجرة الحسابات |
| `CostCenter` | مراكز التكلفة |
| `FiscalYear` | السنوات المالية والفترات |
| `JournalEntry` | القيود اليومية |
| `GeneralLedger` | الأستاذ العام |
| `AccountsPayable` | الذمم الدائنة |
| `AccountsReceivable` | الذمم المدينة |
| `Budget` | الموازنات |
| `FinancialStatements` | التقارير المالية |
| `AiInsights` | التحليل الذكي بالذكاء الاصطناعي |

</details>

<details>
<summary><strong>🟣 الموارد البشرية — 14 Controller</strong></summary>

| Controller | الوصف |
|:----------:|:-----:|
| `HrDashboard` | لوحة تحكم HR |
| `HrEmployee` | سجل الموظفين |
| `HrAttendance` | الحضور والانصراف |
| `HrPayroll` | الرواتب والمسيرات |
| `HrField` | العمليات الميدانية |
| `HrEvaluation` | تقييمات الأداء |
| `HrLoan` | السلف والقروض |
| `HrLeave` | الإجازات |
| `HrShift` | الورديات |
| `HrWorkZone` | مناطق العمل الجغرافية |
| `HrTrackingApi` | واجهة تتبع GPS |
| `HrTrackingReport` | تقارير التتبع |
| `HrReport` | التقارير العامة |

</details>

<details>
<summary><strong>🟠 المخزون — 6 وحدات</strong></summary>

| الوحدة | الوصف |
|:------:|:-----:|
| `items` | كتالوج الأصناف |
| `inventoryItems` | الأصناف المخزنية والأرقام التسلسلية |
| `inventoryItemQuantities` | الكميات حسب الموقع |
| `inventoryInvoices` | فواتير التوريد |
| `inventoryStockLocations` | مواقع التخزين |
| `inventorySuppliers` | الموردون |

</details>

<details>
<summary><strong>⚪ المتابعة والتواصل — 14 وحدة</strong></summary>

| الوحدة | الوصف |
|:------:|:-----:|
| `followUp` | المتابعة الشاملة: SMS، لوحة، AI، مهام، جدول زمني |
| `followUpReport` | تقارير المتابعة |
| `rejesterFollowUpType` | أنواع المتابعة |
| `sms` | سجل الرسائل |
| `notification` | الإشعارات الداخلية |
| `phoneNumbers` | أرقام الهواتف |
| `address` | العناوين |
| `citizen` | الجنسيات |
| `cousins` | الأقارب والكفلاء |
| `contactType` | أنواع التواصل |
| `connectionResponse` | نتائج الاتصال |
| `feelings` | مشاعر العميل |
| `hearAboutUs` | مصادر التسويق |
| `loanScheduling` | جدولة القروض |

</details>

<details>
<summary><strong>💎 الاستثمار — 5 وحدات</strong></summary>

| الوحدة | الوصف |
|:------:|:-----:|
| `shareholders` | المساهمون |
| `capitalTransactions` | حركات رأس المال |
| `profitDistribution` | توزيع الأرباح |
| `sharedExpenses` | المصاريف المشتركة |
| `shares` | سجل الأسهم |

</details>

<details>
<summary><strong>🔧 إدارة النظام والبيانات المرجعية — 15+ وحدة</strong></summary>

| الوحدة | الوصف |
|:------:|:-----:|
| `companies` | الشركات |
| `companyManagement` | إدارة وتوفير الشركات (Multi-tenant) |
| `diwan` | الديوان والمراسلات |
| `reports` | التقارير عبر الأقسام |
| `authAssignment` | إدارة الصلاحيات RBAC |
| `city` | المدن |
| `location` | المواقع |
| `branch` | الفروع |
| `status` | الحالات |
| `department` | الأقسام |
| `designation` | المسميات الوظيفية |
| `employee` | سجل الموظفين (Legacy) |
| `dektrium/user` | إدارة المستخدمين (تسجيل، استعادة، ملف شخصي) |
| `gridview` | Kartik GridView |
| `admin` | RBAC UI (mdm/admin) |

</details>

---

## 🔄 سير العمل الرئيسي

```mermaid
graph LR
    A["📝 تسجيل العميل"] --> B["📄 إنشاء العقد"]
    B --> C["📦 ربط المنتجات"]
    C --> D["💰 جدولة الأقساط"]
    D --> E["📞 المتابعة والتحصيل"]
    E --> F{"هل تم السداد؟"}
    F -->|نعم| G["✅ إغلاق العقد"]
    F -->|لا| H["⚖️ التحويل للقانوني"]
    H --> I["📋 رفع قضية"]
    I --> J["⏰ متابعة المواعيد"]
    J --> K["📊 تقرير نهائي"]

    style A fill:#28a745,color:#fff
    style B fill:#17a2b8,color:#fff
    style C fill:#fd7e14,color:#fff
    style D fill:#6f42c1,color:#fff
    style E fill:#800020,color:#fff
    style G fill:#28a745,color:#fff
    style H fill:#dc3545,color:#fff
    style I fill:#dc3545,color:#fff
    style J fill:#ffc107,color:#000
    style K fill:#343a40,color:#fff
```

---

## 📁 هيكل المشروع

```
Tayseer/
│
├── 📂 backend/                     لوحة التحكم — التطبيق الرئيسي
│   ├── assets/                     حزم الأصول (AppAsset, PrintAsset, ...)
│   ├── components/                 مكونات مخصصة (RouteAccessBehavior, ...)
│   ├── config/                     إعدادات Backend
│   ├── controllers/                Controllers رئيسية (Site, Pin, Theme, ...)
│   ├── helpers/                    مساعدات (FlatpickrAsset, ...)
│   ├── modules/                    ← 75+ وحدة — قلب النظام
│   │   ├── accounting/             المحاسبة (11 controller)
│   │   ├── contracts/              العقود
│   │   ├── customers/              العملاء
│   │   ├── followUp/               المتابعة
│   │   ├── hr/                     الموارد البشرية (14 controller)
│   │   ├── judiciary/              القضاء
│   │   ├── inventoryItems/         المخزون
│   │   └── ...                     60+ وحدة أخرى
│   ├── views/                      قوالب العرض
│   │   ├── layouts/                القوالب الرئيسية (Vuexy theme)
│   │   └── site/                   الصفحات العامة (Dashboard, Error)
│   └── web/                        الملفات العامة
│       ├── css/                    أنماط مخصصة + Design Tokens + Component Library
│       ├── js/                     سكربتات مخصصة (30+ ملف)
│       ├── vuexy/                  ثيم Vuexy الكامل
│       ├── plugins/                إضافات (Select2, Flatpickr, ...)
│       ├── manifest.json           PWA Manifest
│       └── sw.js                   Service Worker
│
├── 📂 api/                         REST API — واجهة برمجية
│   ├── config/                     إعدادات API
│   ├── helpers/                    مساعدات (Auth, FCM, SMS, ...)
│   └── modules/v1/                 الإصدار الأول
│       └── controllers/            Payments, Users, CustomerImages
│
├── 📂 common/                      الكود المشترك
│   ├── config/                     إعدادات عامة (DB, params, SMS, ...)
│   ├── models/                     نماذج مشتركة (User, SystemSettings, ...)
│   ├── services/                   خدمات (NotificationService)
│   ├── helper/                     مساعدات (SMSHelper, Permissions, ...)
│   └── components/                 مكونات مشتركة
│
├── 📂 console/                     أوامر CLI
│   ├── controllers/                أوامر Console (Deadline, ...)
│   └── migrations/                 242 ملف ترحيل قاعدة البيانات
│
├── 📂 frontend/                    الواجهة العامة (تسجيل دخول، تواصل)
│
├── 📂 judiciary-v3/                تطبيق القضاء المستقل
│   ├── server/                     Node.js Express API
│   └── client/                     React 18 + Vite + Tailwind
│
├── 📂 landing/                     الموقع التسويقي (HTML/CSS/JS)
│
├── 📂 docs/                        التوثيق
│   ├── setup-guide.md              دليل التثبيت
│   ├── HR_MODULE_SPECIFICATION.md  مواصفات الموارد البشرية
│   ├── specs/                      مواصفات تقنية
│   └── reports/                    تقارير هندسة عكسية (479 شاشة)
│
├── 📂 database/                    أصول قاعدة البيانات
│   ├── migrations/                 سكربتات ترحيل عن بُعد
│   ├── seeds/                      بيانات اختبارية
│   └── sql/                        عروض SQL ونصوص
│
├── 📂 scripts/                     أدوات تشغيل (Python)
│   ├── deploy/                     سكربتات النشر
│   ├── fix/                        إصلاحات وتصحيحات
│   └── verify/                     فحوصات ما بعد النشر
│
├── 📂 deploy/                      البنية التحتية
│   ├── docker-legacy/              Docker Compose (تطوير)
│   └── sshfs/                      أدوات SFTP
│
├── 📂 .github/workflows/          CI/CD — GitHub Actions
│   └── deploy.yml                  نشر تلقائي لـ 4 مواقع
│
├── 📂 .cursor/                     إعدادات IDE
│   ├── rules/                      قواعد المشروع
│   └── skills/                     مهارات مخصصة (5 مهارات)
│
├── composer.json                   اعتماديات PHP
├── phpstan.neon                    تحليل ثابت PHPStan
├── vite.config.js                  إعدادات Vite
└── README.md                       هذا الملف
```

---

## 🗄️ قاعدة البيانات

### نموذج البيانات الرئيسي

```mermaid
erDiagram
    CUSTOMERS ||--o{ CONTRACTS : "لديه"
    CONTRACTS ||--o{ INCOME : "أقساط"
    CONTRACTS ||--o{ FOLLOW_UP : "متابعة"
    CONTRACTS ||--o{ JUDICIARY : "قضايا"
    CONTRACTS ||--o{ CONTRACT_INVENTORY : "منتجات"

    CUSTOMERS {
        int id PK
        string name
        string phone
        string national_id
        string address
    }

    CONTRACTS {
        int id PK
        int customer_id FK
        decimal total_value
        decimal monthly_installment
        string status
        date date_of_sale
    }

    INCOME {
        int id PK
        int contract_id FK
        decimal amount
        date date
        string payment_method
    }

    FOLLOW_UP {
        int id PK
        int contract_id FK
        string type
        text notes
        datetime created_at
    }

    JUDICIARY {
        int id PK
        int contract_id FK
        string case_number
        string status
        int lawyer_id FK
        int court_id FK
    }

    ACCOUNTS ||--o{ JOURNAL_ENTRIES : "قيود"
    JOURNAL_ENTRIES ||--o{ JE_LINES : "سطور"

    ACCOUNTS {
        int id PK
        string code
        string name
        string type
        int parent_id FK
    }

    JOURNAL_ENTRIES {
        int id PK
        date entry_date
        string description
        string status
    }

    JE_LINES {
        int id PK
        int je_id FK
        int account_id FK
        decimal debit
        decimal credit
    }

    EMPLOYEES ||--o{ HR_ATTENDANCE : "حضور"
    EMPLOYEES ||--o{ HR_PAYROLL : "رواتب"

    EMPLOYEES {
        int id PK
        string name
        int department_id FK
        string job_title
        decimal salary
    }

    HR_ATTENDANCE {
        int id PK
        int employee_id FK
        datetime check_in
        datetime check_out
        point location
    }
```

### الجداول الرئيسية (180+)

| المجموعة | الجداول | أمثلة |
|:---------:|:-------:|:-----:|
| **الهوية والصلاحيات** | 10+ | `user`, `profile`, `auth_item`, `auth_assignment`, `system_settings` |
| **العملاء والعقود** | 15+ | `customers`, `contracts`, `contract_document_file`, `Income`, `collection` |
| **المتابعة** | 8+ | `follow_up`, `follow_up_connection_reports`, `sms`, `sms_drafts`, `notification` |
| **القضاء** | 15+ | `judiciary`, `judiciary_actions`, `judiciary_deadlines`, `judiciary_seized_assets`, `court`, `lawyers` |
| **المحاسبة** | 12+ | `accounts`, `journal_entries`, `journal_entry_lines`, `fiscal_years`, `budgets`, `receivables` |
| **المخزون** | 8+ | `inventory_items`, `inventory_item_quantities`, `inventory_stock_locations`, `inventory_invoices` |
| **الموارد البشرية** | 15+ | `hr_attendance`, `hr_attendance_log`, `hr_work_zone`, `hr_geofence_event`, `hr_tracking_point` |
| **الاستثمار** | 6+ | `shareholders`, `capital_transactions`, `profit_distributions`, `shared_expense_allocations` |
| **البيانات المرجعية** | 20+ | `companies`, `branch`, `department`, `city`, `location`, `bancks`, `jobs` |

### عروض SQL (Database Views)

- `vw_hr_attendance_daily_summary` — ملخص الحضور اليومي
- `vw_hr_employee_directory` — دليل الموظفين
- `vw_payroll_employee_attendance` — حضور الرواتب
- `vw_contracts_overview` — نظرة عامة على العقود
- `vw_inventory_item_balance` — أرصدة المخزون
- `vw_judiciary_actions_feed` — تغذية الإجراءات القضائية
- `v_deadline_live` — المواعيد القضائية الحية

---

## 🌐 واجهة برمجة التطبيقات (REST API)

```
Base URL: /api/v1/
Format: JSON Only
Auth: Token-based (ApiAccessRule + Authenticator)
```

| Endpoint | Method | الوصف |
|:--------:|:------:|:-----:|
| `/v1/users` | GET | قائمة المستخدمين |
| `/v1/users` | POST | إنشاء مستخدم |
| `/v1/payments/contract-enquiry` | POST | استعلام عقد |
| `/v1/payments/flat-contract-enquiry` | POST | استعلام عقد (مبسط) |
| `/v1/payments/new-payment` | POST | تسجيل دفعة جديدة |
| `/v1/payments/flat-new-payment` | POST | تسجيل دفعة (مبسط) |
| `/v1/customer-images` | GET | صور العميل |

---

## 🔌 التكاملات الخارجية

```mermaid
graph LR
    TAYSEER["نظام تيسير"]

    TAYSEER --> SMS_GW["📱 بوابات SMS"]
    SMS_GW --> T1["Taqnyat"]
    SMS_GW --> T2["Unifonic"]
    SMS_GW --> T3["Twilio"]
    SMS_GW --> T4["Vonage"]
    SMS_GW --> T5["SMS April"]

    TAYSEER --> FCM_GW["🔔 Firebase FCM<br/>إشعارات Push"]
    TAYSEER --> MAPS_GW["🗺️ Google Maps<br/>تتبع GPS"]
    TAYSEER --> QUEUE_GW["📨 AMQP Queue<br/>معالجة خلفية"]

    style TAYSEER fill:#800020,color:#fff,stroke-width:3px
    style SMS_GW fill:#28a745,color:#fff
    style FCM_GW fill:#ffc107,color:#000
    style MAPS_GW fill:#4285f4,color:#fff
    style QUEUE_GW fill:#6f42c1,color:#fff
```

---

## 🚀 التثبيت والتشغيل

### المتطلبات

| المتطلب | الإصدار |
|:-------:|:-------:|
| PHP | 8.5+ |
| MySQL | 8.0+ |
| Composer | 2.x |
| Node.js | 18+ (لتطبيق القضاء v3) |
| Apache/Nginx | أي إصدار حديث |

### خطوات التثبيت

```bash
# 1. استنساخ المستودع
git clone https://github.com/EngOsamaQazan/Tayseer.git
cd Tayseer

# 2. تثبيت الاعتماديات
composer install

# 3. تهيئة البيئة
php init            # Linux/Mac
init.bat            # Windows

# 4. إعداد قاعدة البيانات
# عدّل common/config/main-local.php بإعدادات DB الخاصة بك

# 5. تشغيل الترحيلات
php yii migrate

# 6. تشغيل الخادم
php yii serve
```

### إعداد تطبيق القضاء v3

```bash
cd judiciary-v3

# تثبيت جميع الاعتماديات
npm run install:all

# إعداد البيئة
cp .env.example .env
# عدّل .env بإعدادات DB

# تشغيل التطوير
npm run dev
# API: http://localhost:3001
# Client: http://localhost:5173
```

> للحصول على دليل تفصيلي كامل، راجع [`docs/setup-guide.md`](docs/setup-guide.md)

---

## 📤 النشر والتوزيع

### النشر التلقائي (CI/CD)

```mermaid
graph LR
    DEV["👨‍💻 المطور"] -->|git push main| GH["GitHub"]
    GH -->|GitHub Actions| CI["CI/CD Pipeline"]
    CI -->|SSH Deploy| VPS["VPS Server"]

    VPS --> S1["🌐 jadal.aqssat.co"]
    VPS --> S2["🌐 namaa.aqssat.co"]
    VPS --> S3["🌐 watar.aqssat.co"]
    VPS --> S4["🌐 majd.aqssat.co"]
    VPS --> S5["🌐 aqssat.co<br/>الموقع التسويقي"]

    style DEV fill:#28a745,color:#fff
    style GH fill:#333,color:#fff
    style CI fill:#2088FF,color:#fff
    style VPS fill:#800020,color:#fff
```

**مراحل النشر التلقائي:**
1. `git push` إلى فرع `main` يُفعّل GitHub Actions
2. الاتصال بالخادم عبر SSH
3. سحب آخر التحديثات لكل موقع
4. نسخ إعدادات البيئة الخاصة بكل موقع
5. تثبيت الاعتماديات (`composer install --no-dev`)
6. تشغيل الترحيلات (`php yii migrate/up`)
7. مسح ذاكرة التخزين المؤقت
8. إعادة تشغيل الخدمات

### البيئات المتعددة (Multi-tenant)

| البيئة | المسار | الاستخدام |
|:------:|:------:|:---------:|
| `prod_jadal` | `/var/www/jadal.aqssat.co` | شركة جدل |
| `prod_namaa` | `/var/www/namaa.aqssat.co` | شركة نماء |
| `prod_watar` | `/var/www/watar.aqssat.co` | شركة وتر |
| `prod_majd` | `/var/www/majd.aqssat.co` | شركة مجد |

---

## ♿ إمكانية الوصول والجودة

### المعايير المطبقة

| المعيار | المستوى | التفاصيل |
|:-------:|:-------:|:--------:|
| **WCAG 2.2** | AAA | تباين 7:1، أهداف لمس 44px، مخطط تركيز واضح |
| **ISO 9241-110** | ✅ | مبادئ التفاعل |
| **ISO 9241-125** | ✅ | العرض المرئي |
| **ISO 9241-171** | ✅ | إمكانية الوصول |
| **Nielsen's Heuristics** | ✅ | 10 قواعد قابلية الاستخدام |

### ميزات إمكانية الوصول
- رابط "انتقل للمحتوى الرئيسي" (Skip to Content)
- هرمية عناوين صحيحة (`<h1>` → `<h6>`)
- سمات ARIA على القائمة الجانبية والتبويبات والنوافذ
- اختصارات لوحة مفاتيح مع لوحة مساعدة (`?`)
- دعم كامل لقارئات الشاشة
- وضع تقليل الحركة (`prefers-reduced-motion`)

---

## 📱 تطبيق الويب التقدمي (PWA)

- تثبيت كتطبيق على الهاتف والحاسوب
- عمل بدون اتصال (Offline-first) عبر Service Worker
- واجهة عربية كاملة مع دعم RTL
- أيقونة قابلة للتخصيص (Maskable Icon)

---

## 📚 التوثيق

| المستند | الرابط | الوصف |
|:-------:|:------:|:-----:|
| دليل التثبيت | [`docs/setup-guide.md`](docs/setup-guide.md) | إعداد بيئة التطوير المحلية |
| مواصفات HR | [`docs/HR_MODULE_SPECIFICATION.md`](docs/HR_MODULE_SPECIFICATION.md) | مواصفات تقنية كاملة للموارد البشرية |
| معالج الفواتير | [`docs/invoice-wizard-and-approval-flow.md`](docs/invoice-wizard-and-approval-flow.md) | تصميم معالج التوريد والموافقات |
| تصميم القضاء | [`docs/specs/JUDICIARY_REDESIGN_PROMPT.md`](docs/specs/JUDICIARY_REDESIGN_PROMPT.md) | مخطط تطبيق القضاء v3 |
| Design Tokens | [`backend/web/css/DESIGN-TOKENS.md`](backend/web/css/DESIGN-TOKENS.md) | مرجع متغيرات CSS |
| مكتبة المكونات | [`backend/web/css/COMPONENT-LIBRARY.md`](backend/web/css/COMPONENT-LIBRARY.md) | مرجع مكونات الواجهة |
| تقرير هندسة عكسية | [`docs/reports/`](docs/reports/) | تحليل 479 شاشة و77 وحدة |

---

## 🛡️ الأمان والصلاحيات

- **نظام RBAC كامل** — أدوار وصلاحيات دقيقة عبر `mdm/admin`
- **مصادقة متقدمة** — تسجيل، استعادة كلمة مرور، ملفات شخصية عبر `dektrium/user`
- **التحكم بالوصول** — `RouteAccessBehavior` على مستوى المسارات
- **تشفير الإعدادات الحساسة** — `SystemSettings` مع تشفير القيم
- **Soft Delete** — حذف ناعم للبيانات الحساسة
- **نظام PIN** — قفل إضافي للعمليات الحساسة

---

## 🧪 ضمان الجودة

- **PHPStan Level 3** — تحليل ثابت للكود
- **Codeception** — اختبارات وحدة وتكامل
- **GitHub Actions** — نشر تلقائي مع فحوصات

---

<div align="center">

## 📄 الترخيص

مرخص بموجب [BSD License](LICENSE.md)

---

**نظام تيسير** — صُمم وطُوّر لتلبية احتياجات شركات التقسيط والتمويل في المنطقة العربية

<br>

![PHP](https://img.shields.io/badge/1932_PHP_Files-777BB4?style=flat-square&logo=php&logoColor=white)
![JS](https://img.shields.io/badge/114_JS_Files-F7DF1E?style=flat-square&logo=javascript&logoColor=black)
![CSS](https://img.shields.io/badge/130_CSS_Files-1572B6?style=flat-square&logo=css3&logoColor=white)
![Views](https://img.shields.io/badge/737_Views-28a745?style=flat-square)
![Screens](https://img.shields.io/badge/479_Screens-800020?style=flat-square)
![Tables](https://img.shields.io/badge/180+_Tables-4479A1?style=flat-square&logo=mysql&logoColor=white)

</div>
