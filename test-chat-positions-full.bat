@echo off
echo ==========================================
echo Complete Chat Position Testing
echo ==========================================
echo.

echo Step 1: Creating test chats...
echo ==========================================
C:\xampp\php\php.exe create_test_chats.php

echo.
echo Step 2: Testing chat positions...
echo ==========================================
C:\xampp\php\php.exe artisan chats:test-positions

echo.
echo Step 3: Additional position tests...
echo ==========================================
echo Running top-ten rule test...
C:\xampp\php\php.exe artisan chats:test-top-ten

echo.
echo Running replacement logic test...
C:\xampp\php\php.exe artisan chats:test-replacement

echo.
echo Running full system test...
C:\xampp\php\php.exe artisan chats:test-full-system

echo.
echo ==========================================
echo All chat position tests completed!
echo ==========================================
echo.
echo Press any key to exit...
pause > nul 