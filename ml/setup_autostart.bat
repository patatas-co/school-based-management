@echo off
title SBM ML - Task Scheduler Setup
color 0B

echo ================================================
echo   Setting up Auto-Start on Windows Boot
echo ================================================
echo.
echo This will register the ML server to start
echo automatically every time Windows starts.
echo.
echo [!] Run this as Administrator!
echo.
pause

:: Register the task in Windows Task Scheduler
schtasks /create /tn "SBM_ML_Server" /tr "C:\xampp\htdocs\sbm\ml\start_sbm_ml_silent.bat" /sc onlogon /rl highest /f

if errorlevel 1 (
    echo.
    echo [ERROR] Failed. Make sure you right-clicked and chose
    echo         "Run as Administrator"
    pause
    exit /b 1
)

echo.
echo [OK] Task registered successfully!
echo [OK] The ML server will now start automatically on login.
echo.
echo To remove auto-start later, run:
echo   schtasks /delete /tn "SBM_ML_Server" /f
echo.
pause
