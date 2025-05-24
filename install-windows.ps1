# تثبيت منصة تيسير - Windows PowerShell Script
# يتطلب تشغيل PowerShell كمسؤول

Write-Host "===============================================" -ForegroundColor Cyan
Write-Host "     مرحباً بك في برنامج تثبيت منصة تيسير     " -ForegroundColor Cyan
Write-Host "===============================================" -ForegroundColor Cyan
Write-Host ""

# التحقق من تشغيل PowerShell كمسؤول
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Host "❌ يجب تشغيل هذا السكريبت كمسؤول!" -ForegroundColor Red
    Write-Host "الرجاء النقر بزر الماوس الأيمن على PowerShell واختيار 'Run as Administrator'" -ForegroundColor Yellow
    pause
    exit 1
}

# دالة للتحقق من تثبيت البرامج
function Test-Command($cmdname) {
    return [bool](Get-Command -Name $cmdname -ErrorAction SilentlyContinue)
}

# دالة لتثبيت Chocolatey
function Install-Chocolatey {
    if (!(Test-Command choco)) {
        Write-Host "📦 تثبيت Chocolatey (مدير الحزم)..." -ForegroundColor Yellow
        Set-ExecutionPolicy Bypass -Scope Process -Force
        [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
        iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))
        $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
    }
}

# التحقق من المتطلبات
Write-Host "🔍 التحقق من المتطلبات الأساسية..." -ForegroundColor Yellow
Write-Host ""

$requirements = @{
    "Node.js" = "node"
    "npm" = "npm"
    "Git" = "git"
    "Docker" = "docker"
    "Docker Compose" = "docker-compose"
}

$missingTools = @()

foreach ($tool in $requirements.Keys) {
    if (Test-Command $requirements[$tool]) {
        $version = & $requirements[$tool] --version 2>&1
        Write-Host "✅ $tool مثبت: $version" -ForegroundColor Green
    } else {
        Write-Host "❌ $tool غير مثبت" -ForegroundColor Red
        $missingTools += $tool
    }
}

Write-Host ""

# تثبيت الأدوات المفقودة
if ($missingTools.Count -gt 0) {
    Write-Host "📥 سيتم تثبيت الأدوات التالية:" -ForegroundColor Yellow
    $missingTools | ForEach-Object { Write-Host "   - $_" -ForegroundColor White }
    Write-Host ""
    
    $install = Read-Host "هل تريد المتابعة؟ (Y/N)"
    if ($install -eq 'Y' -or $install -eq 'y') {
        
        # تثبيت Chocolatey إذا لزم الأمر
        Install-Chocolatey
        
        # تثبيت الأدوات المفقودة
        foreach ($tool in $missingTools) {
            Write-Host "📦 تثبيت $tool..." -ForegroundColor Yellow
            
            switch ($tool) {
                "Node.js" {
                    choco install nodejs -y
                }
                "Git" {
                    choco install git -y
                }
                "Docker" {
                    Write-Host "⚠️  Docker Desktop يتطلب إعادة تشغيل الجهاز بعد التثبيت" -ForegroundColor Yellow
                    choco install docker-desktop -y
                }
            }
        }
        
        # تحديث متغيرات البيئة
        $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
        
        Write-Host ""
        Write-Host "✅ تم تثبيت جميع الأدوات بنجاح!" -ForegroundColor Green
        
        if ($missingTools -contains "Docker") {
            Write-Host "⚠️  يرجى إعادة تشغيل الجهاز وتشغيل Docker Desktop قبل المتابعة" -ForegroundColor Yellow
            pause
            exit 0
        }
    } else {
        Write-Host "❌ تم إلغاء التثبيت" -ForegroundColor Red
        exit 1
    }
}

# التحقق من تشغيل Docker
Write-Host ""
Write-Host "🐳 التحقق من حالة Docker..." -ForegroundColor Yellow

$dockerRunning = $false
try {
    docker ps 2>&1 | Out-Null
    $dockerRunning = $?
} catch {}

if (-not $dockerRunning) {
    Write-Host "❌ Docker غير مشغل. يرجى تشغيل Docker Desktop أولاً." -ForegroundColor Red
    Write-Host "انتظر حتى يظهر أيقونة Docker في شريط المهام ويصبح أخضر" -ForegroundColor Yellow
    pause
    exit 1
}

Write-Host "✅ Docker يعمل بشكل صحيح" -ForegroundColor Green

# الانتقال إلى مجلد المشروع
Write-Host ""
Write-Host "📁 الانتقال إلى مجلد المشروع..." -ForegroundColor Yellow
$projectPath = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $projectPath
Write-Host "📍 المسار الحالي: $projectPath" -ForegroundColor Cyan

# تثبيت التبعيات
Write-Host ""
Write-Host "📦 تثبيت تبعيات المشروع..." -ForegroundColor Yellow
Write-Host "هذا قد يستغرق بضع دقائق..." -ForegroundColor Gray

npm install
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ فشل تثبيت التبعيات" -ForegroundColor Red
    pause
    exit 1
}

npm install --workspaces
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ فشل تثبيت تبعيات المشاريع الفرعية" -ForegroundColor Red
    pause
    exit 1
}

Write-Host "✅ تم تثبيت جميع التبعيات بنجاح" -ForegroundColor Green

# إعداد ملفات البيئة
Write-Host ""
Write-Host "⚙️  إعداد ملفات البيئة..." -ForegroundColor Yellow

# نسخ ملف البيئة للخادم الخلفي
if (Test-Path "backend\.env.example") {
    Copy-Item "backend\.env.example" "backend\.env" -Force
    Write-Host "✅ تم إنشاء backend/.env" -ForegroundColor Green
} else {
    Write-Host "⚠️  لم يتم العثور على backend/.env.example" -ForegroundColor Yellow
}

# إنشاء ملف البيئة للواجهة الأمامية
@"
REACT_APP_API_URL=http://localhost:3000
REACT_APP_WS_URL=ws://localhost:3000
"@ | Out-File -FilePath "frontend\.env" -Encoding UTF8
Write-Host "✅ تم إنشاء frontend/.env" -ForegroundColor Green

# تشغيل خدمات Docker
Write-Host ""
Write-Host "🚀 تشغيل قواعد البيانات والخدمات..." -ForegroundColor Yellow
Write-Host "هذا قد يستغرق بضع دقائق في المرة الأولى..." -ForegroundColor Gray

docker-compose up -d
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ فشل تشغيل خدمات Docker" -ForegroundColor Red
    pause
    exit 1
}

# الانتظار حتى تصبح الخدمات جاهزة
Write-Host ""
Write-Host "⏳ انتظار حتى تصبح قواعد البيانات جاهزة..." -ForegroundColor Yellow

$maxAttempts = 30
$attempt = 0
$servicesReady = $false

while ($attempt -lt $maxAttempts -and -not $servicesReady) {
    $attempt++
    Write-Host "المحاولة $attempt من $maxAttempts..." -ForegroundColor Gray
    
    $healthyServices = docker-compose ps --services --filter "status=running" 2>&1
    $runningCount = ($healthyServices | Measure-Object -Line).Lines
    
    if ($runningCount -ge 5) {
        $servicesReady = $true
    } else {
        Start-Sleep -Seconds 5
    }
}

if ($servicesReady) {
    Write-Host "✅ جميع الخدمات تعمل بنجاح" -ForegroundColor Green
} else {
    Write-Host "⚠️  بعض الخدمات لم تبدأ بعد. يمكنك التحقق باستخدام: docker-compose ps" -ForegroundColor Yellow
}

# تشغيل ترحيلات قاعدة البيانات
Write-Host ""
Write-Host "🗄️  إعداد قاعدة البيانات..." -ForegroundColor Yellow

Set-Location "backend"
npm run migrate
if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ تم تشغيل ترحيلات قاعدة البيانات بنجاح" -ForegroundColor Green
} else {
    Write-Host "⚠️  فشل تشغيل بعض الترحيلات. يمكنك المحاولة لاحقاً باستخدام: npm run migrate" -ForegroundColor Yellow
}

# العودة للمجلد الرئيسي
Set-Location ..

# عرض معلومات الوصول
Write-Host ""
Write-Host "===============================================" -ForegroundColor Green
Write-Host "        ✅ تم تثبيت منصة تيسير بنجاح!         " -ForegroundColor Green
Write-Host "===============================================" -ForegroundColor Green
Write-Host ""
Write-Host "🌐 يمكنك الآن الوصول إلى:" -ForegroundColor Cyan
Write-Host "   - الواجهة الأمامية: http://localhost:3001" -ForegroundColor White
Write-Host "   - API الخادم: http://localhost:3000" -ForegroundColor White
Write-Host "   - وثائق API: http://localhost:3000/api-docs" -ForegroundColor White
Write-Host ""
Write-Host "🛠️  أدوات الإدارة (اختياري):" -ForegroundColor Cyan
Write-Host "   لتشغيلها: docker-compose --profile tools up -d" -ForegroundColor Gray
Write-Host "   - pgAdmin: http://localhost:5050" -ForegroundColor White
Write-Host "   - Redis Commander: http://localhost:8081" -ForegroundColor White
Write-Host "   - RabbitMQ: http://localhost:15672" -ForegroundColor White
Write-Host "   - MinIO: http://localhost:9001" -ForegroundColor White
Write-Host ""
Write-Host "📝 لتشغيل التطبيق:" -ForegroundColor Yellow
Write-Host "   npm run dev" -ForegroundColor White
Write-Host ""
Write-Host "📚 للمزيد من المعلومات، راجع ملف INSTALLATION.md" -ForegroundColor Gray
Write-Host ""

# السؤال عن تشغيل التطبيق
$runApp = Read-Host "هل تريد تشغيل التطبيق الآن؟ (Y/N)"
if ($runApp -eq 'Y' -or $runApp -eq 'y') {
    Write-Host ""
    Write-Host "🚀 تشغيل التطبيق..." -ForegroundColor Yellow
    Write-Host "للإيقاف: اضغط Ctrl+C" -ForegroundColor Gray
    Write-Host ""
    npm run dev
} else {
    Write-Host "👍 يمكنك تشغيل التطبيق لاحقاً باستخدام: npm run dev" -ForegroundColor Cyan
}

pause