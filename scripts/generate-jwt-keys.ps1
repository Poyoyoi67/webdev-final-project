# Generates config/jwt/private.pem and public.pem using OpenSSL.
# Passphrase must match JWT_PASSPHRASE in .env

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$jwtDir = Join-Path $root 'config\jwt'
$envFile = Join-Path $root '.env'

if (-not (Test-Path $envFile)) {
    Write-Error ".env not found at $envFile"
}

$pass = $null
Get-Content $envFile | ForEach-Object {
    if ($_ -match '^JWT_PASSPHRASE=(.+)$') {
        $pass = $matches[1].Trim()
    }
}
if (-not $pass) {
    Write-Error 'JWT_PASSPHRASE not set in .env'
}

$opensslCandidates = @(
    'C:\Program Files\Git\usr\bin\openssl.exe',
    'C:\Program Files\OpenSSL-Win64\bin\openssl.exe',
    'openssl'
)
$openssl = $opensslCandidates | Where-Object { $_ -eq 'openssl' -or (Test-Path $_) } | Select-Object -First 1
if (-not $openssl) {
    Write-Error 'OpenSSL not found. Install Git for Windows or OpenSSL.'
}

New-Item -ItemType Directory -Force -Path $jwtDir | Out-Null
$private = Join-Path $jwtDir 'private.pem'
$public = Join-Path $jwtDir 'public.pem'

Write-Host "Generating RSA keys in $jwtDir ..."
& $openssl genrsa -aes256 -passout "pass:$pass" -out $private 4096
& $openssl rsa -pubout -in $private -passin "pass:$pass" -out $public
Write-Host 'Done. Run: php bin/console cache:clear'
