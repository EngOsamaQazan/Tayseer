# Tayseer ERP — Design Tokens Reference

> مرجع موحد لجميع متغيرات CSS المستخدمة في نظام تيسير

## Color Palettes

النظام يدعم 6 ألوان رئيسية عبر `data-theme-color`:
`burgundy` (افتراضي) | `ocean` | `forest` | `sunset` | `royal` | `slate`

كل لوحة ألوان تحدد المتغيرات التالية في `tayseer-themes.css`:

| Token | Light | Dark | Usage |
|-------|-------|------|-------|
| `--t-primary` | `#800020` | `#e06080` | اللون الأساسي |
| `--t-primary-rgb` | `128, 0, 32` | `224, 96, 128` | لاستخدام rgba() |
| `--t-primary-hover` | `#6a001a` | `#c8506c` | حالة hover |
| `--t-primary-emphasis` | `#5a0016` | `#f0a0b8` | نص مؤكد |
| `--t-primary-subtle-bg` | `rgba(128,0,32,.08)` | `rgba(224,96,128,.15)` | خلفيات خفيفة |
| `--t-primary-subtle-border` | `rgba(128,0,32,.2)` | `rgba(224,96,128,.3)` | حدود خفيفة |
| `--t-accent` | `#c8a04a` | `#d4b060` | اللون الثانوي/ذهبي |
| `--t-sidebar-bg` | `#2a0810` | `#1e0510` | خلفية الشريط الجانبي |

## Legacy Design Tokens (`--clr-*`)

مُعرّفة في `tayseer-vuexy.css` — تُستخدم في CSS القديم:

### Colors
| Token | Value | Usage |
|-------|-------|-------|
| `--clr-primary` | `#800020` | اللون الأساسي |
| `--clr-primary-900` → `--clr-primary-50` | Scale | تدرج اللون الأساسي (9 مستويات) |
| `--clr-accent` | `#c8a04a` | اللون الذهبي المميز |
| `--clr-accent-light` | `#e8d08a` | ذهبي فاتح |
| `--clr-accent-dark` | `#9a7a2a` | ذهبي غامق |
| `--clr-bg` | `#f4f6f9` | خلفية الصفحة |
| `--clr-surface` | `#ffffff` | خلفية البطاقات |
| `--clr-border` | `#e0e0e0` | لون الحدود |
| `--clr-text` | `#2c2c2c` | نص أساسي |
| `--clr-text-muted` | `#6c757d` | نص ثانوي |
| `--clr-text-light` | `#999` | نص فاتح |
| `--clr-text-on-primary` | `#ffffff` | نص على خلفية أساسية |
| `--clr-success` | `#28a745` | نجاح |
| `--clr-warning` | `#ffc107` | تحذير |
| `--clr-danger` | `#dc3545` | خطأ |
| `--clr-info` | `#17a2b8` | معلومة |

### Shadows
| Token | Value | Usage |
|-------|-------|-------|
| `--shadow-sm` | `0 1px 3px rgba(0,0,0,0.08)` | بطاقات عادية |
| `--shadow-md` | `0 4px 12px rgba(0,0,0,0.1)` | بطاقات مرفوعة / hover |
| `--shadow-lg` | `0 8px 30px rgba(0,0,0,0.12)` | نوافذ / dropdowns |

### Border Radius
| Token | Value | Usage |
|-------|-------|-------|
| `--radius-sm` | `6px` | حقول الإدخال، أزرار صغيرة |
| `--radius-md` | `10px` | بطاقات، panels |
| `--radius-lg` | `16px` | modals، cards كبيرة |

### Typography
| Token | Value | Usage |
|-------|-------|-------|
| `--font-size-xs` | `11px` | تسميات صغيرة |
| `--font-size-sm` | `13px` | نص ثانوي |
| `--font-size-base` | `14px` | نص أساسي |
| `--font-size-md` | `16px` | عناوين فرعية |
| `--font-size-lg` | `20px` | عناوين |
| `--font-size-xl` | `26px` | عناوين رئيسية |

### Animation
| Token | Value | Usage |
|-------|-------|-------|
| `--transition` | `all 0.25s ease` | انتقال عام |

## Bootstrap 5 Overrides (`--bs-*`)

مُعرّفة في `tayseer-vuexy.css`:

| Token | Value | Notes |
|-------|-------|-------|
| `--bs-primary` | `#800020` | يستخدمه Bootstrap للأزرار/الروابط |
| `--bs-primary-rgb` | `128, 0, 32` | لـ rgba |
| `--bs-font-sans-serif` | `'Cairo', sans-serif` | خط النظام |
| `--bs-body-font-family` | `'Cairo', sans-serif` | خط الجسم |
| `--bs-link-color` | `#800020` | لون الروابط |

## GridView Tokens (`--ty-*`)

مُعرّفة في `tayseer-gridview-responsive.css`:

| Token | Value | Usage |
|-------|-------|-------|
| `--ty-clr-primary` | `var(--t-primary, #800020)` | GridView primary |
| `--ty-clr-primary-dark` | `var(--t-primary-hover, #650019)` | GridView hover |
| `--ty-clr-surface` | `#fff` | خلفية الصفوف |
| `--ty-clr-bg` | `#FAFBFC` | خلفية الجدول |
| `--ty-clr-border` | `#E2E8F0` | حدود الجدول |
| `--ty-clr-border-light` | `#F1F5F9` | حدود خفيفة |
| `--ty-clr-text` | `#1E293B` | نص أساسي |
| `--ty-clr-text-muted` | `#64748B` | عناوين الأعمدة |
| `--ty-clr-text-light` | `#94A3B8` | نص فاتح |
| `--ty-radius-sm` | `6px` | أزرار، حقول |
| `--ty-radius-md` | `10px` | بطاقات |
| `--ty-radius-lg` | `12px` | panels |
| `--ty-shadow-card` | `0 1px 3px rgba(0,0,0,.04)` | ظل بطاقة |
| `--ty-font` | `'Tajawal','Cairo',sans-serif` | خط GridView |

## OCP Panel Tokens (`--ocp-*`)

مُعرّفة في `ocp.css` — لوحة تحكم العقد:

| Token | Value | Usage |
|-------|-------|-------|
| `--ocp-primary` | `var(--t-primary, #800020)` | اللون الأساسي |
| `--ocp-success` | `#16A34A` | حالة ناجحة |
| `--ocp-danger` | `#DC2626` | حالة خطيرة |
| `--ocp-warning` | `#D97706` | تحذير |
| `--ocp-font-size-sm` | `13px` | نص صغير |
| `--ocp-space-sm` | `8px` | مسافة صغيرة |
| `--ocp-space-md` | `12px` | مسافة متوسطة |
| `--ocp-space-lg` | `16px` | مسافة كبيرة |
| `--ocp-space-xl` | `20px` | مسافة أكبر |

## Usage Guidelines

### Selecting the Right Token

1. **لـ CSS جديد**: استخدم `--bs-*` tokens (Bootstrap 5) أو `--t-*` (Theme System)
2. **لـ CSS موروث**: استخدم `--clr-*` tokens
3. **لـ GridView**: استخدم `--ty-*` tokens
4. **لـ OCP Panel**: استخدم `--ocp-*` tokens

### Dark Mode

- لا تستخدم ألوان ثابتة مثل `#fff` أو `#333`
- استخدم `var(--bs-body-bg)` بدلاً من `#fff`
- استخدم `var(--bs-body-color)` بدلاً من `#333`
- استخدم `var(--bs-card-bg)` لخلفية البطاقات
- استخدم `var(--bs-border-color)` للحدود

### RTL

- استخدم `margin-inline-start` بدلاً من `margin-left`
- استخدم `padding-inline-end` بدلاً من `padding-right`
- `border-right` → مستخدم عمداً لبطاقات KPI (دائماً على اليمين في RTL)

---

**آخر تحديث:** 2026-04-16
