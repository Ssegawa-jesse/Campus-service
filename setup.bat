@echo off
REM ==================================================================
REM  Pearl360 one-click setup for Windows / XAMPP
REM  1. Start MySQL in the XAMPP Control Panel
REM  2. Double-click this file
REM ==================================================================
cd /d "%~dp0"
title Pearl360 setup

where php >nul 2>nul
if errorlevel 1 (
    if exist "C:\xampp\php\php.exe" (
        set "PATH=C:\xampp\php;%PATH%"
    ) else (
        echo PHP was not found. Install XAMPP or add PHP to your PATH.
        pause
        exit /b 1
    )
)

if not exist "vendor\autoload.php" (
    echo The vendor folder is missing. Running composer install...
    call composer install --no-interaction
    if errorlevel 1 (
        echo composer install failed. Install Composer from https://getcomposer.org or use the "ready" zip that includes vendor.
        pause
        exit /b 1
    )
)

echo.
echo === Checking and fixing settings (.env, app key, database) ===
php artisan pearl360:doctor --fix
if errorlevel 1 (
    echo.
    echo Please fix the problems marked with X above, then run setup.bat again.
    pause
    exit /b 1
)

php artisan config:clear >nul

echo.
set /p RESET="Create tables and load the demo data? This ERASES existing data. (Y/N): "
if /i "%RESET%"=="Y" (
    php artisan migrate:fresh --seed --force
) else (
    php artisan migrate --force
)

echo.
echo ==================================================================
echo  Pearl360 is ready:  http://localhost:8000
echo  Admin login:  2400725284@mubs.ac.ug   password: Pearl360@2026
echo  Keep this window open while you use the site. Close it to stop.
echo ==================================================================
start "" http://localhost:8000
php artisan serve
