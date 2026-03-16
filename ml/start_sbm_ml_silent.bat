@echo off
:: This script runs the ML server silently in the background
:: Place this in your startup folder or use Task Scheduler

cd /d "C:\xampp\htdocs\sbm\ml"

:: Run Flask hidden (no console window)
start /min "" "venv\Scripts\python.exe" app.py

exit
