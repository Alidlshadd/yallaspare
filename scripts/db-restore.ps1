param(
    [Parameter(Mandatory = $true)]
    [string]$BackupFile,
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

if (!(Test-Path $BackupFile)) {
    throw "Backup file not found: $BackupFile"
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

$mysqlPath = "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe"
if (!(Test-Path $mysqlPath)) {
    throw "mysql not found: $mysqlPath"
}

Get-Content $BackupFile | & $mysqlPath `
    "--host=$dbHost" `
    "--port=$dbPort" `
    "--user=$dbUser" `
    "--password=$dbPass" `
    $dbName

Write-Host "Restore completed from: $BackupFile"
