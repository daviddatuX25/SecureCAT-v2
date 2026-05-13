# SecureCAT Installation Pathway — Spec

**Date:** 2026-05-13
**Status:** Draft
**Depends on:** [Tiered Licensing Design](../superpowers/specs/2026-05-12-tiered-licensing-design.md)

---

## 1. Goal

Provide a single-command installation experience for SecureCAT on Windows machines running Laragon. Two delivery phases:

| Phase | Deliverable | Effort | When |
|-------|------------|--------|------|
| **Now** | `install.ps1` PowerShell script + `securecat:install` artisan command | Low | During licensing implementation |
| **Later** | `SecureCAT-Setup.exe` via Inno Setup (bundles all dependencies) | Medium | When distributing to external clients |

---

## 2. Architecture

```
User downloads SecureCAT
        │
        ▼
install.ps1  ←── PowerShell bootstrap (Phase 1)
   OR
SecureCAT-Setup.exe  ←── Windows installer (Phase 2)
        │
        ├─ 1. Check prerequisites (PHP, Composer, Node, PostgreSQL)
        ├─ 2. Install Ollama if missing (winget)
        ├─ 3. Pull required AI models
        ├─ 4. composer install + npm install + npm run build
        ├─ 5. Configure .env from template
        ├─ 6. php artisan securecat:install
        │       ├─ Generate installation_id (hardware fingerprint)
        │       ├─ Run migrations
        │       ├─ Seed default data
        │       ├─ Prompt for license key (or skip → trial)
        │       ├─ Call LicenseService::resolve()
        │       ├─ Verify Ollama connectivity
        │       └─ Write results to .env
        └─ 7. Print success + URL
```

---

## 3. Phase 1: PowerShell Bootstrap Script

### 3.1 File: `install.ps1` (project root)

```powershell
#Requires -Version 5.1
# SecureCAT Installer — Run: Right-click → Run with PowerShell
# Or: powershell -ExecutionPolicy Bypass -File install.ps1

param(
    [switch]$SkipOllama,       # Skip Ollama installation
    [switch]$SkipModels,       # Skip model pulling (slow)
    [switch]$Force,            # Overwrite existing .env
    [string]$LicenseKey = ""   # Pre-supply license key (non-interactive)
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# ─── Banner ───
Write-Host ""
Write-Host "  ╔═══════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "  ║   SecureCAT Installer v1.0            ║" -ForegroundColor Cyan
Write-Host "  ║   Computerized Admission & Testing    ║" -ForegroundColor Cyan
Write-Host "  ╚═══════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# ─── Helper Functions ───

function Write-Check($label, $ok) {
    if ($ok) {
        Write-Host "  ✓ $label" -ForegroundColor Green
    } else {
        Write-Host "  ✗ $label" -ForegroundColor Red
    }
    return $ok
}

function Test-Command($cmd) {
    return [bool](Get-Command $cmd -ErrorAction SilentlyContinue)
}

# ─── Step 1: Prerequisites ───

Write-Host "── Step 1: Checking Prerequisites ──" -ForegroundColor Yellow
Write-Host ""

$phpOk    = Write-Check "PHP 8.4+"        (Test-Command "php")
$compOk   = Write-Check "Composer"        (Test-Command "composer")
$nodeOk   = Write-Check "Node.js"         (Test-Command "node")
$npmOk    = Write-Check "npm"             (Test-Command "npm")

# Check PostgreSQL via pg_isready or psql
$pgOk = $false
if (Test-Command "pg_isready") {
    $pgResult = pg_isready 2>&1
    $pgOk = $LASTEXITCODE -eq 0
} elseif (Test-Command "psql") {
    $pgOk = $true
}
Write-Check "PostgreSQL" $pgOk | Out-Null

Write-Host ""

# Verify PHP version
if ($phpOk) {
    $phpVersion = (php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;") 2>&1
    if ([version]$phpVersion -lt [version]"8.4") {
        Write-Host "  ⚠ PHP $phpVersion detected — 8.4+ required" -ForegroundColor Red
        $phpOk = $false
    }
}

# Check required PHP extensions
if ($phpOk) {
    $requiredExts = @("pdo_pgsql", "pgsql", "mbstring", "openssl", "curl", "xml", "zip", "gd")
    $loadedExts = (php -m) 2>&1
    foreach ($ext in $requiredExts) {
        $found = $loadedExts | Where-Object { $_ -eq $ext }
        if (-not $found) {
            Write-Host "  ⚠ Missing PHP extension: $ext" -ForegroundColor Red
            Write-Host "    → Enable in php.ini: extension=$ext" -ForegroundColor DarkGray
        }
    }
}

# Bail if critical deps missing
if (-not ($phpOk -and $compOk -and $nodeOk)) {
    Write-Host ""
    Write-Host "  MISSING PREREQUISITES. Install via Laragon:" -ForegroundColor Red
    Write-Host "  → PHP 8.4:      Laragon → Menu → PHP → Add Version" -ForegroundColor DarkGray
    Write-Host "  → Composer:     Included with Laragon" -ForegroundColor DarkGray
    Write-Host "  → Node.js:      https://nodejs.org (LTS)" -ForegroundColor DarkGray
    Write-Host "  → PostgreSQL:   Laragon → Menu → PostgreSQL → Add Version" -ForegroundColor DarkGray
    exit 1
}

# ─── Step 2: Ollama ───

if (-not $SkipOllama) {
    Write-Host "── Step 2: Ollama Setup ──" -ForegroundColor Yellow
    Write-Host ""

    if (-not (Test-Command "ollama")) {
        Write-Host "  Installing Ollama via winget..." -ForegroundColor DarkGray

        if (Test-Command "winget") {
            winget install Ollama.Ollama --accept-package-agreements --accept-source-agreements
            # Refresh PATH
            $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" +
                        [System.Environment]::GetEnvironmentVariable("Path","User")
        } else {
            Write-Host "  ⚠ winget not found. Install Ollama manually:" -ForegroundColor Red
            Write-Host "    → https://ollama.com/download/windows" -ForegroundColor DarkGray
        }
    }

    if (Test-Command "ollama") {
        Write-Check "Ollama installed" $true | Out-Null

        if (-not $SkipModels) {
            Write-Host "  Pulling mxbai-embed-large (embeddings)..." -ForegroundColor DarkGray
            ollama pull mxbai-embed-large

            Write-Host "  Pulling llama3 (LLM)..." -ForegroundColor DarkGray
            ollama pull llama3
        }

        # Verify Ollama is running
        try {
            $ollamaCheck = Invoke-RestMethod -Uri "http://localhost:11434/api/tags" -TimeoutSec 5
            Write-Check "Ollama responding at localhost:11434" $true | Out-Null
        } catch {
            Write-Host "  ⚠ Ollama installed but not responding." -ForegroundColor Yellow
            Write-Host "    → Start Ollama from the system tray or run: ollama serve" -ForegroundColor DarkGray
        }
    }
} else {
    Write-Host "── Step 2: Ollama (skipped) ──" -ForegroundColor DarkGray
}

Write-Host ""

# ─── Step 3: Dependencies ───

Write-Host "── Step 3: Installing Dependencies ──" -ForegroundColor Yellow
Write-Host ""

Write-Host "  Running composer install..." -ForegroundColor DarkGray
composer install --no-interaction --optimize-autoloader 2>&1 | Out-Null
Write-Check "Composer dependencies" $? | Out-Null

Write-Host "  Running npm install..." -ForegroundColor DarkGray
npm install 2>&1 | Out-Null
Write-Check "NPM dependencies" $? | Out-Null

Write-Host "  Building frontend assets..." -ForegroundColor DarkGray
npm run build 2>&1 | Out-Null
Write-Check "Frontend build" $? | Out-Null

Write-Host ""

# ─── Step 4: Environment ───

Write-Host "── Step 4: Environment Configuration ──" -ForegroundColor Yellow
Write-Host ""

if ((Test-Path ".env") -and -not $Force) {
    Write-Host "  .env already exists (use -Force to overwrite)" -ForegroundColor DarkGray
} else {
    Copy-Item ".env.example" ".env" -Force
    Write-Check ".env created from template" $true | Out-Null
}

# Generate app key if missing
$envContent = Get-Content ".env" -Raw
if ($envContent -match "APP_KEY=$" -or $envContent -match "APP_KEY=\s*$") {
    php artisan key:generate --no-interaction 2>&1 | Out-Null
    Write-Check "APP_KEY generated" $true | Out-Null
}

Write-Host ""

# ─── Step 5: Artisan Install ───

Write-Host "── Step 5: SecureCAT Setup ──" -ForegroundColor Yellow
Write-Host ""

if ($LicenseKey) {
    php artisan securecat:install --license="$LicenseKey" --no-interaction
} else {
    php artisan securecat:install
}

Write-Host ""

# ─── Done ───

Write-Host ""
Write-Host "  ╔═══════════════════════════════════════╗" -ForegroundColor Green
Write-Host "  ║   ✓ SecureCAT is ready!               ║" -ForegroundColor Green
Write-Host "  ║                                       ║" -ForegroundColor Green
Write-Host "  ║   Start Laragon → visit your URL      ║" -ForegroundColor Green
Write-Host "  ╚═══════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""
```

### 3.2 Double-Click Wrapper: `setup-securecat.bat`

For non-technical users who don't know how to right-click → Run with PowerShell:

```batch
@echo off
echo Starting SecureCAT Installer...
powershell -ExecutionPolicy Bypass -File "%~dp0install.ps1"
pause
```

### 3.3 Usage

```powershell
# Default — interactive, installs everything
.\install.ps1

# Pre-supply license key (for automated deployments)
.\install.ps1 -LicenseKey "SCAT-XXXX-XXXX-XXXX"

# Skip Ollama (if already installed separately)
.\install.ps1 -SkipOllama

# Skip model pulling (for slow connections — pull later)
.\install.ps1 -SkipOllama -SkipModels

# Force overwrite existing .env
.\install.ps1 -Force
```

---

## 4. Artisan Install Command

### 4.1 File: `app/Console/Commands/SecurecatInstall.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LicenseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SecurecatInstall extends Command
{
    protected $signature = 'securecat:install
        {--license= : License key (leave blank for trial)}
        {--skip-ollama : Skip Ollama connectivity check}';

    protected $description = 'First-time SecureCAT setup: migrations, fingerprint, license, Ollama check';

    public function handle(LicenseService $license): int
    {
        $this->components->info('SecureCAT First-Time Setup');
        $this->newLine();

        // 1. Generate installation_id
        $this->components->task('Generating installation fingerprint', function () {
            $id = $this->generateInstallationId();
            $this->updateEnv('SECURECAT_INSTALLATION_ID', $id);
            return true;
        });

        // 2. Run migrations
        $this->components->task('Running database migrations', function () {
            $this->callSilently('migrate', ['--force' => true]);
            return true;
        });

        // 3. Seed default data
        $this->components->task('Seeding default data', function () {
            $this->callSilently('db:seed', ['--force' => true]);
            return true;
        });

        // 4. License activation
        $key = $this->option('license')
            ?: $this->ask('License key (leave blank for 14-day trial)', '');

        if ($key) {
            $this->updateEnv('SECURECAT_LICENSE_KEY', $key);
            $this->components->task('Validating license', fn () => $license->resolve());
            $tier = config('securecat.tier', 'standard');
            $this->components->info("License tier: {$tier}");
        } else {
            $this->components->task('Activating trial', fn () => $license->resolve());
            $this->components->warn('Trial activated — 14-day time limit, usage limits apply');
        }

        // 5. Ollama check
        if (! $this->option('skip-ollama')) {
            $this->components->task('Checking Ollama connectivity', function () {
                try {
                    $response = Http::timeout(5)->get('http://localhost:11434/api/tags');
                    if ($response->ok()) {
                        $models = collect($response->json('models', []))
                            ->pluck('name')->toArray();

                        $hasEmbed = in_array('mxbai-embed-large:latest', $models);
                        if (! $hasEmbed) {
                            $this->components->warn(
                                'mxbai-embed-large not found. Run: ollama pull mxbai-embed-large'
                            );
                        }
                        return true;
                    }
                    return false;
                } catch (\Exception) {
                    $this->components->warn(
                        'Ollama not responding at localhost:11434. Start it or set LLM_DRIVER=openrouter'
                    );
                    return false;
                }
            });
        }

        // 6. Summary
        $this->newLine();
        $this->components->info('✓ SecureCAT is ready!');
        $this->line('  Visit your Laragon URL to get started.');
        $this->newLine();

        return self::SUCCESS;
    }

    private function generateInstallationId(): string
    {
        // Hardware fingerprint: MAC address + hostname
        $mac = $this->getPrimaryMac();
        $hostname = gethostname();
        return hash('sha256', $mac . '|' . $hostname);
    }

    private function getPrimaryMac(): string
    {
        // Windows: parse getmac output
        $output = shell_exec('getmac /fo csv /nh 2>&1');
        if ($output && preg_match('/([0-9A-F]{2}-){5}[0-9A-F]{2}/i', $output, $m)) {
            return $m[0];
        }
        return Str::uuid()->toString(); // Fallback
    }

    private function updateEnv(string $key, string $value): void
    {
        $path = base_path('.env');
        $content = file_get_contents($path);

        if (str_contains($content, "{$key}=")) {
            $content = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $content
            );
        } else {
            $content .= "\n{$key}={$value}";
        }

        file_put_contents($path, $content);
    }
}
```

### 4.2 Expected Output

```
SecureCAT First-Time Setup

  Generating installation fingerprint ........ DONE
  Running database migrations ................ DONE
  Seeding default data ....................... DONE

  License key (leave blank for 14-day trial):
  > [enter]

  Activating trial ........................... DONE

  WARN  Trial activated — 14-day time limit, usage limits apply

  Checking Ollama connectivity ............... DONE

  INFO  ✓ SecureCAT is ready!
  Visit your Laragon URL to get started.
```

---

## 5. Phase 2: Windows Installer (.exe)

### When to Build This

Build the .exe installer when:
- First external client needs to install SecureCAT
- Client IT staff cannot run PowerShell scripts
- You want a "professional software" distribution feel

### Tool: Inno Setup (Free)

[Inno Setup](https://jrsoftware.org/isinfo.php) — free, mature, widely used for Windows installers.

### What the .exe Would Bundle

| Component | Size (approx) | Bundled? |
|-----------|--------------|----------|
| Laragon Portable | ~150 MB | ✅ Yes |
| PHP 8.4 (with extensions) | ~30 MB | ✅ Yes (inside Laragon) |
| PostgreSQL 16 | ~100 MB | ✅ Yes (inside Laragon) |
| Node.js LTS | ~40 MB | ✅ Yes |
| Ollama installer | ~100 MB | ✅ Yes |
| SecureCAT source | ~50 MB | ✅ Yes |
| AI models (mxbai + llama3) | ~4 GB | ❌ Downloaded post-install |
| **Total installer** | **~470 MB** | |

### Inno Setup Script Skeleton

```iss
; SecureCAT-Setup.iss — Inno Setup script (skeleton)
[Setup]
AppName=SecureCAT
AppVersion=1.0.0
DefaultDirName={autopf}\SecureCAT
DefaultGroupName=SecureCAT
OutputBaseFilename=SecureCAT-Setup-v1.0.0
Compression=lzma2/ultra64
SetupIconFile=resources\securecat-icon.ico

[Files]
; Bundle Laragon portable
Source: "bundle\laragon-portable\*"; DestDir: "{app}\laragon"; Flags: recursesubdirs

; Bundle SecureCAT source
Source: "bundle\securecat\*"; DestDir: "{app}\securecat"; Flags: recursesubdirs

; Bundle Ollama installer
Source: "bundle\OllamaSetup.exe"; DestDir: "{tmp}"

[Run]
; Install Ollama silently
Filename: "{tmp}\OllamaSetup.exe"; Parameters: "/SILENT"; StatusMsg: "Installing Ollama..."

; Run SecureCAT install
Filename: "{app}\laragon\bin\php\php-8.4\php.exe"; \
    Parameters: "artisan securecat:install --no-interaction"; \
    WorkingDir: "{app}\securecat"; \
    StatusMsg: "Configuring SecureCAT..."

[Icons]
Name: "{group}\SecureCAT"; Filename: "{app}\laragon\laragon.exe"
Name: "{commondesktop}\SecureCAT"; Filename: "{app}\laragon\laragon.exe"
```

### Installer UX Flow

```
┌─────────────────────────────────────┐
│  Welcome to SecureCAT Setup         │
│                                     │
│  This will install SecureCAT v1.0   │
│  on your computer.                  │
│                                     │
│  [Next >]   [Cancel]                │
└─────────────────────────────────────┘
        │
        ▼
┌─────────────────────────────────────┐
│  License Agreement                  │
│  ┌─────────────────────────────┐    │
│  │ SecureCAT License Terms...  │    │
│  └─────────────────────────────┘    │
│  ☑ I accept the agreement          │
│                                     │
│  [< Back]  [Next >]  [Cancel]       │
└─────────────────────────────────────┘
        │
        ▼
┌─────────────────────────────────────┐
│  Installation Directory             │
│  C:\SecureCAT  [Browse...]          │
│                                     │
│  Required space: 500 MB             │
│  Available: 120 GB                  │
│                                     │
│  [< Back]  [Next >]  [Cancel]       │
└─────────────────────────────────────┘
        │
        ▼
┌─────────────────────────────────────┐
│  License Key                        │
│                                     │
│  Enter your license key:            │
│  ┌─────────────────────────────┐    │
│  │ SCAT-____-____-____         │    │
│  └─────────────────────────────┘    │
│  ☐ Start with 14-day free trial    │
│                                     │
│  [< Back]  [Install]  [Cancel]      │
└─────────────────────────────────────┘
        │
        ▼
┌─────────────────────────────────────┐
│  Installing...                      │
│  ████████████░░░░░░░░  60%          │
│                                     │
│  Setting up database...             │
│                                     │
│  [Cancel]                           │
└─────────────────────────────────────┘
        │
        ▼
┌─────────────────────────────────────┐
│  ✓ Setup Complete!                  │
│                                     │
│  SecureCAT has been installed.      │
│  ☑ Launch SecureCAT now             │
│                                     │
│  NOTE: AI models will download      │
│  on first use (~4 GB).              │
│                                     │
│  [Finish]                           │
└─────────────────────────────────────┘
```

---

## 6. Companion: `securecat:status` Command

Diagnostic command for support/troubleshooting after installation:

```
php artisan securecat:status

  SecureCAT Status
  ────────────────────────────────────
  Version:          1.0.0
  Installation ID:  a3f8c...d7e2
  License Tier:     Premium
  License Expires:  2027-05-12
  Last Validated:   2 hours ago

  Dependencies
  ────────────────────────────────────
  PHP:              8.4.1 ✓
  PostgreSQL:       16.2  ✓
  Node.js:          24.1  ✓
  Ollama:           0.5.1 ✓
    mxbai-embed:    loaded ✓
    llama3:         loaded ✓

  Features
  ────────────────────────────────────
  AI Companion:     ✓ enabled (unlimited)
  AI Scheduling:    ✓ enabled (unlimited)
  Public Portal:    ✓ enabled
  Applicant Email:  ✓ enabled (relay: smtp.securecat.ph)

  Connectivity
  ────────────────────────────────────
  License Server:   ✓ reachable (42ms)
  Ollama:           ✓ responding (localhost:11434)
  SMTP Relay:       ✓ connected (smtp.securecat.ph:587)
```

---

## 7. File Inventory

| File | Type | Phase |
|------|------|-------|
| `install.ps1` | PowerShell bootstrap | Now |
| `setup-securecat.bat` | Double-click wrapper for install.ps1 | Now |
| `app/Console/Commands/SecurecatInstall.php` | Artisan first-boot command | Now |
| `app/Console/Commands/SecurecatStatus.php` | Artisan diagnostic command | Now |
| `INSTALL.md` | Root-level quick-start doc | Now |
| `docs/deployment/premium-setup.md` | Cloudflare + SMTP guide (premium) | Now |
| `docs/deployment/troubleshooting.md` | "If X fails, do Y" reference | Now |
| `SecureCAT-Setup.iss` | Inno Setup installer script | Later |
| `bundle/` | Bundled dependencies for .exe | Later |

---

## 8. INSTALL.md (Root-Level Quick Start)

This is the only doc most users need:

```markdown
# Installing SecureCAT

## Quick Start (Windows + Laragon)

### Prerequisites
- [Laragon](https://laragon.org/) with PHP 8.4 and PostgreSQL 16
- [Node.js](https://nodejs.org/) LTS (v24+)
- [Ollama](https://ollama.com/) (optional — for AI features)

### One-Command Install

Double-click `setup-securecat.bat` or run:

    powershell -ExecutionPolicy Bypass -File install.ps1

### Manual Install

    composer install
    npm install && npm run build
    copy .env.example .env
    php artisan key:generate
    php artisan securecat:install

### Verify

    php artisan securecat:status

### Need Help?

See `docs/deployment/troubleshooting.md`
```

---

## 9. Out of Scope

- **Licensing server setup** — separate project, separate installer
- **Linux/macOS support** — Laragon is Windows-only; Docker path is a future option
- **Auto-update mechanism** — `git pull` + `composer install` for now
- **CI/CD pipeline** — not needed until multi-client distribution
