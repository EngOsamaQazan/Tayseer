@echo off
chcp 65001 >nul 2>&1
title Tayseer ERP — ADB Installer
color 1F

echo.
echo  ╔══════════════════════════════════════════════╗
echo  ║     Tayseer ERP - ADB Call Service Setup     ║
echo  ║          تثبيت خدمة الاتصال عبر USB          ║
echo  ╚══════════════════════════════════════════════╝
echo.

:: Check for admin privileges
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo  [!] يجب تشغيل هذا الملف كمسؤول ^(Run as Administrator^)
    echo.
    echo  اضغط بزر الفأرة الأيمن على الملف واختر "Run as administrator"
    echo.
    pause
    exit /b 1
)

set "ADB_DIR=C:\platform-tools"
set "ADB_EXE=%ADB_DIR%\adb.exe"
set "DOWNLOAD_URL=https://dl.google.com/android/repository/platform-tools-latest-windows.zip"
set "ZIP_FILE=%TEMP%\platform-tools.zip"

:: Check if already installed
if exist "%ADB_EXE%" (
    echo  [OK] ADB مثبت مسبقاً في %ADB_DIR%
    echo.
    "%ADB_EXE%" version 2>nul
    echo.
    goto :check_device
)

echo  [1/3] جاري تحميل Android Platform Tools...
echo        من: %DOWNLOAD_URL%
echo.

:: Download using PowerShell
powershell -NoProfile -Command "try { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri '%DOWNLOAD_URL%' -OutFile '%ZIP_FILE%' -UseBasicParsing; Write-Host '  [OK] تم التحميل بنجاح' } catch { Write-Host '  [FAIL] فشل التحميل:' $_.Exception.Message; exit 1 }"
if %errorlevel% neq 0 (
    echo.
    echo  [!] فشل التحميل. تأكد من اتصال الإنترنت وحاول مرة أخرى.
    pause
    exit /b 1
)

echo.
echo  [2/3] جاري فك الضغط إلى %ADB_DIR%...

:: Extract using PowerShell
powershell -NoProfile -Command "try { Expand-Archive -Path '%ZIP_FILE%' -DestinationPath 'C:\' -Force; Write-Host '  [OK] تم فك الضغط بنجاح' } catch { Write-Host '  [FAIL] فشل فك الضغط:' $_.Exception.Message; exit 1 }"
if %errorlevel% neq 0 (
    echo.
    echo  [!] فشل فك الضغط.
    pause
    exit /b 1
)

:: Cleanup
del "%ZIP_FILE%" >nul 2>&1

:: Verify installation
if not exist "%ADB_EXE%" (
    echo.
    echo  [!] فشل التثبيت — الملف غير موجود: %ADB_EXE%
    pause
    exit /b 1
)

echo.
echo  [3/3] تم التثبيت بنجاح!
echo.
"%ADB_EXE%" version 2>nul
echo.

:check_device
echo  ─────────────────────────────────────────────
echo   فحص الأجهزة المتصلة...
echo  ─────────────────────────────────────────────
echo.

"%ADB_EXE%" devices 2>nul | findstr /C:"device" | findstr /V /C:"attached" >nul 2>&1
if %errorlevel% equ 0 (
    "%ADB_EXE%" devices
    echo.
    echo  [OK] تم العثور على جهاز متصل!
    echo.
    echo  ╔══════════════════════════════════════════════╗
    echo  ║   التثبيت مكتمل — الخدمة جاهزة للاستخدام   ║
    echo  ╚══════════════════════════════════════════════╝
) else (
    "%ADB_EXE%" devices
    echo.
    echo  [!] لا يوجد جهاز متصل أو الجهاز غير مصرّح.
    echo.
    echo  تأكد من:
    echo    1. الموبايل متصل بكيبل USB
    echo    2. Developer Options مفعّلة على الموبايل
    echo    3. USB Debugging مفعّل
    echo    4. اضغط "Allow" على إشعار USB Debugging على الموبايل
    echo.
    echo  بعد الإعداد، شغّل هذا الملف مرة أخرى للتأكد.
)

echo.
pause
