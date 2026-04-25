# Inventory Items Screen — Professional Redesign Plan

**Module:** `backend/modules/inventoryItems`
**Screen:** `/inventoryItems/items` (`actionItems`)
**Date:** 2026-04-25
**Owner:** Tayseer ERP — Frontend Engineering
**Status:** Approved → Ready for Implementation

---

## 1. الهدف الاستراتيجي (Strategic Goal)

تحويل شاشة "أصناف المخزون" من **جدول مسطّح** إلى **مركز عمليات مخزون احترافي** يخدم ثلاث وظائف:

1. **القراءة السريعة** — ماذا أملك؟ ما الذي ينقصني؟ ما قيمة مخزوني الحالي؟
2. **اتخاذ القرار** — ما الأصناف التي تحتاج اعتماد؟ ما هو تحت الحد الأدنى؟ ما معدّل دورانه ضعيف؟
3. **الإجراء الفوري** — إضافة، تعديل، اعتماد جماعي، حذف، تصدير، تحديث كمية inline.

**Success metric:** تقليل عدد النقرات اللازمة لاتخاذ قرار مخزوني من 5+ إلى ≤ 2 نقرات.

---

## 2. المعايير المرجعية (Standards Compliance Matrix)

| المعيار | البند | كيف يُطبَّق في التصميم |
|---|---|---|
| **ISO 9241-110:2020** | Interaction Principles | تناسق، تحكم، تسامح أخطاء، self-descriptiveness |
| **ISO 9241-112:2017** | Information Presentation | تجميع منطقي، تسلسل بصري، حد معلوماتي مدروس |
| **ISO 9241-125:2017** | Visual Design Guidance | تباين 4.5:1+، grid 8px، تسلسل هرمي |
| **ISO 9241-143:2012** | Forms & Dialogues | تجميع حقول، رسائل خطأ بقرب الحقل، required indicators |
| **ISO 9241-171:2008** | Accessibility | ARIA، keyboard nav، screen reader support |
| **ISO 9241-161:2016** | Visual Elements / WUI | controls موحَّدة، affordance واضح |
| **ISO 8601** | Date/Time format | `YYYY-MM-DD` لكل التواريخ |
| **WCAG 2.2 Level AA** | Web Content Accessibility | focus visible، contrast، resizable text، target size 24×24+ |
| **GS1 GDSN Inventory KPIs** | Industry standards | Stock turnover, days of supply, stockout rate |
| **Fitts's Law** | HCI heuristic | أزرار CTA كبيرة في مناطق سهلة الوصول |
| **Hick's Law** | Cognitive load | ≤ 7 خيارات في الفلاتر الأولية |
| **Nielsen's 10 Heuristics** | Usability | visibility of status, match real world, user control |

---

## 3. هندسة المعلومات (Information Architecture)

```
┌─────────────────────────────────────────────────────────┐
│  TABS BAR (موجود — لا يُعدَّل)                              │
├─────────────────────────────────────────────────────────┤
│  ① PAGE HEADER                                          │
│     عنوان + وصف + Live indicator + CTA رئيسية            │
├─────────────────────────────────────────────────────────┤
│  ② KPI STRIP — 6 بطاقات                                │
│     [إجمالي] [معتمد] [بانتظار] [ناقص] [نفد] [القيمة]      │
├─────────────────────────────────────────────────────────┤
│  ③ SAVED VIEWS BAR (Optional — حسب توفر مفضلات)         │
│     [⭐ كل الأصناف] [⚠️ تحت الحد] [⏳ بانتظار] [+حفظ هذا]  │
├─────────────────────────────────────────────────────────┤
│  ④ STATUS SEGMENT FILTERS (pills)                       │
│     [الكل] [معتمد] [بانتظار] [مرفوض] [تحت الحد] [نفد]    │
├─────────────────────────────────────────────────────────┤
│  ⑤ CATEGORY CHIPS (scrollable horizontal)               │
│     [كل التصنيفات] [أجهزة] [هواتف] [إكسسوارات] →         │
├─────────────────────────────────────────────────────────┤
│  ⑥ SEARCH + SORT + VIEW TOOLBAR (sticky)                │
│     [🔍 بحث ذكي] [↕ فرز] [بطاقات│جدول] [⬇ تصدير]        │
├─────────────────────────────────────────────────────────┤
│  ⑦ CONTENT AREA (Pjax — id="crud-datatable-pjax")       │
│     ┌─ Cards Grid (Default) ──────────────────────┐    │
│     │  Card  Card  Card  Card                      │    │
│     │  Card  Card  Card  Card                      │    │
│     │  Card  Card  Card  Card                      │    │
│     └──────────────────────────────────────────────┘    │
│     OR                                                   │
│     ┌─ Enhanced Table (GridView) ─────────────────┐    │
│     │  ☐  الصنف  باركود  مخزون  سعر  دوران  ...   │    │
│     └──────────────────────────────────────────────┘    │
├─────────────────────────────────────────────────────────┤
│  ⑧ FOOTER: ملخّص + Pagination                            │
├─────────────────────────────────────────────────────────┤
│  ⑨ FLOATING BULK ACTION BAR (يظهر عند تحديد ≥ 1)         │
│     [n محدد]  [✓ اعتماد]  [✗ رفض]  [🗑 حذف]  [إلغاء]    │
├─────────────────────────────────────────────────────────┤
│  ⑩ LIVE TOAST AREA (top-center) — تحديثات لحظية          │
└─────────────────────────────────────────────────────────┘
```

---

## 4. نظام التصميم البصري (Visual Design System)

### الباليتة (متوازنة مع هوية تيسير `#800020`)

```css
/* Primary Brand (Tayseer Burgundy) */
--inv-brand:        #800020;  /* للهوية فقط: روابط أساسية، عنوان الصفحة */
--inv-brand-dark:   #5c0017;
--inv-brand-light:  #a3324d;
--inv-accent:       #d4a853;  /* ذهبي — للنقاط المميزة */

/* Semantic (Material-inspired, AAA contrast on white) */
--inv-success:      #15803d;  --inv-success-bg: #dcfce7;
--inv-warning:      #b45309;  --inv-warning-bg: #fef3c7;
--inv-danger:       #b91c1c;  --inv-danger-bg:  #fee2e2;
--inv-info:         #075985;  --inv-info-bg:    #e0f2fe;
--inv-purple:       #6d28d9;  --inv-purple-bg:  #ede9fe;

/* Neutrals */
--inv-bg:           #f6f8fb;
--inv-surface:      #ffffff;
--inv-surface-2:    #f8fafc;
--inv-border:       #e2e8f0;
--inv-border-2:     #cbd5e1;
--inv-text-1:       #0f172a;
--inv-text-2:       #475569;
--inv-text-3:       #94a3b8;

/* Effects */
--inv-r-sm:  6px;
--inv-r-md:  10px;
--inv-r-lg:  14px;
--inv-shadow-sm: 0 1px 2px rgba(15,23,42,0.05);
--inv-shadow-md: 0 4px 12px rgba(15,23,42,0.06);
--inv-shadow-lg: 0 10px 30px rgba(15,23,42,0.10);
--inv-shadow-brand: 0 6px 20px rgba(128,0,32,0.18);

/* Motion */
--inv-tr-fast: 150ms ease-out;
--inv-tr-base: 220ms cubic-bezier(.2,.8,.2,1);
```

### Typography

```css
font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
/* Body 13.5px / 1.55 */
/* Headings: Noto Kufi Arabic 600-800 */
font-feature-settings: "tnum" 1;  /* tabular numerals للأرقام */
```

### Spacing & Grid

- **Base unit:** 4px
- **Spacing scale:** 4 / 8 / 12 / 16 / 20 / 24 / 32 / 48
- **Cards grid gap:** 16px
- **Section vertical spacing:** 20px

---

## 5. تشريح بطاقة الصنف (Item Card Anatomy)

### الهيكل

```
┌─────────────────────────────────────────────┐
│ ░░░░░░░░░░ status-stripe (top 3px) ░░░░░░░░ │
├─────────────────────────────────────────────┤
│ ☐  [📦]            [● شارة الحالة]      ⋮  │ ← header (40px)
│                                              │
│  اسم الصنف الكامل …                          │ ← title (15px / 700)
│  ▮ TYR-2026-0142  (mono ltr)                 │ ← barcode (12px)
│                                              │
│  ┌─ Stock Health ──────────────────────┐    │
│  │ ████████████░░░░░  142  ↻ 4.2x/سنة │    │ ← stock bar + turnover
│  │ المتوفر / الحد الأدنى 50            │    │
│  └─────────────────────────────────────┘    │
│                                              │
│  💰 السعر         45.00 د.أ                  │
│  📊 قيمة مخزون    6,390.00 د.أ               │
│  📂 التصنيف       [أجهزة]                    │
│  🕒 محدّث منذ      3 أيام                     │
│                                              │
├─────────────────────────────────────────────┤
│  [👁 عرض]  [✎ تعديل]  [✓ اعتماد]  [🗑]      │ ← actions footer
└─────────────────────────────────────────────┘
```

### Variants حسب الحالة

| الحالة | الـ stripe | الخلفية | المؤشرات |
|---|---|---|---|
| Approved + Healthy | أخضر #15803d | white | شارة "معتمد" خضراء |
| Approved + Low | كهرماني #b45309 | tint #fffbeb | تحذير + watermark "ناقص" |
| Approved + Out | أحمر #b91c1c | tint #fef2f2 | watermark "نفد" قطري شفاف |
| Pending | كهرماني نابض | white | شارة نابضة + زر اعتماد بارز |
| Rejected | أحمر | white + opacity 0.85 | tooltip بسبب الرفض |
| Draft | رمادي #94a3b8 | white | tag "مسودة" |

### Stock Turnover Chip (جديد)

- **الحساب:** `total_movements_out_last_90d / avg_stock_last_90d`
- **العرض:** `↻ 4.2× / سنة` بجانب شريط المخزون
- **الترميز اللوني:**
  - Healthy ≥ 4×/سنة → أخضر
  - Slow 1–4×/سنة → كهرماني
  - Stale < 1×/سنة → رمادي مع تنبيه
- **يحتاج DB View جديد:** `vw_inventory_item_turnover` (في خطة منفصلة)
- **Fallback:** إن لم يتوفر → عرض شارة "—" بدون لون.

### Accessibility per Card

```html
<article role="article"
         aria-label="صنف آيفون 15 برو، باركود TYR-2026-0142، متوفر 142 قطعة، الحالة معتمد"
         tabindex="0">
```

- Focus ring واضح 2px solid `#800020`.
- Keyboard: `Enter` يفتح "عرض"، `E` يفتح "تعديل" (اختصار).
- جميع الأزرار `aria-label` + `title`.

---

## 6. الميزات التفاعلية (Interaction Design)

### 6.1 Saved Views (مفضلات)

**التصميم:**
- شريط أعلى الفلاتر يحوي pills مع نجمة.
- زر `+ حفظ هذا العرض` يحفظ التركيبة الحالية من الفلاتر.

**التخزين:**
- جدول جديد: `os_inventory_saved_views` (`id`, `user_id`, `name`, `query`, `is_default`, `created_at`).
- أو **تخزين محلي بـ `localStorage`** كحل سريع (ننفّذه أولاً).

**Built-in defaults (للجميع):**
- ⭐ كل الأصناف
- ⚠️ تحت الحد الأدنى
- 🔴 نافد المخزون
- ⏳ بانتظار الاعتماد
- 🆕 المضافة هذا الأسبوع

**Decision:** نبدأ بـ localStorage + 5 views افتراضية من السيرفر. توسعة لاحقاً لجدول DB.

### 6.2 Real-time Updates (Polling)

**الاستراتيجية:**
- **Polling خفيف** كل 30 ثانية (لا WebSocket لتجنّب تعقيد البنية التحتية).
- **Endpoint جديد:** `GET /inventoryItems/inventory-items/items-stream`
  - يُرجع: `{ updated_at, kpi: {...}, changed_ids: [123, 456], removed_ids: [789] }`.
- **Diff-based update:**
  - تحديث KPIs بدون reload.
  - علامة "✦ جديد" على البطاقات المتغيرة لمدة 5 ثوانٍ.
  - إشعار toast: "تم تحديث 3 أصناف منذ آخر زيارتك".

**التحكم:**
- زر `⏸ إيقاف التحديث المباشر` في الترويسة.
- مؤشر بصري: نقطة خضراء نابضة "● مباشر" أو رمادية "⊝ متوقّف".

**الكفاءة:**
- إرسال `If-Modified-Since` header.
- إيقاف polling عندما `document.hidden === true`.

### 6.3 Filters & Search

| العنصر | السلوك |
|---|---|
| **Status pills** | Pjax reload فوري — يُحدّث query param `InventoryItemsSearch[status]` |
| **Category chips** | Pjax reload فوري — يُحدّث `InventoryItemsSearch[category]` |
| **Search input** | debounce 350ms ثم Pjax reload — يبحث في `item_name` + `item_barcode` + `serial_number` |
| **Sort dropdown** | الاسم / السعر / الكمية / التاريخ — تصاعدي/تنازلي |
| **View toggle** | Cards ⇄ Table — يحفظ في query `view=cards|table` |
| **Clear all** | زر "مسح المرشحات" يظهر عند وجود فلاتر مفعّلة |

### 6.4 Bulk Actions

- Checkbox على كل بطاقة (top-left).
- زر "تحديد الكل في الصفحة" في التولبار.
- شريط عائم سفلي (sticky bottom) يظهر عند `selectedCount ≥ 1`:
  - عداد المحدّد
  - أزرار: اعتماد / رفض / حذف / تصدير المحدد فقط
  - زر "إلغاء التحديد"

### 6.5 Empty States

| الحالة | الرسالة | CTA |
|---|---|---|
| لا أصناف بعد | "ابدأ ببناء مخزونك" | "+ إضافة صنف" / "📦 إضافة دفعة" |
| لا نتائج للبحث | "لا توجد أصناف تطابق البحث" | "مسح المرشحات" |
| لا أصناف ناقصة | "كل المخزون فوق الحد الأدنى ✓" | — |

### 6.6 Loading States

- **Skeleton cards** (لا spinner مزعج) عند Pjax reload.
- 6 بطاقات skeleton بأبعاد متطابقة للبطاقة الحقيقية.

---

## 7. الاستجابة (Responsive Strategy)

| Breakpoint | Cards Columns | KPI Strip | Filters |
|---|---|---|---|
| `≥ 1400px` | 4 | 6 بطاقات | كل الفلاتر مرئية |
| `1100-1399` | 3 | 6 بطاقات | كل الفلاتر مرئية |
| `768-1099` | 2 | 3 بطاقات / صف (صفّان) | فلاتر مكدّسة |
| `< 768px` | 1 | scroll أفقي | accordion للفلاتر المتقدمة |

- **Sticky toolbar** يصبح `position: sticky; top: 0` على ≥ 768px.
- **Bulk bar** على الموبايل يظهر full-width في الأسفل.

---

## 8. الأداء (Performance Budget)

| Metric | Target |
|---|---|
| Initial render | < 1s على 500 صنف |
| Pjax reload | < 400ms |
| Polling roundtrip | < 200ms |
| CSS bundle | < 12KB scoped |
| JS bundle | < 8KB (vanilla — no frameworks) |
| FCP (First Contentful Paint) | < 1.5s |
| CLS (Cumulative Layout Shift) | 0 (skeleton matches real card) |

**تكتيكات:**
- استعلام KPIs مُجمّع واحد عبر `GROUP BY`.
- CSS مضمَّن داخل scope `.inv-items-pro`.
- لا framework JS — vanilla + Alpine.js (موجود).
- Lazy load images (لو أُضيفت لاحقاً).
- `Cache-Control: private, max-age=10` على endpoint الـ stream.

---

## 9. RBAC & Security

```
INVITEM_VIEW   → الشاشة + البطاقات + التصدير + الفلاتر
INVITEM_CREATE → زر "إضافة صنف" + "إضافة دفعة" + "حفظ عرض"
INVITEM_UPDATE → "تعديل" + "اعتماد/رفض" + "اعتماد جماعي" + adjustment inline
INVITEM_DELETE → "حذف" + "حذف جماعي"
```

كل زر يُلفّ بـ `Permissions::can(...)`.

---

## 10. الملفات المتأثرة (File-Level Changes)

| الملف | النوع | الوصف |
|---|---|---|
| `controllers/InventoryItemsController.php` | ✏️ تعديل | `actionItems` (✅ تم)، `actionItemsStream` (جديد) |
| `views/inventory-items/items.php` | 🔄 إعادة كتابة | الشاشة الكاملة |
| `views/inventory-items/_card.php` | ✨ جديد | قالب بطاقة الصنف |
| `views/inventory-items/_columns.php` | ✏️ تنقيح | تحسين أعمدة وضع الجدول |
| `web/css/inv-items-pro.css` | ✨ جديد | CSS منعزل scoped |
| `web/js/inv-items-pro.js` | ✨ جديد | polling + saved views + bulk + interactions |
| `models/InventoryItemsSearch.php` | ✏️ تعديل | إضافة فلاتر `low_stock`, `out_of_stock`, `sort` |

> **ملاحظة هامة:** ملفات CSS/JS الجديدة في `backend/web/css` و `backend/web/js` (لأنها أصول واجهة الباك-إند، وليست سكربتات utility — لا تذهب إلى `scripts/`).

---

## 11. خطة التنفيذ (Execution Roadmap)

### المرحلة 1 — Foundation (Static UI)
- [ ] **1.1** إنشاء `inv-items-pro.css` — متغيّرات + reset + grid + typography
- [ ] **1.2** Page header + KPI strip (6 بطاقات)
- [ ] **1.3** Status segment pills + Category chips
- [ ] **1.4** Toolbar (search + sort + view toggle + export)
- [ ] **1.5** Empty state component

### المرحلة 2 — Cards Mode
- [ ] **2.1** قالب `_card.php` كامل
- [ ] **2.2** Stock health bar (CSS-only)
- [ ] **2.3** Stock turnover chip (مع fallback)
- [ ] **2.4** Cards grid + responsive breakpoints
- [ ] **2.5** Skeleton loading state

### المرحلة 3 — Table Mode
- [ ] **3.1** تحديث `_columns.php` — إضافة turnover column + تحسين stock + price
- [ ] **3.2** Toggle smooth بين Cards/Table بدون reload الصفحة كاملة

### المرحلة 4 — Interactions
- [ ] **4.1** `inv-items-pro.js` foundation
- [ ] **4.2** Bulk selection + floating action bar
- [ ] **4.3** Saved views via localStorage + 5 defaults
- [ ] **4.4** Search debounce + Pjax integration
- [ ] **4.5** Sort dropdown handler

### المرحلة 5 — Real-time
- [ ] **5.1** Endpoint `actionItemsStream` في الكنترولر
- [ ] **5.2** Polling client (every 30s, pause when hidden)
- [ ] **5.3** KPI live update + diff highlight + toast
- [ ] **5.4** Live indicator UI (● مباشر / ⊝ متوقف)

### المرحلة 6 — Stock Turnover
- [ ] **6.1** SQL: `database/sql/views/vw_inventory_item_turnover.sql` (يُنشأ منفصلاً)
- [ ] **6.2** Method في `InventoryItems::getTurnover()` مع fallback
- [ ] **6.3** عرض في البطاقة + الجدول

### المرحلة 7 — QA
- [ ] **7.1** Lint check
- [ ] **7.2** Responsive sweep (360px → 1920px)
- [ ] **7.3** Keyboard navigation test
- [ ] **7.4** Screen reader smoke test (NVDA/VoiceOver)
- [ ] **7.5** Performance check (Lighthouse)

---

## 12. معايير القبول النهائية (Definition of Done)

- [ ] جميع KPIs تُحسب بشكل صحيح ومتوافقة مع الفلاتر المفعّلة.
- [ ] تباين الألوان ≥ 4.5:1 لكل النصوص (تم التحقق في الباليتة).
- [ ] التنقّل الكامل بلوحة المفاتيح بدون فأرة.
- [ ] قارئ الشاشة يقرأ كل بطاقة بشكل دلالي صحيح.
- [ ] متجاوبة من 360px → 1920px بدون كسر.
- [ ] لا يكسر `modal-remote` أو `Pjax` أو "تصدير".
- [ ] Real-time polling يعمل ولا يُحمّل السيرفر (< 200ms response).
- [ ] Saved views تستمر بعد إعادة فتح المتصفح.
- [ ] Stock turnover يعرض رقم حقيقي أو fallback أنيق.
- [ ] احترام كامل لـ `Permissions::INVITEM_*`.
- [ ] صفر `console.error` و صفر تحذيرات linter.
- [ ] الصفحة تعمل على Chrome/Edge/Firefox/Safari آخر إصدارين.

---

## 13. خارج النطاق (Out of Scope — للجولات القادمة)

- ❌ رفع صور للأصناف (يحتاج storage strategy).
- ❌ Barcode scanner integration (يحتاج جهاز).
- ❌ Multi-warehouse view (تخزين واحد حالياً).
- ❌ Forecast / AI suggestions.
- ❌ Inventory transfer between locations (شاشة منفصلة).

---

## 14. المخاطر والتخفيفات

| الخطر | الاحتمال | الأثر | التخفيف |
|---|---|---|---|
| Polling يُحمّل السيرفر مع 100+ مستخدم متزامن | متوسط | متوسط | استعلام cache 10s + If-Modified-Since header |
| Pjax يكسر مع Cards mode | منخفض | عالي | تطابق container ID `#crud-datatable-pjax` |
| Turnover view يبطئ الاستعلام | متوسط | متوسط | View مع index + computed cache يومي |
| Saved views تفقد البيانات عند مسح المتصفح | محتوم | منخفض | تنبيه + خطة upgrade لجدول DB لاحقاً |
| لا يوجد بيانات حركة كافية لحساب turnover | عالي | منخفض | fallback "—" مع tooltip توضيحي |

---

## 15. مرجع التتبع (Sign-off)

- **Approved framework:** ✅ User confirmed 2026-04-25
- **Default view:** Cards
- **Optional features included:** Stock turnover chip, Saved views, Real-time polling
- **Brand alignment:** Tayseer Burgundy `#800020` كهوية، مع semantic palette متوازنة
- **Implementation start:** فور الموافقة على هذا الملف

---

**End of Plan.**
