@echo off
:: This script runs the ML server silently in the background
:: Place this in your startup folder or use Task Scheduler

cd /d "C:\xampp\htdocs\sbm\ml"

:: Install requirements if the suggestion ranker's CatBoost dependency is missing
"venv64\Scripts\python.exe" -c "import catboost" >nul 2>&1
if errorlevel 1 (
    "venv64\Scripts\python.exe" -m pip install -r requirements.txt --prefer-binary -q >> "%~dp0start_sbm_ml_silent.log" 2>&1
    if errorlevel 1 exit /b 1
)

"venv64\Scripts\python.exe" -c "import catboost" >nul 2>&1
if errorlevel 1 exit /b 1

:: Run Flask hidden (no console window)
start /min "" "venv64\Scripts\python.exe" app.py

exit
