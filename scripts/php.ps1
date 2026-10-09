param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]] $PhpArguments
)

$ErrorActionPreference = 'Stop'
$projectDirectory = Split-Path -Parent $PSScriptRoot
$driverDirectory = Join-Path $projectDirectory 'storage\app\sqlserver-setup\drivers-5.13.3\Windows'
$configurationRoot = Join-Path $projectDirectory 'storage\app\sqlserver-setup\php-conf'
$configurationDirectory = Join-Path $configurationRoot ('runtime-' + $PID)
$phpExecutable = (Get-Command php.exe -ErrorAction Stop).Source
$phpDetails = @'
<?php
echo json_encode(['version' => PHP_MAJOR_VERSION . PHP_MINOR_VERSION, 'mode' => PHP_ZTS ? 'ts' : 'nts', 'arch' => PHP_INT_SIZE === 8 ? 'x64' : 'x86']);
'@ | & $phpExecutable
if ($LASTEXITCODE -ne 0) { throw 'Unable to inspect the PHP runtime.' }
$phpRuntime = $phpDetails | ConvertFrom-Json
$configuration = @('upload_max_filesize=5M', 'post_max_size=30M')
foreach ($extension in @('sqlsrv', 'pdo_sqlsrv')) {
    $driverFilename = 'php_{0}_{1}_{2}_{3}.dll' -f $extension, $phpRuntime.version, $phpRuntime.mode, $phpRuntime.arch
    $driverPath = Join-Path $driverDirectory $driverFilename
    if (-not (Test-Path -LiteralPath $driverPath)) {
        throw 'SQL Server driver is missing for this PHP version. Run scripts\setup-sqlserver.ps1 first.'
    }
    $configuration += 'extension="' + $driverPath.Replace('\', '/') + '"'
}
New-Item -ItemType Directory -Path $configurationDirectory -Force | Out-Null
[System.IO.File]::WriteAllLines((Join-Path $configurationDirectory 'sqlserver.ini'), $configuration, [System.Text.UTF8Encoding]::new($false))
$previousScanDirectory = $env:PHP_INI_SCAN_DIR
try {
    $env:PHP_INI_SCAN_DIR = if ($previousScanDirectory) { $previousScanDirectory + ';' + $configurationDirectory } else { $configurationDirectory }
    & $phpExecutable @PhpArguments
    $phpExitCode = $LASTEXITCODE
} finally {
    $env:PHP_INI_SCAN_DIR = $previousScanDirectory
    Remove-Item -LiteralPath (Join-Path $configurationDirectory 'sqlserver.ini') -ErrorAction SilentlyContinue
    if (Test-Path -LiteralPath $configurationDirectory) { [System.IO.Directory]::Delete($configurationDirectory) }
}
exit $phpExitCode
