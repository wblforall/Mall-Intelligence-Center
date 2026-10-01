@echo off
rem ====================================================================
rem  WBL AI Monitor - Pemasang sekali-klik
rem  Klik-kanan file ini > "Run as administrator".
rem  (Jika diklik dua kali biasa, ia akan minta izin admin sendiri.)
rem ====================================================================
cd /d "%~dp0"

rem --- Pastikan jalan sebagai administrator; jika belum, minta elevasi. ---
net session >nul 2>&1
if errorlevel 1 (
    echo Meminta izin administrator...
    powershell -NoProfile -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b
)

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0pasang-klik.ps1"

echo.
pause
