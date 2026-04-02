@echo off
title Tayseer ERP - ADB Call Service Setup
color 1F

echo.
echo  ======================================================
echo       Tayseer ERP - ADB Call Service Installer
echo       USB Direct Call Setup (No Bluetooth needed)
echo  ======================================================
echo.

:: Check for admin privileges
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo  [!] ERROR: This installer must run as Administrator.
    echo.
    echo      Right-click the file and choose "Run as administrator"
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
    echo  [OK] ADB is already installed at %ADB_DIR%
    echo.
    "%ADB_EXE%" version 2>nul
    echo.
    goto :check_device
)

echo  [1/3] Downloading Android Platform Tools...
echo        From: Google Official Repository
echo.

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "try { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri '%DOWNLOAD_URL%' -OutFile '%ZIP_FILE%' -UseBasicParsing; Write-Host '  [OK] Download completed successfully.' } catch { Write-Host '  [FAIL] Download failed:' $_.Exception.Message; exit 1 }"

if %errorlevel% neq 0 (
    echo.
    echo  [!] Download failed. Check your internet connection and try again.
    pause
    exit /b 1
)

echo.
echo  [2/3] Extracting to %ADB_DIR%...

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "try { Expand-Archive -Path '%ZIP_FILE%' -DestinationPath 'C:\' -Force; Write-Host '  [OK] Extraction completed successfully.' } catch { Write-Host '  [FAIL] Extraction failed:' $_.Exception.Message; exit 1 }"

if %errorlevel% neq 0 (
    echo.
    echo  [!] Extraction failed.
    pause
    exit /b 1
)

:: Cleanup temp file
del "%ZIP_FILE%" >nul 2>&1

:: Verify
if not exist "%ADB_EXE%" (
    echo.
    echo  [!] Installation failed - file not found: %ADB_EXE%
    pause
    exit /b 1
)

echo.
echo  [3/3] Installation successful!
echo.
"%ADB_EXE%" version 2>nul
echo.

:check_device
echo  ------------------------------------------------------
echo   Checking connected devices...
echo  ------------------------------------------------------
echo.

"%ADB_EXE%" devices 2>nul

:: Check if any authorized device is connected
"%ADB_EXE%" devices 2>nul | findstr /R /C:"	device$" >nul 2>&1
if %errorlevel% equ 0 (
    echo.
    echo  ======================================================
    echo   [OK] Device found and authorized!
    echo   Installation complete - USB Call Service is READY.
    echo  ======================================================
) else (
    "%ADB_EXE%" devices 2>nul | findstr /C:"unauthorized" >nul 2>&1
    if %errorlevel% equ 0 (
        echo.
        echo  [!] Device found but NOT authorized.
        echo      Check your phone screen and tap "Allow" on the
        echo      USB Debugging prompt. Then run this installer again.
    ) else (
        echo.
        echo  [!] No device connected.
        echo.
        echo  Setup steps on your phone:
        echo    1. Connect phone via USB cable
        echo    2. Go to Settings ^> About phone
        echo    3. Tap "Build number" 7 times to enable Developer Options
        echo    4. Go to Settings ^> Developer Options
        echo    5. Enable "USB Debugging"
        echo    6. Tap "Allow" on the USB Debugging prompt
        echo    7. Run this installer again to verify
    )
)

echo.
pause
