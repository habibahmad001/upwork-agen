@echo off
echo Stopping Upwork Job Checker...
echo.

taskkill /F /IM node.exe >nul 2>&1

if %errorlevel% == 0 (
    echo Successfully stopped!
) else (
    echo No running crawler found.
)

echo.
pause
