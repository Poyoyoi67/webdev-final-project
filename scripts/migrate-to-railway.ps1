# Migrates local Docker MySQL (test_demo_db) to Railway MySQL.
#
# Prerequisites:
#   - Local stack running: docker compose up (MySQL on port 3308)
#   - Railway MySQL PUBLIC URL (not mysql.railway.internal — that only works inside Railway)
#
# Usage:
#   $env:RAILWAY_DATABASE_URL = "mysql://root:PASSWORD@HOST:PORT/railway"
#   .\scripts\migrate-to-railway.ps1
#
# Get MYSQL_PUBLIC_URL from Railway → MySQL service → Variables → MYSQL_PUBLIC_URL

param(
    [string]$RailwayUrl = $env:RAILWAY_DATABASE_URL,
    [string]$LocalContainer = "healthcare-mysql-1",
    [string]$LocalUser = "test_demo_user",
    [string]$LocalPassword = "test_demo_password",
    [string]$LocalDatabase = "test_demo_db"
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$exportDir = Join-Path $PSScriptRoot "db-export"
$dumpFile = Join-Path $exportDir "local_full_dump.sql"

function Parse-MySqlUrl([string]$url) {
    if ($url -notmatch '^mysql://([^:]+):([^@]+)@([^:/]+):?(\d+)?/([^?]+)') {
        throw "Invalid MySQL URL. Use: mysql://user:pass@host:port/database"
    }
    return @{
        User = $matches[1]
        Password = [uri]::UnescapeDataString($matches[2])
        Host = $matches[3]
        Port = if ($matches[4]) { $matches[4] } else { "3306" }
        Database = $matches[5]
    }
}

if (-not $RailwayUrl) {
    Write-Host @"

Set your Railway PUBLIC MySQL URL first (from Railway → MySQL → Variables → MYSQL_PUBLIC_URL):

  `$env:RAILWAY_DATABASE_URL = "mysql://root:YOUR_PASSWORD@YOUR_HOST:PORT/railway"
  .\scripts\migrate-to-railway.ps1

"@
    exit 1
}

$rail = Parse-MySqlUrl $RailwayUrl

Write-Host "==> Checking local MySQL container..."
docker ps --format "{{.Names}}" | Select-String -Pattern $LocalContainer -Quiet | Out-Null
if (-not $?) {
    throw "Container '$LocalContainer' not running. Start: cd HealthCare; docker compose up -d"
}

New-Item -ItemType Directory -Force -Path $exportDir | Out-Null

Write-Host "==> Exporting local database '$LocalDatabase'..."
docker exec $LocalContainer sh -c "mysqldump -u$LocalUser -p$LocalPassword $LocalDatabase --single-transaction --routines --triggers --hex-blob > /tmp/local_full_dump.sql"
if ($LASTEXITCODE -ne 0) { throw "mysqldump failed inside container." }
docker cp "${LocalContainer}:/tmp/local_full_dump.sql" $dumpFile

if (-not (Test-Path $dumpFile) -or (Get-Item $dumpFile).Length -lt 500) {
    throw "Export failed or dump file is too small: $dumpFile"
}

Write-Host "    Dump saved: $dumpFile ($((Get-Item $dumpFile).Length) bytes)"

function Invoke-RailwayMysql([string]$Sql) {
    $pass = $rail.Password
    $prev = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    $out = docker run --rm mysql:8.0 mysql `
        -h $rail.Host -P $rail.Port "-u$($rail.User)" "-p$pass" `
        -e $Sql $rail.Database 2>&1
    $ErrorActionPreference = $prev
    if ($LASTEXITCODE -ne 0) {
        throw "MySQL command failed: $out"
    }
}

function Import-RailwayDump() {
    $pass = $rail.Password
    $prev = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    docker run --rm -v "${dumpFile}:/dump.sql:ro" mysql:8.0 sh -c `
        "mysql --binary-mode=1 -h $($rail.Host) -P $($rail.Port) -u$($rail.User) -p$pass $($rail.Database) < /dump.sql"
    $code = $LASTEXITCODE
    $ErrorActionPreference = $prev
    if ($code -ne 0) {
        throw "Import failed. Verify MYSQL_PUBLIC_URL and that Railway MySQL is online."
    }
}

Write-Host "==> Testing Railway connection ($($rail.Host):$($rail.Port))..."
try {
    Invoke-RailwayMysql "SELECT 1 AS ok"
} catch {
    throw "Cannot connect to Railway MySQL. Use MYSQL_PUBLIC_URL from Railway (not mysql.railway.internal)."
}

Write-Host "==> Importing into Railway database '$($rail.Database)' (replaces existing tables/data)..."
Import-RailwayDump

Write-Host "==> Verifying row counts on Railway..."
Invoke-RailwayMysql "SELECT 'user' t, COUNT(*) c FROM user UNION SELECT 'doctor', COUNT(*) FROM doctor UNION SELECT 'service', COUNT(*) FROM service UNION SELECT 'appointment', COUNT(*) FROM appointment UNION SELECT 'doctor_availability', COUNT(*) FROM doctor_availability;"

Write-Host ""
Write-Host "Done. Local data is now on Railway."
