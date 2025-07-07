@echo off
chcp 65001 >nul
echo 🔍 Отладка парсинга Schedule...
echo.

where php >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP не найден в PATH
    pause
    exit /b 1
)

php debug_schedule_parsing.php
pause 