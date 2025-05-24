<div dir="rtl">

# دليل تثبيت وتشغيل منصة تيسير

## 📋 المتطلبات الأساسية

### 1. البرامج المطلوبة

قبل البدء، تأكد من تثبيت البرامج التالية على جهازك:

#### أ. Node.js و npm
- **الإصدار المطلوب**: Node.js 18.0.0 أو أحدث
- **رابط التحميل**: https://nodejs.org/
- **التحقق من التثبيت**:
  ```bash
  node --version
  npm --version
  ```

#### ب. Docker و Docker Compose
- **Docker Desktop** (يتضمن Docker Compose)
- **رابط التحميل**: https://www.docker.com/products/docker-desktop/
- **التحقق من التثبيت**:
  ```bash
  docker --version
  docker-compose --version
  ```

#### ج. Git
- **رابط التحميل**: https://git-scm.com/
- **التحقق من التثبيت**:
  ```bash
  git --version
  ```

#### د. محرر أكواد (اختياري)
- **Visual Studio Code** (موصى به): https://code.visualstudio.com/
- أو أي محرر آخر تفضله

### 2. المتطلبات النظامية

- **ذاكرة RAM**: 8 جيجابايت على الأقل (16 جيجابايت موصى به)
- **مساحة القرص**: 10 جيجابايت على الأقل
- **المعالج**: معالج متعدد النوى
- **نظام التشغيل**: Windows 10/11، macOS، أو Linux

## 🚀 خطوات التثبيت

### 1. استنساخ المشروع

```bash
# استنساخ المستودع
git clone https://github.com/your-org/tayseer-platform.git
cd tayseer-platform
```

### 2. تثبيت التبعيات

```bash
# تثبيت التبعيات الرئيسية
npm install

# تثبيت تبعيات المشاريع الفرعية
npm install --workspaces
```

### 3. إعداد متغيرات البيئة

#### أ. للخادم الخلفي (Backend)

```bash
# نسخ ملف البيئة النموذجي
cp backend/.env.example backend/.env

# تعديل الملف حسب احتياجاتك
# يمكنك استخدام المحرر المفضل لديك
```

#### ب. للواجهة الأمامية (Frontend)

```bash
# إنشاء ملف البيئة
echo "REACT_APP_API_URL=http://localhost:3000" > frontend/.env
```

### 4. تشغيل قواعد البيانات والخدمات

```bash
# تشغيل جميع الخدمات باستخدام Docker Compose
docker-compose up -d

# التحقق من حالة الخدمات
docker-compose ps
```

### 5. إعداد قاعدة البيانات

```bash
# الانتقال إلى مجلد الخادم الخلفي
cd backend

# تشغيل ترحيلات قاعدة البيانات
npm run migrate

# (اختياري) تعبئة البيانات التجريبية
npm run seed

# العودة إلى المجلد الرئيسي
cd ..
```

### 6. تشغيل التطبيق

#### أ. تشغيل في وضع التطوير

```bash
# تشغيل الخادم الخلفي والواجهة الأمامية معاً
npm run dev
```

#### ب. أو تشغيل كل جزء منفصلاً

```bash
# في نافذة طرفية أولى - تشغيل الخادم الخلفي
npm run dev:backend

# في نافذة طرفية ثانية - تشغيل الواجهة الأمامية
npm run dev:frontend
```

## 🔗 الوصول إلى التطبيق

بعد تشغيل جميع الخدمات، يمكنك الوصول إلى:

- **الواجهة الأمامية**: http://localhost:3001
- **الخادم الخلفي API**: http://localhost:3000
- **وثائق API**: http://localhost:3000/api-docs

### أدوات إدارة قواعد البيانات (اختياري)

لتشغيل أدوات الإدارة:

```bash
docker-compose --profile tools up -d
```

- **pgAdmin** (PostgreSQL): http://localhost:5050
  - البريد: admin@tayseer.com
  - كلمة المرور: admin123

- **Redis Commander**: http://localhost:8081

- **Mongo Express**: http://localhost:8082
  - المستخدم: admin
  - كلمة المرور: admin123

- **Kibana** (Elasticsearch): http://localhost:5601

- **RabbitMQ Management**: http://localhost:15672
  - المستخدم: admin
  - كلمة المرور: rabbit123

- **MinIO Console**: http://localhost:9001
  - المستخدم: minioadmin
  - كلمة المرور: minioadmin123

## 🛠️ أوامر مفيدة

### إدارة Docker

```bash
# إيقاف جميع الخدمات
docker-compose down

# إيقاف وحذف جميع البيانات
docker-compose down -v

# عرض سجلات الخدمات
docker-compose logs -f [service_name]

# إعادة بناء الخدمات
docker-compose build
```

### إدارة قاعدة البيانات

```bash
# إنشاء ترحيل جديد
cd backend
npm run migrate:create -- --name migration_name

# تشغيل الترحيلات
npm run migrate

# التراجع عن آخر ترحيل
npm run migrate:rollback
```

### التطوير

```bash
# تشغيل الاختبارات
npm run test

# تشغيل الـ linter
npm run lint

# تنسيق الكود
npm run format
```

## ❗ حل المشاكل الشائعة

### 1. خطأ في الاتصال بقاعدة البيانات

```bash
# التأكد من تشغيل خدمة PostgreSQL
docker-compose ps postgres

# عرض سجلات PostgreSQL
docker-compose logs postgres
```

### 2. خطأ في المنافذ المحجوزة

```bash
# التحقق من المنافذ المستخدمة
netstat -ano | findstr :3000
netstat -ano | findstr :5432

# تغيير المنافذ في docker-compose.yml و .env
```

### 3. خطأ في تثبيت التبعيات

```bash
# حذف ملفات القفل وإعادة التثبيت
rm -rf node_modules package-lock.json
npm cache clean --force
npm install
```

### 4. مشاكل الذاكرة مع Docker

- افتح Docker Desktop
- اذهب إلى Settings > Resources
- زيادة Memory إلى 4GB على الأقل

## 📞 الدعم

إذا واجهت أي مشاكل:

1. تحقق من قسم حل المشاكل أعلاه
2. ابحث في قسم Issues في GitHub
3. اطرح سؤالك في قسم Discussions
4. تواصل مع فريق الدعم

## 🎯 الخطوات التالية

بعد نجاح التثبيت:

1. قم بتسجيل الدخول باستخدام الحساب الافتراضي
2. أنشئ شركتك الأولى
3. قم بإعداد الموظفين والصلاحيات
4. ابدأ في إدخال البيانات

</div>