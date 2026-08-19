@echo off
REM Profile Analyzer with Cookie Setup
REM =====================================

echo.
echo ============================================
echo   Upwork Profile Analyzer
echo ============================================
echo.
echo Step 1: Cookie Setup
echo --------------------
echo If you haven't set up cookies yet:
echo   1. Install EditThisCookie extension in Chrome
echo   2. Login to Upwork in Chrome
echo   3. Go to your profile page
echo   4. Click EditThisCookie icon -^> Export -^> Copy to clipboard
echo   5. Run: node playwright/setup-cookies.js
echo   6. Paste the cookies and press Enter twice
echo.
echo Step 2: Run Profile Analyzer
echo -----------------------------
echo.
cd /d "%~dp0"
node profile-analyzer.js %1
pause
