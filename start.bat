@echo off
setlocal
cd /d "%~dp0"
echo Starting Paolo Paolo at http://127.0.0.1:8000
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\php.ps1" artisan serve --host=127.0.0.1 --port=8000 --no-reload
if errorlevel 1 pause
