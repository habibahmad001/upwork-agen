@echo off
echo Starting Upwork Job Checker (Auto-Start Mode)...
echo.
cd /d D:\nodejsapps\upwork-agen\crawler
start /min cmd /c "node ai-job-checker.js --auto-start"
echo.
echo Job checker is starting in background...
echo It will auto-start checking jobs in 30 seconds.
echo.
echo To stop it: Close the window or press Ctrl+C
echo.
pause
