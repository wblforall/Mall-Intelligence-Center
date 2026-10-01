@echo off
rem ====================================================================
rem  WBL AI Monitor - Pencopot sekali-klik (hanya IT)
rem  Klik-kanan > "Run as administrator". Akan meminta password copot
rem  yang hanya diketahui IT (diatur dari dashboard MIC).
rem ====================================================================
cd /d "%~dp0"

net session >nul 2>&1
if errorlevel 1 (
    echo Meminta izin administrator...
    powershell -NoProfile -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b
)

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0copot.ps1"

echo.
pause
