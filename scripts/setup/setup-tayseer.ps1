<#
.SYNOPSIS
    Tayseer ERP - Full Local Development Setup Script
    سكربت إعداد بيئة التطوير المحلية لنظام تيسير

.DESCRIPTION
    This script automates the entire setup process:
    1. Downloads and installs Laragon (if not installed)
    2. Installs Composer (PHP dependency manager)
    3. Installs all PHP packages via composer install
    4. Creates MySQL databases and user
    5. Imports SQL backup files
    6. Configures Yii2 local environment files
    7. Creates required writable directories

.NOTES
    HOW TO RUN THIS SCRIPT:
    ========================
    1. Open PowerShell as Administrator (Right-click PowerShell > Run as Administrator)
    2. If you get an execution policy error, run this first:
       Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser -Force
    3. Navigate to the project folder:
       cd "C:\Users\PC\Desktop\Tayseer"
    4. Run the script:
       .\setup-tayseer.ps1

    REQUIREMENTS:
    =============
    - Windows 10 or later
    - Internet connection (for downloading Laragon, Composer, and PHP packages)
    - At least 2 GB free disk space
    - PowerShell 5.1 or later (pre-installed on Windows 10+)
#>

# ============================================================
#  CONFIGURATION - يمكنك تعديل هذه القيم حسب حاجتك
# ============================================================
$Config = @{
    # Laragon
    LaragonPath          = "C:\laragon"
    LaragonDownloadUrl   = "https://github.com/niceilm/laragon/releases/download/6.0.0/laragon-wamp.exe"
    LaragonInstallerName = "laragon-wamp.exe"

    # MySQL
    DbHost               = "localhost"
    DbPort               = 3306
    DbRootUser           = "root"
    DbRootPassword       = ""
    DbAppUser            = "tayseer_user"
    DbAppPassword        = $env:TAYSEER_DB_APP_PASSWORD ?? "ChangeMe!Tayseer"
    DbNameErp            = "namaa_erp"
    DbNameJadal          = "namaa_jadal"
    DbTablePrefix        = "os_"

    # Project
    ProjectPath          = $PSScriptRoot
}

# ============================================================
#  HELPER FUNCTIONS
# ============================================================

function Write-Banner {
    param([string]$Text)
    $line = "=" * 60
    Write-Host ""
    Write-Host $line -ForegroundColor Cyan
    Write-Host "  $Text" -ForegroundColor Yellow
    Write-Host $line -ForegroundColor Cyan
}

function Write-Step {
    param([int]$Num, [string]$Text)
    Write-Host ""
    Write-Host "[$Num] $Text" -ForegroundColor Green
    Write-Host ("-" * 50) -ForegroundColor DarkGray
}

function Write-Info {
    param([string]$Text)
    Write-Host "    [INFO] $Text" -ForegroundColor Gray
}

function Write-OK {
    param([string]$Text)
    Write-Host "    [OK] $Text" -ForegroundColor Green
}

function Write-Warn {
    param([string]$Text)
    Write-Host "    [WARN] $Text" -ForegroundColor Yellow
}

function Write-Err {
    param([string]$Text)
    Write-Host "    [ERROR] $Text" -ForegroundColor Red
}

function Test-Admin {
    $currentPrincipal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
    return $currentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Find-LaragonBinary {
    param([string]$BinType, [string]$ExeName)
    $binRoot = Join-Path $Config.LaragonPath "bin\$BinType"
    if (-not (Test-Path $binRoot)) { return $null }

    $dirs = Get-ChildItem -Path $binRoot -Directory | Sort-Object Name -Descending
    foreach ($dir in $dirs) {
        $exePath = Join-Path $dir.FullName $ExeName
        if (Test-Path $exePath) { return $exePath }
        $binSubPath = Join-Path $dir.FullName "bin\$ExeName"
        if (Test-Path $binSubPath) { return $binSubPath }
    }
    return $null
}

function Add-ToSessionPath {
    param([string]$Dir)
    if ($Dir -and (Test-Path $Dir)) {
        $parent = Split-Path $Dir -Parent
        if ($env:Path -notlike "*$parent*") {
            $env:Path = "$parent;$env:Path"
            Write-Info "Added to PATH: $parent"
        }
    }
}

# ============================================================
#  MAIN SCRIPT
# ============================================================

Write-Banner "Tayseer ERP - Setup Script"
Write-Host "  Project: $($Config.ProjectPath)" -ForegroundColor White
Write-Host "  Date   : $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor White

# Check admin privileges
if (-not (Test-Admin)) {
    Write-Warn "This script is NOT running as Administrator."
    Write-Warn "Some operations (like installing Laragon) may fail."
    Write-Host ""
    $continue = Read-Host "Continue anyway? (y/n)"
    if ($continue -ne 'y') {
        Write-Host "Aborted. Please re-run as Administrator." -ForegroundColor Red
        exit 1
    }
}

# ============================================================
#  STEP 1: Check / Install Laragon
# ============================================================
Write-Step 1 "Checking Laragon installation..."

$laragonExe = Join-Path $Config.LaragonPath "laragon.exe"
if (Test-Path $laragonExe) {
    Write-OK "Laragon found at: $($Config.LaragonPath)"
} else {
    Write-Warn "Laragon not found at $($Config.LaragonPath)"
    Write-Info "Downloading Laragon installer..."

    $tempDir = Join-Path $env:TEMP "tayseer-setup"
    if (-not (Test-Path $tempDir)) { New-Item -ItemType Directory -Path $tempDir -Force | Out-Null }
    $installerPath = Join-Path $tempDir $Config.LaragonInstallerName

    try {
        $ProgressPreference = 'SilentlyContinue'
        Invoke-WebRequest -Uri $Config.LaragonDownloadUrl -OutFile $installerPath -UseBasicParsing
        $ProgressPreference = 'Continue'
        Write-OK "Downloaded Laragon installer to: $installerPath"
    } catch {
        Write-Err "Failed to download Laragon automatically."
        Write-Host ""
        Write-Host "    Please download Laragon manually from:" -ForegroundColor Yellow
        Write-Host "    https://laragon.org/download/" -ForegroundColor White
        Write-Host ""
        Write-Host "    Install it to: C:\laragon" -ForegroundColor White
        Write-Host "    Then re-run this script." -ForegroundColor Yellow
        Write-Host ""
        $manualContinue = Read-Host "    Or press ENTER if you already installed Laragon elsewhere"
        if (-not (Test-Path $laragonExe)) {
            Write-Err "Laragon still not found. Aborting."
            exit 1
        }
    }

    if (Test-Path $installerPath) {
        Write-Info "Running Laragon installer (follow the on-screen instructions)..."
        Write-Info "IMPORTANT: Install to C:\laragon (default path)"
        Start-Process -FilePath $installerPath -Wait
        if (Test-Path $laragonExe) {
            Write-OK "Laragon installed successfully!"
        } else {
            Write-Err "Laragon installation may have failed. Check if it's installed at C:\laragon"
            $manualPath = Read-Host "    Enter Laragon path (or press ENTER to abort)"
            if ($manualPath -and (Test-Path (Join-Path $manualPath "laragon.exe"))) {
                $Config.LaragonPath = $manualPath
                Write-OK "Using Laragon at: $manualPath"
            } else {
                Write-Err "Cannot continue without Laragon. Aborting."
                exit 1
            }
        }
    }
}

# ============================================================
#  STEP 2: Locate PHP and MySQL binaries from Laragon
# ============================================================
Write-Step 2 "Locating PHP and MySQL from Laragon..."

$phpExe = Find-LaragonBinary "php" "php.exe"
$mysqlExe = Find-LaragonBinary "mysql" "mysql.exe"

# Also check system PATH as fallback
if (-not $phpExe) {
    $phpExe = (Get-Command php -ErrorAction SilentlyContinue)?.Source
}
if (-not $mysqlExe) {
    $mysqlExe = (Get-Command mysql -ErrorAction SilentlyContinue)?.Source
}

if ($phpExe) {
    Write-OK "PHP found: $phpExe"
    $phpVersion = & $phpExe -v 2>&1 | Select-Object -First 1
    Write-Info "Version: $phpVersion"
    Add-ToSessionPath $phpExe
} else {
    Write-Err "PHP not found! Make sure Laragon is installed with PHP."
    Write-Host "    Laragon should have PHP in: $($Config.LaragonPath)\bin\php\" -ForegroundColor Yellow
    exit 1
}

if ($mysqlExe) {
    Write-OK "MySQL client found: $mysqlExe"
    Add-ToSessionPath $mysqlExe
} else {
    Write-Err "MySQL client not found! Make sure Laragon is installed with MySQL."
    Write-Host "    Laragon should have MySQL in: $($Config.LaragonPath)\bin\mysql\" -ForegroundColor Yellow
    exit 1
}

# ============================================================
#  STEP 3: Start Laragon services (MySQL + Apache)
# ============================================================
Write-Step 3 "Starting Laragon services..."

$laragonExe = Join-Path $Config.LaragonPath "laragon.exe"
if (Test-Path $laragonExe) {
    Write-Info "Starting Laragon (this will start Apache + MySQL)..."
    Start-Process -FilePath $laragonExe -ArgumentList "start" -WindowStyle Normal

    Write-Info "Waiting for MySQL to become available..."
    $retries = 0
    $maxRetries = 30
    $mysqlReady = $false

    while ($retries -lt $maxRetries) {
        Start-Sleep -Seconds 2
        $retries++
        try {
            $testResult = & $mysqlExe -u $Config.DbRootUser --password="$($Config.DbRootPassword)" -h $Config.DbHost -P $Config.DbPort -e "SELECT 1" 2>&1
            if ($testResult -match "1") {
                $mysqlReady = $true
                break
            }
        } catch { }
        Write-Host "." -NoNewline
    }

    if ($mysqlReady) {
        Write-OK "MySQL is running and accepting connections!"
    } else {
        Write-Warn "Could not verify MySQL connection after $maxRetries attempts."
        Write-Warn "Please make sure Laragon is running and MySQL is started."
        Write-Host ""
        $continueAnyway = Read-Host "    Continue anyway? (y/n)"
        if ($continueAnyway -ne 'y') { exit 1 }
    }
} else {
    Write-Warn "Cannot auto-start Laragon. Please start it manually."
    Write-Host "    Open Laragon and click 'Start All'" -ForegroundColor Yellow
    Read-Host "    Press ENTER when Laragon services are running"
}

# ============================================================
#  STEP 4: Install / Verify Composer
# ============================================================
Write-Step 4 "Checking Composer..."

$composerCmd = $null

# Check common locations
$composerLocations = @(
    (Join-Path $Config.LaragonPath "bin\composer\composer.phar"),
    (Join-Path $Config.LaragonPath "bin\composer\composer.bat"),
    "C:\ProgramData\ComposerSetup\bin\composer.bat"
)

foreach ($loc in $composerLocations) {
    if (Test-Path $loc) {
        $composerCmd = $loc
        break
    }
}

# Check system PATH
if (-not $composerCmd) {
    $systemComposer = (Get-Command composer -ErrorAction SilentlyContinue)?.Source
    if ($systemComposer) { $composerCmd = $systemComposer }
}

if ($composerCmd) {
    Write-OK "Composer found: $composerCmd"
} else {
    Write-Info "Composer not found. Installing Composer..."

    $composerInstallerUrl = "https://getcomposer.org/installer"
    $composerInstaller = Join-Path $env:TEMP "composer-setup.php"

    try {
        $ProgressPreference = 'SilentlyContinue'
        Invoke-WebRequest -Uri $composerInstallerUrl -OutFile $composerInstaller -UseBasicParsing
        $ProgressPreference = 'Continue'

        $composerDir = Join-Path $Config.LaragonPath "bin\composer"
        if (-not (Test-Path $composerDir)) {
            New-Item -ItemType Directory -Path $composerDir -Force | Out-Null
        }

        Push-Location $composerDir
        & $phpExe $composerInstaller --install-dir=$composerDir --filename=composer.phar 2>&1
        Pop-Location

        $composerPhar = Join-Path $composerDir "composer.phar"
        if (Test-Path $composerPhar) {
            $composerCmd = $composerPhar
            Write-OK "Composer installed at: $composerPhar"
        } else {
            Write-Err "Composer installation failed."
            Write-Host "    Please install Composer manually from: https://getcomposer.org/download/" -ForegroundColor Yellow
            exit 1
        }
    } catch {
        Write-Err "Failed to download Composer installer: $_"
        exit 1
    }
}

# Determine how to invoke composer
function Invoke-Composer {
    param([string[]]$Arguments)
    $allArgs = @()
    if ($composerCmd -like "*.phar") {
        & $phpExe $composerCmd @Arguments 2>&1
    } else {
        & $composerCmd @Arguments 2>&1
    }
}

# ============================================================
#  STEP 5: Install PHP dependencies (composer install)
# ============================================================
Write-Step 5 "Installing PHP dependencies (composer install)..."

Push-Location $Config.ProjectPath
Write-Info "Working directory: $($Config.ProjectPath)"
Write-Info "This may take several minutes on the first run..."

$composerResult = Invoke-Composer @("install", "--no-interaction", "--prefer-dist")
$composerResult | ForEach-Object { Write-Host "    $_" }

if ($LASTEXITCODE -eq 0) {
    Write-OK "Composer dependencies installed successfully!"
} else {
    Write-Warn "Composer install completed with warnings/errors. Check the output above."
    Write-Info "You can retry later with: composer install"
}
Pop-Location

# ============================================================
#  STEP 6: Create MySQL databases and user
# ============================================================
Write-Step 6 "Setting up MySQL databases and user..."

function Invoke-MySql {
    param([string]$Query, [string]$Database = "")
    $args = @("-u", $Config.DbRootUser, "-h", $Config.DbHost, "-P", $Config.DbPort.ToString())
    if ($Config.DbRootPassword) {
        $args += "--password=$($Config.DbRootPassword)"
    }
    if ($Database) {
        $args += $Database
    }
    $args += "-e"
    $args += $Query
    & $mysqlExe @args 2>&1
}

# Create databases
Write-Info "Creating database: $($Config.DbNameErp)..."
Invoke-MySql "CREATE DATABASE IF NOT EXISTS ``$($Config.DbNameErp)`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if ($LASTEXITCODE -eq 0) { Write-OK "Database $($Config.DbNameErp) ready." }
else { Write-Warn "Could not create $($Config.DbNameErp) - it may already exist." }

Write-Info "Creating database: $($Config.DbNameJadal)..."
Invoke-MySql "CREATE DATABASE IF NOT EXISTS ``$($Config.DbNameJadal)`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if ($LASTEXITCODE -eq 0) { Write-OK "Database $($Config.DbNameJadal) ready." }
else { Write-Warn "Could not create $($Config.DbNameJadal) - it may already exist." }

# Create app user
Write-Info "Creating MySQL user: $($Config.DbAppUser)..."
Invoke-MySql "CREATE USER IF NOT EXISTS '$($Config.DbAppUser)'@'localhost' IDENTIFIED BY '$($Config.DbAppPassword)';"
Invoke-MySql "GRANT ALL PRIVILEGES ON ``$($Config.DbNameErp)``.* TO '$($Config.DbAppUser)'@'localhost';"
Invoke-MySql "GRANT ALL PRIVILEGES ON ``$($Config.DbNameJadal)``.* TO '$($Config.DbAppUser)'@'localhost';"
Invoke-MySql "FLUSH PRIVILEGES;"
Write-OK "MySQL user $($Config.DbAppUser) created with full access."

# ============================================================
#  STEP 7: Import SQL backup files
# ============================================================
Write-Step 7 "Importing SQL backup files..."

$backupDir = Join-Path $Config.ProjectPath "_backups"
$sqlErp    = Join-Path $backupDir "namaa_erp 14-02-2026.sql"
$sqlJadal  = Join-Path $backupDir "namaa_jadal 14-02-2026.sql"

function Import-SqlFile {
    param([string]$FilePath, [string]$Database)

    if (-not (Test-Path $FilePath)) {
        Write-Warn "SQL file not found: $FilePath"
        return $false
    }

    $sizeMB = [math]::Round((Get-Item $FilePath).Length / 1MB, 1)
    Write-Info "Importing $FilePath ($sizeMB MB) into $Database..."
    Write-Info "This may take a few minutes for large files..."

    $importArgs = @("-u", $Config.DbRootUser, "-h", $Config.DbHost, "-P", $Config.DbPort.ToString())
    if ($Config.DbRootPassword) {
        $importArgs += "--password=$($Config.DbRootPassword)"
    }
    $importArgs += $Database

    Get-Content $FilePath -Raw | & $mysqlExe @importArgs 2>&1

    if ($LASTEXITCODE -eq 0) {
        Write-OK "Successfully imported into $Database"
        return $true
    } else {
        Write-Err "Failed to import into $Database"
        return $false
    }
}

# Check for SQL files
if (Test-Path $backupDir) {
    # Find the most recent SQL backup for each database
    $erpFiles = Get-ChildItem -Path $backupDir -Filter "namaa_erp*.sql" | Sort-Object LastWriteTime -Descending
    $jadalFiles = Get-ChildItem -Path $backupDir -Filter "namaa_jadal*.sql" | Sort-Object LastWriteTime -Descending

    if ($erpFiles.Count -gt 0) {
        $selectedErp = $erpFiles[0].FullName
        if ($erpFiles.Count -gt 1) {
            Write-Info "Found multiple ERP backups, using newest: $($erpFiles[0].Name)"
        }
        Import-SqlFile -FilePath $selectedErp -Database $Config.DbNameErp
    } else {
        Write-Warn "No namaa_erp*.sql backup found in _backups/"
    }

    if ($jadalFiles.Count -gt 0) {
        $selectedJadal = $jadalFiles[0].FullName
        if ($jadalFiles.Count -gt 1) {
            Write-Info "Found multiple Jadal backups, using newest: $($jadalFiles[0].Name)"
        }
        Import-SqlFile -FilePath $selectedJadal -Database $Config.DbNameJadal
    } else {
        Write-Warn "No namaa_jadal*.sql backup found in _backups/"
    }
} else {
    Write-Warn "Backup directory not found: $backupDir"
    Write-Info "You'll need to import database SQL files manually."
}

# ============================================================
#  STEP 8: Configure Yii2 local environment files
# ============================================================
Write-Step 8 "Configuring Yii2 local environment files..."

# Update common/config/main-local.php with Laragon DB credentials
$mainLocalPath = Join-Path $Config.ProjectPath "common\config\main-local.php"

$mainLocalContent = @"
<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=$($Config.DbHost);dbname=$($Config.DbNameErp)',
            'username' => '$($Config.DbAppUser)',
            'password' => '$($Config.DbAppPassword)',
            'charset' => 'utf8',
            'tablePrefix' => '$($Config.DbTablePrefix)',
            'enableSchemaCache' => true,
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            'useFileTransport' => true,
        ],
    ],
];
"@

# Backup existing file
if (Test-Path $mainLocalPath) {
    $backupPath = "$mainLocalPath.bak.$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    Copy-Item $mainLocalPath $backupPath
    Write-Info "Backed up existing config to: $backupPath"
}

Set-Content -Path $mainLocalPath -Value $mainLocalContent -Encoding UTF8
Write-OK "Updated: common/config/main-local.php"

# Verify backend and frontend local configs exist
$configFiles = @(
    "backend\config\main-local.php",
    "frontend\config\main-local.php",
    "console\config\main-local.php",
    "backend\config\params-local.php",
    "frontend\config\params-local.php",
    "console\config\params-local.php",
    "common\config\params-local.php"
)

foreach ($cf in $configFiles) {
    $fullPath = Join-Path $Config.ProjectPath $cf
    if (-not (Test-Path $fullPath)) {
        Write-Warn "Missing config file: $cf - Creating default..."
        $dir = Split-Path $fullPath -Parent
        if (-not (Test-Path $dir)) {
            New-Item -ItemType Directory -Path $dir -Force | Out-Null
        }

        if ($cf -like "*main-local.php") {
            $defaultContent = @"
<?php
`$config = [
    'components' => [
        'request' => [
            'cookieValidationKey' => '$(New-Guid)',
        ],
    ],
];

return `$config;
"@
        } else {
            $defaultContent = @"
<?php
return [
];
"@
        }
        Set-Content -Path $fullPath -Value $defaultContent -Encoding UTF8
        Write-OK "Created: $cf"
    } else {
        Write-OK "Exists: $cf"
    }
}

# ============================================================
#  STEP 9: Create required writable directories
# ============================================================
Write-Step 9 "Creating required writable directories..."

$writableDirs = @(
    "backend\runtime",
    "backend\web\assets",
    "frontend\runtime",
    "frontend\web\assets",
    "console\runtime",
    "backend\web\images",
    "backend\web\images\imagemanager"
)

foreach ($dir in $writableDirs) {
    $fullPath = Join-Path $Config.ProjectPath $dir
    if (-not (Test-Path $fullPath)) {
        New-Item -ItemType Directory -Path $fullPath -Force | Out-Null
        Write-OK "Created: $dir"
    } else {
        Write-OK "Exists: $dir"
    }
}

# ============================================================
#  STEP 10: Run Yii2 migrations (if applicable)
# ============================================================
Write-Step 10 "Running pending database migrations..."

$yiiConsole = Join-Path $Config.ProjectPath "yii"
if (Test-Path $yiiConsole) {
    Push-Location $Config.ProjectPath
    Write-Info "Checking for pending migrations..."
    $migrationResult = & $phpExe $yiiConsole migrate --interactive=0 2>&1
    $migrationResult | ForEach-Object { Write-Host "    $_" }
    if ($LASTEXITCODE -eq 0) {
        Write-OK "Migrations completed."
    } else {
        Write-Warn "Migration command returned warnings/errors. This may be normal on first setup."
    }
    Pop-Location
} else {
    Write-Warn "Yii console script not found. Skipping migrations."
}

# ============================================================
#  STEP 11: Configure Laragon Virtual Host (Optional)
# ============================================================
Write-Step 11 "Configuring Laragon virtual host..."

$vhostDir = Join-Path $Config.LaragonPath "etc\apache2\sites-enabled"
$vhostFile = Join-Path $vhostDir "auto.tayseer.test.conf"

if (Test-Path $vhostDir) {
    $backendWebPath = (Join-Path $Config.ProjectPath "backend\web").Replace("\", "/")

    $vhostContent = @"
<VirtualHost *:80>
    ServerName tayseer.test
    ServerAlias www.tayseer.test
    DocumentRoot "$backendWebPath"

    <Directory "$backendWebPath">
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks

        # Yii2 URL rewrite
        RewriteEngine on
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . index.php
    </Directory>
</VirtualHost>
"@

    Set-Content -Path $vhostFile -Value $vhostContent -Encoding UTF8
    Write-OK "Virtual host created: tayseer.test -> backend/web"

    # Add to Windows hosts file
    $hostsFile = "C:\Windows\System32\drivers\etc\hosts"
    $hostsContent = Get-Content $hostsFile -Raw -ErrorAction SilentlyContinue
    if ($hostsContent -notlike "*tayseer.test*") {
        try {
            Add-Content -Path $hostsFile -Value "`n127.0.0.1    tayseer.test" -ErrorAction Stop
            Write-OK "Added tayseer.test to Windows hosts file."
        } catch {
            Write-Warn "Could not modify hosts file (need Administrator privileges)."
            Write-Info "Add this line manually to C:\Windows\System32\drivers\etc\hosts:"
            Write-Host "    127.0.0.1    tayseer.test" -ForegroundColor White
        }
    } else {
        Write-OK "tayseer.test already in hosts file."
    }
} else {
    Write-Warn "Laragon vhost directory not found. Skipping virtual host setup."
    Write-Info "You can access the app via: http://localhost/Tayseer/backend/web/"
}

# ============================================================
#  STEP 12: Final Summary
# ============================================================
Write-Banner "SETUP COMPLETE!"

Write-Host ""
Write-Host "  Application URLs:" -ForegroundColor White
Write-Host "  ================================================" -ForegroundColor DarkGray
Write-Host "  Backend (Main App) : http://tayseer.test" -ForegroundColor Cyan
Write-Host "  Alternative        : http://localhost/Tayseer/backend/web/" -ForegroundColor Cyan
Write-Host "  Mobile App         : http://tayseer.test/hr/field/mobile-login" -ForegroundColor Cyan
Write-Host ""
Write-Host "  Database Information:" -ForegroundColor White
Write-Host "  ================================================" -ForegroundColor DarkGray
Write-Host "  MySQL Host : $($Config.DbHost):$($Config.DbPort)" -ForegroundColor White
Write-Host "  DB User    : $($Config.DbAppUser)" -ForegroundColor White
Write-Host "  DB Password: $($Config.DbAppPassword)" -ForegroundColor White
Write-Host "  Database 1 : $($Config.DbNameErp) (Main ERP)" -ForegroundColor White
Write-Host "  Database 2 : $($Config.DbNameJadal) (Jadal Module)" -ForegroundColor White
Write-Host ""
Write-Host "  Quick Commands:" -ForegroundColor White
Write-Host "  ================================================" -ForegroundColor DarkGray
Write-Host "  Restart Laragon   : Open Laragon > Stop All > Start All" -ForegroundColor Gray
Write-Host "  Clear Yii2 Cache  : php yii cache/flush-all" -ForegroundColor Gray
Write-Host "  Run Migrations    : php yii migrate" -ForegroundColor Gray
Write-Host "  Composer Update   : composer update" -ForegroundColor Gray
Write-Host ""
Write-Host "  Troubleshooting:" -ForegroundColor White
Write-Host "  ================================================" -ForegroundColor DarkGray
Write-Host "  - If page shows blank: Check backend/runtime/logs/ for errors" -ForegroundColor Gray
Write-Host "  - If DB error: Verify Laragon MySQL is running (green icon)" -ForegroundColor Gray
Write-Host "  - If 404 error: Make sure Apache mod_rewrite is enabled in Laragon" -ForegroundColor Gray
Write-Host "  - Config files: common/config/main-local.php (DB settings)" -ForegroundColor Gray
Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "  Setup finished at $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""
