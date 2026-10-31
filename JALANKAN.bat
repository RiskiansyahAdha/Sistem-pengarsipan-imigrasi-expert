@ECHO OFF
REM BFCPEOPTIONSTART
REM Advanced BAT to EXE Converter www.BatToExeConverter.com
REM BFCPEEXE=
REM BFCPEICON=
REM BFCPEICONINDEX=-1
REM BFCPEEMBEDDISPLAY=0
REM BFCPEEMBEDDELETE=1
REM BFCPEADMINEXE=0
REM BFCPEINVISEXE=0
REM BFCPEVERINCLUDE=0
REM BFCPEVERVERSION=1.0.0.0
REM BFCPEVERPRODUCT=Product Name
REM BFCPEVERDESC=Product Description
REM BFCPEVERCOMPANY=Your Company
REM BFCPEVERCOPYRIGHT=Copyright Info
REM BFCPEWINDOWCENTER=1
REM BFCPEDISABLEQE=0
REM BFCPEWINDOWHEIGHT=30
REM BFCPEWINDOWWIDTH=120
REM BFCPEWTITLE=Window Title
REM BFCPEOPTIONEND
@echo off
title Dashboard Statistik Umur
cd /d "%~dp0"

if not exist "php\php.exe" (
    exit /b 1
)

if not exist ".env" (
    copy /y ".env.offline" ".env" >nul
)

findstr /b /c:"APP_KEY=base64:" ".env" >nul 2>&1
if errorlevel 1 (
    php\php.exe artisan key:generate --force
)

if not exist "database\database.sqlite" (
    type nul > "database\database.sqlite"
)

php\php.exe artisan migrate --force
if errorlevel 1 (
    exit /b 1
)

:: Membuka browser otomatis setelah 2 detik ke halaman dashboard
start "" cmd /c "timeout /t 2 /nobreak >nul & start http://127.0.0.1:8000/dashboard"

:: Menjalankan server Laravel di latar belakang (tanpa perintah pause)
php\php.exe artisan serve --host=127.0.0.1 --port=8000