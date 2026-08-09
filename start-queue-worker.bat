@echo off
echo Starting Laravel Queue Worker...
echo.
echo This will process jobs in the background (AI evaluation, notifications, etc.)
echo Press Ctrl+C to stop
echo.
cd /d D:\nodejsapps\upwork-agen
php artisan queue:work --tries=3 --timeout=60 --sleep=3 --max-jobs=0
pause
