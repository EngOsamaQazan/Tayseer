# تكامل تيسير ↔ نظام الفهرس (Fahras Integration)

> **الإصدار:** 1.0 — أبريل 2026
> **النطاق:** منع تكرار العميل عبر شركات التقسيط الأردنية باستخدام نظام
> الفهرس المركزي قبل إنشاء العميل في تيسير.

---

## 1. لماذا هذا التكامل؟

نظام **الفهرس** هو سجل وطني يجمع بيانات العملاء من جميع شركات التقسيط
المشتركة، ويوفّر «محرّك مخالفات» يقرّر ما إذا كان يحق لشركة معيّنة بيع
العميل أو لا. الهدف من التكامل:

1. منع إضافة عميل في تيسير إذا كان نظام الفهرس يحظره (`cannot_sell`).
2. تنبيه مندوب المبيعات بضرورة التواصل مع شركة سابقة قبل البيع
   (`contact_first`).
3. توثيق كل عملية فحص مع نتيجتها في سجل تدقيقي قابل للمراجعة.
4. إتاحة تجاوز الحظر للمدراء فقط، مع تسجيل السبب وإرسال إشعار.

---

## 2. الهندسة المعمارية

```
┌──────────────────────┐         HTTPS         ┌─────────────────────┐
│   Tayseer (Yii2)     │ ────────────────────▶ │   Fahras (PHP 8)    │
│                      │  GET /admin/api/      │                     │
│  WizardController    │   check.php           │  violation_engine   │
│        │             │   search.php          │   + 6 remote APIs   │
│        ▼             │                       │   (Zajal/Jadal/...) │
│  FahrasService       │ ◀──── JSON ────────── │                     │
│        │             │                       │                     │
│        ▼             │                       │                     │
│  FahrasVerdict (DTO) │                       │                     │
│        │             │                       │                     │
│        ▼             │                       │                     │
│  os_fahras_check_log │                       │                     │
└──────────────────────┘                       └─────────────────────┘
```

### مكوّنات تيسير

| الملف | الدور |
|-------|--------|
| `common/services/FahrasService.php` | غلاف خدمة لاستدعاء API الفهرس مع caching/timeouts/fail-closed. |
| `common/services/dto/FahrasVerdict.php` | كائن قيمة immutable يحمل نتيجة الفحص. |
| `common/models/FahrasCheckLog.php` | ActiveRecord للسجل التدقيقي. |
| `common/models/FahrasCheckLogSearch.php` | نموذج البحث لشاشة السجل. |
| `console/migrations/m260420_100000_create_fahras_check_log.php` | إنشاء الجدول `os_fahras_check_log`. |
| `backend/modules/customers/controllers/WizardController.php` | يستدعي الخدمة من `actionFahrasCheck/Search/Override` ويفرض النتيجة في `validateStep1` و`actionFinish`. |
| `backend/modules/customers/controllers/FahrasLogController.php` | شاشة سجل الفحوصات للمدراء. |
| `backend/modules/customers/views/wizard/_step_1_identity.php` | بطاقة الفحص + Modals (تجاوز/بحث). |
| `backend/web/js/customer-wizard/fahras.js` | منطق الواجهة (debounce, render, override). |
| `backend/web/css/customer-wizard/fahras.css` | تنسيق البطاقة وحالاتها. |

### مكوّنات الفهرس

| الملف | الدور |
|-------|--------|
| `admin/api/check.php` | endpoint موحّد يُرجع verdict جاهزاً (`can_sell`/`cannot_sell`/`contact_first`/`no_record`/`error`). |
| `admin/api/search.php` | بحث خام بالاسم لإظهار المرشحين في Modal «بحث في الفهرس». |

---

## 3. تدفّق الاستخدام

### 3.1 المسار السعيد

1. المستخدم يفتح ساحر العميل ويدخل **الرقم الوطني** و**الاسم**.
2. بعد 700 ميلي/ث من توقّف الكتابة، يقوم `fahras.js` باستدعاء
   `POST /customers/wizard/fahras-check`.
3. `WizardController::actionFahrasCheck` يستدعي `Yii::$app->fahras->check()`
   التي بدورها تنادي `https://fahras.aqssat.co/admin/api/check.php`.
4. الفهرس يُعيد JSON يحتوي على `verdict` + `reason_ar` + `matches`.
5. تيسير يسجّل العملية في `os_fahras_check_log` (مصدر = `step1`).
6. الواجهة تعرض البطاقة بحالة مناسبة (`can / warn / block / error`).
7. عند الضغط على **التالي** يُرسل النموذج إلى `validate` ثم `save`،
   وتعيد `validateStep1()` فحص الفهرس مرّة أخرى (defence in depth).
8. في `actionFinish()` يُربط كل سجلّات `os_fahras_check_log` الأخيرة
   بمعرّف العميل المنشأ.

### 3.2 حالات الحظر

- **`cannot_sell`**: زر «التالي» يُعطّل، تظهر بطاقة حمراء + سبب الحظر.
  إذا كان المستخدم يحمل صلاحية `customer.fahras.override` يظهر زر
  «تجاوز الحظر (مدير)».
- **`error` + `failurePolicy = closed`** (الإنتاج): يُعامَل كـ
  `cannot_sell` (يتم الحظر).
- **`error` + `failurePolicy = open`** (التطوير/الاختبار): تظهر بطاقة
  تحذير ويتم السماح بالمتابعة.

### 3.3 تجاوز المدير

1. المدير يضغط «تجاوز الحظر» ويملأ سبباً (10 أحرف على الأقل).
2. `actionFahrasOverride` يتحقّق من الصلاحية، يعيد فحص الفهرس،
   ويسجّل التجاوز (مصدر = `manual`، `override_user_id`، `override_reason`).
3. يُحفظ التجاوز داخل `WizardDraft` تحت مفتاح `_fahras_override` وصلاحيته
   24 ساعة.
4. تُرسَل إشعارات من نوع `Notification::TYPE_FAHRAS_OVERRIDE` لكل من له دور
   `مدير / manager / admin`.
5. عند `actionFinish()` يتحقّق `runFahrasGate()` من وجود التجاوز ويتجاوز
   الفحص؛ والسجل يبقى مرتبطاً بالعميل المنشأ.

---

## 4. الإعداد

### 4.1 إعدادات تيسير

`common/config/params.php` (الافتراضيات):

```php
'fahras' => [
    'enabled'        => true,
    'baseUrl'        => 'https://fahras.aqssat.co',
    'token'          => null,
    'clientId'       => 'tayseer',
    'companyName'    => null, // ← يُحقن من FAHRAS_COMPANY_NAME (انظر أدناه)
    'timeoutSec'     => 8,
    'cacheTtlSec'    => 0,    // ← لا تستخدم — الكاش مُلغى بالكامل في طبقة الخدمة
    'failurePolicy'  => 'closed',
    'overridePerm'   => 'customer.fahras.override',
    'logViewPerm'    => 'customer.fahras.log.view',
],
```

`environments/<env>/common/config/params-local.php` يصدر التوكن **حصراً**
عبر `getenv('FAHRAS_TOKEN_TAYSEER')`. لا يوضع التوكن نصّاً صريحاً في
git. القيمة الفعلية تُحقن في وقت التشغيل عبر Apache:

```apache
<VirtualHost *:443>
    ServerName <tenant>.aqssat.co
    SetEnv FAHRAS_TOKEN_TAYSEER tayseer_fahras_2026_<full_secret>
    SetEnv FAHRAS_COMPANY_NAME جدل
    ...
</VirtualHost>
```

> ملاحظة: `FAHRAS_COMPANY_NAME` يفعّل اختصار **«إضافة عقد جديد»** عندما
> تكون كل المطابقات في الفهرس تخصّ هذه الشركة فقط (انظر §3.4).
> القيم القانونية: `جدل`، `نماء`، `وتر`، `بسيل`، `زجل`، `عالم المجد`.
> إن لم يُضبط، يبقى السلوك الافتراضي (حظر صلب لمنع التكرار) دون عرض الزر.

تُحفظ القيمة الأصلية في `/root/.fahras_tayseer_token` (660، root فقط)
لإعادة الزرع بعد أي إعادة بناء. السكربت
`scripts/fix_fahras_tenant_vhosts.sh` يقوم بزرعها بشكل عَيري (idempotent)
على كل تنانت ويعيد تحميل Apache. لا تتأثر هذه القيمة بأي
`git pull` لأنها خارج شجرة العمل.

### 4.2 إعدادات الفهرس

`config/api_tokens.php` (يجب إنشاؤه/تحديثه):

```php
return [
    'tayseer' => [
        'token' => 'tayseer_fahras_2026_<rotated>',
        'origins' => ['https://tayseer.aqssat.co'],
    ],
];
```

ثم تطبيق المهاجرة في تيسير:

```bash
php yii migrate --interactive=0
```

### 4.3 الصلاحيات (RBAC)

تُعرَّف في `common/helper/Permissions.php`:

| ثابت | معنى |
|------|-------|
| `CUST_FAHRAS_OVERRIDE` | السماح للمستخدم بتجاوز قرار `cannot_sell`. |
| `CUST_FAHRAS_LOG_VIEW` | السماح بمشاهدة شاشة سجل الفحوصات. |

تُسند هذه الصلاحيات للأدوار من خلال شاشة إدارة الصلاحيات الموجودة في
تيسير.

---

## 5. واجهات API

### 5.1 endpoints الفهرس (تستهلكها تيسير)

#### `GET /admin/api/check.php`

| المعامل | إلزامي | الوصف |
|----------|--------|--------|
| `token` | ✅ | الـ token الخاص بتيسير. |
| `client` | ✅ | معرّف الشركة (`tayseer`). |
| `id_number` | * | الرقم الوطني (إلزامي إذا لم يُمرَّر `name`). |
| `name` | * | الاسم الرباعي (إلزامي إذا لم يُمرَّر `id_number`). |
| `phone` | اختياري | هاتف العميل (يحسّن الدقّة). |

استجابة ناجحة:

```json
{
  "ok": true,
  "verdict": "cannot_sell",
  "reason_code": "ACTIVE_CONTRACT_ELSEWHERE",
  "reason_ar": "العميل لديه عقد قائم في شركة زجل.",
  "matches": [
    {"source": "زجل", "name": "محمد أحمد", "id_number": "9XXXXXXXXX",
     "phone": "0790000000", "account": "Z-1234", "created_at": "2025-09-12"}
  ],
  "remote_errors": [],
  "request_id": "f4a2-...",
  "checked_at": 1745052873
}
```

#### `GET /admin/api/search.php`

| المعامل | إلزامي | الوصف |
|----------|--------|--------|
| `token`, `client` | ✅ | كما أعلاه. |
| `q` | ✅ | نصّ البحث (3 أحرف على الأقل). |
| `limit` | اختياري | الافتراضي 20، الأقصى 50. |

### 5.2 endpoints تيسير (يستهلكها المتصفّح)

| Method | URL | الدور |
|--------|-----|--------|
| `POST` | `/customers/wizard/fahras-check` | فحص لحظي وإرجاع verdict. |
| `POST` | `/customers/wizard/fahras-search` | بحث في الفهرس بالاسم. |
| `POST` | `/customers/wizard/fahras-override` | تسجيل تجاوز مدير. |

شكل الاستجابة (`fahras-check`):

```json
{
  "ok": true,
  "enabled": true,
  "verdict": "cannot_sell",
  "reason_code": "ACTIVE_CONTRACT_ELSEWHERE",
  "reason_ar": "...",
  "matches": [...],
  "remote_errors": [],
  "request_id": "f4a2-...",
  "blocks": true,
  "warns": false,
  "from_cache": false,
  "can_override": true,
  "failure_policy": "closed"
}
```

---

## 6. سجل التدقيق

الجدول `os_fahras_check_log` يحفظ صفّاً واحداً لكل استدعاء (تلقائي/يدوي/
تجاوز). أعمدة مهمّة:

| العمود | المعنى |
|---------|--------|
| `verdict`, `reason_code`, `reason_ar` | نتيجة الفهرس. |
| `matches_json` | المطابقات الكاملة كما أعادها الفهرس. |
| `source` | `step1` / `finish` / `manual` / `search`. |
| `override_user_id`, `override_reason` | بيانات تجاوز المدير (NULL في الحالات العادية). |
| `customer_id` | يُملَأ في `actionFinish()` بعد إنشاء العميل. |
| `from_cache` | يُترَك دائماً `false` — تمّ إلغاء طبقة الكاش بالكامل بحيث يُضرَب الفهرس مباشرةً في كل فحص (سياسة "Live ground truth"). الحقل مُبقىً للتوافق مع المخطط فقط. |
| `duration_ms`, `http_status`, `request_id` | بيانات تشخيصية. |

شاشة المراجعة: **`/customers/fahras-log`** (تتطلّب صلاحية
`CUST_FAHRAS_LOG_VIEW`).

---

## 7. سياسة الفشل (Fail-closed)

في الإنتاج تكون `failurePolicy = 'closed'`:

- أي خطأ شبكة، انتهاء مهلة، استجابة غير صالحة، أو حالة HTTP ≥ 400 تُعتبر
  حظراً.
- يضمن هذا أنّ تعطّل الفهرس **لا** يؤدّي إلى إنشاء عملاء بدون فحص.
- المدراء وحدهم يستطيعون التجاوز بسبب موثَّق.

في بيئات التطوير/الاختبار يمكن ضبط `failurePolicy = 'open'` لتجنّب
تعطيل العمل عند انقطاع الفهرس.

---

## 8. الاختبار

### 8.1 Unit Tests

```bash
cd common
../vendor/bin/codecept run unit services/dto/FahrasVerdictTest
../vendor/bin/codecept run unit services/FahrasServiceTest
```

تغطّي:

- منطق `blocks()` تحت كل verdict + كل failurePolicy.
- إجبار HTTPS عند `requireHttps = true`.
- short-circuit الخدمة عند `enabled = false`.
- التحقق من المدخلات (Id/name/short query).

### 8.2 اختبار يدوي

1. أعدّ token صالح في `params-local.php`.
2. افتح ساحر العميل وادخل رقماً وطنياً معروفاً بحالات مختلفة في الفهرس
   (`can_sell` / `cannot_sell` / `contact_first`).
3. تأكّد من ظهور البطاقة بالحالة الصحيحة وتغيُّر زر «التالي» وفقاً لذلك.
4. اختبر سيناريو الانقطاع: غيّر `baseUrl` إلى عنوان غير قابل للوصول
   مؤقتاً وتأكّد من ظهور البطاقة الحمراء + قفل الزر تحت
   `failurePolicy = closed`.
5. كمدير: نفّذ تجاوزاً وتأكّد من:
   - وصول الإشعار إلى المدير الآخر.
   - وجود سطر بمصدر `manual` و`override_reason` في
     `/customers/fahras-log`.
6. أكمِل إنشاء العميل وتأكّد من ظهور `customer_id` في السجل بعد الحفظ.

---

## 9. أمان وحوكمة

- **التوكن** يُمرَّر كـ query string لتسهيل الاستهلاك من cURL، لكنه
  محمي بـ HTTPS و `CURLOPT_SSL_VERIFYPEER` مفعّل.
- لا يُسجَّل التوكن في أي ملف log داخل تيسير.
- الـ `origins` في الفهرس يجب أن تشمل دومين تيسير الإنتاجي فقط.
- يجب تدوير التوكن دورياً (يُنصح كل 90 يوماً).
- صلاحيات `customer.fahras.override` تُسند فقط لمن يملكون صلاحية
  مكافئة في سياسات الشركة (مدير عام / مدير مبيعات).

---

## 10. خطوات التشغيل في بيئة جديدة

```bash
# 1) في تيسير
php yii migrate --interactive=0
# 2) عدّل params-local.php لتضع التوكن الفعلي
# 3) امسح الكاش
php yii cache/flush-all
# 4) في الفهرس: أضف توكن تيسير ضمن config/api_tokens.php
# 5) جرّب من المتصفح
curl 'https://fahras.aqssat.co/admin/api/check.php?token=...&client=tayseer&id_number=9XXXXXXXXX'
```

---

## 11. خريطة طريق مستقبلية

- ربط نتائج الفهرس بمحرّك المخاطر `RiskEngine` لرفع/خفض درجة العميل.
- أرشفة المطابقات حدثياً (لا فقط Snapshot عند الفحص).
- تنبيهات استباقية عند تغيُّر حالة عميل قائم في الفهرس بعد إنشائه.
- استبدال HTTP GET بـ POST + توقيع HMAC على body.
