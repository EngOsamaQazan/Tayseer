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
set "SERVICE_DIR=C:\TayseerCallService"
set "SERVICE_SCRIPT=%SERVICE_DIR%\adb_call_server.ps1"
set "DOWNLOAD_URL=https://dl.google.com/android/repository/platform-tools-latest-windows.zip"
set "ZIP_FILE=%TEMP%\platform-tools.zip"

:: ══════════════ STEP 1: Install ADB ══════════════

if exist "%ADB_EXE%" (
    echo  [OK] ADB is already installed at %ADB_DIR%
    "%ADB_EXE%" version 2>nul
    echo.
    goto :install_service
)

echo  [1/4] Downloading Android Platform Tools...
echo.

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "try { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri '%DOWNLOAD_URL%' -OutFile '%ZIP_FILE%' -UseBasicParsing; Write-Host '  [OK] Download completed.' } catch { Write-Host '  [FAIL] Download failed:' $_.Exception.Message; exit 1 }"

if %errorlevel% neq 0 (
    echo  [!] Download failed. Check your internet connection.
    pause
    exit /b 1
)

echo.
echo  [2/4] Extracting to %ADB_DIR%...

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "try { Expand-Archive -Path '%ZIP_FILE%' -DestinationPath 'C:\' -Force; Write-Host '  [OK] Extraction completed.' } catch { Write-Host '  [FAIL]' $_.Exception.Message; exit 1 }"

if %errorlevel% neq 0 (
    echo  [!] Extraction failed.
    pause
    exit /b 1
)

del "%ZIP_FILE%" >nul 2>&1

if not exist "%ADB_EXE%" (
    echo  [!] Installation failed - ADB not found.
    pause
    exit /b 1
)

echo  [OK] ADB installed successfully.
echo.

:: ══════════════ STEP 2: Install Call Service ══════════════

:install_service
echo  [3/4] Installing Tayseer Call Service...

if not exist "%SERVICE_DIR%" mkdir "%SERVICE_DIR%"

:: Write the PowerShell server script
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "Invoke-WebRequest -Uri 'https://raw.githubusercontent.com/EngOsamaQazan/Tayseer/main/scripts/deploy/_adb_call_server.ps1' -OutFile '%SERVICE_SCRIPT%' -UseBasicParsing 2>$null; if (-not (Test-Path '%SERVICE_SCRIPT%')) { exit 1 }"

if %errorlevel% neq 0 (
    echo  [!] Could not download service script. Creating local copy...
    
    :: Fallback: create the script directly
    > "%SERVICE_SCRIPT%" (
        echo $ADB = "C:\platform-tools\adb.exe"
        echo $PORT = 9876
        echo $prefix = "http://localhost:$PORT/"
        echo if (-not ^(Test-Path $ADB^)^) { exit 1 }
        echo $listener = New-Object System.Net.HttpListener
        echo $listener.Prefixes.Add^($prefix^)
        echo try { $listener.Start^(^) } catch { exit 1 }
        echo while ^($listener.IsListening^) {
        echo   try {
        echo     $context = $listener.GetContext^(^)
        echo     $req = $context.Request; $res = $context.Response
        echo     $res.Headers.Add^("Access-Control-Allow-Origin", "*"^)
        echo     $res.Headers.Add^("Access-Control-Allow-Methods", "GET, POST, OPTIONS"^)
        echo     $res.Headers.Add^("Access-Control-Allow-Headers", "Content-Type"^)
        echo     $res.ContentType = "application/json; charset=utf-8"
        echo     if ^($req.HttpMethod -eq "OPTIONS"^) { $res.StatusCode = 200; $res.Close^(^); continue }
        echo     $phone = $req.QueryString["phone"]
        echo     if ^(-not $phone -and $req.HasEntityBody^) {
        echo       $rd = New-Object System.IO.StreamReader^($req.InputStream^)
        echo       $bd = $rd.ReadToEnd^(^); $rd.Close^(^)
        echo       if ^($bd -match "phone=^([^^&]+^)"^) { $phone = [System.Uri]::UnescapeDataString^($matches[1]^) }
        echo     }
        echo     $phone = $phone -replace '[^^0-9+]', ''
        echo     if ^($req.Url.AbsolutePath -eq "/status"^) {
        echo       $dv = ^& $ADB devices 2^>^&1 ^| Out-String
        echo       $json = @{ ok = $true; device = ^($dv -match "(?m)\t\s*device\s*$"^) } ^| ConvertTo-Json -Compress
        echo       $b = [System.Text.Encoding]::UTF8.GetBytes^($json^); $res.OutputStream.Write^($b, 0, $b.Length^); $res.Close^(^); continue
        echo     }
        echo     if ^(-not $phone -or $phone.Length -lt 7^) {
        echo       $b = [System.Text.Encoding]::UTF8.GetBytes^('{"ok":false,"error":"Invalid phone"}'^); $res.OutputStream.Write^($b, 0, $b.Length^); $res.Close^(^); continue
        echo     }
        echo     $dv = ^& $ADB devices 2^>^&1 ^| Out-String
        echo     if ^(-not ^($dv -match "(?m)\t\s*device\s*$"^)^) {
        echo       $b = [System.Text.Encoding]::UTF8.GetBytes^('{"ok":false,"error":"No device connected"}'^); $res.OutputStream.Write^($b, 0, $b.Length^); $res.Close^(^); continue
        echo     }
        echo     $co = ^& $ADB shell am start -a android.intent.action.CALL -d "tel:$phone" 2^>^&1 ^| Out-String
        echo     $ok = $co -match "Starting" -or $co -match "Activity"
        echo     $json = @{ ok = $ok; phone = $phone } ^| ConvertTo-Json -Compress
        echo     $b = [System.Text.Encoding]::UTF8.GetBytes^($json^); $res.OutputStream.Write^($b, 0, $b.Length^); $res.Close^(^)
        echo   } catch [System.Net.HttpListenerException] { break }
        echo   catch { }
        echo }
        echo $listener.Stop^(^)
    )
)

echo  [OK] Call service installed at %SERVICE_DIR%
echo.

:: ══════════════ STEP 3: Auto-start + Run ══════════════

echo  [4/4] Setting up auto-start...

:: Create startup shortcut using PowerShell
set "STARTUP_FOLDER=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup"
set "SHORTCUT=%STARTUP_FOLDER%\TayseerCallService.lnk"

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$ws = New-Object -ComObject WScript.Shell; $sc = $ws.CreateShortcut('%SHORTCUT%'); $sc.TargetPath = 'powershell.exe'; $sc.Arguments = '-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File \"%SERVICE_SCRIPT%\"'; $sc.WindowStyle = 7; $sc.Description = 'Tayseer ADB Call Service'; $sc.Save(); Write-Host '  [OK] Auto-start configured.'"

:: Kill any existing instance and start fresh
powershell -NoProfile -Command "Get-Process powershell -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like '*adb_call_server*' } | Stop-Process -Force -ErrorAction SilentlyContinue" >nul 2>&1

:: Start the service now (hidden window)
start /min powershell -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "%SERVICE_SCRIPT%"

echo  [OK] Call service is now running.
echo.

:: ══════════════ VERIFY ══════════════

echo  ------------------------------------------------------
echo   Checking connected devices...
echo  ------------------------------------------------------
echo.

"%ADB_EXE%" devices 2>nul

"%ADB_EXE%" devices 2>nul | findstr /R /C:"	device$" >nul 2>&1
if %errorlevel% equ 0 (
    echo.
    echo  ======================================================
    echo   [OK] SETUP COMPLETE!
    echo.
    echo   - ADB installed at %ADB_DIR%
    echo   - Call service running on localhost:9876
    echo   - Auto-start enabled (runs on Windows login)
    echo   - Device connected and authorized
    echo.
    echo   You can now make calls from Tayseer ERP directly!
    echo  ======================================================
) else (
    "%ADB_EXE%" devices 2>nul | findstr /C:"unauthorized" >nul 2>&1
    if %errorlevel% equ 0 (
        echo.
        echo  [!] Device found but NOT authorized.
        echo      Tap "Allow" on your phone, then run this again.
    ) else (
        echo.
        echo  [!] ADB + Call Service installed successfully,
        echo      but no phone is connected yet.
        echo.
        echo  To connect your phone:
        echo    1. Connect phone via USB cable
        echo    2. Settings ^> About phone ^> Tap "Build number" 7 times
        echo    3. Settings ^> Developer Options ^> Enable "USB Debugging"
        echo    4. Tap "Allow" on the USB Debugging prompt
    )
)

echo.
pause
