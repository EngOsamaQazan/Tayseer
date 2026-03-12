# Tayseer ERP - دليل إعداد بيئة التشغيل المحلية

## المتطلبات الأساسية

| المتطلب | الحد الأدنى | ملاحظات |
|---------|------------|---------|
| نظام التشغيل | Windows 10 أو أحدث | 64-bit |
| المساحة الحرة | 2 GB على الأقل | للبرامج + قاعدة البيانات |
| الذاكرة (RAM) | 4 GB على الأقل | يُفضّل 8 GB |
| اتصال إنترنت | مطلوب | لتحميل البرامج والمكتبات |

---

## الطريقة السريعة (سكربت تلقائي)

### الخطوة 1: فتح PowerShell كمسؤول (Administrator)

1. اضغط على زر **Windows** في لوحة المفاتيح
2. اكتب **PowerShell**
3. اضغط بالزر الأيمن على **Windows PowerShell**
4. اختر **Run as Administrator** (تشغيل كمسؤول)

### الخطوة 2: السماح بتشغيل السكربتات

انسخ والصق الأمر التالي في نافذة PowerShell:

```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser -Force
```

### الخطوة 3: الانتقال لمجلد المشروع

```powershell
cd "C:\Users\PC\Desktop\Tayseer"
```

> **ملاحظة:** غيّر المسار إذا كان المشروع في مكان آخر على جهازك.

### الخطوة 4: تشغيل سكربت الإعداد

```powershell
.\setup-tayseer.ps1
```

السكربت سيقوم تلقائياً بـ:
- ✅ تحميل وتثبيت **Laragon** (خادم ويب محلي يشمل Apache + MySQL + PHP)
- ✅ تثبيت **Composer** (مدير حزم PHP)
- ✅ تثبيت جميع مكتبات PHP المطلوبة (`composer install`)
- ✅ إنشاء قواعد البيانات (`namaa_erp` و `namaa_jadal`)
- ✅ إنشاء مستخدم قاعدة البيانات
- ✅ استيراد ملفات SQL من مجلد `_backups/`
- ✅ إعداد ملفات التكوين المحلية
- ✅ إنشاء المجلدات المطلوبة
- ✅ تشغيل عمليات ترحيل قاعدة البيانات (migrations)
- ✅ إعداد رابط محلي `http://tayseer.test`

---

## الطريقة اليدوية (خطوة بخطوة)

### 1. تثبيت Laragon

1. حمّل Laragon Full من: [https://laragon.org/download/](https://laragon.org/download/)
2. ثبّته في المسار الافتراضي: `C:\laragon`
3. افتح Laragon واضغط **Start All**

> **Laragon يتضمن:** Apache, MySQL, PHP 8.x, Node.js, Git, وأدوات أخرى.

### 2. تثبيت Composer

1. افتح **Laragon Terminal** (من داخل Laragon)
2. تأكد أن Composer مثبت:

```bash
composer --version
```

إذا لم يكن مثبتاً:
- حمّله من: [https://getcomposer.org/download/](https://getcomposer.org/download/)
- أو من داخل Laragon: **Menu > Tools > Quick Add > composer**

### 3. تثبيت مكتبات PHP

```bash
cd C:\Users\PC\Desktop\Tayseer
composer install
```

> هذا الأمر سيقرأ ملف `composer.json` وينزّل جميع المكتبات المطلوبة في مجلد `vendor/`.

### 4. إعداد قاعدة البيانات

افتح **Laragon Terminal** أو **HeidiSQL** (مدمج مع Laragon) وقم بالتالي:

```sql
-- إنشاء قواعد البيانات
CREATE DATABASE IF NOT EXISTS namaa_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS namaa_jadal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- إنشاء مستخدم
CREATE USER IF NOT EXISTS 'tayseer_user'@'localhost' IDENTIFIED BY 'Tayseer@2026';
GRANT ALL PRIVILEGES ON namaa_erp.* TO 'tayseer_user'@'localhost';
GRANT ALL PRIVILEGES ON namaa_jadal.* TO 'tayseer_user'@'localhost';
FLUSH PRIVILEGES;
```

### 5. استيراد قاعدة البيانات

```bash
mysql -u root namaa_erp < "_backups\namaa_erp 14-02-2026.sql"
mysql -u root namaa_jadal < "_backups\namaa_jadal 14-02-2026.sql"
```

أو من **HeidiSQL**:
1. افتح HeidiSQL من Laragon
2. اتصل بالخادم المحلي (root بدون كلمة مرور)
3. اختر قاعدة البيانات المطلوبة
4. **File > Run SQL File** واختر ملف الـ SQL المناسب

### 6. إعداد ملفات التكوين

عدّل ملف `common/config/main-local.php`:

```php
<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=namaa_erp',
            'username' => 'tayseer_user',
            'password' => 'Tayseer@2026',
            'charset' => 'utf8',
            'tablePrefix' => 'os_',
            'enableSchemaCache' => true,
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            'useFileTransport' => true,
        ],
    ],
];
```

### 7. إنشاء المجلدات المطلوبة

```bash
mkdir backend\runtime
mkdir backend\web\assets
mkdir frontend\runtime
mkdir frontend\web\assets
mkdir console\runtime
```

### 8. تشغيل عمليات الترحيل (Migrations)

```bash
php yii migrate --interactive=0
```

---

## الوصول للنظام

| الخدمة | الرابط |
|--------|--------|
| لوحة التحكم (Backend) | [http://tayseer.test](http://tayseer.test) |
| الرابط البديل | [http://localhost/Tayseer/backend/web/](http://localhost/Tayseer/backend/web/) |
| نظام الحضور والانصراف (Mobile) | [http://tayseer.test/hr/field/mobile-login](http://tayseer.test/hr/field/mobile-login) |

---

## معلومات قاعدة البيانات

| الحقل | القيمة |
|-------|--------|
| Host | localhost |
| Port | 3306 |
| المستخدم | tayseer_user |
| كلمة المرور | Tayseer@2026 |
| قاعدة البيانات الرئيسية | namaa_erp |
| قاعدة البيانات الثانوية | namaa_jadal |
| بادئة الجداول | os_ |

---

## حل المشاكل الشائعة

### مشكلة: صفحة بيضاء فارغة
- تأكد من أن Laragon يعمل (Apache + MySQL أخضر)
- تحقق من ملفات السجل: `backend/runtime/logs/app.log`
- تأكد من وجود مجلد `vendor/` (شغّل `composer install`)

### مشكلة: خطأ في الاتصال بقاعدة البيانات
- تأكد من أن MySQL يعمل في Laragon
- تحقق من بيانات الاتصال في `common/config/main-local.php`
- تأكد من أن قاعدة البيانات مستوردة

### مشكلة: خطأ 404 في كل الصفحات
- تأكد من تفعيل mod_rewrite في Apache
- في Laragon: **Menu > Apache > modules > rewrite_module** (يجب أن يكون مفعّل)

### مشكلة: Execution Policy Error عند تشغيل السكربت
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser -Force
```

### مشكلة: Composer يفشل بسبب إصدار PHP
- تأكد من أن إصدار PHP >= 7.4
- في Laragon يمكنك تغيير إصدار PHP: **Menu > PHP > Version**

### مشكلة: لا يمكن الوصول لـ tayseer.test
- تأكد من إضافة السطر التالي لملف hosts:
```
127.0.0.1    tayseer.test
```
- مسار الملف: `C:\Windows\System32\drivers\etc\hosts`
- أو استخدم الرابط البديل: `http://localhost/Tayseer/backend/web/`

---

## هيكل المشروع

```
Tayseer/
├── backend/          ← التطبيق الرئيسي (لوحة التحكم)
│   ├── config/       ← إعدادات Backend
│   ├── controllers/  ← المتحكمات
│   ├── models/       ← النماذج
│   ├── modules/      ← الوحدات (HR, Inventory, etc.)
│   ├── views/        ← واجهات العرض
│   ├── web/          ← المجلد العام (DocumentRoot)
│   └── runtime/      ← ملفات مؤقتة وسجلات
├── common/           ← ملفات مشتركة
│   ├── config/       ← إعدادات مشتركة (قاعدة البيانات)
│   ├── helper/       ← أدوات مساعدة (الصلاحيات)
│   └── models/       ← نماذج مشتركة
├── console/          ← أوامر سطر الأوامر
│   └── migrations/   ← ملفات ترحيل قاعدة البيانات
├── frontend/         ← الواجهة الأمامية (غير مستخدمة حالياً)
├── vendor/           ← مكتبات PHP (يُنشئها Composer)
├── environments/     ← إعدادات البيئات المختلفة
├── _backups/         ← نسخ احتياطية لقاعدة البيانات
├── composer.json     ← تعريف مكتبات PHP
└── setup-tayseer.ps1 ← سكربت الإعداد التلقائي
```
