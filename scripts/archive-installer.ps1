param([string] $ZipPath = $env:PAOLO_INSTALLER_ZIP, [string] $Destination = $env:PAOLO_INSTALLER_DESTINATION, [switch] $ExtractOnly = ($env:PAOLO_INSTALLER_EXTRACT_ONLY -eq '1'))

$ErrorActionPreference = 'Stop'
try {
    $launcherDirectory = if ($env:PAOLO_INSTALLER_FILE) { Split-Path -Parent $env:PAOLO_INSTALLER_FILE } else { Split-Path -Parent $PSScriptRoot }
    if (-not $ZipPath -and -not $ExtractOnly -and (Test-Path -LiteralPath (Join-Path $launcherDirectory 'scripts\install-laptop.ps1'))) {
        & (Join-Path $launcherDirectory 'scripts\install-laptop.ps1')
        exit $LASTEXITCODE
    }
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    if (-not $ZipPath -or -not $Destination) { Add-Type -AssemblyName System.Windows.Forms }
    if (-not $ZipPath) {
        $dialog = New-Object System.Windows.Forms.OpenFileDialog
        $dialog.Title = 'Select your Paolo Paolo system ZIP'
        $dialog.Filter = 'ZIP archives (*.zip)|*.zip'
        $dialog.InitialDirectory = $launcherDirectory
        if ($dialog.ShowDialog() -ne 'OK') { throw 'No ZIP selected.' }
        $ZipPath = $dialog.FileName
    }
    $ZipPath = (Resolve-Path -LiteralPath $ZipPath).Path
    if ([IO.Path]::GetExtension($ZipPath) -ine '.zip') { throw 'Select a ZIP archive.' }
    if (-not $Destination) {
        $dialog = New-Object System.Windows.Forms.FolderBrowserDialog
        $dialog.Description = 'Choose an EMPTY permanent folder for Paolo Paolo. Use New Folder if needed.'
        $dialog.ShowNewFolderButton = $true
        if ($dialog.ShowDialog() -ne 'OK') { throw 'No installation folder selected.' }
        $Destination = $dialog.SelectedPath
    }
    $Destination = [IO.Path]::GetFullPath($Destination).TrimEnd('\')
    if ($Destination.StartsWith('\\') -or $Destination -eq [IO.Path]::GetPathRoot($Destination).TrimEnd('\')) { throw 'Choose a local application folder, not a drive root or network share.' }
    $hash = (Get-FileHash -LiteralPath $ZipPath -Algorithm SHA256).Hash
    $marker = Join-Path $Destination '.laptop-archive.json'
    $resumingExtraction = $false
    if (Test-Path -LiteralPath $Destination) {
        if ((Get-Item -LiteralPath $Destination).Attributes -band [IO.FileAttributes]::ReparsePoint) { throw 'The installation folder cannot be a link.' }
        if (@(Get-ChildItem -LiteralPath $Destination -Force).Count -gt 0) {
            if (-not (Test-Path -LiteralPath $marker) -or (Get-Content -LiteralPath $marker -Raw | ConvertFrom-Json).Hash -ne $hash) {
                throw 'Choose an empty folder. Existing files were not overwritten. To resume, use the same ZIP and its previous destination.'
            }
            $state = Get-Content -LiteralPath $marker -Raw | ConvertFrom-Json
            if ($state.State -notin @('Extracting', 'Ready')) { throw 'The previous extraction state is invalid. Choose an empty folder.' }
            $resumingExtraction = $state.State -eq 'Extracting'
            if (-not $resumingExtraction) {
                if (-not $ExtractOnly) { & (Join-Path $Destination 'scripts\install-laptop.ps1'); exit $LASTEXITCODE }
                Write-Output ('Previously extracted installation preserved: ' + $Destination)
                exit 0
            }
        }
    }
    $archive = [IO.Compression.ZipFile]::OpenRead($ZipPath)
    try {
        $entries = @{}
        foreach ($entry in $archive.Entries) {
            $name = $entry.FullName.Replace('\', '/')
            if ($name.StartsWith('/') -or $name -match '(^|/)\.\.(/|$)|[:\x00]' -or $entries.ContainsKey($name)) { throw 'The ZIP contains an unsafe or duplicate path.' }
            $entries[$name] = $entry
        }
        $prefixes = @($entries.Keys | Where-Object { $_ -match '(^|/)artisan$' } | ForEach-Object { $_.Substring(0, $_.Length - 7) } | Where-Object { $entries.ContainsKey($_ + 'composer.json') -and $entries.ContainsKey($_ + 'scripts/install-laptop.ps1') })
        if ($prefixes.Count -ne 1) { throw 'The ZIP must contain exactly one complete Paolo Paolo project, including scripts/install-laptop.ps1.' }
        $prefix = $prefixes[0]
        $selected = @{}
        $size = 0L
        foreach ($name in $entries.Keys) {
            if (-not $name.StartsWith($prefix, [StringComparison]::OrdinalIgnoreCase)) { continue }
            $relative = $name.Substring($prefix.Length)
            if ($relative.EndsWith('/')) { continue }
            $allowed = $relative -match '^(app|config|resources|routes|scripts)/|^database/(migrations|seeders|factories)/|^public/'
            $allowed = $allowed -or $relative -match '^(artisan|composer\.json|composer\.lock|\.env\.example|start\.bat|install-laptop\.bat|README\.md|INSTALL-LAPTOP\.md)$|^bootstrap/(app|providers)\.php$'
            $allowed = $allowed -or $relative -match '^storage/app/laptop-setup/packages/(SQL2022-SSEI-Expr\.exe|vc_redist\.x64\.exe|msodbcsql18\.msi|php-8\.4\.26-nts-Win32-vs17-x64\.zip)$|^storage/app/sqlserver-setup/Windows_5\.13\.3RTW\.zip$'
            $allowed = $allowed -or $relative -eq 'storage/app/laptop-setup/admin-account.json'
            if (-not $allowed -or $relative -match '^public/(uploads/|storage/|hot$|build/|fonts-manifest\.dev\.json$)|(^|/)(__pycache__|node_modules|\.git)/') { continue }
            $entry = $entries[$name]
            if ((($entry.ExternalAttributes -shr 16) -band 0xF000) -eq 0xA000) { throw 'Linked files cannot be installed from the ZIP.' }
            $selected[$relative] = $entry
            $size += $entry.Length
        }
        foreach ($required in @('artisan', 'composer.json', 'composer.lock', '.env.example', 'scripts/install-laptop.ps1', 'scripts/laptop-setup-functions.ps1', 'public/assets/build/styles.css', 'public/assets/build/app.js', 'start.bat', 'storage/app/laptop-setup/admin-account.json')) {
            if (-not $selected.ContainsKey($required)) { throw ('The ZIP is missing: ' + $required) }
        }
        if ($size -gt 2GB) { throw 'The selected application files exceed the 2 GB installation limit.' }
        New-Item -ItemType Directory -Path $Destination -Force | Out-Null
        @{Hash=$hash; State='Extracting'} | ConvertTo-Json | Set-Content -LiteralPath $marker -Encoding UTF8
        $base = $Destination + '\'
        foreach ($relative in $selected.Keys) {
            $target = [IO.Path]::GetFullPath((Join-Path $Destination $relative))
            if (-not $target.StartsWith($base, [StringComparison]::OrdinalIgnoreCase)) { throw 'A ZIP entry escaped the installation folder.' }
            $ancestor = $target
            while ($ancestor.Length -gt $Destination.Length) {
                if ((Test-Path -LiteralPath $ancestor) -and ((Get-Item -LiteralPath $ancestor -Force).Attributes -band [IO.FileAttributes]::ReparsePoint)) { throw 'An extraction path contains a link. Choose an empty folder.' }
                $ancestor = Split-Path -Parent $ancestor
            }
            New-Item -ItemType Directory -Path (Split-Path -Parent $target) -Force | Out-Null
            $inputStream = $selected[$relative].Open()
            try {
                $outputStream = [IO.File]::Open($target, $(if ($resumingExtraction) { 'Create' } else { 'CreateNew' }))
                try { $inputStream.CopyTo($outputStream) } finally { $outputStream.Dispose() }
            } finally { $inputStream.Dispose() }
        }
        @{Hash=$hash; State='Ready'} | ConvertTo-Json | Set-Content -LiteralPath $marker -Encoding UTF8
    } finally { $archive.Dispose() }
    Write-Output ('System extracted to: ' + $Destination)
    Write-Output 'Old settings, business records, product photos and backups were skipped. Only the prepared administrator login is retained for a fresh database.'
    if (-not $ExtractOnly) { & (Join-Path $Destination 'scripts\install-laptop.ps1'); exit $LASTEXITCODE }
    exit 0
} catch {
    Write-Host ('ZIP setup stopped: ' + $_.Exception.Message) -ForegroundColor Red
    exit 1
}
