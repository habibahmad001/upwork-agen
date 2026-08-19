@echo off
REM Profile Analyzer - From HTML
REM ===============================
echo.
echo ============================================
echo   Upwork Profile Analyzer (from HTML)
echo ============================================
echo.
echo How to get your profile HTML:
echo.
echo Option 1 (Recommended):
echo   1. Open Chrome and go to your Upwork profile
echo   2. Press F12 to open DevTools
echo   3. Right-click on the ^<html^> element in Elements tab
echo   4. Select "Copy" -^> "Copy outerHTML"
echo   5. Paste below and press Ctrl+D when done
echo.
echo Option 2:
echo   1. Open Chrome and go to your Upwork profile
echo   2. Right-click -^> "Save Page As" -^> "Webpage, Complete"
echo   3. Open the saved .html file in Notepad
echo   4. Copy everything and paste below
echo.
echo ============================================
echo.
cd /d "%~dp0"
node profile-analyzer-from-html.js
pause
