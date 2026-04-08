; ============================================================
;  Tayseer ADB Call Service — Inno Setup Installer Script
; ============================================================
;
;  HOW TO BUILD
;  ────────────
;  1. Install Inno Setup 6.x  →  https://jrsoftware.org/isinfo.php
;  2. (Optional) Place platform-tools.zip next to this file:
;     https://dl.google.com/android/repository/platform-tools-latest-windows.zip
;     If omitted the installer downloads ADB automatically.
;  3. Open this file in Inno Setup Compiler → Build → Compile (Ctrl+F9)
;  4. Output:  Output\TayseerCallServiceSetup.exe
;
; ============================================================

; ────────────────────────────────────────────────────────────
;  [Setup] — Application metadata & installer behaviour
; ────────────────────────────────────────────────────────────
[Setup]
AppId={{8F3E2A1B-5C4D-4E6F-9A8B-7C2D1E3F4A5B}
AppName=Tayseer ADB Call Service
AppVersion=1.0.0
AppVerName=Tayseer ADB Call Service v1.0.0
AppPublisher=Tayseer ERP
AppPublisherURL=https://tayseer.example.com
AppSupportURL=https://tayseer.example.com
DefaultDirName=C:\TayseerCallService
DisableDirPage=yes
DefaultGroupName=Tayseer ERP
DisableProgramGroupPage=yes
DisableWelcomePage=no
PrivilegesRequired=admin
OutputDir=Output
OutputBaseFilename=TayseerCallServiceSetup
Compression=lzma2
SolidCompression=yes
WizardStyle=modern
WizardSizePercent=110
SetupLogging=yes
UninstallDisplayName=Tayseer ADB Call Service
CloseApplications=yes
RestartApplications=no
VersionInfoCompany=Tayseer ERP
VersionInfoDescription=Tayseer ADB Call Service - USB Direct Call Installer
VersionInfoProductName=Tayseer ADB Call Service
VersionInfoVersion=1.0.0.0
VersionInfoProductVersion=1.0.0.0

; ────────────────────────────────────────────────────────────
;  [Languages] — Arabic (default) + English
; ────────────────────────────────────────────────────────────
[Languages]
Name: "arabic";  MessagesFile: "compiler:Languages\Arabic.isl"
Name: "english"; MessagesFile: "compiler:Default.isl"

; ────────────────────────────────────────────────────────────
;  [CustomMessages] — Wizard screen text in both languages
; ────────────────────────────────────────────────────────────
[CustomMessages]
; ── Arabic ─────────────────────────────────────────────────
arabic.WelcomeLabel2=سيقوم هذا المعالج بتثبيت خدمة الاتصال المباشر عبر USB لنظام تيسير ERP.%n%nهذه الخدمة تتيح لك إجراء مكالمات هاتفية مباشرة من النظام عبر هاتفك المتصل بكابل USB.%n%nالمتطلبات:%n   • هاتف أندرويد متصل عبر USB%n   • تفعيل وضع المطور (USB Debugging)%n%nاضغط "التالي" للمتابعة.
arabic.FinishedLabel=تم تثبيت خدمة الاتصال المباشر بنجاح!%n%nالخدمة تعمل الآن وستبدأ تلقائياً عند تشغيل الجهاز.%n%nللتأكد من عمل الخدمة:%n   1. وصّل هاتفك عبر USB%n   2. فعّل USB Debugging%n   3. وافق على طلب التصريح على الهاتف%n   4. افتح http://localhost:9876/status في المتصفح
arabic.PrevInstallDetected=تم اكتشاف تثبيت سابق لخدمة الاتصال المباشر.%n%nماذا تريد أن تفعل؟%n%n   • "نعم" = إعادة تثبيت (إصلاح)%n   • "لا" = إزالة الخدمة بالكامل%n   • "إلغاء" = الخروج بدون تغيير
arabic.StatusExtractADB=جاري تثبيت أدوات ADB...
arabic.StatusDownloadADB=جاري تحميل أدوات ADB من الإنترنت...
arabic.StatusRegisterTask=جاري تسجيل التشغيل التلقائي...
arabic.StatusStartService=جاري تشغيل الخدمة...
arabic.ErrorDownloadADB=فشل تحميل أدوات ADB.%nتأكد من اتصالك بالإنترنت وأعد المحاولة.

; ── English ────────────────────────────────────────────────
english.WelcomeLabel2=This wizard will install the Tayseer USB Direct Call Service.%n%nThis service lets you make phone calls directly from Tayseer ERP through your USB-connected Android phone.%n%nRequirements:%n   • Android phone connected via USB%n   • USB Debugging enabled%n%nClick "Next" to continue.
english.FinishedLabel=The USB Direct Call Service has been installed successfully!%n%nThe service is now running and will start automatically on boot.%n%nTo verify:%n   1. Connect your phone via USB%n   2. Enable USB Debugging%n   3. Approve the authorization prompt%n   4. Visit http://localhost:9876/status
english.PrevInstallDetected=A previous installation was detected.%n%nWhat would you like to do?%n%n   • "Yes" = Reinstall (Repair)%n   • "No" = Uninstall completely%n   • "Cancel" = Exit without changes
english.StatusExtractADB=Installing ADB Platform Tools...
english.StatusDownloadADB=Downloading ADB Platform Tools...
english.StatusRegisterTask=Registering auto-start services...
english.StatusStartService=Starting the call service...
english.ErrorDownloadADB=Failed to download ADB Platform Tools.%nCheck your internet connection and try again.

; ────────────────────────────────────────────────────────────
;  [Files] — Embedded files
; ────────────────────────────────────────────────────────────
[Files]
Source: "_adb_call_server.ps1"; DestDir: "{app}"; DestName: "adb_call_server.ps1"; Flags: ignoreversion
; ADB platform-tools — embedded if the zip is present at compile time,
; otherwise the installer downloads it during installation.
#ifexist "platform-tools.zip"
Source: "platform-tools.zip"; DestDir: "{tmp}"; Flags: deleteafterinstall
#endif

; ────────────────────────────────────────────────────────────
;  [Icons] — Startup folder shortcut (auto-removed on uninstall)
; ────────────────────────────────────────────────────────────
[Icons]
Name: "{userstartup}\TayseerCallService"; Filename: "powershell.exe"; \
  Parameters: "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File ""{app}\adb_call_server.ps1"""; \
  Comment: "Tayseer ADB Call Service"

; ────────────────────────────────────────────────────────────
;  [Registry] — Run key backup (auto-removed on uninstall)
; ────────────────────────────────────────────────────────────
[Registry]
Root: HKCU; Subkey: "Software\Microsoft\Windows\CurrentVersion\Run"; \
  ValueType: string; ValueName: "TayseerCallService"; \
  ValueData: "powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File ""{app}\adb_call_server.ps1"""; \
  Flags: uninsdeletevalue

; ────────────────────────────────────────────────────────────
;  [UninstallDelete] — Remove the entire app directory
; ────────────────────────────────────────────────────────────
[UninstallDelete]
Type: filesandordirs; Name: "{app}"

; ────────────────────────────────────────────────────────────
;  [Code] — Pascal Script: install logic, repair, uninstall
; ────────────────────────────────────────────────────────────
[Code]

const
  ADB_EXE_PATH    = 'C:\platform-tools\adb.exe';
  ADB_DOWNLOAD    = 'https://dl.google.com/android/repository/platform-tools-latest-windows.zip';
  SCHED_TASK_NAME = 'Tayseer ADB Call Server';

(* ════════════════════════════════════════════════════════════
   Helper: check whether ADB is already on the machine
   ════════════════════════════════════════════════════════════ *)
function ADBInstalled: Boolean;
begin
  Result := FileExists(ADB_EXE_PATH);
end;

(* ════════════════════════════════════════════════════════════
   Helper: kill any running adb_call_server PowerShell process
   ════════════════════════════════════════════════════════════ *)
procedure KillCallServer;
var
  R: Integer;
begin
  Exec('powershell.exe',
    '-NoProfile -ExecutionPolicy Bypass -Command "' +
    'Get-Process powershell -EA SilentlyContinue | ' +
    'Where-Object { $_.CommandLine -like ''*adb_call_server*'' } | ' +
    'Stop-Process -Force -EA SilentlyContinue"',
    '', SW_HIDE, ewWaitUntilTerminated, R);
end;

(* ════════════════════════════════════════════════════════════
   Post-install step 1: Install ADB (extract or download)
   ════════════════════════════════════════════════════════════ *)
procedure InstallADB;
var
  R: Integer;
  EmbeddedZip, DownloadZip, PSScript, TempPS1: String;
begin
  if ADBInstalled then begin
    Log('ADB already present — skipping');
    Exit;
  end;

  EmbeddedZip := ExpandConstant('{tmp}\platform-tools.zip');

  if FileExists(EmbeddedZip) then begin
    (* Extract from the zip that was embedded in the installer *)
    WizardForm.StatusLabel.Caption := CustomMessage('StatusExtractADB');
    Log('Extracting embedded ADB from ' + EmbeddedZip);
    Exec('powershell.exe',
      '-NoProfile -ExecutionPolicy Bypass -Command "' +
      'Expand-Archive -Path ''' + EmbeddedZip + ''' -DestinationPath ''C:\'' -Force"',
      '', SW_HIDE, ewWaitUntilTerminated, R);
  end else begin
    (* No embedded zip — download from Google *)
    WizardForm.StatusLabel.Caption := CustomMessage('StatusDownloadADB');
    Log('Downloading ADB from internet');
    DownloadZip := ExpandConstant('{tmp}\platform-tools-dl.zip');
    TempPS1     := ExpandConstant('{tmp}\download_adb.ps1');
    PSScript :=
      '[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12' + #13#10 +
      'Invoke-WebRequest -Uri "' + ADB_DOWNLOAD + '" -OutFile "' + DownloadZip + '" -UseBasicParsing' + #13#10 +
      'Expand-Archive -Path "' + DownloadZip + '" -DestinationPath "C:\" -Force';
    SaveStringToFile(TempPS1, PSScript, False);
    Exec('powershell.exe',
      '-NoProfile -ExecutionPolicy Bypass -File "' + TempPS1 + '"',
      '', SW_HIDE, ewWaitUntilTerminated, R);
    DeleteFile(TempPS1);
  end;

  if not ADBInstalled then begin
    Log('ADB install FAILED');
    MsgBox(CustomMessage('ErrorDownloadADB'), mbError, MB_OK);
  end else
    Log('ADB installed OK');
end;

(* ════════════════════════════════════════════════════════════
   Post-install step 2: Register Task Scheduler (primary
   auto-start with auto-restart on crash)
   ════════════════════════════════════════════════════════════ *)
procedure RegisterScheduledTask;
var
  R: Integer;
  Script, TempPS1: String;
begin
  WizardForm.StatusLabel.Caption := CustomMessage('StatusRegisterTask');
  Script  := ExpandConstant('{app}\adb_call_server.ps1');
  TempPS1 := ExpandConstant('{tmp}\reg_task.ps1');

  SaveStringToFile(TempPS1,
    'schtasks /Delete /TN "' + SCHED_TASK_NAME + '" /F 2>$null' + #13#10 +
    '$a = New-ScheduledTaskAction -Execute "powershell.exe" `' + #13#10 +
    '  -Argument ''-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "' + Script + '"''' + #13#10 +
    '$t = New-ScheduledTaskTrigger -AtLogOn' + #13#10 +
    '$s = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries `' + #13#10 +
    '  -DontStopIfGoingOnBatteries -RestartCount 3 `' + #13#10 +
    '  -RestartInterval (New-TimeSpan -Minutes 1) `' + #13#10 +
    '  -ExecutionTimeLimit (New-TimeSpan -Days 9999)' + #13#10 +
    'Register-ScheduledTask -TaskName "' + SCHED_TASK_NAME + '" `' + #13#10 +
    '  -Action $a -Trigger $t -Settings $s `' + #13#10 +
    '  -Description "Tayseer ERP - USB Direct Call Service (auto-restart)" `' + #13#10 +
    '  -RunLevel Highest -Force | Out-Null',
    False);

  Exec('powershell.exe',
    '-NoProfile -ExecutionPolicy Bypass -File "' + TempPS1 + '"',
    '', SW_HIDE, ewWaitUntilTerminated, R);
  DeleteFile(TempPS1);

  if R = 0 then
    Log('Task Scheduler registered OK')
  else
    Log('Task Scheduler FAILED — exit code ' + IntToStr(R));
end;

(* ════════════════════════════════════════════════════════════
   Uninstall helper: remove scheduled task
   ════════════════════════════════════════════════════════════ *)
procedure UnregisterScheduledTask;
var
  R: Integer;
begin
  Exec('schtasks.exe',
    '/Delete /TN "' + SCHED_TASK_NAME + '" /F',
    '', SW_HIDE, ewWaitUntilTerminated, R);
  Log('Task Scheduler entry removed');
end;

(* ════════════════════════════════════════════════════════════
   Post-install step 3: Launch the call server
   ════════════════════════════════════════════════════════════ *)
procedure LaunchCallServer;
var
  R: Integer;
  Script: String;
begin
  WizardForm.StatusLabel.Caption := CustomMessage('StatusStartService');
  Script := ExpandConstant('{app}\adb_call_server.ps1');
  Exec('powershell.exe',
    '-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "' + Script + '"',
    '', SW_HIDE, ewNoWait, R);
  Log('Call server launched');
end;

(* ════════════════════════════════════════════════════════════
   Previous-install detection
   ════════════════════════════════════════════════════════════ *)
function FindPreviousUninstaller: String;
var
  S: String;
begin
  Result := '';
  if RegQueryStringValue(HKLM,
    'Software\Microsoft\Windows\CurrentVersion\Uninstall\' +
    '{8F3E2A1B-5C4D-4E6F-9A8B-7C2D1E3F4A5B}_is1',
    'UninstallString', S) then
    Result := S;
end;

(* ════════════════════════════════════════════════════════════
   EVENT: InitializeSetup
   — Detect previous install → offer Repair / Uninstall
   ════════════════════════════════════════════════════════════ *)
function InitializeSetup: Boolean;
var
  Prev: String;
  Btn, RC: Integer;
begin
  Result := True;
  Prev := FindPreviousUninstaller;
  if Prev = '' then Exit;

  Btn := MsgBox(CustomMessage('PrevInstallDetected'),
    mbConfirmation, MB_YESNOCANCEL);

  case Btn of
    IDYES:
      begin
        (* Repair — stop service, then continue normal install *)
        KillCallServer;
      end;
    IDNO:
      begin
        (* Uninstall — run existing uninstaller silently, then exit *)
        KillCallServer;
        Exec(RemoveQuotes(Prev), '/SILENT /NORESTART',
          '', SW_SHOWNORMAL, ewWaitUntilTerminated, RC);
        Result := False;
      end;
  else
    (* Cancel *)
    Result := False;
  end;
end;

(* ════════════════════════════════════════════════════════════
   EVENT: CurStepChanged — Post-install sequence
   ════════════════════════════════════════════════════════════ *)
procedure CurStepChanged(CurStep: TSetupStep);
begin
  if CurStep = ssPostInstall then begin
    Log('══ Post-install sequence ══');
    KillCallServer;
    InstallADB;
    RegisterScheduledTask;
    (* Registry Run key  → handled by [Registry] section  *)
    (* Startup shortcut  → handled by [Icons] section     *)
    LaunchCallServer;
    Log('══ Post-install complete ══');
  end;
end;

(* ════════════════════════════════════════════════════════════
   EVENT: CurUninstallStepChanged — Full cleanup
   ════════════════════════════════════════════════════════════ *)
procedure CurUninstallStepChanged(CurUninstallStep: TUninstallStep);
begin
  if CurUninstallStep = usUninstall then begin
    Log('══ Uninstall cleanup ══');
    KillCallServer;
    UnregisterScheduledTask;
    (* Registry Run key  → auto-removed by uninsdeletevalue flag *)
    (* Startup shortcut  → auto-removed by Inno Setup            *)
    (* App files         → auto-removed by [UninstallDelete]      *)
    Log('══ Uninstall complete ══');
  end;
end;
