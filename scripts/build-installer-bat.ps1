$ErrorActionPreference = 'Stop'
$project = Split-Path -Parent $PSScriptRoot
$header = @'
@echo off
setlocal
set "PAOLO_INSTALLER_FILE=%~f0"
set "PAOLO_INSTALLER_ZIP=%~1"
echo Paolo Paolo - Laptop Installation
if /I "%~1"=="-CheckOnly" (
    powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\install-laptop.ps1" -CheckOnly
) else (
    powershell.exe -NoProfile -ExecutionPolicy Bypass -STA -Command "$s=[IO.File]::ReadAllText($env:PAOLO_INSTALLER_FILE); $m=':__'+'POWERSHELL_PAYLOAD__'; & ([scriptblock]::Create($s.Substring($s.IndexOf($m)+$m.Length)))"
)
set "SETUP_EXIT_CODE=%ERRORLEVEL%"
echo.
if "%SETUP_EXIT_CODE%"=="0" (
    echo Setup finished. Use the Paolo Paolo desktop shortcut or start.bat.
) else if "%SETUP_EXIT_CODE%"=="3010" (
    echo Restart the laptop, then run this installer again.
) else (
    echo Setup stopped. Read the message above before retrying.
)
if not "%PAOLO_INSTALLER_NO_PAUSE%"=="1" pause
exit /b %SETUP_EXIT_CODE%
:__POWERSHELL_PAYLOAD__
'@
$payload = [IO.File]::ReadAllText((Join-Path $PSScriptRoot 'archive-installer.ps1'))
$content = $header.TrimEnd() + "`r`n" + $payload
[IO.File]::WriteAllText((Join-Path $project 'install-laptop.bat'), $content, [Text.UTF8Encoding]::new($false))
Write-Output 'Self-contained install-laptop.bat generated.'
