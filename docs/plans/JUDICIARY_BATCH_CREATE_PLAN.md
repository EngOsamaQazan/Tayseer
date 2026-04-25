# خطة تطوير: المعالج الجماعي لإنشاء القضايا (MVP)

> **الحالة**: ✅ منفّذ بالكامل (Apr 2026).
> **التذكرة المؤجلة (فيز 2)**: [`JUDICIARY_CASE_LEVEL_ACTIONS_TICKET.md`](./JUDICIARY_CASE_LEVEL_ACTIONS_TICKET.md).

---

## 1. الأهداف

- تقليل وقت تجهيز 100 قضية من ساعات إلى دقائق.
- ثلاث طرق إدخال موحَّدة (لصق، Excel، اختيار شامل من جدول مفلتر).
- Override فردي لكل عقد (محامي/نوع/شركة/موطن) فوق بيانات مشتركة (محكمة/نسبة).
- قوالب مشتركة بين كل الموظفين لتسريع تكرار العمليات.
- التراجع عن دفعة كاملة خلال 72 ساعة (للمنشئ والمدير).
- شريط تقدّم حقيقي عبر تنفيذ مُجزّأ (Chunked AJAX) — يتجنّب timeout.

---

## 2. ما تم تنفيذه

### تنظيف
- حذف مجلد `judiciary-v3/` بالكامل (لم يكن مستخدماً).

### Database (3 migrations جديدة)
- `console/migrations/m260425_100000_create_judiciary_batches.php`
- `console/migrations/m260425_100001_create_judiciary_batch_items.php`
- `console/migrations/m260425_100002_create_judiciary_batch_templates.php`

### AR Models
- `backend/modules/judiciary/models/JudiciaryBatch.php`
- `backend/modules/judiciary/models/JudiciaryBatchItem.php`
- `backend/modules/judiciary/models/JudiciaryBatchTemplate.php`

### Services
- `backend/services/judiciary/BatchCreateService.php`
  - `startBatch()` / `executeChunk()` / `finalizeBatch()`
  - `canRevertBatch()` / `revertBatch()`
  - `resolveCasePreparationActionId()` (idempotent، بحث بالاسم/النوع، إنشاء عند الغياب).
- `backend/services/judiciary/BatchInputParserService.php`
  - `parsePaste()` / `parseExcel()` (xlsx + csv، اكتشاف عمود ID)
  - `validateContractIds()` / `buildPreview()`
- `backend/services/judiciary/BatchTemplateService.php`
  - CRUD + `incrementUsage()` + قوالب مشتركة.

### Controller (`JudiciaryController`)
11 action جديد:
- `actionBatchCreate` (GET فقط، يعرض المعالج)
- `actionBatchParseInput`
- `actionBatchStart`
- `actionBatchExecuteChunk`
- `actionBatchFinalize`
- `actionBatchPrintRedirect`
- `actionBatchHistory`
- `actionBatchRevert`
- `actionBatchTemplateList` (مع `include_data`)
- `actionBatchTemplateSave`
- `actionBatchTemplateDelete`
- `actionContractSearch`

تم استبدال `judiciary_actions_id = 1` الثابت في `actionCreate()` بنتيجة `BatchCreateService::resolveCasePreparationActionId()`.

### Permissions (`common/helper/Permissions.php`)
أُضيفت كل الـ 12 action لخريطة `judiciary/judiciary` تحت `JUD_CREATE`.

### Views
- إعادة كتابة كاملة لـ `backend/modules/judiciary/views/judiciary/batch_create.php`:
  - Stepper بـ3 خطوات.
  - 3 تبويبات إدخال (لصق / Excel drag-and-drop / اختيار من النظام بفلاتر متقدمة).
  - جدول معاينة مع Override فردي لكل صف.
  - قوالب مشتركة (Save/Load/Delete).
  - شريط تقدّم حقيقي + Live log.
- `backend/modules/judiciary/views/judiciary/batch_history.php` — تاريخ الدفعات + التراجع.
- `backend/web/js/judiciary-batch-create.js` — منطق المعالج كاملاً.

### تكامل
- زر «قضايا جماعية» في `_tab_cases.php`.
- تعديل `index-legal-department.php`: زر مرئي دائماً + تحويل الفورم إلى GET للوصول للمعالج.

---

## 3. تغييرات قاعدة البيانات

### `os_judiciary_batches`
| العمود | النوع | ملاحظات |
|--------|-------|---------|
| `id` | PK | |
| `batch_uuid` | VARCHAR(36) UNIQUE | للروابط الخارجية |
| `created_by` | INT | FK to user |
| `created_at` | INT | UNIX timestamp |
| `entry_method` | ENUM('paste','excel','selection') | |
| `contract_count` | INT | |
| `success_count` | INT | |
| `failed_count` | INT | |
| `shared_data` | JSON | court_id, lawyer_id, type_id, percentage, year, address_mode, address_id, company_id, auto_print |
| `status` | ENUM('completed','partial','reverted','running') | |
| `reverted_at` / `reverted_by` / `revert_reason` | NULL | |

### `os_judiciary_batch_items`
| العمود | النوع |
|--------|-------|
| `id` | PK |
| `batch_id` | FK |
| `contract_id` | INT |
| `judiciary_id` | INT NULL |
| `previous_contract_status` | VARCHAR(50) — لاسترجاعها عند التراجع |
| `status` | ENUM('pending','success','failed','reverted','skipped') |
| `error_message` | TEXT NULL |
| `overrides` | JSON NULL — قيم override الفردية |

### `os_judiciary_batch_templates`
| العمود | النوع |
|--------|-------|
| `id` | PK |
| `name` | VARCHAR(100) |
| `created_by` | INT |
| `created_at` / `updated_at` | INT |
| `data` | JSON |
| `usage_count` | INT DEFAULT 0 |
| `is_deleted` | TINYINT DEFAULT 0 |

### إجراء «تجهيز قضية» — Idempotent Seed
يُنشَأ تلقائياً عند الحاجة (لا migration). البحث:
```sql
SELECT id FROM os_judiciary_actions
WHERE (action_type = 'case_preparation' OR name REGEXP 'تجهيز.*قضي(ة|ه)')
  AND (is_deleted = 0 OR is_deleted IS NULL)
ORDER BY action_type='case_preparation' DESC, id ASC
LIMIT 1;
```
عند الغياب: INSERT بـ `name='تجهيز قضية'` + `action_type='case_preparation'` + `action_nature='process'`.

---

## 4. تفاصيل UX حاسمة

### 4.1 تبويب «اختيار من النظام»
- بحث AJAX عبر `actionContractSearch` على `os_contracts` JOIN `os_customers` JOIN `os_jobs` JOIN `os_jobs_type`.
- فلاتر: حالة العقد (multi)، نوع وظيفة العميل، نوع العقد، أشهر متأخرة (`>= N`)، شركة، نطاق تواريخ بيع، نص بحث.
- لا يقتصر على `status='legal_department'`.
- عمود «حالة القضية»: «جاهز» أو «له قضية #N» (مع رابط).
- 50 لكل صفحة.

### 4.2 القوالب المشتركة
- Dropdown «تحميل قالب» مع زر حذف لكل عنصر.
- زر «حفظ كقالب» — modal بسيط للاسم.
- snapshot كامل للقيم المشتركة + `auto_print`.
- `usage_count++` عند التحميل.
- الحذف للمنشئ أو المدير فقط.

### 4.3 الـUndo Logic
1. فحص الصلاحية: `created_by == current_user OR is_manager`.
2. فحص النافذة: `now - created_at <= 72*3600` (`JudiciaryBatch::REVERT_WINDOW_SECONDS`).
3. لكل item ناجح: عدّ `judiciary_customers_actions WHERE judiciary_id = ?`. لو فيه إجراءات > عدد الأطراف (يعني فيه إجراء بعد «تجهيز قضية» الآلي) → يُحجَب.
4. عرض ملخّص قبل التأكيد: «N من M قابلة للتراجع».
5. عند التأكيد:
   - Soft delete على `Judiciary` + `JudiciaryCustomersActions` + `ContractDocumentFile`.
   - استعادة `Contracts.status` من `previous_contract_status`.
   - `judiciary_batches.status='reverted'` + `reverted_at` + `reverted_by` + `revert_reason`.
   - items المُتراجَع عنها → `status='reverted'`.
   - تحديث الكاش.

### 4.4 Chunked Execution
- Chunk = 10 عقود.
- كل chunk داخل transaction مستقل.
- لو فشل chunk → rollback لذلك chunk فقط، الباقي يكمل.
- شريط التقدم = `(completed / total) * 100`.
- في النهاية: `batch-finalize` → redirect لـ `batch-print` (لو مفعّل) أو `judiciary/index`.

### 4.5 Race Condition Protection
- فحص `os_judiciary` (للعقد المُنفّذ حالياً) داخل كل chunk transaction قبل INSERT.
- الـ`afterSave` في `JudiciaryCustomersActions` يبقى مُفعّل (ليس معطّل).

---

## 5. سيناريوهات الاختبار اليدوي

| # | سيناريو | المتوقع |
|---|--------|---------|
| 1 | لصق 3 أرقام عقود صحيحة | جدول معاينة بـ3 صفوف |
| 2 | لصق ID غير موجود | تنبيه «N ID غير موجود» + إخفاؤه |
| 3 | لصق عقد له قضية مسبقة | إبراز بصري + إمكانية الإزالة |
| 4 | رفع xlsx بعمود `id` | استخراج صحيح |
| 5 | رفع csv بدون header | يستخدم العمود الأول |
| 6 | رفع xlsx بعمود `رقم العقد` | اكتشاف صحيح |
| 7 | رفع ملف غير xlsx/csv | رفض + رسالة |
| 8 | اختيار 100 عقد + إنشاء بقالب محفوظ | نجاح كامل |
| 9 | اختيار 101 عقد | منع + رسالة «الحد 100» |
| 10 | فشل chunk واحد (مثلاً عقد محذوف) | باقي الـchunks تكتمل + log واضح |
| 11 | Override المحامي لصف واحد | تطبيق الـoverride دون التأثير على الباقي |
| 12 | اختيار «موطن عشوائي» | كل قضية تحصل على موطن مختلف random |
| 13 | حفظ قالب + تحميله | كل القيم تُملأ بشكل صحيح |
| 14 | تراجع عن دفعة < 72h بدون إجراءات لاحقة | نجاح كامل |
| 15 | تراجع عن دفعة فيها قضية مع إجراء يدوي إضافي | تراجع جزئي + رسالة واضحة |
| 16 | تراجع عن دفعة > 72h | منع + رسالة |
| 17 | موظف غير المنشئ يحاول التراجع (غير مدير) | منع |

---

## 6. الفحص الساكن المُنجَز

- `php -l` على كل الملفات الجديدة والمعدّلة → ✅
- لنت JS → ✅
- مراجعة منطقية للأكواد ضد الـ 17 سيناريو → ✅
- تصحيح: `$item->refresh()` في `BatchCreateService::executeChunk()` لتجنّب تسريب `judiciary_id` المُعدَّل في الذاكرة بعد rollback.

---

## 7. خطوات بعد الـMerge (للنشر)

1. تشغيل `php yii migrate` على الـstaging.
2. اختبار 3-5 سيناريوهات حرجة (لصق 100 + Excel + Override + Revert).
3. النشر إلى production.
4. مراقبة `judiciary_batches.status='partial'` خلال أول أسبوع — لو كثرت، نراجع أسباب الفشل في `batch_items.error_message`.
