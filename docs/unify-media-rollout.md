# Unify Media — Rollout Safety Net

> هذا الملف يربط كل القطع التي بُنيت حول خطة `Unify Media`:
> النسخ الاحتياطي، بيئة staging، الـ feature flags، استراتيجية الفروع.
> الكود نفسه (`MediaService`, migrations, jobs) موثّق داخل ملفاته.

---

## نظرة سريعة على القطع

| طبقة | المكان | الغرض |
|---|---|---|
| Backups | `scripts/backup/` | لقطات يومية لـ DBs + media (rsync hard-links + verify drill) |
| Staging | `scripts/staging/` + `environments/prod_staging/` | بيئة QA معزولة تتحدث ليلياً من الإنتاج |
| Feature Flags | `common/config/params.php` + `common/helper/MediaFlags.php` | switch لكل controller adopter بدون redeploy |
| Branch Strategy | `.github/workflows/deploy-staging.yml` | فرع `unify-media` ينشر تلقائياً على staging فقط |
| Banner | `backend/views/layouts/main.php` | شريط أحمر دائم على staging — لا يمكن تجاهله |

---

## خطوات التنفيذ بالترتيب

### 1) قبل أي push للسيرفر — نسخ احتياطي أولي يدوياً

اتصل بالسيرفر:

```bash
ssh root@<server>
mkdir -p /var/backups/tayseer/manual
for site in jadal namaa watar majd; do
  cd /var/www/$site.aqssat.co
  DB=$(php -r '$c=require "common/config/main-local.php"; preg_match("/dbname=([^;]+)/",$c["components"]["db"]["dsn"],$m); echo $m[1];')
  USER=$(php -r '$c=require "common/config/main-local.php"; echo $c["components"]["db"]["username"];')
  PASS=$(php -r '$c=require "common/config/main-local.php"; echo $c["components"]["db"]["password"];')
  echo "Dumping $site → $DB"
  MYSQL_PWD="$PASS" mysqldump -u"$USER" --single-transaction --quick --routines --triggers --events --hex-blob "$DB" \
    | gzip > /var/backups/tayseer/manual/${site}-pre-unify-$(date +%F).sql.gz
  rsync -a backend/web/uploads/  /var/backups/tayseer/manual/${site}-uploads/
  rsync -a backend/web/images/   /var/backups/tayseer/manual/${site}-images/
done
ls -lh /var/backups/tayseer/manual/
```

> **لماذا يدوياً أولاً؟** السكربتات لم تُختبر على هذا السيرفر بعد.
> النسخة اليدوية تضمن لقطة آمنة قبل أن نحاول أي شيء آخر.

### 2) نشر الـ workflow الحالي + سكربتات الـ backup

```bash
git checkout main                              # نبقى على main حالياً
git add scripts/backup/ .gitattributes docs/unify-media-rollout.md
git commit -m "infra(backup): add daily snapshot + restore + verify scripts"
git push origin main
# هذا push آمن — السكربتات ملفات جديدة، لا تغير سلوك الإنتاج
```

ثم على السيرفر:

```bash
ssh root@<server>
sudo bash /var/www/jadal.aqssat.co/scripts/backup/install.sh
sudo nano /etc/default/tayseer-backup   # عدّل HEARTBEAT_URL + OFFSITE_RSYNC_TARGET
sudo /opt/tayseer-backup/daily-snapshot.sh   # تجربة فعلية
sudo /opt/tayseer-backup/verify-snapshot.sh
```

### 3) إعداد فرع `unify-media`

```powershell
# على ويندوز محلياً
git checkout -b unify-media
git add common/services/ common/contracts/ common/jobs/ common/helper/MediaFlags.php common/helper/Permissions.php common/config/main.php common/config/params.php console/migrations/ console/controllers/MediaBackfillController.php console/controllers/RecoverOrphanMediaController.php backend/controllers/MediaController.php backend/widgets/MediaHealthWidget.php backend/assets/MediaUploaderAsset.php backend/web/js/media-uploader/ backend/web/js/smart-media.js backend/web/js/smart-onboarding.js backend/web/js/customer-wizard/ environments/prod_staging/ scripts/staging/ .github/workflows/deploy-staging.yml backend/views/layouts/main.php docs/unify-media-rollout.md
git commit -m "feat(media): unify media subsystem — phase 0 + 1 + safety net"
git push -u origin unify-media
```

> **مهم:** لا تـ push لـ main بعد — فرع `unify-media` فقط.
> الـ workflow `deploy-staging.yml` سيلتقط الـ push ويبدأ نشر staging تلقائياً.

### 4) إعداد staging على السيرفر

```bash
# DNS أولاً: أضف A-record لـ staging.aqssat.co يشير لنفس IP السيرفر
# ثم:
ssh root@<server>
sudo bash /var/www/jadal.aqssat.co/scripts/staging/install.sh
# هذا السكربت ينفذ كل شي:
#   - استنساخ unify-media branch
#   - تطبيق environments/prod_staging
#   - إنشاء tayseer_staging schema فاضية
#   - تركيب Apache vhost + Let's Encrypt
#   - cron يومي للـ refresh من snapshot الإنتاج

# تحديث أولي يدوي (قبل ما الـ cron يشتغل ليلاً):
sudo /opt/tayseer-staging-refresh.sh

# افتح: https://staging.aqssat.co
# الدخول: qa@aqssat.co / Qa@2026
```

### 5) Phase 0: تطبيق الـ migrations على staging

الـ `deploy-staging.yml` يفعل هذا تلقائياً، لكن للتأكد:

```bash
ssh root@<server>
cd /var/www/staging.aqssat.co
sudo -u www-data php yii migrate/up --interactive=0
sudo -u www-data php yii media-backfill/initial      # DRY-RUN
sudo -u www-data php yii media-backfill/initial --apply --batch=200
```

اختبر على staging:
- ارفع صورة عميل من wizard
- ارفع مستند من smart_media
- ارفع صورة محامي
- تأكد من ظهور الصور بعد الحفظ
- تحقق من جدول `media_audit_log` (يجب أن يحتوي صفوف `store`)

### 6) Phase 0 على الإنتاج (بعد نجاح staging أسبوع)

```bash
# اعمل snapshot قسري قبل أي شي
ssh root@<server>
sudo /opt/tayseer-backup/daily-snapshot.sh
sudo /opt/tayseer-backup/verify-snapshot.sh

# ثم merge فرع unify-media → main (الـ migrations فقط، الـ flags كلها false في الإنتاج)
git checkout main
git merge --no-ff unify-media -m "feat(media): merge phase 0 — additive migrations only"
git push origin main
# deploy.yml يلتقط ويعمل migrations على الـ 4 sites
# الكود الجديد موجود لكن غير مفعّل (use_unified=false)
```

### 7) Phase 2+: تفعيل controller واحد كل أسبوع

هاي الخطوة اللي تفرّق الخطة الذكية عن المتسرعة:

```bash
ssh root@<server>
# اختر tenant واحد، abtdأ بأقل المخاطر (مثلاً majd)
nano /var/www/majd.aqssat.co/common/config/params-local.php
```

أضف:
```php
'media' => [
    'use_unified' => true,
    'controllers' => ['lawyers' => true],   // فقط lawyer photos أولاً
],
```

ثم:
```bash
cd /var/www/majd.aqssat.co
sudo -u www-data php yii cache/flush-all
sudo systemctl reload php8.5-fpm
# راقب /var/www/majd.aqssat.co/backend/runtime/logs/app.log + جدول media_audit_log أسبوع
```

إذا شي صار غلط: عدّل `params-local.php` رجوع لـ `false` + flush cache. **بدون redeploy، بدون migration rollback.**

عندما يستقر: كرر لـ controller التالي، ثم لـ tenant التالي.

---

## مخطط الاسترداد إذا شي صار خطأ

| العطل | إجراء فوري | مدة الاستعادة |
|---|---|---|
| Controller جديد يرفض رفع الملفات | `params-local.php` flag → false + flush | ثوانٍ |
| Migration كسرت جدول | `restore-snapshot.sh --site X --date YYYY-MM-DD --what db --apply --force` | 5–15 دقيقة |
| Backfill كتب بيانات غلط على `os_ImageManager` | restore جزئي (DB فقط) من snapshot الأمس | 5–10 دقائق |
| ضاع ملف صورة | restore جزئي (media فقط) | دقائق |
| السيرفر كله انفجر | استرداد VPS snapshot من المزود + استعادة آخر off-site backup | ساعة–ساعتان |

---

## مهام دورية (أضفها لـ calendar)

| كل | عمل |
|---|---|
| يومياً تلقائي | snapshot + verify (cron 03:17 + 03:55) |
| أسبوعياً | تحقق `tayseer-backup.log` لا يحتوي `FAILED` |
| شهرياً | عرض حجم `du -sh /var/backups/tayseer` للتأكد من نمو طبيعي |
| ربع سنوياً | restore drill كامل (انظر `scripts/backup/README.md`) |
| قبل كل phase rollout | snapshot يدوي قسري + verify |
