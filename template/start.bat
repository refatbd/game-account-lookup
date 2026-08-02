@echo off
setlocal
cd /d "%~dp0\.."
where php >nul 2>nul
if errorlevel 1 (
  echo PHP 8.1 or newer is required and must be available in PATH.
  exit /b 1
)
echo Game Account Lookup tester: http://127.0.0.1:8080
php -S 127.0.0.1:8080 -t template
