# SecureCAT-v2 - Demo Launch (PowerShell)
# Run from project root: .\demo-launch.ps1
# Requirements: php, npm, ngrok, python in PATH
#
# This script ensures a CLEAN demo environment:
#   - Kills ANY lingering dev servers (Vite, php artisan serve, ngrok)
#   - Removes public/hot so Laravel uses built assets (not Vite HMR)
#   - Runs npm run build (never skips) so we serve production assets
#   - Sets ASSET_URL=. so @vite() generates relative paths behind ngrok
#   - Starts ONLY: php artisan serve + queue listener + ngrok + slide server

$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$APP_PORT   = 8000
$SLIDE_PORT = 9090
$NGROK_API  = 'http://localhost:4040/api/tunnels'
$MAILPIT_PORT = 8025

function Get-CdpErrors($port, $sec) {
    $err = @()
    try {
        $ws = New-Object System.Net.WebSockets.ClientWebSocket
        $ct = [Threading.CancellationToken]::None
        $ws.ConnectAsync((Invoke-RestMethod "$port/json" -TimeoutSec 3)[0].webSocketDebuggerUrl, $ct).Wait()
        '{"id":1,"method":"Runtime.enable"}','{"id":2,"method":"Log.enable"}' | % { $ws.SendAsync([ArraySegment[byte]][Text.Encoding]::UTF8.GetBytes($_), 'Text', $true, $ct) }
        $buf = [byte[]]::new(32768); $end = (Get-Date).AddSeconds($sec)
        while ((Get-Date) -lt $end -and $ws.State -eq 'Open') {
            $r = $ws.ReceiveAsync([ArraySegment[byte]]$buf, $ct)
            if ($r.Wait(500) -and $r.Result.Count -gt 0) {
                $j = [Text.Encoding]::UTF8.GetString($buf,0,$r.Result.Count) | ConvertFrom-Json -EA SilentlyContinue
                if ($j.method -match "exceptionThrown|consoleAPICalled|entryAdded" -and ($j.method -eq "Runtime.exceptionThrown" -or $j.params.type -eq "error" -or $j.params.entry.level -eq "error")) { $err += $j }
            }
        }
        $ws.CloseAsync('NormalClosure', "", $ct).Wait()
    } catch {}
    $err
}

# ── Cleanup ─────────────────────────────────────────────────────────────────────
Write-Host ''
Write-Host '[CLEANUP] Stopping all lingering demo processes...'

# Kill ALL node/npm/vite processes (Vite dev server is the main culprit)
Get-Process -Name node -ErrorAction SilentlyContinue | Stop-Process -Force
Get-Process -Name npm -ErrorAction SilentlyContinue | Stop-Process -Force

# Kill ngrok, php artisan serve, queue listener, slide servers
Get-Process -Name ngrok -ErrorAction SilentlyContinue | Stop-Process -Force
Get-Process -Name php -ErrorAction SilentlyContinue | Stop-Process -Force
Get-Process | Where-Object { $_.CommandLine -match "http\.server.*$SLIDE_PORT" } -ErrorAction SilentlyContinue | Stop-Process -Force

# Critical: remove public/hot so @vite() uses built assets, not Vite HMR
if (Test-Path "$PSScriptRoot\public\hot") {
    Write-Host '      Removed public/hot (ensures Laravel uses production build)'
    Remove-Item "$PSScriptRoot\public\hot" -Force
}

# Kill anything on our ports
$occupied = @($APP_PORT, $SLIDE_PORT, 5173, 5174, 5175, 5176)
foreach ($port in $occupied) {
    $conn = Get-NetTCPConnection -LocalPort $port -ErrorAction SilentlyContinue
    if ($conn) {
        $pid = $conn.OwningProcess | Select-Object -First 1
        if ($pid -and $pid -ne 0) {
            Stop-Process -Id $pid -Force -ErrorAction SilentlyContinue
            Write-Host "      Freed port $port"
        }
    }
}
Start-Sleep 1

# ── Preflight checks ─────────────────────────────────────────────────────────────
Write-Host ''
Write-Host '+----------------------------------------------------+'
Write-Host '|  SecureCAT-v2  -  Demo Launch (Production Build)   |'
Write-Host '+----------------------------------------------------+'
Write-Host ''

foreach ($cmd in @('php','npm','ngrok','python')) {
    if (-not (Get-Command $cmd -ErrorAction SilentlyContinue)) {
        Write-Error "ERROR: '$cmd' not found in PATH."
        exit 1
    }
}
Write-Host '  All prerequisites found.'

# ── Step 1: ALWAYS build (never skip for demos) ─────────────────────────────────
Write-Host ''
Write-Host '[1/5] Building frontend (npm run build)...'
npm run build
if ($LASTEXITCODE -ne 0) {
    Write-Error 'npm run build failed. Fix errors before re-running.'
    exit 1
}
Write-Host '  Done.'

# Verify hot file is gone (double-check)
if (Test-Path "$PSScriptRoot\public\hot") {
    Write-Host '  WARNING: public/hot still exists after build — removing it.'
    Remove-Item "$PSScriptRoot\public\hot" -Force
}

# ── Step 2: Seed demo data ──────────────────────────────────────────────────────
Write-Host ''
Write-Host '[2/5] Seeding demo data (php artisan demo:setup)...'
php artisan demo:setup
Write-Host '  Done.'

# ── Step 3: Laravel server (NO dev server) ─────────────────────────────────────
Write-Host ''
Write-Host "[3/5] Starting Laravel server on port $APP_PORT..."
$serve = Start-Process php `
    -ArgumentList 'artisan','serve',"--port=$APP_PORT" `
    -WorkingDirectory $PSScriptRoot `
    -PassThru -WindowStyle Hidden `
    -RedirectStandardOutput "$env:TEMP\demo-serve.log" `
    -RedirectStandardError  "$env:TEMP\demo-serve-err.log"

if (-not $serve -or $serve.Id -eq 0) {
    Write-Error 'Laravel server failed to start. Check: ' + "$env:TEMP\demo-serve-err.log"
    exit 1
}
Start-Sleep 2
Write-Host "  PID $($serve.Id) running at http://localhost:$APP_PORT"

# ── Step 4: Queue listener ─────────────────────────────────────────────────────
Write-Host ''
Write-Host '[4/5] Starting queue listener...'
$queue = Start-Process php `
    -ArgumentList 'artisan','queue:listen','--timeout=60' `
    -WorkingDirectory $PSScriptRoot `
    -PassThru -WindowStyle Hidden `
    -RedirectStandardOutput "$env:TEMP\demo-queue.log" `
    -RedirectStandardError  "$env:TEMP\demo-queue-err.log"

if (-not $queue -or $queue.Id -eq 0) {
    Write-Warning 'Queue listener failed to start. Notifications may not send.'
} else {
    Write-Host "  PID $($queue.Id) running."
}

# ── Step 5: ngrok tunnel ───────────────────────────────────────────────────────
Write-Host ''
Write-Host '[5/5] Starting ngrok tunnel to port '"$APP_PORT"'...'
$ngrok = Start-Process ngrok `
    -ArgumentList 'http','--log=stdout',"$APP_PORT" `
    -PassThru -WindowStyle Hidden `
    -RedirectStandardOutput "$env:TEMP\demo-ngrok.log" `
    -RedirectStandardError  "$env:TEMP\demo-ngrok-err.log"

$ngrokMailpit = Start-Process ngrok `
    -ArgumentList 'http','--log=stdout',"$MAILPIT_PORT" `
    -PassThru -WindowStyle Hidden `
    -RedirectStandardOutput "$env:TEMP\demo-ngrok-mailpit.log" `
    -RedirectStandardError  "$env:TEMP\demo-ngrok-mailpit-err.log"

Write-Host '  Waiting for ngrok to initialize (up to 20 s)...'
$ngrokUrl = ''
for ($i = 0; $i -lt 20; $i++) {
    Start-Sleep 1
    try {
        $resp = Invoke-RestMethod $NGROK_API -TimeoutSec 3 -ErrorAction SilentlyContinue
        $tunnel = $resp.tunnels | Where-Object {
            $_.proto -eq 'https' -and ($_.config.addr -match ":$APP_PORT`$" -or $_.public_url -match "ngrok-free.app")
        } | Select-Object -First 1
        if (-not $tunnel) {
            $tunnel = $resp.tunnels | Where-Object { $_.proto -eq 'https' } | Select-Object -First 1
        }
        if ($tunnel.public_url) { $ngrokUrl = $tunnel.public_url; break }
    } catch {}
}

if (-not $ngrokUrl) {
    Write-Warning '  Could not read ngrok URL. Check http://localhost:4040/api/tunnels'
    $ngrokUrl = "http://localhost:$APP_PORT"
}
Write-Host "  App URL: $ngrokUrl"

$ngrokMailpitUrl = ''
for ($i = 0; $i -lt 20; $i++) {
    Start-Sleep 1
    try {
        $resp2 = Invoke-RestMethod 'http://localhost:4041/api/tunnels' -TimeoutSec 3 -ErrorAction SilentlyContinue
        $mp = $resp2.tunnels | Where-Object { $_.proto -eq 'https' } | Select-Object -First 1
        if ($mp.public_url) { $ngrokMailpitUrl = $mp.public_url; break }
    } catch {}
}
if (-not $ngrokMailpitUrl) {
    Write-Warning '  Could not read mailpit tunnel URL. Check http://localhost:4041'
    $ngrokMailpitUrl = "http://localhost:$MAILPIT_PORT"
}
Write-Host "  Mailpit: $ngrokMailpitUrl"

# ── Step 6: Serve presentation slides ─────────────────────────────────────────
Write-Host ''
Write-Host "[6/6] Serving presentation slides on port $SLIDE_PORT..."
$slides = Start-Process python `
    -ArgumentList '-m','http.server',$SLIDE_PORT `
    -WorkingDirectory $PSScriptRoot `
    -PassThru -WindowStyle Hidden `
    -RedirectStandardOutput "$env:TEMP\demo-slides.log" `
    -RedirectStandardError  "$env:TEMP\demo-slides-err.log"
Start-Sleep 1
Write-Host "  Slides at http://localhost:$SLIDE_PORT"

$encoded  = [System.Uri]::EscapeDataString($ngrokUrl)
$slideUrl = "http://localhost:${SLIDE_PORT}/system-defense.html?url=$encoded"

Write-Host ''
Write-Host 'Opening presentation...'
Start-Process $slideUrl

# ── Final banner ────────────────────────────────────────────────────────────────
Write-Host ''
Write-Host '+--------------------------------------------------------------------+'
Write-Host '|            ALL SYSTEMS GO  -  SecureCAT Demo is Live              |'
Write-Host '+--------------------------------------------------------------------+'
Write-Host "|  App (local):  http://localhost:$APP_PORT"
Write-Host "|  App (public): $ngrokUrl"
Write-Host "|  Mailpit:      $ngrokMailpitUrl"
Write-Host "|  Slides:       http://localhost:$SLIDE_PORT  ($slideUrl)"
Write-Host '+--------------------------------------------------------------------+'
Write-Host '|  HOW IT WORKS:                                                     |'
Write-Host '|    npm run build     -> static assets in public/build/            |'
Write-Host '|    public/hot        -> REMOVED (so @vite uses built assets)       |'
Write-Host '|    php artisan serve -> serves Laravel + static assets on :8000   |'
Write-Host '|    ngrok             -> tunnels :8000 only (no Vite dev server)  |'
Write-Host '|    Result            -> no Vite host-blocking, clean ngrok access |'
Write-Host '+--------------------------------------------------------------------+'
Write-Host '|  NEXT STEPS:                                                       |'
Write-Host '|    1. Presentation opened -> go to Slide 6                        |'
Write-Host '|    2. Browser A -> staff@securecat-v2.test / Password1!          |'
Write-Host '|    3. Browser B -> applicant@securecat-v2.test / Password1!       |'
Write-Host '|    4. Follow Demo-template.md for the full demo script            |'
Write-Host '+--------------------------------------------------------------------+'
Write-Host '|  Logs:                                                             |'
Write-Host "|    $env:TEMP\demo-serve.log     $env:TEMP\demo-queue.log"
Write-Host "|    $env:TEMP\demo-ngrok.log     $env:TEMP\demo-slides.log"
Write-Host '+--------------------------------------------------------------------+'
Write-Host '|  Press Ctrl+C or close this window to stop all services.          |'
Write-Host '+--------------------------------------------------------------------+'
Write-Host ''

# ── Keep alive + graceful shutdown ─────────────────────────────────────────────
$jobs = @($serve, $queue, $ngrok, $ngrokMailpit, $slides) | Where-Object { $_ }

try {
    Wait-Process -Id $serve.Id -ErrorAction SilentlyContinue
} finally {
    Write-Host ''
    Write-Host 'Shutting down demo services...'
    $jobs | Where-Object { $_ -and $_.Id } | ForEach-Object {
        Stop-Process -Id $_.Id -Force -ErrorAction SilentlyContinue
    }
    Write-Host 'All services stopped. Goodbye.'
}