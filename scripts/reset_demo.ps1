param(
    [string]$MysqlPath = 'C:\xampp\mysql\bin\mysql.exe',
    [string]$RootUser = 'root',
    [string]$RootPassword = '',
    [string]$DbName = 'ecoride_db',
    [switch]$Yes
)

Write-Host "EcoRide — Reset demo data" -ForegroundColor Green
Write-Host "MySQL client: $MysqlPath" -ForegroundColor DarkGray

if (-not $Yes) {
    $ans = Read-Host "This will DROP and RECREATE '$DbName'. Proceed? (y/N)"
    if ($ans -ne 'y' -and $ans -ne 'Y') { Write-Host 'Aborted.'; exit 1 }
}

if (-not (Test-Path $MysqlPath)) {
    Write-Error "mysql.exe not found at '$MysqlPath'"; exit 1
}

$ScriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot   = Split-Path -Parent $ScriptRoot

function Invoke-MySql {
    param([string[]]$Args)
    $pwdArg = @()
    if ($RootPassword -ne '') { $pwdArg = @("-p$RootPassword") }
    & $MysqlPath -u $RootUser @pwdArg @Args
    if ($LASTEXITCODE -ne 0) { throw "mysql.exe failed ($LASTEXITCODE)" }
}

try {
    Write-Host "Recreating database..." -ForegroundColor Yellow
    Invoke-MySql -Args @("-e","DROP DATABASE IF EXISTS $DbName; CREATE DATABASE $DbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;")

    $dump = Join-Path $RepoRoot 'ecoride_db.sql'
    if (Test-Path $dump) {
        Write-Host "Importing base dump..." -ForegroundColor Yellow
        Invoke-MySql -Args @($DbName, "-e", "SOURCE $dump")
    } else {
        Write-Warning "Base dump not found: $dump"
    }

    $scripts = @(
        Join-Path $RepoRoot 'database\20241104_schema_upgrade.sql',
        Join-Path $RepoRoot 'database\20241107_credit_upgrade.sql',
        Join-Path $RepoRoot 'database\20251107_add_travel_status.sql',
        Join-Path $RepoRoot 'database\20251107_add_departure_index.sql',
        Join-Path $RepoRoot 'database\sample\seed_minimal.sql'
    )
    foreach ($f in $scripts) {
        if (Test-Path $f) {
            Write-Host "Applying: $(Split-Path $f -Leaf)" -ForegroundColor Yellow
            Invoke-MySql -Args @($DbName, "-e", "SOURCE $f")
        }
    }

    # Ensure auto-confirm OFF by default (configuration default label 'default')
    $toggle = @(
      "INSERT IGNORE INTO configurations (label) VALUES ('default');",
      "INSERT IGNORE INTO parameters (property, default_value) VALUES ('booking_auto_confirm','1');",
      "INSERT INTO configuration_parameters (configuration_id, parameter_id, value)",
      "SELECT c.id, p.id, '0' FROM configurations c, parameters p",
      "WHERE c.label='default' AND p.property='booking_auto_confirm'",
      "ON DUPLICATE KEY UPDATE value=VALUES(value);"
    ) -join ' '
    Invoke-MySql -Args @($DbName, "-e", $toggle)

    Write-Host "Reset complete." -ForegroundColor Green
} catch {
    Write-Error $_
    exit 1
}

