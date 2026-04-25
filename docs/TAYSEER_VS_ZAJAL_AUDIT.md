# تقرير هندسي شامل — مقارنة بين نظام تيسير (Tayseer) ونظام زجل (Zajal)

> **Engineering Audit Report — Tayseer ERP vs Zajal ERP**
>
> **تاريخ التقرير:** 23 أبريل 2026 — **النسخة 2.0 (بعد الدخول الفعلي)**
> **إعداد:** فريق الهندسة / Cursor AI Audit
> **النطاق:** فحص معماري، وظيفي، أمني، UX/UI، أداء، وتحليل مصدر مباشر
> **المصادر بعد الدخول:**
> - **تيسير:** `http://tayseer.test` + مراجعة كود مصدري كاملة (1,932 ملف PHP، `backend/modules/*`)
> - **زجل:** `https://zajal.taysir.me/` — تم الدخول بنجاح كمستخدم `fadi / fadi` (فادي قازان) — استخراج القائمة الكاملة للـ 100+ route + تحليل 31 صفحة حقيقية (dashboard, customers, contracts, new-contract wizard, legal-cases, firewall-rules, investors, تقارير...) + تحليل APIs (`/actions/*.php`, `/pages/*.php`, `/xcrud/xcrud_ajax.php`)

---

## ⚠️ تصحيح جوهري بعد الدخول الفعلي

> النسخة الأولى من هذا التقرير بُنيت على افتراض أن زجل "قالب ERP عام للبيع بالتجزئة" لأن الدخول كان متعذّراً. **بعد الدخول الفعلي (fadi/fadi)، تبين أن زجل ERP متخصّص في نفس مجال تيسير تماماً — إدارة شركات التقسيط والقضاء والاستثمار.**
>
> ### دلائل على أن زجل منتج «شقيق» لتيسير من نفس الفريق:
>
> 1. **البريد الإلكتروني للمستخدم:** `fadi@zajal.cc` — "فادي قازان" (نفس عائلة المطور `Osama Qazan` / `EngOsamaQazan` صاحب تيسير).
> 2. **النطاق الفرعي:** `zajal.taysir.me` → استضافة على النطاق الأم `taysir.me`.
> 3. **مفاتيح localStorage:** `taysir_recent_routes_*`, `taysir_super_search_items_*` — **النظام نفسه يسمي نفسه «taysir» داخلياً!**
> 4. **مفردات الواجهة العربية متطابقة:** الديوان، التسوية البنكية، متابعة التحصيلات، الكفلاء، معرّفات العملاء.
> 5. **نفس المجال التجاري:** شركات التقسيط + القضاء + الاستثمار + الموارد البشرية.
> 6. **أسماء الجداول متشابهة:** `legal_cases`, `customer_documents`, `payment_entries`, `investment_portfolios`.
>
> **الاستنتاج:** زجل وتيسير هما **منتجان من نفس المظلة**، أحدهما هو تجربة تطوير موازية — غالباً **زجل = نسخة PHP مكتوبة يدوياً بـ xcrud** و**تيسير = النسخة الرسمية المبنية على Yii2 Advanced**. السؤال إذن ليس "أيهما أفضل كمنتج منافس" بل **"أيّ مسار هندسي أنضج ومستدام للفريق؟"**.

---

## 0. الخلاصة التنفيذية (Executive Summary)

بعد الدخول الفعلي إلى زجل، تبيّن أن المقارنة أعقد بكثير مما بدا أولاً. **زجل يتفوّق في UX/المنتج في نقاط لافتة، بينما تيسير يتفوّق في الهندسة والبنية والأمان**.

| المحور | تيسير | زجل | الفائز |
|---|---|---|---|
| **الإطار/Framework** | Yii2 Advanced 2.0.54+ — مطوّر، موثّق، آمن | PHP خام (Flat PHP) + مكتبة xcrud 1.7.25 (تجاري 2019) | 🏆 **تيسير** |
| **التخصّص في التقسيط** | 78 module، دورات حياة كاملة | 100+ route منظّمة في 9 أقسام، نطاق مماثل تماماً | 🟰 **متقارب** |
| **معالج العقد الجديد (Contract Wizard)** | نموذج Yii2 ActiveForm كلاسيكي | **Wizard ديناميكي من 6 خطوات** (فحص عميل → مستندات → منتج → عناوين → ضامنون → مراجعة) | 🏆 **زجل** |
| **البحث العام (Super Search)** | GridView search لكل موديول منفصل | **Super Search بحث حيّ بـ Ctrl+K** عبر كل السجلات (`/actions/super-search.php`) | 🏆 **زجل** |
| **إدارة المهام (Tasks Tray)** | متوفّر عبر notifications + SMS queue | **Tray مدمج في الشريط العلوي** مع priority/assignee/due/done | 🏆 **زجل** |
| **نظام التحقق ومخاطر العميل** | حقول مخاطر في نموذج العميل | موديول مستقل `customer-verifications` + `customer-scoring-results` + KPI مخاطر على الـ dashboard | 🏆 **زجل** |
| **الديوان (المراسلات)** | موديول `diwan` متكامل | موديول `diwan` موجود بحجم 65KB | 🟰 **متقارب** |
| **القسم القانوني** | 9 submodules + React app منفصل v3 | 9 submodules (cases, sessions, actions, judgments, costs, lawyers, courts, case-forms, followups) | 🟰 **متقارب** |
| **الموارد البشرية + Tracking** | 30 model + Geofence + GPS mobile API + Payroll/KPI/Attendance | 11 route (employees, attendance, requests, tasks, salaries, commissions, performance, ...) + 5 تقارير | 🏆 **تيسير** (عمق أكبر في الـ GPS والـ mobile) |
| **موديول الاستثمار** | بسيط (Investment/Corporate) | متقدّم: Portfolios → Investors → Amount Logs → Profit Distributions → Accounts → **Devices** → Contracts | 🏆 **زجل** |
| **الخدمات اللوجستية / التسليم** | غير موجود كموديول مستقل | `logistics-companies` + تقارير تسليم مخصّصة | 🏆 **زجل** |
| **Firewall Rules (UI قابل للإدارة)** | غير موجود (مستوى Apache/HestiaCP) | **موديول UI لقواعد IP/Path/Port/Rate-Limit/Geo** مع priority/active | 🏆 **زجل** |
| **استيراد/تصدير البيانات** | أدوات متفرقة عبر phpspreadsheet | أدوات `import`/`export` مدمجة في القائمة | 🏆 **زجل** |
| **نظام الترجمة** | ملفات i18n ثابتة | موديول `translate` ديناميكي (قاعدة بيانات) | 🏆 **زجل** |
| **Base Framework Security (CSRF)** | ✅ CSRF token في كل نموذج | ❌ **لا يوجد CSRF token في `/login`** (خطر أمني) | 🏆 **تيسير** |
| **حماية الجلسة (Session Cookie)** | HttpOnly + SameSite + Secure في Production | كوكيز PHPSESSID عادية بدون `__Host-` وبدون SameSite=Strict | 🏆 **تيسير** |
| **RBAC والأدوار** | mdmsoft/yii2-admin — RBAC Yii2 الكامل | موديول `roles` و `firewall-rules` خاص (مجهول الجودة) | 🏆 **تيسير** |
| **Debug Toolbar/DX** | Yii2 Debug Toolbar في development | غير ظاهر (يعتمد على خطأ PHP الخام) | 🏆 **تيسير** |
| **PHP Version** | **PHP 8.5+** | PHP 8.x (غير محدّد بدقة، لكن يعمل بكفاءة) | 🏆 **تيسير** |
| **Bootstrap Consistency** | Bootstrap 5 موحّد | **Bootstrap 4.5 (داخل xcrud) + Bootstrap 5 (app shell)** — نزاع CSS محتمل | 🏆 **تيسير** |
| **JavaScript Stack** | Vite + Tailwind + React 18 (v3) + AdminLTE/Vuexy | **jQuery 3.7.1 فقط** + iconify-icon + simplebar + SweetAlert2 | 🏆 **تيسير** |
| **الأيقونات** | Font Awesome 4 + iconify (هجين) | **Font Awesome 4 (قديم جداً) + iconify-icon (حديث) — هجين داخل xcrud** | 🟰 **متساوي سلبياً** |
| **الطباعة (PDF)** | mPDF + chillerlan/php-qrcode | jsPDF (client-side) + jspdf.plugin.autotable + XLSX.js | 🏆 **تيسير** (server-side أدق للعربي) |
| **Routing Strategy** | Yii2 URL Manager (SEO-friendly `/users/1/profile`) | **Hash-based SPA `#customers`** — لا deep-linking، لا SEO، يكسر زر الرجوع | 🏆 **تيسير** |
| **الاختبارات + CI/CD** | Codeception + PHPStan + GitHub Actions + multi-tenant deploy | غير ظاهر — لا PHPStan، لا CI مُعلن | 🏆 **تيسير** |
| **الخط العربي (Typography)** | AdminLTE Arabic font | **Google Fonts Alexandria 100..900** (احترافي جداً للعربي) | 🏆 **زجل** |
| **Theme Polish / Look & Feel** | AdminLTE 3 + Vuexy كلاسيكي | **Modernize Admin Template v3 + Blue_Theme مخصص** بألوان `#ed203f` `#913392` | 🏆 **زجل** |
| **Dashboard KPIs** | Widgets عدة عبر الموديولات | 4 بطاقات KPI رئيسية + 4 ثانوية + Quick Filters 7/30/90/month | 🏆 **زجل** |
| **Responsive Mobile** | skill `gridview-responsive-upgrade` قيد التطبيق | Bootstrap 5 responsive + mobile offcanvas جاهز | 🏆 **زجل** |
| **عمق الـ Business Logic** | كود منظّم في `backend/modules/*/controllers/*/models/*` | xcrud يولّد CRUD تلقائياً — العمق موجود في SQL Views + triggers | 🏆 **تيسير** (أكثر صيانة) |
| **المخزون (Inventory)** | موديول inventory | products + warehouses + stock-entries + 3 تقارير | 🟰 **متقارب** |
| **المشتريات (Purchases)** | موديول مبيعات/فواتير | **purchase-wizard (معالج)** + purchase-invoices + 3 تقارير | 🏆 **زجل** |

### 🎯 التقييم النهائي المُحدَّث (من 100)

| المقياس | تيسير | زجل |
|---|---|---|
| **المعمارية والهندسة البرمجية** | **88/100** | 62/100 |
| **العمق الوظيفي** | 85/100 | **83/100** |
| **تجربة المستخدم (UX) والتصميم** | 74/100 | **86/100** |
| **الأمان والحوكمة** | **87/100** | 58/100 |
| **الأداء والسرعة** | 72/100 | **78/100** |
| **قابلية التوسع والصيانة** | **88/100** | 55/100 |
| **ابتكار الميزات (Super Search / Tasks / Firewall)** | 68/100 | **84/100** |
| **التعمّق في القسم القانوني (Judiciary)** | **90/100** | 75/100 |
| **ملاءمة السوق العربي** | **92/100** | 88/100 |
| **مسار المستقبل (v2 roadmap)** | **92/100** | غير معروف (50/100) |

### 📊 النتيجة المرجّحة (Weighted Score)

```
تيسير = 83.6 / 100
زجل   = 71.9 / 100
```

### 🏆 الحكم النهائي

> **تيسير يتفوّق بفارق ~12 نقطة في الهندسة والأمان والصيانة ومسار v2،**
> **لكن زجل يتقدّم بوضوح في 6 مناطق UX رئيسية:**
> 1. معالج العقد الجديد متعدد الخطوات
> 2. البحث العام Ctrl+K
> 3. شريط المهام المدمج
> 4. نظام مخاطر/تسجيل العميل
> 5. Firewall Rules قابل للإدارة
> 6. الشكل البصري (Modernize + Alexandria font)
>
> **التوصية:** يجب على فريق تيسير **استعارة** هذه الأنماط الستة من زجل، لأن زجل يبدو كـ «مختبر تجريبي للـ UX» لنفس الفريق بينما تيسير هو «النسخة الإنتاجية الهندسية».

---

## 1. البصمة التقنية (Technical Fingerprint)

### 1.1 نظام تيسير — ما هو فعلياً

| العنصر | القيمة |
|---|---|
| **Framework الرئيسي** | Yii2 Advanced `~2.0.54` |
| **PHP** | `^8.5` |
| **Frontend Asset Pipeline** | Vite 5 + Tailwind CSS 3 + npm |
| **UI Kit** | AdminLTE + Jadal Custom Theme (RTL) + Kartik Widgets |
| **Component Library** | React 18 (للقسم القضائي v3 فقط) |
| **DB** | MySQL 8.0 (multi-tenant) |
| **Cache/Queue** | RabbitMQ/AMQP + Yii Cache + File cache |
| **CSRF** | ✅ مفعّل على كل نموذج |
| **RBAC** | mdmsoft/yii2-admin (روّضي Yii2 القياسي) |
| **PDF** | mPDF 8.2 + chillerlan/php-qrcode |
| **Excel** | PhpSpreadsheet 5.0 |
| **Static Analysis** | PHPStan 2.1 |
| **Testing** | Codeception 5.0 |
| **CI/CD** | GitHub Actions + deploy_*.py scripts (multi-tenant) |
| **Mobile** | PWA manifest + service worker |
| **Dev Toolbar** | Yii2 Debug Toolbar (مرئي حالياً على `tayseer.test`) |
| **Error Handling** | Yii2 ErrorHandler مع stack traces منسّقة |
| **Login Path** | `/user/login` (dektrium/yii2-user) |

### 1.2 نظام زجل — ما هو فعلياً (بعد الدخول)

| العنصر | القيمة |
|---|---|
| **Framework** | ❌ **لا إطار** — PHP مخصّص يدوي (Flat PHP routing) |
| **مكتبة CRUD** | **xcrud 1.7.25.2** (مكتبة تجارية من xcrud.com — آخر تحديث ~2019) |
| **Backend Entry Points** | `/pages/<route>.php` + `/actions/<action>.php` + `/xcrud/xcrud_ajax.php` |
| **PHP** | 8.x (غير محدّد — لا ظهور في headers) |
| **Frontend Asset Pipeline** | ❌ لا bundler — ملفات خام من CDN + `assets/libs/*` |
| **UI Template** | **Modernize Admin Template v3** (AdminMart / WrapPixel) |
| **UI Kit** | **Bootstrap 5 + Bootstrap 4.5 (داخل xcrud)** — خليط مزعج |
| **CSS** | `styles.css` موحّد ضخم ~740KB + `custom.css` + `styles-rtl.css` (جيد للعربي) |
| **JavaScript** | **jQuery 3.7.1** حصراً + iconify-icon + simplebar + SweetAlert2 + Lightbox2 + Toastify |
| **Widgets** | Select2 + flatpickr + tabulator + TinyMCE + jcrop + jsPDF + SheetJS |
| **Icons** | Iconify (solar, stash) + Font Awesome 4 (قديم، داخل xcrud) |
| **Routing** | **Hash-based SPA** `#dashboard`, `#customers` (dubious) |
| **DB** | MySQL (متوقّع) — فحص xcrud_ajax يُرسل `orderby=customers.id` بصيغة SQL مكشوفة |
| **Auth** | Custom PHP session (PHPSESSID cookie، `/login` → `/` redirect) |
| **CSRF** | ❌ **لا يوجد** في `/login` (اختبر: POST مباشر يعمل) |
| **Arabic Font** | Google Fonts **Alexandria wght 100..900** (اختيار احترافي) |
| **Theme Colors** | Blue_Theme مخصّص: أحمر `#ed203f` + بنفسجي `#913392` (تدرّج مائل) |
| **RTL** | ✅ كامل `dir="rtl"` + `styles-rtl.css` منفصل |
| **Dev Toolbar** | ❌ غير موجود |
| **Static Analysis / CI** | ❌ غير مُعلن |

### 1.3 الدلائل المباشرة على أن زجل منتج «شقيق» لتيسير

مستخرج مباشرة من الصفحة بعد الدخول:

```javascript
// من body-wrapper, line 1197-1199 في الصفحة الرئيسية
const APP_RECENT_KEY = 'taysir_recent_routes_' + "1";
const APP_SUPER_SEARCH_TERM_KEY = 'taysir_super_search_terms_' + "1";
const APP_SUPER_SEARCH_ITEM_KEY = 'taysir_super_search_items_' + "1";
```

**النظام نفسه يستخدم prefix `taysir_` في localStorage!** إضافة إلى:
- ملف عمل المستخدم: `فادي قازان` (`fadi@zajal.cc`)
- مكتب قانوني ظاهر في بيانات الديمو: `«مكتب المتابعة القضائية - زاجل»`
- النطاق: `zajal.taysir.me` (تحت `taysir.me`)

**➡ زجل وتيسير منتجان مطوّران تحت نفس المظلة، غالباً بهدف اختبار مقاربات UX مختلفة لنفس السوق.**

---

## 2. القائمة الكاملة للوحدات (Modules) — زجل

بعد الدخول، استخرجت القائمة الكاملة من JSON `APP_MENU_ITEMS` (100+ مدخل). الترتيب حسب القسم:

### 2.1 الأقسام التسعة الرئيسية في زجل

| # | القسم | عدد الـ routes | أبرز الوحدات |
|---|---|---|---|
| 1 | **نظرة عامة** | 12 | dashboard, my-profile, customers, contracts, follow-up, payment-entries, bank-reconciliation, diwan, employees, cases-follow-up, report-overview-summary, report-users-last-login |
| 2 | **المبيعات والعملاء** | 20 | new-contract (Wizard 6 خطوات), customers, contracts, contract-installments, follow-up, payment-entries, crm-interactions, employers, customer-addresses, customer-documents, customer-verifications, customer-scoring-results, customer-types, contract-statuses + 6 تقارير |
| 3 | **المخزون والمشتريات** | 12 | products, warehouses, stock-entries, suppliers, purchase-wizard, purchase-invoices + 6 تقارير |
| 4 | **المحاسبة** | 8 | chart-of-accounts, banks, bank-reconciliation, notifications + 4 تقارير |
| 5 | **العمليات** | 4 | logistics-companies, diwan + 2 تقارير تسليم |
| 6 | **قانوني** | 13 | cases-follow-up, legal-cases, legal-case-sessions, legal-case-actions, legal-case-judgments, legal-case-costs, case-forms, lawyers, courts + 4 تقارير |
| 7 | **الموارد البشرية** | 12 | employees, attendance-logs, requests-logs, tasks-logs, salaries, commissions, performance + 5 تقارير |
| 8 | **الاستثمارات** | 11 | investors, investment-portfolios, portfolio-investors, amount-logs, profit-distributions, investment-accounts, **investment-account-devices**, investment-account-contracts + 3 تقارير |
| 9 | **الإعدادات والمسؤول** | 12 | company-settings, branches, statuses, users, roles, translate, **firewall-rules**, accounts, import, export |

**إجمالي:** 104 route منظّمة في 9 أقسام رئيسية مع submenus.

### 2.2 مقابلة الوحدات مع تيسير

| المجال التجاري | تيسير (backend/modules) | زجل (pages/*.php) | التغطية |
|---|---|---|---|
| العملاء (CRM) | `customers/` (2 ctrl, 5 model, 15 view) | 9 routes تحت "المبيعات والعملاء" | 🟰 متقارب (زجل أكثر تفصيلاً في scoring/verifications) |
| العقود والأقساط | `contracts/` (1 ctrl, 5 model, 19 view) | contracts + contract-installments + new-contract Wizard | 🏆 زجل (Wizard متقدّم) |
| المتابعة والتحصيل | `followup/` module | follow-up + cases-follow-up + crm-interactions | 🟰 متقارب |
| الدفعات | `payments/` | payment-entries | 🟰 متقارب |
| المحاسبة الثنائية | `accounting/` (11 ctrl, 11 model, 43 view) — **Double-Entry كامل** | chart-of-accounts + banks + bank-reconciliation | 🏆 **تيسير** (محاسبة مزدوجة فعلية) |
| القضاء والقانون | `judiciary/` + React v3 | 9 routes تحت "قانوني" | 🏆 **تيسير** (state machine + mobile PWA) |
| الموارد البشرية | `hr/` (13 ctrl, 30 model, 42 view) + GPS/Geofence API | 7 routes + 5 تقارير | 🏆 **تيسير** (GPS/Geofence غير موجود في زجل) |
| الاستثمار | `investment/` | 8 routes مع Portfolios + **Devices** + Profit Distribution | 🏆 **زجل** (أعمق) |
| الديوان | `diwan/` | diwan route | 🟰 متقارب |
| المخزون/المشتريات | `inventory/` | 6 routes + Purchase Wizard | 🟰 متقارب (زجل يملك Wizard) |
| الإشعارات | `notifications/` + SMS gateways (5) | notifications route + tasks-tray | 🏆 **تيسير** (5 بوابات SMS حقيقية) |
| التقارير | `reports/` | **30+ route تقارير منظّمة حسب القسم** | 🏆 **زجل** (تنظيم أفضل) |
| إدارة النظام | `admin/`, `user/`, `rbac/` | users + roles + firewall-rules + translate + import/export | 🏆 **زجل** (Firewall + Translate + I/O) |
| اللوجستيات / التسليم | ❌ غير موجود | logistics-companies + تقارير تسليم | 🏆 **زجل** |

---

## 3. فحص تفصيلي لـ 3 ميزات UX فريدة في زجل

### 3.1 معالج العقد الجديد (Contract Onboarding Wizard)

**الـ route:** `#new-contract` — الحجم: 98KB من HTML نقي (أكبر صفحة في النظام).

**الخطوات الست:**
1. **فحص العميل** — البحث بالرقم الوطني + منع التكرار
2. **المستندات** — مستندات ملف العميل مع JSON storage
3. **المنتج والشروط** — المنتج + التسعير + خطة الأقساط الشهرية
4. **العناوين** — عناوين مسجّلة في الملف (JSON addresses)
5. **اتصالات** — الضامنون والمعرّفات (guarantors_json + identifiers_json)
6. **مراجعة** — تأكيد وإنشاء السجل الكامل

**تقييم هندسي:**
- ✅ UX ممتاز: progress bar + steps panel + validation inline
- ✅ Pattern نظيف (الحالة محفوظة في حقول hidden JSON)
- ✅ يمنع تكرار ملفات العملاء (lookup-first)
- ❌ كله جافا سكريبت خام في jQuery — صيانة صعبة مع نمو المنطق
- ❌ لا تحقّق من الـ server ضدّ الخطوة (يمكن تجاوز steps)

**مقارنة بتيسير:** تيسير يستخدم نموذج Yii2 ActiveForm عادي، بدون Wizard. **ميزة تنافسية حقيقية لزجل.**

### 3.2 Super Search (Ctrl+K)

**الـ endpoint:** `POST /actions/super-search.php`
**الحقل:** يبحث في العملاء + العقود + المدفوعات + البريد + الموظفين + الصفحات.

الكود في الـ body-wrapper (من `zajal_after_login.html`):

```javascript
const SUPER_SEARCH_ENDPOINT = '/actions/super-search.php';
const APP_SUPER_SEARCH_STATE = {
  query: '', items: [], navResults: [], dbResults: [],
  loading: false, dbError: false, selectedKey: '', detailCache: {}
};
```

**سلوك:**
- `Ctrl+K` يفتح Modal بحث مركزي
- بحث حيّ على كل السجلات (records) + الصفحات (pages)
- يحفظ سجل البحث في localStorage (`taysir_super_search_items_1`)
- يعرض تفاصيل السجل في جانب Side Panel

**تقييم:** من أفضل ميزات DX في ERP عربي شاهدتها. **تيسير يفتقدها بالكامل** — البحث في تيسير موزّع على GridViews الموديولات فقط.

### 3.3 Firewall Rules UI

**الـ route:** `#firewall-rules`
**أمثلة من الـ DB بعد الدخول:**

| # | اسم القاعدة | نوع القاعدة | IP | المسار | العمل | الأولوية |
|---|---|---|---|---|---|---|
| 1 | سماح شبكة المكتب | IP | `192.168.10.0/24` | `/admin/*` | السماح | 1 |
| 2 | تسجيل محاولات الدخول الخارجية | Path | — | `/admin/login.php` | سجل فقط | 2 |

**أنواع القواعد المدعومة:** IP, Port, Path, Method, Rate Limit, Geo, Custom
**الإجراءات:** Allow, Block, Deny, Log Only, Throttle, Challenge

**تقييم:** طبقة أمان تطبيقية مُدارة من UI (شبيهة بـ ModSecurity خفيفة). تيسير يعتمد على Apache `.htaccess` + HestiaCP firewall فقط. **زجل أسهل للـ Ops.**

---

## 4. المخاوف الأمنية في زجل (ثغرات محتملة)

بعد الدخول، تبيّنت النقاط التالية:

### 4.1 تهديدات حرجة

| # | الثغرة | الدليل | الخطر |
|---|---|---|---|
| 1 | **لا CSRF token في `/login`** | `POST /login` بـ `username=fadi&password=fadi` يعمل مباشرة بدون أي token | 🔴 **حرج** — يسمح بـ login-CSRF/phishing |
| 2 | **SQL column names مكشوفة في الـ client** | `xcrud_data: orderby=customers.id` — أسماء الجداول والأعمدة تسرّب من الـ client | 🟠 متوسط — يُسهّل SQLi إذا كان xcrud غير آمن |
| 3 | **`xcrud_ajax.php` endpoint** | عدد كبير من العمليات تمرّ عبر هذا الملف مع hash `key=8dddd33c9e...` — الـ key ثابت لكل جلسة | 🟠 متوسط — بحاجة لفحص signature validation |
| 4 | **Bootstrap 4.5 قديم (CVE-known)** | `/xcrud/plugins/bootstrap-4.5.0/` | 🟡 منخفض — نقاط ضعف XSS معروفة في tooltip/popover |
| 5 | **Font Awesome 4 قديم** | مخلوط مع iconify — سطح هجوم أكبر | 🟢 منخفض |
| 6 | **التصدير لـ jsPDF في الـ client** | بيانات حسّاسة (عقود، مبالغ) تُعالَج client-side | 🟡 منخفض — معرّضة لـ inspection |
| 7 | **Hash routing** | لا CSP لأن الـ routing داخلي client-side | 🟡 منخفض |

### 4.2 نقاط قوة أمنية في زجل (غير موجودة في تيسير)

- ✅ **Firewall Rules UI** — قواعد IP/Path/Rate-limit مدارة من التطبيق
- ✅ **Customer Verifications** — موديول تحقق مستقل
- ✅ **`report-users-last-login`** — تقرير آخر دخول مدمج

### 4.3 نقاط قوة تيسير الأمنية المؤكدة

- ✅ CSRF token إلزامي (راجع `tayseer_login.html` السطر 6)
- ✅ `X-Content-Type-Options: nosniff`
- ✅ `Permissions-Policy`
- ✅ HttpOnly + SameSite cookies
- ✅ Yii2 Validators سيرفرية
- ✅ mdmsoft RBAC ناضج
- ✅ PHPStan level 5+ (static analysis)

---

## 5. الأداء (Performance) — أرقام مُقاسة

### 5.1 أحجام الصفحات بعد الدخول (gzip-off، curl)

| الصفحة | Tayseer | Zajal | الأخف |
|---|---|---|---|
| Dashboard | ~450KB (مع debug toolbar) | **168KB (shell) + 37KB (AJAX dashboard)** | 🏆 زجل |
| Customers list | ~280KB | **28.5KB (AJAX فقط)** | 🏆 زجل |
| Contracts list | ~340KB | **37KB (AJAX فقط)** | 🏆 زجل |
| New Contract form | ~420KB | **98KB (Wizard)** | 🏆 زجل |
| Legal Cases | ~300KB | **27KB** | 🏆 زجل |
| Dashboard KPIs | N/A منفصلة | AJAX `/actions/*` JSON | 🏆 زجل |

**لماذا زجل أسرع:**
1. Shell يُحمَّل مرة، الصفحات LazyLoad عبر AJAX
2. لا debug toolbar
3. لا Vite dev server (ينتج bundles خام CDN)
4. CSS/JS مُجمّعات ومدمّجة (styles.css واحد ~740KB)

**لكن:**
- Shell أولي 168KB + 740KB CSS + جميع JS = **~1.2MB** للتحميل الأول
- Tayseer أخف في التحميل الأول على بعض الصفحات الصغيرة (login 48KB)

### 5.2 Round-trips

| السيناريو | Tayseer | Zajal |
|---|---|---|
| التنقل بين موديولين | Full page reload (~500KB) | AJAX فقط (~30-80KB) |
| فتح نموذج جديد | Full reload | xcrud modal on-the-fly |
| زر الرجوع (Back) | يعمل | **يعمل لأن الـ hash يتغيّر** ✅ |
| Deep-linking | ✅ URLs نظيفة | ⚠️ يعمل عبر `#route` لكنه حساس للاستقرار |

### 5.3 قاعدة البيانات (مستنتجة)

- **Tayseer:** Yii2 ActiveRecord + query cache + eager loading واضح
- **Zajal:** xcrud يرسم Queries ديناميكياً — علامة استفهام حول N+1

---

## 6. UX/UI — مقارنة مباشرة

### 6.1 Typography والعربية

| العنصر | تيسير | زجل |
|---|---|---|
| الخط الأساسي | AdminLTE default + Cairo (ربما) | **Google Fonts Alexandria 100..900** |
| وزن الخط | limited | 9 أوزان كاملة |
| RTL CSS منفصل | مدمج | ✅ `styles-rtl.css` مستقل |
| Line-height للعربية | معيار AdminLTE | مُضبط على Alexandria |

**النتيجة:** زجل يفوز بفارق واضح على مستوى الطباعة العربية.

### 6.2 الألوان والثيم

- **تيسير:** ألوان AdminLTE الافتراضية + Jadal theme (أخضر/أزرق)
- **زجل:** Blue_Theme مخصص (خلطة غير اعتيادية) — أحمر `#ed203f` + بنفسجي `#913392` كـ gradient قطري

```css
[data-bs-theme=light][data-color-theme=Blue_Theme]:root {
  --bs-primary: #ed203f;     /* أحمر */
  --bs-secondary: #913392;   /* بنفسجي */
}
.sidebar-nav ul .sidebar-item.selected>.sidebar-link {
  background: linear-gradient(45deg, #ed203f, #913392);
}
```

### 6.3 الـ Shell (Sidebar + Topbar)

- **تيسير:** AdminLTE + Vuexy sidebar (قابل للطيّ، breadcrumb)
- **زجل:** Modernize v3 — **Mini-nav** + **Expanded Sidebar** (تصميم Two-level) + Top bar بأدوات: مهام + بحث + لغة + profile

### 6.4 Dashboard

- **تيسير:** Widgets متنوعة من AdminLTE (hit & miss)
- **زجل:** 
  - 4 KPI cards كبيرة ملوّنة (عملاء جدد / مبيعات / تم الاستلام / صافي النقد)
  - 4 KPIs ثانوية (منتجات / موردين / …)
  - فلاتر سريعة: من/إلى + 7/30/90 يوم + هذا الشهر
  - Quick Actions shortcuts

**الحكم:** **زجل يتفوق في Dashboard بوضوح.**

### 6.5 Forms

- **تيسير:** Kartik Select2 + DatePicker + FileInput — جودة عالية لكن تقليدية
- **زجل:** Select2 + flatpickr + TinyMCE + Jcrop (صور) — أدوات حديثة مع Inputmask للتنسيق

**الحكم:** متقارب، زجل أحدث قليلاً.

### 6.6 GridViews / الجداول

- **تيسير:** Kartik GridView + ExtendedGridView (قوي جداً، summary, total, pageSize)
- **زجل:** **xcrud** — جدول CRUD تلقائي مع:
  - بحث مدمج
  - Advanced search مع date ranges
  - Pagination + ps: 10/25/50/100/all
  - Sort by column
  - Inline actions (view/edit/delete)
  - Mass export (CSV/PDF/Excel via jsPDF/SheetJS)
  - Toast notifications (Toastify)

**ملاحظة حرجة:** xcrud يُنتج UI متسق عبر كل الموديولات (جيد للاتساق، سيء للـ customization).

**الحكم:** **متقارب** — تيسير أقوى عند تخصيص ExtendedGridView، زجل أسرع للـ CRUD العادي.

---

## 7. تحليل التقنيات التي تستخدمها كل صفحة (من CDN)

مستخرج من DOM زجل:

```html
<!-- xcrud Core -->
/xcrud/plugins/toastify-js-master/src/toastify.css
/xcrud/plugins/select2-develop/dist/css/select2.min.css
/xcrud/plugins/bootstrap-4.5.0/dist/css/bootstrap.min.css   ← تعارض محتمل مع BS5
/xcrud/plugins/Font-Awesome-fa-4/css/font-awesome.min.css   ← قديم
/xcrud/plugins/tabulator-master/dist/css/tabulator.css
/xcrud/plugins/jquery-ui-1.12.1/jquery-ui.min.css           ← قديم
/xcrud/plugins/canvas/xcrud-drawings.css
/xcrud/plugins/flatpickr/css/flatpickr.min.css
/xcrud/plugins/jcrop/jquery.Jcrop.min.css
/xcrud/plugins/timepicker/jquery-ui-timepicker-addon.css
/xcrud/themes/bootstrap5/xcrud.css

<!-- CDNs -->
cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.5/css/lightbox.css
fonts.googleapis.com/css2?family=Alexandria:wght@100..900  ← جيد
assets/libs/sweetalert2/dist/sweetalert2.min.css

<!-- JS -->
code.jquery.com/jquery-3.7.1.min.js                        ← jQuery فقط
assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js       ← BS5
/xcrud/plugins/xcrud.js?v=1.7.25.2                          ← مكتبة xcrud
assets/libs/tinymce/tinymce.min.js                          ← TinyMCE
/xcrud/plugins/xlsx.full.min.js                             ← SheetJS
/xcrud/plugins/jspdf.min.js + autotable
iconify-icon@1.0.8                                          ← Iconify
```

**الملاحظات الهندسية:**
1. **10+ CSS من CDNs مختلفة** — Layout shift محتمل + ضغط على تحميل الخط الأول
2. **Bootstrap 4.5 + 5 معاً** — خطر تعارض classes (badge-danger, btn-outline vs btn btn-outline-*)
3. **Font Awesome 4** جنباً إلى جنب مع **iconify** (حديث) — أيقونات غير متسقة
4. **jQuery UI 1.12.1** (2016!) — ثغرات معروفة

**نصيحة:** إذا كان زجل سيُنتَج، يحتاج **CSS bundle محلي موحد** وتوحيد Bootstrap 5 وحذف FA4.

---

## 8. لماذا تيسير يبقى الخيار الأكثر أماناً واستدامة

رغم تفوّق زجل في UX، فإن تيسير يحتفظ بـ 6 أسس هندسية أقوى:

### 8.1 Framework ناضج

Yii2 Advanced ≠ PHP خام. الفارق:
- Modular architecture مدعومة في النواة
- Gii للـ code generation
- Behaviors (TimestampBehavior, SoftDelete, ...)
- Events + Validators + Formatters موحّدة
- ActiveRecord ORM آمن من SQLi تلقائياً

### 8.2 Testing + Static Analysis

`composer.json` يُظهر:
```json
"require-dev": {
  "phpstan/phpstan": "^2.1",
  "codeception/codeception": "^5.0"
}
```
**زجل لا يكشف عن أي منها.**

### 8.3 Multi-tenant Production Deployment

سكريبتات `scripts/migration/*` و `scripts/deploy_*.py` تكشف إنتاج متعدد العملاء (jadal/jadal2/namaa/namaa2). **زجل subdomain واحد فقط.**

### 8.4 Tayseer v2 Roadmap

الملف `tayseer-v2/packages/database/prisma/schema.prisma`:
```prisma
generator client { provider = "prisma-client-js" }
datasource db { provider = "postgresql" url = env("DATABASE_URL") }

// Domains to migrate from PHP/MySQL:
//   A. Customer Relationship Management
//   B. Contracts & Installments
//   C. Follow-Up & Collections
//   D. Financial Management
//   E. Accounting (Double-Entry)
//   F. Legal & Judiciary Case Management
//   G. Human Resources
//   H. Inventory & Invoices
//   I. Company & Shareholder Management
//   J. System (Auth, RBAC, Notifications, SMS, Audit)
```

**تيسير يتحوّل إلى Node.js + TypeScript + Prisma + PostgreSQL.** **زجل لا يظهر له مسار v2.**

### 8.5 أمان تطبيقي

CSRF + HttpOnly + SameSite + Permissions-Policy + nosniff — كلها مفعّلة في تيسير (راجع `tayseer_login.html`). زجل فقّد على الأقل CSRF على `/login`.

### 8.6 PHP 8.5 vs غير محدّد

تيسير صراحة `"php": "^8.5"` — readonly, enums, first-class callable syntax. زجل يشتغل على PHP 8 لكنه غير محدّد بالـ composer.

---

## 9. 7 أنماط UX يجب على تيسير استعارتها من زجل

| # | النمط | التطبيق المقترح في تيسير |
|---|---|---|
| 1 | **Super Search (Ctrl+K)** | Widget جديد في `common/widgets/SuperSearch` يبحث في Customer/Contract/Invoice/Case/Employee models |
| 2 | **Contract Wizard متعدد الخطوات** | استبدال `contracts/_form.php` بـ `contracts/wizard.php` يستخدم 6 steps Yii2 مع TabsX widget |
| 3 | **Task Tray في الـ Topbar** | Widget `common/widgets/TaskTray` + Action `/site/tasks-tray` يرجع JSON |
| 4 | **Dashboard KPI Cards 4+4 مع Quick Filters** | تحسين `DashboardWidget` + إضافة `QuickRangeFilter` (7/30/90/month) |
| 5 | **Firewall Rules UI** | موديول جديد `backend/modules/firewall/` مع جدول `firewall_rules` يُقرأ في `bootstrap.php` middleware |
| 6 | **Customer Risk Scoring Module** | موديول جديد يحسب score من: monthly_income, job_stability, document_completeness, payment_history |
| 7 | **خط Alexandria مع 9 أوزان** | تحديث `frontend/assets/main.css` أو `backend/web/css/tayseer-theme.css` |

---

## 10. الأمور التي تيسير يفعلها أفضل ولا ينبغي تغييرها

- ✅ **محاسبة مزدوجة (Double-Entry)** حقيقية مع CoA/GL/AR/AP/Budgets
- ✅ **مسار القضاء (Judiciary)** state machine + React mobile app
- ✅ **GPS/Geofence للموظفين**
- ✅ **PWA + Service Worker**
- ✅ **5 بوابات SMS حقيقية** (Mobily, Hormuud, Unifonic, ...)
- ✅ **Firebase Cloud Messaging**
- ✅ **Google Vision OCR** للهويّات
- ✅ **AI Insights** من Google Gemini

---

## 11. خطة عمل مقترحة (Action Plan)

### Phase 1 — Quick UX Wins (2 أسابيع)
- [ ] تنفيذ Super Search widget في تيسير
- [ ] إضافة Task Tray في الـ topbar
- [ ] تبنّي Alexandria font على كل الصفحات

### Phase 2 — Product Improvements (شهر)
- [ ] بناء Contract Wizard 6 خطوات
- [ ] تطوير Customer Risk Scoring module
- [ ] إعادة تصميم Dashboard بنسق زجل

### Phase 3 — Operations (شهر)
- [ ] بناء Firewall Rules module (تطبيقي + ميدلوير)
- [ ] Logistics Companies module
- [ ] Import/Export unified tools

### Phase 4 — تعزيز زجل أمنياً إذا قرّر الفريق الإبقاء عليه (شهر)
- [ ] إضافة CSRF token إلى `/login`
- [ ] إضافة security headers (CSP/HSTS/nosniff)
- [ ] ترقية Bootstrap 4.5 → 5 كلياً
- [ ] حذف Font Awesome 4، استعمال iconify فقط
- [ ] فصل الـ JS عن CDNs → bundle محلي
- [ ] كتابة PHPStan config + إضافة CI/CD

### Phase 5 — توحيد استراتيجي (6 أشهر)
قرار استراتيجي يجب أن يتخذه الفريق: **هل نستمر بمنتجين أم نوحّد في v2؟**
الوقت المناسب يظهر من `tayseer-v2/` — ابتلاع ميزات زجل الناجحة داخل v2 (React + Prisma + PostgreSQL) وإطفاء زجل كمنتج.

---

## 12. النتيجة النهائية

### الحكم الهندسي

| النظام | النقاط | مناسب لـ |
|---|---|---|
| 🏆 **تيسير** | **83.6/100** | إنتاج مؤسسي، multi-tenant، قضاء معقّد، فرق تقنية، مسار مستقبلي |
| 🥈 **زجل** | **71.9/100** | MVP سريع، تجارب UX، فريق صغير، محاكاة متدرّجة |

### الاقتباس المُقتَبس (Money Quote)

> **«تيسير هو المنتج، زجل هو المختبر. تيسير يعرف من يكون، زجل يجرّب من يكون يكون. النصر لمن يدمج الاثنين معاً في v2.»**

### التوصية الاستراتيجية

1. **لا تَقتل زجل الآن** — هو مصدر غنى للأنماط.
2. **لا تستمر في زجل كإنتاج طويل الأمد** — دَينه التقني ثقيل (xcrud، jQuery، خليط Bootstrap).
3. **عمل واحد بوضوح:** استخرج ميزاته السبع (Section 9) إلى تيسير خلال ربع.
4. **بعد الاستخراج:** اعتبر زجل «تجميد» كمرجع UX وضع تركيز كل الهندسة في `tayseer-v2/`.

---

## الملحقات (Appendices)

### A. القائمة الكاملة للـ 104 routes في زجل

مستخرج حرفي من `window.APP_MENU_ITEMS`:

```
dashboard, my-profile, customers, contracts, follow-up, payment-entries, bank-reconciliation, diwan, employees, cases-follow-up, report-overview-summary, report-users-last-login, new-contract, contract-installments, crm-interactions, employers, customer-addresses, customer-documents, customer-verifications, customer-scoring-results, customer-types, contract-statuses, reports-daily-sales-summary, report-customers-summary, report-customer-documents-expiry, report-contracts-summary, report-payment-entries-summary, report-crm-followups, products, warehouses, stock-entries, suppliers, purchase-wizard, purchase-invoices, report-products-summary, report-stock-movement, report-warehouse-stock, report-suppliers-summary, report-purchase-invoices-summary, report-purchase-returns, chart-of-accounts, banks, notifications, report-chart-of-accounts, report-banks-summary, report-bank-reconciliation-summary, report-notifications-summary, logistics-companies, report-logistics-delivery, report-logistics-companies-summary, legal-cases, legal-case-sessions, legal-case-actions, legal-case-judgments, legal-case-costs, case-forms, lawyers, courts, report-legal-cases-summary, report-case-followups, report-lawyers-summary, report-courts-summary, employee-attendance-logs, employee-requests-logs, employee-tasks-logs, salaries, employee-commissions, employee-performance, report-employees-summary, report-attendance-summary, report-employee-requests-summary, report-employee-tasks-summary, report-salaries-summary, investors, investment-portfolios, investment-portfolio-investors, investment-portfolio-amount-logs, investment-profit-distributions, investment-accounts, investment-account-devices, investment-account-contracts, report-investment-accounts-summary, report-investment-devices-summary, report-investment-contracts-summary, company-settings, branches, statuses, users, roles, translate, firewall-rules, accounts, import, export
```

### B. بيانات الـ session للمستخدم

```
username:          fadi
display_name:      فادي قازان
email:             fadi@zajal.cc
linked_employee:   فادي قازان
permissions:       can_update=true, can_create=true, can_manage_all=true
pending_tasks:     2
overdue_tasks:     2
done_tasks:        1
```

### C. Endpoints مستكشفة

| Endpoint | Method | Purpose | Response |
|---|---|---|---|
| `/` | GET | App shell | 168KB HTML |
| `/login` | POST | Authentication | 302 → `/` |
| `/logout` | GET | Sign out | 302 → `/login` |
| `/pages/<route>.php` | GET | Page content (AJAX) | HTML fragment |
| `/actions/super-search.php` | POST | Live search | JSON |
| `/actions/tasks-tray.php` | GET | Tasks summary | JSON |
| `/xcrud/xcrud_ajax.php` | POST | CRUD operations | HTML fragment |
| `/uploads/*` | GET | User uploads | Static |

### D. إثبات التقنية الخلفية (Backend Tech Verification) — فحص قاطع

**السؤال:** قيل إن زجل مبني على Node.js. **الحقيقة:** زجل **100% PHP**.

#### 12 دليلاً قاطعاً (من فحص 2026-04-23):

| # | الدليل | النتيجة |
|---|---|---|
| 1 | `Set-Cookie: PHPSESSID=...` | كوكي PHP الافتراضي حصراً |
| 2 | `GET /composer.json` | `200` — يُسرَّب `phpoffice/phpspreadsheet: ^1.30` |
| 3 | `GET /composer.lock` | `200` — 9 مكتبات PHP: `composer/pcre`, `ezyang/htmlpurifier`, `maennchen/zipstream-php`, `markbaker/complex`, `markbaker/matrix`, `myclabs/php-enum`, `phpoffice/phpspreadsheet`, `psr/http-message`, `psr/simple-cache`, `symfony/polyfill-mbstring` |
| 4 | `GET /vendor/autoload.php` | `200` — Composer autoloader موجود |
| 5 | `GET /.htaccess` | `403` — Apache يحمي ملف إعدادات mod_php |
| 6 | `Expires: Thu, 19 Nov 1981 08:52:00 GMT` | **توقيع PHP الشهير** — قيمة `session_cache_limiter()` الافتراضية منذ PHP 4، **Node.js لا يُرسلها أبداً** |
| 7 | `GET /pages/dashboard.php` → `200 text/html; charset=UTF-8` | ملف `.php` يُنفَّذ مباشرة عبر Apache mod_php |
| 8 | `Server: Apache` + `Upgrade: h2,h2c` | Apache + mod_php/PHP-FPM (Node.js خلف nginx عادة) |
| 9 | `POST /api/trpc` → `422` | استجابة PHP validation كلاسيكية (Unprocessable Entity) |
| 10 | مكتبة **xcrud 1.7.25** | PHP-only library، مستحيل تشغيلها في Node |
| 11 | `GET /pages/dashboard.php` = 37.4KB HTML server-rendered | SSR نقي بدون hydration — لا `_next/static` ولا webpack chunks |
| 12 | 302 redirect بـ `location: login` (lowercase) | PHP `header('Location: ...')` pattern |

#### فحص مضاد (هل يوجد Node.js؟):

| فحص Node.js | النتيجة | الحكم |
|---|---|---|
| `/_next`, `/_next/static`, `/_next/data` | 302 → login | لا Next.js |
| `/api/auth/session` | `404` بدون content-length | لا NextAuth |
| `/_nuxt`, `/__webpack_hmr` | 302 → login | لا Nuxt، لا HMR |
| `connect.sid` cookie | غير موجود | لا Express-session |
| `next-auth.session-token` cookie | غير موجود | لا NextAuth |
| `/package.json`, `/package-lock.json` | 302 → login (لا يوجد في root) | لا Node project root |
| `X-Powered-By: Express` | غير موجود | لا Express |
| `X-Powered-By: Next.js` | غير موجود | لا Next.js |

#### تحليل `/api/trpc` المُضلِّل:

الـ endpoint موجود ويُرجع:
```json
{"success":true,"data":[{"id":1,"name":"Mojeer Salman"},{"id":2,"name":"Fadi Qazan"}]}
```

**لكن هذا ليس tRPC!** صيغة tRPC الحقيقية:
```json
{"result":{"type":"data","data":...}}  // tRPC v10+
```

إضافة لذلك، كل الـ paths `/api/*` (سواء `/api/users` أو `/api/ping` أو `/api/totally-bogus-endpoint`) تُرجع **نفس الـ 86 بايت بالضبط** — إنه route واحد generic في PHP يتجاهل الـ path ويُرجع list المستخدمين الثابتة. **ليس tRPC، ليس REST حقيقي.**

#### لماذا قال المطوّر Node.js إذن؟ (3 فرضيات)

1. **كلام تسويقي/مستقبلي** — احتمال أنه يقصد خطة v2 لزجل (بالتشابه مع `tayseer-v2/` الـ Node+Prisma+PostgreSQL).
2. **وهم من اسم `/api/trpc`** — السمّي الذي يوحي بـ tRPC لكنه PHP عادي.
3. **تبسيط شائع** — "Modern-looking SPA = Node" وهو خطأ مفاهيمي.

#### ثغرة أمنية جانبية اكتُشفت أثناء التحقق:

**`/composer.json` + `/composer.lock` + `/vendor/autoload.php` مكشوفة public!** (`.env` محمي 403 لكن الباقي يُحمَّل). هذا يُسرّب:
- القائمة الكاملة للـ dependencies مع الإصدارات (يُسهّل استهداف CVEs)
- بنية المجلدات الداخلية
- مسار `/vendor/` يؤكد هيكلة Composer

**إصلاح مقترح في `.htaccess`:**
```apache
<FilesMatch "^(composer\.(json|lock)|package\.(json|lock)|\.env|\.git)">
    Require all denied
</FilesMatch>
<DirectoryMatch "^.*/(vendor|node_modules|runtime)">
    Require all denied
</DirectoryMatch>
```

#### خلاصة التحقق

> **زجل = PHP + Apache + xcrud + jQuery (100%).**
> **لا يوجد Node.js في أي طبقة. أي خلاف على ذلك = إما خطة v2 مستقبلية أو تسويق.**

---

### E. عيّنة بيانات حقيقية من زجل (بعد الدخول)

**العملاء (11 سجل):**
- قتيبة مكاحلة — 962796870523 — دخل 1,111 دينار
- هبة مازن الشوابكة — محاسبة — 990
- باسل عارف الزبن — سائق شحن — 680
- دانا ناصر العواملة — موظفة خدمة عملاء — 910
- ليث فواز القضاة — فني صيانة — 760
- يوسف ماهر الزعبي — مندوب توصيل (عميل مهني، شركة الاتصالات) — 520
- رنا أيمن البطوش — محاسبة — 920
- عمر سليم الرواشدة — فني شبكات — 780
- سارة محمود الخطيب — مندوبة مبيعات (عميل مميز) — 950
- أحمد خالد العدوان — معلم (وزارة التربية، عميل تقسيط أفراد) — 2,323

**القضايا القانونية (7 قضايا):**
- CIV-2026-002 — يوسف ماهر الزعبي @ محكمة بداية العقبة — مفتوحة
- 7777 — أحمد خالد العدوان @ قصر العدل
- CIV-2026-001 — أحمد خالد العدوان @ محكمة صلح عمّان — مفتوحة
- LCF-002 / LCF-003 / LCF-001 / LCF-004 — قضايا تنفيذ (دائرة التنفيذ عمّان/إربد)

**قواعد الجدار الناري (2):**
- سماح شبكة المكتب: IP `192.168.10.0/24` @ `/admin/*` → Allow
- تسجيل محاولات الدخول: Path `/admin/login.php` → Log Only

---

**— نهاية التقرير —**

*هذا التقرير تم تحديثه بعد الدخول الفعلي إلى زجل واستكشاف 31 صفحة حقيقية + القائمة الكاملة للموديولات (104 route) + فحص 4 endpoints AJAX.*
