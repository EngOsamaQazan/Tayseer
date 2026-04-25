# دليل النقل من OVH إلى Contabo
# Migration Guide: OVH → Contabo VPS

## المتطلبات الأولية

```bash
pip install paramiko requests
```

---

## الخطوة 0: شراء VPS من Contabo

1. اذهب إلى [contabo.com](https://contabo.com/en/vps/)
2. اختر **Cloud VPS 10** (€4.50/شهر)
   - 4 vCPU, 8GB RAM, 75GB NVMe
3. اختر **Debian 12** كنظام تشغيل
4. اختر أقرب داتاسنتر (Europe - Germany مثلاً)
5. احفظ IP السيرفر الجديد وكلمة المرور

---

## الخطوة 1: تجهيز السيرفر الجديد

```bash
# عدّل المتغيرات في الملف أولاً:
# NEW_HOST, NEW_USER, NEW_PASS

python scripts/migration/01_setup_new_server.py
```

**يثبت:** PHP 8.3, MariaDB, Apache, Composer, UFW, Fail2ban, phpMyAdmin, Swap 2GB

---

## الخطوة 2: نقل البيانات

```bash
# عدّل NEW_HOST, NEW_PASS في الملف

python scripts/migration/02_migrate_data.py
```

**ينقل:**
- قاعدة بيانات `namaa_erp` (موقع نماء)
- قاعدة بيانات `namaa_jadal` (موقع جدل)
- كل ملفات الكود والمرفقات والصور
- يحدّث إعدادات الاتصال بقاعدة البيانات

---

## الخطوة 3: تحديث DNS

قبل تشغيل الخطوة التالية، حدّث سجلات DNS:

| النوع | الاسم | القيمة القديمة | القيمة الجديدة |
|-------|-------|----------------|----------------|
| A | jadal.aqssat.co | 54.38.236.112 | IP_الجديد |
| A | namaa.aqssat.co | 54.38.236.112 | IP_الجديد |

**ملاحظة:** انتظر 5-30 دقيقة لانتشار DNS (يمكن التحقق عبر [dnschecker.org](https://dnschecker.org))

---

## الخطوة 4: إعداد VirtualHosts + SSL

```bash
python scripts/migration/03_setup_vhosts_ssl.py
```

**ينشئ:**
- Apache VirtualHosts لكل موقع
- شهادات SSL عبر Let's Encrypt
- Security headers

---

## الخطوة 5: التحقق النهائي

```bash
python scripts/migration/04_verify_and_switch.py
```

**يتحقق من:**
- خدمات السيرفر (Apache, MariaDB, PHP)
- قواعد البيانات وعدد الجداول
- صلاحيات الملفات
- شهادات SSL
- الاستجابة HTTP
- Yii Console

---

## الخطوة 6: تحديث سكربتات النشر

```bash
python scripts/migration/05_update_deploy_scripts.py
```

---

## ما بعد النقل

- [ ] اختبر تسجيل الدخول على كلا الموقعين
- [ ] اختبر العقود، القضايا، الموارد البشرية
- [ ] اختبر رفع الملفات والصور
- [ ] راقب الأداء لمدة أسبوع
- [ ] غيّر كلمة مرور SSH للسيرفر الجديد
- [ ] الغِ اشتراك OVH بعد أسبوعين من الاستقرار

---

## معلومات مهمة

| البند | القيمة |
|-------|--------|
| السيرفر القديم | 54.38.236.112 (OVH) |
| مستخدم DB الجديد | tayseer_db |
| كلمة مرور DB | راجع `scripts/credentials.py` (`DB_PASS`) |
| المواقع | jadal.aqssat.co, namaa.aqssat.co |
| قواعد البيانات | namaa_erp, namaa_jadal |
| مسارات المواقع | /var/www/{site}.aqssat.co |

---

## في حالة الطوارئ (Rollback)

إذا حصلت مشكلة، ارجع DNS للسيرفر القديم:

| A | jadal.aqssat.co | 54.38.236.112 |
| A | namaa.aqssat.co | 54.38.236.112 |

السيرفر القديم لا يزال يعمل ولن يتأثر بالنقل.
