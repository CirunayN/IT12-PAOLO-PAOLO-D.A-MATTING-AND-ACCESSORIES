param([string] $PrerequisiteDirectory = (Join-Path (Split-Path -Parent $PSScriptRoot) 'storage\app\laptop-setup\packages'), [string] $AdminUsername)

$ErrorActionPreference = 'Stop'
$project = Split-Path -Parent $PSScriptRoot
Push-Location -LiteralPath $project
try {
    $exportArguments = @('artisan', 'app:export-laptop-admin', '--no-interaction')
    if ($AdminUsername) { $exportArguments += '--username=' + $AdminUsername }
    & (Join-Path $PSScriptRoot 'php.ps1') @exportArguments
    if ($LASTEXITCODE -ne 0) { throw 'The administrator could not be prepared. No deployment ZIP was generated.' }
} finally { Pop-Location }
$output = Join-Path $project 'output\deployment'
$staging = Join-Path $project ('storage\app\laptop-package-' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $output, $staging -Force | Out-Null
$zipPath = Join-Path $output 'Paolo-Paolo-Laptop-Setup.zip'
try {
    foreach ($directory in @('app', 'config', 'resources', 'routes')) {
        Copy-Item -LiteralPath (Join-Path $project $directory) -Destination $staging -Recurse
    }
    New-Item -ItemType Directory -Path (Join-Path $staging 'database') -Force | Out-Null
    foreach ($directory in @('migrations', 'seeders', 'factories')) {
        Copy-Item -LiteralPath (Join-Path $project ('database\' + $directory)) -Destination (Join-Path $staging 'database') -Recurse
    }
    New-Item -ItemType Directory -Path (Join-Path $staging 'bootstrap\cache'), (Join-Path $staging 'public'), (Join-Path $staging 'scripts') -Force | Out-Null
    foreach ($filename in @('app.php', 'providers.php')) {
        Copy-Item -LiteralPath (Join-Path $project ('bootstrap\' + $filename)) -Destination (Join-Path $staging 'bootstrap')
    }
    foreach ($item in (Get-ChildItem -LiteralPath (Join-Path $project 'public') -Force)) {
        if ($item.Name -notin @('uploads', 'storage', 'hot', 'build', 'fonts-manifest.dev.json')) {
            Copy-Item -LiteralPath $item.FullName -Destination (Join-Path $staging 'public') -Recurse
        }
    }
    foreach ($filename in @('php.ps1', 'setup-sqlserver.ps1', 'start-app.ps1', 'check-startup.php', 'choose-backup-folder.ps1', 'check-php-runtime.php', 'install-laptop.ps1', 'laptop-setup-functions.ps1', 'prepare-laptop-database.php', 'archive-installer.ps1', 'build-installer-bat.ps1')) {
        Copy-Item -LiteralPath (Join-Path $PSScriptRoot $filename) -Destination (Join-Path $staging 'scripts')
    }
    foreach ($filename in @('artisan', 'composer.json', 'composer.lock', '.env.example', 'start.bat', 'install-laptop.bat', 'INSTALL-LAPTOP.md', 'README.md')) {
        Copy-Item -LiteralPath (Join-Path $project $filename) -Destination $staging
    }
    $adminDirectory = Join-Path $staging 'storage\app\laptop-setup'
    New-Item -ItemType Directory -Path $adminDirectory -Force | Out-Null
    Copy-Item -LiteralPath (Join-Path $project 'storage\app\laptop-setup\admin-account.json') -Destination $adminDirectory
    if ($PrerequisiteDirectory) {
        $packages = Join-Path $staging 'storage\app\laptop-setup\packages'
        New-Item -ItemType Directory -Path $packages -Force | Out-Null
        foreach ($filename in @('SQL2022-SSEI-Expr.exe', 'vc_redist.x64.exe', 'msodbcsql18.msi', 'php-8.4.26-nts-Win32-vs17-x64.zip')) {
            $source = Join-Path $PrerequisiteDirectory $filename
            if (Test-Path -LiteralPath $source) { Copy-Item -LiteralPath $source -Destination $packages }
        }
    }
    $driverArchive = Join-Path $project 'storage\app\sqlserver-setup\Windows_5.13.3RTW.zip'
    if (Test-Path -LiteralPath $driverArchive) {
        $driverTarget = Join-Path $staging 'storage\app\sqlserver-setup'
        New-Item -ItemType Directory -Path $driverTarget -Force | Out-Null
        Copy-Item -LiteralPath $driverArchive -Destination $driverTarget
    }
    if (Test-Path -LiteralPath $zipPath) { Remove-Item -LiteralPath $zipPath -Force }
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    Add-Type -AssemblyName System.IO.Compression
    $zipStream = [IO.File]::Open($zipPath, 'CreateNew')
    $archive = [IO.Compression.ZipArchive]::new($zipStream, [IO.Compression.ZipArchiveMode]::Create, $false)
    try {
        foreach ($file in (Get-ChildItem -LiteralPath $staging -Recurse -File -Force)) {
            $relative = $file.FullName.Substring($staging.Length + 1).Replace('\', '/')
            [IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $file.FullName, $relative, [IO.Compression.CompressionLevel]::Optimal) | Out-Null
        }
    } finally { $archive.Dispose(); $zipStream.Dispose() }
    $zip = [IO.Compression.ZipFile]::OpenRead($zipPath)
    try {
        $entries = @($zip.Entries | ForEach-Object { $_.FullName.Replace('\', '/') })
        foreach ($required in @('install-laptop.bat', 'start.bat', '.env.example', 'public/assets/build/styles.css', 'public/assets/build/app.js', 'scripts/install-laptop.ps1', 'storage/app/laptop-setup/admin-account.json')) {
            if ($entries -notcontains $required) { throw ('Deployment package is missing ' + $required) }
        }
        if ($entries | Where-Object { $_ -match '(^|/)(\.env|auth\.json|database\.sqlite)$|^(\.git|node_modules|vendor)/|^public/uploads/|^storage/(logs|app/(backups|db-recovery))/' }) {
            throw 'A private environment, database, cache, or development directory was included unexpectedly.'
        }
    } finally { $zip.Dispose() }
    Get-Item -LiteralPath $zipPath | Select-Object FullName, Length
} finally {
    $resolvedTarget = (Resolve-Path -LiteralPath $staging).Path
    $expectedParent = (Resolve-Path -LiteralPath (Join-Path $project 'storage\app')).Path.TrimEnd('\') + '\'
    if (-not $resolvedTarget.StartsWith($expectedParent, [StringComparison]::OrdinalIgnoreCase) -or [IO.Path]::GetFileName($resolvedTarget) -notmatch '^laptop-package-[0-9a-f]{32}$') { throw 'Refusing cleanup outside the deployment staging directory.' }
    Remove-Item -LiteralPath $resolvedTarget -Recurse -Force
}
