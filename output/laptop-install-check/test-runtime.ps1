$ErrorActionPreference = 'Stop'
$repository = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '../..')).Path
. (Join-Path $repository 'scripts/laptop-setup-functions.ps1')
$script:SetupLog = $null
$temporaryBase = Join-Path ([IO.Path]::GetTempPath()) ('paolo-runtime-' + [guid]::NewGuid().ToString('N'))
$script:ProjectDirectory = Join-Path $temporaryBase "Owner's Laptop"
$script:RuntimeDirectory = Join-Path $script:ProjectDirectory 'storage/app/laptop-runtime'
$script:PackageDirectory = Join-Path $repository 'storage/app/laptop-setup/packages'
$previousLocation = Get-Location
try {
    foreach ($directory in @('scripts', 'app', 'database/factories', 'database/seeders', 'storage/app/laptop-runtime', 'storage/app/sqlserver-setup')) {
        New-Item -ItemType Directory -Path (Join-Path $script:ProjectDirectory $directory) -Force | Out-Null
    }
    foreach ($filename in @('check-php-runtime.php', 'php.ps1', 'setup-sqlserver.ps1')) {
        Copy-Item -LiteralPath (Join-Path $repository ('scripts/' + $filename)) -Destination (Join-Path $script:ProjectDirectory 'scripts')
    }
    Copy-Item -LiteralPath (Join-Path $repository 'storage/app/sqlserver-setup/Windows_5.13.3RTW.zip') -Destination (Join-Path $script:ProjectDirectory 'storage/app/sqlserver-setup')
    foreach ($filename in @('composer.json', 'composer.lock')) {
        Copy-Item -LiteralPath (Join-Path $repository $filename) -Destination $script:ProjectDirectory
    }
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
    $ProgressPreference = 'SilentlyContinue'
    Initialize-PrivatePhp
    & (Join-Path $script:ProjectDirectory 'scripts/setup-sqlserver.ps1')
    if ($LASTEXITCODE -ne 0) { throw 'Private PHP SQL Server driver test failed.' }
    Set-Location -LiteralPath $script:ProjectDirectory
    Install-ApplicationDependencies
    $probe = Join-Path $script:ProjectDirectory 'check-package.php'
    [IO.File]::WriteAllText($probe, "<?php require __DIR__.'/vendor/autoload.php'; if (!class_exists(Illuminate\Foundation\Application::class) || !class_exists(Barryvdh\DomPDF\Facade\Pdf::class)) exit(1); echo 'Private runtime, SQL drivers, HTTPS, and production dependencies passed.', PHP_EOL;")
    & $script:PrivatePhp $probe
    if ($LASTEXITCODE -ne 0) { throw 'Private runtime dependency test failed.' }
} finally {
    Set-Location -LiteralPath $previousLocation
    if (Test-Path -LiteralPath $temporaryBase) {
        $resolvedTarget = (Resolve-Path -LiteralPath $temporaryBase).Path
        $parent = [IO.Path]::GetFullPath([IO.Path]::GetTempPath()).TrimEnd('\') + '\'
        if (-not $resolvedTarget.StartsWith($parent, [StringComparison]::OrdinalIgnoreCase) -or [IO.Path]::GetFileName($resolvedTarget) -notmatch '^paolo-runtime-[0-9a-f]{32}$') { throw 'Unexpected runtime cleanup target.' }
        Remove-Item -LiteralPath $resolvedTarget -Recurse -Force
    }
}
