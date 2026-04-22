# Tayseer Backup System

> الشبكة الأمنية تحت rollout الـ Unify Media. تحمي ما لا يرجع: قواعد البيانات وملفات الميديا.
> Safety net for the Unify Media rollout. Protects what `git reset` can't bring back.

---

## ما يحميه هذا النظام / What this protects

| الأصل / Asset | لماذا حساس / Why it matters |
|---|---|
| 4 قواعد بيانات MySQL لكل tenant | كل سجل عميل، عقد، حركة، نشاط مالي |
| `backend/web/uploads/` لكل tenant | مستندات العملاء المرفوعة |
| `backend/web/images/` لكل tenant | صور `os_ImageManager` (هذا اللي خطة Unify Media تعدل عليه) |
| `frontend/web/uploads/` لكل tenant | مرفقات الواجهة العامة |

ما يحميه: ❌ الكود (محفوظ في git) ❌ vendor/ (يُعاد تركيبه بـ composer) ❌ runtime/cache (يتولّد).

---

## البنية / Architecture

```
/var/backups/tayseer/
├── daily/
│   ├── 2026-04-22/
│   │   ├── jadal/{db.sql.gz, media/..., manifest.json}
│   │   ├── namaa/{db.sql.gz, media/..., manifest.json}
│   │   ├── watar/...
│   │   └── majd/...
│   ├── 2026-04-21/...   ← rsync --link-dest يجعل الملفات المشتركة inodes واحدة
│   └── ...              (الاحتفاظ: KEEP_DAILY يوم، افتراضي 14)
├── weekly/2026-W17/  → symlink لأحد الـ Sundays
├── monthly/2026-04/  → symlink لأول كل شهر
└── pre-restore/      ← لقطات أمان قبل كل عملية استعادة
```

**التكلفة الفعلية:** ميديا 50 GB تنمو بـ 100 MB/يوم = ~ 50 GB أول يوم + 100 MB/يوم بعدها (بفضل hard-links). 14 يوم ≈ 51.4 GB لا 700 GB.

---

## التركيب على السيرفر / Server install

```bash
# على VPS، بعد ما يكون deploy.yml نزّل المشروع لـ /var/www
sudo bash /var/www/jadal.aqssat.co/scripts/backup/install.sh

# عدّل الإعدادات (على الأقل HEARTBEAT_URL و OFFSITE_RSYNC_TARGET)
sudo nano /etc/default/tayseer-backup

# تجربة فعلية
sudo /opt/tayseer-backup/daily-snapshot.sh
sudo /opt/tayseer-backup/verify-snapshot.sh
```

التركيب:
- يضع السكربتات في `/opt/tayseer-backup/` بصلاحيات `root:root 0750`
- ينشئ `/etc/cron.d/tayseer-backup` (يومي 03:17 + verify 03:55)
- ينشئ `/etc/logrotate.d/tayseer-backup` (12 أسبوع)
- ينشئ stub لـ `/etc/default/tayseer-backup` (لا يتم استبداله إن كان موجوداً، فإعداداتك تنجو من ترقية السكربت)

---

## الإعدادات / Configuration knobs

كل المتغيرات تعدّل في `/etc/default/tayseer-backup`:

| Var | Default | الوصف |
|---|---|---|
| `BACKUP_ROOT` | `/var/backups/tayseer` | المسار الجذر للنسخ |
| `SITES` | `jadal namaa watar majd` | المواقع المشمولة |
| `KEEP_DAILY` | `14` | عدد النسخ اليومية |
| `KEEP_WEEKLY` | `8` | عدد النسخ الأسبوعية (يوم الأحد) |
| `KEEP_MONTHLY` | `12` | عدد النسخ الشهرية (أول الشهر) |
| `BACKUP_COMPRESSOR` | `gzip` | `gzip` أو `zstd` (أصغر وأسرع) |
| `HEARTBEAT_URL` | (فارغ) | dead-man-switch URL مثل HealthChecks.io |
| `OFFSITE_RSYNC_TARGET` | (فارغ) | `user@host:/path` لدفع النسخ خارج السيرفر |
| `OFFSITE_SSH_KEY` | `/root/.ssh/id_ed25519_backup` | مفتاح SSH للـ off-site |

> **نصيحة قوية:** فعّل `OFFSITE_RSYNC_TARGET` ولو لـ box رخيص. backup على نفس السيرفر = ليس backup إذا فُقد السيرفر.

---

## الاستعادة / Restoring

### حالة 1 — اختبار الاستعادة على staging (افعلها كل ربع سنة)

```bash
# اعرض النسخ المتاحة
sudo /opt/tayseer-backup/restore-snapshot.sh --list

# جرّب استعادة DB إلى staging (آمن، لا يلمس الإنتاج)
sudo /opt/tayseer-backup/restore-snapshot.sh \
  --site namaa --date 2026-04-22 --what db --target staging --apply

# تحقق من العدادات
mysql -e "SELECT COUNT(*) FROM os_customer" namaa_staging
```

### حالة 2 — كارثة فعلية (دقّق مرتين قبل التنفيذ)

```bash
# DRY-RUN أولاً (افتراضي)
sudo /opt/tayseer-backup/restore-snapshot.sh \
  --site namaa --date 2026-04-22 --what all

# يطلب --force عشان الإنتاج
sudo /opt/tayseer-backup/restore-snapshot.sh \
  --site namaa --date 2026-04-22 --what all --apply --force
```

السكربت **يأخذ نسخة من الحالة الراهنة قبل أي شيء** ويحفظها في `/var/backups/tayseer/pre-restore/` — حتى لو الاستعادة كانت غلط، عندك طريق رجوع.

---

## الـ Restore Drill ربع السنوي / Quarterly drill

```bash
# 1. اعمل snapshot جديد قسري
sudo /opt/tayseer-backup/daily-snapshot.sh

# 2. استعد آخر snapshot على staging
TODAY=$(date +%F)
sudo /opt/tayseer-backup/restore-snapshot.sh \
  --site namaa --date $TODAY --what all --target staging --apply

# 3. افتح staging.aqssat.co — تحقق من:
#    - تسجيل الدخول
#    - ظهور آخر العملاء
#    - تحميل صورة عميل (يجب أن تظهر)
#    - ظهور آخر الحركات في تقرير اليوم

# 4. وثّق التاريخ في docs/disaster-recovery-log.md
```

إذا فشل الـ drill — **النسخ ليست نسخاً**. أصلح المشكلة قبل أي عملية انشار خطرة على الإنتاج.

---

## الربط مع خطة Unify Media

السكربت ينفّذ المسار **أ** في خطة الـ rollout الكاملة:

| Phase | Backup needed before |
|---|---|
| Phase 0 (migration `m260419_100001` + `m260419_100002`) | snapshot كامل + verify |
| Phase 1 (`MediaService` + `LocalDiskDriver`) | snapshot يومي حي |
| Phase 2+ (تبديل كل controller لـ `MediaService`) | snapshot قبل الـ deploy + feature flag |
| Phase 5 (`media-backfill/initial --apply`) | snapshot + drill على staging أولاً |
| Phase 8 (إسقاط الأعمدة legacy `customer_id` / `contractId`) | snapshot + 30 يوم من الـ daily backups |

---

## استكشاف الأخطاء / Troubleshooting

| العَرَض | السبب الغالب | العلاج |
|---|---|---|
| `DB FAILED` في اللوج | المستخدم لا يملك `LOCK TABLES` أو `EVENT` | امنحه: `GRANT LOCK TABLES, EVENT ON *.* TO 'osama'@'localhost';` |
| `MEDIA FAILED` | rsync ما قدر يقرأ نسخة قديمة | تحقق من ownership، شغّل `chown -R www-data:www-data` |
| الـ disk امتلأ بسرعة | الـ `--link-dest` انكسر (غالباً غيّرت `BACKUP_ROOT`) | احذف `daily/*` كلها والسكربت يعيد البناء |
| `Permission denied` على `/etc/default/tayseer-backup` | السكربت يشتغل بمستخدم غير root | شغّل بـ sudo (cron يفعل أوتوماتيكياً) |
