@echo off
title Tayseer ERP - ADB Call Service Setup
color 1F

set "ADB_DIR=C:\platform-tools"
set "ADB_EXE=%ADB_DIR%\adb.exe"
set "SERVICE_DIR=C:\TayseerCallService"
set "SERVICE_SCRIPT=%SERVICE_DIR%\adb_call_server.ps1"
set "TASK_NAME=Tayseer ADB Call Server"
set "REG_KEY=HKCU\Software\Microsoft\Windows\CurrentVersion\Run"
set "REG_VALUE=TayseerCallService"
set "STARTUP_FOLDER=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup"
set "SHORTCUT=%STARTUP_FOLDER%\TayseerCallService.lnk"
set "UNINSTALL_KEY=HKLM\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\TayseerCallService"
set "INSTALLER_COPY=%SERVICE_DIR%\uninstall.bat"
set "DOWNLOAD_URL=https://dl.google.com/android/repository/platform-tools-latest-windows.zip"
set "ZIP_FILE=%TEMP%\platform-tools.zip"
set "PS_CMD=powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File \"%SERVICE_SCRIPT%\""

:: ══════════════ UNINSTALL MODE ══════════════

if /i "%~1"=="/uninstall" goto :uninstall
if /i "%~1"=="--uninstall" goto :uninstall

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

:: Copy the PS1 from the same extracted folder (IExpress extracts both files together)
set "LOCAL_PS1=%~dp0_adb_call_server.ps1"
if exist "%LOCAL_PS1%" (
    copy /Y "%LOCAL_PS1%" "%SERVICE_SCRIPT%" >nul
    echo  [OK] Call service script copied from installer package.
) else (
    echo  [!] Service script not found in package. Downloading...
    powershell -NoProfile -ExecutionPolicy Bypass -Command ^
      "try { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri 'https://raw.githubusercontent.com/EngOsamaQazan/Tayseer/main/scripts/deploy/_adb_call_server.ps1' -OutFile '%SERVICE_SCRIPT%' -UseBasicParsing; Write-Host '  [OK] Downloaded.' } catch { Write-Host '  [FAIL]' $_.Exception.Message; exit 1 }"
    if %errorlevel% neq 0 (
        echo  [!] Could not install call service. Setup incomplete.
        pause
        exit /b 1
    )
)

echo  [OK] Call service installed at %SERVICE_DIR%
echo.

:: ══════════════ STEP 3: Auto-start (3 methods) + Run ══════════════

echo  [4/4] Setting up auto-start (3 methods)...
echo.

:: --- Method 1: Task Scheduler (primary — supports auto-restart) ---
echo    [4a] Task Scheduler...
schtasks /Delete /TN "%TASK_NAME%" /F >nul 2>&1
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument '-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File \"%SERVICE_SCRIPT%\"'; $trigger = New-ScheduledTaskTrigger -AtLogOn; $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 1) -ExecutionTimeLimit (New-TimeSpan -Days 9999); Register-ScheduledTask -TaskName '%TASK_NAME%' -Action $action -Trigger $trigger -Settings $settings -Description 'Tayseer ERP - USB Direct Call Service (auto-restart)' -RunLevel Highest -Force | Out-Null; Write-Host '        [OK] Task Scheduler configured (with auto-restart)'"

:: --- Method 2: Registry Run Key (backup) ---
echo    [4b] Registry Run Key...
reg add "%REG_KEY%" /v "%REG_VALUE%" /t REG_SZ /d "powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File \"%SERVICE_SCRIPT%\"" /f >nul 2>&1
if %errorlevel% equ 0 (
    echo         [OK] Registry auto-start added
) else (
    echo         [!] Registry entry failed - skipping
)

:: --- Method 3: Startup Folder Shortcut (fallback) ---
echo    [4c] Startup Folder Shortcut...
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$ws = New-Object -ComObject WScript.Shell; $sc = $ws.CreateShortcut('%SHORTCUT%'); $sc.TargetPath = 'powershell.exe'; $sc.Arguments = '-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File \"%SERVICE_SCRIPT%\"'; $sc.WindowStyle = 7; $sc.Description = 'Tayseer ADB Call Service'; $sc.Save(); Write-Host '        [OK] Startup shortcut created'"

:: --- Register in Windows Apps & Features ---
echo    [4d] Windows Apps ^& Features...
copy /Y "%~f0" "%INSTALLER_COPY%" >nul 2>&1
reg add "%UNINSTALL_KEY%" /v "DisplayName" /t REG_SZ /d "Tayseer ADB Call Service" /f >nul 2>&1
reg add "%UNINSTALL_KEY%" /v "UninstallString" /t REG_SZ /d "\"%INSTALLER_COPY%\" /uninstall" /f >nul 2>&1
reg add "%UNINSTALL_KEY%" /v "DisplayIcon" /t REG_SZ /d "%ADB_EXE%" /f >nul 2>&1
reg add "%UNINSTALL_KEY%" /v "Publisher" /t REG_SZ /d "Tayseer ERP" /f >nul 2>&1
reg add "%UNINSTALL_KEY%" /v "DisplayVersion" /t REG_SZ /d "1.0.0" /f >nul 2>&1
reg add "%UNINSTALL_KEY%" /v "InstallLocation" /t REG_SZ /d "%SERVICE_DIR%" /f >nul 2>&1
reg add "%UNINSTALL_KEY%" /v "NoModify" /t REG_DWORD /d 1 /f >nul 2>&1
reg add "%UNINSTALL_KEY%" /v "NoRepair" /t REG_DWORD /d 1 /f >nul 2>&1
reg add "%UNINSTALL_KEY%" /v "EstimatedSize" /t REG_DWORD /d 1024 /f >nul 2>&1
echo         [OK] Registered in Apps ^& Features
echo.

:: --- Kill any existing instance and start fresh ---
powershell -NoProfile -Command "Get-Process powershell -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like '*adb_call_server*' } | Stop-Process -Force -ErrorAction SilentlyContinue" >nul 2>&1

:: Start the service now (hidden window)
start /min powershell -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "%SERVICE_SCRIPT%"

echo  [OK] Call service is now running on localhost:9876
echo.

:: ══════════════ VERIFY ══════════════

echo  ------------------------------------------------------
echo   Checking auto-start registration...
echo  ------------------------------------------------------
echo.

:: Verify Task Scheduler
schtasks /Query /TN "%TASK_NAME%" >nul 2>&1
if %errorlevel% equ 0 (
    echo   [OK] Task Scheduler: registered
) else (
    echo   [!]  Task Scheduler: NOT registered
)

:: Verify Registry
reg query "%REG_KEY%" /v "%REG_VALUE%" >nul 2>&1
if %errorlevel% equ 0 (
    echo   [OK] Registry Run Key: registered
) else (
    echo   [!]  Registry Run Key: NOT registered
)

:: Verify Startup Shortcut
if exist "%SHORTCUT%" (
    echo   [OK] Startup Shortcut: exists
) else (
    echo   [!]  Startup Shortcut: NOT found
)

:: Verify Apps & Features
reg query "%UNINSTALL_KEY%" /v "DisplayName" >nul 2>&1
if %errorlevel% equ 0 (
    echo   [OK] Apps ^& Features: registered
) else (
    echo   [!]  Apps ^& Features: NOT registered
)

echo.
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
    echo   - Auto-start: Task Scheduler + Registry + Startup
    echo   - Auto-restart: enabled (if service crashes)
    echo   - Device connected and authorized
    echo.
    echo   You can now make calls from Tayseer ERP directly!
    echo.
    echo   To uninstall: run this file with /uninstall
    echo     _install_adb.bat /uninstall
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
exit /b 0

:: ══════════════ UNINSTALL ══════════════

:uninstall
echo.
echo  ======================================================
echo       Tayseer ERP - ADB Call Service UNINSTALLER
echo  ======================================================
echo.

net session >nul 2>&1
if %errorlevel% neq 0 (
    echo  [!] ERROR: Uninstall must run as Administrator.
    pause
    exit /b 1
)

echo  [1/6] Stopping running service...
powershell -NoProfile -Command "Get-Process powershell -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like '*adb_call_server*' } | Stop-Process -Force -ErrorAction SilentlyContinue" >nul 2>&1
echo        [OK] Service stopped

echo  [2/6] Removing Task Scheduler entry...
schtasks /Delete /TN "%TASK_NAME%" /F >nul 2>&1
if %errorlevel% equ 0 (
    echo        [OK] Task removed
) else (
    echo        [--] No task found (already removed)
)

echo  [3/6] Removing Registry Run Key...
reg delete "%REG_KEY%" /v "%REG_VALUE%" /f >nul 2>&1
if %errorlevel% equ 0 (
    echo        [OK] Registry key removed
) else (
    echo        [--] No registry key found (already removed)
)

echo  [4/6] Removing Startup shortcut...
if exist "%SHORTCUT%" (
    del "%SHORTCUT%" >nul 2>&1
    echo        [OK] Shortcut removed
) else (
    echo        [--] No shortcut found (already removed)
)

echo  [5/6] Removing Apps ^& Features entry...
reg delete "%UNINSTALL_KEY%" /f >nul 2>&1
if %errorlevel% equ 0 (
    echo        [OK] Apps ^& Features entry removed
) else (
    echo        [--] No Apps ^& Features entry found
)

echo  [6/6] Removing service files...
if exist "%SERVICE_DIR%" (
    rmdir /S /Q "%SERVICE_DIR%" >nul 2>&1
    echo        [OK] %SERVICE_DIR% removed
) else (
    echo        [--] Service directory not found
)

echo.
echo  ======================================================
echo   [OK] UNINSTALL COMPLETE
echo.
echo   ADB platform-tools at %ADB_DIR% were NOT removed.
echo   To remove ADB too, delete %ADB_DIR% manually.
echo  ======================================================
echo.
pause
exit /b 0
