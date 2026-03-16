@echo off
title SBM ML Server
color 0A

echo ================================================
echo   DepEd SBM - ML Server Auto-Start
echo ================================================
echo.

:: Change to the ml directory
cd /d "C:\xampp\htdocs\sbm\ml"

:: Check if venv exists
if not exist "venv\Scripts\activate.bat" (
    echo [ERROR] Virtual environment not found!
    echo Please run setup first.
    pause
    exit /b 1
)

:: Activate venv
call venv\Scripts\activate.bat

:: Check if Flask is installed
python -c "import flask" 2>nul
if errorlevel 1 (
    echo [INFO] Installing dependencies...
    pip install flask==3.0.3 vaderSentiment==3.3.2 scikit-learn==1.5.1 numpy==1.26.4 pandas==2.2.2 requests==2.32.3 python-dotenv==1.0.1 openai==1.40.0 joblib==1.4.2
)

echo.
echo [OK] Starting ML Server on http://127.0.0.1:5000
echo [OK] Keep this window open while using the system.
echo.

:: Start Flask
python app.py

pause
