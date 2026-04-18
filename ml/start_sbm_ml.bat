@echo off
title SBM ML Server
color 0A

echo ================================================
echo   DepEd SBM - ML Server Auto-Start
echo ================================================
echo.

:: Change to the ml directory
cd /d "C:\xampp\htdocs\sbm\ml"

:: Check if 64-bit venv exists (required for scikit-learn)
if not exist "venv64\Scripts\activate.bat" (
    echo [ERROR] 64-bit virtual environment not found!
    echo Run this to create it:
    echo   C:\Users\Pat\AppData\Local\Programs\Python\Python311\python.exe -m venv venv64
    echo   venv64\Scripts\pip install -r requirements.txt --prefer-binary
    pause
    exit /b 1
)

:: Activate 64-bit venv (supports scikit-learn Decision Tree models)
call venv64\Scripts\activate.bat

:: Check if Flask is installed
python -c "import flask" 2>nul
if errorlevel 1 (
    echo [INFO] Installing dependencies into venv64...
    pip install -r requirements.txt --prefer-binary -q
)

:: Verify sklearn is available (needed for Decision Tree classifiers)
python -c "import sklearn" 2>nul
if errorlevel 1 (
    echo [ERROR] scikit-learn not found in venv64!
    echo Run: venv64\Scripts\pip install scikit-learn --prefer-binary
    pause
    exit /b 1
)

echo.
echo [OK] Starting ML Server on http://127.0.0.1:5001 (64-bit Python + Decision Tree ML)
echo [OK] Keep this window open while using the system.
echo.

:: Start Flask
python app.py

pause
