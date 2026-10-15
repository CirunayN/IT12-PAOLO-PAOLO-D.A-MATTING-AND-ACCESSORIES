$ErrorActionPreference = 'Stop'

function Write-SetupStep([string] $Message) {
    Write-Host ('[Setup] ' + $Message) -ForegroundColor Cyan
    if ($script:SetupLog) { Add-Content -LiteralPath $script:SetupLog -Value ((Get-Date -Format s) + ' ' + $Message) }
}

function Test-MicrosoftPackage([string] $Path) {
    $signature = Get-AuthenticodeSignature -LiteralPath $Path
    if ($signature.Status -ne 'Valid' -or $signature.SignerCertificate.Subject -notmatch 'O=Microsoft Corporation(?:,|$)') {
        throw ('Microsoft signature verification failed: ' + [IO.Path]::GetFileName($Path))
    }
}

function Get-SetupDownload {
    param([string] $Url, [string] $Path, [string] $Sha256, [switch] $Microsoft, [string] $FallbackUrl)
    if (-not (Test-Path -LiteralPath $Path)) {
        $partial = $Path + '.download' + [IO.Path]::GetExtension($Path)
        Write-SetupStep ('Downloading ' + [IO.Path]::GetFileName($Path) + '...')
        try {
            try { Invoke-WebRequest -UseBasicParsing -Uri $Url -OutFile $partial -TimeoutSec 600 }
            catch {
                if (-not $FallbackUrl) { throw }
                Invoke-WebRequest -UseBasicParsing -Uri $FallbackUrl -OutFile $partial -TimeoutSec 600
            }
            if ($Sha256 -and (Get-FileHash -LiteralPath $partial -Algorithm SHA256).Hash -ne $Sha256) { throw 'Downloaded file checksum did not match.' }
            if ($Microsoft) { Test-MicrosoftPackage $partial }
            Move-Item -LiteralPath $partial -Destination $Path -Force
        } catch {
            if (Test-Path -LiteralPath $partial) { Remove-Item -LiteralPath $partial -Force }
            throw ('Unable to download a verified installer. Connect to the internet and retry. ' + $_.Exception.Message)
        }
    }
    if ($Sha256 -and (Get-FileHash -LiteralPath $Path -Algorithm SHA256).Hash -ne $Sha256) { throw ('Cached file checksum did not match: ' + $Path) }
    if ($Microsoft) { Test-MicrosoftPackage $Path }
    return $Path
}

function Invoke-SetupPackage([string] $Path, [string[]] $Arguments, [int[]] $AlsoAccept = @()) {
    $process = Start-Process -FilePath $Path -ArgumentList $Arguments -WindowStyle Hidden -Wait -PassThru
    if ($process.ExitCode -in @(3010, 1641)) {
        $script:SetupRestartRequired = $true
        throw 'Windows needs a restart to finish installing a prerequisite. Restart the laptop, then run install-laptop.bat again.'
    }
    if ($process.ExitCode -notin (@(0) + $AlsoAccept)) { throw ('Prerequisite installation failed (exit ' + $process.ExitCode + '). See storage\logs\laptop-install and the Microsoft installer logs.') }
}

function Invoke-SetupPhp([string[]] $Arguments) {
    & (Join-Path $script:ProjectDirectory 'scripts\php.ps1') @Arguments
    if ($LASTEXITCODE -ne 0) { throw 'An application setup step failed. Read the message above and run the installer again after correcting it.' }
}

function Get-SetupOdbc18 {
    $drivers = Get-ItemProperty -LiteralPath 'HKLM:\SOFTWARE\ODBC\ODBCINST.INI\ODBC Drivers' -ErrorAction SilentlyContinue
    return ($null -ne $drivers -and $drivers.'ODBC Driver 18 for SQL Server' -eq 'Installed')
}

function Get-SetupText([string] $Url) {
    $body = (Invoke-WebRequest -UseBasicParsing -Uri $Url -TimeoutSec 60).Content
    if ($body -is [byte[]]) { return [Text.Encoding]::UTF8.GetString($body) }
    return [string] $body
}

function Initialize-PrivatePhp {
    $phpDirectory = Join-Path $script:RuntimeDirectory 'php'
    $php = Join-Path $phpDirectory 'php.exe'
    if (-not (Test-Path -LiteralPath $php)) {
        $file = 'php-8.4.26-nts-Win32-vs17-x64.zip'
        $archive = Get-SetupDownload -Url ('https://downloads.php.net/~windows/releases/' + $file) -FallbackUrl ('https://downloads.php.net/~windows/releases/archives/' + $file) -Path (Join-Path $script:PackageDirectory $file) -Sha256 'da68394f9193b7f6b89d0c76861a4034ae10efee7fd55a7255d8118c2acf70d7'
        New-Item -ItemType Directory -Path $phpDirectory -Force | Out-Null
        Expand-Archive -LiteralPath $archive -DestinationPath $phpDirectory -Force
    }
    $certificate = Join-Path $phpDirectory 'cacert.pem'
    if (-not (Test-Path -LiteralPath $certificate)) {
        $checksumText = Get-SetupText 'https://curl.se/ca/cacert.pem.sha256'
        if ($checksumText -notmatch '\A\s*([0-9a-fA-F]{64})\b') { throw 'Unable to verify the HTTPS certificate bundle.' }
        $null = Get-SetupDownload -Url 'https://curl.se/ca/cacert.pem' -Path $certificate -Sha256 $Matches[1]
    }
    $extensionDirectory = (Join-Path $phpDirectory 'ext').Replace('\', '/')
    $certificate = $certificate.Replace('\', '/')
    $configuration = @(
        '[PHP]', ('extension_dir="' + $extensionDirectory + '"'),
        'extension=curl', 'extension=fileinfo', 'extension=gd', 'extension=intl',
        'extension=mbstring', 'extension=openssl', 'extension=pdo_sqlite', 'extension=sqlite3', 'extension=zip',
        'date.timezone=Asia/Manila', 'memory_limit=512M', 'max_execution_time=120',
        'upload_max_filesize=5M', 'post_max_size=30M', 'display_errors=Off', 'log_errors=On',
        'allow_url_fopen=On', ('openssl.cafile="' + $certificate + '"'), ('curl.cainfo="' + $certificate + '"')
    )
    [IO.File]::WriteAllLines((Join-Path $phpDirectory 'php.ini'), $configuration, [Text.UTF8Encoding]::new($false))
    & $php (Join-Path $script:ProjectDirectory 'scripts\check-php-runtime.php')
    if ($LASTEXITCODE -ne 0) { throw 'The private PHP runtime could not start with the required extensions.' }
    $script:PrivatePhp = $php
}

function Initialize-SetupEnvironment {
    $environmentPath = Join-Path $script:ProjectDirectory '.env'
    if (-not (Test-Path -LiteralPath $environmentPath)) {
        $content = [IO.File]::ReadAllText((Join-Path $script:ProjectDirectory '.env.example'))
        $content = $content -replace '(?m)^APP_NAME=.*$', 'APP_NAME="Paolo Paolo"'
        $content = $content -replace '(?m)^APP_ENV=.*$', 'APP_ENV=production'
        $content = $content -replace '(?m)^APP_DEBUG=.*$', 'APP_DEBUG=false'
        $content = $content -replace '(?m)^DB_DATABASE=.*$', 'DB_DATABASE=paolo_paolo_store'
        [IO.File]::WriteAllText($environmentPath, $content, [Text.UTF8Encoding]::new($false))
    }
    # These are generated caches, not source files or database records.
    foreach ($filename in @('config.php', 'packages.php', 'services.php', 'routes-v7.php')) {
        $cacheFile = Join-Path $script:ProjectDirectory ('bootstrap\cache\' + $filename)
        if (Test-Path -LiteralPath $cacheFile) { Remove-Item -LiteralPath $cacheFile -Force }
    }
}

function Install-ApplicationDependencies {
    $composer = Join-Path $script:RuntimeDirectory 'composer.phar'
    if (-not (Test-Path -LiteralPath $composer)) {
        $installer = Join-Path $script:PackageDirectory 'composer-setup.php'
        $expected = (Get-SetupText 'https://composer.github.io/installer.sig').Trim()
        if ($expected -notmatch '\A[0-9a-fA-F]{96}\z') { throw 'The Composer installer checksum is invalid.' }
        Invoke-WebRequest -UseBasicParsing -Uri 'https://getcomposer.org/installer' -OutFile $installer -TimeoutSec 120
        if ((Get-FileHash -LiteralPath $installer -Algorithm SHA384).Hash -ne $expected) { throw 'Composer installer checksum mismatch.' }
        & $script:PrivatePhp $installer ('--install-dir=' + $script:RuntimeDirectory) '--filename=composer.phar' '--quiet'
        if ($LASTEXITCODE -ne 0) { throw 'Unable to prepare Composer.' }
    }
    & $script:PrivatePhp $composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress --no-scripts --no-plugins
    if ($LASTEXITCODE -ne 0) { throw 'Application dependencies could not be installed. Keep the internet connected and retry. See the error above.' }
}

function Initialize-SqlExpress {
    $service = Get-Service -Name 'MSSQL$SQLEXPRESS' -ErrorAction SilentlyContinue
    if (-not $service) {
        $package = Get-SetupDownload -Url 'https://download.microsoft.com/download/29654887-7cde-4397-bba3-d7f087970845/SQL2022-SSEI-Expr.exe' -Path (Join-Path $script:PackageDirectory 'SQL2022-SSEI-Expr.exe') -Sha256 'E2B8E8AAD30D8B1A1922C6C0976C806F0A2E08D04B2DE100A9D70B9F94750BB1' -Microsoft
        Write-SetupStep 'Installing SQL Server Express. Its download and installation may take several minutes...'
        Invoke-SetupPackage -Path $package -Arguments @('/IACCEPTSQLSERVERLICENSETERMS', '/ENU', '/ACTION=Install', '/quiet', ('/InstallPath="' + (Join-Path $env:ProgramFiles 'Microsoft SQL Server') + '"'))
        $service = Get-Service -Name 'MSSQL$SQLEXPRESS' -ErrorAction SilentlyContinue
        if (-not $service) { throw 'SQLEXPRESS was not created. Check the Microsoft SQL Server Setup Bootstrap logs, then rerun this installer.' }
    }
    Set-Service -Name 'MSSQL$SQLEXPRESS' -StartupType Automatic
    if ($service.Status -ne 'Running') { Start-Service -Name 'MSSQL$SQLEXPRESS' }
    (Get-Service -Name 'MSSQL$SQLEXPRESS').WaitForStatus('Running', [TimeSpan]::FromSeconds(60))
}

function New-LaptopShortcut([string] $DesktopPath) {
    if (-not $DesktopPath -or -not (Test-Path -LiteralPath $DesktopPath -PathType Container)) {
        Write-Warning 'Desktop shortcut could not be created. Use start.bat in the project folder.'
        return
    }
    $shell = New-Object -ComObject WScript.Shell
    $shortcut = $shell.CreateShortcut((Join-Path $DesktopPath 'Paolo Paolo.lnk'))
    $shortcut.TargetPath = Join-Path $script:ProjectDirectory 'start.bat'
    $shortcut.WorkingDirectory = $script:ProjectDirectory
    $shortcut.Description = 'Start the offline Paolo Paolo system and automatic backups'
    $shortcut.Save()
}

function Set-LaptopWriteAccess([string] $WindowsSid) {
    foreach ($directory in @('storage', 'bootstrap\cache', 'public\uploads')) {
        $target = Join-Path $script:ProjectDirectory $directory
        New-Item -ItemType Directory -Path $target -Force | Out-Null
        # Grant only the intended operator the access needed by logs, compiled
        # views, product photos, runtime configuration, and local backups.
        & icacls.exe $target '/grant' ('*' + $WindowsSid + ':(OI)(CI)M') | Out-Null
        if ($LASTEXITCODE -ne 0) { throw ('Unable to prepare writable application folder: ' + $directory) }
    }
}
