# تذكرة (Phase 2): إصلاح بنية «إجراء على مستوى القضية vs على مستوى الطرف»

> **الحالة**: مؤجَّلة — تنفَّذ بعد استقرار MVP الـ Batch Create.
> **الأولوية**: متوسطة (لا تحجب MVP، لكنها تُلوّث البيانات).
> **مرتبطة بـ**: [`JUDICIARY_BATCH_CREATE_PLAN.md`](./JUDICIARY_BATCH_CREATE_PLAN.md).

---

## المشكلة

النموذج الحالي [`backend/modules/judiciaryCustomersActions/models/JudiciaryCustomersActions.php`](../../backend/modules/judiciaryCustomersActions/models/JudiciaryCustomersActions.php) يفرض `customers_id` كحقل required.

هذا يعني أن **كل إجراء قضائي** لا بد أن يُربط بطرف (مدّعى عليه). لكن في الواقع هناك إجراءات تخصّ القضية ككل، وليست خاصة بطرف:

- تسجيل الدعوى في المحكمة.
- تثبيت رقم القضية في النظام القضائي الرسمي.
- قرار قضائي عام (حكم نهائي على القضية، تأجيل جلسة، إلخ).
- نقل القضية بين دوائر/محاكم.

### الحل المؤقّت في MVP

عند Auto-create لإجراء «تجهيز قضية» (`case_preparation`)، يتم تكرار الـ INSERT لكل طرف — أي إذا كانت القضية فيها 3 مدّعى عليهم، يُسجَّل إجراء «تجهيز قضية» 3 مرات.

**هذا يُنجح العملية لكنه يلوّث:**
- التايملاين (تكرار غير مرغوب فيه).
- التقارير (إحصاءات مُضخّمة).
- منطق `canRevertBatch` (نضطر للمقارنة بـ `count(actions) > count(parties)` بدل `count > 1`).

---

## الحل المقترح (Option 3)

إضافة جدول جديد `os_judiciary_actions_log` للإجراءات على مستوى القضية:

### 1. جدول جديد

```sql
CREATE TABLE os_judiciary_actions_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    judiciary_id INT NOT NULL,
    judiciary_actions_id INT NOT NULL,
    action_date INT,
    notes TEXT,
    created_by INT,
    created_at INT,
    is_deleted TINYINT DEFAULT 0,
    INDEX idx_judiciary (judiciary_id),
    FOREIGN KEY (judiciary_id) REFERENCES os_judiciary(id),
    FOREIGN KEY (judiciary_actions_id) REFERENCES os_judiciary_actions(id)
);
```

### 2. تمييز نوع الإجراء

إضافة عمود `scope` لـ `os_judiciary_actions`:

```sql
ALTER TABLE os_judiciary_actions
ADD COLUMN scope ENUM('case','party') DEFAULT 'party';
```

- `scope='case'` → يذهب لـ `os_judiciary_actions_log`.
- `scope='party'` → يذهب لـ `os_judiciary_customers_actions` (الحالي).

### 3. عرض موحَّد

إنشاء VIEW SQL أو merge في PHP layer لعرض timeline موحَّد:

```sql
CREATE VIEW v_judiciary_timeline AS
SELECT id, judiciary_id, NULL AS customers_id, judiciary_actions_id,
       action_date, 'case' AS scope, notes, created_by, created_at
FROM os_judiciary_actions_log
WHERE is_deleted = 0
UNION ALL
SELECT id, judiciary_id, customers_id, judiciary_actions_id,
       action_date, 'party' AS scope, notes, created_by, created_at
FROM os_judiciary_customers_actions
WHERE is_deleted = 0;
```

---

## التأثيرات (نقاط يجب مراجعتها)

| المكان | التأثير |
|--------|---------|
| `JudiciaryCustomersActions` model | قد يُقسَّم لـ 2 models |
| صفحة view القضية | تجمع من المصدرين |
| شاشة الـTimeline | تستهلك الـ VIEW الموحَّدة |
| تقارير القضاء | فحص الـ scope |
| `JudiciaryDefendantStage` | مراجعة منطق المراحل |
| `BatchCreateService::resolveCasePreparationActionId()` | يضع `scope='case'` على إجراء «تجهيز قضية» |
| `BatchCreateService::createOneCase()` | INSERT في `actions_log` بدلاً من تكرار INSERT لكل طرف |
| `BatchCreateService::canRevertBatch()` | يُبسَّط: لو `count(party_actions) > 0 OR count(case_actions) > 1` (الـ1 = تجهيز قضية) |
| Migration للبيانات القديمة | تحويل إجراءات `case_preparation` المُكرَّرة (اختياري) |

---

## الفوائد

- بيانات نظيفة: إجراء واحد على القضية = صف واحد.
- تايملاين أوضح.
- تقارير دقيقة.
- منطق التراجع أبسط وأقوى.
- يدعم سيناريوهات مستقبلية (قرارات قضائية على مستوى القضية).

---

## المخاطر

| الخطر | التخفيف |
|------|---------|
| كسر التقارير القائمة | كل التقارير تُمرَّر على VIEW الموحَّدة |
| فقدان بيانات قديمة | Migration تحويل اختياري لا يحذف |
| عمل إضافي على الـ frontend | المُكوّنات الحالية تستهلك VIEW موحَّدة |

---

## معايير القبول

- [ ] جدول `os_judiciary_actions_log` منشأ مع migration.
- [ ] عمود `scope` مُضاف لـ `os_judiciary_actions`.
- [ ] إجراء «تجهيز قضية» مُحدَّد بـ `scope='case'`.
- [ ] `BatchCreateService` يُسجّل في الجدول الصحيح.
- [ ] صفحة القضية تعرض timeline موحَّد.
- [ ] تقرير الإجراءات يفصل بين الـ scopes.
- [ ] منطق التراجع يستخدم البنية الجديدة.
- [ ] اختبار للسيناريوهات الـ17 من MVP يبقى ناجحاً.

---

## الجدول الزمني المقترح

تُنفَّذ بعد:
1. استقرار MVP في الإنتاج (≥ شهر).
2. جمع feedback من المستخدمين على Batch Create.
3. تحديد إن كانت Phase 2 تحتاج تغييرات إضافية بناءً على ذلك.
