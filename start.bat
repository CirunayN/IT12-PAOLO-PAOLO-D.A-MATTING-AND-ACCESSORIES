@echo off
setlocal
cd /d "%~dp0"
echo Starting Paolo Paolo at http://127.0.0.1:8000
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\start-app.ps1"
if errorlevel 1 pause
