param(
    [switch] $CheckOnly,
    [string] $InstallUser,
    [string] $DesktopPath
)

$ErrorActionPreference = 'Stop'
$script:ProjectDirectory = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $script:ProjectDirectory
. (Join-Path $PSScriptRoot 'laptop-setup-functions.ps1')
$script:SetupLog = $null
$script:SetupRestartRequired = $false
$setupLock = $null

try {
    if (-not [Environment]::Is64BitOperatingSystem -or -not [Environment]::Is64BitProcess -or $env:PROCESSOR_ARCHITECTURE -ne 'AMD64') {
        throw 'This installer requires 64-bit Intel/AMD Windows 10 or 11 and 64-bit PowerShell. ARM laptops are not supported by this SQL Express package.'
    }
    if ([Environment]::OSVersion.Version.Build -lt 19041) { throw 'Update Windows 10/11 before installing this system (Windows build 19041 or newer is required).' }
    foreach ($requiredFile in @('composer.json', 'composer.lock', '.env.example', 'artisan', 'start.bat', 'public\assets\build\styles.css', 'public\assets\build\app.js')) {
        if (-not (Test-Path -LiteralPath (Join-Path $script:ProjectDirectory $requiredFile))) {
            throw ('Missing project file: ' + $requiredFile + '. Extract the complete project folder before running the installer.')
        }
    }
    if ($CheckOnly) {
        $privatePhp = Join-Path $script:ProjectDirectory 'storage\app\laptop-runtime\php\php.exe'
        $globalPhp = Get-Command php.exe -ErrorAction SilentlyContinue
        Write-Host ('Private PHP: ' + (Test-Path -LiteralPath $privatePhp))
        Write-Host ('Global PHP available: ' + [bool]$globalPhp)
        Write-Host ('ODBC Driver 18 installed: ' + (Get-SetupOdbc18))
        $service = Get-Service -Name 'MSSQL$SQLEXPRESS' -ErrorAction SilentlyContinue
        Write-Host ('SQL Express service: ' + $(if ($service) { $service.Status } else { 'Not installed' }))
        Write-Host ('Application dependencies present: ' + (Test-Path -LiteralPath (Join-Path $script:ProjectDirectory 'vendor\autoload.php')))
        Write-Host ('Environment file present: ' + (Test-Path -LiteralPath (Join-Path $script:ProjectDirectory '.env')))
        Write-Host 'Read-only check completed. No installations or database changes were made.'
        exit 0
    }

    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    if (-not $InstallUser) { $InstallUser = $identity.Name }
    if (-not $DesktopPath) { $DesktopPath = [Environment]::GetFolderPath('DesktopDirectory') }
    $principal = [Security.Principal.WindowsPrincipal]::new($identity)
    if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        # Single-quoted PowerShell literals, encoded as UTF-16, preserve spaces and
        # apostrophes without executing folder names as shell code.
        $scriptLiteral = $PSCommandPath.Replace("'", "''")
        $userLiteral = $InstallUser.Replace("'", "''")
        $desktopLiteral = $DesktopPath.Replace("'", "''")
        $command = "& '$scriptLiteral' -InstallUser '$userLiteral' -DesktopPath '$desktopLiteral'"
        $encoded = [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($command))
        Write-Host 'Windows will ask permission to install the required software.'
        $elevated = Start-Process -FilePath 'powershell.exe' -Verb RunAs -ArgumentList @('-NoProfile', '-ExecutionPolicy', 'Bypass', '-EncodedCommand', $encoded) -Wait -PassThru
        exit $elevated.ExitCode
    }
    $account = [Security.Principal.NTAccount]::new($InstallUser)
    $installSid = $account.Translate([Security.Principal.SecurityIdentifier]).Value
    Write-Host 'This setup installs PHP, Microsoft Visual C++, ODBC Driver 18, SQL Server Express, and application dependencies.'
    Write-Host 'Keep the laptop plugged in and connected to the internet during installation. Daily use works offline.'
    Write-Host 'Existing accounts, records, and the application key are preserved. No demo records are loaded.'
    Write-Host 'Microsoft software terms: https://aka.ms/useterms'
    Write-Host 'PHP license: https://www.php.net/license/ | Composer license: https://getcomposer.org/license/'
    if ((Read-Host 'Type INSTALL to accept the software terms and install on this laptop') -cne 'INSTALL') { throw 'Installation cancelled.' }

    foreach ($directory in @('storage\app', 'storage\framework\cache\data', 'storage\framework\sessions', 'storage\framework\views', 'storage\logs\laptop-install', 'bootstrap\cache')) {
        New-Item -ItemType Directory -Path (Join-Path $script:ProjectDirectory $directory) -Force | Out-Null
    }
    $setupLock = [IO.File]::Open((Join-Path $script:ProjectDirectory 'storage\app\laptop-install.lock'), 'OpenOrCreate', 'ReadWrite', 'None')
    $script:RuntimeDirectory = Join-Path $script:ProjectDirectory 'storage\app\laptop-runtime'
    $script:PackageDirectory = Join-Path $script:ProjectDirectory 'storage\app\laptop-setup\packages'
    New-Item -ItemType Directory -Path $script:RuntimeDirectory, $script:PackageDirectory -Force | Out-Null
    $script:SetupLog = Join-Path $script:ProjectDirectory ('storage\logs\laptop-install\setup-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.log')
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
    $ProgressPreference = 'SilentlyContinue'

    Write-SetupStep 'Preparing writable folders for the laptop operator...'
    Set-LaptopWriteAccess -WindowsSid $installSid

    Write-SetupStep 'Preparing Microsoft Visual C++ runtime...'
    $vc = Get-SetupDownload -Url 'https://aka.ms/vc14/vc_redist.x64.exe' -Path (Join-Path $script:PackageDirectory 'vc_redist.x64.exe') -Microsoft
    Invoke-SetupPackage -Path $vc -Arguments @('/install', '/quiet', '/norestart') -AlsoAccept @(1638)
    Write-SetupStep 'Preparing private PHP (no global PHP settings are changed)...'
    Initialize-PrivatePhp
    Write-SetupStep 'Preparing application configuration and dependencies...'
    Initialize-SetupEnvironment
    Install-ApplicationDependencies

    if (-not (Get-SetupOdbc18)) {
        Write-SetupStep 'Installing Microsoft ODBC Driver 18...'
        $odbc = Get-SetupDownload -Url 'https://download.microsoft.com/download/d624e1c6-293b-4d6f-91b8-6e515a5d6a77/amd64/1033/msodbcsql.msi' -Path (Join-Path $script:PackageDirectory 'msodbcsql18.msi') -Microsoft
        $msiLog = Join-Path $script:ProjectDirectory 'storage\logs\laptop-install\odbc.log'
        Invoke-SetupPackage -Path 'msiexec.exe' -Arguments @('/i', ('"' + $odbc + '"'), '/qn', '/norestart', 'IACCEPTMSODBCSQLLICENSETERMS=YES', '/L*v', ('"' + $msiLog + '"'))
        if (-not (Get-SetupOdbc18)) { throw 'ODBC Driver 18 was not detected after installation.' }
    }
    Write-SetupStep 'Preparing the local SQL Express service...'
    Initialize-SqlExpress
    Write-SetupStep 'Installing PHP SQL Server drivers...'
    & (Join-Path $PSScriptRoot 'setup-sqlserver.ps1')
    if ($LASTEXITCODE -ne 0) { throw 'PHP SQL Server driver setup failed.' }
    $env:PAOLO_INSTALL_WINDOWS_USER = $InstallUser
    Write-SetupStep 'Preparing the local database and access for the laptop user...'
    Invoke-SetupPhp -Arguments @((Join-Path $PSScriptRoot 'prepare-laptop-database.php'))
    Invoke-SetupPhp -Arguments @('artisan', 'package:discover', '--ansi')
    $environment = [IO.File]::ReadAllText((Join-Path $script:ProjectDirectory '.env'))
    $keyMatch = [regex]::Match($environment, '(?m)^APP_KEY[ \t]*=[ \t]*(?<value>[^\r\n]*)')
    $currentKey = $keyMatch.Groups['value'].Value.Trim().Trim([char[]]@('"', "'"))
    if (-not $currentKey) {
        Invoke-SetupPhp -Arguments @('artisan', 'key:generate', '--force')
    }
    Write-SetupStep 'Applying pending database migrations (existing records are retained)...'
    Invoke-SetupPhp -Arguments @('artisan', 'migrate', '--force')
    Write-SetupStep 'Preparing required records and retaining the administrator login when supplied...'
    # This is interactive. Passwords are hidden and are never written to the setup log.
    Invoke-SetupPhp -Arguments @('artisan', 'app:initialize-laptop')
    Invoke-SetupPhp -Arguments @((Join-Path $PSScriptRoot 'check-startup.php'))
    New-LaptopShortcut -DesktopPath $DesktopPath
    Write-SetupStep 'Installation completed successfully.'
    Write-Host ''
    Write-Host 'Use the Paolo Paolo desktop shortcut or start.bat to open the system.' -ForegroundColor Green
    Write-Host 'Keep its launcher open and the laptop awake for automatic backups. The browser can be closed.'
    Write-Host 'This deployment starts with empty business records and keeps only the supplied administrator login. Enter new store data after signing in.'
    Write-Host 'Keep the installed project folder in this location. Move it only by rerunning setup at the new location.'
    Read-Host 'Press Enter to close setup' | Out-Null
    exit 0
} catch {
    Write-Host ('Setup stopped: ' + $_.Exception.Message) -ForegroundColor Red
    if ($script:SetupLog) { Add-Content -LiteralPath $script:SetupLog -Value ('Stopped: ' + $_.Exception.Message) }
    if ($script:SetupRestartRequired) { exit 3010 }
    if (-not $CheckOnly) { Read-Host 'Press Enter to close setup' | Out-Null }
    exit 1
} finally {
    if ($setupLock) { $setupLock.Dispose() }
}
