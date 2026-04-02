$ADB = "C:\platform-tools\adb.exe"
$PORT = 9876
$prefix = "http://localhost:$PORT/"

if (-not (Test-Path $ADB)) {
    Write-Host "[ERROR] ADB not found at $ADB" -ForegroundColor Red
    Write-Host "Run _install_adb.bat first." -ForegroundColor Yellow
    Start-Sleep -Seconds 5
    exit 1
}

$listener = New-Object System.Net.HttpListener
$listener.Prefixes.Add($prefix)

try {
    $listener.Start()
} catch {
    Write-Host "[ERROR] Could not start on port $PORT - already running?" -ForegroundColor Red
    Start-Sleep -Seconds 3
    exit 1
}

Write-Host "============================================" -ForegroundColor Cyan
Write-Host " Tayseer ADB Call Server - Running" -ForegroundColor Cyan
Write-Host " Listening on: $prefix" -ForegroundColor Green
Write-Host " Press Ctrl+C to stop" -ForegroundColor Gray
Write-Host "============================================" -ForegroundColor Cyan

while ($listener.IsListening) {
    try {
        $context = $listener.GetContext()
        $request = $context.Request
        $response = $context.Response

        $response.Headers.Add("Access-Control-Allow-Origin", "*")
        $response.Headers.Add("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        $response.Headers.Add("Access-Control-Allow-Headers", "Content-Type")
        $response.ContentType = "application/json; charset=utf-8"

        if ($request.HttpMethod -eq "OPTIONS") {
            $response.StatusCode = 200
            $response.Close()
            continue
        }

        $phone = $request.QueryString["phone"]
        if (-not $phone) {
            $body = ""
            if ($request.HasEntityBody) {
                $reader = New-Object System.IO.StreamReader($request.InputStream)
                $body = $reader.ReadToEnd()
                $reader.Close()
                if ($body -match "phone=([^&]+)") {
                    $phone = [System.Uri]::UnescapeDataString($matches[1])
                }
            }
        }

        $phone = $phone -replace '[^0-9+]', ''

        if ($request.Url.AbsolutePath -eq "/status") {
            $devOut = & $ADB devices 2>&1 | Out-String
            $hasDevice = $devOut -match "(?m)\t\s*device\s*$"
            $json = @{ ok = $true; adb = $true; device = $hasDevice } | ConvertTo-Json -Compress
            $bytes = [System.Text.Encoding]::UTF8.GetBytes($json)
            $response.OutputStream.Write($bytes, 0, $bytes.Length)
            $response.Close()
            continue
        }

        if (-not $phone -or $phone.Length -lt 7) {
            $json = '{"ok":false,"error":"Invalid phone number"}'
            $bytes = [System.Text.Encoding]::UTF8.GetBytes($json)
            $response.OutputStream.Write($bytes, 0, $bytes.Length)
            $response.Close()
            continue
        }

        $devOut = & $ADB devices 2>&1 | Out-String
        if ($devOut -match "unauthorized") {
            $json = '{"ok":false,"error":"Device unauthorized - tap Allow on phone"}'
            $bytes = [System.Text.Encoding]::UTF8.GetBytes($json)
            $response.OutputStream.Write($bytes, 0, $bytes.Length)
            $response.Close()
            continue
        }
        if (-not ($devOut -match "(?m)\t\s*device\s*$")) {
            $json = '{"ok":false,"error":"No device connected via USB"}'
            $bytes = [System.Text.Encoding]::UTF8.GetBytes($json)
            $response.OutputStream.Write($bytes, 0, $bytes.Length)
            $response.Close()
            continue
        }

        $callOut = & $ADB shell am start -a android.intent.action.CALL -d "tel:$phone" 2>&1 | Out-String
        $ok = $callOut -match "Starting" -or $callOut -match "Activity"

        $timestamp = Get-Date -Format "HH:mm:ss"
        if ($ok) {
            Write-Host "[$timestamp] CALL $phone - OK" -ForegroundColor Green
        } else {
            Write-Host "[$timestamp] CALL $phone - FAILED: $callOut" -ForegroundColor Red
        }

        $json = @{ ok = $ok; phone = $phone } | ConvertTo-Json -Compress
        $bytes = [System.Text.Encoding]::UTF8.GetBytes($json)
        $response.OutputStream.Write($bytes, 0, $bytes.Length)
        $response.Close()

    } catch [System.Net.HttpListenerException] {
        break
    } catch {
        Write-Host "[ERROR] $($_.Exception.Message)" -ForegroundColor Red
    }
}

$listener.Stop()
