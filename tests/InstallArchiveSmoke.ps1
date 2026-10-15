$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression
$repository = Split-Path -Parent $PSScriptRoot
$temporaryRoot = Join-Path ([IO.Path]::GetTempPath()) ("paolo-archive-" + [guid]::NewGuid().ToString('N') + " owner's laptop")
$checks = 0
function Assert-Archive([bool] $Condition, [string] $Message) {
    if (-not $Condition) { throw $Message }
    $script:checks++
}
function New-FixtureZip([string] $Path, [string[]] $Extra = @(), [switch] $WithoutAdmin) {
    $archive = [IO.Compression.ZipFile]::Open($Path, [IO.Compression.ZipArchiveMode]::Create)
    try {
        $files = @('artisan', 'composer.json', 'composer.lock', '.env.example', 'start.bat', 'scripts/install-laptop.ps1', 'scripts/laptop-setup-functions.ps1', 'public/assets/build/styles.css', 'public/assets/build/app.js', 'app/Models/Product.php', '.env', 'vendor/autoload.php', 'node_modules/private.js', 'storage/app/backups/private.json', 'bootstrap/cache/config.php', 'database/database.sqlite', 'public/uploads/products/old-product.jpg', 'storage/app/laptop-setup/admin-account.json', 'storage/app/laptop-setup/initialized.json')
        foreach ($name in $files) {
            if ($WithoutAdmin -and $name -eq 'storage/app/laptop-setup/admin-account.json') { continue }
            $entry = $archive.CreateEntry('Whole System/' + $name)
            $writer = [IO.StreamWriter]::new($entry.Open())
            try { $writer.Write('fixture') } finally { $writer.Dispose() }
        }
        foreach ($name in $Extra) { $null = $archive.CreateEntry($name) }
    } finally { $archive.Dispose() }
}
function Invoke-Archive([string] $Zip, [string] $Target, [switch] $Bat) {
    $env:PAOLO_INSTALLER_EXTRACT_ONLY = '1'
    $env:PAOLO_INSTALLER_NO_PAUSE = '1'
    $env:PAOLO_INSTALLER_DESTINATION = $Target
    $env:PAOLO_INSTALLER_ZIP = $Zip
    $env:PAOLO_INSTALLER_FILE = Join-Path $temporaryRoot 'install-laptop.bat'
    if ($Bat) {
        Push-Location -LiteralPath $temporaryRoot
        try { $result = & cmd.exe /d /c 'install-laptop.bat payload.zip' 2>&1 } finally { Pop-Location }
    } else {
        $result = & powershell.exe -NoProfile -ExecutionPolicy Bypass -File (Join-Path $repository 'scripts/archive-installer.ps1') 2>&1
    }
    return @{Code=$LASTEXITCODE; Output=($result -join "`n")}
}
try {
    New-Item -ItemType Directory -Path $temporaryRoot -Force | Out-Null
    $zip = Join-Path $temporaryRoot 'payload.zip'
    New-FixtureZip $zip
    Copy-Item -LiteralPath (Join-Path $repository 'install-laptop.bat') -Destination $temporaryRoot
    $target = Join-Path $temporaryRoot 'Installed App'
    $result = Invoke-Archive $zip $target -Bat
    Assert-Archive ($result.Code -eq 0) ('Standalone BAT failed: ' + $result.Output)
    Assert-Archive (Test-Path -LiteralPath (Join-Path $target 'app/Models/Product.php')) 'Nested project source was not extracted.'
    Assert-Archive (Test-Path -LiteralPath (Join-Path $target 'storage/app/laptop-setup/admin-account.json')) 'The prepared administrator login must be retained.'
    foreach ($private in @('.env', 'vendor', 'node_modules', 'storage/app/backups', 'bootstrap/cache/config.php', 'database/database.sqlite', 'public/uploads', 'storage/app/laptop-setup/initialized.json')) {
        Assert-Archive (-not (Test-Path -LiteralPath (Join-Path $target $private))) ('Private or machine-specific file extracted: ' + $private)
    }
    [IO.File]::WriteAllText((Join-Path $target '.env'), 'keep new laptop settings')
    $result = Invoke-Archive $zip $target
    Assert-Archive ($result.Code -eq 0 -and [IO.File]::ReadAllText((Join-Path $target '.env')) -eq 'keep new laptop settings') 'Resume must preserve new laptop settings.'
    $occupied = Join-Path $temporaryRoot 'Existing Folder'
    New-Item -ItemType Directory -Path $occupied | Out-Null
    [IO.File]::WriteAllText((Join-Path $occupied 'keep.txt'), 'keep')
    $result = Invoke-Archive $zip $occupied
    Assert-Archive ($result.Code -ne 0 -and -not (Test-Path -LiteralPath (Join-Path $occupied 'artisan'))) 'Nonempty unrelated folders must not be overwritten.'
    $missingAdmin = Join-Path $temporaryRoot 'missing-admin.zip'
    New-FixtureZip $missingAdmin -WithoutAdmin
    $missingTarget = Join-Path $temporaryRoot 'Missing Admin'
    $result = Invoke-Archive $missingAdmin $missingTarget
    Assert-Archive ($result.Code -ne 0 -and -not (Test-Path -LiteralPath $missingTarget)) 'A ZIP without the retained administrator must stop before extraction.'
    foreach ($unsafe in @('../escaped.txt', 'Whole System/ARTISAN', 'C:/escaped.txt')) {
        $badZip = Join-Path $temporaryRoot ([guid]::NewGuid().ToString('N') + '.zip')
        New-FixtureZip $badZip @($unsafe)
        $badTarget = Join-Path $temporaryRoot ([guid]::NewGuid().ToString('N'))
        $result = Invoke-Archive $badZip $badTarget
        Assert-Archive ($result.Code -ne 0 -and -not (Test-Path -LiteralPath $badTarget)) ('Unsafe ZIP accepted: ' + $unsafe)
    }
    Write-Output ($checks.ToString() + ' ZIP installer checks passed, including the standalone BAT.')
} finally {
    foreach ($name in @('PAOLO_INSTALLER_FILE', 'PAOLO_INSTALLER_ZIP', 'PAOLO_INSTALLER_DESTINATION', 'PAOLO_INSTALLER_EXTRACT_ONLY', 'PAOLO_INSTALLER_NO_PAUSE')) { [Environment]::SetEnvironmentVariable($name, $null, 'Process') }
    if (Test-Path -LiteralPath $temporaryRoot) {
        $resolvedTarget = (Resolve-Path -LiteralPath $temporaryRoot).Path
        $parent = [IO.Path]::GetFullPath([IO.Path]::GetTempPath()).TrimEnd('\') + '\'
        if (-not $resolvedTarget.StartsWith($parent, [StringComparison]::OrdinalIgnoreCase) -or [IO.Path]::GetFileName($resolvedTarget) -notmatch "^paolo-archive-[0-9a-f]{32} owner's laptop$") { throw 'Refusing cleanup outside the ZIP test folder.' }
        Remove-Item -LiteralPath $resolvedTarget -Recurse -Force
    }
}
