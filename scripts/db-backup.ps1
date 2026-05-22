param(
    [string]$OutputDir = ".\storage\backups\db",
    [string]$EnvFile = ".\.env"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

function Get-EnvValue {
    param(
        [string]$Key,
        [string[]]$Lines
    )

    $prefix = "$Key="
    foreach ($line in $Lines) {
        if ($line.StartsWith($prefix)) {
            return $line.Substring($prefix.Length).Trim('"')
        }
    }

    throw "Missing '$Key' in $EnvFile"
}

if (!(Test-Path $EnvFile)) {
    throw ".env file not found: $EnvFile"
}

$envLines = Get-Content $EnvFile
$dbHost = Get-EnvValue -Key "DB_HOST" -Lines $envLines
$dbPort = Get-EnvValue -Key "DB_PORT" -Lines $envLines
$dbName = Get-EnvValue -Key "DB_DATABASE" -Lines $envLines
$dbUser = Get-EnvValue -Key "DB_USERNAME" -Lines $envLines
$dbPass = Get-EnvValue -Key "DB_PASSWORD" -Lines $envLines

$mysqldumpPath = "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe"
if (!(Test-Path $mysqldumpPath)) {
    throw "mysqldump not found: $mysqldumpPath"
}

if (!(Test-Path $OutputDir)) {
    New-Item -Path $OutputDir -ItemType Directory -Force | Out-Null
}

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupFile = Join-Path $OutputDir "$dbName`_$timestamp.sql"

& $mysqldumpPath `
    "--host=$dbHost" `
    "--port=$dbPort" `
    "--user=$dbUser" `
    "--password=$dbPass" `
    "--single-transaction" `
    "--quick" `
    "--routines" `
    "--events" `
    "--triggers" `
    $dbName `
    "--result-file=$backupFile"

Write-Host "Backup created: $backupFile"
