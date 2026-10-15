$ErrorActionPreference = 'Stop'
$repository = Split-Path -Parent $PSScriptRoot
. (Join-Path $repository 'scripts\laptop-setup-functions.ps1')
$script:SetupLog = $null
$temporaryRoot = Join-Path ([IO.Path]::GetTempPath()) ('paolo-laptop-script-' + [guid]::NewGuid().ToString('N'))
$script:ProjectDirectory = $temporaryRoot
$checks = 0
function Assert-Setup([bool] $Condition, [string] $Message) {
    if (-not $Condition) { throw $Message }
    $script:checks++
}
try {
    New-Item -ItemType Directory -Path (Join-Path $temporaryRoot 'bootstrap\cache') -Force | Out-Null
    [IO.File]::WriteAllText((Join-Path $temporaryRoot '.env.example'), "APP_NAME=Laravel`nAPP_ENV=local`nAPP_KEY=`nAPP_DEBUG=true`nDB_DATABASE=it12`n")
    Initialize-SetupEnvironment
    $fresh = [IO.File]::ReadAllText((Join-Path $temporaryRoot '.env'))
    Assert-Setup ($fresh -match 'APP_ENV=production') 'Fresh setup must use production mode.'
    Assert-Setup ($fresh -match 'APP_DEBUG=false') 'Fresh setup must disable debug output.'
    Assert-Setup ($fresh -match 'DB_DATABASE=paolo_paolo_store') 'Fresh setup must use a new shop database instead of the developer database.'
    $keyMatch = [regex]::Match($fresh, '(?m)^APP_KEY[ \t]*=[ \t]*(?<value>[^\r\n]*)')
    Assert-Setup ($keyMatch.Groups['value'].Value -eq '') 'Empty APP_KEY must not consume the next line.'
    $existing = "APP_KEY=base64:private-existing-key`r`nDB_DATABASE=existing_store`r`nAPP_DEBUG=false`r`n"
    [IO.File]::WriteAllText((Join-Path $temporaryRoot '.env'), $existing)
    [IO.File]::WriteAllText((Join-Path $temporaryRoot 'bootstrap\cache\config.php'), 'generated cache')
    Initialize-SetupEnvironment
    Assert-Setup ([IO.File]::ReadAllText((Join-Path $temporaryRoot '.env')) -ceq $existing) 'Existing environment and encryption key must remain unchanged.'
    Assert-Setup (-not (Test-Path -LiteralPath (Join-Path $temporaryRoot 'bootstrap\cache\config.php'))) 'A stale generated config cache must be cleared.'
    $cached = Join-Path $temporaryRoot 'cached.zip'
    [IO.File]::WriteAllText($cached, 'verified fixture')
    $hash = (Get-FileHash -LiteralPath $cached -Algorithm SHA256).Hash
    $result = Get-SetupDownload -Url 'https://example.invalid/do-not-request' -Path $cached -Sha256 $hash
    Assert-Setup ($result -eq $cached) 'An intact cached package must not require a network download.'
    $rejected = $false
    try { $null = Get-SetupDownload -Url 'https://example.invalid/do-not-request' -Path $cached -Sha256 ('0' * 64) } catch { $rejected = $true }
    Assert-Setup $rejected 'A modified cached package must be rejected.'
    $unsigned = Join-Path $temporaryRoot 'unsigned.exe'
    [IO.File]::WriteAllText($unsigned, 'This is not a signed Microsoft installer.')
    $rejected = $false
    try { Test-MicrosoftPackage $unsigned } catch { $rejected = $true }
    Assert-Setup $rejected 'Unsigned prerequisite installers must be rejected.'
    Write-Output ($checks.ToString() + ' installer safety checks passed.')
} finally {
    if (Test-Path -LiteralPath $temporaryRoot) {
        $resolvedTarget = (Resolve-Path -LiteralPath $temporaryRoot).Path
        $parent = [IO.Path]::GetFullPath([IO.Path]::GetTempPath()).TrimEnd('\') + '\'
        if (-not $resolvedTarget.StartsWith($parent, [StringComparison]::OrdinalIgnoreCase) -or [IO.Path]::GetFileName($resolvedTarget) -notmatch '^paolo-laptop-script-[0-9a-f]{32}$') { throw 'Refusing cleanup outside the temporary test directory.' }
        Remove-Item -LiteralPath $resolvedTarget -Recurse -Force
    }
}
