$ErrorActionPreference = 'Stop'
$projectDirectory = Split-Path -Parent $PSScriptRoot
$setupDirectory = Join-Path $projectDirectory 'storage\app\sqlserver-setup'
$archivePath = Join-Path $setupDirectory 'Windows_5.13.3RTW.zip'
$archiveHash = '1538292e1463dc706e0c3038d685b9e55781e592b0dc8634ecf54f44eef4d4d8'
New-Item -ItemType Directory -Path $setupDirectory -Force | Out-Null
if (-not (Test-Path -LiteralPath $archivePath)) {
    Invoke-WebRequest -Uri 'https://github.com/microsoft/msphpsql/releases/download/v5.13.3/Windows_5.13.3RTW.zip' -OutFile $archivePath
}
if ((Get-FileHash -LiteralPath $archivePath -Algorithm SHA256).Hash.ToLowerInvariant() -ne $archiveHash) {
    throw 'Microsoft driver archive checksum mismatch.'
}
Expand-Archive -LiteralPath $archivePath -DestinationPath (Join-Path $setupDirectory 'drivers-5.13.3') -Force
& (Join-Path $PSScriptRoot 'php.ps1') --ri pdo_sqlsrv
exit $LASTEXITCODE
